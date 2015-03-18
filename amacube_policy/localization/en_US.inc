<?php
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

$labels = array();
$labels['page_title'] = 'Amavis Settings';
$labels['menu_button'] = 'Amavis Policy';
$labels['box_title'] = 'Amavis Policy';
$labels['section_spam'] = 'Spam Settings';
$labels['section_spam_check'] = 'Enable Spam Detection';
$labels['section_spam_settings'] = 'Quarantined Email Destination:';
$labels['section_spam_junk_score'] = 'Move Mail to Junk Folder when score exceeds:';
$labels['section_spam_quarantine_score'] = 'Move Mail to Quarantine when score exceeds:';
$labels['section_virus'] = 'Virus Settings';
$labels['section_virus_check'] = 'Enable Virus Scanning';
$labels['section_virus_settings'] = 'Infected Email Destination:';
$labels['section_banned'] = 'Banned Attachment Settings';
$labels['section_banned_check'] = 'Enable Banned Attachment Screening';
$labels['section_banned_settings'] = 'Banned File Email Destination:';
$labels['section_header'] = 'Bad Header Settings';
$labels['section_header_check'] = 'Enable Bad Header Detection';
$labels['section_header_settings'] = 'Email Containing Bad Headers Destination:';

$messages = array();
$messages['successfully saved'] = 'Your new settings have been stored successfully.';
$messages['policy_default_message'] = 'No policy found for this user, initializing default policy!';
$messages['spam_junk_score_error'] = 'Spam level must be an integer number between 0 and 20';
$messages['spam_quarantine_score_error'] = 'Quarantine level must be an integer number between 0 and 20';
?>