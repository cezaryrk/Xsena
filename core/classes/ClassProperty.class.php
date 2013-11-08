<?php


namespace Xsena\core\classes;

class ClassProperty extends ClassMember {
  
  
  private $type;
  
  private $defaultValue = null;
  
  
  public function getDefaultValue(){
    return $this->defaultValue;
  }
  
  // TODO this can be also raw mode
  public function setDefaultValue($defaultValue){
    $this->defaultValue = $defaultValue;
  }
  
  public function hasDefaultValue(){
    return isset($this->defaultValue);
  }
  
//   function __construct($class, $propertyName){
//     parent::__construct($class);
//     $this->setPropertyName($propertyName);
//   }
  
//   function getPropertyName(){
//     return $this->propertyName;
//   }
  
//   function setPropertyName($propertyName){
//     $this->propertyName = $propertyName;
//   }
  
}