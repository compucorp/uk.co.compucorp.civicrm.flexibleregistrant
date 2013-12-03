<?php

require_once 'flexibleregistrant.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function flexibleregistrant_civicrm_config(&$config) {
  _flexibleregistrant_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function flexibleregistrant_civicrm_xmlMenu(&$files) {
  _flexibleregistrant_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function flexibleregistrant_civicrm_install() {
  require_once 'CRM/Utils/Migrate/Import.php';
  $import = new CRM_Utils_Migrate_Import( );
  $extRoot = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
  $op = $extRoot  . 'xml' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'CustomGroupData.xml';
  $import->run( $op );
  // rebuild the menu so our path is picked up
  require_once 'CRM/Core/Invoke.php';
  CRM_Core_Invoke::rebuildMenuAndCaches( );
  return _flexibleregistrant_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function flexibleregistrant_civicrm_uninstall() {
  return _flexibleregistrant_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function flexibleregistrant_civicrm_enable() {
  return _flexibleregistrant_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function flexibleregistrant_civicrm_disable() {
  return _flexibleregistrant_civix_civicrm_disable();
}


function flexibleregistrant_civicrm_pre( $op, $objectName, $id, &$params ){
  if($objectName == 'LineItem'){
    $results = civicrm_api("Participant","get", array(
      'version' => '3',
      'sequential' =>'1',
      'id' => $params['entity_id'] ,
      'entity_table' => 'civicrm_participant'));
    if(!empty($results['values'][0])){
      $participant = $results['values'][0];
      $eventId = $participant['event_id'];
      $isFlex = CRM_Flexibleregistrant_Utils::isEventConfiguredToUseFlexiblePriceSet($eventId);
      if($isFlex){
        $params['participant_count'] = 1; // force the participant count = 1
      }
    }
  }
}

function flexibleregistrant_civicrm_validateForm( $formName, &$fields, &$files, &$form, &$errors ) {
  if ( $formName == 'CRM_Event_Form_ManageEvent_Fee' ) {
    if($fields['is_monetary']){
      $eid = CRM_Utils_Array::value('id', $fields);
      if($eid){
        $isFlex = CRM_Flexibleregistrant_Utils::isEventConfiguredToUseFlexiblePriceSet($eid);
        if($isFlex){
          $priceSetId = CRM_Utils_Array::value('price_set_id', $fields);
          if(!$priceSetId){
            $errors['price_set_id'] = ts('This event is a flexible registrant event, please select a price set');
          }else{
            $isValid = FALSE;
            $priceFields = civicrm_api("PriceField","get", array ('version' => '3', 'price_set_id' => $priceSetId));
            if(CRM_Utils_Array::value('count', $priceFields) == 1){
               $priceFieldValue = civicrm_api("PriceFieldValue","get", array (
                'version' => '3',
                'sequential' =>'1',
                'price_field_id' => CRM_Utils_Array::value('id', $priceFields)
               ));
               foreach ($priceFieldValue['values'] as $key => $value) {
                if( CRM_Utils_Array::value('amount', $value) == 0 && CRM_Utils_Array::value('is_default', $value) == 1){
                  $isValid = TRUE;
                  return;
                }
               }
            }
            if(!$isValid){
              $errors['price_set_id'] = ts('The selected price set is invalid for flexible price set event');
            }
          }
        }
      }
    }else{
      $errors['is_monetary'] = ts('this event is using flexible price set, therefore must be paid event');
    }
  }
  return;
}

function flexibleregistrant_civicrm_pageRun( &$page ){
  if(get_class($page) == 'CRM_Event_Page_EventInfo'){}

}


function flexibleregistrant_civicrm_buildForm($formName, &$form) {
  if($formName == 'CRM_Event_Form_Registration_Register' || $formName == 'CRM_Event_Form_Registration_AdditionalParticipant'){
    $isFlex = CRM_Flexibleregistrant_Utils::isEventConfiguredToUseFlexiblePriceSet($form->_eventId);
    if($isFlex){
      switch ($formName) {
        case 'CRM_Event_Form_Registration_Register':
          CRM_Core_Resources::singleton()->addScriptFile(
            'uk.co.compucorp.civicrm.flexibleregistrant',
            'templates/CRM/Event/Form/Registration/register.js',
            10,
            'page-footer');
          CRM_Core_Resources::singleton()->addStyleFile(
            'uk.co.compucorp.civicrm.flexibleregistrant',
            'css/register.css');
          break;
         case 'CRM_Event_Form_Registration_AdditionalParticipant':
          CRM_Core_Resources::singleton()->addStyleFile(
            'uk.co.compucorp.civicrm.flexibleregistrant',
            'css/add_participant.css');
          break;
        }
    }
  }
  /* CPM-201 */
  elseif ($formName == 'CRM_Event_Form_Registration_Confirm' || $formName == 'CRM_Event_Form_Registration_ThankYou'){
    $isFlex = CRM_Flexibleregistrant_Utils::isEventConfiguredToUseFlexiblePriceSet($form->_eventId);
    if($isFlex){
      
      $amount = $form->getVar('_amount');
      $params = $form->getVar('_params');

      //calculate participants
      $skipCount = 0;
      $participantCount = 0;
      foreach ($params as $k => $v) {
        $participantCount++;
        if(($v == 'skip') && !is_array($v)){
          $skipCount++;
        }
      }
      $form->assign('skip_count',$skipCount);
      $form->assign('total_count',$participantCount);
      
      //add modified content
      CRM_Core_Region::instance('page-body')->add(array(
      'template' => 'CRM/Event/Form/Registration/Eventfee.tpl'
      ));
      
      
    }
  }
}



/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function flexibleregistrant_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _flexibleregistrant_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function flexibleregistrant_civicrm_managed(&$entities) {
  return _flexibleregistrant_civix_civicrm_managed($entities);
}


