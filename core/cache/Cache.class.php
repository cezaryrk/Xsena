<?php
namespace Xsena\core\cache;

require_once __DIR__ . '/../logging/Logger.class.php';
require_once __DIR__ . '/../helper/Serializer.class.php';
require_once __DIR__ . '/../helper/Options.class.php';

use Xsena\core\helper\Options;
use Xsena\core\logging\Logger;
use Xsena\core\helper\Serializer;

interface CacheInterface {

  public function get($cid, $bin = NULL);

  public function getMultiple($cids, $bin = NULL);

  public function set($cid, $data, $bin = NULL, $expire = NULL);
}

interface Cacheable {
}

interface SystemCacheable extends Cacheable {
}

class Cache {

  public $options = NULL;
    
  public $name = '';
  
  public $prefix = '';

  public $defaultBin = 'cache';

  public $defaultHandler = 'Xsena\core\cache\DefaultFileCache';

  public $handler = array();

  public $bins = array();

  public $activeHandler = array();
  
  public function __construct(Options $settings = null) {
    $this->options = new Options($this->defaultSettings());
     
    
    if ($settings) {
      $this->options->extend($settings);  
    }
    
    $prefix = $this->options->get('prefix');
    if($prefix){
    	$this->prefix = $prefix;
    }

    $name = $this->options->get('name');
    if($name){
    	$this->name = $name;
    }
  }
  
  // TODO Fallback to default handler!
  private function handler($name) {
  	if(isset($this->activeHandler[$name])){
      return $this->activeHandler[$name];
  	}else{  	  
  	  $options = $this->options->select('handler.' . $name);
  	  $options->set('name',$this->name);
  	  $classname = $options->get('handler.class');
  	  if(is_array($classname)){
  	    // TODO fix this bug 
  	    /*
ARRAY: Array
(
    [0] => Xsena\core\cache\DefaultFileCache
    [1] => dfw\core\DrupalCache
)
  	     */
  	  	$classname = array_pop($classname);
  	  }
  	  Logger::log($classname);  	  
  	  $this->activeHandler[$name] = new $classname($options);
  	  return $this->activeHandler[$name];
  	}
  }

  
  private function defaultSettings() {
    return array(
      'handler' => array(
        'default' => array(       
          'handler' => array(
            'class' => 'Xsena\core\cache\DefaultFileCache'            
          ),
          'serializer' => 'Serializer',
        )
      ),
      'bins' => array(
        'cache' => 'default'
      )
    );
  }

  
  private function &handlerForBin($bin = null){    
    $name = $this->options->get('bins.'.$bin);
    $handler = $this->handler($name);
  	return $handler;
  }
  

  public function get($cid, $bin = NULL) {
    $_bin = $this->saniterizeBin($bin);
    $cid = $this->saniterizeKey($cid);
    Logger::log($bin . ' ' . $cid);
    return $this->handlerForBin($bin)->get($cid, $_bin);
  }

  public function getMultiple($cids, $bin = NULL) {}

  public function set($cid, $data, $bin = NULL, $expire = NULL) {
    $_bin = $this->saniterizeBin($bin);
    $cid = $this->saniterizeKey($cid);
    Logger::log($bin . ' ' . $cid);
    return $this->handlerForBin($bin)->set($cid, $data, $_bin, $expire);
  }

  private function saniterizeKey($cid){
  	return preg_replace('![^\w]!', '-', $cid);
  } 
  
  private function saniterizeBin(&$bin){
    $_bin = '';
    
    if($this->prefix){
      $_bin .= $this->prefix;	
    }
    
    if(!empty($_bin)){
      $_bin .= '_';
    }
    
    if($bin){      
      $_bin .= $bin;
    }else{
      $bin = 'cache';
      $_bin .= 'cache';      
    }
      
    return $_bin;
  }
  
  public function finalize() {}
}

class CacheObject {
  
  public $cid;
	
  public $data;
  
  public $serialized;
  
  public $expire;
}

class DefaultFileCache implements CacheInterface {

  private $options;
  
  private $cacheRootDirectory = '/tmp/xsena';

  public function __construct(Options $options = null) {
    $this->options = $options;
    
    $name = $this->options->get('name');
    if($name){
      $name = preg_replace('!\\|/!', '_' , $name);
      $this->cacheRootDirectory = $this->cacheRootDirectory . '/' . $name;
    }
    
    if(!file_exists($this->cacheRootDirectory)){
    	mkdir($this->cacheRootDirectory,0777,true);
    }
  }

  
  public function get($cid, $bin = NULL) {
    $path = $this->cacheRootDirectory . '/' . $bin;
    if(!file_exists($path)){
      mkdir($path,0777,true);
    }
    
    $path .= '/' . $cid;
    if(file_exists($path)){
    	$data = file_get_contents($path);
    	return Serializer::unserialize($data);
    }
    return null;    
  }

  
  public function getMultiple($cids, $bin = NULL) {}

  
  public function set($cid, $data, $bin = NULL, $expire = NULL) {
    $path = $this->cacheRootDirectory . '/' . $bin;
    if(!file_exists($path)){
      mkdir($path,0777,true);
    }
    
    $path .= '/' . $cid;
    $serialized =  Serializer::serialize($data);
    
    file_put_contents($path, $serialized);
    @chmod($path, 0777);
  }
}