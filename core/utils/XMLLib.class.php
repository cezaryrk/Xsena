<?php

namespace Xsena\core\utils;

class XMLLib {

	static function addFragment(DomDocument $dom, $data){
		$node =& $dom->createDocumentFragment();
		$node->appendXML($data);
		$dom->documentElement->appendChild($node);		
	}
	
	static function transform($xml, $xslt, $version = '1.0',$encoding = LSF_ENCODING){
		$xmlDoc = self::getDocument($xml,$version,$encoding);
		$xslDoc = self::getDocument($xslt,$version,$encoding);

		$proc = new XSLTProcessor();
		$proc->importStylesheet($xslDoc);
		$erg = $proc->transformToXML($xmlDoc);
		return $erg;
	}

	static function &getDocument($xml,$version = '1.0',$encoding = LSF_ENCODING){
		if($xml instanceof DOMDocument) return $xml;
		$xmlDoc = new DOMDocument($version,$encoding);
		$xmlDoc->formatOutput = true;

		if(preg_match("/\s*</",$xml)){
			$xmlDoc->loadXML($xml);
		}elseif(file_exists($xml)){
			$xmlDoc->load($xml);
		}else{
			
			// TODO throw exception!
			return null;
		}
		return $xmlDoc;
	}
}
