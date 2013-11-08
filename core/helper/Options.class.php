<?php
namespace Xsena\core\helper;

use Xsena\core\logging\Logger;
class Options {

  const DELIMITER = ".";

  public $options = array();

  public function __construct($options = array()) {
    if (is_array($options)) {
      $this->options = $options;
    }
  }
  
  // TODO
  public function parse($string) {}

  public function parseFile($file, $context = null, $overwrite = true) {
    Logger::log(__METHOD__ . " load " . $file);
    $array = file($file);
    foreach ($array as $entry) {
      if (empty($entry) || ! preg_match("/^\w/", $entry))
        continue;
      $_e = explode("=", $entry, 2);
      
      if (count($_e) == 2) {
        $path = trim($_e[0]);
        if ($context) {
          $path = $context . "." . $path;
        }
        if (! empty($path)) {
          $value = trim($_e[1]);
          $this->set($path, $value, $overwrite);
        } else {
          Logger::error(__METHOD__ . " can't add " . $entry . " to xfConfig");
        }
      }
    }
  }
  
  public function extend(Options $options, $overwrite = true){
  	$this->options = array_merge_recursive($this->options, $options->values());
  }

  public function get($selector) {
    return $this->find($selector);
  }

  public function set($selector, $value, $overwrite = true) {
    if (! preg_match("/" . preg_quote(self::DELIMITER) . "/", $selector)) {
      $this->options[$selector] = $value;
    } else {
      $path = explode(self::DELIMITER, $selector);
      if (count($path) > 0) {
        $str = "['" . implode("']['", $path) . "']";
        Logger::log(__METHOD__ . "  \$this->vars" . $str . " = " . print_r($value, true) . ";");
        if ($overwrite)
          eval("\$this->options" . $str . " = \$value;"); // \$this->map['".$_path."'] =& \$this->vars".$str.";");
        else
          eval("if(!isset(\$this->options" . $str . ")) \$this->options" . $str . " = \$value;");
      }
    }
  }

  private function find($path) {
    $tmp = $this->options;
    $array = explode(self::DELIMITER, $path);
    $count = count($array);
    $value = null;
    
    for ($i = 0; $i < $count; $i ++) {
      $key = $array[$i];
      if (isset($tmp[$key])) {
        $value = $tmp[$key];
        $tmp = $value;
      } else {
        $value = null;
        break;
      }
    }
    return $value;
  }

  public function select($selector) {
    $value = $this->get($selector);    
    if ($value) {
      $clazz = __CLASS__;
      return new $clazz($value);
    } else {
      return NULL;
    }
  }

  public function where($key, $value = null) {
    $result = array();
    foreach ($this->options as $_k => $_v) {
      if (is_array($_v) && isset($_v[$key]) && ($value != null ? $_v[$key] === $value : true)) {
        $result[$_k] = $_v;
      }
    }
    $clazz = __CLASS__;
    return new $clazz($result);
  }

  /**
   *
   *
   * Makes an instance as xfConfig of an array
   * 
   * @param
   *          string key
   * @return xfConfig
   */
  public function whereIsSet($key) {
    $result = array();
    foreach ($this->options as $_k => $_v) {
      if (is_array($_v) && isset($_v[$key])) {
        $result[$_k] = $_v;
      }
    }
    $clazz = __CLASS__;
    return new $clazz($result);
  }

  /**
   *
   *
   * Returns the variable, if it is set
   * 
   * @deprecated ???
   * @return string
   */
  public function hasValues() {
    return ! empty($this->options);
  }

  /**
   *
   *
   * Returns the variable, if it is set
   * 
   * @deprecated ???
   * @return int
   */
  public function size() {
    return count($this->options);
  }

  /**
   *
   *
   * Returns a variable
   * 
   * @deprecated ???
   * @return string
   */
  public function values() {
    return $this->options;
  }
}