<?php
/**
* This file is part of the Amacube-Remix_Quarantine Roundcube plugin
* Copyright (C) 2015, Tony VanGerpen <Tony.VanGerpen@hotmail.com>
* 
* A Roundcube plugin to let users release quarantined mail (which must be stored in a database)
* Based heavily on the amacube plugin by Alexander Köb (https://github.com/akoeb/amacube)
* 
* Licensed under the GNU General Public License version 3. 
* See the COPYING file in parent directory for a full license statement.
*/

class amacube_remix_quarantine extends rcube_plugin
{
  private $rcmail;
  private $quarantine;
  
  function init() {
    # Fetch Instance (Once)
    $this->rcmail = rcmail::get_instance();
    
    # Register Task: Quarantine
    $this->register_task( 'quarantine' );
    
    # Register Quarantine Langauge Files
    $this->add_texts( 'localization/', true );
    
    # Add Quarantine Icon to the Taskbar
    $this->register_icon();
    
    # Only Register Actions if we have chosen Quarantine from the Taskbar
    if( $this->rcmail->task == 'quarantine' ) {
      $this->register_action( 'index', array( $this, 'event_init' ) );
      $this->register_action( 'refresh', array( $this, 'event_refresh' ) );
      $this->register_action( 'plugin.request_ajax', array( $this, 'request_handler' ) );
      
      $this->add_hook( 'startup', array( $this, 'startup' ) );
    }
  }
  
  function register_icon() {
    # Load Icon Stylesheet
    $this->include_stylesheet( 'styles/amacube_remix_quarantine.icon.css' );
    
    # Add Quarantine Icon to the Taskbar
    $this->add_button( array(
      'command'    => 'quarantine',
      'class'      => 'button-quarantine',
      'classsel'   => 'button-quarantine button-selected',
      'innerclass' => 'button-inner',
      'label'      => 'quarantine',
      'domain'     => 'amacube_remix_quarantine'
    ), 'taskbar' );
  }
  
  function startup() {
    $this->load_config();
    
    # UI Includes
    $this->include_script( 'scripts/amacube_remix_quarantine.js' );
    $this->include_stylesheet( 'styles/amacube_remix_quarantine.css' );
  }
  
  // Ajax Request Director
  function request_handler() {
    switch( $_POST['action'] ) {
      case 'show_quarantine':
        $this->rcmail->output->command( 'plugin.response_messagelist', $this->get_quarantine() );
        break;
      case 'quarantine_release':
        $this->quarantine_release();
        $this->rcmail->output->command( 'plugin.response_messagelist', $this->get_quarantine() );
        break;
      case 'quarantine_discard':
        if( $this->quarantine_discard() )
          $this->rcmail->output->command( 'plugin.response_messagelist', $this->get_quarantine() );
        break;
    }
    
    return false;
  }
  
  /*
   * Events
   */
  function event_init() {
    $this->rcmail->output->set_pagetitle( $this->gettext( 'quarantine_pagetitle' ) );
    $this->rcmail->output->send( 'amacube_remix_quarantine.quarantine' ); // Template to Send
  }
  
  function event_refresh() {
    $this->rcmail->output->command( 'plugin.response_refresh' );
  }
  
  /*
   * Actions
   */
  function get_quarantine() {
    # Define Variables
    $criteria = array();
    $output = array( 'raw' => '', 'errors' => '' );
    
    # Fetch POST Vars
    $criteria['current_page'] = ( isset( $_POST['settings']['current_page'] ) && is_numeric( $_POST['settings']['current_page'] ) ) ? intval( $_POST['settings']['current_page'] ) : 0;
    $criteria['items_per_page'] = ( isset( $_POST['settings']['items_per_page'] ) && is_numeric( $_POST['settings']['items_per_page'] ) ) ? intval( $_POST['settings']['items_per_page'] ) : 25;
    $criteria['search_from'] = ( isset( $_POST['settings']['search_filter_by_sender'] ) && $_POST['settings']['search_filter_by_sender'] == '1' ) ? true : false;
    $criteria['search_subject'] = ( isset( $_POST['settings']['search_filter_by_subject'] ) && $_POST['settings']['search_filter_by_subject'] == '1' ) ? true : false;
    $criteria['search_body'] = ( isset( $_POST['settings']['search_filter_by_body'] ) && $_POST['settings']['search_filter_by_body'] == '1' ) ? true : false;
    $criteria['search_term'] = ( isset( $_POST['settings']['search_term'] ) ) ? $_POST['settings']['search_term'] : '';
    $criteria['sort_by'] = ( isset( $_POST['settings']['sort_by'] ) ) ? $_POST['settings']['sort_by'] : '';
    $criteria['sort_order'] = ( isset( $_POST['settings']['sort_order'] ) && $_POST['settings']['sort_order'] == 'DESC' ) ? 'DESC' : 'ASC';
    
    # Validate POST Vars
    switch( $criteria['sort_by'] ) {
      case 'sender':
      case 'subject':
      case 'date':
      case 'type':
      case 'score':
        // Do Nothing, Column name is valid
        break;
      default:
        $criteria['sort_by'] = '';
        break;
    }
    
    # Initialize Quarantine Object
    include_once('AmavisQuarantine.php');
    $this->quarantine = new AmavisQuarantine( $this->rcmail->config->get( 'amacube_remix_db_dsn' ), 
                                              $this->rcmail->config->get( 'amacube_remix_amavis_host' ), 
                                              $this->rcmail->config->get( 'amacube_remix_amavis_port' ) );
    
    #Fetch List from Quarantine
    //$output['count'] = $this->quarantine->fetch_count();
    $results = $this->quarantine->fetch_list( $criteria );
    
    if( count( $results['errors'] ) == 0 ) {
      $output['count'] = $results['count'];
      
      foreach( $results['items'] AS $item ) {
        if( $item['content'] != 'S' )
          $item['level'] = '--';
        
        # Build Results Tbody
        $output['raw'] .= html::tag( 'tr', null,
                                     html::tag( 'td', array( 'class' => 'ac_from'), $item['sender'] ) .
                                     html::tag( 'td', array( 'class' => 'ac_subject'), $item['subject'] ) .
                                     html::tag( 'td', array( 'class' => 'ac_date'), $item['age'] = date( 'Y-m-d - h:i a', $item['age'] ) ) .
                                     html::tag( 'td', array( 'class' => 'ac_type'), $item['content'] ) .
                                     html::tag( 'td', array( 'class' => 'ac_score'), $item['level'] ) .
                                     html::tag( 'td', array( 'class' => 'ac_action'),
                                                html::tag( 'div', array( 'id' => $item['id'] ),
                                                           html::tag( 'a', array( 'href' => '#', 'onclick' => "rcmail.command('plugin.request_quarantine_release', this)"),
                                                                      html::tag( 'img', array( 'alt' => 'Release', 'src' => 'plugins/amacube_remix_quarantine/media/like.png', 'title' => 'Release' ) ) ) .
                                                           html::tag( 'a', array( 'href' => '#', 'onclick' => "rcmail.command('plugin.request_quarantine_discard', this)"),
                                                                      html::tag( 'img', array( 'alt' => 'Discard', 'src' => 'plugins/amacube_remix_quarantine/media/waste.png', 'title' => 'Discard' ) ) )
                                                         )
                                              )
                                   );
        
      }
    } else {
      foreach( $results['errors'] AS $error )
        $this->rcmail->output->command( 'display_message', $error, 'error' );
    }
    
    # Return
    return $output;
  }
  
  function quarantine_release() {
    # Declare Variables
    $data = array();
    
    # Get POST Vars
    $data['mail_id'] = ( isset( $_POST['mail_id'] ) ) ? $_POST['mail_id'] : '0';
    
    # Validate Post Vars
    if( $data['mail_id'] == '0' ) {
      // Todo: Update this error message
      $this->rcmail->output->command( 'display_message', $this->gettext( 'intersection_error' ), 'error' );
      return false;
    }
    
    # Initialize Quarantine Object
    include_once('AmavisQuarantine.php');
    $this->quarantine = new AmavisQuarantine( $this->rcmail->config->get( 'amacube_remix_db_dsn' ), 
                                              $this->rcmail->config->get( 'amacube_remix_amavis_host' ), 
                                              $this->rcmail->config->get( 'amacube_remix_amavis_port' ) );
    
    # Release Message
    $errors = $this->quarantine->release( $data['mail_id'] );
    
    # Display/Log Release Errors
    if( is_array( $errors ) )
      foreach( $errors AS $error )
        $this->rcmail->output->command( 'display_message', $error, 'error' );
    
    # Return
    return true;
  }
  
  function quarantine_discard() {
    # Declare Variables
    $data = array();
    
    # Get POST Vars
    $data['mail_id'] = ( isset( $_POST['mail_id'] ) ) ? $_POST['mail_id'] : '0';
    
    # Validate Post Vars
    if( $data['mail_id'] == '0' ) {
      $this->rcmail->output->command( 'display_message', $this->gettext( 'error_quarantine_discard' ), 'error' );
      return false;
    }
    
    # Initialize Quarantine Object
    include_once('AmavisQuarantine.php');
    $this->quarantine = new AmavisQuarantine( $this->rcmail->config->get( 'amacube_remix_db_dsn' ), 
                                              $this->rcmail->config->get( 'amacube_remix_amavis_host' ), 
                                              $this->rcmail->config->get( 'amacube_remix_amavis_port' ) );
    
    # Discard Message
    $errors = $this->quarantine->delete( $data['mail_id'] );
    
    # Display/Log Discard Errors
    if( is_array( $errors ) )
      foreach( $errors AS $error )
        $this->rcmail->output->command( 'display_message', $error, 'error' );
    
    # Return
    return true;
  }
}
?>
