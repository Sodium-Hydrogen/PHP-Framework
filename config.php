<?php 
/*
This file is were the server mysql information goes.
When it is first run it will ask for a username and password.
This is what you will use to login to your website's login page.

*/


// This is what will show in the page title
$pageName = "Sample";
// These are the pages that will be generated and what their names will be
// It is a good idea to leave home in the list
$subPage = array("home", "page1");
$login_header_link = array(
  //Use this to link other php scripts to the login headder in the framework
	//array("Other Page", "/other.php")
);

// This is the name of the database that the website will be using.
$sql_database = "serverDb";
// This is the mysql username
$sql_user_name = "username";
// This is where the mysql password goes
$sql_password = "password"; 
// This is the max allowed login attemps for the login page from one single ip address
$login_attemps = 4;
// This is the time that an ip address will be denied the login page once the max amount of attemps has been met
// It is also the time until failed logins are droped from the list
$ban_hours = 48;
// while this is set to true the program will display a coming soon page. 
// it is also needed to be on to set up mysql for the first time.
// After initial setup it is recommended to turn this option off.
$setup = true;
// Enables verbose logging and php error reporting
$debug_mode = false; 

if ($debug_mode) {
  //setting error reporting
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
}

// This Section loads all variables into the $_SESSION to be used later

session_start();

$_SESSION['page'] = $subPage;
$_SESSION['headerLink'] = $login_header_link;
$_SESSION['db'] = $sql_database;
$_SESSION['dbUser'] = $sql_user_name;
$_SESSION['dbPass'] = $sql_password;
$_SESSION['debug'] = $debug_mode;
$_SESSION['setup'] = $setup;
$_SESSION['site'] = $pageName;
$_SESSION['retry'] = $login_attemps;
$_SESSION['banTime'] = $ban_hours;

// This code will only run if you are currently on config.php.
// Additionaly it will only give you access to create a username and password
// for your website if debugging mode is on.
// Once you turn setup mode off this page will simply redirect to a 404 error.
// The user it creates will be created with full admin privilages.

$actualLink = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$actualLink = substr($actualLink, strpos($actualLink, "//") +  2);
if($actualLink == $_SERVER['HTTP_HOST'] . "/config.php" || $actualLink == $_SERVER['HTTP_HOST'] . "/config.php/"){
  if($setup){
    ?>
    <head>
      <title>
        Login
      </title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link rel="stylesheet" href="/resources/login.css">
    </head>
    <?php
    $database = new mysqli("localhost", $sql_user_name, $sql_password, $sql_database);

    if ($database->connect_errno) {
      echo "Failed to connect to MySQL: (" . $database->connect_errno . ") " . $database->connect_error;
    }else{
      echo "Success: ";
      echo $database->host_info . "\n";
      $command = "SELECT * FROM accounts";

      if(!mysqli_query($database, $command)){
        $command = "CREATE TABLE accounts (
          username VARCHAR(20) NOT NULL,
          password VARCHAR(64) NOT NULL,
	        salt VARCHAR(16) NOT NULL,
	        privilages VARCHAR(5) NOT NULL
        )";
        if(mysqli_query($database, $command)){
          $command = "CREATE TABLE blacklist (
            ipaddress VARCHAR(16) NOT NULL,
            attemps VARCHAR(1) NOT NULL,
            untilFree VARCHAR(11) NOT NULL
          )";
          mysqli_query($database, $command);
          if($debug_mode){
            echo "<br>table creation command successful<br>";
          }
        }
      }

      $command = "SELECT username FROM accounts";

      $outPut = mysqli_query($database, $command);
      $results = $outPut->fetch_assoc();
      if($results[username] == ""){
        if($_SERVER["REQUEST_METHOD"] == "POST"){
          if(!empty($_POST["username"]) && !empty($_POST["password"])){
            $username = $_POST["username"];
            $password = $_POST["password"];
            $username = trim($username);
	          $password = trim($password);
	          $salt = (string)bin2hex(openssl_random_pseudo_bytes(8));
	          $password = hash('sha256', ($password . $salt)); 
            $username = mysqli_real_escape_string($username);
            $command = "INSERT INTO accounts (username, password, salt, privilages)
            VALUES ('" . $username . "', '" . $password . "', '" . $salt . "', 'ADMIN')";

            if(mysqli_query($database, $command)){
              echo "User successfully writen <br>";
              header("location: /login.php");
            }else if($debug_mode){
              echo "error saving user " . mysqli_error($database);
            }
          }
        }
        ?>
        <div class="loginBox">
          <div clas="logo">
          </div>
          <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
            User Name:<br><input type="text" name="username"><br>
            Password:<br><input type="text" name="password"><br>
            <input type="submit" value="Create Account">
          </form>
        </div>
        <?php
      }
    }
    mysqli_close($database);
  }else{
    require("resources/theme/page/404.php");
  }
}
?>
