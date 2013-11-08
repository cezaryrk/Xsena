<?php


namespace Xsena\core\utils;

class URL {
	public $url = NULL;
	public $path = array();
	public $query = array();

	private static $__URL__ = null;

	function __construct($path = array(), $query = array()){
		if(is_array($path)){
			$this->path = $path;
		}elseif(is_string($path)){
			$path = preg_replace(array("/\/{0,1}(\?.*){0,1}$/","/^\//"),array("",""),$path);
			$this->path = explode("/",$path);
		}
		$this->updateURL();
		if(is_array($query)){
			$this->query = $query;
		}else if(is_string($query)){
			// TODO: rekursiv aufbauen
			$this->query = parse_url($query,PHP_URL_QUERY);
		}
	}
	
	private function updateURL(){
		$this->url = implode("/", $this->path);
	}
	
	function getPath($index = null){
		if(!empty($index) && is_numeric($index)){
			if(isset($this->path[$index])) return $this->path[$index]; 	
			return null; 
		}
		return $this->url; 
	}
	
	function getQuery($index = null){
		if(!empty($index)){
			if(isset($this->query[$index])) return $this->query[$index]; 	
			return null; 
		}
		return $this->query; 
		
	}
	
	function parseURL($pattern, $vars = array()){
		$matches = array();
		$result = array();
		if(preg_match("/".$pattern."/", $this->url,$matches)){			
			if(empty($vars)){
				return $matches;
			} 
			foreach($matches as  $key => $value){
				if(isset($vars[$key])){
					$result[$vars[$key]] = $value;
				}
			}
		}
		return $result;
	}
	
	
	static function current(){
		if(!isset(self::$__URL__)){
			self::$__URL__ = new URL($_SERVER['REQUEST_URI'],$_REQUEST);
		}
		return self::$__URL__;
	}

	static function create($path, $query){
		return new URL($path, $query);
	}

	function &query($key,$value){
		$this->query[$key] = $value;
		return $this;
	}
	

	function &path($path){
		$this->path[] = $path;
		$this->updateURL();
		return $this;
	}


	function build(){
		$url = $this->getPath();
		if(!empty($this->query)){
			$url .= "?".http_build_query($this->query);
		}
		return $url;
	}
	
	static function urlencode($data) {
		if (is_string($data)) return urlencode($data);
		if (is_object($data) || is_array($data)) {
			$parts = array();
			foreach ($data as $k => $v) {
				$parts[] = urlencode($k).'='.urlencode($v);
			}
			return implode('&', $parts);
		}
		throw new Exception("Unknown type for urlencode");
	}
}


?>