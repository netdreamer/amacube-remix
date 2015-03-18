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

$labels = array();
$labels['page_title'] = 'Amavis WBlist';
$labels['menu_button'] = 'Amavis WBlist';
$labels['box_title'] = 'Amavis White / Black List';
$labels['no_entries'] = 'No Entries in WBlist policy';
$labels['section_wblist_add'] = 'Add Entry';

$messages = array();
$messages['error_wblist_add_address'] = 'Unable to add empty wblist entry';
$messages['error_wblist_add_policy'] = 'Unable to add Invalid Policy';
$messages['error_wblist_delete'] = 'Unable to delete wblist entry';
?>