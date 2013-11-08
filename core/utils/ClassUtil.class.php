<?php

namespace Xsena\core\utils;

class ClassUtil {
	public static function &createNewInstance($classname, &$_ ){
		$rc = new ReflectionClass($classname);
		Logger::log(__METHOD__." NewInstance: ".$classname." ARGS: ".print_r($_,true));
		if($_){
			if(is_array($_)){
				$object =& $rc->newInstanceArgs($_);
			}else{
				$object =& $rc->newInstanceArgs(array($_));
			}
		}else{
			$object =& $rc->newInstanceArgs(array());
		}
		return $object;
	}
	
}