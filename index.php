<?php


/*
This file loads all the environments for
this framework

Created by Mike Julander
*/

require_once("resources/phpScripts/load.php");
load_logged_header();
$actual_link = explode('/', get_url(false));
if(empty($_SESSION['error'])){
	if($_SESSION['setup'] && empty($_SESSION['user'])){
		load_page_head();
		echo "<h1>Coming Soon</h1>";
		if($_SESSION["show_login"]){
			echo "<p><a href='/login.php'>Login</a></p>";
		}
	}else{
		$success = true;
		if($actual_link[0] != ''){
			$success = false;
			foreach($_SESSION['pages'] as $main_page){
				if(strtolower($main_page['title']) == $actual_link[0]){
					$success = true;
					break;
				}
			}
		}
			// }else{
			// 	foreach($main_page['posts'] as $sub_page){
			// 		if(strtolower($main_page['name'] . '/' . $sub_page) == $actual_link){
			// 			$success = true;
			// 			break;
			// 		}
			// 	}
			// }
		if($success){
			if(file_exists("resources/theme/page/index.php")){
				require("resources/theme/page/index.php");
			}else{
				queue_body("<h2> Unable to load a theme, please install one.</h2>");
				$_SESSION['error'] = '501';
			}
		}else{
			print "error";
			$_SESSION['error'] = '404';
		}
	}
}
if(isset($_SESSION['error'])){
	if(null !== $_SESSION['error']){
		if(file_exists("resources/theme/page/error.php") === true && !($_SESSION['setup'] && empty($_SESSION['user']))){
			require("resources/theme/page/error.php");
		}else{
			load_page_head();
			print "<h1>Error $_SESSION[error] </h1><h3>".get_error_message($_SESSION["error"])."</h3>";
			if(!$_SESSION["setup"]){
				echo "<hr>Unable to load error page falling back to the built in one.";
			}else if($_SESSION["show_login"]){
				echo "<p><a href='/login.php'>Login</a></p>";
			}
			http_response_code($_SESSION["error"]);
			unset($_SESSION["error"]);
		}
	}
}

?>
