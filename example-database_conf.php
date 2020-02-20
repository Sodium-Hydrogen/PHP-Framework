<?php
/*
-------------------------------------------
!! RENAME THIS FILE TO database_conf.php !!
-------------------------------------------
*/


// This frameworks uses a mysql or mariadb database
// This is the name of the database that the website will be using.
$sql_database = "Database Name";
// This is the mysql username
$sql_user_name = "Database Username";
// This is where the mysql password goes
$sql_password = "Database Password";

if($_SERVER['SCRIPT_NAME'] === '/database_conf.php'){
	$_GET['error'] = '404';
	require("index.php");
}

?>
