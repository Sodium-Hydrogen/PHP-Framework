<?php
/*
This is were the functions 
go for the framework

Here are the headers for the functions
string get_url();
string login($username, $password, $sql_username, $sql_password, $sql_database);



*/



function get_url(){
  $actualLink = $_SERVER[REQUEST_URI];
  $pos = strpos($actualLink, "/");
  if($pos >= 0){
    $relLink = substr($actualLink, $pos);
    if($relLink !== ""){
      $relLink = substr($relLink, 1);
      $pos = strrpos($relLink, "/");
      if($pos == strlen($relLink)-1){
        $relLink = substr($relLink, 0, $pos);
      }
    }
    if($relLink == "" || $relLink == "index.php"){
      $relLink = "home";
    }
  }
  return $relLink;
}

function login($userName, $passWord, $sql_username, $sql_password, $sql_database){
  $userName = trim($userName);
  $passWord = trim($passWord);
  $database = new mysqli("localhost", $sql_username, $sql_password, $sql_database);

  $command = "SELECT * FROM accounts";
  $output = mysqli_query($database, $command);
  $information = $output->fetch_assoc();
  $permisions = "none";

  if($information[username] == $userName && $information[password] == $passWord){
      $permisions = $information[privilages];
  }

  mysqli_close($database);
  return $permisions;
}
?>
