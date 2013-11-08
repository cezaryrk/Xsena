<?php

namespace Xsena\core\classes;

class ClassMember extends Object {

  const PRIVATE_ACCESS = 0;
  const PROTECTED_ACCESS = 1;
  const PUBLIC_ACCESS = 2;
  
  const VARIABLE_MEMBER = 0;
  const STATIC_MEMBER = 1;
  const CONSTANT_MEMBER = 2;
  
  //   private $propertyName;
  private $accessType = self::PUBLIC_ACCESS;  
  private $memberType = self::VARIABLE_MEMBER;
  
  private $localName;
  private $declaringClass;
  

  /**
   * 
   * Enter description here ...
   * @return xfClass
   */
  public function &getClass(){
    return $this->declaringClass;
  }

  public function setClass(xfClass &$class){
    $this->declaringClass = $class;
  }
  
  
  function getLocalName(){
    return $this->localName;
  }
  
  function setLocalName($localName){
    $this->localName = $localName;
  }
  
  function setAccessType($access_type){
    $this->accessType = $access_type;
  }

  
  function getAccessType(){
    return $this->accessType;
  }

  
  function getMemberType(){
    return $this->memberType;
  }
  
  function setMemberType($type){
    $this->memberType = $type;
  }
  
  function isStatic(){
    return $this->memberType == self::STATIC_MEMBER;
  }
  
  function isConstant(){
    return $this->memberType == self::CONSTANT_MEMBER;
  }
  
  function isVariable(){
    return $this->memberType == self::VARIABLE_MEMBER;
  }
  
  function isPrivate(){
    return $this->accessType == self::PRIVATE_ACCESS;
  }

  function isProtected(){
    return $this->accessType == self::PROTECTED_ACCESS;
  }

  function isPublic(){
    return $this->accessType == self::PUBLIC_ACCESS;
  }
  
}