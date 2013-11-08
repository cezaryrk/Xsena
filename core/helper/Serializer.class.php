<?php

namespace Xsena\core\helper;


class Serializer {
	
  static $SERIALIZE = 'serialize';
  
  static $UNSERIALIZE = 'unserialize';
  
  public static function serialize($value, $override = null){
  	return call_user_func(self::$SERIALIZE,$value);
  }

  public static function unserialize($value, $override = null){
    return call_user_func(self::$UNSERIALIZE,$value);
  }
  
}