<?php
// This genarates the login page

require_once("resources/phpScripts/load.php");
$page = strtolower(get_url("login.php"));
if(check_attemps() >= $_SESSION['retry']){
  require("resources/theme/page/404.php");
  return 0;
}
load_page_head("Login");
echo "<link rel='stylesheet' href='/resources/login.css'>";
echo "<link rel='icon' href='/resources/theme/resources/favicon.png'>";
?>


<?php
if(!empty($_SESSION['user']) && $page !== "logout"){
  load_logged_header();
}
if("manageusers" == $page && "ADMIN" == $_SESSION['permisions']){
  $displayNewPass = false;
  if($_SERVER["REQUEST_METHOD"] == "POST"){
    //foreach($_POST as $key => $test){
    //  echo $key . " = " . $test . "<br>";
    //}
    if($_POST["function"] == "Delete User"){
      $user = $_POST['username'];
      $priv = $_POST['permis'];
      if($user !== $_SESSION['user']){
        delete_account($user, $priv);
      }
    }else if($_POST['function'] == "Reset Password"){
      $pieces = [];
      $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $max = strlen($keyspace) - 1;
	for ($i = 0; $i < 14; ++$i) {
          $pieces []= $keyspace[random_int(0, $max)];
	}
      $newPass = implode('', $pieces);
      $user = $_POST['username'];
      $displayNewPass = true;
      if($user !== $_SESSION['user']){
        admin_change_password($user, $newPass);
      }
    }
  }

  $data = viewUsers();
  ?>
  <div class="users">
    <div class="row specialRow">
      <div class="headCell colOne">Username</div>
      <div class="headCell colTwo">Permisions</div>
      <div class="headCell colThree"></div>
      <div class="headCell colFour"></div>
    </div>
    <?php
    if(mysqli_num_rows($data)>0){
      while($row = mysqli_fetch_assoc($data)){
	echo "<form method='post' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "' onsubmit=\"return confirm('Are you sure you want to ' + choice + '?');\">";
        echo "<div class='row'>";
        echo "<div class='cell colOne'>" . $row['username'] . "</div>
        <div class='cell colTwo'>" . $row['privilages'] . "</div>";
	echo "<input type='hidden' name='username' value='" . $row['username'] . "'>
	<input type='hidden' name='permis' value='" . $row['privilages'] . "'>";
	echo "<div class='cell colThree'>";
	if($row['username'] != $_SESSION['user'] && !$displayNewPass){
          echo "<input type='submit' name='function' value='Reset Password' onclick=\"choice = 'reset the password for user: " . $row['username'] . "'\">";
	}else if($row['username'] == $user){
	  echo $newPass;
	}
        echo "</div>
	<div class='cell colFour'>";
	if($row['username'] != $_SESSION['user']){
          echo "<input type='submit' name='function' value='Delete User' onclick=\"choice = 'delete user: " . $row['username'] . "'\">";
	}
	echo "</div>
        </div>
	</form>";
      }
    }
    ?>
    <hr class='spacer'>
    <div class='row specialRow'><div class='cell colTwo'><button onclick="window.location='/login.php/newUser'">Add New User</button></div></div>
  </div>
  <?php

}else if("logout" == $page && null !== $_SESSION['permisions']){
  echo "<div class='loginBox'>";
  echo "Username: " . $_SESSION['user'] . "<br>" . "Account Type: " . $_SESSION['permisions'];
  if(session_destroy()){
    echo "<br>logged Out<br>";
  }
  echo "<a href='/'>Return to Main Page</a>";
  echo "</div>";
}else if((!empty($_POST["newUser"]) || "newuser" == $page) && "ADMIN" == $_SESSION['permisions']){
  $message = NULL;
  if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!empty($_POST["newUsername"]) && !empty($_POST["newPassword"])){
      $username = $_POST["newUsername"];
      $password = $_POST["newPassword"];
      if("Basic" == $_POST['permis']){
        $privilages = "BASIC";
      }else{
        $privilages = "ADMIN";
      }
      if($_POST['newPassword'] == $_POST['repeat'] && strlen($_POST['newPassword']) >= 8){
        $result = createAccount($username, $password, $privilages);
      }else if(strlen($_POST['newPassword']) < 8){
	$result = "none";
        $message = "Password is too short.";
      }else{
	$result = "none";
        $message = "Passwords do not match.";
      }
      if($result !== "none"){
        header("location: /login.php/manageUsers");
      }else{
	echo "<div class='warning'>
	Unable to create account. <br>";
	if($message != NULL){
	  echo $message;
	}else{
		echo "message User may already exist.";
        } echo "</div>"; }
    }
  }

  ?>
  <div class="loginBox">
    <div clas="logo">
    </div>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
      User Name:<br><input type="text" name="newUsername" required><br>
      Password:<br><input type="password" name="newPassword" required><br>
      Retype Password:<br><input type="password" name="repeat" required><br>
      Account Type:<br><input type="radio" name="permis" value="Admin">Admin<br>
      <input type="radio" name="permis" value="Basic" checked>Basic<br>
      <input type="submit" name="newUser" value="Create Account">
      <br><br><a href="/login.php/manageUsers">Go Back?</a>
    </form>
  </div>
  <?php
}else if($page == "changepassword" && null !== $_SESSION['permisions']){
  if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!empty($_POST["oldPassword"]) && !empty($_POST["newPassword"])){
      $username = $_SESSION["user"];
      $oldPassword = $_POST["oldPassword"];
      $newPassword = $_POST["newPassword"];
      if($_POST['newPassword'] == $_POST['repeat'] && strlen($_POST['newPassword']) >= 8){
        $result = change_password($username, $oldPassword, $newPassword);
      }else if(strlen($_POST['newPassword']) < 8){
	$result = "none";
        $message = "Password is too short.";
      }else{
	$result = "none";
        $message = "Passwords do not match.";
      }
      if($result == "none"){
	echo "<div class='warning'>
	Unable to change password. <br>";
	echo $message;
	echo "</div>";
      }else if($result == "success"){
	echo "<div class='success'>
	Password was changed successfuly!
	</div>";
      }
    }
  }
  ?>
  <div class="loginBox">
    <div clas="logo">
    </div>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
      Old Password:<br><input type="password" name="oldPassword" required><br>
      New Password:<br><input type="password" name="newPassword" required><br>
      Repeat New Password:<br><input type="password" name="repeat" required><br>
      <input type="submit" name="changePass" value="Change Password">
    </form>
  </div>
  <?php
}else if($page == "home"){
  $users = "";
  if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!empty($_POST["username"]) && !empty($_POST["password"])){
      $username = $_POST["username"];
      $password = $_POST["password"];
      $users = login($username, $password);
    }
  }
  if($users == "none"){
    save_fail();
    ?>
    <div class="warning">
      Incorrect user name or password
    </div>
    <?php
  }else if($users !== ""){
    clear_fails();
    $_SESSION['permisions'] = $users;
    $_SESSION['user'] = $username;
    header("location: /");
  }


  ?>
  <div class="loginBox">
    <div clas="logo">
    </div>
       <?php
        if(!empty($_SESSION['user'])){
          print("You are already logged in.<br><br>"); 
          print("<a href='/login.php/logout'>Log Out?</a>"); 
        }else{
      ?>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
      User Name:<br><input type="text" name="username" required><br>
      Password:<br><input type="password" name="password" required><br>
      <input type="submit" name="login">
      <br><br><a href="/">Return to Site</a>
    </form>
	<?php
	}
	?>
  </div>
  <?php
}else{
  header('location: /login.php');
}
?>
