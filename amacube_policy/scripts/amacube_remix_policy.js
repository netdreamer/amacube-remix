/**
* This file is part of the Amacube-Remix_Policy Roundcube plugin
* Copyright (C) 2015, Tony VanGerpen <Tony.VanGerpen@hotmail.com>
* 
* A Roundcube plugin to let users change their amavis policy settings (which must be stored in a database)
* Based heavily on the amacube plugin by Alexander Köb (https://github.com/akoeb/amacube)
* 
* Licensed under the GNU General Public License version 3. 
* See the COPYING file in parent directory for a full license statement.
*/

if (window.rcmail) {
  rcmail.addEventListener('init', function(evt) {
    rcmail.register_command('plugin.amacube_remix_policy', function() { rcmail.gui_objects.amacube_remix_policy_form.submit(); }, true);
  });
  
  /* Spam Range Slider */
  $(function() {
    $( "#spam_slider" ).slider({
      range: true,
      step: 0.1,
      min: -10,
      max: 23,
      values: [ $( "#spam_settings_junk_score" ).val(), $( "#spam_settings_quarantine_score" ).val() ],
      slide: function( event, ui ) {
        $( "#spam_settings_junk_score" ).val( ui.values[ 0 ] );
        $( "#spam_settings_quarantine_score" ).val( ui.values[ 1 ] );
        $( "#spam_settings_junk_score_display" ).text( ui.values[ 0 ] );
        $( "#spam_settings_quarantine_score_display" ).text( ui.values[ 1 ] );
        $( "#spam_slider_upper_range").css('width', 100 - ( ( ui.values[1] + 10 ) * 3 ) +'%');
      }
    }).append( '<div id="spam_slider_upper_range" style="width: ' + ( 100 -( parseInt( $( "#spam_slider" ).slider( 'option', 'values' )[1] ) + 10 ) * 3 ) + '%"></div>' );
 
    /* Set Hidden Form Fields to Policy Default Values */
    $( "#spam_settings_junk_score" ).val( $( "#spam_slider" ).slider( "values", 0 ) );
    $( "#spam_settings_quarantine_score" ).val( $( "#spam_slider" ).slider( "values", 1 ) );
    $( "#spam_settings_junk_score_display" ).text( $( "#spam_slider" ).slider( "values", 0 ) );
    $( "#spam_settings_quarantine_score_display" ).text( $( "#spam_slider" ).slider( "values", 1 ) );
    
    /* Event Handler - Spam Checks */
    $( "input#spam_check_toggle" ).change( function() {
      var display = $( this ).is( ':checked' ) ? 'table-row' : 'none';
      $( "tr#spam_check_score_settings" ).css( 'display', display );
      $( "tr#spam_check_quarantine_settings" ).css( 'display', display );
    });
    
    /* Event Handler - Virus Checks */
    $( "input#virus_check_toggle" ).change( function() {
      var display = $( this ).is( ':checked' ) ? 'table-row' : 'none';
      $( "tr#virus_check_quarantine_settings" ).css( 'display', display );
    });
    
    /* Event Handler - Banned Files Checks */
    $( "input#banned_check_toggle" ).change( function() {
      var display = $( this ).is( ':checked' ) ? 'table-row' : 'none';
      $( "tr#banned_check_quarantine_settings" ).css( 'display', display );
    });
    
    /* Event Handler - Bad Header Checks */
    $( "input#header_check_toggle" ).change( function() {
      var display = $( this ).is( ':checked' ) ? 'table-row' : 'none';
      $( "tr#header_check_quarantine_settings" ).css( 'display', display );
    });
    
    /* Hack to show vertical scrollbar (Kinda dumb, but couldn't find a better way) */
    $( "div#pluginbody" ).css( 'overflow-y', 'auto' );
  });
}