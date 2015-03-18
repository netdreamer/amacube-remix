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

$labels = array();
$labels['header_date'] = 'Date';
$labels['header_from'] = 'From';
$labels['header_subject'] = 'Subject';
$labels['header_type'] = 'Type';
$labels['header_score'] = 'Score';
$labels['content_decode_B'] = 'Banned Content';
$labels['content_decode_C'] = 'Clean';
$labels['content_decode_H'] = 'Bad Header';
$labels['content_decode_M'] = 'Bad Mime';
$labels['content_decode_O'] = 'Oversized';
$labels['content_decode_S'] = 'Killed Spam';
$labels['content_decode_T'] = 'MTA error';
$labels['content_decode_U'] = 'Unchecked';
$labels['content_decode_V'] = 'Virus';
$labels['content_decode_Y'] = 'Spam';
$labels['process'] = 'Process';
$labels['quarantine'] = 'Quarantine';
$labels['quarantine heading'] = 'Quarantined E-Mails';
$labels['quarantine_pagetitle'] = 'Quarantined emails';

$messages = array();
$messages['error_quarantine_discard'] = 'Error discarding message.  Please try again';
$messages['intersection_error'] = 'You cannot delete AND release quarantined emails for delivery.';
$messages['quarantine_no_result'] = 'No quarantined emails have been found.';
?>