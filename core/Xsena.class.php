<?php


require_once 'logging/Logger.class.php';
require_once 'helper/Options.class.php';
require_once 'cache/Cache.class.php';
require_once 'classes/ClassLoader.class.php';

$utils = glob(__DIR__ . '/utils/*.class.php');
foreach ($utils as $file) {
  require_once $file;
}


use Xsena\core\logging\Logger;
use Xsena\core\classes\ClassDescriptor;
use Xsena\core\helper\Options;
use Xsena\core\cache\Cache;

Logger::setLogFile("/tmp/_drupal_WIZ.log");


class Xsena {

  static $instance;
      
  static $ROOT_DIR = __DIR__;

  private $name = 'default';
  
  private $classLoader;

  private $cacheController = null;

  private $settings = array();

  public function __construct($settings) {
    
    $this->settings = new Options($settings);      

    $name = $this->settings->get('name');
    if($name){
    	$this->name = $name; 
    }else{
    	throw new \Exception('Xsena instance needs a name!');
    }
    
  
    $this->initCache();
    
    $this->initClassLoader();
  }

  
  private function initClassLoader(){
    
    $controller = 'Xsena\core\classes\ClassLoader';
    $options = $this->settings->select('classloader');    
    
    $classPaths = array(array('path' => __DIR__));
    
    if($options){
      $_controller = $options->get('controller');
      if($_controller && is_subclass_of($_controller, $controller)){
        $controller = $_controller;
      }
      
      $addtional_paths = $options->get('extend');
      
      foreach($addtional_paths as $entry){
      	$classPaths[] = $entry;
      }      
    }

//     Logger::log("Init classLoader " . $controller);
//     Logger::log($classPaths);
    
    $classLoader = $this->cache()->get($controller);
    $rebuild = false;
    if($classLoader){
      $this->classLoader = $classLoader;
      $rebuild = $this->classLoader->classpaths($classPaths);
    }else{
      $this->classLoader = new $controller($options);
      $rebuild = $this->classLoader->classpaths($classPaths);
    }
    
    if($rebuild){
      $this->cache()->set($controller, $this->classLoader);
    }
  }
  
  private function initCache(){
//     Logger::log("Init cache");
    $controller = 'Xsena\core\cache\Cache';
    $options = $this->settings->select('cache');    
    if($options){            
      $_controller = $options->get('controller');    
      if($_controller && is_subclass_of($_controller, $controller)){
        $controller = $_controller;
      }
    }else{
    	$options = new Options();
    }
    $options->set('name', $this->name);
    
    $this->cacheController = new $controller($options);
  }
  
  /**
   * @return Cache
   */
  public function cache(){
  	return $this->cacheController;
  }

  
  static function &getInstance($settings = array()) {
    if (! isset(self::$instance)) {
      $cls = __CLASS__;
      self::$instance = new $cls($settings);
    }
    return self::$instance;
  }
}