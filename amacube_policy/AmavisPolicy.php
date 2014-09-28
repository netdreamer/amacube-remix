<?php
/**
* AmavisPolicy - class to load and store Amavis settings in DB
*/

/*
This file is part of the amacube Roundcube plugin
Copyright (C) 2013, Alexander Köb <nerdkram@koeb.me>

Licensed under the GNU General Public License version 3. 
See the COPYING file for a full license statement.          

*/
include_once('AmavisAbstract.php');
class AmavisPolicy extends AmavisAbstract
{
  // USER SETTINGS
  private $priority = 7; // we do not change the amavis default for that
  public $user_pk; // primary key for the user record
  public $fullname; // Full Name of the user, for reference, Amavis does not use that  

  // POLICY SETTINGS
  public $policy_pk; // primary key of the policy record
  public $policy_name; // Name of the policy, for reference, Amavis does not use that
  public $policy_setting = array(
    'virus_lover' => false,         // bool
    'spam_lover' => false,          // bool
    'banned_files_lover' => false,  // bool
    'bad_header_lover' => false,    // bool
    'bypass_virus_checks' => false, // bool
    'bypass_spam_checks' => false,  // bool
    'bypass_banned_checks' => false,// bool
    'bypass_header_checks' => false,// bool
    'spam_tag_level' => -999,       // float
    'spam_tag2_level' => 2.3,         // float
    'spam_tag3_level' => 6,        // float
    'spam_kill_level' => 6,        // float
    'spam_dsn_cutoff_level' => 20,  // float
    'spam_quarantine_cutoff_level' => 20, // float
    
    'banned_quarantine_to' => true,    // string 'sql:', but treated as boolean
    'bad_header_quarantine_to' => true,    // string 'sql:', but treated as boolean
    'virus_quarantine_to' => true,      // string 'sql:', but treated as boolean
    'spam_quarantine_to' => true,      // string 'sql:', but treated as boolean
    
    'unchecked_quarantine_to' => '',    // unused
    'clean_quarantine_to' => '',        // unused
    'archive_quarantine_to' => '',      // unused
  );
    
    /*
    'user_id' => 3
    'priority' => 7
    'email' => tmctesterson@testlab.w-link.net
    'fullname' => 
    'id' => 5
    'policy_name' => Normal
    'virus_lover' => N
    'spam_lover' => N
    'unchecked_lover' => 
    'banned_files_lover' => N
    'bad_header_lover' => N
    'bypass_virus_checks' => N
    'bypass_spam_checks' => N
    'bypass_banned_checks' => N
    'bypass_header_checks' => N
    'virus_quarantine_to' => 
    'spam_quarantine_to' => 
    'banned_quarantine_to' => 
    'unchecked_quarantine_to' => 
    'bad_header_quarantine_to' => 
    'clean_quarantine_to' => 
    'archive_quarantine_to' => 
    'spam_tag_level' => 3
    'spam_tag2_level' => 6.9
    'spam_tag3_level' => 
    'spam_kill_level' => 6.9
    'spam_dsn_cutoff_level' => 
    'spam_quarantine_cutoff_level' => 
    'addr_extension_virus' => 
    'addr_extension_spam' => 
    'addr_extension_banned' => 
    'addr_extension_bad_header' => 
    'warnvirusrecip' => 
    'warnbannedrecip' => 
    'warnbadhrecip' => 
    'newvirus_admin' => 
    'virus_admin' => 
    'banned_admin' => 
    'bad_header_admin' => 
    'spam_admin' => 
    'spam_subject_tag' => 
    'spam_subject_tag2' => 
    'spam_subject_tag3' => 
    'message_size_limit' => 
    'banned_rulenames' => 
    'disclaimer_options' => 
    'forward_method' => 
    'sa_userconf' => 
    'sa_username' =>
    */
    
    
    
    

    // class variables(static), the same in all instances:
    private static $boolean_settings = array(
            'virus_lover',
            'spam_lover',
            'unchecked_lover',
            'banned_files_lover',
            'bad_header_lover',
            'bypass_virus_checks',
            'bypass_spam_checks',
            'bypass_banned_checks',
            'bypass_header_checks',
            );

    /* 
    The following settings are unused, I added the lines for later implementation if needed

    addr_extension_virus; // unused
    addr_extension_spam; // unused
    addr_extension_banned; // unused
    addr_extension_bad_header; // unused
    warnvirusrecip; // unused
    warnbannedrecip; // unused
    warnbadhrecip; // unused
    newvirus_admin; // unused
    virus_admin; // unused
    banned_admin; // unused
    bad_header_admin; // unused
    spam_admin; // unused
    spam_subject_tag; // unused
    spam_subject_tag2; // unused
    spam_subject_tag3; // unused
    message_size_limit; // unused
    banned_rulenames; // unused
    disclaimer_options; // unused
    forward_method; // unused
    sa_userconf; // unused
    sa_username; // unused
    */




  function __construct( $db_config ) {
    parent::__construct( $db_config );
    
    # Read User's Policy from db if it exists
    $this->read_from_db();
    
    # Verify User's Policy
    $verify = $this->verify_policy_array();
				
    if( isset( $verify ) && is_array( $verify ) ) {
        // TODO: something is dead wrong, database settngs do not verify
        // FiXME: throw error
        error_log("AMACUBE: verification of database settings failed...".implode(',',$verify));
    }
  }
    
  // method to verify the policy settings are correct
  function verify_policy_array( $array = null ) {
    $errors = array();
    
    # Load Default policty if array argument is empty
    if( !is_array( $array ) || count( $array ) == 0 )
      $array = $this->policy_setting;
      
    # Validate Booleans
    if( !is_bool( $array['virus_lover'] ) )								array_push( $errors, 'virus_lover' );
    if( !is_bool( $array['spam_lover'] ) )								array_push( $errors, 'spam_lover' );
    //if( !is_bool( $array['unchecked_lover'] ) )						array_push( $errors, 'unchecked_lover' );
    if( !is_bool( $array['banned_files_lover'] ) )				array_push( $errors, 'banned_files_lover' );
    if( !is_bool( $array['bad_header_lover'] ) )					array_push( $errors, 'bad_header_lover' );
    if( !is_bool( $array['bypass_virus_checks'] ) )				array_push( $errors, 'bypass_virus_checks' );
    if( !is_bool( $array['bypass_spam_checks'] ) )				array_push( $errors, 'bypass_spam_checks' );
    if( !is_bool( $array['bypass_banned_checks'] ) )			array_push( $errors, 'bypass_banned_checks' );
    if( !is_bool( $array['bypass_header_checks'] ) ) 			array_push( $errors, 'bypass_header_checks' );
    if( !is_bool( $array['banned_quarantine_to'] ) ) 			array_push( $errors, 'banned_quarantine_to' );
    if( !is_bool( $array['bad_header_quarantine_to'] ) )	array_push( $errors, 'bad_header_quarantine_to' );
    if( !is_bool( $array['virus_quarantine_to'] ) )				array_push( $errors, 'virus_quarantine_to' );
    if( !is_bool( $array['spam_quarantine_to'] ) )				array_push( $errors, 'spam_quarantine_to' );
    
    # Validate Floats
    if( !is_numeric( $array['spam_tag_level'] ) ) array_push( $errors, 'spam_tag_level:' . $array['spam_tag_level'] . "___" . gettype( $array['spam_tag_level'] ) );
    if( !is_numeric( $array['spam_tag2_level'] ) ) array_push( $errors, 'spam_tag2_level');
    //if( !is_numeric( $array['spam_tag3_level'] ) ) array_push( $errors, 'spam_tag3_level' );
    if( !is_numeric( $array['spam_kill_level'] ) ) array_push($errors, 'spam_kill_level');
    //if( !is_numeric( $array['spam_dsn_cutoff_level'] ) ) array_push( $errors, 'spam_dsn_cutoff_level' );
    //if( !is_numeric( $array['spam_quarantine_cutoff_level'] ) ) array_push($errors, 'spam_quarantine_cutoff_level');
    
    # Whitelist: Strip Extra Elements from Policy
    foreach( $array as $key => $value ) {
      if( !array_key_exists( $key, $this->policy_setting ) )
        array_push( $errors, 'unknown: ' . $key );
    }
		
    # Return Errors
    if( !empty( $errors ) )
      return $errors;
  }

  // read amavis settings from database
  function read_from_db() {
    # Connect to db
    if( !is_resource( $this->db_conn ) )
      $this->init_db();

    # Build Query
    $query = 'SELECT users.id as user_id, users.priority, users.email, users.fullname, policy.*
      FROM users, policy
      WHERE users.policy_id = policy.id 
      AND users.email = ? ';

    # Execute Query
    $results = $this->db_conn->query( $query, $this->user_email );
    //TODO: error check

    // write the first result line to settings array
    if( $results ) {
      if( $results_array = $this->db_conn->fetch_assoc( $results ) ) {
        
        // read all keys of policy_setting array
        foreach( $this->policy_setting as $key => $value )
          $this->policy_setting[$key] = $this->map_from_db( $key, $results_array[$key] );
          
        $this->user_pk = $results_array['user_id'];
        $this->priority = $results_array['priority'];
        $this->fullname = $results_array['fullname'];
        $this->policy_pk = $results_array['id'];
        $this->policy_name = $results_array['policy_name'];
        
        return;
      }
    }
    
    //TODO: Show Error: Unable to read policy from the database
  }
  
  // write settings back to database
  // FIXME: this method must return an error string in case something fails
  function write_to_db() {
    # Connect to db
    if( !is_resource( $this->db_conn ) )
      $this->init_db();
          
    $query_params = array();

    // TODO: Remove before production
    // Enable Database Debugging
    $this->db_conn->set_debug(TRUE);

    # if we have a primary key, the row exists already in the database
    if( !empty( $this->policy_pk ) ) {
      $query = 'UPDATE policy SET ';
      $keys = array_keys( $this->policy_setting );
      $max = sizeof( $keys );
      
      for( $i = 0; $i < $max; $i++ ) {
        $query .= $keys[$i] .' = ? ';
        array_push($query_params, $this->map_to_db($keys[$i], $this->policy_setting[$keys[$i]]));
        if($i < $max - 1 ) {
            $query .= ', ';
        }
      }
      $query .= ' WHERE id = ? ';
      array_push($query_params, $this->policy_pk);
    }
    // no PK, insert
    else {
        // we insert the policy row first, the user row comes later
        $keys = array_keys($this->policy_setting);
        array_push($keys, 'policy_name');
        $max = sizeof($keys);
        $query = 'INSERT INTO policy (';
        $query .= implode(',', $keys);
        $query .= ') VALUES (';
        for($i = 0; $i < $max; $i++) {
            $query .= '?';
            if($i < $max - 1 ) {
                $query .= ', ';
            }
        }
        $query .= ')';
        foreach($keys as $k) {
            if($k == 'policy_name') {
                array_push($query_params,'amacube_'.$this->user_email);
            }
            else {
                array_push($query_params, $this->map_to_db($k,$this->policy_setting[$k]));
            }
        }
    }
    
				
      $res = $this->db_conn->query($query, $query_params);
						
      //print_r($res);exit;
      
      // error check
      if($this->db_error()) {
          return "Error in insert/update policy: ".$this->db_error();
      }

      // in case this was an insert, read policy_pk and insert user as well if needed 
      if(empty($this->policy_pk)) {

          $this->policy_pk = $this->db_conn->insert_id();
          // error check
          if(empty($this->policy_pk)) {
              return "Could not get Primary Key for policy: ".$this->db_error();
          }

          // now that we have the policy pk, we check 
          // whether we need to insert or update the user as well
          $res = $this->db_conn->query(
              'SELECT id from users where email = ? ', 
              $this->user_email);
          
          // error check
          if($this->db_error) {
              return "Error in checking for user record: ".$this->db_error();
          }

          if ($res && ($res_array = $this->db_conn->fetch_assoc($res))) {
              // we need to update:
              $this->user_pk = $res_array['id'];
              $res2 = $this->db_conn->query(
                  'UPDATE users set policy_id = ? WHERE id = ?',
                  $this->policy_pk, $this->user_pk);
          }
          else {
              // INSERT user as well
              $res2 = $this->db_conn->query(
                  'INSERT INTO users (policy_id, email) VALUES (?,?)',
                  $this->policy_pk, $this->user_email);
          }
          //  error check
          if($this->db_error) {
              return "Error in inserting/updating user record: ".$this->db_error();
          }
      }
      // all good:
      return null;
  }


  // CONVENIENCE METHODS:
  
  // set the checkbox checked mark if user is a NOT spam or virus lover
  // (the checkbox marks ACTIVATION of the check, DEACTIVATION means user is a *_lover)
  function is_check_activated_checkbox( $type ) {
    if( $type !== 'banned' && $type !== 'header' && $type !== 'spam' && $type !== 'virus' ) {
      //FIXME throw error
      return false;
    }
    elseif( $this->policy_setting['bypass_'.$type.'_checks'] ) {
      return false;
    }
    
    return true;
  }
    
  function is_lover_activated_checkbox( $type ) {
    if( $type !== 'banned_files' && $type !== 'bad_header' && $type !== 'spam' && $type !== 'virus' ) {
        //FIXME throw error
        return false;
    }
    elseif ( $this->policy_setting[$type.'_lover'] ) {
      return false;
    }
    
    return true;
  }
    
  // set the checkbox checked mark if user has quarantine activated
  function is_quarantine_activated_checkbox( $type ) {
    if( $type !== 'banned' && $type !== 'bad_header' && $type !== 'spam' && $type !== 'virus' ) {
      //FIXME throw error
      return false;
    }
    elseif($this->policy_setting[$type.'_quarantine_to']) {
      return true;
    }
    
    return false;
  }

  // mapping function internal representation - database content
  function map_to_db( $key, $value ) {
    $retval = null;

    # Map boolean settings to Y/N as stored in the database
    if( in_array( $key, self::$boolean_settings ) ) {
      if( $value )
        $retval = 'Y';
      else
        $retval = 'N';
    }
    
    # special mapping for the quarantine settings we use:
    elseif( $key == 'banned_quarantine_to' || $key == 'bad_header_quarantine_to' || $key == 'spam_quarantine_to' || $key == 'virus_quarantine_to' ) {
      if( $value )
        $retval = 'sql:';
      else
        $retval = null;
    }
    
    # all other settings do not require mapping
    else {
      $retval = $value;
    }
    
    return $retval;
  }
  
  // mapping function database content - internal representation 
  function map_from_db( $key, $value ) {
    $retval = $value;
    
    # Map boolean settings from Y/N as stored in the database
    if( in_array( $key, self::$boolean_settings ) ) {
      if( !empty( $value ) && $value == 'Y' )
        $retval = true;
      else
        $retval = false;
    }
    
    # special mapping for the quarantine settings we use:
    elseif( $key == 'banned_quarantine_to' || $key == 'bad_header_quarantine_to' || $key == 'spam_quarantine_to' || $key == 'virus_quarantine_to' ) {
      if( !empty( $value ) && $value == 'sql:' )
        $retval = true;
      else
        $retval = false;
    }
    
    return $retval;
  }
}
?>