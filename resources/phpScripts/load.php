<?php
include(dirname(__FILE__)."/functions.php");

session_set_cookie_params(0, '/', null, true, true);
session_name("__Host-SID");
session_start();


if(refresh_session()){
	// This Section loads all variables into the $_SESSION to be used later
	include(dirname(__FILE__).'/../../database_conf.php');
	$_SESSION['db'] = $sql_database;
	$_SESSION['dbUser'] = $sql_user_name;
	$_SESSION['dbPass'] = $sql_password;
	$_SESSION["database_version"] = $_database_version;
	$_SESSION['vars'] = [];

	login_extended();

	load_variables_from_database();

	save_referer();
}



if (isset($_SESSION['debug']) && $_SESSION['debug'] == true) {
	//setting error reporting
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}else{
	error_reporting(0);
	ini_set('display_errors', 0);
}



 ?>
