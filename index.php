<?php


/*
This file loads all the environments for
this framework

Created by Mike Julander 
*/

require_once("resources/phpScripts/load.php");
$actual_link = strtolower(get_url("index.php"));
if($_GET){
  if(null !== ($error = $_GET['error'])){
    require("resources/theme/page/$error.php");
  }
}else{
  if($setup && null == $_SESSION['user']){
    echo "<h1>Coming Soon</h1>";
    load_page_head("");
  }else{

    $subPage = $_SESSION['page'];
    $success = false;
    for($i = 0; $i < count($subPage); $i++){
      if(substr($actual_link, 0, strpos($actual_link, '/', 0)) == $subPage[$i]){
        if(is_valid_subpage($subPage[$i], substr($actual_link, (strpos($actual_link, '/', 0) + 1)))){
	  $success = true;  
        }
      }else if($actual_link == $subPage[$i]){
	$success = true;
      }
    }
    if($success){
      require("resources/theme/page/index.php");
    }else{
      require("resources/theme/page/404.php");
    }
  }
}
?>
