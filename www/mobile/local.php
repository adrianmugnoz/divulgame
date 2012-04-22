<?php

//$globals['server_name']	= $_SERVER['HTTP_HOST'];
$globals['lang'] = $dblang	= 'es';
$globals['page_size'] = $page_size	= 30;
$globals['anonnymous_vote'] = $anonnymous_vote = true;
$globals['external_ads'] = $external_ads = true;
$globals['behind_load_balancer'] = false; // LB as those in Amazon EC2 don't send the real remote address
$globals['ssl_server'] = false; //Secure server must have the same name and base
$globals['email_domain'] = 'divulgame.net'; // Used for sending emails from web, if not defined it uses server_name
$globals['external_ads'] = false;
$globals['external_user_ads'] = false;
$globals['db_server'] = 'localhost';
$globals['db_name'] = 'divulgame_net';
$globals['db_user'] = 'divulgame_net';
$globals['db_password'] = '1592545';
$globals['db_use_transactions'] = true; // Disable it if you use MyISAM and have high loads

?>
