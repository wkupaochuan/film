<?php
$active_group = 'default';
$query_builder = TRUE;

$db['default'] = array(
	'hostname' => '127.0.0.1',
	'port' => 3307,
	'username' => 'root',
	'password' => 'root',
	'database' => 'skin',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'production'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE,
);

