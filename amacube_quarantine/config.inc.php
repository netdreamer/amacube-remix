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

# The database connection settings where amavis stores settings and emails
$rcmail_config['amacube_remix_db_dsn'] = 'mysql://amacube:amacube@localhost/amavisd';

# Enter hostname and port of the amavis AM.PDP process:
$rcmail_config['amacube_remix_amavis_host'] = '192.168.10.2';
$rcmail_config['amacube_remix_amavis_port'] = '9998';

# Enter config for bayesian training
$rcmail_config['amacube_remix_bayes']['enabled'] = false;
$rcmail_config['amacube_remix_bayes']['ham_pipe'] = '';
$rcmail_config['amacube_remix_bayes']['spam_pipe'] = '';
?>