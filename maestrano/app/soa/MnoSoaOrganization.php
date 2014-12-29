<?php

/**
* Mno Organization Class
* Note for supplier organizations: by default, supplier organizations
* get treated as regular vTiger Accounts.
* The LINK_SUPPLIER_TO_VENDOR parameter allows us to change that behaviour
* and map supplier organizations to vTiger Vendors.
*/
class MnoSoaOrganization extends MnoSoaBaseOrganization
{
  # Should we map Supplier Organizations to vTiger Vendors
  # or should we consider them as Accounts (default)
  const IS_SUPPLIER_MAPPED_TO_VENDOR = false;
  
  protected $_local_entity_module = "Accounts";
  protected $_local_entity_name = "accounts";
  
  /*
   * Static accessor to class constant
   * Used in Maestrano Vendor hooks to check if we 
   * should persist the Vendor in Connec! when
   * it is saved in vTiger
   */
  public static function isSupplierMappedToVendor() {
    return self::IS_SUPPLIER_MAPPED_TO_VENDOR;
  }
  
  /*
   * Check whether this entity is supposed to be a vTiger
   * vendor or not
   * Return true if either the _local_entity is a vendor
   * or the "entity.supplier" attribute is true
   */
  public function isVendor() {
    $is_native_vendor = ($this->_local_entity && get_class($this->_local_entity) == "Vendors");
    $is_vendor_by_attribute = ($this->_entity && array_key_exists('supplier',$this->_entity) && $this->_entity['supplier']);
    
    return $is_native_vendor || $is_vendor_by_attribute;
  }
  
  /*
   * Assign the right _local_entity_module and _local_entity_name
   * to the instance based on:
   * 1) whether the instance is a vendor/supplier
   * 2) whether suppliers should be mapped to vTiger vendors 
   *   (see IS_SUPPLIER_MAPPED_TO_VENDOR constant)
   *
   * This method is used in both pullId and pushId as they
   * are the first methods used when serializing/deserializing
   * for Connec!
   */
  protected function evaluateModuleToUse() {
    if (IS_SUPPLIER_MAPPED_TO_VENDOR && $this->isVendor()) {
      $this->_local_entity_module = "Vendors";
      $this->_local_entity_name = "vendors";
    } else {
      $this->_local_entity_module = "Accounts";
      $this->_local_entity_name = "accounts";
    }
    
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " is: " . $this->_local_entity_module);
    
    return true;
  }
  
  /*
   * Return true if "Vendors" is the vTiger module
   * being used to map this Connec! entity
   */
  public function isUsingVendorsModule() {
    return $this->_local_entity_module == "Vendors";
  }
  
  /*
   * Return true if "Vendors" is the vTiger module
   * being used to map this Connec! entity
   */
  public function isUsingAccountsModule() {
    return $this->_local_entity_module == "Accounts";
  }
  
  protected function pushId() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start");
    
    // Evaluate which module should be used - Accounts or Vendors
    $this->evaluateModuleToUse();
    
    $id = $this->getLocalEntityIdentifier();
	
    if (!empty($id)) {
      $mno_id = $this->getMnoIdByLocalId($id);
	    
      if ($this->isValidIdentifier($mno_id)) {
        $this->_id = $mno_id->_id;
      }
    }
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end");
  }
    
  protected function pullId() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start");
      
    // Fail if no id
    if (empty($this->_id)) {
      $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " return STATUS_ERROR");
      return constant('MnoSoaBaseEntity::STATUS_ERROR');
    }
    
    // Evaluate which module should be used - Accounts or Vendors
    $this->evaluateModuleToUse();
    
    // Retrieve the local entity id using the Maestrano IdMap table
    $local_id = $this->getLocalIdByMnoId($this->_id);

    if ($this->isValidIdentifier($local_id)) {
      // Setup the vTiger entity
      $this->_local_entity = CRMEntity::getInstance($this->_local_entity_module);
      $this->_local_entity->retrieve_entity_info($local_id->_id,$this->_local_entity_module);
      vtlib_setup_modulevars($this->_local_entity_module, $this->_local_entity);
      
      $this->_local_entity->id = $local_id->_id;
      $this->_local_entity->mode = 'edit';
      $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " is STATUS_EXISTING_ID");
      return constant('MnoSoaBaseEntity::STATUS_EXISTING_ID');

    } else if ($this->isDeletedIdentifier($local_id)) {
      $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " is STATUS_DELETED_ID");
      return constant('MnoSoaBaseEntity::STATUS_DELETED_ID');

    } else {
      $this->_local_entity = new Accounts();
      $this->_local_entity->column_fields['assigned_user_id'] = "1";
      $this->pullName();
      return constant('MnoSoaBaseEntity::STATUS_NEW_ID');
    }
  }
  
  /*
   * Push the name from local entity to soa instance
   */ 
  protected function pushName() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    
    // Check which field to use
    $field_name = $this->isUsingVendorsModule() ? 'vendorname' : 'accountname';
    
    // Assign value
    $this->_name = $this->push_set_or_delete_value($this->_local_entity->column_fields[$field_name]);
    
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * Pull the name to local entity from soa instance
   */ 
  protected function pullName() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    
    // Check which field to use
    $field_name = $this->isUsingVendorsModule() ? 'vendorname' : 'accountname';
    
    // Assign value
    $this->_local_entity->column_fields[$field_name] = $this->pull_set_or_delete_value($this->_name);
    
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * Push the industry from local entity to soa instance
   * Only applicable to Accounts module
   */ 
  protected function pushIndustry() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    
    if ($this->isUsingAccountsModule()) {
      $industry = $this->push_set_or_delete_value($this->_local_entity->column_fields['industry']);
      if (strcmp($industry, '--None--') == 0 || strcmp($industry, 'Other') == 0) { $industry = ""; }
      $this->_industry = $industry;
    }
    
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * Pull the industry to local entity from soa instance
   * Only applicable to Accounts module
   */ 
  protected function pullIndustry() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    
    if ($this->isUsingAccountsModule()) {
      $this->_local_entity->column_fields['industry'] = $this->pull_set_or_delete_value($this->_industry);
    }
    
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * Push the annual revenue from local entity to soa instance
   * Only applicable to Accounts module
   */ 
  protected function pushAnnualRevenue() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    
    if ($this->isUsingAccountsModule()) {
      $annual_revenue = $this->getNumeric($this->_local_entity->column_fields['annual_revenue']);
      $this->_annual_revenue = $this->push_set_or_delete_value($annual_revenue);
    }
    
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * Pull the annual revenue to local entity from soa instance
   * Only applicable to Accounts module
   */ 
  protected function pullAnnualRevenue() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    
    if ($this->isUsingAccountsModule()) {
      $this->_local_entity->column_fields['annual_revenue'] = $this->pull_set_or_delete_value($this->_annual_revenue);
    }
    
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
    
  protected function pushCapital() {
    // DO NOTHING
  }
    
  protected function pullCapital() {
    // DO NOTHING
  }
  
  /*
   * Push the number of employees from local entity to soa instance
   * Only applicable to Accounts module
   */
  protected function pushNumberOfEmployees() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    
    if ($this->isUsingAccountsModule()) {
      $number_of_employees = $this->getNumeric($this->_local_entity->column_fields['employees']);
      $this->_number_of_employees = $this->push_set_or_delete_value($number_of_employees);
    }
    
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * Pull the number of employees to local entity from soa instance
   * Only applicable to Accounts module
   */
  protected function pullNumberOfEmployees() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    
    if ($this->isUsingAccountsModule()) {
      $this->_local_entity->column_fields['employees'] = $this->pull_set_or_delete_value($this->_number_of_employees);
    }
    
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * Push the local entity billing address to soa instance postal address
   * Push the local entity shipping address to soa instance street address (only applicable
   * to Accounts module)
   */
  protected function pushAddresses() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    
    // BILLING ADDRESS -> POSTAL ADDRESS
    // Field name prefix varies depending on the module being used (Accounts or Vendors)
    $addr_prefix = $this->isUsingVendorsModule() ? '' : 'bill_';
    $this->_address->postalAddress->streetAddress = $this->push_set_or_delete_value($this->_local_entity->column_fields[$addr_prefix . 'street']);
    $this->_address->postalAddress->locality = $this->push_set_or_delete_value($this->_local_entity->column_fields[$addr_prefix . 'city']);
    $this->_address->postalAddress->region = $this->push_set_or_delete_value($this->_local_entity->column_fields[$addr_prefix . 'state']);
    $this->_address->postalAddress->postalCode = $this->push_set_or_delete_value($this->_local_entity->column_fields[$addr_prefix . 'code']);
    $country_code = $this->mapCountryToISO3166($this->_local_entity->column_fields[$addr_prefix . 'country']);
    $this->_address->postalAddress->country = strtoupper($this->push_set_or_delete_value($country_code));
    
    
    // SHIPPING ADDRESS -> STREET ADDRESS
    // Only applicable to Accounts module
    if ($this->isUsingAccountsModule()) {
      $this->_address->streetAddress->streetAddress = $this->push_set_or_delete_value($this->_local_entity->column_fields['ship_street']);
      $this->_address->streetAddress->locality = $this->push_set_or_delete_value($this->_local_entity->column_fields['ship_city']);
      $this->_address->streetAddress->region = $this->push_set_or_delete_value($this->_local_entity->column_fields['ship_state']);
      $this->_address->streetAddress->postalCode = $this->push_set_or_delete_value($this->_local_entity->column_fields['ship_code']);
      $country_code = $this->mapCountryToISO3166($this->_local_entity->column_fields['ship_country']);
      $this->_address->streetAddress->country = strtoupper($this->push_set_or_delete_value($country_code));
    }
    
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * Pull the soa instance postal address into the local entity billing address
   * Pull the soa instance street address into the local entity shipping address (only applicable
   * to Accounts module)
   */
  protected function pullAddresses() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    
    // POSTAL ADDRESS -> BILLING ADDRESS
    // Field name prefix varies depending on the module being used (Accounts or Vendors)
    $addr_prefix = $this->isUsingVendorsModule() ? '' : 'bill_';
    $this->_local_entity->column_fields[$addr_prefix . 'street'] = $this->pull_set_or_delete_value($this->_address->postalAddress->streetAddress);
    $this->_local_entity->column_fields[$addr_prefix . 'city'] = $this->pull_set_or_delete_value($this->_address->postalAddress->locality);
    $this->_local_entity->column_fields[$addr_prefix . 'state'] = $this->pull_set_or_delete_value($this->_address->postalAddress->region);
    $this->_local_entity->column_fields[$addr_prefix . 'code'] = $this->pull_set_or_delete_value($this->_address->postalAddress->postalCode);
    $country = $this->mapISO3166ToCountry($this->_address->postalAddress->country);
    $this->_local_entity->column_fields[$addr_prefix . 'country'] = $this->pull_set_or_delete_value($country);
    
    // STREET ADDRESS -> SHIPPING ADDRESS
    if ($this->isUsingAccountsModule()) {
      $this->_local_entity->column_fields['ship_street'] = $this->pull_set_or_delete_value($this->_address->streetAddress->streetAddress);
      $this->_local_entity->column_fields['ship_city'] = $this->pull_set_or_delete_value($this->_address->streetAddress->locality);
      $this->_local_entity->column_fields['ship_state'] = $this->pull_set_or_delete_value($this->_address->streetAddress->region);
      $this->_local_entity->column_fields['ship_code'] = $this->pull_set_or_delete_value($this->_address->streetAddress->postalCode);
      $country = $this->mapISO3166ToCountry($this->_address->streetAddress->country);
      $this->_local_entity->column_fields['ship_country'] = $this->pull_set_or_delete_value($country);
    }
    
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * Push the emails from local entity to soa instance
   * Only one email available for Vendors module
   */
  protected function pushEmails() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    
    if ($this->isUsingVendorsModule()) {
      $this->_email->emailAddress = $this->push_set_or_delete_value($this->_local_entity->column_fields['email']);
    } else {
      $this->_email->emailAddress = $this->push_set_or_delete_value($this->_local_entity->column_fields['email1']);
      $this->_email->emailAddress2 = $this->push_set_or_delete_value($this->_local_entity->column_fields['email2']);
    }
    
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * Pull the emails to local entity from soa instance
   * Only one email available for Vendors module
   */
  protected function pullEmails() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    
    if ($this->isUsingVendorsModule()) {
      $this->_local_entity->column_fields['email'] = $this->pull_set_or_delete_value($this->_email->emailAddress);
    } else {
      $this->_local_entity->column_fields['email1'] = $this->pull_set_or_delete_value($this->_email->emailAddress);
      $this->_local_entity->column_fields['email2'] = $this->pull_set_or_delete_value($this->_email->emailAddress2);
    }
    
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * Push the phone number(s) from local entity to soa instance
   * Only one phone number available for Vendors module
   * Fax is only applicable to Accounts module
   */
  protected function pushTelephones() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    
    $this->_telephone->voice = $this->push_set_or_delete_value($this->_local_entity->column_fields['phone']);
    
    if ($this->isUsingAccountsModule()) {
      $this->_telephone->voice2 = $this->push_set_or_delete_value($this->_local_entity->column_fields['otherphone']);
      $this->_telephone->fax = $this->push_set_or_delete_value($this->_local_entity->column_fields['fax']);
    }
    
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * Pull the phone number(s) to local entity from soa instance
   * Only one phone number available for Vendors module
   * Fax is only applicable to Accounts module
   */
  protected function pullTelephones() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    
    $this->_local_entity->column_fields['phone'] = $this->pull_set_or_delete_value($this->_telephone->voice);
    
    if ($this->isUsingAccountsModule()) {
      $this->_local_entity->column_fields['otherphone'] = $this->pull_set_or_delete_value($this->_telephone->voice2);
      $this->_local_entity->column_fields['fax'] = $this->pull_set_or_delete_value($this->_telephone->fax);
    }
    
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * Push the website from local entity to soa instance
   */
  protected function pushWebsites() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    $this->_website->url = $this->push_set_or_delete_value($this->_local_entity->column_fields['website']);
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * Pull the website to local entity from soa instance
   */
  protected function pullWebsites() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    $this->_local_entity->column_fields['website'] = $this->pull_set_or_delete_value($this->_website->url, "");
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * Only used when we specifically map supplier organizations
   * to vendors. In this case, Accounts get mapped to "customer"
   * and Vendors get mapped to "supplier"
   */
  protected function pushEntity() {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    
    if (self::IS_SUPPLIER_MAPPED_TO_VENDOR) {
      $this->_entity = array();
      
      // Minimal assignment based known context (e.g: do not override
      // customer flag in Connec! just because it's a vendor in vTiger)
      if ($this->isUsingVendorsModule()) $this->_entity['supplier'] = true;
      if ($this->isUsingAccountsModule()) $this->_entity['customer'] = true;
    }
    
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * No need to pull the entity from local soa instance to
   * local entity as vTiger is unable to store whether accounts are
   * suppliers or customers.
   * When suppliers are explicitly mapped to vendors then the
   * nature of the local entity is obtained by using the
   * appropriate module (see evaluateModuleToUse)
   */
  protected function pullEntity() {
    // DO NOTHING
  }
  
  /*
   * Call the save method on the local entity
   * @param $push_to_maestrano whether to push the entity to Connec! or not
   */
  protected function saveLocalEntity($push_to_maestrano) {
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " start ");
    $this->_local_entity->save($this->_local_entity_module, '', $push_to_maestrano);
    $this->_log->debug(__CLASS__ . ' ' . __FUNCTION__ . " end ");
  }
  
  /*
   * Accessor method used to retrieve the id of the 
   * local entity
   */
  public function getLocalEntityIdentifier() {
    return $this->_local_entity->id;
  }
}

?>