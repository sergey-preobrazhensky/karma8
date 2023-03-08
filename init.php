<?php

$dbhost = 'localhost';
$dbname = 'karma8';
$dbuser = 'postgres';
$dbpass = '';

$dbconn = pg_connect("host=$dbhost dbname=$dbname user=$dbuser password=$dbpass")
or exit('Could not connect: '.pg_last_error());

function shutdown($dbconn)
{
    pg_close($dbconn);
}
register_shutdown_function('shutdown', $dbconn);
