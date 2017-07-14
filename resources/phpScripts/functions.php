<?php
/*
get_url($name_of_file);
  It will return the url following the php file name that you specify
request_page_head();
  Loads the <head> for the website, it also calls load_page_head(); and get_url();
load_page_head($page_name);
  Loads some meta tags, links the fontawesome characters, and the title of the page
load_logged_header();
  Loads the necessary files for a header to appear when a user is logged in
  It also loads the header navigation header for when someone is logged in
load_content($page_name);
  This will read and display the content out of /content/page for the page specified
load_footer();
  Loads the footer of the website using all the information in /content/footer
breakup_file($input_string, $beginning_character_or_string, $ending_character_or_string);
  Used by load_content() and load_footer() to split the string from reading the file
break_to_end($input_string, $beginning_character_or_string);
  Used by load_content() and load_footer() to split the string from reading the file
login($Username, $Password);
  It will return the privilages of the user if successful
viewUsers();
  It will return an array of all users for the website
createAccount($username, $password, $privilages);
  Creates a new user with the specified username, password, and account privilages
delete_account($username, $password, $privilages);
  Only deletes the account if all input fields match
save_fail();
  It saves the login fail and the time until it will be cleared from record
check_attemps();
  This will return the number of fails the ip address has
clear_fails();
  Clears all login fails of the connecting ip address




*/

function get_url($fileName){
  $actualLink = $_SERVER['REQUEST_URI'];
  $pos = strrpos($actualLink, $fileName);
  if($pos >= 0){
    $relLink = substr($actualLink, $pos + strlen($fileName));
    if($relLink !== ""){
      $relLink = substr($relLink, 1);
      $pos = strrpos($relLink, "/");
      if($pos == strlen($relLink)-1){
        $relLink = substr($relLink, 0, $pos);
      }
    }
    if($relLink == "" || $relLink == $fileName){
      $relLink = "home";
    }
  }
  return strtolower($relLink);
}
function request_page_head(){
  $actual_link = get_url("index.php");

  if($actual_link !== "home"){
    $second = $actual_link;
  }else{
    $second = "";
  }
  load_page_head($second);
}
function load_page_head($second){
  if(!empty($second)){
    $second = " - " . ucfirst($second);
  }
  ?>
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content = "#222" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <title><?php echo $_SESSION['site'] . $second; ?></title>
  </head>
  <?php
}
function load_logged_header(){
  if(!empty($_SESSION['user'])){
    session_start();
    $user = $_SESSION['user'];
    ?>
    <script src="/resources/header.js"></script>
    <link rel="stylesheet" href="/resources/userHeaderStyle.css">
    <body onload="loadPreferences()">
    <div class="loginHeader" id="loginHeader">
      <button class="hideBtn" id="hideBtn" onclick="hide_logged_menu()">Hide</button>
      <div class="headerMenu" id="login_menu">
        <div class="username">
          <?php echo "Welcome, " . $user; ?>
        </div>
        <div class="links">
          <button class="dropBtn" onclick="header_dropdown()">Menu</button>
          <div class="dropdown-content" id="dropdownMenu">
            <a href="/">Home</a>
            <a href="/login.php/logout">Logout</a>
            <?php  if($_SESSION['permisions'] == "ADMIN"){?>
              <a href="/login.php/viewUser">View Users</a>
              <a href="/login.php/newUser">Add User</a>
              <a href="/login.php/delUser">Delete User</a>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
    <?php
  }
}
function load_content($url){
  $file = file_get_contents("content/page");
  $location = strpos($file, "## $url ##") + strlen("## $url ##");
  $end = strpos($file, "## End $url");
  $content = substr($file, $location, $end-$location);
  $location = strpos($content, "### Title ###");
  $end = strpos($content, "### Content ###");
  $dirLoc = strpos("### Direction ###") + strlen("### Direction ###") + 1;
  $direction = strtolower(trim(substr($content, $dirLoc, $location-$dirLoc)));
  $location += strlen("### Title ###");
  echo "<div class='content $direction'>";
  echo "<div class='title'><h3>";
  echo substr($content, $location, $end-$location);
  echo "</h3></div><div class='post'>";
  echo substr($content, $end+strlen("### Content ###"));
  echo "</div>";
}
function load_footer(){
  $file = file_get_contents("content/footer");
  $first = strpos($file, "\n");
  $second = strpos($file, "\n", $first+1);
  $ammount = trim(substr($file, $first+1, $second-$first));
  for ($i=1; $i <= $ammount; $i++) {
    echo "<div class='sections num$ammount section$i'>";
    $section1 = "## Section $i ##";
    $section2 = "## End Section $i";
    $fileEnd = "# End of File #";
    $raw = breakup_file($file, $section1, $section2);
    if(strpos($raw, "### Social ###") == 0){
      $title = "### Title ###";
      $end = "### Content ###";
      $content = breakup_file($raw, $title, $end);
      echo "<h4>$content</h4>";
      echo break_to_end($raw, $end);
    }else{
      $title = "### Title ###";
      $links = "### Links ###";
      $content = breakup_file($raw, $title, $links);
      echo "<h4>$content</h4>";
      $content = ltrim(break_to_end($raw, $links));
      $repeat = substr_count($content, " # ");
      echo "<ul class='socialBox'>";
      for ($n=0; $n < $repeat; $n++) {
        $name = strtolower(breakup_file($content, "", " # "));
        $name = str_replace(" ", "-", $name);
        $link = breakup_file($content, " # ", "\n");
        $content = break_to_end($content, "\n");
        echo "<li><a class='social' href='$link' target='_blank'><i class='fa fa-$name'>";
        echo "</i></a></li>";
      }
      echo "</ul>";
    }
    echo "</div>";
  }
}
function breakup_file($file, $begin, $end){
  $first = strpos($file, $begin)+strlen($begin);
  $second = strpos($file, $end);
  $content = substr($file, $first, $second - $first);
  return $content;
  // echo $content;
}
function break_to_end($file, $begin){
  $first = strpos($file, $begin)+strlen($begin);
  $content = substr($file, $first);
  return $content;

}
function login($userName, $passWord){
  session_start();
  $sql_user_name = $_SESSION['dbUser'];
  $sql_password = $_SESSION['dbPass'];
  $sql_database = $_SESSION['db'];
  $userName = trim($userName);
  $passWord = trim($passWord);
  $database = new mysqli("localhost", $sql_user_name, $sql_password, $sql_database);

  $command = "SELECT * FROM accounts WHERE username = '$userName' and password ='$passWord'";
  $output = mysqli_query($database, $command);
  $information = $output->fetch_assoc();
  $permisions = "none";

  if($information['username'] == $userName && $information['password'] == $passWord){
    $permisions = $information['privilages'];
  }

  mysqli_close($database);
  return $permisions;
}
function viewUsers(){
  session_start();
  $sql_user_name = $_SESSION['dbUser'];
  $sql_password = $_SESSION['dbPass'];
  $sql_database = $_SESSION['db'];
  $database = new mysqli("localhost", $sql_user_name, $sql_password, $sql_database);
  $command = "SELECT * FROM accounts";

  $output = mysqli_query($database, $command);
  return $output;

}
function createAccount($userName, $passWord, $privilages){
  session_start();
  $sql_user_name = $_SESSION['dbUser'];
  $sql_password = $_SESSION['dbPass'];
  $sql_database = $_SESSION['db'];
  $database = new mysqli("localhost", $sql_user_name, $sql_password, $sql_database);

  $userName = mysqli_real_escape_string($database, $userName);
  $passWord = mysqli_real_escape_string($database, $passWord);
  $command = "SELECT username FROM accounts WHERE username = '$userName'";

  $check = mysqli_query($database, $command);
  $result = "none";
  if(empty(mysqli_fetch_assoc($check))){

    $command = "INSERT INTO accounts (username, password, privilages)
    VALUES ('$userName', '$passWord', '$privilages')";
    mysqli_query($database, $command);
    $result = "success";
  }
  mysqli_close($database);

  return $result;

}
function delete_account($userName, $passWord, $privilages){
  session_start();
  $sql_user_name = $_SESSION['dbUser'];
  $sql_password = $_SESSION['dbPass'];
  $sql_database = $_SESSION['db'];
  $database = new mysqli("localhost", $sql_user_name, $sql_password, $sql_database);

  $userName = mysqli_real_escape_string($database, $userName);
  $passWord = mysqli_real_escape_string($database, $passWord);

  $command = "DELETE FROM accounts WHERE username = '$userName' and
  password = '$passWord' and privilages = '$privilages'";

  mysqli_query($database, $command);
  mysqli_close($database);

}
function save_fail(){
  $dbUser = $_SESSION['dbUser'];
  $dbPass = $_SESSION['dbPass'];
  $db = $_SESSION['db'];
  $timeout = $_SESSION['banTime'] * 3600;
  $cur = $_SERVER['REQUEST_TIME'];
  $ip = $_SERVER['REMOTE_ADDR'];
  $updated = false;
  $database = new mysqli("localhost", $dbUser, $dbPass, $db);

  $command = "SELECT * FROM blacklist";

  $output = mysqli_query($database, $command);
  if(mysqli_num_rows($output)>0){
    while($row = mysqli_fetch_assoc($output)){
      if($cur > $row['untilFree']){
        $tmp = $row['ipaddress'];
        $command = "DELETE FROM blacklist WHERE ipaddress = '$tmp'";
        mysqli_query($database, $command);
      }
      if($ip == $row['ipaddress']){
        $tmp = (int)$row['attemps'] + 1;
        $tmpT = $cur+$timeout;
        $command = "UPDATE blacklist SET attemps = '$tmp', untilFree = '$tmpT' WHERE ipaddress = '$ip'";
        mysqli_query($database, $command);
        $updated = true;
      }
    }
  }
  if(!$updated){
    $tmpT = $cur+$timeout;
    $command = "INSERT INTO blacklist (ipaddress, attemps, untilFree)
    VALUES ('$ip', '1', '$tmpT')";
    mysqli_query($database, $command);
  }

  mysqli_close($database);

}
function check_attemps(){
  $dbUser = $_SESSION['dbUser'];
  $dbPass = $_SESSION['dbPass'];
  $db = $_SESSION['db'];
  $timeout = $_SESSION['banTime'] * 3600;
  $cur = $_SERVER['REQUEST_TIME'];
  $ip = $_SERVER['REMOTE_ADDR'];

  $database = new mysqli("localhost", $dbUser, $dbPass, $db);

  $command = "SELECT * FROM blacklist WHERE ipaddress = '$ip'";

  $output = mysqli_query($database, $command);

  $output = mysqli_fetch_assoc($output);

  if($output['untilFree'] > $cur){
    return $output['attemps'];
  }else{
    return 0;
  }
  mysqli_close($database);

}
function clear_fails(){
  $dbUser = $_SESSION['dbUser'];
  $dbPass = $_SESSION['dbPass'];
  $db = $_SESSION['db'];
  $ip = $_SERVER['REMOTE_ADDR'];

  $database = new mysqli("localhost", $dbUser, $dbPass, $db);

  $command = "DELETE FROM blacklist WHERE ipaddress = '$ip'";

  mysqli_query($database, $command);

  mysqli_close($database);

}
?>
