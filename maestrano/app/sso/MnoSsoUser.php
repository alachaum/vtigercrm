<?php

/**
 * Configure App specific behavior for 
 * Maestrano SSO
 */
class MnoSsoUser extends MnoSsoBaseUser
{
  /**
   * Database connection
   * @var PDO
   */
  public $connection = null;
  
  /**
   * Application Unique Key
   * @var PDO
   */
  public $app_unique_key = '';
  
  /**
   * Vtiger User object
   * @var PDO
   */
  public $_user = null;
  
  /**
   * Extend constructor to inialize app specific objects
   *
   * @param OneLogin_Saml_Response $saml_response
   *   A SamlResponse object from Maestrano containing details
   *   about the user being authenticated
   */
  public function __construct(OneLogin_Saml_Response $saml_response, &$session = array(), $opts = array())
  {
    // Call Parent
    parent::__construct($saml_response,$session);
    
    // Define global log
    global $log;
    $log = LoggerManager::getLogger('user');
    
    // Assign new attributes
    $this->connection = $opts['db_connection'];
    $this->app_unique_key = $opts['app_unique_key'];
    $this->_user = new Users();
  }
  
  
  /**
   * Sign the user in the application. 
   * Parent method deals with putting the mno_uid, 
   * mno_session and mno_session_recheck in session.
   *
   * @return boolean whether the user was successfully set in session or not
   */
  protected function setInSession()
  {
    
    if ($this->local_id) {
        // Get user language and username
        $username = '';
        $lang = 'en_us';
        
        $query = "SELECT language,user_name from vtiger_users where id=?";
        $result = $this->connection->pquery($query, array($this->local_id));
        if ($result) {
          $tmp_lang = $this->connection->query_result($result,0,'language');
          if ($tmp_lang && $tmp_lang != '') { $lang = $tmp_lang; }
          
          $username = $this->connection->query_result($result,0,'user_name');
        }
        
        // Set session
        $this->session['authenticated_user_id'] = $this->local_id;
        $this->session['app_unique_key'] = $this->app_unique_key;
        $this->session['authenticated_user_language'] = $lang;
        
        // Record login
        $usip = $_SERVER['HTTP_X_REAL_IP'] ? $_SERVER['HTTP_X_REAL_IP'] : "192.168.1.1";
        $intime=date("Y/m/d H:i:s");
        $loghistory=new LoginHistory();
        $Signin = $loghistory->user_login($username,$usip,$intime);
        
        return true;
    } else {
        return false;
    }
  }
  
  
  /**
   * Used by createLocalUserOrDenyAccess to create a local user 
   * based on the sso user.
   * If the method returns null then access is denied
   *
   * @return the ID of the user created, null otherwise
   */
  protected function createLocalUser()
  {
    $lid = null;
    
    if ($this->accessScope() == 'private') {
      // Build local user
      $this->buildLocalUser();
      
      // Save user and get id
      $this->_user->save('Users');
      $lid = $this->_user->id;
    }
    
    return $lid;
  }
  
  /**
   * Get the ID of a local user via Maestrano UID lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByUid()
  {    
    // Fetch record
    $query = "SELECT id from vtiger_users where mno_uid=?";
    $result = $this->connection->pquery($query, array($this->uid));
    
    // Return id value
    if ($result) {
      return $this->connection->query_result($result,0,'id');
    }
    
    return null;
  }
  
  /**
   * Get the ID of a local user via email lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByEmail()
  {
    // Fetch record
    $query = "SELECT id from vtiger_users where email1=?";
    $result = $this->connection->pquery($query, array($this->email));
    
    // Return id value
    if ($result) {
      return $this->connection->query_result($result,0,'id');
    }
    
    return null;
  }
  
  /**
   * Set all 'soft' details on the user (like name, surname, email)
   * Implementing this method is optional.
   *
   * @return boolean whether the user was synced or not
   */
   protected function syncLocalDetails()
   {
     if($this->local_id) {
       // Update record
       $query = "UPDATE vtiger_users SET email1=?, first_name=?, last_name=? where id=?";
       $upd = $this->connection->pquery($query, array($this->email, $this->name, $this->surname, $this->local_id));
       return $upd;
     }

     return false;
   }
  
  /**
   * Set the Maestrano UID on a local user via id lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function setLocalUid()
  {
    if($this->local_id) {
      // Update record
      $query = "UPDATE vtiger_users SET mno_uid=? where id=?";
      $upd = $this->connection->pquery($query, array($this->uid, $this->local_id));
      return $upd;
    }
    
    return false;
  }
  
  /**
   * Return whether the user should be admin or
   * not.
   * User is considered admin if app_owner or if
   * admin of the orga owning this app
   *
   * @return boolean true if admin, false otherwise
   */
  protected function isLocalUserAdmin() {
    $ret_value = false;
    
    if ($this->app_owner) {
      $ret_value = true; // Admin
    } else {
      foreach ($this->organizations as $organization) {
        if ($organization['role'] == 'Admin' || $organization['role'] == 'Super Admin') {
          $ret_value = true;
        } else {
          $ret_value = false;
        }
      }
    }
    
    return $ret_value;
  }
  
  /**
   * Build a vtiger user for creation
   *
   * @return Users the user object
   */
   protected function buildLocalUser()
   {
     $fields = &$this->_user->column_fields;
     $fields["user_name"] = $this->email;
     $fields["email1"] = $this->email;
     $fields["is_admin"] = $this->isLocalUserAdmin() ? 'on' : 'off';
     $fields["user_password"] = $this->generatePassword();
     $fields["confirm_password"] = $fields["user_password"];
     $fields["first_name"] = $this->name;
     $fields["last_name"] = $this->surname;
     $fields["roleid"] = "H2"; # H2 role cannot be deleted
     $fields["status"] = "Active";
     $fields["activity_view"] = "Today";
     $fields["lead_view"] = "Today";
     $fields["hour_format"] = "";
     $fields["end_hour"] = "";
     $fields["start_hour"] = "";
     $fields["title"] = "";
     $fields["phone_work"] = "";
     $fields["department"] = "";
     $fields["phone_mobile"] = "";
     $fields["reports_to_id"] = "";
     $fields["phone_other"] = "";
     $fields["email2"] = "";
     $fields["phone_fax"] = "";
     $fields["secondaryemail"] = "";
     $fields["phone_home"] = "";
     $fields["date_format"] = "dd-mm-yyyy";
     $fields["signature"] = "";
     $fields["description"] = "";
     $fields["address_street"] = "";
     $fields["address_city"] = "";
     $fields["address_state"] = "";
     $fields["address_postalcode"] = "";
     $fields["address_country"] = "";
     $fields["accesskey"] = "";
     $fields["time_zone"] = "UTC";
     $fields["currency_id"] = "1";
     $fields["currency_grouping_pattern"] = "123,456,789";
     $fields["currency_decimal_separator"] = ".";
     $fields["currency_grouping_separator"] = ",";
     $fields["currency_symbol_placement"] = "$1.0";
     $fields["imagename"] = "";
     $fields["internal_mailer"] = "on";
     $fields["theme"] = "softed";
     $fields["language"] = "en_us";
     $fields["reminder_interval"] = "None";
     $fields["asterisk_extension"] = "";
     $fields["use_asterisk"] = "on";
     $fields["ccurrency_name"] = "";
     $fields["currency_code"] = "";
     $fields["currency_symbol"] = "";
     $fields["conv_rate"] = "";
     
     return $this->_user;
   }
}