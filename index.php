<?php


/*
This file loads all the environments for
this framework

Created by Mike Julander
*/

require_once("resources/phpScripts/load.php");
load_logged_header();
$actual_link = get_url();
if(!$_GET){
	if($_SESSION['setup'] && empty($_SESSION['user'])){
		load_page_head();
		echo "<h1>Coming Soon</h1>";
	}else{
		$success = false;
		foreach($_SESSION['pages'] as $main_page){
			if(strtolower($main_page['name']) == $actual_link){
				$success = true;
				break;
			}else{
				foreach($main_page['posts'] as $sub_page){
					if(strtolower($main_page['name'] . '/' . $sub_page) === $actual_link){
						$success = true;
						break;
					}
				}
			}
		}
		if($success){
			if(file_exists("resources/theme/page/index.php")){
				require("resources/theme/page/index.php");
			}else{
				load_page_head();
				echo "<h2> Unable to load a theme, please install one.</h2>";
				$_GET['error'] = '501';
			}
		}else{
			$_GET['error'] = '404';
		}
	}
}
if($_GET){
	if(null !== ($error = $_GET['error'])){
		$_GET['error'] = strval(intval($_GET['error']));
		if(file_exists("resources/theme/page/error.php") === true){
			require("resources/theme/page/error.php");
		}else{
			load_page_head();
			echo "<h1>Error " . $error . "</h1><h3>";
			echo get_error_message($error);
			echo "</h3><hr>Unable to load error page falling back to the built in one.";
			http_response_code($error);
		}
	}
}

?>
