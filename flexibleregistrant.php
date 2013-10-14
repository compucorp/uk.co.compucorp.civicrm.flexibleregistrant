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
  civicrm_api('CustomGroup', 'delete', array(
    'version' => 3,
    'id' => CRM_Utils_Array::value('id',civicrm_api('CustomGroup', 'getsingle', array(
      'version' => 3,
      'name' => 'CUP_Event_Flexible_Configuration')
    )),
  ));
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
    $results = civicrm_api("Participant","get", array('version' => '3','sequential' =>'1', 'id' => $params['entity_id']));
    if(!empty($results['values'][0])){
      $participant = $results['values'][0];
      $eid = $participant['event_id'];
      $eresult =civicrm_api("Event","get", array ('version' => '3','sequential' =>'1', 'id' => $eid, 'return' => 'custom'));
      $event = $eresult['values']['0'];
      $custom = $event['custom_40']; //TODO: Looked up for the field name;
      if($custom == 1){
       $params['participant_count'] = 1; // force the participant count = 1

      }
    }
  }
}

function flexibleregistrant_civicrm_validateForm( $formName, &$fields, &$files, &$form, &$errors ) {
  if ( $formName == 'CRM_Event_Form_ManageEvent_Fee' ) {
    $eid = CRM_Utils_Array::value('id', $fields);
    if($eid){
      $isFlex = _isEventConfiguredToUseFlexiblePriceSet($eid);
      if($isFlex){
        if(!CRM_Utils_Array::value('price_set_id', $fields)){
          $errors['price_set_id'] = ts('This event is a flexible registrant event, please select a price set');
        }
      }
    }
  }
  return;
}


function flexibleregistrant_civicrm_buildForm($formName, &$form) {
  if($formName == 'CRM_Event_Form_Registration_Register' || $formName == 'CRM_Event_Form_Registration_AdditionalParticipant'){
    $isFlex = _isEventConfiguredToUseFlexiblePriceSet($form->_id);
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
  elseif ($formName == 'CRM_Event_Form_Registration_Confirm' || $formName == 'CRM_Event_Form_Registration_ThankYou'){
    $isFlex = _isEventConfiguredToUseFlexiblePriceSet($form->_id);
    if($isFlex){
      $form->assign('lineItem', NULL );
      $amount = $form->getVar('_amount');
      $params = $form->getVar('_params');
      //Rebuild amount
      foreach ($params as $k => $v) {
        if (is_array($v)) {
          foreach (array(
            'first_name', 'last_name') as $name) {
            if (isset($v['billing_' . $name]) &&
              !isset($v[$name])
            ) {
              $v[$name] = $v['billing_' . $name];
            }
          }

          if (CRM_Utils_Array::value('first_name', $v) && CRM_Utils_Array::value('last_name', $v)) {
              $append = $v['first_name'] . ' ' . $v['last_name'];
          }
          else {
            //use an email if we have one
            foreach ($v as $v_key => $v_val) {
              if (substr($v_key, 0, 6) == 'email-') {
                $append = $v[$v_key];
              }
            }
          }

          $amount[$k]['label'] = $append;
        }
      }
      $form->assign('amounts', $amount );
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


/**
* A function to check if the event is configured to use the flexible price set
*
*/
function _isEventConfiguredToUseFlexiblePriceSet($eid){
  $isConfigured = FALSE;
  if(!$eid){
    return $isConfigured;
  }
  $q = " SELECT use_flexible_price_set
         FROM   civicrm_value_cup_event_flexible_configuration
         WHERE entity_id = %1";
  $params = array( 1 => array( $eid, 'Integer' ) );
  $useFlexiblePriceSet = CRM_Core_DAO::singleValueQuery( $q, $params );
  if($useFlexiblePriceSet == 1){
    $isConfigured = TRUE;
  }
  return $isConfigured;
}


