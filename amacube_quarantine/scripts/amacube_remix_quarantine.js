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

var total_items_in_quarantine = 0;
var settings = { current_page: 1,
								 items_per_page: 25,
								 search_term: '',
								 search_filter_by_body: 0,
								 search_filter_by_sender: 0,
								 search_filter_by_subject: 0,
								 sort_by: '',
								 sort_order: 'ASC',
						};

$( document ).ready(function() {
  update_thead_width();
});

$( window ).resize(function() {
  update_thead_width();
});

if( window.rcmail ) {
	// Register Event Listeners
	rcmail.addEventListener( 'plugin.response_refresh', response_refresh );
	rcmail.addEventListener( 'plugin.response_messagelist', response_messagelist );
	
	rcmail.addEventListener( 'init', function( evt ) {
		// Register Event Handlers
		rcmail.register_command('plugin.request_pagination', request_pagination, true );
		rcmail.register_command('plugin.request_search', request_search, true );
		rcmail.register_command('plugin.request_search_filter', request_search_filter, true );
		rcmail.register_command('plugin.request_search_reset', request_search_reset, true );
		rcmail.register_command('plugin.request_sort_by', request_sort_by, true );
		rcmail.register_command('plugin.request_quarantine_release', request_quarantine_release, true );
		rcmail.register_command('plugin.request_quarantine_discard', request_quarantine_discard, true );
		
		// Populate Quarantine Table
		request_initialize();
	});	
}

	/* Helper Functions */
	function set_loading_state() {
			// Clear Contents
			$( 'table#messagelist tbody' ).html( '' );
			
			// Enable Loader Icon
			$( 'div#messagelist' ).addClass( 'loading' );
	}
	
	function set_loaded_state( content ) {
			// Disable Loader Icon
			$( 'div#messagelist' ).removeClass( 'loading' );
			
			// Write Response
			$( 'table#messagelist tbody' ).html( content );
	}
	
	function update_thead_width() {
		$( "div#messagelist table.fixedcopy" ).css( 'width', $( "table#messagelist" ).css( 'width' ) );
	}
	
	function update_sort_arrows( sender, order ) {
		$( 'div#messagelist thead tr td a' ).addClass( 'ac_sort_none' ).removeClass( 'ac_sort_desc' ).removeClass( 'ac_sort_asc');
			
		switch( order ) {
			case 'ASC':
				$( sender ).removeClass( 'ac_sort_none' ).addClass( 'ac_sort_asc' );
				break;
			case 'DESC':
				$( sender ).removeClass( 'ac_sort_none' ).addClass( 'ac_sort_desc' );
				break;
		}
	}
	
	function update_message_count() {
		var first_item = ( ( settings['current_page'] -1 ) * settings['items_per_page'] ) + 1;
		var last_item = 0;
		
		if( settings['items_per_page'] > total_items_in_quarantine ) {
			last_item = total_items_in_quarantine;
		} else {
			if( ( settings['current_page'] * settings['items_per_page'] ) < total_items_in_quarantine ) {
				last_item = ( settings['current_page'] * settings['items_per_page'] );
			} else {
				last_item = total_items_in_quarantine % ( settings['current_page'] * settings['items_per_page'] );
			}
		}
		$( 'div#countcontrols span#rcmcountdisplay' ).html( 'Messages ' + first_item + ' to ' + last_item + ' of ' + total_items_in_quarantine );
		
		update_pagination_buttons( last_item );
	}
	
	function update_pagination_buttons( last_item ) {
		var first_button = $( 'div#countcontrols span.pagenavbuttons a.firstpage' );
		var prev_button = $( 'div#countcontrols span.pagenavbuttons a.prevpage' );
		var next_button = $( 'div#countcontrols span.pagenavbuttons a.nextpage' );
		var last_button = $( 'div#countcontrols span.pagenavbuttons a.lastpage' );
		
		// Backward Buttons
		if( settings['current_page'] == 1 ) {
			if( !first_button.hasClass( 'disabled' ) )
				first_button.addClass( 'disabled' );
			
			if( !prev_button.hasClass( 'disabled' ) )
				prev_button.addClass( 'disabled' );
		} else {
			if( first_button.hasClass( 'disabled' ) )
				first_button.removeClass( 'disabled' );
			
			if( prev_button.hasClass( 'disabled' ) )
				prev_button.removeClass( 'disabled' );
		}
		
		// Forward Buttons
		if( last_item < total_items_in_quarantine ) {
			if( next_button.hasClass( 'disabled' ) )
				next_button.removeClass( 'disabled' );
			
			if( last_button.hasClass( 'disabled' ) )
				last_button.removeClass( 'disabled' );
		} else {
			if( !next_button.hasClass( 'disabled' ) )
				next_button.addClass( 'disabled' );
			
			if( !last_button.hasClass( 'disabled' ) )
				last_button.addClass( 'disabled' );
		}
	}

	/* Request Handlers */
	function request_initialize() {
		set_loading_state();
		rcmail.http_post( 'plugin.request_ajax', { action: 'show_quarantine', settings: settings } );
	}
	
	function request_pagination( sender ) {
		// Ignore request from disabled buttons
		if( $( sender ).hasClass( 'disabled' ) )
			return;
		
		// Update Pagination Buttons
		switch( $( sender ).attr( 'id' ) ) {
			case 'page_first':
				settings['current_page'] = 1;
				break;
			case 'page_prev':
				if( settings['current_page'] > 1 )
					settings['current_page']--;
					
				break;
			case 'page_next':
				if( settings['current_page'] < Math.ceil( total_items_in_quarantine / settings['items_per_page'] ) )
					settings['current_page']++;
				break;
			case 'page_last':
				settings['current_page'] = Math.ceil( total_items_in_quarantine / settings['items_per_page'] );
				break;
		}
		
		set_loading_state();
		rcmail.http_post( 'plugin.request_ajax', { action: 'show_quarantine', settings: settings } );
	}
	
	function request_search() {
		settings['current_page'] = 1;
		settings['search_term'] = $( 'form[name=rcmqsearchform] input#quicksearchbox' ).val();
		
		set_loading_state();
		rcmail.http_post( 'plugin.request_ajax', { action: 'show_quarantine', settings: settings } );
	}
	
	function request_search_filter( sender ) {
		switch( $( sender ).attr( 'id' ) ) {
			case 'search_filter_by_body':
				settings['search_filter_by_body'] = $( sender ).is( ':checked' ) ? 1 : 0;
				break;
			case 'search_filter_by_sender':
				settings['search_filter_by_sender'] = $( sender ).is( ':checked' ) ? 1 : 0;
				break;
			case 'search_filter_by_subject':
				settings['search_filter_by_subject'] = $( sender ).is( ':checked' ) ? 1 : 0;
				break;
		}
	}
	
	function request_search_reset() {
		$( 'form[name=rcmqsearchform] input#quicksearchbox' ).val( '' );
		settings['current_page'] = 1;
		settings['search_term'] = '';
		
		set_loading_state();
		rcmail.http_post( 'plugin.request_ajax', { action: 'show_quarantine', settings: settings } );
	}
	
	function request_sort_by( sender ) {
		switch( $( sender ).attr( 'id' ) ) {
			case 'sort_by_sender':
				if( settings['sort_by'] == 'sender' ) {
					settings['sort_order'] = ( settings['sort_order'] == 'ASC' ) ? 'DESC' : 'ASC';
				} else {
					settings['sort_by'] = 'sender';
					settings['sort_order'] = 'ASC';
				}
				
				update_sort_arrows( sender, settings['sort_order'] );
				break;
			case 'sort_by_subject':
				if( settings['sort_by'] == 'subject' ) {
					settings['sort_order'] = ( settings['sort_order'] == 'ASC' ) ? 'DESC' : 'ASC';
				} else {
					settings['sort_by'] = 'subject';
					settings['sort_order'] = 'ASC';
				}
				
				update_sort_arrows( sender, settings['sort_order'] );
				break;
			case 'sort_by_date':
				if( settings['sort_by'] == 'date' ) {
					settings['sort_order'] = ( settings['sort_order'] == 'ASC' ) ? 'DESC' : 'ASC';
				} else {
					settings['sort_by'] = 'date';
					settings['sort_order'] = 'ASC';
				}
				
				update_sort_arrows( sender, settings['sort_order'] );
				break;
			case 'sort_by_type':
				if( settings['sort_by'] == 'type' ) {
					settings['sort_order'] = ( settings['sort_order'] == 'ASC' ) ? 'DESC' : 'ASC';
				} else {
					settings['sort_by'] = 'type';
					settings['sort_order'] = 'ASC';
				}
				
				update_sort_arrows( sender, settings['sort_order'] );
				break;
			case 'sort_by_score':
				if( settings['sort_by'] == 'score' ) {
					settings['sort_order'] = ( settings['sort_order'] == 'ASC' ) ? 'DESC' : 'ASC';
				} else {
					settings['sort_by'] = 'score';
					settings['sort_order'] = 'ASC';
				}
				
				update_sort_arrows( sender, settings['sort_order'] );
				break;
			default:
				return;
		}
		
		set_loading_state();
		rcmail.http_post( 'plugin.request_ajax', { action: 'show_quarantine', settings: settings } );
	}
	
	function request_quarantine_release( sender ) {
		var value = 0;
		
		if( $(sender).parent() && $(sender).parent().attr( 'id' ) )
			value = $(sender).parent().attr( 'id' );
			
		set_loading_state();
		rcmail.http_post( 'plugin.request_ajax', { action: 'quarantine_release', settings: settings, mail_id: value } );
	}
	
	function request_quarantine_discard( sender ) {
		var value = 0;
		
		if( $(sender).parent() && $(sender).parent().attr( 'id' ) )
			value = $(sender).parent().attr( 'id' );
			
		set_loading_state();
		rcmail.http_post( 'plugin.request_ajax', { action: 'quarantine_discard', settings: settings, mail_id: value } );
	}

	/* Response Handlers */
	function response_refresh() {
		rcmail.http_post( 'plugin.request_ajax', { action: 'show_quarantine', settings: settings } );
	}
	
	function response_messagelist( response ) {
		if( response.count ) {
			total_items_in_quarantine = response.count;
			
			update_message_count();
		}
		
		if( response.raw )
			set_loaded_state( response.raw );
		else
			set_loaded_state( 'No Results' );
	}
