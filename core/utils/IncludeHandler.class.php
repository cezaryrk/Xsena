<?php 

namespace Xsena\core\utils;

class IncludeHandler {
	public static function evaluate($filepath, $args) {
  		extract($args, EXTR_SKIP); 
  		ob_start(); 
  		if(empty($filepath)) throw new Exception(__METHOD__." filepath is empty");
  		$tmp = include $filepath;
  		if($tmp == 1){
  			$contents = ob_get_contents();	
  		} else {
  			$contents = $tmp;	
  		} 		 
  		ob_end_clean(); 
  		return $contents;
	}
	
	public static function file($filepath, $args = array()){
		// FIXME compatibility hack!
		if(preg_match("/^(tpl|conf)\//", $filepath)){ 
			$filepath = dirname(__FILE__)."/inc/".$filepath;
		}
		//Logger::log(__METHOD__." ".$filepath);
		
		return self::evaluate($filepath, $args);
//		if (is_array($args) || is_object($args)) {
//			// extract($__vars);
//			foreach ($args as $__k => $__v) {
//				$$__k = $__v;
//			}
//		}
//		return include $filepath;		
	}
	
	

}


?>