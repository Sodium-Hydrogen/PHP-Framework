<?php
/*
get_url();
	It will return the url following the php file name in the requested url
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
	It will return the privileges of the user if successful
view_users();
	It will return an array of all users for the website
create_account($username, $password, $privileges);
	Creates a new user with the specified username, password, and account privileges
delete_account($username, $privileges);
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


function get_url(){
	if(isset($_SERVER['PATH_INFO'])){
		$link = $_SERVER['PATH_INFO'];
		$link = substr($link, strpos($link, "/")+1);
		if($link === ""){
			$link = "home";
		}
		return strtolower(str_replace("%20", " ", $link));
	}else{
		return "home";
	}
}
function request_page_head($second = null){
	if(empty($second)){
		$actual_link = get_url();
		if(strpos($actual_link, '/') > 0){
			$actual_link = substr($actual_link, 0, strpos($actual_link, '/'));
		}
		$second = $actual_link;
		foreach($_SESSION["pages"] as $page){
			if(strcasecmp($actual_link, $page['name']) == 0){
				$second = fetch_content($page['name'])['title'];
				break;
			}
		}
	}
	load_page_head($second);
}
function queue_header($string){
	if(empty($GLOBALS['header_info'])){
		$GLOBALS['header_info'] = "";
	}
	$GLOBALS['header_info'] .= ("\t\t" . $string . "\n");
}
function queue_body($string){
	if(empty($GLOBALS['body_info'])){
		$GLOBALS['body_info'] = "";
	}
	$GLOBALS['body_info'] .= $string;
}
function load_page_head($second = NULL){
	if(isset($GLOBALS['page_head_loaded'])){
		return;
	}
	if(!empty($second)){
		$second = " - " . $second;
	}
	$GLOBALS['page_head_loaded'] = true;
	?>
	<!DOCTYPE html>
	<html lang="en-us">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="theme-color" content = "#222" />
		<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> -->
		<link rel='icon' href='/resources/theme/resources/favicon.png'>
		<title><?php echo $_SESSION['site'] . $second; ?></title>
		<?php echo isset($GLOBALS['header_info'])?$GLOBALS['header_info']:""; ?>
	</head>
	<body>
	<?php echo isset($GLOBALS['body_info'])?$GLOBALS['body_info']:"";
}
function load_logged_header(){
	if(isset($_SESSION['user'])){
		$user = $_SESSION['user'];
		queue_header("<script src='/resources/header.js'></script>");
		queue_header("<link rel='stylesheet' href='/resources/userHeaderStyle.css'>");
		queue_body("<div class='loginHeader' id='loginHeader'>
			<div class='headerMenu' id='login_menu'><div class='username'>
		");
		queue_body('Welcome, ' . $user . "</div><div class='links'>
			<button class='dropBtn' onclick='header_dropdown()'>Menu</button>
			<div class='dropdown-content' id='dropdownMenu'>
			<a href='/'>Home</a><a class='odd-row' href='/login.php/logout'>Logout</a>
		");
		if(count($_SESSION['headerLink']) > 0){
			foreach($_SESSION['headerLink'] as $index => $link){
				if($link["min_permission"] <= $_SESSION['permissions']){
					queue_body("<a");
					if($index%2 == 1){
						queue_body(" class='odd-row'");
					}
					queue_body(" href=".$link['url'].">".$link['name']."</a>");
				}
			}
		}
		queue_body("</div></div></div></div>");
	}
}
function fetch_content($page, $post=null){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$page = $database->real_escape_string($page);
	$res = Array();
	if($post){
		$post = $database->real_escape_string($post);
		$command = "SELECT * FROM posts WHERE name = '$post' and parent = '$page'";
		$res = $database->query($command)->fetch_assoc();
	}else{
		$command = "SELECT * FROM pages WHERE name = '$page'";
		$res = $database->query($command)->fetch_assoc();
		$res['posts'] = Array();
		$command = "SELECT name, title, picture FROM posts WHERE parent='$page' ORDER BY position";
		$posts = $database->query($command);
		while($post = $posts->fetch_assoc()){
			// $name = $post['name'];
			// unset($post['name']);
			array_push($res['posts'], $post);
		}
	}

	$database->close();
	return $res;
}
function get_all_footers($everything=true){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$command = "SELECT * FROM footer ORDER BY position";
	$res = $database->query($command);
	$footer = [];
	if($res){
		while($row = $res->fetch_assoc()){
			if($everything){
				$parent = $database->real_escape_string($row['name']);
				$command = "SELECT * FROM footerlinks WHERE parent='$parent' ORDER BY position";
				$subres = $database->query($command);
				$row['links'] = Array();
				if($subres){
					while($link = $subres->fetch_assoc()){
						array_push($row['links'], $link);
					}
				}
			}
			array_push($footer, $row);
		}
	}

	$database->close();
	return $footer;
}
function fetch_footer($footer, $link=null){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$footer = $database->real_escape_string($footer);
	$result = [];
	if($link){
		$link = $database->real_escape_string($link);
		$command = "SELECT * FROM footerlinks WHERE name='$link' AND parent='$footer'";
		$res = $database->query($command);
		if($res){
			$result = $res->fetch_assoc();
		}
	}else{
		$command = "SELECT * FROM footer WHERE name='$footer'";
		$res = $database->query($command);
		if($res){
			$result = $res->fetch_assoc();
			$result["links"] = Array();
			$command = "SELECT * FROM footerlinks WHERE parent='$footer' ORDER BY position";
			$res = $database->query($command);
			if($res){
				// $res = $res->fetch_assoc();
				while($link = $res->fetch_assoc()){
					array_push($result["links"], $link);
				}
			}
		}
	}
	$database->close();

	return $result;
}
function login($username, $password){
	$username = strtolower(trim($username));
	$password = trim($password);

	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$username = $database->real_escape_string($username);
	$command = "SELECT * FROM accounts WHERE username ='$username'";

	$salt = $database->query($command)->fetch_assoc()['salt'];
	$password = hash('sha256', $password . $salt);

	$command = "SELECT * FROM accounts WHERE username = '$username' and password ='$password'";
	$information = $database->query($command)->fetch_assoc();
	$permissions = -1;

	if(strtolower($information['username']) == $username && $information['password'] == $password){
		$permissions = $information['privileges'];
	}


	$database->close();
	return $permissions;
}
function view_users(){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$command = "SELECT username, privileges FROM accounts order by privileges DESC, username";

	$output = $database->query($command);
	$users = array();
	while($row = $output->fetch_assoc()){
		array_push($users, $row);
	}
	$database->close();
	return $users;

}
function create_account($username, $password, $privileges){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$username = $database->real_escape_string($username);

	$command = "SELECT username FROM accounts WHERE username = '$username'";

	$check = $database->query($command);
	$result = "none";
	if(empty($check->fetch_assoc())){
		$salt = (string)bin2hex(openssl_random_pseudo_bytes(8));
		$password = hash('sha256', ($password . $salt));

		$command = "INSERT INTO accounts (username, password, salt, privileges)
		VALUES ('$username', '$password', '$salt', $privileges)";
		$database->query($command);
		$result = "success";
	}
	$database->close();

	return $result;

}
function admin_change_password($username, $newPassword){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$username = $database->real_escape_string($username);
	$newPassword = $database->real_escape_string($newPassword);

	$command = "SELECT username FROM accounts WHERE username = '$username'";

	$check = $database->query($command);
	$result = "none";

	if(!empty($check->fetch_assoc())){
		$salt = (string)bin2hex(openssl_random_pseudo_bytes(8));
		$newPassword = hash('sha256', ($newPassword . $salt));

		$command = "UPDATE accounts SET password='$newPassword', salt='$salt'	WHERE username='$username'";

		$database->query($command);
		$result = "success";
	}

	$database->close();

}
function change_password($username, $oldPassword, $newPassword){
	$database = new mysqli("localhost", $sql_user_name, $sql_password, $sql_database);

	$username = $database->real_escape_string($username);

	$command = "SELECT password, salt FROM accounts WHERE username = '$username'";

	$output = $database->query($command);
	$result = 'none';
	$data = $output->fetch_assoc();
	$salt = $data['salt'];
	$oldPassword = hash('sha256', ($oldPassword . $salt));
	$password = $data['password'];
	if($oldPassword == $password){
		admin_change_password($username, $newPassword);
		$result = "success";
	}
	$database->close();
	return $result;
}
function delete_account($username, $privileges){
	$database = new mysqli("localhost", $sql_user_name, $sql_password, $sql_database);

	$username = $database->real_escape_string($username);
	$privileges = $database->real_escape_string($privileges);

	$command = "DELETE FROM accounts WHERE username = '$username' and privileges = $privileges";
	$database->query($command);

	$command = "DELETE FROM extendedsession WHERE username = '$username' and permissions = $privileges";
	$database->query($command);

	$database->close();

}
function save_fail(){
	$timeout = $_SESSION['ban_time'] * 3600;
	$cur = time();
	$ip = $_SERVER['REMOTE_ADDR'];
	$updated = false;
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$command = "SELECT * FROM blacklist";

	$output = $database->query($command);
	if(mysqli_num_rows($output)>0){
		while($row = $output->fetch_assoc()){
			if($cur > $row['untilFree']){
				$tmp = $row['ipaddress'];
				$command = "DELETE FROM blacklist WHERE ipaddress = '$tmp'";
				$database->query($command);
			}
			if($ip == $row['ipaddress']){
				$tmp = $row['attemps'] + 1;
				$tmpT = $cur+$timeout;
				$command = "UPDATE blacklist SET attemps = $tmp, untilFree = $tmpT WHERE ipaddress = '$ip'";
				$database->query($command);
				$updated = true;
			}
		}
	}
	if(!$updated){
		$tmpT = $cur+$timeout;
		$command = "INSERT INTO blacklist (ipaddress, attemps, untilFree)	VALUES ('$ip', 1, $tmpT)";
		$database->query($command);
	}

	$database->close();

}

function check_attemps(){
	$timeout = $_SESSION['ban_time'] * 3600;
	$cur = time();
	$ip = $_SERVER['REMOTE_ADDR'];

	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$command = "SELECT * FROM blacklist WHERE ipaddress = '$ip'";

	$output = $database->query($command);

	$output = $output->fetch_assoc();

	if($output['untilFree'] > $cur){
		return $output['attemps'];
	}else{
		return 0;
	}
	$database->close();

}
function clear_fails(){
	$ip = $_SERVER['REMOTE_ADDR'];

	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$command = "DELETE FROM blacklist WHERE ipaddress = '$ip'";

	$database->query($command);

	$database->close();

}
function setup_database(){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$command = "SELECT * FROM accounts";
	$res = $database->query($command);

	// Since there should always be an account check to see if the database has been
	// created and user entered. If it has not it creates all the tables and enters
	// some default data
	if($res === false){
		$command = "CREATE TABLE refresh (target VARCHAR(64) PRIMARY KEY NOT NULL, older_than BIGINT NOT NULL)";
		$res = $database->query($command);

		$command = "CREATE TABLE accounts (userid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			username TEXT NOT NULL,	password VARCHAR(64) NOT NULL, salt VARCHAR(16) NOT NULL,
			privileges INT NOT NULL)";
		$res = $database->query($command);

		$command = "CREATE TABLE blacklist (ipaddress VARCHAR(40) NOT NULL PRIMARY KEY,
			attemps INT NOT NULL, untilFree BIGINT NOT NULL)";
		$res = $res && $database->query($command);

		$command = "CREATE TABLE footer (name VARCHAR(64) NOT NULL PRIMARY KEY, content TEXT, position INT)";
		$res = $res && $database->query($command);

		$command = "CREATE TABLE footerlinks (name VARCHAR(64) NOT NULL, url TEXT, icon TEXT, type TEXT,
			parent VARCHAR(64) NOT NULL, position INT, FOREIGN KEY (parent) REFERENCES footer(name) )";
		$res = $res && $database->query($command);

		$command = "CREATE TABLE pages (name VARCHAR(64) NOT NULL PRIMARY KEY, title TEXT, direction TEXT NOT NULL,
			position INT, content LONGTEXT, protected BOOL)";
		$res = $res && $database->query($command);

		$command = "CREATE TABLE posts (name VARCHAR(64) NOT NULL, title TEXT,
			picture TEXT, content LONGTEXT, parent VARCHAR(64) NOT NULL, position INT,
			FOREIGN KEY (parent) REFERENCES pages(name) )";
		$res = $res && $database->query($command);

		$command = "CREATE TABLE configs (setting VARCHAR(64) NOT NULL PRIMARY KEY, value TEXT NOT NULL,
			type ENUM('INT', 'BOOL', 'STRING'), description TEXT, protected BOOL)";
		$res = $res && $database->query($command);

		$command = "CREATE TABLE loginlinks ( name VARCHAR(64) NOT NULL PRIMARY KEY, url TEXT NOT NULL,
			min_permission INT NOT NULL, protected BOOL)";
		$res = $res && $database->query($command);

		$command = "CREATE TABLE extendedsession (uid VARCHAR(64) NOT NULL PRIMARY KEY, username TEXT NOT NULL,
			permissions INT NOT NULL, expiration BIGINT NOT NULL, ip_address VARCHAR(40) )";
		$res = $res && $database->query($command);

		$command = "INSERT INTO refresh (target, older_than) VALUES ('ALL', 0)";
		$res = $res && $database->query($command);

		$command = "INSERT INTO pages (name, title, direction, position, protected) VALUES ('Home', 'Home', 'column', 0, true)";
		$res = $res && $database->query($command);

		$command = "INSERT INTO posts (name, title, content, parent, position)
			VALUES ('helloworld', 'Hello World', '42', 'home', 0)";
		$res = $res && $database->query($command);

		$command = "INSERT INTO loginlinks (url, name, min_permission, protected) VALUES
			('/login.php/manageUsers', 'Manage Users', 90, true), ('/config.php/settings', 'Settings', 80, true),
			('/config.php/pages', 'Update Content', 70, true), ('/login.php/account', 'Account', 5, true),
			('/config.php/loginlinks', 'Header Links', 90, true)";
		$res = $res && $database->query($command);

		$command = "INSERT INTO configs (setting, value, type, description, protected)	VALUES
			('retry', '4', 'INT', 'Number of allowed failed login attemps.', true),
			('site', 'TITLE', 'STRING', 'The name of this site.', true),
			('sub_title', 'SUB TITLE', 'STRING', 'A two word title to be loaded on the pages.', true),
			('ban_time', '48', 'INT', 'Ban time to prevent login in hours.', true),
			('debug', 'false', 'BOOL', 'Activates php errors to be sent in html responses as well as the log files.', true),
			('setup', 'true', 'BOOL', 'Put the site in setup mode which hides the homepage unless a user is logged in.', true),
			('extended_timeout', '30', 'INT', 'The count in days of how long a user stays logged in.', true),
			('alt_login_text', '', 'STRING', 'If this is set it will display a link to an alternate login script on the main login page.', true),
			('alt_login_url', '', 'STRING', 'The url to an alternate login script.', true),
			('force_sync', 'true', 'BOOL', 'Force all sessions to recheck every request if they need to refresh variables.', true)";
		$res = $res && $database->query($command);

		if($_SESSION['debug']){
			if($res !== true){
				echo "<br>table creation commands failed<br>";
			}else{
				echo "<br>table creation commands successful<br>";
			}
		}
		return $res;
	}
	$database->close();
}

function get_error_message($code){
	$messages = array(
		"300" => "Multiple Choices",
		"301" => "Moved Permanently",
		"302" => "Found",
		"303" => "See Other",
		"304" => "Not Modified",
		"306" => "Switch Proxy",
		"307" => "Temporary Redirect",
		"308" => "Resume Incomplete",
		"400" => "Bad Request",
		"401" => "Unauthorized",
		"402" => "Payment Required",
		"403" => "Forbidden",
		"404" => "Not Found",
		"405" => "Method Not Allowed",
		"406" => "Not Acceptable",
		"407" => "Proxy Authentication Required",
		"408" => "Request Timeout",
		"409" => "Conflict",
		"410" => "Gone",
		"411" => "Length Required",
		"412" => "Precondition Failed",
		"413" => "Request Entity Too Large",
		"414" => "Request-URI Too Long",
		"415" => "Unsupported Media Type",
		"416" => "Requested Range Not Satisfiable",
		"417" => "Expectation Failed",
		"500" => "Internal Server Error",
		"501" => "Not Implemented",
		"502" => "Bad Gateway",
		"503" => "Service Unavailable",
		"504" => "Gateway Timeout",
		"505" => "HTTP Version Not Supported",
		"511" => "Network Authentication Required"
	);

	if(isset($messages[$code])){
		return $messages[$code];
	}else{
		return "Unknown Status Code";
	}

}

function load_variables_from_database(){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$command = "SELECT * FROM configs";

	$_SESSION['session_start'] = time();

	$res = $database->query($command);
	if($res !== false){
		while($row = $res->fetch_assoc()){
			if($row['type'] == "BOOL"){
				$val = (bool)($row['value'] == 'true');
			}else if($row['type'] == "INT"){
				$val = (int)$row['value'];
			}else{
				$val = (string)$row['value'];
			}
			$_SESSION[$row['setting']] = $val;
		}
		$command = "SELECT name FROM pages ORDER BY position, name";
		$res = $database->query($command);
		$_SESSION['pages'] = Array();
		while($row = $res->fetch_assoc()){
			$posts = Array();
			$command = "SELECT name FROM posts WHERE parent='" . $row['name'] . "' ORDER BY position";
			$post_res = $database->query($command);
			while($post = $post_res->fetch_assoc()){
				array_push($posts, $post['name']);
			}
			array_push($_SESSION['pages'], Array(
				'name' => $row['name'],
				'posts' => $posts
			));
		}
		$command = "SELECT name, url, min_permission, protected FROM loginlinks ORDER BY protected DESC, name";
		$res = $database->query($command);
		$_SESSION['headerLink'] = Array();
		while($row = $res->fetch_assoc()){
			array_push($_SESSION['headerLink'], $row);
		}

	}else{
		$_SESSION['setup'] = true;
	}
	$database->close();

}
function secure_key($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'){
	$pieces = [];
	$max = strlen($keyspace) - 1;
	for ($i = 0; $i < $length; ++$i) {
		$pieces []= $keyspace[random_int(0, $max)];
	}
	$key = implode('', $pieces);
	return $key;
}
function update_configs($config_dataset){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	foreach($config_dataset as $key => $value){
	 	$value = $database->real_escape_string($value);
	 	$key = $database->real_escape_string($key);
	 	$command = "UPDATE configs SET value = '$value' WHERE setting = '$key'";
	 	$database->query($command);
	}
	$database->close();
	force_refresh();
}
function delete_config($config_name){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$setting = $database->real_escape_string($config_name);
	$command = "DELETE FROM configs WHERE setting = '$setting' AND (protected != 1 OR protected IS NULL)";
	$database->query($command);
	$database->close();
	force_refresh();
}
function create_config($setting, $value, $type, $desc){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
  $setting = $database->real_escape_string($setting);
  $value = $database->real_escape_string($value);
  $type = $database->real_escape_string($type);
  $desc = $database->real_escape_string($desc);
  $command = "INSERT INTO configs (setting, value, type, description) VALUES ('$setting', '$value', '$type', '$desc')";
  $database->query($command);
	$database->close();
	force_refresh();
}
function get_configs(){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
  $command = "SELECT * FROM configs ORDER BY protected DESC, setting";
	$res = $database->query($command);
	$configs = array();
	while($row = $res->fetch_assoc()){
		$setting = $row["setting"];
		unset($row["setting"]);
		$configs[$setting] = $row;
	}
	$database->close();
	return $configs;
}
function clean_long_session_table($database){
	$command = "DELETE FROM extendedsession WHERE expiration <= " . time();
	$database->query($command);
}
function create_long_session(){
	$timeout = $_SESSION['extended_timeout'];
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$key = "";
	do{
		$key = secure_key(64);
		$command = "SELECT key FROM extendedsession WHERE key = $key";
	}while ($database->query($command));
	$expire = ($timeout * 24 * 3600) + time();
	$username = $database->real_escape_string($_SESSION['user']);
	$permissions = $database->real_escape_string($_SESSION['permissions']);
	$command = "INSERT INTO extendedsession (uid, username, permissions, expiration, ip_address) VALUES
		('$key', '$username', $permissions, $expire, '".$_SERVER['REMOTE_ADDR']."')";

	$database->query($command);

	setcookie("extendedsession", $key, $expire, '/', $_SERVER["HTTP_HOST"], true, true);

	$database->close();
}
function destroy_long_session(){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$username = $database->real_escape_string($_SESSION['user']);
	$permissions = $database->real_escape_string($_SESSION['permissions']);
	$key = $database->real_escape_string($_COOKIE['extendedsession']);
	$command = "DELETE FROM extendedsession WHERE username = '$username' AND permissions = $permissions AND uid = '$key'";
	$database->query($command);

	setcookie("extendedsession", "", 0, '/', $_SERVER["HTTP_HOST"], true, true);

	$database->close();
}
function login_extended(){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	clean_long_session_table($database);
	if(isset($_COOKIE['extendedsession']) && !isset($_SESSION['user'])){
		$key = $database->real_escape_string($_COOKIE['extendedsession']);
		$command = "SELECT username, permissions FROM extendedsession WHERE uid = '$key'";
		$res = $database->query($command);
		if($res){
			$info = $res->fetch_assoc();
			$_SESSION['user'] = $info['username'];
			$_SESSION['permissions'] = $info['permissions'];
			$command = "UPDATE extendedsession SET ip_address='".$_SERVER['REMOTE_ADDR']."' WHERE uid='$key'";
			$database->query($command);
		}
	}
	$database->close();
}
function logout_all_extended($username, $privileges){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$username = $database->real_escape_string($username);

	$command = "DELETE FROM extendedsession WHERE username = '$username' AND permissions = $privileges";

	$database->query($command);

	$database->close();
	force_refresh($username);
}
function get_all_extended($username=null, $privileges=null, $key=null){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$username = ($username==null)?$_SESSION['user']:$username;
	$privileges = ($privileges==null)?$_SESSION['permissions']:$privileges;
	$key = ($key==null)?$_COOKIE['extendedsession']:$key;

	$username = $database->real_escape_string($username);
	$privileges = intval($privileges);
	$key = $database->real_escape_string($key);

	$command = "SELECT ip_address, expiration FROM extendedsession WHERE username='$username' AND
		permissions=$privileges AND uid!='$key' ORDER BY expiration";

	$ips = Array();
	$res = $database->query($command);
	if($res){
		while($row = $res->fetch_assoc()){
			array_push($ips, $row);
		}
	}
	$database->close();
	return $ips;
}
function update_order($new_order, $tablename="pages", $parent=null){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	foreach($new_order as $index => $name){
		$index = intval($index);
		$name = $database->real_escape_string($name);
		if($parent){
			$parent = $database->real_escape_string($parent);
			$command = "UPDATE $tablename SET position='$index' WHERE name='$name' AND parent='$parent'";
		}else{
			$command = "UPDATE $tablename SET position='$index' WHERE name='$name'";
		}
		$database->query($command);
	}
	$database->close();
	force_refresh();
}
function save_page($pageContent){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$updatedPostOrder = Array();
	foreach($pageContent as $index => $value){
		if(gettype($index) == "integer"){
			$updatedPostOrder[$index] = $value;
			unset($pageContent[$index]);
		}
	}
	$name = $database->real_escape_string($pageContent['name']);
	$title = $database->real_escape_string($pageContent["title"]);
	$content = $database->real_escape_string($pageContent["content"]);
	$direction = $database->real_escape_string($pageContent["direction"]);
	$command = "UPDATE pages SET title='$title', content='$content', direction='$direction' WHERE name='$name'";
	$database->query($command);
	$database->close();
	update_order($updatedPostOrder, "posts", $name);
}
function add_page($newPage){
	$found = false;
	foreach($_SESSION['pages'] as $page){
		if(strtolower($page['name']) == strtolower($newPage['name'])){
			$found = true;
			break;
		}
	}
	if($found){
		return false;
	}

	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$name = $database->real_escape_string($newPage['name']);
	$title = $database->real_escape_string($newPage["title"]);
	$content = $database->real_escape_string($newPage["content"]);
	$direction = $database->real_escape_string($newPage["direction"]);

	$position = count($_SESSION['pages']);

	$command = "INSERT INTO pages (name, title, content, direction, position) VALUES
		('$name', '$title', '$content', '$direction', $position)";

	$database->query($command);

	$database->close();
	force_refresh();
	return true;
}
function delete_page($pageName){
	$protected = fetch_content($pageName)['protected'];
	if($protected === true){
		return false;
	}
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$pageName = $database->real_escape_string($pageName);

	$command = "DELETE FROM posts WHERE parent='$pageName'";
	$database->query($command);
	$command = "DELETE FROM pages WHERE name='$pageName'";
	$database->query($command);

	$database->close();
	force_refresh();
	return true;
}
function add_post($post, $parent){
  $found = false;
  foreach($_SESSION['pages'] as $page){
    if($page['name'] == $parent){
      foreach($page['posts'] as $sespost){
        if(strtolower($post['name']) == strtolower($sespost)){
          $found = true;
          break;
        }
      }
      if($found){
        break;
      }
    }
  }
	if($found === true){
		return false;
	}
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$parent = $database->real_escape_string($parent);
	$name = $database->real_escape_string($post['name']);
	$title = $database->real_escape_string($post['title']);
	$content = $database->real_escape_string($post['content']);
	$picture = $database->real_escape_string($post['picture']);

	$command = "SELECT COUNT(name) AS count FROM posts WHERE parent='$parent'";
	$count = $database->query($command)->fetch_assoc()['count'];

	$command = "INSERT INTO posts (name, title, content, picture, position, parent) VALUES
		('$name', '$title', '$content', '$picture', $count, '$parent')";
	$database->query($command);
	$database->close();
	force_refresh();
	return true;
}
function save_post($post, $parent){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$parent = $database->real_escape_string($parent);
	$name = $database->real_escape_string($post['name']);
	$title = $database->real_escape_string($post['title']);
	$content = $database->real_escape_string($post['content']);
	$picture = $database->real_escape_string($post['picture']);

	$command = "UPDATE posts SET title='$title', content='$content', picture='$picture'
		WHERE name='$name' and parent='$parent'";

	$database->query($command);
	$database->close();
	force_refresh();
}
function delete_post($post, $parent){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$parent = $database->real_escape_string($parent);
	$post = $database->real_escape_string($post);

	$command = "DELETE FROM posts WHERE name='$post' and parent='$parent'";
	$database->query($command);
	$database->close();
	force_refresh();
}
function save_footer($footer, $parent=null){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$name = $database->real_escape_string($footer['name']);
	$updatedOrder = Array();

	if($parent){
		$parent = $database->real_escape_string($parent);
		$url = $database->real_escape_string($footer['url']);
		$type = $database->real_escape_string($footer['type']);
		$icon = $database->real_escape_string($footer['icon']);

		$command = "UPDATE footerlinks SET url='$url', type='$type', icon='$icon'
			WHERE name='$name' and parent='$parent'";

	}else{
		$content = $database->real_escape_string($footer['content']);

		foreach($footer as $index => $value){
			if(gettype($index) == "integer"){
				$updatedOrder[$index] = $value;
				unset($footer[$index]);
			}
		}

		$command = "UPDATE footer SET content='$content' WHERE name='$name'";
	}

	$database->query($command);
	$database->close();
	if(empty($parent)){
		update_order($updatedOrder, "footerlinks", $name);
	}else{
		force_refresh();
	}
}
function add_footer($footer, $parent=null){
  $found = false;

	$search = ($parent)?fetch_footer($parent)["links"]:get_all_footers(false);

  foreach($search as $item){
    if(strtolower($item['name']) == strtolower($footer['name'])){
      $found = true;
    	break;
  	}
  }
	if($found === true){
		return false;
	}
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$name = $database->real_escape_string($footer['name']);
	if($parent){
		$parent = $database->real_escape_string($parent);
		$icon = $database->real_escape_string($footer['icon']);
		$type = $database->real_escape_string($footer['type']);
		$url = $database->real_escape_string($footer['url']);

		$command = "SELECT COUNT(name) AS count FROM footerlinks WHERE parent='$parent'";
		$count = $database->query($command)->fetch_assoc()['count'];
		$command = "INSERT INTO footerlinks (name, icon, type, url, parent, position) VALUES
			('$name', '$icon', '$type', '$url', '$parent', $count)";
	}else{
		$content = $database->real_escape_string($footer['content']);

		$command = "SELECT COUNT(name) AS count FROM footer";
		$count = $database->query($command)->fetch_assoc()['count'];

		$command = "INSERT INTO footer (name, content, position) VALUES
			('$name', '$content', $count)";
	}

	$database->query($command);
	$database->close();
	force_refresh();
	return true;
}
function delete_footer($name, $parent=null){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$name = $database->real_escape_string($name);

	if($parent){
		$parent = $database->real_escape_string($parent);
		$command = "DELETE FROM footerlinks WHERE name='$name' AND parent='$parent'";

	}else{
		$command = "DELETE FROM footerlinks WHERE parent='$name'";
		$database->query($command);
		$command = "DELETE FROM footer WHERE name='$name'";
	}

	$database->query($command);
	$database->close();
	force_refresh();
}
function update_links($raw_links){
	$links = Array();
	foreach($raw_links as $index => $value){
		$split = strrpos($index, "-");
		$link_name = urldecode(substr($index, 0, $split));
		$link_value = substr($index, $split+1);
		if(!isset($links)){
			$links[$link_name] = Array();
		}
		$links[$link_name][$link_value] = $value;
	}
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	foreach($links as $link => $values){
		$protected = false;
		foreach($_SESSION['headerLink'] as $headerLink){
			if($headerLink['name'] == $link && $headerLink['protected']){
				$protected = true;
				break;
			}
		}
		$command = "UPDATE loginlinks SET";
		foreach($values as $column => $newValue){
			if($column == "min_permission" || !$protected){
				$newValue = $database->real_escape_string($newValue);
				$command .= " $column='$newValue',";
			}
		}
		$link = $database->real_escape_string($link);
		$command = substr($command, 0, strlen($command)-1)." WHERE name='$link'";
		$database->query($command);

	}
	$database->close();
	force_refresh();
}
function delete_link($target){
	foreach($_SESSION['headerLink'] as $headerLink){
		if($headerLink['name'] == $target && $headerLink['protected']){
			return false;
		}
	}
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$target = $database->real_escape_string($target);
	$command = "DELETE FROM loginlinks WHERE name='$target'";

	$database->query($command);
	$database->close();
	force_refresh();
	return true;
}
function add_link($newLink){
	foreach($_SESSION['headerLink'] as $headerLink){
		if($headerLink['name'] == $newLink['name']){
			return false;
		}
	}
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$name = $database->real_escape_string($newLink['name']);
	$min = $database->real_escape_string($newLink['min_permission']);
	$url = $database->real_escape_string($newLink['url']);

	$command = "INSERT INTO loginlinks (name, url, min_permission) VALUES ('$name', '$url', '$min')";

	$database->query($command);
	$database->close();
	force_refresh();
	return true;
}
function force_refresh($target='ALL'){
	if($_SESSION['force_sync']){
		$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
		$target = $database->real_escape_string($target);
		$command = "INSERT INTO refresh (target, older_than) VALUES ('$target', ".time();
		$command .= ") ON DUPLICATE KEY UPDATE older_than = VALUES(older_than)";
		$database->query($command);
		$timeout = time() - (10*24*60*60);
		$command = "DELETE FROM refresh WHERE older_than<$timeout AND target!='ALL'";
		$database->query($command);
		$database->close();
	}
}
function refresh_session(){
	$reset = false;
	if(!empty($_SESSION) && $_SESSION['force_sync']){
		$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
		if(isset($_SESSION['user'])){
			$user = $database->real_escape_string($_SESSION['user']);
			$command = "SELECT older_than FROM refresh WHERE target='$user'";
			$res = $database->query($command);
			if($res){
				$olderthan = $res->fetch_array()[0];
				if($olderthan > $_SESSION['session_start']){
					$_SESSION = Array();
					$reset = true;
				}
			}else{
				$reset = false;
			}
		}
		if(!$reset){
			$olderthan = $database->query("SELECT older_than FROM refresh WHERE target='ALL'")->fetch_array()[0];
			if($olderthan > $_SESSION['session_start']){
				$reset = true;
			}
		}
		$database->close();
	}else if(empty($_SESSION)){
		$reset = true;
	}
	return $reset;
}
?>
