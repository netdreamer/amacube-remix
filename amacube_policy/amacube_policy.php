<?php
/**
*  Amacube_Remix
* 
* A Roundcube plugin to let users change their amavis settings (which must be stored
* in a database) based on the amacube plugin by Alexander Köb (https://github.com/akoeb/amacube)
* 
* @version 0.1
* @author Tony VanGerpen
* @url https://github.com/akoeb/amacube
*
*/

/*
This file is part of the amacube_remix Roundcube plugin
Copyright (C) 2014, Tony VanGerpen

Licensed under the GNU General Public License version 3. 
See the COPYING file for a full license statement.          

*/
/*
// DEBUG
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
ini_set("log_errors", 1);
ini_set("error_log", "/var/www/roundcube/plugins/amacube/roundcube-error.log");
*/

class amacube_policy extends rcube_plugin
{
  private $rcmail;
  private $spam_score_max = 20;
  private $spam_score_min = 0;
  
  public $task = 'settings';
  public $actions = array( 'plugin.amacube_policy' => 'action_policy',
                           'plugin.amacube_policy-save' => 'action_policy_save' );
  
  function init() {
    # Fetch Instance (Once)
    $this->rcmail = rcmail::get_instance();
    
    # Register Langauge Files
    $this->add_texts( 'localization/', true );
    
    # Add "Amavis Policy" Icon to the Settings Menu
    $this->add_hook( 'settings_actions', array( $this, 'settings_actions' ) );
    
    
    # Only Register Actions if we have chosen "Settings" from the Taskbar
    if( $this->rcmail->task == 'settings' ) {
      foreach( $this->actions AS $key => $value)
        $this->register_action( $key, array( $this, $value ) );
      
      # Only Startup if we have chosen "Amavis Policy" from the Settings Menu
      if( array_key_exists( $this->rcmail->action, $this->actions ) )
        $this->add_hook( 'startup', array( $this, 'startup' ) );
    }
  }
  
  function settings_actions( $args ) {
    # Load Icon Stylesheet
    $this->include_stylesheet( 'amacube_policy_icon.css' );
    
    # Add Amavis Policy Icon to the Settings Menu
    $args['actions'][] = array( 'action' => 'plugin.amacube_policy',
                                'class' => 'amacube_policy',
                                'label' => 'menu_button',
                                'domain' => 'amacube_policy'
                              );
    return $args;
  }
  
  function startup() {
    $this->load_config();
    
    # UI Includes
    $this->include_script( 'amacube_policy.js' );
    $this->include_stylesheet( 'amacube_policy.css' );
  }
  
  /*
   * Actions
   */
  function action_policy() {
    $this->register_handler( 'plugin.body', array( $this, '_build_policy_form' ) );
    $this->rcmail->output->set_pagetitle( $this->gettext( 'page_title' ) );
    $this->rcmail->output->send( 'plugin' );
  }
  
  function action_policy_save() {
    $this->register_handler( 'plugin.body', array( $this, '_build_policy_form' ) );
    $this->rcmail->output->set_pagetitle( $this->gettext( 'page_title' ) );
    
    # Fetch post data
    $data = array();
    $data['spam_check'] = get_input_value( '_spam_check_toggle', RCUBE_INPUT_POST, false );
    $data['spam_junk_score'] = get_input_value( '_spam_settings_junk_score', RCUBE_INPUT_POST, false );
    $data['spam_quarantine_score'] = get_input_value( '_spam_settings_quarantine_score', RCUBE_INPUT_POST, false );
    $data['spam_check_destination'] = get_input_value( '_spam_check_destination', RCUBE_INPUT_POST, false );
    $data['virus_check'] = get_input_value( '_virus_check_toggle', RCUBE_INPUT_POST, false );
    $data['virus_check_destination'] = get_input_value( '_virus_check_destination', RCUBE_INPUT_POST, false );
    $data['banned_check'] = get_input_value( '_banned_check_toggle', RCUBE_INPUT_POST, false );
    $data['banned_check_destination'] = get_input_value( '_banned_check_destination', RCUBE_INPUT_POST, false );
    $data['header_check'] = get_input_value( '_header_check_toggle', RCUBE_INPUT_POST, false );
    $data['header_check_destination'] = get_input_value( '_header_check_destination', RCUBE_INPUT_POST, false );
    
    # Validate Spam Levels
    $error = false;
    
    if( !is_numeric( $data['spam_junk_score'] )
        || $data['spam_junk_score'] > $this->spam_score_max
        || $data['spam_junk_score'] < $this->spam_score_min
        || $data['spam_junk_score'] > $data['spam_quarantine_score']
      ) {
      $this->rcmail->output->command( 'display_message', $this->gettext( 'spam_junk_score_error' ), 'error' );
      $error = true;
    }
    
    if( !is_numeric( $data['spam_quarantine_score'] )
        || $data['spam_quarantine_score'] > $this->spam_score_max
        || $data['spam_quarantine_score'] < $this->spam_score_min
        || $data['spam_quarantine_score'] < $data['spam_junk_score']
      ) {
      $this->rcmail->output->command( 'display_message', $this->gettext( 'spam_quarantine_score_error' ), 'error' );
      $error = true;
    }
    
    # Write the policy to the database
    if( !$error ) {
      $write_error = '';
      
      include_once( 'AmavisPolicy.php' );
      
      # Load the default policy
      $this->storage = new AmavisPolicy( $this->rcmail->config->get( 'amacube_db_dsn' ) );
      
      # Override default policy with User's policy
      $this->storage->policy_setting['bypass_banned_checks'] = empty( $data['banned_check'] );
      $this->storage->policy_setting['bypass_header_checks'] = empty( $data['header_check'] );
      $this->storage->policy_setting['bypass_spam_checks'] = empty( $data['spam_check'] );
      $this->storage->policy_setting['bypass_virus_checks'] = empty( $data['virus_check'] );
      
      $this->storage->policy_setting['banned_files_lover'] = empty( $data['banned_check'] );
      $this->storage->policy_setting['bad_header_lover'] = empty( $data['header_check'] );
      $this->storage->policy_setting['spam_lover'] = empty( $data['spam_check'] );
      $this->storage->policy_setting['virus_lover'] = empty( $data['virus_check'] );
      
      $this->storage->policy_setting['banned_quarantine_to'] = ( $data['banned_check_destination'] == 'quarantine' ) ? true : false;
      $this->storage->policy_setting['bad_header_quarantine_to'] = ( $data['header_check_destination'] == 'quarantine' ) ? true : false;
      $this->storage->policy_setting['spam_quarantine_to'] = ( $data['spam_check_destination'] == 'quarantine' ) ? true : false;
      $this->storage->policy_setting['virus_quarantine_to'] = ( $data['virus_check_destination'] == 'quarantine' ) ? true : false;
      
      $this->storage->policy_setting['spam_tag2_level'] = $data['spam_junk_score'];
      $this->storage->policy_setting['spam_kill_level'] = $data['spam_quarantine_score'];
        
      # Validate User's Policy
      $verify = $this->storage->verify_policy_array();
      
      # Write User's Policy or Display Errors
      if( isset( $verify ) && is_array( $verify ) )
        $this->rcmail->output->command( 'display_message', $this->gettext( 'verification_error' ), 'error' );
      else
        $write_error = $this->storage->write_to_db();
        
      # Display Results of Writing User's Policy
      if( $write_error ) {
        $this->rcmail->output->command( 'display_message', $this->gettext( 'write_error' ), 'error' );
        $this->rcmail->output->command( 'display_message', $write_error, 'error' );
      } else {
        $this->rcmail->output->command( 'display_message', $this->gettext( 'successfully saved' ), 'confirmation') ;
      }
    }
    
    $this->rcmail->overwrite_action( 'plugin.amacube_policy' );
    $this->rcmail->output->send( 'plugin' );
  }
  
  /*
   * Helpers
   */
  function _build_policy_form() {
    include_once( 'AmavisPolicy.php' );
    
    $this->storage = new AmavisPolicy( $this->rcmail->config->get( 'amacube_db_dsn' ) );
    
    // Display Message: No User Policy Exists, Creating from Default Values
    if( !$this->storage->policy_pk )
      $this->rcmail->output->command( 'display_message', $this->gettext( 'policy_default_message' ), 'warning' );
    
    /*
     * Spam Table
     */
    $tr = array();
    
    # Spam Check Toggle
    $tr[] = html::tag( 'tr', null,
                      html::tag( 'td', array( 'class' => 'title' ), html::label( 'spam_check_toggle', Q( $this->gettext( 'section_spam_check' ) ) ) ) .
                      html::tag( 'td', null, $this->_show_checkbox( 'spam_check_toggle', $this->storage->is_check_activated_checkbox( 'spam' ) ) )
                      );
    
    # Spam Check Score Settings
    $tr[] = html::tag( 'tr', array( 'id' => 'spam_check_score_settings', 'style' => ( $this->storage->is_check_activated_checkbox( 'spam' ) ) ? 'display: table-row' : 'display: none' ),
                      html::tag( 'td', array( 'class' => 'title', 'colspan' => '2' ),
                                html::tag( 'p', null,
                                          html::tag( 'label', array( 'for' => 'spam_settings_junk_score' ), Q( $this->gettext( 'section_spam_junk_score' ) ) ) .
                                          html::tag( 'input', array( 'type' => 'hidden', 'id' => 'spam_settings_junk_score', 'name' => '_spam_settings_junk_score', 'value' => $this->storage->policy_setting['spam_tag2_level'] ) ) .
                                          html::tag( 'span', array( 'class' => 'spam_score spam_score_junk', 'id' => 'spam_settings_junk_score_display' ) )
                                          ) .
                                html::tag( 'p', null,
                                          html::tag( 'label', array( 'for' => 'spam_settings_quarantine_score' ), Q( $this->gettext( 'section_spam_quarantine_score' ) ) ) .
                                          html::tag( 'input', array( 'type' => 'hidden', 'id' => 'spam_settings_quarantine_score', 'name' => '_spam_settings_quarantine_score', 'value' => $this->storage->policy_setting['spam_kill_level'] ) ) .
                                          html::tag( 'span', array( 'class' => 'spam_score spam_score_quarantine', 'id' => 'spam_settings_quarantine_score_display' ) )
                                          ) .
                                html::div( array( 'id' => 'spam_slider' ) )
                                )
                      );
    
    # Spam Check Quarantine Settings
    $tr[] = html::tag( 'tr', array( 'id' => 'spam_check_quarantine_settings', 'style' => ( $this->storage->is_check_activated_checkbox( 'spam' ) ) ? 'display: table-row' : 'display: none' ),
                      html::tag( 'td', array( 'class' => 'title' ), html::label( 'spam_check_destination', Q( $this->gettext( 'section_spam_settings' ) ) ) ) .
                      html::tag( 'td', null,
                                html::tag( 'select', array( 'name' => '_spam_check_destination' ),
                                         html::tag( 'option', array( 'value' => 'quarantine', 'selected' => ( $this->storage->policy_setting['spam_quarantine_to'] == 1 ) ? 'selected' : '' ), 'Quarantine' ) .
                                         html::tag( 'option', array( 'value' => 'discard', 'selected' => ( $this->storage->policy_setting['spam_quarantine_to'] == 0 ) ? 'selected' : '' ), 'Discard' )
                                         )
                                )
                      );
    
    $table = '';
    foreach( $tr AS $row )
      $table .= $row;
    
    $table = html::tag( 'table', array( 'class' => 'propform', 'cols' => '2' ), $table );
    $spam = html::tag('fieldset', null, html::tag('legend', null, Q( $this->gettext( 'section_spam' ) ) ) . $table );
    
    
    /*
     * Virus Table
     */
    $tr = array();
    
    # Virus Check Toggle
    $tr[] = html::tag( 'tr', null,
                      html::tag( 'td', array( 'class' => 'title' ), html::label( 'virus_check_toggle', Q( $this->gettext( 'section_virus_check' ) ) ) ) .
                      html::tag( 'td', null, $this->_show_checkbox( 'virus_check_toggle', $this->storage->is_check_activated_checkbox( 'virus' ) ) )
                      );
    
    # Virus Check Quarantine Settings
    $tr[] = html::tag( 'tr', array( 'id' => 'virus_check_quarantine_settings', 'style' => ( $this->storage->is_check_activated_checkbox( 'virus' ) ) ? 'display: table-row' : 'display: none' ),
                      html::tag( 'td', array( 'class' => 'title' ), html::label( 'virus_check_destination', Q( $this->gettext( 'section_virus_settings' ) ) ) ) .
                      html::tag( 'td', null,
                                html::tag( 'select', array( 'name' => '_virus_check_destination' ),
                                         html::tag( 'option', array( 'value' => 'quarantine', 'selected' => ( $this->storage->policy_setting['virus_quarantine_to'] == 1 ) ? 'selected' : '' ), 'Quarantine' ) .
                                         html::tag( 'option', array( 'value' => 'discard', 'selected' => ( $this->storage->policy_setting['virus_quarantine_to'] == 0 ) ? 'selected' : '' ), 'Discard' )
                                         )
                                )
                      );
    
    $table = '';
    foreach( $tr AS $row )
      $table .= $row;
    
    $table = html::tag( 'table', array( 'class' => 'propform', 'cols' => '2' ), $table );
    $virus = html::tag('fieldset', null, html::tag('legend', null, Q( $this->gettext( 'section_virus' ) ) ) . $table );
    
    
    /*
     * Banned Files Table
     */
    $tr = array();
    
    # Banned Files Check Toggle
    $tr[] = html::tag( 'tr', null,
                      html::tag( 'td', array( 'class' => 'title' ), html::label( 'banned_check_toggle', Q( $this->gettext( 'section_banned_check' ) ) ) ) .
                      html::tag( 'td', null, $this->_show_checkbox( 'banned_check_toggle', $this->storage->is_check_activated_checkbox( 'banned' ) ) )
                      );
    
    # Banned Files Check Quarantine Settings
    $tr[] = html::tag( 'tr', array( 'id' => 'banned_check_quarantine_settings', 'style' => ( $this->storage->is_check_activated_checkbox( 'banned' ) ) ? 'display: table-row' : 'display: none' ),
                      html::tag( 'td', array( 'class' => 'title' ), html::label( 'banned_check_destination', Q( $this->gettext( 'section_banned_settings' ) ) ) ) .
                      html::tag( 'td', null,
                                html::tag( 'select', array( 'name' => '_banned_check_destination' ),
                                         html::tag( 'option', array( 'value' => 'quarantine', 'selected' => ( $this->storage->policy_setting['banned_quarantine_to'] == 1 ) ? 'selected' : '' ), 'Quarantine' ) .
                                         html::tag( 'option', array( 'value' => 'discard', 'selected' => ( $this->storage->policy_setting['banned_quarantine_to'] == 0 ) ? 'selected' : '' ), 'Discard' )
                                         )
                                )
                      );
    
    $table = '';
    foreach( $tr AS $row )
      $table .= $row;
    
    $table = html::tag( 'table', array( 'class' => 'propform', 'cols' => '2' ), $table );
    $banned = html::tag('fieldset', null, html::tag('legend', null, Q( $this->gettext( 'section_banned' ) ) ) . $table );
    
    /*
     * Bad Headers Table
     */
    $tr = array();
    
    # Banned Files Check Toggle
    $tr[] = html::tag( 'tr', null,
                      html::tag( 'td', array( 'class' => 'title' ), html::label( 'header_check_toggle', Q( $this->gettext( 'section_header_check' ) ) ) ) .
                      html::tag( 'td', null, $this->_show_checkbox( 'header_check_toggle', $this->storage->is_check_activated_checkbox( 'header' ) ) )
                      );
    
    # Banned Files Check Quarantine Settings
    $tr[] = html::tag( 'tr', array( 'id' => 'header_check_quarantine_settings', 'style' => ( $this->storage->is_check_activated_checkbox( 'header' ) ) ? 'display: table-row' : 'display: none' ),
                      html::tag( 'td', array( 'class' => 'title' ), html::label( 'header_check_destination', Q( $this->gettext( 'section_header_settings' ) ) ) ) .
                      html::tag( 'td', null,
                                html::tag( 'select', array( 'name' => '_header_check_destination' ),
                                         html::tag( 'option', array( 'value' => 'quarantine', 'selected' => ( $this->storage->policy_setting['bad_header_quarantine_to'] == 1 ) ? 'selected' : '' ), 'Quarantine' ) .
                                         html::tag( 'option', array( 'value' => 'discard', 'selected' => ( $this->storage->policy_setting['bad_header_quarantine_to'] == 0 ) ? 'selected' : '' ), 'Discard' )
                                         )
                                )
                      );
    
    $table = '';
    foreach( $tr AS $row )
      $table .= $row;
    
    $table = html::tag( 'table', array( 'class' => 'propform', 'cols' => '2' ), $table );
    $header = html::tag( 'fieldset', null, html::tag( 'legend', null, Q( $this->gettext( 'section_header' ) ) ) . $table );
    
    # Assemble Form
    $form = $this->rcmail->output->form_tag( array(
                'class' => 'propform boxcontent',
                'id' => 'amacube_policy_form',
                'name' => 'amacube_policy_form',
                'method' => 'post',
                'action' => './?_task=settings&_action=plugin.amacube_policy-save',
                ), $spam . $virus . $banned . $header . html::p( null, $this->rcmail->output->button( array(
              'command' => 'plugin.amacube_policy-save',
              'type' => 'input',
              'class' => 'button mainaction',
              'label' => 'save',
      ) ) ) );
    
    # Assemble Output
    $out = html::div( array(), html::tag( 'h2', array( 'class' => 'boxtitle' ), $this->gettext( 'box_title' ) ) . $form );
    
    # Register Form
    $this->rcmail->output->add_gui_object( 'amacube_policy_form', 'amacube_policy_form' );
    
    return $out;
  }
  
  function _show_checkbox( $id, $checked = false ) {
    $attr_array = array( 'name' => '_' . $id, 'id' => $id, 'type' => 'checkbox' );
    
    if( $checked )
      $attr_array['checked'] = 'checked';
      
    $box = html::tag( 'input', $attr_array );
    
    return $box;
  }
}
?>