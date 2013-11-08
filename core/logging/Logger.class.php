<?php // -*- mode: php; -*-

namespace Xsena\core\logging;


class Logger
{
  static $LEVEL_TYPES = array(1 => "ERROR",2 => "WARN",3 => "INFO",4 => "DEBUG",5 => "LOG");
  static $instance;
  
  public $files = array();
  public $tags = array();

  
  static function &getInstance() {
    if(!isset(self::$instance)){
      $cls = __CLASS__;
      self::$instance = new $cls();
    }
    return self::$instance;
  }
  

  static function setLogFile($filename, $tags = null){
    $logger =& self::getInstance();
    $id = count($logger->files);
    $logger->files[$id] = $filename;
    if(!isset($tags) && !isset($logger->tags['default'])){
      $logger->tags['default']['fid'][] = $id;
    }elseif(!empty($tags)){
      $arr_tag = $logger->stringToArray($tags);
      foreach($arr_tag as $tag){
        if(isset($logger->tags[$tag]['fid'])){
          if(!in_array($id, $logger->tags[$tag]['fid']))
          $logger->tags[$tag]['fid'][] = $id;
        }else{
          $logger->tags[$tag]['fid'][] = $id;
        }
      }
    }
  }


  function stringToArray($str, $split = ','){
    $arr = array();
    if(is_string($str)){
      $arr = explode($split, $str);
      $arr = array_map('trim',$arr);
    }
    return $arr;
  }


  function setLogLevel($tags, $loglevel){
    if(isset($tags)){
      $logger =& self::getInstance();
      $arr_tag = $logger->stringToArray($tags);
      foreach($arr_tag as $tag){
        if(isset($logger->tags[$tag]))
        $logger->tags[$tag]['log'] = $loglevel;
      }
    }
  }


  static function log2($class,$method, $str,  $tags = 'default', $level = 5){
    if(is_string($class)){
      $str = array_pop(explode("::",$class))."::".$method."".$str;
    }elseif(is_object($class)){
      $str = get_class($class)."->".$method."".$str;
    }
    self::log($str,$tags,$level);
  }


  static function blog($str, $tags = 'default', $level = 5){
    $bt = debug_backtrace();

    //preg_match("/\/(xf[^\.]*)\./",$bt[1]['file'], $bt_match);
    /*
    $stack = array();
    foreach($bt as $id => $entry){
    $_entry = "\t";
    if(isset($entry['class'])){
    $_entry .= $entry['class']."::";
    }
    $_entry .= $entry['function'];
    $tmp_id = $id+1;
    if(isset($bt[$tmp_id])){
    $_entry .= " called in (";
    if(isset($bt[$tmp_id]['class'])){
    $_entry .= $bt[$tmp_id]['class']."::";
    }
    $_entry .= $bt[$tmp_id]['function'];
    if(isset($entry['line'])){
    $_entry .= "[".$entry['line']."]";
    }
    $_entry .= ")";
    }
    $stack[] = $_entry;
    }
    */
    $stack = self::stacktrace_format($bt);
    self::log($str."\nStack:\n".$stack."\n");
  }

  static function stacktrace_format_entry($backtrace, $id = 0){
    $entry = $backtrace[$id];
    $prev_id = $id - 1; $next_id = $id + 1;

    $prev_entry = null;$next_entry = null;

    if(isset($backtrace[$prev_id])){
      $prev_entry = $backtrace[$prev_id];
    }

    if(isset($backtrace[$next_id])){
      $next_entry = $backtrace[$next_id];
    }

    $_entry = "\t";
    if(isset($entry['class'])){
      $_entry .= $entry['class']."::";
    }
    $_entry .= $entry['function']."[".$prev_entry['line']."]";

    if(false && $next_entry){
      $_entry .= " called in (";
      if(isset($next_entry['class'])){
        $_entry .= $next_entry['class']."::";
      }
      $_entry .= $next_entry['function'];
      if(isset($entry['line'])){
        $_entry .= "[".$entry['line']."]";
      }
      $_entry .= ")";
    }
    return $_entry;
  }

  static function stacktrace_format($backtrace, $mode = "default"){

    switch($mode)	{
      case "line":
        return self::stacktrace_format_entry($backtrace);

      default:
        $stack = array();
      foreach($backtrace as $id => $entry){
        $_entry = self::stacktrace_format_entry($backtrace,$id);
        $stack[] = $_entry;
      }
      return implode("\n",$stack);
    }
     

  }




  static function log($str, $tags = 'default', $level = 5){
     
	if(!defined("__LOGGER__")){
		define("__LOGGER__",false);
	}
	if(!__LOGGER__) return;

    //$bt = debug_backtrace();
    //preg_match("/\/(xf[^\.]*)\./",$bt[1]['file'], $bt_match);

    $logger =& self::getInstance();
    $arr_tag = $logger->stringToArray($tags);
    $arr_file = array();

    $test = self::stacktrace_format_entry(debug_backtrace(),1);

    if(is_array($str) || is_object($str)){
      $str = "ARRAY: ".print_r($str,true);
    }

    $str_level = ($level > 0 && $level < 6) ? " - ".self::$LEVEL_TYPES[$level]." " : "";
    $str_time = "[".date("d/M/Y H:i:s",time())."]".$str_level. " $test: ";//."[".$bt_match[1].":".$bt[1]['line']."]";
    $str = trim($str);
    $str_log = $str_time." ".$str;    
    if(function_exists("drush_print")){
    	drush_print($str_log,0,STDERR);
    }
    $str_log .= "\n";
    
    foreach($arr_tag as $tag){
      if(isset($logger->tags[$tag]['fid'])){
        foreach($logger->tags[$tag]['fid'] as $fid){
          if(isset($logger->files[$fid]) && !in_array($fid,$arr_file)){
            $arr_file[] = $fid;
            //$str_log = $str_time." ".$tag." ".$str_level.": ".$str;
            
            self::write($logger->files[$fid], $str_log);
          }
        }
      }
    }
  }


  static function error($str, $tags = 'default'){
    self::log($str,$tags,1);
  }


  static function warn($str, $tags = 'default'){
    self::log($str,$tags,2);
  }


  static function info($str, $tags = 'default'){
    self::log($str,$tags,3);
  }


  static function debug($str, $tags = 'default'){
    self::log($str,$tags,4);
  }


  static function write($file, $logstring){
    if(!file_exists($file) && is_writable(dirname($file))){
      touch($file);
      chmod ($file, 0777);
    }
    
    
      
    if ($handle = fopen($file,"a+")) {
        fwrite($handle, $logstring);
        fclose($handle);
      }
  }
  
}











