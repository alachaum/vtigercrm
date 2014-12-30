<?php

/**
 * Mno Organization Class
 */
class MnoSoaPerson extends MnoSoaBasePerson
{
  protected $_local_entity_name = "contacts";
  protected $_skip_save = false;
  
  protected function pushId() {
    $this->_log->debug(__FUNCTION__ . " start");
    $id = $this->getLocalEntityIdentifier();
    
    if (!empty($id)) {
      $mno_id = $this->getMnoIdByLocalId($id);
      
      if ($this->isValidIdentifier($mno_id)) {
        $this->_log->debug(__FUNCTION__ . " this->getMnoIdByLocalId(id) = " . json_encode($mno_id));
        $this->_id = $mno_id->_id;
      }
    }
    $this->_log->debug(__FUNCTION__ . " end");
  }
  
  protected function pullId() {
    $this->_log->debug(__FUNCTION__ . " start " . $this->_id);
    
    if (!empty($this->_id)) {
      $local_id = $this->getLocalIdByMnoId($this->_id);
      $this->_log->debug(__FUNCTION__ . " this->getLocalIdByMnoId(this->_id) = " . json_encode($local_id));
      
      if ($this->isValidIdentifier($local_id)) {
        $this->_log->debug(__FUNCTION__ . " is STATUS_EXISTING_ID");
        $this->_local_entity = CRMEntity::getInstance("Contacts");
        $this->_local_entity->retrieve_entity_info($local_id->_id,"Contacts");
        vtlib_setup_modulevars("Contacts", $this->_local_entity);
        $this->_local_entity->id = $local_id->_id;
        $this->_local_entity->mode = 'edit';
        return constant('MnoSoaBaseEntity::STATUS_EXISTING_ID');
      
      } else if ($this->isDeletedIdentifier($local_id)) {
        $this->_log->debug(__FUNCTION__ . " is STATUS_DELETED_ID");
        return constant('MnoSoaBaseEntity::STATUS_DELETED_ID');
      
      } else {
        $this->_local_entity = new Contacts();
        $this->_local_entity->column_fields['assigned_user_id'] = "1";
        $this->pullName();
        return constant('MnoSoaBaseEntity::STATUS_NEW_ID');
      }
    }
    $this->_log->debug(__FUNCTION__ . " return STATUS_ERROR");
    return constant('MnoSoaBaseEntity::STATUS_ERROR');
  }
  
  protected function pushName() {
    $this->_log->debug(__FUNCTION__ . " start");
    $this->_name->title = $this->push_set_or_delete_value($this->_local_entity->column_fields['salutationtype']);
    $this->_name->givenNames = $this->push_set_or_delete_value($this->_local_entity->column_fields['firstname']);
    $this->_name->familyName = $this->push_set_or_delete_value($this->_local_entity->column_fields['lastname']);
    $this->_log->debug(__FUNCTION__ . " end");
  }
  
  protected function pullName() {
    $this->_log->debug(__FUNCTION__ . " start");
    $this->_local_entity->column_fields['salutationtype'] = $this->pull_set_or_delete_value($this->_name->title);
    $this->_local_entity->column_fields['firstname'] = $this->pull_set_or_delete_value($this->_name->givenNames);
    $this->_local_entity->column_fields['lastname'] = $this->pull_set_or_delete_value($this->_name->familyName);
    $this->_log->debug(__FUNCTION__ . " end");
  }
  
  protected function pushBirthDate() {
    $this->_log->debug(__FUNCTION__ . " start");
    $this->_birth_date = $this->push_set_or_delete_value($this->_local_entity->column_fields['birthday']);
    $this->_log->debug(__FUNCTION__ . " end");
  }
  
  protected function pullBirthDate() {
    $this->_log->debug(__FUNCTION__ . " start");
    $this->_local_entity->column_fields['birthday'] = $this->pull_set_or_delete_value($this->_birth_date);
    $this->_log->debug(__FUNCTION__ . " end");
  }
  
  protected function pushGender() {
  // DO NOTHING
  }
  
  protected function pullGender() {
  // DO NOTHING
  }
  
  protected function pushAddresses() {
    $this->_log->debug(__FUNCTION__ . " start");
        // MAILING ADDRESS -> POSTAL ADDRESS
    $this->_address->work->postalAddress->streetAddress = $this->push_set_or_delete_value($this->_local_entity->column_fields['mailingstreet']);
    $this->_address->work->postalAddress->locality = $this->push_set_or_delete_value($this->_local_entity->column_fields['mailingcity']);
    $this->_address->work->postalAddress->region = $this->push_set_or_delete_value($this->_local_entity->column_fields['mailingstate']);
    $this->_address->work->postalAddress->postalCode = $this->push_set_or_delete_value($this->_local_entity->column_fields['mailingzip']);
    $country_code = $this->mapCountryToISO3166($this->_local_entity->column_fields['mailingcountry']);
    $this->_address->work->postalAddress->country = strtoupper($this->push_set_or_delete_value($country_code));
        // OTHER ADDRESS -> POSTAL ADDRESS #2
    $this->_address->work->postalAddress2->streetAddress = $this->push_set_or_delete_value($this->_local_entity->column_fields['otherstreet']);
    $this->_address->work->postalAddress2->locality = $this->push_set_or_delete_value($this->_local_entity->column_fields['othercity']);
    $this->_address->work->postalAddress2->region = $this->push_set_or_delete_value($this->_local_entity->column_fields['otherstate']);
    $this->_address->work->postalAddress2->postalCode = $this->push_set_or_delete_value($this->_local_entity->column_fields['otherzip']);
    $country_code = $this->mapCountryToISO3166($this->_local_entity->column_fields['othercountry']);
    $this->_address->work->postalAddress2->country = strtoupper($this->push_set_or_delete_value($country_code));
    $this->_log->debug(__FUNCTION__ . " end");
  }
  
  protected function pullAddresses() {
    $this->_log->debug(__FUNCTION__ . " start");
        // POSTAL ADDRESS -> MAILING ADDRESS
    $this->_local_entity->column_fields['mailingstreet'] = $this->pull_set_or_delete_value($this->_address->work->postalAddress->streetAddress);
    $this->_local_entity->column_fields['mailingcity'] = $this->pull_set_or_delete_value($this->_address->work->postalAddress->locality);
    $this->_local_entity->column_fields['mailingstate'] = $this->pull_set_or_delete_value($this->_address->work->postalAddress->region);
    $this->_local_entity->column_fields['mailingzip'] = $this->pull_set_or_delete_value($this->_address->work->postalAddress->postalCode);
    $country = $this->mapISO3166ToCountry($this->_address->work->postalAddress->country);
    $this->_local_entity->column_fields['mailingcountry'] = $this->pull_set_or_delete_value($country);
        // POSTAL ADDRESS #2 -> OTHER ADDRESS
    $this->_local_entity->column_fields['otherstreet'] = $this->pull_set_or_delete_value($this->_address->work->postalAddress2->streetAddress);
    $this->_local_entity->column_fields['othercity'] = $this->pull_set_or_delete_value($this->_address->work->postalAddress2->locality);
    $this->_local_entity->column_fields['otherstate'] = $this->pull_set_or_delete_value($this->_address->work->postalAddress2->region);
    $this->_local_entity->column_fields['otherzip'] = $this->pull_set_or_delete_value($this->_address->work->postalAddress2->postalCode);
    $country = $this->mapISO3166ToCountry($this->_address->work->postalAddress2->country);
    $this->_local_entity->column_fields['othercountry'] = $this->pull_set_or_delete_value($country);
    $this->_log->debug(__FUNCTION__ . " end");
  }
  
  protected function pushEmails() {
    $this->_log->debug(__FUNCTION__ . " start ");
    $this->_email->emailAddress = $this->push_set_or_delete_value($this->_local_entity->column_fields['email']);
    $this->_email->emailAddress2 = $this->push_set_or_delete_value($this->_local_entity->column_fields['secondaryemail']);
    $this->_log->debug(__FUNCTION__ . " end ");
  }
  
  protected function pullEmails() {
    $this->_log->debug(__FUNCTION__ . " start ");
    $this->_local_entity->column_fields['email'] = $this->pull_set_or_delete_value($this->_email->emailAddress);
    $this->_local_entity->column_fields['secondaryemail'] = $this->pull_set_or_delete_value($this->_email->emailAddress2);
    $this->_log->debug(__FUNCTION__ . " end ");
  }
  
  
  protected function pushTelephones() {
    $this->_log->debug(__FUNCTION__ . " start ");
    $this->_telephone->work->voice = $this->push_set_or_delete_value($this->_local_entity->column_fields['phone']);
    $this->_telephone->home->mobile = $this->push_set_or_delete_value($this->_local_entity->column_fields['mobile']);
    $this->_telephone->home->voice = $this->push_set_or_delete_value($this->_local_entity->column_fields['homephone']);
    $this->_telephone->work->voice2 = $this->push_set_or_delete_value($this->_local_entity->column_fields['otherphone']);
    $this->_telephone->work->fax = $this->push_set_or_delete_value($this->_local_entity->column_fields['fax']);
    $this->_log->debug(__FUNCTION__ . " end ");
  }
  
  protected function pullTelephones() {
    $this->_log->debug(__FUNCTION__ . " start ");
    $this->_local_entity->column_fields['phone'] = $this->pull_set_or_delete_value($this->_telephone->work->voice);
    $this->_local_entity->column_fields['mobile'] = $this->pull_set_or_delete_value($this->_telephone->home->mobile);
    $this->_local_entity->column_fields['homephone'] = $this->pull_set_or_delete_value($this->_telephone->home->voice);
    $this->_local_entity->column_fields['otherphone'] = $this->pull_set_or_delete_value($this->_telephone->work->voice2);
    $this->_local_entity->column_fields['fax'] = $this->pull_set_or_delete_value($this->_telephone->work->fax);
    $this->_log->debug(__FUNCTION__ . " end ");
  }
  
  protected function pushWebsites() {
  // DO NOTHING
  }
  
  protected function pullWebsites() {
  // DO NOTHING
  }

  protected function pushNotes() {
    global $adb;

    $this->_log->debug(__FUNCTION__ . " start");

    $this->_notes = array();
    
    try {
      // Select notes related to this person
      $id = $this->_local_entity->column_fields['record_id'];
      if(isset($id)) {
        $this->_log->debug(__FUNCTION__ . " fetching notes related to person " . json_encode($id));

        $entityInstance = CRMEntity::getInstance("ModComments");
        vtlib_setup_modulevars("ModComments", $this->_local_entity);
        $queryCriteria = sprintf(" ORDER BY %s.%s DESC ", $entityInstance->table_name, $entityInstance->table_index);
        $query = $entityInstance->getListQuery($moduleName, sprintf(" AND %s.related_to=?", $entityInstance->table_name));
        $query .= $queryCriteria;
        $result = $adb->pquery($query, array($id));
        if($adb->num_rows($result)) {
          while($resultrow = $adb->fetch_array($result)) {
            $this->_log->debug(__FUNCTION__ . " fetched note: " . json_encode($resultrow));
            $comment_local_id = $resultrow['crmid'];
            $comment_description = $resultrow['commentcontent'];
            
            // TODO: Causing exception, see https://discussions.vtiger.com/index.php?p=/discussion/44222/bug-when-creating-comments/p1
            $comment_mno_id = $this->getMnoIdByLocalIdName($comment_local_id, "mod_comments");
            if (!$this->isValidIdentifier($comment_mno_id)) {
              // Generate and save ID
              $comment_mno_id = uniqid();
              $this->_mno_soa_db_interface->addIdMapEntry($comment_local_id, "mod_comments", $comment_mno_id, "notes");
            }

            $this->_notes[$comment_mno_id] = array("description" => $resultrow['commentcontent']);
          }
        }
      }
    } catch (Exception $e) {
      $this->_log->warn('Caught exception: ' . $e->getMessage());
    }

    $this->_log->debug(__FUNCTION__ . " end");
  }

  protected function pullNotes() {
    // DO NOTHING
  }

  protected function pushTasks() {
    global $adb;

    $this->_log->debug(__FUNCTION__ . " start");

    $this->_tasks = array();
    $id = $this->getLocalEntityIdentifier();
    $select_activities = "SELECT vtiger_activity.*, vtiger_crmentity.description, vtiger_crmentity.smownerid " .
             "FROM vtiger_cntactivityrel " .
             "  JOIN vtiger_activity ON (vtiger_cntactivityrel.activityid = vtiger_activity.activityid) " .
             "  JOIN vtiger_crmentity ON (vtiger_cntactivityrel.activityid = vtiger_crmentity.crmid) " .
             "WHERE vtiger_cntactivityrel.contactid = ?";
    $activity_row = $adb->pquery($select_activities, array($id));
    if($adb->num_rows($activity_row)) {
      while($resultrow = $adb->fetch_array($activity_row)) {
        $this->_log->debug(__FUNCTION__ . " fetched task: " . json_encode($resultrow));
        
        $task_local_id = $resultrow['activityid'];
        $task_name = $resultrow['subject'];
        $task_description = $resultrow['description'];
        $task_status = $resultrow['status'];
        $task_start_date = strtotime($resultrow['date_start']);
        $task_due_date = strtotime($resultrow['due_date']);
        $smownerid = $resultrow['smownerid'];

        // Fetch user assigned to activity
        $select_user = "SELECT vtiger_users.mno_uid " .
             "FROM vtiger_users " .
             "WHERE vtiger_users.id = ?";
        $user_row = $adb->pquery($select_user, array($smownerid));
        $task_assigned_to = $adb->query_result($user_row, 0, 'mno_uid');
        
        $mno_entity = $this->getMnoIdByLocalIdName($task_local_id, "ACTIVITY");
        if (!$this->isValidIdentifier($mno_entity)) {
          // Generate and save ID
          $task_mno_id = uniqid();
          $this->_mno_soa_db_interface->addIdMapEntry($task_local_id, "ACTIVITY", $task_mno_id, "ACTIVITY");
        } else {
          $task_mno_id = $mno_entity->_id;
        }

        $task = array(
          "id" => $task_mno_id,
          "name" => $task_name,
          "description" => $task_description,
          "status" => $task_status,
          "startDate" => $task_start_date,
          "dueDate" => $task_due_date,
          "assignedTo" => array($task_assigned_to => "ACTIVE")
        );
        $this->_tasks[$task_mno_id] = $task;
      }
    }

    $this->_log->debug(__FUNCTION__ . " end");
  }

  protected function pullTasks() {
    // DO NOTHING
  }
  
  protected function pushEntity() {
    // DO NOTHING
  }
  
  protected function pullEntity() {
    // DO NOTHING
  }
  
  protected function pushRole() {
    $local_id = $this->_local_entity->column_fields['account_id'];
    
    if (!empty($local_id)) {
      $mno_id = $this->getMnoIdByLocalIdName($local_id, 'accounts');
      
      if ($this->isValidIdentifier($mno_id)) {
        $this->_log->debug(__FUNCTION__ . " mno_id = " . json_encode($mno_id));
        $this->_role->organization->id = $mno_id->_id;
        $this->_role->title = $this->push_set_or_delete_value($this->_local_entity->column_fields['title']);
      } else if ($this->isDeletedIdentifier($mno_id)) {
            // do not update
        return;
      } else {
        $org_contact = CRMEntity::getInstance("Accounts");
        $org_contact->retrieve_entity_info($local_id,"Accounts");
        vtlib_setup_modulevars("Accounts", $this->_local_entity);
        $org_contact->id = $local_id;
        
        $organization = new MnoSoaOrganization($this->_db, $this->_log);
        $status = $organization->send($org_contact);

        if ($status) {
          $mno_id = $this->getMnoIdByLocalIdName($local_id, "accounts");

          if ($this->isValidIdentifier($mno_id)) {
            $this->_role->organization->id = $mno_id->_id;
            $this->_role->title = $this->push_set_or_delete_value($this->_local_entity->column_fields['title']);
          }
        }
      }
    } else {
      $this->_role = (object) array();
    }
  }
  
  /*
   * Pull role (relation to organization) to local instance from soa instance
   * If the person is related to a Vendor then the contact DOES NOT get persisted
   * as vTiger is unable to properly manage Vendor contacts (there is a relation but
   * it's absolutely not functional and more confusing than anything else)
   */
  protected function pullRole() {
    if (empty($this->_role->organization->id)) {
      $this->_local_entity->column_fields['account_id'] = "";
      $this->_local_entity->column_fields['title'] = "";
    } else {
      $local_id = $this->getLocalIdByMnoIdName($this->_role->organization->id, "organizations");
      
      // Check local_id is not related to a Vendor
      // Flag the local instance as "not to be saved" if that's the case
      if (!is_null($local_id)) {        
        if ($local_id->_entity == "VENDORS") {
          $this->_skip_save = true;
          return false;
        }
      }
      
      if ($this->isValidIdentifier($local_id)) {
        $this->_log->debug(__FUNCTION__ . " local_id = " . json_encode($local_id));
        $this->_local_entity->column_fields['account_id'] = $this->pull_set_or_delete_value($local_id->_id);
        $this->_local_entity->column_fields['title'] = $this->pull_set_or_delete_value($this->_role->title);
      } else if ($this->isDeletedIdentifier($local_id)) {
                // do not update
        return;
      } else {
        $notification->entity = "organizations";
        $notification->id = $this->_role->organization->id;
        $organization = new MnoSoaOrganization($this->_db, $this->_log);    
        $status = $organization->receiveNotification($notification);
        
        if ($status) {
          // If related organization is mapped to a Vendor
          // then the person does not get saved (see above)
          if ($organization->isUsingVendorsModule()) {
            $this->_skip_save = true;
            return false;
          } else {
            $this->_local_entity->column_fields['account_id'] = $this->pull_set_or_delete_value($organization->getLocalEntityIdentifier());
            $this->_local_entity->column_fields['title'] = $this->pull_set_or_delete_value($this->_role->title);
          }
        }
      }
    }
  }
  
  /*
   * Save the local entity in databse
   * If the person is related to a Vendor then the contact DOES NOT get persisted
   * as vTiger is unable to properly manage Vendor contacts (there is a relation but
   * it's absolutely not functional and more confusing than anything else)
   */
  protected function saveLocalEntity($push_to_maestrano) {
    $this->_log->debug(__FUNCTION__ . " start");
    
    if ($this->_skip_save) {
      $this->_log->debug(__FUNCTION__ . " skipping save as person is related to Vendor (and not Account)");
    } else {
      $this->_local_entity->save("Contacts", '', $push_to_maestrano);
      $this->_log->debug(__FUNCTION__ . " save notes");
      $this->saveNotes();
      $this->_log->debug(__FUNCTION__ . " save tasks");
      $this->saveTasks();
    }
    
    $this->_log->debug(__FUNCTION__ . " end");
  }

  protected function saveNotes() {
    if (!empty($this->_notes)) {
      $mno_person_note = new MnoSoaPersonNotes($this->_db, $this->_log, $this);
      $mno_person_note->receive($this->_notes);
    }
  }

  protected function saveTasks() {
    if (!empty($this->_tasks)) {
      $mno_person_activity = new MnoSoaPersonActivities($this->_db, $this->_log, $this);
      $mno_person_activity->receive($this->_tasks);
    }
  }

  protected function mapSalutationToHonorificPrefix($in) {
    $in_form = strtoupper(trim($in));
    
    switch ($in_form) {
      case "MR.": return "MR";
      case "MS.": return "MS";
      case "MRS.": return "MRS";
      case "DR.": return "DR";
      case "PROF.": return "PROF";
      default: return null;
    }
  }

  protected function mapHonorificPrefixToSalutation($in) {
    $in_form = strtoupper(trim($in));
    
    switch ($in_form) {
      case "MR": return "MR.";
      case "MS": return "MS.";
      case "MRS": return "MRS.";
      case "DR": return "DR.";
      case "PROF": return "PROF.";
      default: return null;
    }
  }
  
  public function getLocalEntityIdentifier() {
    return $this->_local_entity->id;
  }
}

?>