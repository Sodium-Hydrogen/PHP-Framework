<?php
/*

These are all the functions required to make the backend of the framework work correctly.
See DOCS.md for information

*/


function get_url(bool $resolve_home=true){
	if(isset($_SERVER["REDIRECT_STATUS"])){
		$_SESSION["error"] = $_SERVER["REDIRECT_STATUS"];
	}
	if(isset($_SERVER['PATH_INFO'])){
		$link = $_SERVER['PATH_INFO'];
		$link = substr($link, strpos($link, "/")+1);
		if($link === "" && $resolve_home){
			$link = "home";
		}
		return trim(strtolower(urldecode($link)), "/");
	}else{
		return $resolve_home?"home":"";
	}
}
function request_page_head($second = null){
	if($_SERVER["HTTP_ACCEPT"] == "application/json"){
		header('Content-Type: application/json; charset=UTF-8');
		return;
	}
	if(empty($second)){
		$actual_link = get_url();
		if(strpos($actual_link, '/') > 0){
			$actual_link = substr($actual_link, 0, strpos($actual_link, '/'));
		}
		$second = $actual_link;
		foreach($_SESSION["pages"] as $page){
			if(strcasecmp($actual_link, $page['title']) == 0){
				$second = fetch_content($page['id'])['title'];
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
function load_logged_header(){
	if(isset($_SESSION['user'])){
		$user = $_SESSION['user']["username"];
		queue_header("<script src='/resources/header.js'></script>");
		queue_header("<link rel='stylesheet' href='/resources/userHeaderStyle.css'>");
		queue_body("<div class='loginHeader' id='loginHeader'>
			<div class='headerMenu' id='login_menu'><div class='username'>
		");
		queue_body('Welcome, ' . $user . "</div><div class='links'>
			<button class='dropBtn' onclick='header_dropdown()'>Menu</button>
			<div class='dropdown-content' id='dropdownMenu'>
			<a href='/'>Site</a><a href='/login.php/logout'>Logout</a>
		");
		foreach($_SESSION['headerLink'] as $link){
			queue_body("<a href=".$link['url'].">".$link['name']."</a>");
		}
		queue_body("</div></div></div></div>");
	}
}
function fetch_content(int $page, int $post=null, bool $only_published=true){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$only_published = $only_published?"AND published=true":"";
	$res = [];
	if($post){
		$post = $database->real_escape_string($post);
		$command = "SELECT * FROM posts WHERE id=$post and parent=$page $only_published";
		$res = $database->query($command)->fetch_assoc();
	}else{
		$command = "SELECT * FROM pages WHERE id=$page";
		$res = $database->query($command)->fetch_assoc();
		$res["in_html_header"] = [];
		$res["page_content"] = [];
		$posts = [];
		$content = [];
		$command = "SELECT id,title,picture,position FROM posts WHERE parent=$page $only_published ORDER BY position DESC";
		$db_res = $database->query($command);
		while($post = $db_res->fetch_assoc()){
			$posts[] = $post;
		}
		$command = "SELECT in_html_header,content,position FROM page_content WHERE parent=$page $only_published ORDER BY position DESC";
		$db_res = $database->query($command);
		while($cnt = $db_res->fetch_assoc()){
			if($cnt["in_html_header"]){
				$res["in_html_header"][] = $cnt;
			}else{
				$content[] = $cnt;
			}
		}
		while(sizeof($content) && sizeof($posts)){
			if($posts[sizeof($posts)-1]["position"] < $content[sizeof($content)-1]["position"]){
				$res["page_content"][] = array_pop($posts);
			}else{
				$res["page_content"][] = array_pop($content);
			}
		}
		$res["page_content"] = array_merge($res["page_content"], $posts, $content);
	}

	$database->close();
	if(isset($res['title'])){
		return $res;
	}
}
function get_all_footers($nest_links=true){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$command = "SELECT * FROM footers ORDER BY position";
	$res = $database->query($command);
	$footer = [];
	$links = [];
	if($res){
		while($row = $res->fetch_assoc()){
			$command = "SELECT * FROM footerlinks WHERE parent=$row[id] ORDER BY position";
			$subres = $database->query($command);
			if($nest_links){
				$row['links'] = [];
			}
			while($subres && $link = $subres->fetch_assoc()){
				if($nest_links){
					$row["links"][] = $link;
				}else{
					$lid = $link["id"];
					unset($link["id"]);
					$links[$lid] = $link;
				}
			}
			$id = $row["id"];
			unset($row["id"]);
			$footer[$id] = $row;
		}
	}

	$database->close();
	if($nest_links){
		return $footer;
	}
	return ["footer"=>$footer, "link"=>$links];
}
function fetch_footer(int $footer, int $link=null){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$footer = $database->real_escape_string($footer);
	$result = [];
	if($link){
		$link = $database->real_escape_string($link);
		$command = "SELECT * FROM footerlinks WHERE id=$link AND parent=$footer";
		$res = $database->query($command);
		if($res){
			$result = $res->fetch_assoc();
		}
	}else{
		$command = "SELECT * FROM footers WHERE id=$footer";
		$res = $database->query($command);
		if($res){
			$result = $res->fetch_assoc();
			$result["links"] = Array();
			$command = "SELECT * FROM footerlinks WHERE parent=$footer ORDER BY position";
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

	if(isset($result['name'])){
		return $result;
	}
}
function login(string $username, string $password){
	$username = strtolower(trim($username));
	$password = trim($password);

	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$username = $database->real_escape_string($username);
	$command = "SELECT * FROM accounts WHERE username ='$username'";
	$user_id = null;
	$info = $database->query($command);
	while($user_data = $info->fetch_assoc()){
		$salt = $user_data['salt'];
		$password = hash('sha256', ($salt . hash('sha256', ($password . $salt))));

		$command = "SELECT * FROM accounts WHERE username = '$username' and password ='$password' and local_account = true";
		$information = $database->query($command);

		if($information && $information = $information->fetch_assoc()){
			$user_id = $information["userid"];
			break;
		}
	}

	$database->close();
	return $user_id;
}
function load_user_info(int $userid){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$command = "SELECT userid,username,local_account,superuser FROM accounts WHERE userid ='$userid'";
	$info = $database->query($command)->fetch_assoc();

	if(isset($_SESSION["user"])){
		$_SESSION["user"] = array_merge($_SESSION["user"], $info);
	}else{
		$_SESSION["user"] = $info;
	}

	$_SESSION["user"]["groups"] = [];

	$command = "SELECT groups.groupid,name FROM account_groups JOIN groups ON account_groups.groupid=groups.groupid WHERE userid=$userid";
	$res = $database->query($command);
	while($info = $res->fetch_assoc()){
		$_SESSION["user"]["groups"][$info["groupid"]] = $info["name"];
	}

	$_SESSION["headerLink"] = [];
	$command = "SELECT name, url, groupid, only_local_account, any_user FROM loginlinks ORDER BY protected, name";
	$res = $database->query($command);
	while($res && $link = $res->fetch_assoc()){
		if(has_permissions($link, false)){
			$_SESSION["headerLink"][] = $link;
		}
	}

	$database->close();
}
function view_users(int $id=null, bool $groupid=false) : array{
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$command = "SELECT accounts.userid, username, local_account, superuser FROM accounts ";
	if($id!==null && !$groupid){
		$command.="WHERE userid=$id ";
	}else if($id!==null && $groupid){
		$command.=" JOIN account_groups ON accounts.userid=account_groups.userid WHERE groupid=$id ";
	}
	$command.="ORDER BY local_account DESC, superuser DESC, username";

	$output = $database->query($command);
	$users = [];
	while($output && $row = $output->fetch_assoc()){
		$row["groups"] = [];

		$command = "SELECT name,groups.groupid FROM account_groups JOIN groups ON account_groups.groupid=groups.groupid WHERE userid=".$row["userid"];
		$res = $database->query($command);
		while($group = $res->fetch_assoc()){
			$row["groups"][$group["groupid"]] = $group["name"];
		}

		$users[$row["userid"]] = $row;
	}
	$database->close();
	if($id !== null && !$groupid){
		return $users[$id];
	}
	return $users;

}
function create_account(string $username, string $password, bool $superuser=false){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$username = $database->real_escape_string($username);
	$superuser = var_export(boolval($superuser), true);

	$command = "SELECT username FROM accounts WHERE username = '$username'";

	$check = $database->query($command);
	$result = false;
	if(empty($check->fetch_assoc())){
		$salt = (string)bin2hex(openssl_random_pseudo_bytes(8));
		$password = hash('sha256', ($salt . hash('sha256', ($password . $salt))));

		$command = "INSERT INTO accounts (username, password, salt, superuser, local_account)
		VALUES ('$username', '$password', '$salt', $superuser, true)";
		$database->query($command);

		$command = "SELECT userid FROM accounts WHERE username='$username' AND password='$password'";
		$result = $database->query($command)->fetch_assoc()["userid"];
	}
	$database->close();

	return $result;

}
function admin_change_password($userid, $newPassword){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$userid = intval($userid);

	$command = "SELECT userid FROM accounts WHERE userid=$userid";

	$check = $database->query($command);

	if(!empty($check->fetch_assoc())){
		$salt = (string)bin2hex(openssl_random_pseudo_bytes(8));
		$newPassword = hash('sha256', ($salt . hash('sha256', ($newPassword . $salt))));

		$command = "UPDATE accounts SET password='$newPassword', salt='$salt' WHERE userid='$userid'";

		$database->query($command);
	}

	$database->close();

}
function change_password($userid, $oldPassword, $newPassword){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$userid = intval($userid);

	$command = "SELECT password, salt FROM accounts WHERE userid = '$userid'";

	$output = $database->query($command);
	$result = false;

	$data = $output->fetch_assoc();
	$salt = $data['salt'];

	$oldPassword = hash('sha256', ($salt . hash('sha256', ($oldPassword . $salt))));
	$password = $data['password'];

	$database->close();

	if($oldPassword == $password){
		admin_change_password($userid, $newPassword);
		$result = true;
	}
	return $result;
}
function delete_account($userid){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$userid = intval($userid);

	$command = "DELETE FROM account_groups WHERE userid=$userid";
	$database->query($command);

	$command = "DELETE FROM accounts WHERE userid=$userid";
	$database->query($command);

	$command = "DELETE FROM extendedsession WHERE userid=$userid";
	$database->query($command);

	$database->close();
}
function modify_account_setting($userid, $target_value, $new_value){
	if(!in_array($target_value, ["username", "local_account", "superuser"])){
		return false;
	}

	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	if($target_value != "username"){
		$new_value = var_export(boolval($new_value), true);
	}else{
		$new_value = $database->real_escape_string($new_value);
		$command = "SELECT username FROM accounts WHERE username='$new_value'";
		$res = $database->query($command);
		if(!empty($res->fetch_assoc())){
			$database->close();
			return false;
		}
		$new_value = "'$new_value'";
	}
	$userid = intval($userid);
	$command = "UPDATE accounts SET $target_value=$new_value WHERE userid=$userid";

	$database->query($command);
	$database->close();
	return true;
}
function modify_account_groups(int $userid, $groupid, bool $add=true){
	if(gettype($groupid) == "array"){
		$groupid = implode($add?"),($userid,":") OR (userid=$userid AND groupid=", array_map('intval', $groupid));
	}else{
		$groupid = intval($groupid);
	}
	$command = "INSERT IGNORE INTO account_groups (userid, groupid) VALUES ($userid,";
	if(!$add){
		$command = "DELETE FROM account_groups WHERE (userid=$userid AND groupid=";
	}
	$command.=$groupid.")";

	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$database->query($command);
	$database->close();
}
function save_groups(int $gid, string $name, string $description=""){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$name = $database->real_escape_string($name);
	$description = $database->real_escape_string($description);
	$success = false;

	$command = "SELECT groupid FROM groups WHERE name='$name'";
	$res = $database->query($command);
	if(!$res || $res->num_rows == 0){
		$command = "UPDATE groups SET name='$name', description='$description' WHERE groupid=$gid";
		$database->query($command);
		$success = true;
	}

	$database->close();
	return $success;
}
function delete_group(int $gid){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$users = [];

	$command = "SELECT userid FROM account_groups WHERE groupid=$gid";
	$res = $database->query($command);
	while($res && $row = $res->fetch_assoc()){
		$users[] = $row["userid"];
	}

	$command = "DELETE FROM account_groups WHERE groupid=$gid";
	$database->query($command);
	$command = "DELETE FROM groups WHERE groupid=$gid";
	$database->query($command);

	$database->close();

	return $users;
}
function create_group(string $name, string $description=""){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$name = $database->real_escape_string($name);
	$description = $database->real_escape_string($description);
	$success = false;
	
	$command = "SELECT groupid FROM groups WHERE name='$name'";
	$res = $database->query($command);
	if(!$res || $res->num_rows == 0){
		$command = "INSERT INTO groups (name, description) VALUES ('$name', '$description')";
		$database->query($command);
		$success = true;
	}

	$database->close();
	return $success;
}
function save_fail(){
	$timeout = $_SESSION['ban_time'] * 3600;
	$cur = time();
	$ip = $_SERVER['REMOTE_ADDR'];
	$updated = false;
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$command = "SELECT * FROM banlist WHERE ipaddress='$ip'";
	$tmout = $cur+$timeout;

	$output = $database->query($command);
	if($output && $row = $output->fetch_assoc()){
		$attempts = $row['attempts'] + 1;
		$command = "UPDATE banlist SET attempts = $attempts, untilFree = $tmout WHERE ipaddress = '$ip'";
		if($attempts >= $_SESSION["retry"]){
			create_log("IP Ban", "Too many failed attempts from $ip. Banning until: ".gmdate('c', $tmout), $database);
		}
		$database->query($command);
		$updated = true;
	}
	if(!$updated){
		$command = "INSERT INTO banlist (ipaddress, attempts, untilFree) VALUES ('$ip', 1, $tmout)";
		$database->query($command);
	}

	$database->close();

}

function check_attempts(){
	$cur = time();
	$ip = $_SERVER['REMOTE_ADDR'];
	$attempts = 0;

	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$command = "SELECT * FROM banlist WHERE ipaddress = '$ip' OR untilFree<='".time()."'";

	$output = $database->query($command);

	while($output && $row = $output->fetch_assoc()){
		if($cur > $row['untilFree']){
			$oldip = $row['ipaddress'];
			$row["attempts"] = 0;
			$command = "DELETE FROM banlist WHERE ipaddress = '$oldip'";
			create_log("IP Unban", "Released $oldip from jail.");
			$database->query($command);
		}
		if($ip == $row["ipaddress"]){
			$attempts = $row["attempts"];
		}
	}
	$database->close();

	return $attempts;

}
function clear_fails(){
	$ip = $_SERVER['REMOTE_ADDR'];

	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$command = "DELETE FROM banlist WHERE ipaddress = '$ip'";

	$database->query($command);

	$database->close();

}
function setup_database(){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$command = "SELECT version FROM database_version;";
	$res = $database->query($command);

	// Since there should always be an account check to see if the database has been
	// created and user entered. If it has not it creates all the tables and enters
	// some default data
	if($res === false ){
		$commands = [
			"CREATE TABLE refresh (target VARCHAR(64) PRIMARY KEY NOT NULL, older_than BIGINT NOT NULL)",

			"CREATE TABLE database_version (version FLOAT NOT NULL);",

			"CREATE TABLE accounts (userid INT AUTO_INCREMENT PRIMARY KEY,
				username TEXT NOT NULL, password VARCHAR(64), salt VARCHAR(16),
				superuser BOOLEAN DEFAULT false, local_account BOOLEAN DEFAULT false)",

			"CREATE TABLE groups (groupid INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(64) NOT NULL UNIQUE,
				description TEXT DEFAULT '', protected BOOLEAN DEFAULT false)",

			"CREATE TABLE account_groups (groupid INT NOT NULL, userid INT NOT NULL,
				FOREIGN KEY (groupid) REFERENCES groups(groupid),
				FOREIGN KEY (userid) REFERENCES accounts(userid),
				PRIMARY KEY (groupid, userid));",

			"CREATE TABLE banlist (ipaddress VARCHAR(40) NOT NULL PRIMARY KEY,
				attempts INT NOT NULL, untilFree BIGINT NOT NULL)",

			"CREATE TABLE footers (id INT PRIMARY KEY AUTO_INCREMENT, title VARCHAR(100) NOT NULL UNIQUE,
				content TEXT DEFAULT '', position INT, published BOOLEAN DEFAULT false)",

			"CREATE TABLE footerlinks (id INT PRIMARY KEY AUTO_INCREMENT, url TEXT DEFAULT '', icon TEXT DEFAULT 'link', type ENUM('brand','solid') DEFAULT 'solid',
				parent INT NOT NULL, position INT, FOREIGN KEY (parent) REFERENCES footers(id), published BOOLEAN DEFAULT false )",

			"CREATE TABLE pages (id INT PRIMARY KEY AUTO_INCREMENT, title VARCHAR(100) DEFAULT 'Title' UNIQUE, direction ENUM('column', 'row') DEFAULT 'column',
				position INT, content LONGTEXT DEFAULT '', protected BOOLEAN DEFAULT false, published BOOLEAN DEFAULT false)",

			"CREATE TABLE page_content (id INT PRIMARY KEY AUTO_INCREMENT,
				content LONGTEXT DEFAULT '', parent INT NOT NULL, position INT,
				published BOOLEAN DEFAULT false, in_html_header BOOLEAN DEFAULT false,
				FOREIGN KEY (parent) REFERENCES pages(id) )",

			"CREATE TABLE posts (id INT PRIMARY KEY AUTO_INCREMENT, title TEXT DEFAULT '',
				picture TEXT DEFAULT '', content LONGTEXT DEFAULT '', parent INT NOT NULL, position INT,
				published BOOLEAN DEFAULT false, FOREIGN KEY (parent) REFERENCES pages(id) )",

			"CREATE TABLE configs (setting VARCHAR(64) NOT NULL PRIMARY KEY, value TEXT NOT NULL,
				type ENUM('INT', 'BOOL', 'STRING') DEFAULT 'STRING', description TEXT DEFAULT '', protected BOOLEAN DEFAULT false)",

			"CREATE TABLE loginlinks ( name VARCHAR(64) NOT NULL PRIMARY KEY, url TEXT NOT NULL, groupid INT,
				only_local_account BOOLEAN DEFAULT false, protected BOOLEAN DEFAULT false, any_user BOOLEAN DEFAULT false,
				FOREIGN KEY (groupid) REFERENCES groups(groupid))",

			"CREATE TABLE extendedsession (uid VARCHAR(64) NOT NULL PRIMARY KEY, userid INT NOT NULL,
				expiration BIGINT NOT NULL, ip_address VARCHAR(40), lastaccess BIGINT,
				FOREIGN KEY (userid) REFERENCES accounts(userid))",

			"CREATE TABLE version_control (id INT PRIMARY KEY AUTO_INCREMENT, target_key VARCHAR(128) NOT NULL,
				timestamp BIGINT NOT NULL, notes VARCHAR(100) DEFAULT '', rcs_data TEXT DEFAULT '')",

			"CREATE TABLE logging (id INT PRIMARY KEY AUTO_INCREMENT, uid TEXT NOT NULL,
				timestamp BIGINT NOT NULL, info TEXT NOT NULL)",

			//------------------------------//
			//------Insert Commands---------//
			//------------------------------//
			"INSERT INTO database_version (version) VALUES ($_SESSION[database_version]);",

			"INSERT INTO groups (groupid, name, description, protected) VALUES
				(1, 'editor',    'An example group to show how to give specific users access to only updating content.', false)",

			"INSERT INTO refresh (target, older_than) VALUES ('ALL', 0)",

			"INSERT INTO pages (id, title, direction, position, protected) VALUES (1, 'Home', 'column', 0, true)",

			"INSERT INTO page_content (parent, content, in_html_header, position) VALUES
				(1, 'Example Content', false, 1)",

			"INSERT INTO posts (title, content, parent, position, picture) VALUES
				('Hello World', '42', 1, 0, '')",

			"INSERT INTO loginlinks (url, name, groupid, any_user, protected) VALUES
				('/login.php/manageUsers', 'Manage Users',  NULL, false, true),
				('/content.php',           'Content',       1,    false, true),
				('/content.php/settings',  'Settings',      NULL, false, true),
				('/login.php/account',     'Account',       NULL, true,  true)",

			"INSERT INTO configs (setting, value, type, description, protected)  VALUES
				('retry',               '4',                           'INT',    'Number of allowed failed login attempts.', true),
				('site',                'TITLE',                       'STRING', 'The name of this site.', true),
				('sub_title',           'SUB TITLE',                   'STRING', 'A two word title to be loaded on the pages.', true),
				('ban_time',            '48',                          'INT',    'Ban time to prevent login in hours.', true),
				('debug',               'false',                       'BOOL',   'Activates php errors to be sent in html responses as well as the log files.', true),
				('setup',               'true',                        'BOOL',   'Put the site in setup mode which hides the homepage unless a user is logged in.', true),
				('extended_timeout',    '30',                          'INT',    'The count in days of how long a user stays logged in.', true),
				('alt_login_text',      '',                            'STRING', 'If this is set it will display a link to an alternate login script on the main login page.', true),
				('alt_login_url',       '',                            'STRING', 'The url to an alternate login script.', true),
				('force_sync',          'true',                        'BOOL',   'Force all sessions to recheck every request if they need to refresh variables.', true),
				('show_login',          'true',                        'BOOL',   'Tell the theme to show a login link.', true),
				('dark_mode',           'true',                        'BOOL',   'Tells the framework and themes whether to use dark mode or not.', true),
				('allowed_uploads',     'png,jpg,gif,svg,js,css,html', 'STRING', 'A comma separated list of file extension to allow as file uploads. (php could allow for remote code execution)', true),
				('logging_enabled',     'true',                        'BOOL',   'Tells the framework whether or not to log messages internally.', true),
				('uploads_dir',         'content',                     'STRING', 'The folder to upload files to. Becareful this could allow remote code execution.', true),
				('header_ajax_ace',     '',                            'BIG STRING', 'The header tag used to load ajax ace cloud.<div class=\\'help-msg hidden\\'>If you would like to have extensions use this as a list of script tags with each extension after the main ace.js. Ie ext-spellcheck.js is useful.</div>', true),
				('header_font_awesome', '',                            'BIG STRING', 'The header tag used to load font awesome.', true),
				('version_control',     'true',                        'BOOL',   'Allow editors to take snapshots of content using RCS like version control.', true)",

		];
		foreach($commands as $command){
			$res = $database->query($command);
			if(!$res){
				queue_message("Table creation command failed<hr>$command<hr>".$database->error, "error");
				$database->close();
				return false;
			}
		}
		return true;
	}
	$database->close();
	if($res->fetch_row()[0] != $_SESSION["database_version"]){
		queue_message("Database version does not match", "error");
		return false;
	}
	return true;
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
	$_SESSION["messages"] = [];

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
		$command = "SELECT id,title FROM pages WHERE published=true ORDER BY position, id";
		$res = $database->query($command);
		$_SESSION['pages'] = Array();
		while($row = $res->fetch_assoc()){
			$_SESSION["pages"][] = $row;
		}

		$_SESSION['headerLink'] = [];
		if(isset($_SESSION["user"])){
			$command = "SELECT name, url, groupid, only_local_account, any_user FROM loginlinks ORDER BY protected, name";
			$res = $database->query($command);
			while($res && $link = $res->fetch_assoc()){
				if(has_permissions($link, false)){
					$_SESSION["headerLink"][] = $link;
				}
			}
		}

		$command = "SELECT id,title,content FROM footers WHERE published=true ORDER BY position";
		$res = $database->query($command);
		$_SESSION['footers'] = Array();
		while($row = $res->fetch_assoc()){

			$row["links"] = [];
			$command = "SELECT url,icon,type FROM footerlinks WHERE parent=$row[id] AND published=true ORDER BY position";
			$link_res = $database->query($command);
			while($link_res && $link = $link_res->fetch_assoc()){
				$row["links"][] = $link;
			}
			unset($row["id"]);

			$_SESSION['footers'][] = $row;
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
	if($type == "BOOL"){ $value = $value?"true":"false"; }
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
	$command = "INSERT INTO extendedsession (uid, userid, expiration, ip_address, lastaccess) VALUES
		('$key', '".$_SESSION["user"]["userid"]."', $expire, '".$_SERVER['REMOTE_ADDR']."','".time()."')";

	$database->query($command);

	// Don't set domain so there is is host only. (no subdomain)
	setcookie("__Host-extendedsession", $key, $expire, '/', NULL, true, true);

	$database->close();
}
function destroy_long_session(){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$userid = intval($_SESSION['user']["userid"]);
	// $permissions = $database->real_escape_string($_SESSION['permissions']);
	$key = $database->real_escape_string($_COOKIE['__Host-extendedsession']);
	$command = "DELETE FROM extendedsession WHERE userid = '$userid' AND uid = '$key'";
	$database->query($command);

	setcookie("__Host-extendedsession", "", 0, '/', $_SERVER["HTTP_HOST"], true, true);

	$database->close();
}
function login_extended(){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	clean_long_session_table($database);
	if(isset($_COOKIE['__Host-extendedsession']) && !isset($_SESSION['user'])){
		$key = $database->real_escape_string($_COOKIE['__Host-extendedsession']);
		$command = "SELECT accounts.userid,username,superuser,local_account FROM extendedsession JOIN accounts ON extendedsession.userid=accounts.userid WHERE uid = '$key'";
		$res = $database->query($command);
		if($res && $res->num_rows > 0){
			// print(typeof($res));
			$_SESSION['user'] = $res->fetch_assoc();
			$_SESSION["user"]['groups'] = [];
			$command = "SELECT groups.groupid,name FROM account_groups JOIN groups ON account_groups.groupid=groups.groupid WHERE userid=".$_SESSION["user"]["userid"];
			$res = $database->query($command);
			while($info = $res->fetch_assoc()){
				$_SESSION["user"]["groups"][$info["groupid"]] = $info["name"];
			}
			$command = "UPDATE extendedsession SET ip_address='".$_SERVER['REMOTE_ADDR']."', lastaccess=".time()." WHERE uid='$key'";
			$database->query($command);
		}else{
			setcookie("__Host-extendedsession", "", 0, "/", NULL, true, true);
		}
	}
	$database->close();
}
function logout_all_extended($userid){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$userid = intval($userid);

	$command = "DELETE FROM extendedsession WHERE userid = $userid";

	$database->query($command);

	$database->close();
	force_refresh($userid);
}
function get_all_extended($userid=null, $key=null){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$userid = intval(($userid==null)?$_SESSION['user']["userid"]:$userid);
	$key = ($key==null)?$_COOKIE['__Host-extendedsession']:$key;

	$key = $database->real_escape_string($key);

	$command = "SELECT ip_address, expiration, lastaccess FROM extendedsession WHERE userid=$userid AND uid!='$key' ORDER BY lastaccess DESC, expiration";

	$ips = Array();
	$res = $database->query($command);
	if($res){
		while($row = $res->fetch_assoc()){
			$ips[] = $row;
		}
	}
	$database->close();
	return $ips;
}
function get_all_pages($nest_page_content = true){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$command = 'SELECT * FROM pages ORDER BY position';
	$res = $database->query($command);
	$pages = [];
	$posts = [];
	$page_content = [];
	while($res && $row = $res->fetch_assoc()){
		$id = $row["id"];
		unset($row["id"]);

		$pages[$id] = $row;
		if($nest_page_content){
			$pages[$id]["page_content"] = [];
			$pages[$id]["header_content"] = [];
		}

		$command = "SELECT * FROM posts WHERE parent='$id' ORDER BY position DESC";
		$resp = $database->query($command);
		$psts = [];
		while($resp && $post = $resp->fetch_assoc()){
			if($nest_page_content){
				$psts[] = $post;
			}else{
				$pid = $post["id"];
				unset($post["id"]);
				$posts[$pid] = $post;
			}
		}

		$command = "SELECT * FROM page_content WHERE parent='$id' ORDER BY position DESC";
		$content = $database->query($command);
		$pg_cnt = [];
		while($content && $cntnt = $content->fetch_assoc()){
			if($nest_page_content){
				if($cntnt["in_html_header"]){
					$pages[$id]["header_content"][] = $cntnt;
				}else{
					$pg_cnt[] = $cntnt;
				}
			}else{
				$cid = $cntnt["id"];
				unset($cntnt["id"]);
				$page_content[$cid] = $cntnt;
			}
		}
		if($nest_page_content){
			while(sizeof($psts) && sizeof($pg_cnt)){
				if($psts[sizeof($psts)-1]["position"] < $pg_cnt[sizeof($pg_cnt)-1]["position"]){
					$pages[$id]["page_content"][] = array_pop($psts);
				}else{
					$pages[$id]["page_content"][] = array_pop($pg_cnt);
				}
			}
			$pages[$id]["header_content"] = array_reverse($pages[$id]["header_content"]);
			$pages[$id]["page_content"] = array_merge($pages[$id]["page_content"], $psts, $pg_cnt);
		}
	}

	$database->close();
	if($nest_page_content){
		return $pages;
	}else{
		return ["page"=>$pages, "post"=>$posts, "page_content"=>$page_content];
	}
}
function update_page(int $id, $page){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$title = $database->real_escape_string($page["title"]);
	$content = $database->real_escape_string($page["content"]);
	$direction = $page["direction"]=="column"?"column":"row";
	$position = intval($page["position"]);
	$published = strval($page["published"])=='1'?"true":"false";

	$command = "UPDATE pages SET title='$title', content='$content', direction='$direction', position=$position, published=$published WHERE id=$id";
	$database->query($command);
	$database->close();
	force_refresh();
}
function add_page($newPage){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$title = $database->real_escape_string($newPage["title"]);
	$direction = strtolower($database->real_escape_string($newPage["direction"]));

	$command = "INSERT INTO pages (title, direction, position) SELECT
		'$title', '$direction', MAX(position)+1 FROM pages";

	$id = null;
	if($database->query($command) === true){
		$id = $database->insert_id;
	}

	$database->close();
	return $id;
}
function delete_page(int $id) : bool{
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$command = "SELECT * FROM pages WHERE id=$id";
	$res = $database->query($command);
	$page = ($res && $res = $res->fetch_assoc())?$res:[];

	if(!$page["protected"]){
		$command = "DELETE FROM posts WHERE parent=$id";
		$database->query($command);
		$command = "DELETE FROM page_content WHERE parent=$id";
		$database->query($command);
		$command = "DELETE FROM pages WHERE id=$id";
		$database->query($command);
	}

	$database->close();
	if($page["published"]){
		force_refresh();
	}
	return !$page["protected"];
}
function add_content($data=null, string $type, int $parent=null){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$type = $type=="posts"?"posts":"page_content";

	$id = null;
	if($type == "posts"){
		$title = $database->real_escape_string($data['title']);
		$picture = $database->real_escape_string($data['picture']);

		$command = "UPDATE posts SET position=IF(position IS NULL, 1, position+1) WHERE parent=$parent";
		$database->query($command);
		$command = "UPDATE page_content SET position=IF(position IS NULL, 1, position+1) WHERE parent=$parent AND in_html_header=false";
		$database->query($command);

		$command = "INSERT INTO posts (title, picture, parent, position) VALUES
			('$title', '$picture', $parent, 0 )";
		if($database->query($command) === true){
			$id = $database->insert_id;
		}
	}else{
		$id = [];
		foreach($data["parent"] as $parent){
			$parent = intval($parent);
			$command = "";
			if(isset($data["clone"]) && isset($data["source"])){
				$cpy_id = intval($data["source"]);
				$command = "INSERT INTO page_content (parent, position, content, published, in_html_header) SELECT
					$parent, 0, content, published, in_html_header FROM page_content WHERE id=$cpy_id";
			}else{
				$in_header = boolval($data["in_html_header"])?"true":"false";

				if($in_header == "false"){
					$command = "UPDATE posts SET position=IF(position IS NULL, 1, position+1) WHERE parent=$parent";
					$database->query($command);
				}
				$command = "UPDATE page_content SET position=IF(position IS NULL, 1, position+1) WHERE parent=$parent AND in_html_header=$in_header";
				$database->query($command);

				$command = "INSERT INTO page_content (parent, position, in_html_header) VALUES
					($parent, 0, $in_header )";
			}
			if($database->query($command) === true){
				$id[] = $database->insert_id;
			}
		}
	}

	$database->close();
	return $id;
}
function update_content(int $id, int $parent, $content, $cnt_type){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$command = "";
	if($cnt_type == "posts"){
		$title = $database->real_escape_string($content['title']);
		$cntent = $database->real_escape_string($content['content']);
		$picture = $database->real_escape_string($content['picture']);
		$published = strval($content["published"])=='1'?"true":"false";
		$position = intval($content["position"]);

		$command = "UPDATE posts SET title='$title', content='$cntent', picture='$picture',
			parent=$parent, published=$published, position=$position WHERE id=$id";
	}else{
		$cntent = $database->real_escape_string($content['content']);
		$published = strval($content["published"])=='1'?"true":"false";
		$position = intval($content["position"]);
		$in_header = strval($content["in_html_header"])=='1'?"true":"false";

		$command = "UPDATE page_content SET content='$cntent', in_html_header=$in_header,
			parent=$parent, published=$published, position=$position WHERE id=$id";
	}

	$database->query($command);
	$database->close();
}
function delete_content(int $id, string $type){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$type = $type=="posts"?"posts":"page_content";

	$command = "DELETE FROM $type WHERE id=$id";
	$database->query($command);
	$database->close();
}
function update_footer(int $id, $footer, int $parent=null){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$position = intval($footer["position"]);
	$published = $footer["published"]=='1'?'true':'false';
	if($parent){
		$url = $database->real_escape_string($footer['url']);
		$type = $database->real_escape_string($footer['type']);
		$icon = $database->real_escape_string($footer['icon']);

		$command = "UPDATE footerlinks SET url='$url', type='$type', icon='$icon',
			parent=$parent, position=$position, published=$published WHERE id=$id";

	}else{
		$title = $database->real_escape_string($footer['title']);
		$content = $database->real_escape_string($footer['content']);

		$command = "UPDATE footers SET title='$title', content='$content', published=$published WHERE id=$id";
	}

	$database->query($command);
	$database->close();
	force_refresh();
}
function add_footer($footer, int $parent=null){

	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$command = "";
	if($parent){
		$url = $database->real_escape_string($footer['url']);

		$command = "UPDATE footerlinks SET position=IF(position is NULL, 1, position+1) WHERE parent=$parent";
		$database->query($command);

		$command = "INSERT INTO footerlinks (url, parent, position) VALUES
			('$url', $parent, 0)";
	}else{
		$title = $database->real_escape_string($footer['title']);

		$command = "INSERT INTO footers (title, position) SELECT
			'$title', MAX(position)+1 FROM footers";
	}

	$database->query($command);
	$id = $database->insert_id;
	$database->close();
	return $id;
}
function delete_footer(int $id, bool $link=false){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	if($link){
		$command = "DELETE FROM footerlinks WHERE id=$id";
	}else{
		$command = "DELETE FROM footerlinks WHERE parent=$id";
		$database->query($command);
		$command = "DELETE FROM footers WHERE id=$id";
	}

	$database->query($command);
	$database->close();
	force_refresh();
}
function update_links($links){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	foreach($links as $link => $values){
		$link = $database->real_escape_string($link);
		$url = $database->real_escape_string($values["url"]);
		$gid = ($values["groupid"]!==NULL && $values["groupid"]!=="")?intval($values["groupid"]):"NULL";
		$any_user = $values["any_user"]?"true":"false";
		$only_local_account = $values["only_local_account"]?"true":"false";

		$command = "UPDATE loginlinks SET url='$url', groupid=$gid, only_local_account=$only_local_account, any_user=$any_user
			WHERE name='$link'";

		$database->query($command);

	}
	$database->close();
	force_refresh();
}
function get_links(){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$links = [];

	$command = "SELECT * FROM loginlinks ORDER BY protected desc, name";
	$res = $database->query($command);
	while($res && $row = $res->fetch_assoc()){
		$name = $row["name"];
		unset($row["name"]);
		$links[$name] = $row;
	}

	$database->close();
	return $links;
}
function delete_link($target){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$target = $database->real_escape_string($target);
	$command = "DELETE FROM loginlinks WHERE name='$target'";

	$database->query($command);
	$database->close();
	force_refresh();
}
function add_link(string $name, string $url, int $group = null, bool $any_user = false, bool $only_local_account = false){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	$name = $database->real_escape_string($name);
	$url = $database->real_escape_string($url);
	$group = ($group === null || $group === "")?"NULL":intval($group);
	$only_local_account = $only_local_account?"true":"false";
	$any_user = $any_user?"true":"false";

	$command = "INSERT INTO loginlinks (name, url, groupid, any_user, only_local_account)
		VALUES ('$name', '$url', $group, $any_user, $only_local_account)";


	$database->query($command);
	$database->close();
	force_refresh();
}
function force_refresh($target='ALL'){
	if($_SESSION['force_sync']){
		$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
		$target = $database->real_escape_string($target);
		$command = "INSERT INTO refresh (target, older_than) VALUES ('$target', ".time();
		$command .= ") ON DUPLICATE KEY UPDATE older_than = VALUES(older_than)";
		$database->query($command);
		$timeout = time() - (10*24*60*60); // Ten days
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
			$command = "SELECT older_than FROM refresh WHERE target='".$_SESSION['user']["userid"]."'";
			$res = $database->query($command);
			if($res && $res = $res->fetch_assoc()){
				$olderthan = $res["older_than"];
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
function has_permissions(array $default_permissions=[], bool $current_url=true){
	if(!isset($_SESSION["user"])){
		return false;
	}else if($_SESSION["user"]["superuser"]){
		return true;
	}
	if(!$current_url){
		$local_restrictions = $default_permissions["only_local_account"]?$_SESSION["user"]["local_account"]:true;
		if($default_permissions["any_user"] && $local_restrictions){
			return true;
		}
		if(isset($_SESSION["user"]["groups"][$default_permissions["groupid"]]) && $local_restrictions){
			return true;
		}
	}
	foreach($_SESSION["headerLink"] as $hlink){
		if($current_url && $_SERVER["REQUEST_URI"] == $hlink["url"]){
			return has_permissions($hlink, false);
		}
	}

	return false;
}
function queue_message(string $message, string $type){
	$_SESSION["messages"][] = ["type"=>$type, "message"=>$message];
}
function send_messages(){
	echo "<div id='messages'>";
	foreach($_SESSION["messages"] as $mess){
		echo "<div class='".$mess["type"]."'><div class='clear_message'>️</div>".$mess["message"]."</div>";
	}
	$_SESSION["messages"] = [];
	echo "</div>";
}

function get_dir($path){
	$files = array();
	foreach(scandir($path) as $file){
		if($file === "." || $file === ".."){
			continue;
		}
		if(is_dir($path . '/' . $file)){
			$files[$file] = get_dir($path.'/'.$file);
		}else{
			$files[$file] = mime_content_type($path.'/'.$file);
		}
	}
	return $files;
}
function get_all_groups(){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$command = "SELECT groupid, name, description, protected FROM groups ORDER BY name";
	$res = $database->query($command);
	$groups = [];
	while($group = $res->fetch_assoc()){
		$groups[$group["groupid"]] = ["name"=>$group["name"], "protected"=>boolval($group["protected"]), "description"=>$group["description"]];
	}
	$database->close();
	return $groups;
}
function get_log_count(string $search = NULL, string $log_type = NULL){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$where = "";
	if($search){
		$search = $database->real_escape_string($search);
		$where = "WHERE info RLIKE '$search'";
	}
	if($log_type){
		$log_type = $database->real_escape_string($log_type);
		$where .= ($where==""?"WHERE":"AND")." uid='$log_type'";
	}
	$command = "SELECT COUNT(1) AS log_count FROM logging $where";
	$res = $database->query($command);
	$count = 0;
	if($res && $res = $res->fetch_assoc()){
		$count = $res["log_count"];
	}
	$database->close();
	return $count;
}
function get_logs(int $max_logs = NULL, string $log_type = NULL, string $search = NULL, int $start_offset = 0){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$where = "";
	if($search){
		$search = $database->real_escape_string($search);
		$where = "WHERE info RLIKE '$search'";
	}
	if($log_type){
		$log_type = $database->real_escape_string($log_type);
		$where .= ($where==""?"WHERE":"AND")." uid='$log_type'";
	}
	$command = "SELECT * FROM logging $where ORDER BY timestamp DESC, id DESC";
	if($max_logs !== null){
		$command.=" LIMIT $max_logs OFFSET $start_offset";
	}
	$logs = [];
	$res = $database->query($command);
	while($res && $log = $res->fetch_assoc()){
		$logs[] = $log;
	}
	$database->close();
	return $logs;
}
function get_log_types(string $search = Null){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$where = "";
	if($search){
		$search = $database->real_escape_string($search);
		$where = "WHERE info RLIKE '$search'";
	}
	$command = "SELECT DISTINCT uid FROM logging $where ORDER BY uid";
	$types = [];
	$res = $database->query($command);
	while($res && $row = $res->fetch_assoc()){
		$types[] = $row["uid"];
	}
	$database->close();
	return $types;
}
function create_log(string $uid, string $message, mysqli $database = NULL){
	$close_database = ($database === NULL);
	if($_SESSION["logging_enabled"]){
		if($close_database){
			$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
		}
		$uid = $database->real_escape_string($uid);
		$message = $database->real_escape_string($message);
		$time = time();
		$command = "INSERT INTO logging (uid, timestamp, info) VALUES ('$uid', $time, '$message')";
		$database->query($command);
		if($close_database){
			$database->close();
		}
	}
}
function save_referer(){
	if(isset($_SERVER["HTTP_REFERER"])){
		$referer = parse_url($_SERVER["HTTP_REFERER"]);
		if($referer["host"] != $_SERVER["HTTP_HOST"]){
			create_log("External Referer", "IP: ".$_SERVER["REMOTE_ADDR"].", From: '".$_SERVER["HTTP_REFERER"]."'");
		}
	}
}
function export_database(array $table_list = []){
	$db_data = [];
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

	if(sizeof($table_list) == 0){
		$command = "show tables";
		$res = $database->query($command);
		while($res && $tbl = $res->fetch_array()){
			$table_list[] = $tbl[0];
		}
	}
	foreach($table_list as $table_name){
		$table_name = $database->real_escape_string($table_name);

		$command = "SHOW CREATE TABLE `$table_name`";
		$res = $database->query($command);

		if($res && $res = $res->fetch_assoc()){
			$db_data[$table_name]["meta_data"] = $res["Create Table"];
		}
		$db_data[$table_name]["data"] = [];

		$command = "SELECT * FROM `$table_name`";
		$res = $database->query($command);
		while($res && $row = $res->fetch_assoc()){
			$db_data[$table_name]["data"][] = $row;
		}
	}


	$database->close();
	return json_encode($db_data);
	return $db_data;
}
// A recursive function to take the diff matrix and
// digest it into a list of additions and deletions
// to be processed into a final RCS like diff.
function create_diff($c, $a, $b, $i, $j){
	$res = [];
	if($i>0 && $j>0 && $a[$i-1] == $b[$j-1]){
		$res = array_merge($res, create_diff($c, $a, $b, $i-1, $j-1));
	}else if($j > 0 && ($i == 0 || $c[$i][$j-1] >= $c[$i-1][$j])){
		$res = array_merge($res, create_diff($c, $a, $b, $i, $j-1));
		$last = end($res);
		if($last && $last[0] == 'a' && $last[1] == $i){
			$res[sizeof($res)-1][2]++;
			$res[sizeof($res)-1][3].="\n".$b[$j-1];
		}else
			array_push($res, ['a', $i, 1, $b[$j-1]]);
	}else if($i > 0 && ($j == 0 || $c[$i][$j-1] < $c[$i-1][$j])){
		$res = array_merge($res, create_diff($c, $a, $b, $i-1, $j));
		$last = end($res);
		if($last && $last[0] == 'd' && $last[1]+$last[2] == $i)
			$res[sizeof($res)-1][2]++;
		else
			array_push($res, ['d', $i, 1]);
	}
	return $res;
}

// This creates a largest common string matrix, creates a diff from it, 
// then returns a RCS style diff string.
function compute_diff($a, $b){
	$a = preg_split("/\n/", $a);
	$b = preg_split("/\n/", $b);
	// Create LCS matrix
	$c = [];
	for($i = 0; $i <= sizeof($a); $i++){
		$c[$i] = array(0);
		for($j = 0; $j <= sizeof($b); $j++){
			if($i == 0 || $j == 0)
				$c[$i][$j] = 0;
			else if($a[$i-1] == $b[$j-1])
				$c[$i][$j] = $c[$i-1][$j-1]+1;
			else
				$c[$i][$j] = max($c[$i][$j-1], $c[$i-1][$j]);
		}
	}

	$c = create_diff($c, $a, $b, sizeof($a), sizeof($b));

	$rcs_diff = "";
	foreach($c as $val){
		$rcs_diff.="$val[0]$val[1] $val[2]".($val[0]=='a'?"\n$val[3]":"")."\n";
	}
	$rcs_diff = substr($rcs_diff, 0, -1);
	return $rcs_diff;
}

function parse_diff($diff){
	$parsed = [];
	$lines = preg_split("/\n/", $diff);
	$add_cnt = 0;
	foreach($lines as $line){
		if($add_cnt == 0 && $line){
			preg_match("/^([ad])(\d+) (\d+)$/", $line, $gps);
			array_push($parsed, [$gps[1], $gps[2], $gps[3]]);
			if($gps[1] == 'a'){
				$add_cnt = $gps[3];
				array_push($parsed[sizeof($parsed)-1], []);
			}
		}else if($add_cnt){
			$add_cnt--;
			array_push($parsed[sizeof($parsed)-1][3], $line);
		}
	}
	return $parsed;
}

function apply_diff($text, $diff){
	if(strpos($diff, "@text") === 0){
		return preg_split("/\n/", $diff, 2)[1];
	}
	$diff = array_reverse(parse_diff($diff));
	$lines = preg_split("/\n/", $text);

	foreach($diff as $dif){
		if($dif[0] == 'a')
			array_splice($lines, $dif[1], 0, $dif[3]);
		else
			array_splice($lines, $dif[1]-1, $dif[2], []);
	}
	return implode("\n", $lines);
}

function save_snapshot(string $text, string $key, string $note){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$key = $database->real_escape_string($key);
	$command = "SELECT * FROM version_control WHERE target_key='$key' ORDER BY timestamp DESC";
	$res = $database->query($command);
	if($res && $row = $res->fetch_assoc()){
		if(strpos($row['rcs_data'], "@text") === 0){
			$data = preg_split("/\n/", $row["rcs_data"], 2)[1];
			if(strcmp($data, $text) !== 0){
				$data = $database->real_escape_string(compute_diff($text, $data));
				$command = "UPDATE version_control SET rcs_data='$data' WHERE id=$row[id]";
				$database->query($command);
			}else{
				$database->close();
				return;
			}
		}
	}

	$text = $database->real_escape_string("@text\n$text");
	$note = $database->real_escape_string($note);
	$time = time();

	$command = "INSERT INTO version_control (target_key, timestamp, notes, rcs_data) VALUES ('$key',$time,'$note','$text')";
	$database->query($command);
	$id = $database->insert_id;


	$database->close();
	return $id;
}

function list_snapshots($key){
	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$key = $database->real_escape_string($key);
	$command = "SELECT id,timestamp,notes FROM version_control WHERE target_key='$key' ORDER BY timestamp DESC";
	$res = $database->query($command);
	$rows = [];
	while($row = $res->fetch_assoc()){
		array_push($rows, $row);
	}
	$database->close();
	return $rows;
}

function fetch_snapshot($id){
	$text = "";

	$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);
	$res = $database->query("SELECT target_key,timestamp FROM version_control WHERE id=$id");
	if($res && $res = $res->fetch_assoc()){
		$key = $database->real_escape_string($res["target_key"]);
		$timestamp = $res["timestamp"];
		$res = $database->query(
			"SELECT rcs_data FROM version_control WHERE target_key='$key' AND timestamp >= $timestamp ORDER BY timestamp DESC"
		);
		while($row = $res->fetch_assoc()){
			$text = apply_diff($text, $row["rcs_data"]);
		}
	}

	$database->close();
	return $text;
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
	<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content='text/html; charset=utf-8'>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="theme-color" content = "#222" />
		<link rel='icon' href='/content/favicon.png'>
		<title><?php echo $_SESSION['site'] . $second; ?></title>
		<?php echo isset($GLOBALS['header_info'])?$GLOBALS['header_info']:""; ?>
		<?php echo isset($_SESSION['header_ajax_ace'])?$_SESSION['header_ajax_ace']:""; ?>
		<?php echo isset($_SESSION['header_font_awesome'])?$_SESSION['header_font_awesome']:""; ?>
	</head>
	<body>
	<?php echo isset($GLOBALS['body_info'])?$GLOBALS['body_info']:"";
}

?>
