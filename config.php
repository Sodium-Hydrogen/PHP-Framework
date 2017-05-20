<?php 
/*
This file is were the server mysql information goes.
When it is first run it will ask for a username and password.
This is what you will use to login to your website's login page.

*/

// This is the name of the database that the website will be using.
$sql_database = "serverDb";
// This is the mysql username
$sql_user_name = "username";
// This is where the mysql password goes
$sql_password = "password"; 
// while this is set to true the program will display a coming soon page. 
// it is also needed to be on to set up mysql for the first time.
// After initial setup it is recommended to turn this option off.
$setup = true;
// Enables verbose logging and php error reporting
$debug_mode = true; 

if ($debug_mode) {
  //setting error reporting
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
}

// This code will only run if you are currently on config.php.
// Additionaly it will only give you access to create a username and password
// for your website if debugging mode is on.
// Once you turn setup mode off this page will simply redirect to a 404 error.
// The user it creates will be created with full admin privilages.

$actualLink = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$actualLink = substr($actualLink, strpos($actualLink, "//") +  2);
if($actualLink == $_SERVER[HTTP_HOST] . "/config.php" || $actualLink == $_SERVER[HTTP_HOST] . "/config.php/"){
  if($setup){
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
          password VARCHAR(50) NOT NULL,
          privilages VARCHAR(5) NOT NULL,
          question VARCHAR(40)
        )";
        if(mysqli_query($database, $command)){
          echo "<br>table creation command successful<br>";
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
            $command = "INSERT INTO accounts (username, password, privilages)
            VALUES ('" . $username . "', '" . $password . "', 'ADMIN')";

            if(mysqli_query($database, $command)){
              echo "Users successfully writen <br>";
            }else{
              echo "error saving user " . mysqli_error($database);
            }
          }
        }
        ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
          User Name:<br><input type="text" name="username"><br>
          Password:<br><input type="text" name="password"><br>
          <input type="submit">
        </form>
        <?php
      }
    }
    mysqli_close($database);
  }else{
    header( 'Location: index.php');
  }
}

?>
