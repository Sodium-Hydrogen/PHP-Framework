<?php
/*

The first time this script is run it will setup the database and allow creating of a user.
After a user exists if the $_SESSION['setup'] is still true it will respond if a user isn't logged
in. Once a user is logged in it will allow someone to change various settings for the framework.

*/


require_once("resources/phpScripts/load.php");

if(($_SESSION['setup'] === true && get_url() == "home") || isset($_SESSION['user'])){
  load_logged_header();
  queue_header("<link rel='stylesheet' href='/resources/settings.css'>");
  queue_header("<script src='/resources/settings.js'></script>");
  request_page_head("Config");

  $min_permis = 90;
  $permis_map = Array(
    "newsetting" => "settings",
    "newloginlink" => "loginlinks"
  );
  if(isset($_SESSION["headerLink"])){
    $target = get_url();
    if(isset($permis_map[$target])){
      $target = $permis_map[$target];
    }
    $target = "/config.php/".$target;
    foreach($_SESSION["headerLink"] as $link){
      if($link['url'] == $target){
        $min_permis = $link['min_permission'];
      }
    }
  }

  if(get_url() === "home"){
    if(empty($_SESSION['user'])){
      $database = new mysqli("localhost", $_SESSION['dbUser'], $_SESSION['dbPass'], $_SESSION['db']);

      if ($database->connect_errno) {
        echo "<div class='warning'>Failed to connect to MySQL: (" . $database->connect_errno . ") ";
        echo $database->connect_error . "</div>";
      }else{
        if($_SESSION['debug']){
          echo "<div class='success'>Success: ";
          echo $database->host_info . "</div>";
        }

        $database->close();
        setup_database();

        //$command = "SELECT username FROM accounts";
        //$outPut = mysqli_query($database, $command);
        //$results = $outPut->fetch_assoc();
        $users = view_users();
        if(count($users) == 0){
          if(isset($_POST["username"]) && isset($_POST["password"]) &&
          isset($_POST['check']) && isset($_POST["dbpassword"])){
            $createAccount = true;
            if($_POST['password'] != $_POST['check']){
              echo "<div class='warning'>Passwords do not match</div>";
              $createAccount = false;
            }else if($createAccount && $_POST['dbpassword'] != $_SESSION['dbPass']){
              echo "<div class='warning'>Unable to create account, please try again</div>";
              $createAccount = false;
            }
            if($createAccount === true){
              if(create_account($_POST["username"],$_POST["password"], 100)){
                echo "<div class='loginBox'>";
                echo "User successfully written <br>";
                echo "Please login</a>";
                echo "</div>";
                session_destroy();
                exit();
              }else{
                echo "<div class='warning'>Error while creating account</div>";
              }
            }
          }
          ?>
          <div class="loginBox">
            <div class="logo">
            </div>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
              <h3>Create admin account</h3> <hr>
              User Name:<br><input type="text" name="username" required><br>
              Password:<br><input type="password" name="password" required><br>
              Retype Password:<br><input type="password" name="check" required><br>
              Database password for verification:<br><input type="password" name="dbpassword" required><br>
              <input type="submit" value="Create Account">
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
    }else{
      ?>
      <div class="loginBox">
        Admin account already created.
      </div>
      <?php
    }
  }else if(get_url() == "newsetting" && $_SESSION['permissions'] >= $min_permis){
    if(isset($_GET["errormsg"])){
      echo "<div class='warning'>";
      echo $_GET["errormsg"];
      echo "</div>";
    }
    show_new_setting_page();
  }else if(get_url() === "settings" && $_SESSION["permissions"] >= $min_permis){
    $settings = get_configs();
    if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])){
      if($_POST['action'] == "Save Settings"){
        unset($_POST['action']);
        update_configs($_POST);
        load_variables_from_database();
        header("refresh:0");
      }else if($_POST['action'] == "delete-setting"){
        delete_config($_POST['delete']);
				echo "<div class='success'>Deleted setting: " . $_POST['delete'] . "</div>";
      }else if($_POST['action'] == "Add Setting"){
        if(isset($_SESSION[$_POST["setting"]])){
          header("location: /config.php/newsetting?errormsg=Setting already exists");
        }else{
          create_config($_POST['setting'], $_POST[$_POST['type']], $_POST['type'], $_POST['description']);
					$newSetting = $_POST["setting"];
					echo "<div class='success'>Created new setting: $newSetting</div>";
        }
      }
			$settings = get_configs();
    }
    create_settings_page($settings, isset($newSetting)? $newSetting: null);
  }else if(get_url() === "pages" && $_SESSION["permissions"] >= $min_permis){
    if(isset($_POST['action'])){
      $action = $_POST['action'];
      unset($_POST['action']);

      switch($action){
        case "Save Page Order":
          update_order($_POST);
          break;
        case "Save Section Order":
          update_order($_POST);
          break;
        case "Save Page":
          $_POST['name'] = $_GET['page'];
          save_page($_POST);
          break;
        case "Create New Page":
          if(add_page($_POST)){
            $_GET['success'] = "Created page: " . $_POST['name'];
            $newValue = $_POST['name'];
          }else{
            $_GET['warning'] = "Unable to create page: ".$_POST['name']."<br> It may already exist.";
          }
          break;
        case "Delete Page":
          if(delete_page($_POST['value'])){
            $_GET['success'] = "Deleted page: ".$_POST['value'];
          }else{
            $_GET['warning'] = "Unable to delete page: ".$_POST['value'];
          }
          break;
        case "Create New Post":
          if(add_post($_POST, $_GET['page'])){
            $_GET['success'] = "Created post: ".$_POST['name'];
            $newValue = $_POST['name'];
          }else{
            $_GET['warning'] = "Unable to create post: ".$_POST['name']."<br> It may already exist.";
          }
          break;
        case "Save Post":
          $_POST['name'] = $_GET['post'];
          save_post($_POST, $_GET['page']);
          break;
        case "Delete Post":
          delete_post($_POST['value'], $_GET['page']);
          $_GET['success'] = "Deleted post: ".$_POST['value'];
          break;
        case "Save Link":
          $_POST['name'] = $_GET['link'];
          save_footer($_POST, $_GET['section']);
          break;
        case "Create New Link":
          if(add_footer($_POST, $_GET['section'])){
            $_GET['success'] = "Created link: ".$_POST['name'];
            $newValue = $_POST['name'];
          }else{
            $_GET['warning'] = "Unable to create link: ".$_POST['name']."<br> It may already exist.";
          }
          break;
        case "Delete Link":
          delete_footer($_POST['value'], $_GET['section']);
          $_GET['success'] = "Deleted link: ".$_POST['value'];
          break;
        case "Save Section":
          $_POST['name'] = $_GET['section'];
          save_footer($_POST);
          break;
        case "Create New Section":
          if(add_footer($_POST)){
            $_GET['success'] = "Created section: ".$_POST['name'];
            $newValue = $_POST['name'];
          }else{
            $_GET['warning'] = "Unable to create section: ".$_POST['name']."<br> It may already exist.";
          }
          break;
        case "Delete Section":
          delete_footer($_POST['value']);
          $_GET['success'] = "Deleted section: ".$_POST['value'];
          break;
      }
      load_variables_from_database();
    }
    content_management_page(isset($newValue) ? $newValue: null);
  }else if(get_url() == "newloginlink" && $_SESSION['permissions'] >= $min_permis){
    create_login_link();
  }else if(get_url() == "loginlinks" && $_SESSION['permissions'] >= $min_permis){
    if(isset($_POST['action'])){
      $action = $_POST['action'];
      unset($_POST['action']);
      if($action == "Save Changes"){
        update_links($_POST);
        load_variables_from_database();
        header("refresh:0");
      }else if($action == "Delete Link"){
        if(delete_link($_POST['delete'])){
          $_GET['success'] = "Deleted header link: ".$_POST['delete'];
          load_variables_from_database();
        }else{
          $_GET['warning'] = "Unable to delete header link: ".$_POST['delete'];
        }
      }else if($action == "Add Login Link"){
        if(add_link($_POST)){
          $_GET['success'] = "Created header link: ".$_POST['name'];
          $newValue = $_POST['name'];
          load_variables_from_database();
        }else{
          header("location: /config.php/newloginlink?errormsg=Header link already exists");
        }
      }
    }
    loginlink_management_page(isset($newValue) ? $newValue: null);
  }else if(get_url() == "values" && $_SESSION['permissions'] >= $min_permis){
    echo "<style>body{align-items:unset}</style>";
    echo "<pre>";
    print_r(get_configs());
    echo "<hr>";
    print_r($_SERVER);
    echo "<hr>";
    print_r($_SESSION);
    echo "</pre>";
  }else{
    header("location: ".$_SERVER['SCRIPT_NAME']);
  }
}else{
  $_GET['error'] = '404';
  require("index.php");
}

function loginlink_management_page($newValue){
  if(isset($_GET['success'])){
    echo "<div class='success'>".$_GET['success']."</div>";
  }
  if(isset($_GET['warning'])){
    echo "<div class='warning'>".$_GET['warning']."</div>";
  }
  ?>
  <div class="users left-align">
    <!-- <div class='row'><h2>Login Header Links</h2></div><hr> -->
    <div class='row specialRow'>
      <div class='title'><div class='headCell colThree'>Name</div></div>
      <div class='information'><div class='headCell colThree'>Min Permissions</div>
      <div class='headCell colThree'>Url</div></div>
    </div><hr>
    <form method='post' id='link-form' onsubmit="promptSubmit(event)">
      <?php
      foreach($_SESSION['headerLink'] as $index => $link){
        $edit = $link["min_permission"] <= $_SESSION['permissions'];
        echo "<div class='row".(($index%2==1)?" odd-row":"");
        if(!empty($newValue) && $newValue == $link['name']){
          echo " new-user";
        }
        echo "'><div class='title'>";
        if(!$link['protected']){
          echo "<div class='delete' onclick=\"window.varprompt=
            'You are about to delete link: ".$link['name']."\\n\\nProceed?';
            this.children[0].click();\">";
          echo "<input class='hide' type='submit' name='delete' value='".$link['name']."'><div class='line'></div></div>";
        }
        $key = urlencode($link['name']);
        echo "<div class='cell colThree'>".$link['name']."</div></div><div class='information'>";
        echo "<div class='cell colThree'>";
        echo "<input type='number' min=0 max=100 name='".$key;
        echo "-min_permission' value='".$link["min_permission"]."'>";
        echo "</div><div class='cell colFive'>";
        if($edit && !$link["protected"]){
          echo "<input type='text' name='$key-url' value='".$link["url"]."'>";
        }else{
          echo $link["url"];
        }
        echo "</div></div></div>";
      }
      ?>
      <hr>
      <input type="hidden" name="action" value="Delete Link">
      <div class='row splitRow'><input type='submit' name='action' value='Save Changes'>
      <button type='button' onclick="window.location='/config.php/newloginlink'">
        New Links
      </button></div>
    </form>
  </div>

  <?php
}
function create_login_link(){
  if(isset($_GET['errormsg'])){
    echo "<div class='warning'>".$_GET['errormsg']."</div>";
  }
  ?>
  <div class="loginBox left-align"><form action="/config.php/loginlinks" method="post" autocomplete="off">
    <div class="row specialRow">
      <div class="title headCell">New Link</div>
    </div><hr class="spacer">
    <div class="row">
      <div class="title"><div class="cell colThree">Link name:</div></div>
      <div class="information"><div class="cell"><input type="text" name="name" required></div></div>
    </div>
    <div class="row">
      <div class="title"><div class="cell colThree">Min Permissions:</div></div>
      <div class="information"><div class="cell">
        <input type="number" name="min_permission" min=0 max=100 required>
      </div></div>
    </div>
    <div class="row">
      <div class="title"><div class="cell colThree">Url:</div></div>
      <div class="information"><div class="cell"><input type="text" name="url" required></div></div>
    </div>
    <hr class="spacer">
    <div class="row"><input type="submit" value="Add Login Link" name="action"></div>
    <br><a href="/config.php/loginlinks">Go Back?</a>
  </form></div>
  <?php
}
function show_new_setting_page(){
  ?>
  <div class="loginBox left-align"><form action="/config.php/settings" method="post" autocomplete="off">
    <div class="row specialRow">
      <div class="title headCell">New Setting</div>
    </div><hr class="spacer">
    <div class="row">
      <div class="title"><div class="cell colThree">Setting name:</div></div>
      <div class="information"><div class="cell"><input type="text" name="setting" required/></div></div>
    </div>
    <div class="row">
      <div class="title"><div class="cell colThree">Type:</div></div>
      <div class="information"><div class="cell">
        <select onchange="
        for(var elem of document.getElementsByClassName('toggle-option')){elem.classList.add('hide');}
        document.getElementById(this.value).classList.remove('hide');" name="type">
        <option>BOOL</option><option>INT</option><option>STRING</option>
      </select>
    </div></div></div>
    <div class="row">
      <div class="title"><div class="cell colThree">Value:</div></div>
      <div class="information"><div class="cell">
        <input type="hidden" value="false" name="BOOL">
        <div class="toggle-option"id='BOOL'><input type="checkbox" name="BOOL" value="true"/></div>
        <div class="toggle-option hide" id='INT'><input type="number" name="INT" value='0' required/></div>
        <div class="toggle-option hide" id='STRING'><input type="text" name="STRING"/></div>
      </div></div>
    </div>
    <div class="row col"><div class="title"><div class="cell colThree">Description:</div></div><div class="information">
      <div class="cell"><textarea type="text" name="description" required ></textarea></div>
    </div></div>
    <hr class="spacer">
    <div class="row"><input type="submit" value="Add Setting" name="action"></div>
    <br><a href="/config.php/settings">Go Back?</a>
  </form></div>
  <?php
}
function create_settings_page($settings, $newSetting){
  echo "<div class='users left-align'>";
  echo "<form id='setting-form' method='post' action='/config.php/settings' autocomplete='off'
  onsubmit='promptSubmit(event)'>";
  echo "<input type='hidden' name='action' value='delete-setting'><div class='row specialRow'><div class='title'>
  <div class='headCell colThree'>Name<div id='help-msg'>
  This is the index that the value will be stored in the PHP \$_SESSION variable.</div>
  </div><div class='headCell colThree'>Value</div></div>
  <div class='information'><div class='headCell colFour'>Description</div></div></div><hr class='spacer'>";
  $oddRow = false;
  foreach($settings as $key => $value){
    echo "<div class='row";
    if($oddRow){
      echo " odd-row";
    }
    if(!empty($newSetting) && $newSetting == $key){
      echo " new-user";
    }
    $oddRow = !$oddRow;
    echo "'><div class='title'>";
    if($value['protected'] != true){
      echo "<div class='delete' onclick=\"window.varprompt=
        'You are about to delete setting: $key\\n\\nProceed?';
        this.children[0].click();\">";
      echo "<input class='hide' type='submit' name='delete' value='$key'><div class='line'></div></div>";
    }
    echo "<div class='cell colThree'>$key</div><div class='cell colThree'><input name='$key'";
    if($value['type'] == "INT"){
      echo "type='number' step='1' value='". $value["value"] . "' required";
    }else if($value['type'] == 'BOOL'){
      echo "type='hidden' value='false'><input name='$key' type='checkbox' value='true'";
      if($value['value'] == 'true'){
        echo "checked";
      }
    }else if($value['type'] == 'STRING'){
      echo "type='text' value='". $value["value"] . "'";
    }
    echo "></div></div>";
    echo "<div class='information'><div class='cell colFour'>" . $value['description'] . "</div></div></div>";
  }
  ?><hr class="spacer"><div class="row specialRow splitRow">
    <input type="submit" name="action" value="Save Settings">
      <button type="button" onclick="window.location='/config.php/newSetting'">New Setting</button>
    </div></form>
  </div>
  <?php
}
function content_management_page($newValue){
  echo "<style>body{align-items:unset}</style><div class='nav-bar'>";

  $options = array('Pages' => 'pages','Footer' => 'footer');
  if(empty($_GET['content'])){
    $_GET['content'] = $options["Pages"];
  }
  foreach($options as $title => $link){
    echo "<div class='nav-link";
    if($link == $_GET['content']){
      echo " current-link";
    }
    echo "'><a href='?content=$link'>$title</a></div>";
  }

  echo "</div><div class='page-container'>";

  if(isset($_GET["warning"])){
    echo "<div class='warning'>" . $_GET['warning'] . "</div>";
  }
  if(isset($_GET["success"])){
    echo "<div class='success'>" . $_GET['success'] . "</div>";
  }

  $cont = Array(
    "content" => $_GET["content"],
    "type" => $_GET["content"],
    "newValue" => false,
    "parent" => null,
    "parentType" => null,
    "children" => null,
    "plural_children" => null, // If set it will appear otherwise it will append an s
    "message" => null
  );
  $values = Array("name" => ucfirst($cont["content"]),"protected" => true);

  if(isset($_GET['new'])){
    $cont['newValue'] = true;
    if(isset($_GET['page'])){
      $values = Array('name'=>"", 'title'=>"", 'content'=>'', 'picture'=>"");
      $cont['type'] = "post";
      $cont['parentType'] = "page";
    }else if($cont['content'] == "pages"){
      $values = Array('protected'=>false, 'name'=>"", 'title'=>"",
        'direction'=>'row','content'=>'', 'posts'=>Array()
      );
      $cont['type'] = "page";
      $cont["children"] = "Post";
    }else if(isset($_GET["section"])){
      $values = Array('name'=>'', 'url'=>'', 'type'=>'brand', 'icon'=>'');
      $cont['type'] = "link";
      $cont["parentType"] = "section";
      $cont["message"] = "You can find a list of icons
      <a href='https://fontawesome.com/icons?d=gallery&s=brands,solid&m=free'>here</a>";
    }else if($cont['content'] == "footer"){
      $values = Array('name'=>"","content"=>"","links"=>[]);
      $cont['type'] = "section";
      $cont["children"] = "Link";
    }
  }else{
    if(isset($_GET['page']) && isset($_GET['post'])){
      $values = fetch_content($_GET['page'], $_GET['post']);
      $cont['type'] = "post";
      $cont['parentType'] = "page";
    }else if(isset($_GET['page'])){
      $values = fetch_content($_GET['page']);
      $cont['type'] = "page";
      $cont["children"] = "Post";
    }else if($cont['content'] == 'pages'){
      $cont["children"] = "Page";
      $cont["plural_children"] = " ";
    }else if(isset($_GET['section']) && isset($_GET['link'])){
      $values = fetch_footer($_GET['section'], $_GET['link']);
      $cont['type'] = 'link';
      $cont['parentType'] = 'section';
      $cont["message"] = "You can find a list of icons
      <a href='https://fontawesome.com/icons?d=gallery&s=brands,solid&m=free' target='_blank'>
      here</a>";
    }else if(isset($_GET['section'])){
      $values = fetch_footer($_GET['section']);
      $cont['type'] = 'section';
      $cont['children'] = 'Link';
    }else if($cont['content'] == 'footer'){
      $cont["children"] = "Section";
    }
  }
  if($cont['parentType']){
    $cont['parent'] = $_GET[$cont['parentType']];
  }

  echo "<form method='post' class='form-fill' onsubmit='saveValues(this, ";
  echo (empty($cont["children"])?"false":"true").")'";
  if($cont['newValue']){
    echo "action='?content=".$cont['content'];
    echo ($cont['parent']? "&".$cont["parentType"]."=".$cont["parent"]: "");
    echo "'>";
    echo "<div class='row left-align'><div class='colOne'>".ucfirst($cont["type"])." Name:</div>";
    echo "<input type='text' name='name' value='" . $values['name'] . "' required></div>";
  }else{
    echo "><div class='row left-align'>";
    echo "<div class='colOne'><h2>" . $values['name'] . "</h2></div></div><hr>";
  }
  if($cont['message']){
    echo "<div class='row'><span>".$cont['message']."</span></div><hr>";
  }
  $second_divide = false;
  if(isset($values["title"])){
    echo "<div class='row left-align'><div class='colOne'>Title:</div>";
    echo "<input type='text' name='title' value='" . $values['title'] . "'></div>";
    $second_divide = true;
  }
  if(isset($values['direction'])){
    echo "<div class='row left-align'><div class='colOne'>Page Direction:</div>";
    echo "<select name='direction'>";
    echo "<option". (($values['direction'] == "column")?" selected='selected'": ""). ">column</option>";
    echo "<option". (($values['direction'] == "row")?" selected='selected'": ""). ">row</option>";
    echo "</select></div>";
    $second_divide = true;
  }
  if(isset($values["picture"])){
    echo "<div class='row left-align'><div class='colOne'>Picture:</div><input type='text'";
    echo " name='picture' value='" . $values["picture"] . "'></div>";
    $second_divide = true;
  }
  if(isset($values["type"])){
    echo "<div class='row left-align'><div class='colOne'>Icon Type:</div>";
    echo "<select name='type' id='icon-type'>";
    echo "<option". (($values['type'] == "brand")?" selected='selected'": ""). ">brand</option>";
    echo "<option". (($values['type'] == "solid")?" selected='selected'": ""). ">solid</option>";
    echo "</select></div>";
    $second_divide = true;
  }
  if(isset($values["icon"])){
    echo "<div class='row left-align'><div class='colOne'>Icon:</div><i id='icon'></i>";
    echo "<input type='text' name='icon' value='" . $values["icon"] . "' id='icon-input'></div>";
    $second_divide = true;
  }
  if(isset($values["url"])){
    echo "<div class='row left-align'><div class='colOne'>Url:</div><input type='text'";
    echo " name='url' value='" . $values["url"] . "'></div>";
    $second_divide = true;
  }
  if(isset($values['content'])){
    echo ($second_divide?"<hr>":"")."<div class='row col'>Content:<textarea name='content'>";
    echo $values['content'] . "</textarea></div>";
  }

  $list = [];

  if($cont["type"] == "page"){
    $list = $values["posts"];
    $link = "&page=".$values["name"]."&post=";
  }else if($cont['type'] == 'pages'){
    $list = $_SESSION['pages'];
    $link = "&page=";
  }else if($cont['type'] == "section"){
    $list = $values["links"];
    $link = "&section=".$values["name"]."&link=";
  }else if($cont['type'] == 'footer'){
    $list = get_all_footers(false);
    $link = "&section=";
  }
  if(sizeof($list)){
    echo "<div class='row item-list'><div class='title'>";
    echo empty($cont["plural_children"])?$cont["children"]."s":$cont["plural_children"];
    echo "</div>";
    foreach($list as $row){
      echo "<div class='moveable".(($row['name']==$newValue)?" new-value":"")."'>";
      echo "<div class='bars' draggable='true'><div></div><div></div></div>";
      echo "<div class='name'>" . $row['name'] . "</div>";
      echo "<a href='?content=".$cont["content"].$link.$row['name']."'>Edit</a></div>";
    }
    echo "</div>";
  }
  echo "<hr><div class='row'><input type='submit' name='action' id='page_action' value='";
  echo ($cont["newValue"])? "Create New ": "Save ";

  if($cont['type'] == "pages"){
    echo "Page Order";
  }else if($cont['type'] == "footer"){
    echo "Section Order";
  }else{
    echo ucfirst($cont['type']);
  }
  echo "'></form>";

  if(!$cont['newValue']){
    if(empty($cont["parent"])){
      echo "<a class='nav-btn' href='?content=".$cont['content'];
      echo ($cont['type'] == "page")?"&page=".$values['name']:"";
      echo ($cont['type'] == "section")?"&section=".$values['name']:"";
      echo "&new'>New ".$cont['children']."</a>";
    }
    if(empty($values["protected"]) || !$values["protected"]){
      echo "<form method='post' action='?content=".$cont["content"];
      echo (!empty($cont['parent']))?"&".$cont['parentType']."=".$cont['parent']:"";
      echo "' onsubmit='return confirmDelete(\"".$values["name"]."\", \"";
      echo $cont['type'];
      echo "\")'><input type='hidden' name='value' value='";
      echo $values["name"] . "'><input type='submit' name='action' value='Delete ";
      echo ucfirst($cont['type'])."'></form>";
    }
  }
  if($cont['content'] != $cont['type']){
    echo "<a class='nav-btn' href='?content=".$cont["content"];
    echo (!empty($cont['parent']))?"&".$cont['parentType']."=".$cont['parent']:"";
    echo "'>Back</a>";
  }
  echo "</div></form></div>";
}
?>
