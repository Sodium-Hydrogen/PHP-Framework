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
is_valid_subpage($mainpage, $subpage);
  This checks to see if a subpage of a main page is valid and it will return a true, otherwise 
  it will return a false
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
delete_account($username, $privilages);
  Only deletes the account if all input fields match
admin_change_password($username, $newPassword);
  This function will change a user password with just their username and password
change_password($username, $oldPassword, $newPassword);
  This is used for users changing their own password it verifies that their password is valid
  before changing it.
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
  return strtolower(str_replace("%20", " ", $relLink));
}
function request_page_head(){
  $actual_link = get_url("index.php");

  if($actual_link !== "home"){
    $subPage = $_SESSION['page'];
    $success = false;
    for($i = 0; $i < count($subPage); $i++){
      if(substr($actual_link, 0, strpos($actual_link, '/', 0)) == $subPage[$i]){
          $success = true;
      }
    }if($success){
      $second = substr($actual_link, 0, strpos($actual_link, '/', 0));
    }else{
      $second = $actual_link;
    }
  }else{
    $second = "";
  }
  load_page_head($second);
}
function load_page_head($second){
  if(!empty($second)){
    $second = " - " . ucwords($second);
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
            <a href="/login.php/changePassword">Change Password</a>
            <?php
	    if(count($_SESSION['headerLink']) > 0){
	      for($i = 0; $i < count($_SESSION['headerLink']); $i++){
		if(strspn($_SESSION['headerLink'][$i][0], "*") == 1){
		  if($_SESSION['permisions'] == "ADMIN"){
	            echo "<a href=" . $_SESSION['headerLink'][$i][1] . ">" . substr($_SESSION['headerLink'][$i][0], 1) . "</a>";
		  }
		}else{
	          echo "<a href=" . $_SESSION['headerLink'][$i][1] . ">" . $_SESSION['headerLink'][$i][0] . "</a>";
		}
	      }
	    }
	    if($_SESSION['permisions'] == "ADMIN"){?>
              <a href="/login.php/manageUsers">Manage Users</a>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
    <?php
  }
}
function is_valid_subpage($mainpage, $subpage){
  $file = file_get_contents("content/page");
  $location = strpos($file, "## $mainpage ##") + strlen("## $mainpage ##");
  $end = strpos($file, "## End $mainpage");
  $content = substr($file, $location, $end-$location);
  if(strpos($content, "#### Posts ####") > 0){
    if(strpos($content, "#### $subpage ####") > 0){
      return true;
    }
  }
  return false;
}
function load_content($url){
  $subPage = NULL;
  $file = file_get_contents("content/page");
  if(strpos($url, '/') > 0){
    $subPage = substr($url, strpos($url, '/') + 1);
    $url = substr($url, 0, strpos($url, '/'));
  }
  $location = strpos($file, "## $url ##") + strlen("## $url ##");
  $end = strpos($file, "## End $url");

  $content = substr($file, $location, $end-$location);
  $location = strpos($content, "### Title ###");
  $end = strpos($content, "### Content ###");
  $dirLoc = strpos("### Direction ###") + strlen("### Direction ###") + 1;
  $direction = strtolower(trim(substr($content, $dirLoc, $location-$dirLoc)));
  if($subPage != NULL)
    $direction = 'row';
  $location += strlen("### Title ###");
  echo "<div class='content $direction'>";
  echo "<div class='title'><h3>";
  $pageContent = substr($content, $end+strlen("### Content ###"));
  $pageDisplay = $pageContent;
  if($subPage == NULL){
    echo substr($content, $location, $end-$location);
  }else{
    $titleLocation = strpos($pageContent, "#### $subPage ####") + (10 + strlen($subPage));
    $picLocation = strpos($pageContent, "#### Picture:", $titleLocation);
    echo substr($pageContent, $titleLocation, $picLocation - $titleLocation) . "<br><br>
    <img src='";
    $picLocation += 14;
    $endOfPic = strpos($pageContent, "####", $picLocation);
    echo substr($pageContent, $picLocation, $endOfPic - $picLocation); 
    echo "'/>";
    $endOfPic += 5;
    $pageDisplay = substr($pageContent, $endOfPic, strpos($pageContent, "#### $subPage end ####", $endOfPic) - $endOfPic);
  }
  echo "</h3></div><div class='post'>";
  if(strpos($pageContent, "#### Posts ####") > 0 && $subPage == NULL){
    $startOfDisplay = strpos($pageContent, '#### Posts ####');
    $pageDisplay = substr($pageContent, 0, $startOfDisplay);
    $startOfDisplay += 16;
    $pageContent = substr($pageContent, $startOfDisplay);
    $pageDisplay .= "<br><br><table>";
    while(strpos($pageContent, "end ####") > 0){
      $postName = trim(substr($pageContent, 4, strpos($pageContent, " ####", 4) - 4));
      $pageContent = substr($pageContent, strpos($pageContent, "\n") + 1);
      $title = substr($pageContent, 0, strpos($pageContent, "\n"));
      $pageContent = substr($pageContent, strpos($pageContent, "\n"));
      $picture = substr($pageContent, strpos($pageContent, ":") + 1, strpos($pageContent, "####", 5) - strpos($pageContent, ":") - 1);
      $pageContent = substr($pageContent, strpos($pageContent, "#### $postName end ####") + strlen("#### $postName end ####\n"));
      $postName = "/index.php/$url/$postName";
      $pageDisplay .= "<tr><td><a href='$postName'>$title</a></td>
      <td><a href='$postName'><img src='$picture'></a></td></tr>";
    }
    $pageDisplay .= "</table>";
    $pageContent = $pageDisplay;
  }else if($subPage != NULL){
    $pageContent = $pageDisplay; 
  }
  echo $pageContent;
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
}
function break_to_end($file, $begin){
  $first = strpos($file, $begin)+strlen($begin);
  $content = substr($file, $first);
  return $content;

}
function login($userName, $passWord){
  $sql_user_name = $_SESSION['dbUser'];
  $sql_password = $_SESSION['dbPass'];
  $sql_database = $_SESSION['db'];
  $userName = trim($userName);
  $passWord = trim($passWord);

  $database = new mysqli("localhost", $sql_user_name, $sql_password, $sql_database);

  $userName = mysqli_real_escape_string($database, $userName);

  $command = "SELECT * FROM accounts WHERE username ='$userName'";

  $output = mysqli_query($database, $command);
  $salt = $output->fetch_assoc()['salt'];
  $passWord = hash('sha256', $passWord . $salt);

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
  $sql_user_name = $_SESSION['dbUser'];
  $sql_password = $_SESSION['dbPass'];
  $sql_database = $_SESSION['db'];
  $database = new mysqli("localhost", $sql_user_name, $sql_password, $sql_database);
  $command = "SELECT * FROM accounts";

  $output = mysqli_query($database, $command);
  return $output;

}
function createAccount($userName, $passWord, $privilages){
  $sql_user_name = $_SESSION['dbUser'];
  $sql_password = $_SESSION['dbPass'];
  $sql_database = $_SESSION['db'];
  $database = new mysqli("localhost", $sql_user_name, $sql_password, $sql_database);

  $userName = mysqli_real_escape_string($database, $userName);
  
  $command = "SELECT username FROM accounts WHERE username = '$userName'";

  $check = mysqli_query($database, $command);
  $result = "none";
  if(empty(mysqli_fetch_assoc($check))){
    $salt = (string)bin2hex(openssl_random_pseudo_bytes(8));
    $passWord = hash('sha256', ($passWord . $salt));

    $command = "INSERT INTO accounts (username, password, salt, privilages)
    VALUES ('$userName', '$passWord', '$salt', '$privilages')";
    mysqli_query($database, $command);
    $result = "success";
  }
  mysqli_close($database);

  return $result;

}
function admin_change_password($username, $newPassword){
  $sql_user_name = $_SESSION['dbUser'];
  $sql_password = $_SESSION['dbPass'];
  $sql_database = $_SESSION['db'];
  $database = new mysqli("localhost", $sql_user_name, $sql_password, $sql_database);

  $username = mysqli_real_escape_string($database, $username);
  $newPassword = mysqli_real_escape_string($database, $newPassword);

  $command = "SELECT username FROM accounts WHERE username = '$username'";

  $check = mysqli_query($database, $command);
  $result = "none";

  if(!empty(mysqli_fetch_assoc($check))){
    $salt = (string)bin2hex(openssl_random_pseudo_bytes(8));
    $newPassword = hash('sha256', ($newPassword . $salt));

    $command = "UPDATE accounts
    SET password='$newPassword', salt='$salt'
    WHERE username='$username'";

    mysqli_query($database, $command);
    $result = "success";
  }

  mysqli_query($database, $command);
  mysqli_close($database);

}
function change_password($username, $oldPassword, $newPassword){
  $sql_user_name = $_SESSION['dbUser'];
  $sql_password = $_SESSION['dbPass'];
  $sql_database = $_SESSION['db'];
  $database = new mysqli("localhost", $sql_user_name, $sql_password, $sql_database);
  
  $username = mysqli_real_escape_string($database, $username);

  $command = "SELECT password, salt FROM accounts WHERE username = '$username'";

  $output = mysqli_query($database, $command);
  $result = 'none';
  $data = $output->fetch_assoc();
  $salt = $data['salt'];
  $oldPassword = hash('sha256', ($oldPassword . $salt));
  $password = $data['password'];
  if($oldPassword == $password){
    admin_change_password($username, $newPassword);
    $result = "success";
  }
  mysqli_close($database);
  return $result;
}
function delete_account($userName, $privilages){
  $sql_user_name = $_SESSION['dbUser'];
  $sql_password = $_SESSION['dbPass'];
  $sql_database = $_SESSION['db'];
  $database = new mysqli("localhost", $sql_user_name, $sql_password, $sql_database);

  $userName = mysqli_real_escape_string($database, $userName);

  $command = "DELETE FROM accounts WHERE username = '$userName' and privilages = '$privilages'";

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
