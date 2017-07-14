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
if("viewuser" == $page && "ADMIN" == $_SESSION['permisions']){
  $data = viewUsers();
  ?>
  <div class="users">
    <div class="row">
      <div class="headCell">Username</div>
      <div class="headCell">Password</div>
      <div class="headCell">Permisions</div>
    </div>
    <?php
    if(mysqli_num_rows($data)>0){
      while($row = mysqli_fetch_assoc($data)){
        echo "<div class='row'>
        <div class='cell'>" . $row['username'] . "</div>
        <div class='cell'>" . $row['password'] . "</div>
        <div class='cell'>" . $row['privilages'] . "</div>
        </div>";
      }
    }
    ?>

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
  if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!empty($_POST["newUsername"]) && !empty($_POST["newPassword"])){
      $username = $_POST["newUsername"];
      $password = $_POST["newPassword"];
      if("Basic" == $_POST['permis']){
        $privilages = "BASIC";
      }else{
        $privilages = "ADMIN";
      }
      $result = createAccount($username, $password, $privilages);
      if($result !== "none"){
        header("location: /");
      }
    }
  }

  ?>
  <div class="loginBox">
    <div clas="logo">
    </div>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
      User Name:<br><input type="text" name="newUsername" required><br>
      Password:<br><input type="text" name="newPassword" required><br>
      Account Type:<br><input type="radio" name="permis" value="Admin">Admin<br>
      <input type="radio" name="permis" value="Basic" checked>Basic<br>
      <input type="submit" name="newUser" value="Create Account">
    </form>
  </div>
  <?php
}else if($page == "deluser" && $_SESSION['permisions'] == "ADMIN"){

  if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!empty($_POST["delUsername"]) && !empty($_POST["delPassword"]) && $_POST['confirm'] == "on"){
      $user = $_POST['delUsername'];
      $pass = $_POST['delPassword'];
      $priv = $_POST['delPermis'];
      if($user !== $_SESSION['user']){
        delete_account($user, $pass, $priv);
      }
    }
  }

  ?>
  <div class="loginBox">
    <div clas="logo">
    </div>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
      User Name:<br><input type="text" name="delUsername" required><br>
      Password:<br><input type="text" name="delPassword" required><br>
      Account Type:<br><input type="radio" name="delPermis" value="Admin">Admin<br>
      <input type="radio" name="delPermis" value="Basic" checked>Basic<br>
      <input type="submit" name="delUser" value="Delete Account"><br>
      <input type="checkbox" name="confirm">I am sure about this<br>
    </form>
  </div>
  <?php
}else{
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
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
      User Name:<br><input type="text" name="username" required><br>
      Password:<br><input type="password" name="password" required><br>
      <input type="submit" name="login">
      <br><br><a href="/">Return to Site</a>
    </form>
  </div>
  <?php
}
?>
