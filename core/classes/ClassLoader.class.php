<?php
/**
 * xfClassLoader.class.php
 *
 * Contains class xfClassLoader
 * Contains class ClassExistsException extends Exception
 * <ul>
 *  <li>extends from Exception</li>
 * </ul>
 */
namespace Xsena\core\classes;

require_once 'ClassDescriptor.class.php';

use Xsena\core\classes\ClassDescriptor;
use Xsena\core\logging\Logger;
use Xsena\core\utils\FSUtil;
use Xsena\core\utils\StringUtil;

/**
 * class xfClassLoader
 */
class ClassLoader {

  const LOG_TAG = 'Classloader';

  const NAMESPACE_PATTERN = "/namespace\s+(\S+)\s*;/";

  const USE_PATTERN = "/use\s+(\S+)\s*;/";

  const CLASS_PATTERN = '/(class|interface)\s+(\w+)(\s*extends\s+((\w|\\\)+))?(\s*implements\s+((\w|\\\)+(\s*,\s*(\w|\\\)+)*))?\s*{/';

  const POS_CLASS_TYPE = 1;

  const POS_CLASS_NAME = 2;

  const POS_EXTENDS_TYPE = 4;

  const POS_IMPLEMENTS_TYPE = 7;
  
  // TODO verschönern
  static $autoload_callbacks = array();

  /**
   *
   * @access public
   * @var array contains all the classfile-names and the classnames found in this framework
   */
  public $classList = array();
  
  
  public $timestamps = array();

  
  /**
   *
   * @access public
   * @var array contains a class tree with all dependencies of parent classes up to the root class (e.g. xfObject, xfSingleton)
   */
  public $class_tree = array();

  /**
   *
   * @access public
   * @var array
   */
  public $class_tree_reverse = array();

  /**
   *
   * @access public
   * @var array contains all initialized classes rewritten to lower-string
   */
  public $class_tolowercase_map = array();

  /**
   *
   * @access public
   * @var array contains all classes which are declared
   */
  public $class_declared = array();

  public $parentClassLoader = NULL;

  /**
   *
   * @access public
   * @deprecated function spl_autoload_register()
   * @see function spl_autoload_register()
   * @return void
   */
  public function __construct() {
    spl_autoload_register(array(
      $this,
      "autoload"
    ));
  }

  
  public function __wakeup() {
    // Logger::log("");
    spl_autoload_register(array(
      $this,
      "autoload"
    ));
  }

  /**
   * Checks whether the classname is available
   *
   * @access public
   * @static
   *
   *
   *
   *
   * @param
   *          string classname
   * @return true, if class, interface or user-function in class is defined
   */
  public function autoload($classname) {
    $this->loadClassOnDemand($classname);
    if (class_exists($classname, false) || interface_exists($classname, false)) {
      return true;
    } else {
      if (! empty(self::$autoload_callbacks) && is_array(self::$autoload_callbacks)) {
        foreach (self::$autoload_callbacks as $function) {
          $erg = call_user_func($function, $classname);
          if ($erg)
            return true;
        }
      }
      return false;
    }
  }

  /**
   *
   * @access public
   * @static
   *
   *
   *
   *
   * @param
   *          string autoloader
   * @return true or false
   * @todo What does this function checks? complete abstract and return
   */
  public static function registerAutoloader($autoloader) {
    if (is_array($autoloader) && count($autoloader) == 2) {
      if (method_exists($autoloader[0], $autoloader[1])) {
        self::$autoload_callbacks[] = $autoloader;
        return true;
      }
    } elseif (is_string($autoloader) && function_exists($autoloader)) {
      self::$autoload_callbacks[] = $autoloader;
      return true;
    }
    return false;
  }

  /**
   * Initialize the classpath from the Documentroot
   * sets a filter to the filename-format should be found
   * <code>
   * $filter = array(".class.php",".interface.php");
   * </code>
   * locates the files in the filesystem
   * builds a classlist of all class-files found in the system
   * rewrites the classnames to lower string
   * builds a class-depend tree
   * builds a reverse-class-depend tree
   *
   * @access public
   * @static
   *
   *
   *
   *
   * @param
   *          string classpath
   * @param
   *          array file_filter
   * @return void
   */
  // static function init($classpath, $file_filter = NULL){
  // Logger::log(__METHOD__." CP=".$classpath);
  // $self =& self::getInstance();
  
  // $class_files = FSUtil::files($classpath, $file_filter);
  
  // // if($file_filter) $class_files = $self->locateClassFiles($classpath, $file_filter);
  // // else $class_files = $self->locateClassFiles($classpath);
  
  // if(empty($class_files)) return;
  // $classList = $self->buildClassList($class_files, $claclassList// if(empty($classList)) return;
  
  // $self->classList = array_merge($self->classList,$classList);
  
  // foreach($self->classList as $classname => $data){
  // $self->class_tolowercase_map[strtolower($classname)] = $classname;
  // }
  
  // $self->class_tree = $self->buildClassDependencyTree($self->classList);
  // $self->buildReverseDependencyTree();
  // }
  
  public $classPaths = array();
  
  public function classpaths(array $list, $force = false){
    $rebuild = false;
    $this->classPaths = $list;
    
  	foreach ($list as $item){
  	  $path = $item['path'];
  	  $_rebuild = false;
  	  if(isset($item['filter']) && is_array($item['filter'])){
  	    $_rebuild = $this->scan($path, $item['filter'],$force);
  	  }else{
  	    $_rebuild = $this->scan($path);
  	  }
  	  $rebuild = $rebuild || $_rebuild;
  	}
  	
  	// Logger::log(array_keys($this->classList));
  	return $rebuild;
  }
  
  public function scan($directory, $filter = array("\.class\.php$","\.interface\.php$"), $force = false) {
    $files = FSUtil::files($directory, $filter);
    if (empty($files))
      return false; // TODO throw Error or log problem
    
    $timestamp = 0;
    if(!isset($this->timestamps[$directory])){
      $timestamp = 0;
      $rebuild = true;      
    }else{
      $timestamp = $this->timestamps[$directory]; 
    }
    
    $rebuild = $force;
    
    foreach ($files as $file){
      $_timestamp = filemtime($file);
      if($timestamp < $_timestamp){
        $rebuild = true;
        $timestamp = $_timestamp;  	
        Logger::log('Change in ' . $file . ' detected');
        
      }    	
    }

    if($rebuild){
      $this->timestamps[$directory] = $timestamp;
      $classes = self::findClasses($files);          
      $this->merge($classes);
      return true;
    }
    return false;
  }

  static function findClasses($files) {
    if (! is_array($files))
      return array();
    $classes = array();
    foreach ($files as $file) {
      $classes_in_file = self::findClassesInFile($file);
      $classes = array_merge($classes, $classes_in_file);
    }
    return $classes;
  }

  /**
   * Looks in filenames, whether there is a class or an interfaces and registers the class/interface-name in the object xfClass
   *
   * @access public
   * @param
   *          string filename
   * @uses xfClass to register filename, class/interface, class/interface-name
   * @return array Looks like the return of the method buildClassList
   */
  static function findClassesInFile($filepath) {
    $content = file_get_contents($filepath);
    $matches = array();
    $namespace = NULL;
    $uses = array();
    
    preg_match(self::NAMESPACE_PATTERN, $content, $matches);
    if (isset($matches[1])) {
      $namespace = $matches[1];
    }
    
    $offset = 0;
    while (preg_match(self::USE_PATTERN, $content, $matches, PREG_OFFSET_CAPTURE, $offset)) {
      $use = $matches[1][0];
      $len = strlen($use);
      $offset = $matches[1][1] + $len;
      $tmp = explode('\\', $use);
      $classname = end($tmp);
      $uses[$classname] = $use;
      $uses[$use] = NULL;
    }
    
    preg_match_all(self::CLASS_PATTERN, $content, $matches);
    
    $classes = array();
    
    if (! empty($matches[self::POS_CLASS_NAME])) {
      // FIXME: Problem beim match von Klassen mit Kommentaren im Klassen-Header, also vor "{"
      
      if ($namespace) {
        // Extend uses by all declared files
        foreach ($matches[self::POS_CLASS_NAME] as $key => $match) {
          if (empty($matches[self::POS_CLASS_NAME][$key]) || empty($matches[self::POS_CLASS_TYPE][$key])) {
            // TODO log error
            continue;
          }
          
          if (! isset($uses[$matches[self::POS_CLASS_NAME][$key]]))
            $nsClassName = $namespace . '\\' . $matches[self::POS_CLASS_NAME][$key];
          $uses[$matches[self::POS_CLASS_NAME][$key]] = $nsClassName;
        }
      }
      
      foreach ($matches[self::POS_CLASS_NAME] as $key => $match) {
        if (empty($matches[self::POS_CLASS_NAME][$key]) || empty($matches[self::POS_CLASS_TYPE][$key])) {
          // TODO log error
          continue;
        }
        
        $clazz = new ClassDescriptor();
        $clazz->setClassName($matches[self::POS_CLASS_NAME][$key]);
        $clazz->setLocation($filepath);
        $clazz->setNamespace($namespace);
        $clazz->setType($matches[self::POS_CLASS_TYPE][$key]);
        
        if (! empty($matches[self::POS_EXTENDS_TYPE][$key])) {
          $superClassName = trim($matches[self::POS_EXTENDS_TYPE][$key]);
          
          if (isset($uses[$superClassName]) && ! empty($uses[$superClassName])) {
            $clazz->setSuperClassName($uses[$superClassName]);
          } else {
            $clazz->setSuperClassName($superClassName);
          }
        }
        
        if (! empty($matches[self::POS_IMPLEMENTS_TYPE][$key])) {
          $interfaces = array_map("trim", explode(",", $matches[self::POS_IMPLEMENTS_TYPE][$key]));
          $adapted_interfaces = array();
          
          foreach ($interfaces as $interface) {
            if (isset($uses[$interface]) && ! empty($uses[$interface])) {
              $adapted_interfaces[] = $uses[$interface];
            } else {
              $adapted_interfaces[] = $interface;
            }
          }
          $clazz->setInterfaces($adapted_interfaces);
        }
        
        $classes[$clazz->getNSClassName()] = $clazz;
        
        // $matches[2][$key]
        // $classes[$matches[2][$key]] = array(
        // 'location' => $filename,
        // 'class_name' => $matches[2][$key],
        // 'namespace' => $namespace,
        // 'type' => $matches[1][$key],
        // 'super_class_name' => $matches[4][$key],
        // 'interfaces' => $matches[6][$key],
        // );
      }
    }
    
    return $classes;
  }

  function merge($classes) {
    foreach ($classes as $n => $c) {
      if ($c instanceof ClassDescriptor) {
        $this->classList[$n] = $c;
      } else {
        throw new Exception("wrong class instance " . $n);
      }
    }
    
    foreach (array_keys($this->classList) as $classname) {
      $this->class_tolowercase_map[strtolower($classname)] = $classname;
    }
    
    $this->class_tree = self::buildClassDependencyTree($this->classList);
    $this->buildReverseDependencyTree();
  }

  /**
   *
   * @access public
   * @static
   *
   *
   *
   *
   * @param
   *          string classname
   * @return true
   * @todo what does the function do? complete param and return
   */
  public function contains($classname) {
    if (isset($this->classList[$classname])) {
      return true;
    }
    return false;
  }

  /**
   *
   * @access public
   * @static
   *
   *
   *
   *
   * @param
   *          string classname
   * @param
   *          array extends
   * @return true
   * @todo what does the function do? complete param and return
   */
  public function containsExtension($classname, $extends = array()) {
    if (isset($this->classList[$classname])) {
      $ext_intersect = array_intersect($this->classList[$classname]->ext, $extends);
      $impl_intersect = array_intersect($this->classList[$classname]->impl, $extends);
      if ((count($ext_intersect) + count($impl_intersect)) == count($extends)) {
        return true;
      }
    }
    return false;
  }

  /**
   *
   * @param string $className          
   * @return ClassDescriptor
   *
   */
  function &getClassByName($className) {
    $clsLower = strtolower($className);
    if (isset($this->class_tolowercase_map[$clsLower])) {
      return $this->classList[$this->class_tolowercase_map[$clsLower]];
    }
    $false = null;
    return $false;
  }

  function getClassTreeByName($className) {
    $clsLower = strtolower($className);
    if (isset($this->class_tree[$clsLower])) {
      return $this->class_tree[$clsLower];
    }
    return FALSE;
  }

  /**
   *
   * @access public
   * @static
   *
   *
   *
   *
   * @param
   *          array extends
   * @return array
   * @todo what does the function do? complete param and return
   */
  public function getClassesBy($extends = array()) {
    if (empty($extends))
      return array();
    if (! is_array($extends))
      $extends = array(
        $extends
      );
    
    $return = array();
    
    foreach ($this->classList as $classname => $class_obj) {
      $ext_intersect = array_intersect($class_obj->ext, $extends);
      $impl_intersect = array_intersect($class_obj->impl, $extends);
      if (count($ext_intersect) > 0 || count($impl_intersect) > 0) {
        $return[$classname] = array_merge($class_obj->ext, $class_obj->impl);
        // $additional = self::getClassesBy($classname);
        // $return =
      }
    }
    // FIXME verbessern durch reverse dependency tree
    // $tmp = array();
    // foreach($return as $classname){
    //
    // }
    
    return $return;
  }

  /**
   *
   * @access public
   * @static
   *
   *
   *
   *
   * @param
   *          string extends (rightnow just DALResultsListener)
   * @return array (rightnow just DALResultsModifications)
   * @todo what does the function do?
   */
  public function getClassesByExtension($extends) {
    $result = array();
    $this->_getClassesByExtension($extends, $result);
    return $result;
  }

  /**
   *
   * @access private
   * @param
   *          string extends (rightnow just DALResultsListener)
   * @return &array (rightnow just DALResultsModifications)
   * @todo what does the function do?
   */
  private function _getClassesByExtension($extends, &$array) {
    if (isset($this->class_tree_reverse[$extends])) {
      foreach ($this->class_tree_reverse[$extends] as $classname) {
        $array[] = $classname;
        $this->_getClassesByExtension($classname, $array);
      }
    }
  }

  /**
   * Loads a class when its used, e.g.
   * <ul>
   * <li>xfRender<li>
   * <li>xfSetter<li>
   * <li>DALPlugin<li>
   * <li>Campusuebersicht<li>
   * </ul>
   * if class don't exists, it will be declared and integrated in the class-tree
   * all new classes will be included by require_once
   *
   * @access public
   * @static
   *
   *
   *
   *
   * @param
   *          string classname
   * @return void
   */
  private function loadClassOnDemand($classname) {
    // Logger::log(__METHOD__.": Load on Demand CLASS=".$classname);
    if (! class_exists($classname, false)) {
      // Logger::log(__METHOD__.": CLASS=".$classname." don't exist");
      $cls = & $this->getClassByName($classname);
      
      if (! $cls) {
        // Logger::warn(__METHOD__ . ": NO CLASS LOWER MAP for " . $classname);
        return null;
      }
      
      // $this->getClassTreeByName($clsName);
      $classname = $cls->getNSClassName();
      if (! isset($this->class_tree[$classname])) {
        Logger::warn(__METHOD__ . ": NO CLASS IN DEPENDENCY-TREE");
        return null;
      }
      
      $this->loadClassesByTree($this->class_tree[$classname], $this->classList);
      
      $class_file = $cls->getLocation();
      // Logger::log("require_once " . $class_file);
      
      if(is_readable($class_file)){
        require_once $class_file;
      }else{
      	throw new \Exception("Can't read file: " . $class_file);
      }
    } else {
      // Logger::log(__METHOD__.": CLASS=".$classname." exists");
    }
  }

  /**
   * Locates .
   *
   *
   *
   * class.php and .interface.php files in the framework by parsing the filesystem in the framework
   *
   * @access public
   * @param string $classpath          
   * @param array $file_filter          
   * @return array class-files as a multiple array, separate array for every single folder
   */
  // function locateClassFiles($classpath, $file_filter = array(".class.php",".interface.php")){
  // $classpath = preg_match("/\/$/",$classpath) ? $classpath : $classpath."/";
  // if(!is_dir($classpath)) return array();
  
  // if(empty($file_filter)) return array();
  
  // if(is_array($file_filter)){
  // $filter_regex = "";
  // foreach($file_filter as $filter){
  // if(!empty($filter_regex)) $filter_regex .= "|";
  // $filter_regex .= preg_quote($filter,"/");
  // }
  // }else{
  // $filter_regex = $file_filter;
  // }
  
  // $class_files = array();
  
  // if ($handle = opendir($classpath)) {
  // while (false !== ($file = readdir($handle))) {
  // $class_file = $classpath.$file;
  // if(is_file($class_file) && preg_match("/($filter_regex)$/", $file)) {
  // $class_files[] = $class_file;
  // } elseif(is_dir($class_file) && !preg_match("/^\./",$file)) {
  // $additional = $this->locateClassFiles($class_file,$filter_regex);
  // $class_files = array_merge($class_files,$additional);
  // } else {
  
  // }
  // }
  // closedir($handle);
  // }
  // return $class_files;
  // }
  
  /**
   * Builds a reverse tree of the classnames used in the framework
   * Here is an example of just one line, the method builds
   * <code>
   * Array (
   * [0] => Button
   * [1] => Checkbox
   * [2] => CheckboxWrapper
   * [3] => Option
   * [4] => Optionable
   * [5] => Radio
   * [6] => RadioWrapper
   * [7] => TextField
   * [8] => Textarea
   * )
   * </code>
   *
   * @access public
   * @return array class-files as a multiple array, separate array for every single folder
   */
  function buildReverseDependencyTree() {
    foreach ($this->class_tree as $classname => $dependencies) {
      foreach ($dependencies as $parent_class => $_tmp) {
        if (isset($this->class_tree_reverse[$parent_class])) {
          if (! in_array($classname, $this->class_tree_reverse[$parent_class])) {
            $this->class_tree_reverse[$parent_class][] = $classname;
          }
        } else {
          $this->class_tree_reverse[$parent_class] = array(
            $classname
          );
        }
      }
      // $this->locateReverseClassDependencies($dependencies, $array);
      // Logger::log(__METHOD__.": ".print_r($array,true));
    }
  }

  /**
   *
   * @access public
   * @param
   *          dependencies
   * @param
   *          array
   * @return array
   * @todo What does this function do?
   */
  function locateReverseClassDependencies($dependencies, $array) {
    foreach ($dependencies as $classname => $_dependencies) {
      $array[] = $classname;
      if (empty($_dependencies)) {
        $_merge = "['" . implode("']['", array_reverse($array)) . "']";
        eval("\$this->class_tree_reverse" . $_merge . " = array();");
      } else {
        $this->locateReverseClassDependencies($_dependencies, $array);
      }
      // $array = array_merge($array,$_tmp);
    }
    return $array;
  }

  /**
   * Builds an arraylist of all classes found in the framework filesystem, e.g.
   * the return array of class xfClass and Campusuebersicht
   * <code>
   * [xfClass] => xfClass Object
   * (
   * [file] => xfClass.class.php
   * [hash] =>
   * [name] => xfClass
   * [ext] => Array
   * (
   * )
   * [impl] => Array
   * (
   * )
   * [order] => 0
   * [type] => class
   * * [path] => /home/andreas/_maestria/mydesk/code/web2/htdocs/xsena/core
   * )
   * [Campusuebersicht] => xfClass Object
   * (
   * [file] => Campusuebersicht.class.php
   * [hash] =>
   * [name] => Campusuebersicht
   * [ext] => Array
   * (
   * [0] => DefaultViewPart
   * )
   * [impl] => Array
   * (
   * )
   * [order] => 0
   * [type] => class
   * [path] => /home/andreas/_maestria/mydesk/code/web2/htdocs/mydesk/components/views
   * )
   * </code>
   *
   * @access public
   * @param
   *          array class_files All found .class.php files in the framework
   * @param
   *          string classpath Sets the path where the .class.php files are located
   * @return array See example below
   */
  // function buildClassList($class_files, $classpath){
  // if(!is_array($class_files)) return array();
  // $classes = array();
  // foreach($class_files as $class_file){
  // if(preg_match("/^".preg_quote($classpath,"/")."/",$class_file)){
  // $located_classes = $this->extractClassesFromFile($class_file);
  // foreach($located_classes as $name => $obj){
  // $classes[$name] = $obj;
  // }
  // }
  // }
  // return $classes;
  // }
  
  /**
   * This method takes the array of the buildClassList and builds a tree from it, e.g.
   * return output of one array field
   *
   * <code>
   * [Campusuebersicht] => Array (
   * [DefaultViewPart] => Array (
   * [ViewPart] => Array (
   * [Component] => Array (
   * [xfComponent] => Array (
   * [xfObject] => Array(
   * )
   * )
   * )
   * )
   * )
   * )
   * </code>
   *
   * @access public
   * @param
   *          array class_array
   * @return array See example below
   * @uses xfClassLoader::buildClassList Uses the return array as input
   */
  static function buildClassDependencyTree(&$classes) {
    $tree = array();
    foreach ($classes as $classname => &$cls) {
      self::locateClassDependencies($classname, $cls, $classes, $tree);
    }
    return $tree;
  }

  /**
   * This method creates the dependancies between the classes
   *
   * @access public
   * @param
   *          array classname
   * @param
   *          array &class_obj
   * @param
   *          array &class_array
   * @param
   *          array &tree
   * @return void
   * @todo What does this funtion really do?
   */
  static function locateClassDependencies($classname, ClassDescriptor &$cls, &$classes, &$tree) {
    $tree[$classname] = array();
    $dependencies = array_merge(array(
      $cls->getSuperClassName()
    ), $cls->getInterfaces());
    if (! empty($dependencies)) {
      foreach ($dependencies as $parent_class) {
        if (isset($classes[$parent_class])) {
          self::locateClassDependencies($parent_class, $classes[$parent_class], $classes, $tree[$classname]);
          /*
           * if($class_array[$parent_class]->type == 'class'){ $class_obj->ext[] = $parent_class; }else{ $class_obj->impl[] = $parent_class; }
           */
        }
      }
    }
  }

  /**
   * This method takes the array of the buildClassList.
   * It includes all class-files which are not loaded yet in the tree
   *
   * @access public
   * @param
   *          array class_tree
   * @param
   *          array class_array
   * @return void
   * @todo What does this funtion really do?
   */
  // TODO this can maybe be static
  public function loadClassesByTree($class_tree, $class_array) {
    if (! is_array($class_tree) || ! is_array($class_array))
      return null;
    foreach ($class_tree as $classname => $parent_classnames) {
      if (class_exists($classname, false)) {
        continue;
      }
      if (! empty($parent_classnames)) {
        $this->loadClassesByTree($parent_classnames, $class_array);
      }
      
      $class_obj = $class_array[$classname];
      $class_file = $class_obj->getLocation();
      
      //Logger::log("require_once " . $class_file);
      require_once $class_file;
    }
  }

  /**
   * Joins the classname to an interface
   *
   * @access public
   * @static
   *
   *
   *
   *
   * @param
   *          string classname
   * @param
   *          string interface
   * @uses ReflectionClass:implementsInterface()
   * @return int
   * @todo What does this funtion really do?
   */
  public function hasInterface($classname, $interface) {
    // if(class_exists($classname,true)) {
    $reflectionA = new ReflectionClass($classname);
    return $reflectionA->implementsInterface($interface);
    // }
    // return false;
  }

  /**
   * Gets all classnames, which are declared on the specific website
   *
   * @access public
   * @static
   *
   *
   *
   *
   * @param
   *          string classname
   * @return array
   * @todo What does this funtion really do?
   */
  public function getDeclared($classname) {
    $class = strtolower($classname);
    if (isset($this->class_tolowercase_map[$class])) {
      $class = $this->class_tolowercase_map[$class];
      if (isset($this->class_declared[$class]))
        return $this->class_declared[$class]; // Falls schon berechnet
      $class_tree = $this->class_tree[$class];
      $this->class_declared[$class] = ArrayUtil::flattenArray($class_tree);
      return $this->class_declared[$class];
    }
    return false;
  }
}

// TODO gleichnamige Klassen abfangen!!!
class ClassExistsException extends \Exception {
}


