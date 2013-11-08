<?php

namespace Xsena\core\classes;

use Xsena\core\utils\StringUtil;

/**
 * xfClass.class.php
 *
 * Contains xfClass
 */
class ClassDescriptor  {

  const T_CLASS = 0;
  const T_INTERFACE = 1;

  /**
   * @access public
   * @var string contains, whether we have a class or an interface (mostly you identify this by a part of the filename)
   */
  public $type = self::T_CLASS;


  public $namespace = NULL;
  
  /**
   * @access public
   * @var string contains the name of the class (e.g. xfTheme, Filecache) (mostly you identify this by a part of the filename)
   */
  public $className = null;

  /**
   * extends
   * @var unknown_type
   */
  public $superClassName = null;

  /**
   * extends
   * @var unknown_type
   */
  public $interfaces = array();



  public $location = null;

  /**
   * @access public
   * @var string
   */
  public $hash = null;


  public function __construct($obj = null){
    if($obj != null){
    	if(is_object($obj)){
    		$obj = (array)$obj;
    	}
    	$this->applyDefinition($obj);
    }
  }

  public function applyDefinition(array $def){
    foreach($def as $k=>$v){
      $method_name = 'set' . StringUtil::cc($k);
      if(method_exists($this, $method_name)){
        $this->{$method_name}(trim($v));
      }else{
        throw new Exception("wrong key " . $key);
      }
    }
  }

  /**
   *
   * Enter description here ...
   * @var unknown_type
   */
  public $properties = array();

  public $methods = array();

  public function &addProperty(xfClassProperty $property){
    return $this->addClassMember($property);
  }

  public function &addMethod(xfClassMethod &$method){
    return $this->addClassMember($method);
  }

  private function &addClassMember(xfClassMember $member){
    $localName = $member->getLocalName();
    if($member instanceof xfClassProperty){
      $this->properties[$localName] =& $member;
    }else if($member instanceof xfClassMethod){
      $this->methods[$localName] =& $member;
    }else{
      // TODO Class Exception
      throw new Exception("no class member $localName");
    }
    $member->setClass($this);
    return $member;
  }


  function &getProperties(){
    return $this->properties;
  }

  function &getMethods(){
    return $this->methods;
  }
  
  
  function getNamespace(){
  	return $this->namespace;
  }
    
  
  function setNamespace($namespace){
  	$this->namespace = $namespace;
  }

  function getNSClassName(){
    $ns = $this->getNamespace();
  	if(!is_null($ns) && is_string($ns)){
  		return $ns . '\\' . $this->getClassName();
  	}
    return $this->getClassName();
  }
  
  
  function getClassName(){
    return $this->className;
  }


  function setClassName($className){
    $this->className = $className;
  }

  function getSuperClassName(){
    return $this->superClassName;
  }


  function setSuperClassName($className){
    $this->superClassName = trim($className);
  }

  /**
   * @access public
   * @return string path and Classname
   */
  function getLocation(){
    return $this->location;
  }

  function setLocation($location){
    $this->location = $location;
  }

  function getInterfaces(){
    return $this->interfaces;
  }

  
  function setInterfaces($interfaces){
    if(is_string($interfaces)){
      $this->interfaces = array_map("trim", explode(",",$interfaces));
    }elseif(is_array($interfaces)){
      $this->interfaces = array_map("trim", $interfaces);
    }else{
      throw new \Exception("no interfaces " + $interfaces);
    }
    $this->interfaces = array_filter($this->interfaces,function($value){ return !empty($value); });
  }

  
  function getType(){
    return $this->type;
  }

  
  function setType($typ){
    if (is_string($typ)) {
      $tmp = strtolower($typ);
      if($tmp == 'interface') {
        $this->type = self::T_INTERFACE;
      } elseif ($tmp == 'class') {
        $this->type = self::T_CLASS;
      } else {
        throw new Exception("type wrong " . $typ);
      }
    } else if (is_int($typ) && ($typ == 1 || $typ == 0)) {
      $this->type = $typ;
    } else {
      throw new Exception("type is wrong " . $type);
    }
  }
  

  function hasSuperClassName(){
    return isset($this->superClassName);
  }


  function hasInterfaces(){
    return !empty($this->interfaces);
  }


  function isInterface(){
    return $this->type == self::T_INTERFACE;
  }
  /*********************************************
   *  =========== OLD ================
  */

  //   /**
  //   * @access public
  //   * @var array contains all extended classes
  //   */
  //   public $extends = array();

  //   /**
  //    * @access public
  //    * @var array contains all implemented classes
  //    */
  //   public $implements = array();

  //   /**
  //    * @access public
  //    * @var int order counter
  //    * @todo For what do I need this?
  //    */
  //   public $order = 0;

  //   /**
  //    * @access public
  //    * @var string contains the file-path and file-name of the class Location
  //    * TODO rename to location
  //    */
  //   public $file = null;

  //   // TODO: DEBUG
  //   /**
  //    *
  //    * @access public
  //    * @param string file
  //    * @param string type
  //    * @param string name
  //    * @param array ext
  //    * @param array impl
  //    * @return void
  //    *
  //    */
  //    //   function __construct($file,$type, $name, $ext = null,$impl=null){
  //   //     $this->type = $type;
  //   //     $this->file = basename($file);
  //   //     $this->path = dirname($file);

  //   //     $this->name = $name;

  //   //     if(!empty($ext)){
  //   //       $this->ext[] = $ext;
  //   //     }

  //   //     if(!empty($impl)){
  //   //       $this->implements = explode(",",$impl);
  //   //       $this->implements = array_map('trim',$this->implements);
  //   //     }
  //   //   }

  //   /**
  //    * @access public
  //    * @return string list of classes from which this class extends
  //    */
  //   function getExtend(){
  //     return $this->ext;
  //   }

  //   /**
  //    * @access public
  //    * @return int order
  //    * @todo For what do I need this?
  //    */
  //   function getOrder(){
  //     return $this->order;
  //   }

  //   /**
  //    * Post require_once Funktion (nach loadClasses)
  //    * @access public
  //    * @return the parent class of the actual class (until you find any parent class)
  //    */
  //   function rebuildExtend(){
  //     if(!empty($this->ext)){
  //       $ext = $this->ext[0];
  //       while($ext = get_parent_class($ext)){
  //         $this->ext[] = $ext;
  //       }
  //     }
  //   }

  //   /**
  //    * @access public
  //    * @return string complete path and filename
  //    */
  //   function getFile(){
  //     return $this->path."/".$this->file;
  //   }
  }

