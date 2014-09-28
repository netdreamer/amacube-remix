<?php

// The database connection settings where amavis stores settings and emails
// see mysql.schema for more details.
#$rcmail_config['amacube_db_dsn'] = 'mysql://<MYSQL-USER>:<MYSQL-PASSWORD>@<MYSQL-HOST>/<MYSQL-DATABASE>';
$rcmail_config['amacube_db_dsn'] = 'mysql://amacube:amacube@10.15.81.17/amavisd';

// for release of quarantined emails, amavis must be set up to accept socket connections
// from the host where roundcube is running on. see README.md for more details.
// Enter hostname and port of the amavis process:
$rcmail_config['amacube_amavis_host'] = '10.15.81.17';
$rcmail_config['amacube_amavis_port'] = '9998';

?>
