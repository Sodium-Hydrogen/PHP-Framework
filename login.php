<?php
// This genarates the login page

require_once("resources/phpScripts/load.php");
$page = get_url();
if(check_attempts() >= $_SESSION['retry']){
	if(isset($_SESSION["user"])){
		clear_fails();
	}else{
		$_SESSION['error'] = '404';
		require('index.php');
		exit();
	}
}
if($page !== "logout"){
	load_logged_header();
}
queue_header("<link rel='stylesheet' href='/resources/backend.css'>");
queue_header("<script src='/resources/backend.js'></script>");
request_page_head($page == "home" ? "Login" : ucwords($page));

if("manage users" == $page && has_permissions()){
	$data = view_users();
	$changed_users = [];

	if($_SERVER["REQUEST_METHOD"] == "POST"){

		$actions = [];
		if(isset($_POST["bulk_action"])){
			if(isset($_POST["selected_users"])){
				if($_POST["bulk_action"] == "Delete Selected Users"){
					foreach($_POST["selected_users"] as $uid){
						if ($uid != $_SESSION["user"]["userid"] && isset($data[$uid])){
							queue_message("Deleted user '".$data[$uid]["username"]."'", "success");
							unset($data[$uid]);
							delete_account($uid);
							force_refresh($uid);
						}else if(empty($data[$uid])){
							queue_message("Cannot delete user with id $uid.<br>It doesn't exist.", "warning");
						}else{
							queue_message("Cannot delete current user", "warning");
						}
					}
				}
			}else{
				queue_message( "Unable to execute bulk action: <br>".$_POST["bulk_action"].".<br> No selected users.", "error");
			}
		}else if(isset($_POST["change_pass"])){
			foreach($_POST["change_pass"] as $uid => $_){
				$newPass = secure_key(25);
				$changed_users["new_pass"][$uid] = $newPass;
				admin_change_password($uid, $newPass);
				queue_message("Changed password of account with username '".$data[$uid]["username"]."'", "success");
			}
		}else if(isset($_POST["create_user"])){
			if(isset($_POST["new_user_username"]) && strlen($_POST["new_user_username"])> 0){
				$newPass = secure_key(25);
				$uid = create_account($_POST["new_user_username"], $newPass, $_POST["new_user_superuser"]);
				if($uid){
					$changed_users["new_pass"][$uid] = $newPass;
					if(isset($_POST["new_user_groups"])){
						modify_account_groups($uid, $_POST["new_user_groups"]);
					}
					$data = view_users();
					queue_message("Created user with username '".$_POST["new_user_username"]."'", "success");
				}else{
					queue_message("Unable to create user with username '".$_POST["new_user_username"]."'.<br>Username conflict.", "error");
				}
			}else{
				queue_message("You must set a username when creating a user.", "warning");
			}
		}else if(isset($_POST["create_new_group"])){
			if(isset($_POST["new_group_name"]) && $_POST["new_group_name"]){
				$desc = isset($_POST["new_group_description"])?$_POST["new_group_description"]:"";
				if(create_group($_POST["new_group_name"], $desc)){
					queue_message("Created new group with name '".$_POST["new_group_name"]."'", "success");
				}else{
					queue_message("Cannot create group with duplicate name '".$_POST["new_group_name"]."'", "error");
				}
			}else{
				queue_message("Please enter a group name when creating groups.", "warning");
			}
		}else if(isset($_POST["edit_groups"])){
			$rm_gprs = [];
			$grps = get_all_groups();
			if(isset($_POST["groups"])){
				$rm_gprs = array_diff(array_keys($grps), array_keys($_POST["groups"]));
				foreach($_POST["groups"] as $gid => $group){
					$desc = isset($group["description"])?$group["description"]:"";
					if(!boolval($grps[$gid]["protected"]) &&
							($grps[$gid]["name"] != $group["name"] || $grps[$gid]["description"] != $desc)
						){
						if(save_groups($gid, $group["name"], $desc)){
							if($grps[$gid]["name"] != $group["name"]){
								$users = view_users($gid, true);
								foreach($users as $uid => $user){ force_refresh($uid); }
								queue_message("Renamed group from '".$grps[$gid]["name"]."' to '".$group["name"]."'", "success");
							}
						}else{
							queue_message("Unable to rename group '".$grps[$gid]["name"]."' to duplicate name.", "error");
						}
					}
				}
			}else if(sizeof($grps) == 0){
				queue_message("No groups to save.", "warning");
			}else{
				$rm_gprs = array_keys($grps);
			}
			foreach($rm_gprs as $gid){
				if($grps[$gid]["protected"]){
					queue_message("Unable to delete group '".$grps[$gid]["name"]."'.<br>It is protected.", "warning");
				}else{
					$users = delete_group($gid);
					foreach($users as $uid){ force_refresh($uid); }
					queue_message("Removed group with name '".$grps[$gid]["name"]."'", "success");
				}
			}
			$data = view_users();
		}else if(isset($_POST["users"])){
			foreach($_POST["users"] as $uid => $values){
				if(empty($values["groups"])){
					$values["groups"] = [];
				}
				foreach($values as $setting => $value){
					if(gettype($value) != "array" && $uid != $_SESSION["user"]["userid"] && $data[$uid][$setting] != $value){
						if($setting == "local_account" && $value){
							$newPass = secure_key(25);
							$changed_users["new_pass"][$uid] = $newPass;
							admin_change_password($uid, $newPass);
							queue_message("Enabled account '".$data[$uid]["username"]."' to login", "success");
						}
						if(modify_account_setting($uid, $setting, $value)){
							if($setting == "username"){
								queue_message("Renamed user from '".$data[$uid][$setting]."' to '$value'", "success");
							}
							if($setting == "superuser"){
								$data = view_users();
							}else{
								$data[$uid][$setting] = $value;
							}
							force_refresh($uid);
							if($setting == "local_account" && !$value){
								queue_message("Disabled account '".$data[$uid]["username"]."' to login", "warning");
							}
						}else{
							queue_message("Error while setting '$setting' to '$value' for user '".$data[$uid]["username"]."'", "error");
						}
					}else if($setting == "groups" && !(
							count($data[$uid][$setting]) == count($value) && !array_diff(array_keys($data[$uid][$setting]), $value))
						){
							$changed = [
							"removed" => array_diff(array_keys($data[$uid][$setting]), $value),
							"added" => array_diff($value, array_keys($data[$uid][$setting]))
							];
							modify_account_groups($uid, $changed["removed"], false);
							modify_account_groups($uid, $changed["added"]);
							$data[$uid][$setting] = view_users($uid)[$setting];
							force_refresh($uid);
					}
				}
			}
		}
		// $http = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]?"https":"http";
		// header("location: $http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
		// exit;
	}
	if($_SERVER["HTTP_ACCEPT"] != "application/json"){
		send_messages();
		draw_manage_users_page($data, $changed_users);
	}else{
		print json_encode([
			"current_user" => $_SESSION["user"]["userid"],
			"messages" => $_SESSION["messages"],
			"users"=> $data,
			"changed_users" => $changed_users,
			"users_order" => array_keys($data),
			"groups" => get_all_groups()
		]);
		$_SESSION["messages"] = [];
	}


}else if("logout" == $page && isset($_SESSION['user'])){
	destroy_long_session();
	echo "<div class='loginBox'>";
	echo "Username: " . $_SESSION['user']["username"] . "<br>" . "Account Type: " . ($_SESSION['user']["local_account"]?"Local Account":"External Account");
	if(session_destroy()){
		echo "<br><br>logged Out<br>";
	}
	echo "<a href=''>Log back in</a><br><br>";
	echo "<a href='/'>Goto to main page</a></div>";
}else if($page == "account" && has_permissions()){
	if($_SERVER["REQUEST_METHOD"] == "POST"){
		if($_POST['action'] == "Change Password" && !empty($_POST["oldPassword"]) && !empty($_POST["newPassword"])){
			$oldPassword = $_POST["oldPassword"];
			$newPassword = $_POST["newPassword"];
			if($_POST['newPassword'] == $_POST['repeat'] && strlen($_POST['newPassword']) >= 8){
				$result = change_password($_SESSION["user"]["userid"], $oldPassword, $newPassword);
				$message = "Password was incorrect.";
			}else if(strlen($_POST['newPassword']) < 8){
				$result = false;
				$message = "Password is too short.";
			}else{
				$result = false;
				$message = "Passwords do not match.";
			}
			if($result == false){
				queue_message("Unable to change password. <br>$message", "warning");
			}else{
				queue_message("Password was changed successfully!", "success");
			}
		}
	}
	send_messages();
	echo "<div class='loginBox no-border'><div class='logo'></div>";
	if ($_SESSION["user"]["local_account"]){
		?>
		<div class="row"><h2>Change Password</h2></div><hr>
		<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
			<p><label for="oldpass">Old Password:</label><br><input id="oldpass" type="password" name="oldPassword" required></p>
			<p><label for="newpass">New Password:</label><br><input id="newpass" type="password" name="newPassword" required></p>
			<p><label for="reppass">Repeat New Password:</label><br><input id="reppass" type="password" name="repeat" required></p>
			<p><input type="submit" name="action" value="Change Password"></p>
		</form>
		<!-- <hr class="border"> -->
		<hr>
		<?php
	}else{
		?>
			<div class="row"><h2>External Account</h2></div>
			<p>Your account is not managed by this framework and is most likely a 3rd party account.
				If you would like to change account info you can do it from the external service.
			</p>
		<?php
	}
	$sessions = get_all_extended();
	if(count($sessions) > 0){
		?> <br><div class='row'><h2>Other Sessions</h2></div>
		<div id='all_sessions' class='table-wrapper'><div class='thead'>
			<div class='tr'><div class='th'>Last IP</div><div class='th'>Days Left</div><div class='th'>Last Login</div></div></div>
		<div class='tbody'> <?php
		foreach($sessions as $index => $session){
			echo "<div class='tr'><div class='td td-center'>".$session['ip_address']."</div>";
			echo "<div class='td td-center'>";
			echo round(($session["expiration"]-$_SERVER['REQUEST_TIME'])/(60*60*24))."</div>";
			echo "<div class='td td-center raw-unix-time'>".$session["lastaccess"]."</div></div>";
		}
		?>
		</div></div><div class="row">
			<button type='button' id="logout_everywhere">Logout everywhere</button>
		</div>
		<?php
	}
	echo "</div>";
}else if($page == "purge" && isset($_SESSION['user'])){
	logout_all_extended($_SESSION['user']["userid"]);
	header("location: ./logout");
}else if($page == "home"){
	$users = "";
	if($_SERVER["REQUEST_METHOD"] == "POST"){
		if(isset($_POST["username"]) && isset($_POST["password"])){
			$username = $_POST["username"];
			$password = $_POST["password"];
			$user_id = login($username, $password);
		}
		if($user_id === null){
			$username = $_POST["username"];
			create_log("Auth Fail", "Login attempt failed for user: '$username' from ip: ".$_SERVER["REMOTE_ADDR"]);
			save_fail();
			queue_message("Incorrect user name or password", "error");
		}else{
			clear_fails();
			load_user_info($user_id);
			if($_SESSION["user"]["superuser"]){
				create_log("Superuser Login", "Super User: '$username' successfully logged in from ip: ".$_SERVER["REMOTE_ADDR"]);
			}
			create_long_session();
			if(isset($_GET['url'])){
				header("location: ".urldecode($_GET['url']));
			}else{
				header("location: /");
			}
		}
	}
	send_messages();
	?>
	<div class="loginBox no-border"><div class="logo" style="text-align: center;"></div>
	<?php
	if(!empty($_SESSION['user'])){
		print("You are already logged in.<br><br>");
		print("<a href='/login.php/logout'>Log Out?</a>");
	}else{
		?>
		<form method="post">
			<p> <label for="username">User Name: </label>
				<br> <input id="username" type="text" name="username" required> </p>
			<p> <label for="password"> Password: </label>
				<br><input id="password" type="password" name="password" required></p>
			<p><input type="submit" name="login"></p>
		</form>
		<div class='row no-border'>
		<a href="/">Return to site</a>
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

function draw_manage_users_page($data, $changed_users){
	?>
	<form class="content user-manager no-enter" method='post' spellcheck='false' autocomplete='false' id="user-manager" >
	<div class='table-wrapper' style="width: 100%;"><div class='thead'><div class='tr'>
		<div class='th'><input type="checkbox" id="select_all_users"></div>
		<div class='th'>Username</div> <div class='th'>Local Account
			<div class="help-msg hidden">Local accounts are able to login via /login.php.  <hr> If this is not checked the account will be treated as external and
				won't be able to login. Marking an account as external is a good way to lock the account.
				<hr> If the account is external and will be internal a new password is generated and set to that account.  </div></div>
		<div class='th'>Super User</div> <div class='th'>Groups</div> <div class='th'></div>
	</div></div><div class='tbody'>
	</div></div> <div class="row">
		<div class="section"><select id="group_to_bulk_add">
				<option selected disabled value='' >------</option>
				 </select> <button type='button' id="bulk_add_group">Add Selected to Group</button>
				<button type='button' class='show_popup' id="edit_groups">Edit Groups</button>
		</div> <div class="section">
			<input class="negative" type="submit" id="bulk_delete_accounts" name="bulk_action" value="Delete Selected Users">
			<button type='button' class='show_popup' id="create_new_user">Create New User</button> <input type="submit" class="positive"> </div>
	</div>
	<div class="hidden hidden_popup" id="edit_groups_inputs">
		<div class="loginBox widebox"><div class="clear_message"></div>
		<h2>Groups</h2> <hr><div id="group_edit_list" class="invert-rows">
		</div><hr> <p class='section'><input type="text" name="new_group_name" style="margin-right: 10px;max-width: 150px;" placeholder="Group Name">
			<input type="text" name="new_group_description" style="margin-right: 10px" placeholder="Description">
			<input type="submit" id='create_new_group_submit' class='positive' name="create_new_group" value="Create Group"></p>
		<input type="submit" name="edit_groups" value="Save" id="edit_groups_submit">
		</div> </div>
	<div class="hidden hidden_popup" id="create_new_user_inputs">
		<div class="loginBox"> <div class="clear_message"></div>
			<p> <label for="new_user_username">User Name: </label>
				<input id="new_user_username" type="text" name="new_user_username"> </p>
			<div> Groups: <ul class="groups no-bullets"></ul> </div>
			<p> <label for="new_user_superuser">Super User: </label>
				<input type="hidden" name="new_user_superuser" value=0>
				<input id="new_user_superuser" type="checkbox" name="new_user_superuser" value=1></p>
			<input type="submit" name="create_user" value="Create New User" id="create_new_user_submit"> </div>
	</div> </form> <?php
}

?>