<?php

namespace Xsena\core\objects;


abstract class Object {
	// const REGISTRY_CLASS = "xfRegistry";
	
// 	private $__OID;
// 	private $__NAME;
// 	private $__QUANTIFIED_NAME; // TODO
	
	
// 	function __construct($name = null){
// 		if(is_null($name) || !is_string($name)) {
// 			$this->__NAME = $this->getClassName();					
// 		} else {
// 			$this->__NAME = $name;
// 		}
// 		if($this instanceof xfSingleton){
// 			$this->__OID = $this->__NAME;
// 		}else{
// 			$this->__OID = StringUtil::random();
// 		}
// 		// Logger::log(__METHOD__." ".$this->__OID.", ".$this->__NAME);
// 	}
	
	
// 	function getLocalName(){		
// 		return $this->__NAME;	
// 	}
	
// 	function setLocalName($name){
// 		$this->__NAME = $name;
// // 		xfRegistry::updateName($this);
// 	}
	
	
// 	function getOID(){
// 		return $this->__OID;
// 	}
	
// 	function getObjectID(){
// 		return $this->__OID;
// 	}
	
	function getClassName(){		
		return strtolower(get_class($this));	
	}
	
// 	/**
// 	 * 
// 	 * @return xfApplication
// 	 */
// 	function &getApplication(){
// 		return xfRegistry::getCurrentApplcation();
// 	}
	
// 	function getObjectType(){
// 		return xfRegistry::getObjectType($this);
// 	}

}
?>