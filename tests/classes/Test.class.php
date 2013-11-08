<?php

namespace Xsena\tests;

use dfw\drupal\components\form\Form;
use Xsena\core\logging\Logger;
class Test {
	
  
  function test(){
  	
    module_load_include('inc', 'welt_in_zahlen','welt_in_zahlen.admin');
    $form = array();
    $form_state = array();
    $build = _welt_in_zahlen_excel_upload_form($form, $form_state);
    
    Logger::log($build);
    
//     $output = $form->serialize();
//     Logger::log($output);
    
    
//     $copy = $form->getArrayCopy();
//     Logger::log($copy);
    
    
    
  }
}