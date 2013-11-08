<?php

namespace Xsena\core\utils;

class StringUtil {
  
	static function random($length = 8) {
		$str = '';

		for ($x = 1; $x <= $length; $x++) {
			switch ( rand(1, 3) ) {
				case 1:
					$str .= rand(0, 9);
					break;
				case 2:
					$str .= chr( rand(65, 90) );
					break;
				case 3:
					$str  .= chr( rand(97, 122) );
					break;
			}
		}

		return $str;
	}

	static function cc($str,$ignoreFirst = false){
	  $arr = preg_split("/\s|_/", $str);
	  if($ignoreFirst){
	    $first = array_shift($arr);
	    $arr = array_map("ucfirst",$arr);
	    array_unshift($arr, $first);
	  }else{
	    $arr = array_map("ucfirst",$arr);
	  }
	  return implode("",$arr);
	}
	
	static function uncc($str,$ignoreFirst = false){
	  $arr = preg_split("/[A-Z]/", $str);
	  $arr = array_map('strtolower',$arr);
	  return implode(" ",$arr);
	}
	
	static function _($str){
	  $arr = preg_split("/\s|_/", $str);	  
	  return implode("_",$arr);
	}
}

?>