<?php

namespace Xsena\core\utils;

class FSUtil {

  static function isDir($dir){
    return is_dir($dir);
  }

  static function validDir($dir){
    if(self::isDir($dir)){
      return preg_match("/\/$/",$dir) ? $dir : $dir."/";
    }
    return null;
  }

  // filter = array(".class.php",".interface.php");
  static function files($files_path, $filter_regex = null){
    $files_path = preg_match("/\/$/",$files_path) ? $files_path : $files_path."/";
    if(!is_dir($files_path)) return array();

    if(is_array($filter_regex)){
      $tmp = array();
//       foreach($filter_regex as $filter){
//         $tmp[] = preg_quote($filter,"/");
//       }
      $filter_regex = implode("|",$filter_regex);
    }

    if(empty($filter_regex)){
      $filter_regex = null;
    }

    $files = array();
    if ($handle = opendir($files_path)) {
      while (false !== ($tmp = readdir($handle))) {
        $file = $files_path.$tmp;
        if(is_file($file) && (is_null($filter_regex) || preg_match("/($filter_regex)$/", $tmp))) {
          $files[] = $file;
        } elseif(is_dir($file) && !preg_match("/^\./",$tmp)) {
          $additional = self::files($file,$filter_regex);
          $files = array_merge($files,$additional);
        } 
      }
      closedir($handle);
    }
    return $files;
  }
}

?>