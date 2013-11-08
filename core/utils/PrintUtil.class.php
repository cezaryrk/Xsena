<?php 

namespace Xsena\core\utils;

class PrintUtil {

	public static function rawhtml($data){
		if(is_array($data) || is_object($data)){
			$data =  print_r($data,true);
		}
		return "<pre>".htmlspecialchars($data)."</pre>";
	}
}


