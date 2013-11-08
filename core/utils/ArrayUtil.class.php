<?php

namespace Xsena\core\utils;

class ArrayUtil {
	
	static function toAssoc($key, $array){
		$arr = array();
		foreach($array as $value){
			if(!isset($value[$key])) throw new Exception("FEHLER"); // TODO fixme:)
			$arr[$value[$key]] = $value; 
		}
		return $arr;
	}
	
	static function filterByKeys(array $keys, array $array){
		$arr = array();
		foreach ($array as $key => $value) {
			if(in_array($key, $keys)){
				$arr[$key] = $value;
			}
		}
		return $arr;	
	}
	
	static function merge(array $array1, array $array2){
		foreach($array2 as $key => $value) {
			if(is_array($value)){
				$tmp = array();
				if(isset($array1[$key])){
					$tmp = $array1[$key];
				}else{
					$array1[$key] = array();
				}
				$array1[$key] = self::merge($tmp, $value);
			}else{
				$array1[$key] = $value;
			}
		}
		return $array1;
	}
	
	static function compareAndMerge($orginal, $to_compare, array &$changes, $prekey = array()){
		foreach($to_compare as $key => $value) {
			$tmp_prekey = $prekey;
			$tmp_prekey[] = $key;
			$change_key = implode(":", $tmp_prekey);
			$_has_key = self::hasKey($orginal, $key);
			if(is_null($_has_key)) throw new Exception(__METHOD__." Orginal is null??");
			
			if($_has_key){
				if(is_array($value) || is_object($value)){										
					$orginal[$key] = self::compareAndMerge($orginal[$key], $value, $changes, $tmp_prekey);					
				}else{
					$org_value = self::getKey($orginal, $key);
					if($value != $org_value){
						$changes[$change_key] = array($org_value,$value);						
						self::setKey($orginal, $key, $value);						
					}
				}
			}else{								
				$changes[$change_key] = array(null,$value);
				self::setKey($orginal, $key, $value);
			}
		}
		return $orginal;
	}
	
	static function hasKey($e,$key){
		if(is_object($e)){
			return isset($e->{$key});
		}elseif(is_array($e)){
			return isset($e[$key]);
		}
		return null;
	}
	
	static function getKey($e,$key){
		if(is_object($e)){
			return $e->{$key};
		}elseif(is_array($e)){
			return $e[$key];
		}
		return null;
	}
	
	static function setKey(&$e,$key,$v){
		if(is_object($e)){
			$e->{$key} = $v;
		}elseif(is_array($e)){
			$e[$key] = $v;
		}		
	}
	
	
	/**
	 * Reduces the declared classnames
	 * @access public
	 * @static
	 * @param array array
	 * @return array
	 * @todo What does this funtion really do?
	 */
	static function flattenArray(array $array) {
	  $f_array = array();
	  if(empty($array)) return $f_array;
	  foreach ($array as $key => $value){
	    $f_array[] = $key;
	    if(is_array($value)){
	      $tmp = self::flattenArray($value);
	      $f_array = array_merge($f_array,$tmp);
	    }
	  }
	  return $f_array;
	}

}