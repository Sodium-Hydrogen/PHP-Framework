<?php
include(dirname(__FILE__)."/functions.php");
session_start();



if(refresh_session()){
	// This Section loads all variables into the $_SESSION to be used later
	include(dirname(__FILE__).'/../../database_conf.php');
	$_SESSION['db'] = $sql_database;
	$_SESSION['dbUser'] = $sql_user_name;
	$_SESSION['dbPass'] = $sql_password;
	$_SESSION['vars'] = Array();

	load_variables_from_database();

	login_extended();
}



if ($_SESSION['debug'] == true) {
	//setting error reporting
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}else{
	error_reporting(0);
	ini_set('display_errors', 0);
}



 ?>
