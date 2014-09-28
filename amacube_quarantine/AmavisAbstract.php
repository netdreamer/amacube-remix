<?php
/**
* AmavisAbstract - super class for AmavisSettings and AmavisQuarantine
*/

/*
This file is part of the amacube Roundcube plugin
Copyright (C) 2013, Alexander Köb <nerdkram@koeb.me>

Licensed under the GNU General Public License version 3. 
See the COPYING file for a full license statement.          

*/
class AmavisAbstract
{
  private   $db_config;
  
  protected $db_conn;
  protected $user_email = '';
  
  function __construct( $db_config ) {
    $this->db_config = $db_config;
    $rcmail = rcmail::get_instance();
    $this->user_email = $rcmail->user->data['username'];
  }

  function init_db() {
    # Initialize Database Factory
    if ( !$this->db_conn ) {
      if ( !class_exists( 'rcube_db' ) ) // pre 0.9
        $this->db_conn = new rcube_mdb2( $this->db_config, '', TRUE );
      else // ver 0.9+
        $this->db_conn = rcube_db::factory( $this->db_config, '', TRUE );
    }
    
    # Connect to the Database
    $this->db_conn->db_connect('w');

    # Check DB connections and exit on failure
    if ( $err_str = $this->db_conn->is_error() ) {
      raise_error( array(
        'code' => 603,
        'type' => 'db',
        'message' => $err_str ), true, true );
    }
  }

  function db_error() {
    # Return the last database error message
    if( $this->db_conn && $this->db_conn->is_error() )
      return $this->db_conn->is_error();
      
    return false;
  }
}
?>