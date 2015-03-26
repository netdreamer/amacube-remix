<?php
/**
* This file is part of the Amacube-Remix_WBList Roundcube plugin
* Copyright (C) 2015, Tony VanGerpen <Tony.VanGerpen@hotmail.com>
* 
* A Roundcube plugin to let users manage whitelist/blacklist (which must be stored in a database)
* Based heavily on the amacube plugin by Alexander Köb (https://github.com/akoeb/amacube)
* 
* Licensed under the GNU General Public License version 3. 
* See the COPYING file in parent directory for a full license statement.
*/

class amacube_remix_wblist extends rcube_plugin
{
  private $rcmail;
  private $wblist;
  
  public $task = 'settings';
  public $actions = array( 'plugin.amacube_remix_wblist' => 'action_wblist',
                           'plugin.amacube_remix_wblist-save' => 'action_wblist_save',
                           'plugin.request_ajax' => 'request_handler' );
  
  function init() {
    # Fetch Instance (Once)
    $this->rcmail = rcmail::get_instance();
    
    # Register Policy Langauge Files
    $this->add_texts( 'localization/', true );
    
    # Add "Amavis WBlist" Icon to the Menu
    $this->add_hook( 'settings_actions', array( $this, 'settings_actions' ) );
    
    # Only Register Actions if we have chosen "Settings" from the Taskbar
    if( $this->rcmail->task == 'settings' ) {
      foreach( $this->actions AS $key => $value)
        $this->register_action( $key, array( $this, $value ) );
      
      # Only Startup if we have chosen "Amavis WBlist" from the Settings Menu
      if( array_key_exists( $this->rcmail->action, $this->actions ) )
        $this->add_hook( 'startup', array( $this, 'startup' ) );
    }
  }
  
  function settings_actions( $args ) {
    # Load Icon Stylesheet
    $this->include_stylesheet( 'styles/amacube_remix_wblist.icon.css' );
    
    # Add Amavis WBlist Icon to the Settings Menu
    $args['actions'][] = array( 'action' => 'plugin.amacube_remix_wblist',
                                'class' => 'amacube_remix_wblist',
                                'label' => 'menu_button',
                                'domain' => 'amacube_remix_wblist'
                              );
    return $args;
  }
  
  function startup() {
    $this->load_config();
    
    # UI Includes
    $this->include_script( 'scripts/amacube_remix_wblist.js' );
    $this->include_stylesheet( 'styles/amacube_remix_wblist.css' );
  }
  
  // Ajax Request Director
  function request_handler() {
    switch( $_POST['action'] ) {
      case 'show_wblist':
        $this->rcmail->output->command( 'plugin.response_wblist', $this->_build_wblist_list() );
        break;
      case 'add_entry':
        $this->add_entry();
        $this->rcmail->output->command( 'plugin.response_wblist', $this->_build_wblist_list() );
        break;
      case 'delete_entry':
        $this->delete_entry();
        $this->rcmail->output->command( 'plugin.response_wblist', $this->_build_wblist_list() );
        break;
    }
    
    return false;
  }
  
  /*
   * Actions
   */
  function action_wblist() {
    $this->register_handler( 'plugin.body', array( $this, '_build_boxtitle' ) );
    //$this->register_handler( 'plugin.body.form', array( $this, '_build_wblist_form' ) );
    //$this->register_handler( 'plugin.body.list', array( $this, '_build_wblist_list' ) );
    $this->rcmail->output->set_pagetitle( $this->gettext( 'page_title' ) );
    $this->rcmail->output->send( 'amacube_remix_wblist.wblist' ); // Template to Send
  }
  
  function add_entry() {
    # Declare Variables
    $data = array();
    
    # Get POST Vars
    $data['address'] = ( isset( $_POST['address'] ) ) ? $_POST['address'] : '';
    $data['policy'] = ( isset( $_POST['policy'] ) ) ? $_POST['policy'] : '';
    
    # Validate Address
    if( empty( $data['address'] ) ) {
      $this->rcmail->output->command( 'display_message', $this->gettext( 'error_wblist_add_address' ), 'error' );
      return false;
    }
    
    # Validate Policy
    switch( $data['policy'] ) {
      case 'B':
      case 'W':
        // Do Nothing, is a valid value
        break;
      default:
        $this->rcmail->output->command( 'display_message', $this->gettext( 'error_wblist_add_policy' ), 'error' );
        return false;
        break;
    }
    
    # Initialize WBlist Object
    include_once('AmavisWBlist.php');
    $this->wblist = new AmavisWBlist( $this->rcmail->config->get( 'amacube_remix_db_dsn' ), 
                                      $this->rcmail->config->get( 'amacube_remix_amavis_host' ), 
                                      $this->rcmail->config->get( 'amacube_remix_amavis_port' ) );
    
    # Add WBlist Entry
    $errors = $this->wblist->add( $data['address'], $data['policy'] );
    
    # Display/Log Add Errors
    if( is_array( $errors ) ) {
      foreach( $errors AS $error )
        $this->rcmail->output->command( 'display_message', $error, 'error' );
        
      return false;
    }
        
    # Return
    return true;
  }
  
  function delete_entry() {
    # Declare Variables
    $data = array();
    
    # Get POST Vars
    $data['sender_id'] = ( isset( $_POST['sender_id'] ) ) ? $_POST['sender_id'] : '0';
    
    # Validate Post Vars
    if( $data['sender_id'] == '0' ) {
      $this->rcmail->output->command( 'display_message', $this->gettext( 'error_wblist_delete' ), 'error' );
      return false;
    }
    
    # Initialize WBlist Object
    include_once('AmavisWBlist.php');
    $this->wblist = new AmavisWBlist( $this->rcmail->config->get( 'amacube_remix_db_dsn' ), 
                                      $this->rcmail->config->get( 'amacube_remix_amavis_host' ), 
                                      $this->rcmail->config->get( 'amacube_remix_amavis_port' ) );
    
    # Delete WBlist Entry
    $errors = $this->wblist->delete( $data['sender_id'] );
    
    # Display/Log Delete Errors
    if( is_array( $errors ) )
      foreach( $errors AS $error )
        $this->rcmail->output->command( 'display_message', $error, 'error' );
    
    # Return
    return true;
  }
  
  /*
   * Helpers
   */
  function _build_boxtitle() {
    $out = html::div( array(), html::tag( 'h2', array( 'class' => 'boxtitle' ), $this->gettext( 'box_title' ) ) );
    
    return $out;
  }
  
  function _build_wblist_list() {
    # Define Variables
    $criteria = array();
    $output = array( 'raw' => '', 'errors' => '' );
    
    # Fetch POST Vars
    $criteria['sort_by'] = ( isset( $_POST['settings']['sort_by'] ) ) ? $_POST['settings']['sort_by'] : '';
    $criteria['sort_order'] = ( isset( $_POST['settings']['sort_order'] ) && $_POST['settings']['sort_order'] == 'DESC' ) ? 'DESC' : 'ASC';
    
    # Validate POST Vars
    switch( $criteria['sort_by'] ) {
      case 'policy':
      case 'sender':
        // Do Nothing, Column name is valid
        break;
      default:
        $criteria['sort_by'] = '';
        break;
    }
    
    # Initialize WBlist Object
    include_once('AmavisWBlist.php');
    $this->wblist = new AmavisWBlist( $this->rcmail->config->get( 'amacube_remix_db_dsn' ), 
                                      $this->rcmail->config->get( 'amacube_remix_amavis_host' ), 
                                      $this->rcmail->config->get( 'amacube_remix_amavis_port' ) );
    
    #Fetch List from WBlist
    $results = $this->wblist->fetch_list( $criteria );
    
    if( count( $results['errors'] ) == 0 ) {
      $output['count'] = count( $results['items'] );
      
      if( $output['count'] > 0 ) {
        foreach( $results['items'] AS $item ) {
          # Build Results Tbody
          $output['raw'] .= html::tag( 'tr', null,
                                       html::tag( 'td', array( 'class' => 'ac_sender' ), $item['email'] ) .
                                       html::tag( 'td', array( 'class' => 'ac_policy' ), ( $item['wb'] == 'W' ) ? 'Whitelist' : 'Blacklist'  ) .
                                       html::tag( 'td', array( 'class' => 'ac_action' ),
                                                  html::tag( 'div', array( 'id' => $item['sid'] ),
                                                             html::tag( 'a', array( 'href' => '#', 'onclick' => 'rcmail.command(\'plugin.request_delete_entry\', this)' ),
                                                                        html::tag( 'img', array( 'alt' => 'Delete', 'src' => 'plugins/amacube_remix_wblist/media/waste.png', 'title' => 'Delete' ) )
                                                                      )
                                                           )
                                                )
                                     );
          
        }
      } else {
        $output['raw'] .= html::tag( 'tr', null,
                                     html::tag( 'td', array( 'class' => 'ac_sender', 'colspan' => '4', 'style' => 'text-align: center' ), $this->gettext( 'no_entries' ) )
                                   );
      }
    } else {
      foreach( $results['errors'] AS $error )
        $this->rcmail->output->command( 'display_message', $error, 'error' );
    }
    
    return $output;
  }
}
?>
