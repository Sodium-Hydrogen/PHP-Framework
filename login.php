<?php
// This genarates the login page

require_once("resources/phpScripts/load.php");
$page = get_url();
if(check_attemps() >= $_SESSION['retry']){
	$_GET['error'] = '404';
	require('index.php');
	exit();
}
if(!empty($_SESSION['user']) && $page !== "logout"){
	load_logged_header();
}
queue_header("<link rel='stylesheet' href='/resources/settings.css'>");
queue_header("<script src='/resources/settings.js'></script>");
request_page_head($page == "home" ? "Login" : ucwords($page));

$min_permis = 90;
if(isset($_SESSION["headerLink"])){
  $target = "/login.php/".get_url();
  foreach($_SESSION["headerLink"] as $link){
    if($link['url'] == $target){
      $min_permis = $link['min_permission'];
    }
  }
}

if("manageusers" == $page && $min_permis <= $_SESSION['permissions']){
	$displayNewPass = false;
	if($_SERVER["REQUEST_METHOD"] == "POST"){
		if($_POST["function"] == "Delete User"){
			$user = $_POST['username'];
			$priv = $_POST['permis'];
			if($user !== $_SESSION['user']){
				delete_account($user, $priv);
			}
			echo "<div class='success'>Deleted user: $user</div>";
		}else if($_POST['function'] == "Reset Password" && strcasecmp($_POST['username'], $_SESSION['user']) != 0 ){
			$displayNewPass = true;
			$newPass = secure_key(25);
			$user = $_POST['username'];
			admin_change_password($user, $newPass);
		}else if($_POST['function'] == "Add New User"){
			$users = view_users();
			$alreadyExists = false;
			foreach($users as $userinfo){
				if(strcasecmp($userinfo['username'], $_POST['username']) == 0){
					$alreadyExists = true;
				}
			}
			if($alreadyExists === false){
				$user = $_POST['username'];
				$displayNewPass = true;
				$newPass = secure_key(25);
				create_account($user, $newPass, (int)$_POST["account_permission"]);
				echo "<div class='success'>Created account: ";
				echo $_POST['username'];
				echo " </div>";
			}else{
				echo "<div class='warning'>Unable to create account. <br>User \"";
				echo $_POST['username'];
				echo "\" may already exist.</div>";
			}
		}
	}

	$data = view_users();
	?>
	<div class="users"><div class="row specialRow">
		<div class="title"><div class="headCell colOne">Username</div></div>
		<div class="information"><div class="headCell colTwo">Permissions<div id="help-msg">
			Please keep this number even so the framework knows if the user is stored in the database.
		</div></div>
		<div class="headCell colThree empty"></div><div class="headCell colFour empty"></div>
	</div></div>
	<hr class="spacer">
	<?php
	$oddRow = false;
	foreach($data as $userinfo){
		echo "<form method='post' spellcheck='false' autocomplete='false'action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "'
		onsubmit=\"return confirm('Are you sure you want to ' + choice + '?');\">";
		echo "<div class='row";
		if($displayNewPass && $userinfo['username'] == $user){
			echo " new-user";
		}
		if($oddRow){
			echo " odd-row";
		}
		$oddRow = !$oddRow;
		echo "'><div class='title'>";
		echo "<div class='cell colOne'>" . $userinfo['username'] . "</div></div>";
		echo "<div class='information'><div class='cell colTwo'>" . $userinfo['privileges'] . "</div>";
		echo "<input type='hidden' name='username' value='" . $userinfo['username'] . "'>";
		echo "<input type='hidden' name='permis' value='" . $userinfo['privileges'] . "'>";
		echo "<div class='cell colThree'>";
		if(strcasecmp($userinfo['username'], $_SESSION['user']) != 0 && !($displayNewPass && $user == $userinfo['username'])){
			echo "<input type='submit' name='function' value='Reset Password' onclick=\"";
			echo "choice = 'reset the password for user: " . $userinfo['username'] . "'\">";
		}else if(isset($user) && $userinfo['username'] == $user){
			echo "<input type='text' value='$newPass' class='newPassword'>";
		}
		echo "</div><div class='cell colFour'>";
		if(strcasecmp($userinfo['username'], $_SESSION['user']) != 0){
			echo "<input type='submit' name='function' value='Delete User' onclick=\"";
			echo "choice = 'delete user: " . $userinfo['username'] . "'\">";
		}
		echo "</div></div></div></form>";
	}
	echo "<hr class='spacer'>";
	echo "<form method='post' spellcheck='false' autocomplete='off' action=" . htmlspecialchars($_SERVER["PHP_SELF"]) . ">";
	?>
	<div class='row specialRow'>
		<div class='title'><div class='cell colOne'>
			<input type="text" name="username" placeholder="New Username" required>
		</div></div>
		<div class='information'><div class='cell colTwo'>
			<input name="account_permission" type="number" value="10" min="0" max="100" step="2">
		</div>
		<div class='cell colThree'></div>
		<div class='cell colFour'><input type='submit' name='function' value='Add New User'></div>
	</div></div></form></div>
	<?php

}else if("logout" == $page && isset($_SESSION['user'])){
	destroy_long_session();
	echo "<div class='loginBox'>";
	echo "Username: " . $_SESSION['user'] . "<br>" . "Privilege Level: " . $_SESSION['permissions'];
	if(session_destroy()){
		echo "<br><br>logged Out<br>";
	}
	echo "<a href='/'>Return to Main Page</a></div>";
}else if($page == "account" && $min_permis <= $_SESSION['permissions']){
	if($_SERVER["REQUEST_METHOD"] == "POST"){
		if($_POST['action'] == "Change Password" && !empty($_POST["oldPassword"]) && !empty($_POST["newPassword"])){
			$username = $_SESSION["user"];
			$oldPassword = $_POST["oldPassword"];
			$newPassword = $_POST["newPassword"];
			if($_POST['newPassword'] == $_POST['repeat'] && strlen($_POST['newPassword']) >= 8){
				$result = change_password($username, $oldPassword, $newPassword);
			}else if(strlen($_POST['newPassword']) < 8){
				$result = false;
				$message = "Password is too short.";
			}else{
				$result = false;
				$message = "Passwords do not match.";
			}
			if($result == false){
				echo "<div class='warning'>Unable to change password. <br>$message</div>";
			}else{
				echo "<div class='success'>Password was changed successfully!</div>";
			}
		}
	}
	echo "<div class='loginBox no-border'><div class='logo'></div>";
	if ($_SESSION["permissions"]%2 == 0){
		?>
		<div class="row"><h2>Change Password</h2></div><hr>
		<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
			Old Password:<br><input type="password" name="oldPassword" required><br>
			New Password:<br><input type="password" name="newPassword" required><br>
			Repeat New Password:<br><input type="password" name="repeat" required><br>
			<input type="submit" name="changePass" value="Change Password">
		</form>
		<!-- <hr class="border"> -->
		<hr>
		<?php
	}else{
		?>
			<div class="row"><h2>External Account</h2></div>
			<p>Your account is not saved in the database and is most likely a 3rd party account.
				If you would like to change account info you can do it from the external service.
			</p>
		<?php
	}
	$sessions = get_all_extended();
	if(count($sessions) > 0){
		echo "<br><div class='row'><h2>Other Sessions</h2></div><hr>";
		echo "<div class='row specialRow'><div class='title headerCell'>Last IP</div>";
		echo "<div class='information headerCell'>Days Left</div></div><hr>";
		foreach($sessions as $index => $session){
			echo "<div class='row";
			echo "'><div class='title'>".$session['ip_address']."</div>";
			echo "<div class='information'>";
			echo round(($session["expiration"]-$_SERVER['REQUEST_TIME'])/(60*60*24))."</div>";
			echo "</div>";
		}
		// echo "<a href='/login.php/purge'>Logout on every device</a><hr><br>";
		echo "<button type='button' onclick='if(confirm(\"This will logout you out of every device\\n\\nContinue?\")){
			window.location = \"/login.php/purge\";}'>Logout everywhere</button";
	}
	echo "</div>";
}else if($page == "purge" && isset($_SESSION['user'])){
	logout_all_extended($_SESSION['user'], $_SESSION['permissions']);
	header("location: ./logout");
}else if($page == "home"){
	$users = "";
	if($_SERVER["REQUEST_METHOD"] == "POST"){
		if(isset($_POST["username"]) && isset($_POST["password"])){
			$username = $_POST["username"];
			$password = $_POST["password"];
			$permissions = login($username, $password);
		}
		if($permissions == "none"){
			save_fail();
			echo "<div class='warning'>Incorrect user name or password</div>";
		}else if($permissions !== ""){
			clear_fails();
			$_SESSION['permissions'] = $permissions;
			$_SESSION['user'] = $username;
			create_long_session();
			if(isset($_GET['url'])){
				header("location: ".urldecode($_GET['url']));
			}else{
				header("location: /");
			}
		}
	}
	?>
	<div class="loginBox no-border"><div class="logo"></div>
	<?php
	if(!empty($_SESSION['user'])){
		print("You are already logged in.<br><br>");
		print("<a href='/login.php/logout'>Log Out?</a>");
	}else{
		?>
		<form method="post">
			User Name:<br><input type="text" name="username" required><br>
			Password:<br><input type="password" name="password" required><br>
			<input type="submit" name="login">
		</form>
		<br>
		<div class='row no-border'>
		<a href="/">Return to Site</a>
		<?php
		if(!empty($_SESSION['alt_login_text'])){
			echo "<a href='".$_SESSION['alt_login_url']."'>".$_SESSION['alt_login_text']."</a>";
		}
		echo "</div>";
	}
	?>
</div>
<?php
}else{
	header('location: /login.php');
}
?>
