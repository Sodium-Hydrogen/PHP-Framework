<?php
/*

The first time this script is run it will setup the database and allow creating of a user.
After a user exists if the $_SESSION['setup'] is still true it will respond if a user isn't logged
in. Once a user is logged in it will allow someone to change various settings for the framework.

*/

require_once("resources/phpScripts/load.php");

if(($_SESSION['setup'] === true && get_url() == "home") || isset($_SESSION['user'])){
	if($_SERVER['REQUEST_METHOD'] == "GET"){
		if(empty($_SESSION["header_ajax_ace"]) || strlen($_SESSION["header_ajax_ace"]) < 10){
			queue_message("Unable to load ajax ace editor.<a href=/content.php/settings#header_ajax_ace> Please enter the header for it. </a>", "warning");
		}
		if(empty($_SESSION["header_font_awesome"]) || strlen($_SESSION["header_font_awesome"]) < 10){
			queue_message("Unable to load font awesome.<a href=/content.php/settings#header_font_awesome> Please enter the header for it. </a>", "warning");
		}
	}
	load_logged_header();
	queue_header("<link rel='stylesheet' href='/resources/backend.css'>");
	queue_header("<script src='/resources/backend.js'></script>");
	queue_header("<link rel='stylesheet' href='/resources/settings.css'>");
	request_page_head(get_url() == "home" ? "Content" : ucwords(get_url()));

	if(get_url() === "home"){
		if(empty($_SESSION['user'])){
			$database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

			if ($database->connect_errno) {
				queue_message("Failed to connect to MySQL: (" . $database->connect_errno . ") ", "error");
			}else{
				if($_SESSION['debug']){
					queue_message("Success: ".$database->host_info, "success");
				}

				$database->close();
				if(!setup_database()){
					queue_message("Database setup failed", "error");
					send_messages();
					exit();
				}else{
          queue_message("Database created", "success");
        }

				$users = view_users();
				if(count($users) == 0){
					if(isset($_POST["username"]) && isset($_POST["password"]) &&
					isset($_POST['check']) && isset($_POST["dbpassword"])){
						$createAccount = true;
						if($_POST['password'] != $_POST['check']){
							queue_message("Passwords do not match", "error");
							$createAccount = false;
						}else if($createAccount && $_POST['dbpassword'] != $_SESSION['dbPass']){
							queue_message("Unable to create account, please try again", "error");
							$createAccount = false;
						}
						if($createAccount === true){
							if(create_account($_POST["username"],$_POST["password"], true)){
								echo "<div class='loginBox'>";
								echo "User successfully written <br>";
								echo "Please login</a>";
								echo "</div>";
								session_destroy();
								exit();
							}else{
								queue_message("Error while creating account", "error");
							}
						}
					}
					send_messages();
					?>
					<div class="loginBox">
						<div class="logo">
						</div>
						<form method="post" >
							<h3>Create admin account</h3> <hr>
							<p><label for="username">User Name: </label> <input id="username" type="text" name="username" required></p>
							<p><label for="user_pass">Password: </label><input id="user_pass" type="password" name="password" required>
							<p><label for="user_pass_repeat">Retype Password: </label><input id="user_pass_repeat" type="password" name="check" required>
							<p><label for="db_pass">Database password for verification: </label><input id="db_pass" type="password" name="dbpassword" required>
							<p><input type="submit" value="Create Account">
						</form>
					</div>
					<?php
				}else{
					?>
					<div class="loginBox">
						Admin account already created please <a style="color: red" href="/login.php">login</a>
					</div>
					<?php
				}
			}
		}else if(has_permissions()){
      $pages = null;
      $footers = null;
      $files = null;
			$new_content = [];

      $post_type = null;
			if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST)){
				$post_type = "pages";
				if(isset($_POST["page_content_create"])){
					if(isset($_POST["new_page_content"]["parent"])){
						$id = add_content($_POST["new_page_content"], "page_content");
						$new_content["page_content"] = $id;
					}else{
						queue_message("Please specify at least one parent when creating new page content", "warning");
					}
				}else if(isset($_POST["page"]) && isset($_POST["page_order"])){
					$order = [];
					$cnt_page = "";
					$page_type = "";
					$at_least_one_visible = false;
					foreach($_POST['page_order'] as $ord){
						if(gettype($ord) == "array"){
							$key = array_keys($ord)[0];
							if($key == "page"){
								$cnt_page = $ord["page"];
								$size = sizeof($order);
								$order[$cnt_page] = ["header_content"=>[],"page_content"=>[]];
								$_POST["page"][$ord["page"]]["position"] = $size;
								$at_least_one_visible = $at_least_one_visible || $_POST["page"][$ord["page"]]["published"]=='1';
							}else{
								$size = sizeof($order[$cnt_page][$page_type]);
								$order[$cnt_page][$page_type] = $ord;
								if($key == "page_content"){
									$_POST[$key][$ord[$key]]["in_html_header"] = $page_type == "header_content";
								}
								$_POST[$key][$ord[$key]]["parent"] = $cnt_page;
								$_POST[$key][$ord[$key]]["position"] = $size;
							}
						}else{
							$page_type = $ord;
						}
					}
					if(!$at_least_one_visible){
						queue_message("Please leave at least one page visible", "warning");
					}
					$cnt = get_all_pages(false);
					foreach($cnt as $key=>$entries){
						foreach($_POST[$key] as $id=>$content){
							$diff = array_diff_assoc($content, $entries[$id]);
							if($diff){
								if($key == "page"){
									if(!$at_least_one_visible){
										$content["published"] = $entries[$id]["published"];
									}
									update_page($id, $content);
								}else{
									update_content($id, $content["parent"], $content, $key=="post"?"posts":"page_content");
								}
							}
						}
					}

				}else if(isset($_POST["footer"])){
					$post_type = "footers";
					$cur_foot = '';
					$order = [];
					foreach($_POST["footer_order"] as $ord){
						if(isset($ord["footer"])){
							$_POST["footer"][$ord["footer"]]["position"] = sizeof($order);
							$cur_foot = $ord["footer"];
							$order[$ord["footer"]] = [];
						}else{
							$_POST["link"][$ord["link"]]["position"] = sizeof($order[$cur_foot]);
							$_POST["link"][$ord["link"]]["parent"] = $cur_foot;
							$order[$cur_foot][] = $ord["link"];
						}
					}
					$footrs = get_all_footers(false);
					foreach($footrs as $key=>$entries){
						foreach($_POST[$key] as $id=>$content){
							$diff = array_diff_assoc($content, $entries[$id]);
							if($diff){
								if($key == "footer"){
									update_footer($id, $content);
								}else{
									update_footer($id, $content, $content["parent"]);
								}
							}
						}
					}
				}
				if(isset($_POST["page_create"])){
					if($_POST["new_page"]["title"]){
						$id = add_page($_POST["new_page"]);
						$new_content["page"] = [$id];
						queue_message("Created page with title: '".$_POST['new_page']['title']."'", "success");
					}else{
						queue_message("A page title cannot be blank.", "warning");
					}
				}else if(isset($_POST["post_create"])){
					if($_POST["new_post"]["title"]){
						$id = add_content($_POST["new_post"], "posts", $_POST["new_post"]["parent"]);
						$new_content["post"] = [$id];
						queue_message("Created post with title: '".$_POST['new_post']['title']."'", "success");
					}else{
						queue_message("A post title cannot be blank.", "warning");
					}
				}else if(isset($_POST["remove_page"])){
					$pages = get_all_pages();
					$id = array_key_first($_POST["remove_page"]);
					if($id == "post"){
						$id = array_key_first($_POST["remove_page"]["post"]);
						delete_content($id, "posts");
						queue_message("Deleted post with title: '".$_POST["post"][$id]["title"]."'", "success");
						$pages = null;
					}else if($id == "page_content"){
						$id = array_key_first($_POST["remove_page"]["page_content"]);
						delete_content($id, "page_content");
						queue_message("Deleted content: '".$_POST["page_content"][$id]["content"]."'", "success");
						$pages = null;
					}else if(delete_page($id)){
						queue_message("Deleted page with title: '".$pages[$id]["title"]."'", "success");
						unset($pages[$id]);
					}
				}else if(isset($_POST["footer_create"])){
					$post_type = "footers";
					if($_POST["new_footer"]["title"]){
						$id = add_footer($_POST["new_footer"]);
						$new_content['footer'] = [$id];
						queue_message("Created new footer '".$_POST["new_footer"]["title"]."'", "success");
					}else{
						queue_message("Please enter a title when creating a footer.", "warning");
					}
				}else if(isset($_POST["link_create"])){
					$post_type = "footers";
					$id = add_footer($_POST["new_link"], $_POST["new_link"]["parent"]);
					$new_content['link'] = [$id];
					queue_message("Created new link to '".$_POST["new_link"]["url"]."'", "success");
				}else if(isset($_POST["remove_footer"])){
					$post_type = "footers";
					$id = array_key_first($_POST["remove_footer"]);
					if($id == "link"){
						$id = array_key_first($_POST["remove_footer"][$id]);
						delete_footer($id, true);
						queue_message("Delete link to '".$_POST["link"][$id]["url"]."'", "success");
					}else{
						delete_footer($id);
						queue_message("Delete footer '".$_POST["footer"][$id]["title"]."'", "success");
					}
				}else if(isset($_POST["footers"])){
					$post_type = "footers";
				}else if(isset($_POST["files"])){
					$post_type = "files";
				}

			}

			if($_SERVER["HTTP_ACCEPT"] != "application/json"){
				send_messages();
        draw_content();
      }else{
        $arr = ["messages"=>$_SESSION["messages"],'post'=>$_POST,'new_content'=>$new_content];
        if($post_type === null || $post_type == "pages"){
          $pages = $pages?$pages:get_all_pages();
          $arr = array_merge($arr,["pages"=>$pages,"pages_order"=>array_keys($pages)]);
        }
        if($post_type === null || $post_type == "footers"){
          $footers = $footers?$footers:get_all_footers();
          $arr = array_merge($arr, ["footers"=>$footers,"footers_order"=>array_keys($footers)]);
        }
        if($post_type === null || $post_type == "files"){
          $files = $files?$files:get_dir($_SESSION["uploads_dir"]);
					$allowed_uploads = explode(",",str_replace(" ", "", strtolower($_SESSION["allowed_uploads"])));
					sort($allowed_uploads);
          $arr = array_merge($arr, ["files"=>$files, "root_dir"=>$_SESSION["uploads_dir"],
						"allowed_type"=>$allowed_uploads
					]);
        }
        print json_encode($arr);
        $_SESSION["messages"] = [];
      }	
		}

	}else if(get_url() === "snapshots" && has_permissions()){
		// if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST)){
		// if(isset($_POST[""]))

	}else if(get_url() === "settings" && has_permissions()){
		$configs = get_configs();
		$links = get_links();

		$logs = null;
		$log_size = null;
		$log_uids = null;
		$max_logs = 100;
		$logs_offset = 0;

		$created= ["setting"=>[],"link"=>[]];
		$types = ["BOOL", "INT", "STRING"];
		$post_type = Null;
		if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST)){
			$post_type = "settings";
			if(isset($_POST["new_setting_submit"])){
				if(isset($_POST["new_setting"])){
					$dumy_setting = ["name"=>"", "type"=>"", "description"=>"", "value"=>""];
					$ns = array_merge($dumy_setting, $_POST["new_setting"]);
					if($ns["name"] != "" && empty($configs[$ns["name"]]) && array_search($ns["type"], $types) !== false){
						create_config($ns["name"], $ns["value"], $ns["type"], $ns["description"]);
						queue_message("Created new setting: '".$ns["name"]."'", "success");
						$created["setting"][] = $ns["name"];
						$configs = get_configs();
					}else if(isset($configs[$ns["name"]])){
						queue_message("Cannot create a setting with duplicate name: '".$ns["name"]."'", "error");
					}
				}
			}else if(isset($_POST["remove_settings"])){
				foreach($_POST["remove_settings"] as $setting => $_){
					if(isset($configs[$setting]) && !$configs[$setting]["protected"]){
						delete_config($setting);
						queue_message("Deleted setting '$setting'", "success");
						unset($configs[$setting]);
					}else if(isset($configs[$setting])){
						queue_message("Unable to delete protected setting with name: '$setting'", "error");
					}
				}
			}else if(isset($_POST["setting"])){
				foreach($_POST["setting"] as $name => $value){
					if($configs[$name]["value"] != $value){
						update_configs([$name=>$value]);
						$configs[$name]["value"] = $value;
						queue_message("Updated setting: '$name'", "success");
					}
				}
			}else if(isset($_POST["new_link_submit"])){
				$post_type = "login_links";
				if(isset($_POST["new_link"])){
					$dumy_link = ["name"=>"", "url"=>"","permissions"=>NULL,"only_local_account"=>""];
					$ln = array_merge($dumy_link, $_POST["new_link"]);
					if($ln["name"] != "" && empty($links[$ln["name"]])){
						$group = ($ln["permissions"] === "" || $ln["permissions"] == "any_user")?null:$ln["permissions"];
						$any_user = ($ln["permissions"] == "any_user");
						add_link($ln["name"], $ln["url"], $group, $any_user, $ln["only_local_account"]);
						queue_message("Created login link: '".$ln["name"]."'", "success");
						$created["link"][] = $ln["name"];
						$links = get_links();
					}else if($ln["name"] != ""){
						queue_message("Cannot create a link with duplicate name: '".$ln["name"]."'", "error");
					}
				}
			}else if(isset($_POST["remove_links"])){
				$post_type = "login_links";
				foreach($_POST["remove_links"] as $link => $_){
					if(isset($links[$link]) && !$links[$link]["protected"]){
						delete_link($link);
						unset($links[$link]);
						queue_message("Deleted login link '$link'", "success");
					}else if(isset($links[$link])){
						queue_message("Unable to delete protected login link '$link'", "error");
					}
				}
			}else if(isset($_POST["link"])){
				$post_type = "login_links";
				function link_comp($a, $b){return $a == $b?0:1;}
				foreach($_POST["link"] as $link => $values){
					$values["any_user"] = ($values["permissions"] == "any_user");
					$values["groupid"] = ($values["permissions"] === "" || $values["permissions"] == "any_user")?null:$values["permissions"];
					$values["url"] = $links[$link]["protected"]?$links[$link]["url"]:$values["url"];
					$values["protected"] = $links[$link]["protected"];
					
					if(array_udiff_assoc($links[$link], $values, "link_comp")){
						if($values["protected"]){
							queue_message("Changed access permissions to built in login link '$link'", "warning");
						}
						update_links([$link=>$values]);
					}
					$links = get_links();
				}
			}else if(isset($_POST["login_links"])){ $post_type = "login_links";
			}else if(isset($_POST["logs"])){ $post_type = "logs";
			}else if(isset($_POST["search"]) || isset($_POST["uid"]) || isset($_POST["logs_page"])){
				$logs_offset = isset($_POST["logs_page"])?intval($_POST["logs_page"]):$logs_offset;
				$search = $_POST["search"] == ""?NULL:$_POST["search"];
				$uid = $_POST["uid"] == ""?NULL:$_POST["uid"];
				$post_type = "logs";
				$logs = get_logs($max_logs, $uid, $search, $logs_offset);
				$log_size = get_log_count($search, $uid);
				$log_uids = get_log_types($search);
			}
		}
		if($_SERVER["HTTP_ACCEPT"] != "application/json"){
			send_messages();
			draw_settings();
		}else{
			$arr = ["messages"=>$_SESSION["messages"]];
			if($post_type === null || $post_type == "login_links"){
				$arr = array_merge($arr, [
					"links" => $links,
					"links_order" => array_keys($links),
					"groups" => get_all_groups(),
				]);
			}
			if($post_type === null || $post_type == "settings"){
				$arr = array_merge($arr,[
					"settings"=> $configs,
					"settings_order" => array_keys($configs),
				]);
			}
			if($post_type === null || $post_type == "logs"){
				if($logs === null){$logs = get_logs($max_logs, NULL, NULL, $logs_offset);}
				if($log_uids === null){$log_uids = get_log_types();}
				if($log_size === null){$log_size = get_log_count();}
				$page_cnt = ceil($log_size/$max_logs);
				$arr = array_merge($arr, [
					"uid" => $log_uids,
					"logs" => $logs,
					"count" => ["all_items"=>$log_size,"pages"=>$page_cnt,"items_per_page"=>$max_logs],
					"offset" => $logs_offset,
				]);
			}
			print json_encode($arr);
			$_SESSION["messages"] = [];
		}

	}else{
		header("location: ".$_SERVER['SCRIPT_NAME']);
	}
}else{
	$_SESSION['error'] = '404';
	require("index.php");
}

function draw_settings(){
	?>
		<div id="header-tabs"><div id='mobile_menu'><div></div><div></div><div></div></div>
      <div class="tab current">Settings</div><div class="tab">Login Links</div><div class='tab'>Logs</div></div>
		<div class="content">
			<form class="tab_content no-enter hidden" id="tab_settings" method='post' >
			<div class='table-wrapper'><div class='tbody invert-rows'></div></div>
			<div class='row left'><div class='section'><button type='button' class='show_popup' id="new_setting">New Setting</button>
				<input type="submit" class="positive"></div></div>
			<div class="hidden hidden_popup" id="new_setting_inputs">
				<div class="loginBox"> <div class="clear_message"></div>
				<p><label for="new_setting_name">Setting Name: </label><input type="text" name="new_setting[name]" id="new_setting_name"></p>
				<p><label for="new_setting_type">Type: </label><select name="new_setting[type]" id="new_setting_type">
					<option>BOOL</option> <option>INT</option> <option selected>STRING</option> <option>BIG STRING</option>
				</select></p>
				<p><label for="new_setting_value">Value: </label>
					<input type="hidden" name="new_setting[value]" value="0">
					<input type="text" name="new_setting[value]" id="new_setting_value"></p>
				<p><label for="new_setting_description">Description: </label><input type="text" name="new_setting[description]" id="new_setting_description"></p>
				<input type="submit" name="new_setting_submit" value="Create" class="positive" id="new_setting_submit">
			</div></div>
			</form>

			<form id="tab_login_links" class="hidden tab_content" method="post" ><div class="table-wrapper">
				<div class='thead'><div class='tr'>
					<div class='th'>Name</div> <div class='th'>URL</div> <div class='th'></div> <div class='th'>Local only
						<div class="help-msg hidden">Regardless of the permissions set this link will only allow local accounts to view it.</div>
					</div>
					<div class='th'>Permission</div>
				</div></div>
				<div class='tbody'></div></div>
			<div class='row left'><div class='section'><button type='button' class='show_popup' id="new_link">New Login Link</button>
				<input type="submit" class="positive"></div></div>
			<div class="hidden hidden_popup" id="new_link_inputs">
				<div class="loginBox"> <div class="clear_message"></div>
				<p><label for="new_link_name">Link Name: </label><input type="text" name="new_link[name]" id="new_link_name"></p>
				<p><label for="new_link_url">URL: </label><input type="text" name="new_link[url]" id="new_link_type"></p>
				<p><label for="new_link_permissions">Permissions: </label><select name='new_link[permissions]' id='new_link_permissions'>
					<option></option> <option value="any_user">Any User</option> <optgroup label="Groups"> </optgroup> </select></p>
				<p><input type="hidden" name="new_link[only_local_account]" value=0>
					<label for="new_link_only_local_account">Local Only: </label><input type="checkbox" name="new_link[only_local_account]" id="new_link_only_local_account" value=1></p>
				<input type="submit" name="new_link_submit" value="Create" class="positive" id="new_link_submit">
			</div></div>
			</form>
      <?php if($_SESSION["logging_enabled"]){?>
			<form class="hidden tab_content" id='tab_logs' method="POST">
				<div class="row"><div class="section col2-wrap">
					<label class='no-break' for="logs_uid">Log Type: </label><select name="uid" id='logs_uid'><option value="">------</option></select>
					<label class='no-break' for='logs_search'>Search: </label><input type='text' name='search' id='logs_search'>
					<input type='submit' value='Search'>
				</div> </div>
				<div id="logs_container"></div>
				<div id="logs_paging"></div>
      </form>
      <?php }else{ ?>
        <div class="hidden tab_content" id='tab_logs'>
          Logging Disabled. To enable logging please
          <a href='#logging_enabled'>enable it in settings</a> and reload.
        </div>
      <?php } ?>
      <div class="tab_content">Something went wrong. The javascript didn't work.</div>
		</div>

	<?php
}
function draw_content(){?>
		<div id="header-tabs"><div id='mobile_menu'><div></div><div></div><div></div></div>
      <div class="tab current">Pages</div><div class="tab">Footers</div><div class="tab">Files</div></div>
		<div class="content">
			<form class="tab_content" id="tab_pages" method='post' >
				<div class='row'> <div class='section'>
					<button type='button' class='collapse_all'>Collapse All</button>
				</div><div class='section'>
					<button type='button' class='show_popup' id='new_page'>New Page</button>
					<button type='button' class='show_popup' id='new_post'>New Post</button>
					<button type='button' class='show_popup' id='new_page_content'>New Page Content</button>
				</div><div class='section'>
					<input type='submit' value='Save' name='pages_save' class='positive'>
				</div></div>
				<div class='ordering'></div>
				<!-- Hidden inputs -->
				<div class="hidden hidden_popup" id="new_page_inputs">
					<div class="loginBox"> <div class="clear_message"></div>
						<p><input type='text' name='new_page[title]' placeholder="Page Title"></p>
						<p><label for='new_page_direction'>Direction: </label><select name='new_page[direction]'>
							<option>Column</option> <option>Row</option>
						</select></p>
						<input type='submit' name='page_create' id='page_create' value='Create' class='positive'>
				</div></div>
				<div class="hidden hidden_popup" id="new_post_inputs">
					<div class="loginBox"> <div class="clear_message"></div>
						<p><input type='text' name='new_post[title]' placeholder="Post Title"></p>
						<p><input type='text' name='new_post[picture]' placeholder="Preview Image"></p>
						<p><label for='new_post_parent'>Parent Page: </label>
							<select id='new_post_parent' name='new_post[parent]'></select></p>
						<input type='submit' name='post_create' id='post_create' value='Create' class='positive'>
				</div></div>
				<div class="hidden hidden_popup" id="new_page_content_inputs">
					<div class="loginBox"> <div class="clear_message"></div>
						<p><input type='checkbox' id='new_page_content_clone' name='new_page_content[clone]'>
							<label for='new_page_content_clone'>Clone Page Content</label></p>
						<p class='hidden'><label for='new_page_content_source'>Source Content:</label>
							<select id='new_page_content_source' name='new_page_content[source]'><option value="">----</option></select></p>
						<p> <input type='hidden' name='new_page_content[in_html_header]' value='0'>
							<input type='checkbox' id='new_page_content_in_header' name='new_page_content[in_html_header]' value='1'>
							<label for='new_page_content_in_header'>In HTML header</label></p>
						<p>Target Page:</p><ul id='new_page_content_parents' class='no-bullets'></ul>
						<input type='submit' name='page_content_create' id='page_content_create' value='Create' class='positive'>
				</div></div>
			</form>
			<!-- Footer tab -->
			<form class="tab_content hidden" id="tab_footers" method='post' >
				<div class='row'> <div class='section'>
					<button type='button' class='collapse_all'>Collapse All</button>
				</div><div class='section'>
					<button type='button' class='show_popup' id='new_footer'>New Footer</button>
					<button type='button' class='show_popup' id='new_link'>New Link</button>
				</div><div class='section'>
					<input type='submit' value='Save' name='footers_save' class='positive'>
				</div></div>
				<div class='ordering'></div>
				<div class="hidden hidden_popup" id="new_footer_inputs">
					<div class="loginBox"> <div class="clear_message"></div>
					<p><label for='new_footer_title'>Title:</label>
						<input type='text' name='new_footer[title]' id='new_footer_title'>
					</p>
					<input type='submit' name='footer_create' value="Create" class='positive'>
				</div></div>
				<div class="hidden hidden_popup" id="new_link_inputs">
					<div class="loginBox"> <div class="clear_message"></div>
						<p class='no-break'><label for='new_link_url'>Url: </label>
							<input type='url' id='new_link_url' name='new_link[url]'></p>
						<p><label for='new_link_parent'>Parent Footer: </label>
							<select id='new_link_parent' name='new_link[parent]'></select></p>
						<input type='submit' name='link_create' id='link_create' value='Create' class='positive'>
				</div></div>
			</form>
			<!-- files tab -->
			<form class="tab_content hidden" id="tab_files" method='post' >
				<div class='file-actions'>
					<div class='section'>
						<label for="file_upload"><i class="fas fa-upload"></i><span>Upload</span></label><input type='file' id='file_upload' name='file_upload'>
					</div><div class='column'>
						<h3>Allowed File Types<div class="help-msg hidden">
								This is a list of file extensions that are allowed to be modified from this web editor.
								This list can be modified by changing the setting:
								<a href="/content.php/settings#allowed_uploads" target="_blank"><code>allowed_uploads</code></a>.
							</div>:</h3>
						<p id='allowed_files'></p>
					</div><div class='section'>
							<button type="button" id="add_new_folder" class='positive'><i class='fas fa-folder-plus'></i></button>
							<button type="button" id="add_new_file" class='positive'><i class='fas fa-plus-square'></i></button>
							<button type="button" id="remove_item" class='negative'><i class='fas fa-trash-alt'></i></button>
					</div>
					<div class='file-tree'></div>
				</div>
				<div class='editor-container'>
					<div class='row'><input class='positive' type='submit' value='Save' name='files_save'><div id='file-name'></div></div>
					<div id='file-editor'></div>
				</div>
			</form>
		</div>
<?php }
?>
