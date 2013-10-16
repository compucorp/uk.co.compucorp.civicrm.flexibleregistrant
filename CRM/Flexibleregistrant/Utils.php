<?php

/**
 * Collection of Unitiy functions for Flexibile registrant
 */
class CRM_Flexibleregistrant_Utils{


    /**
    * A function to check if the event is configured to use the flexible price set
    *
    */
  static function isEventConfiguredToUseFlexiblePriceSet($eid){
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




}
