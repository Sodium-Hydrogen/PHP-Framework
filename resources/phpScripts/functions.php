<?php
/*
This is were the functions 
go for the framework
*/

function get_url(){
  $actualLink = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
  $pos = strpos($actualLink, "/index.php");
  $relLink = substr($actualLink, $pos + strlen("/index.php"));
  if($relLink !== ""){
    $relLink = substr($relLink, 1);
  }
  return $relLink;
}

?>
