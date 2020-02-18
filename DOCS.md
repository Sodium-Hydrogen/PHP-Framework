Documentation on all the function provided by `/resources/phpScripts/functions.php`

## Change Log ##
v0.2 - v1
#### Changed ####
* `get_url($name_of_file)` -> `get_url()`
* `request_page_head()` -> `request_page_head($second = NULL)`
* `load_page_head($page_name)` -> `load_page_head($second = NULL)`
* `viewUsers(` -> `view_users(`
* `createAccount(` -> `create_account(`

#### Removed ####
* `load_content`
* `load_footer`
* `breakup_file`
* `break_to_end`
* `is_valid_subpage`

#### Added ####
* `queue_header`
* `queue_body`
* `fetch_content`
* `get_all_footers`
* `fetch_footer`
* `setup_database`
* `get_error_message`
* `load_variables_from_database`
* `secure_key`
* `update_configs`
* `delete_config`
* `create_config`
* `get_configs`
* `clean_long_session_table`
* `create_long_session`
* `destroy_long_session`
* `login_extended`
* `logout_all_extended`
* `get_all_extended`
* `update_order`
* `save_page`
* `add_page`
* `delete_page`
* `add_post`
* `save_post`
* `delete_post`
* `save_footer`
* `add_footer`
* `delete_footer`
* `update_links`
* `delete_link`
* `add_link`
* `force_refresh`
* `refresh_session`

## Functions ##

#### `get_url()` #####
  It will return the appended url of the current script.
  Ex.. `index.php` = `home`, `index.php/test/test` = `test/test`

#### `queue_header($string)` ####
  Call this with any html `<head>` tags you want to send in the document before calling
  `request_page_head` or `load_page_head`.

#### `queue_body($string)` ####
  Call this with any html elements you want in the `<body>` before the page head has
  been sent to prevent data being sent out of order. If the head has already been sent
  by calling `request_page_head` or `load_page_head` you do not need to call this.

#### `load_page_head($second = NULL)` ####
  Loads the `<head></head>` and opening `<body>` tags. If `$second` is provided it
  will display that next to the title in the `<title>` tag, otherwise it will just
  display the page title in the head tag.

#### `request_page_head($second = NULL)` ####
  If no argument is passed it will try to figure out the target page and pass it
  to load_page_head.

#### `load_logged_header()` ####
  Loads the necessary files for a header to appear when a user is logged in
  It also loads the header navigation header for when someone is logged in

#### `fetch_content($page, $post = NULL)` ####
  For pages if found this will return:```
  Array(
    [name] => "Name",
    [title] => "Page Title",
    [direction] => "row",
    [position] => 0,
    [content] => "Lorem ipsum",
    [protected] => 1
    [posts] => Array(
      [0] => Array(
        [name] => "postname",
        [title] => "Post Title",
        [picture] => "/url/to/picture.png"
      ),
      ...
    )
  )```

  If posts is provided and valid post:```
  Array(
    [name] => "post"
    [title] => "Post Example"
    [picture] => "/content/img.jpg"
    [content] => "info"
    [parent] => "Parent page name"
    [position] => 0
  )```

  If it cannot find those values it returns `NULL`;

#### `get_all_footers($everything = true)` ####

#### `fetch_footer($footer, $link = NULL)` ####

#### `login($username, $password)` ####
  It will return the privilages of the user if successful
#### `view_users()` ####
  It will return an array of all users for the website
#### `create_account($username, $password, $privileges)` ####
  Creates a new user with the specified username, password, and account privilages
#### `admin_change_password($username, $newPassword)` ####
  This function will change a user password with just their username and password
#### `change_password($username, $oldPassword, $newPassword)` ####
  This is used for users changing their own password it verifies that their password is valid
  before changing it.
#### `delete_account($username, $privileges)` ####
  Only deletes the account if all input fields match
#### `save_fail()` ####
  It saves the login fail and the time until it will be cleared from record
#### `check_attemps()` ####
  This will return the number of fails the ip address has
#### `clear_fails()` ####
  Clears all login fails of the connecting ip address
#### `setup_database()` ####

#### `get_error_message($code)` ####

#### `load_variables_from_database()` ####

#### `secure_key($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')` ####

#### `update_configs($config_dataset)` ####

#### `delete_config($config_name)` ####

#### `create_config($setting, $value, $type, $desc)` ####

#### `get_configs()` ####

#### `clean_long_session_table($database)` ####

#### `create_long_session()` ####

#### `destroy_long_session()` ####

#### `login_extended()` ####

#### `logout_all_extended($username, $privileges)` ####

#### `get_all_extended($username = NULL, $privileges = NULL, $key = NULL)` ####

#### `update_order($new_order, $tablename = "pages", $parent = NULL)` ####

#### `save_page($pageContent)` ####

#### `add_page($newPage)` ####

#### `delete_page($pageName)` ####

#### `add_post($post, $parent)` ####

#### `save_post($post, $parent)` ####

#### `delete_post($post, $parent)` ####

#### `save_footer($footer, $parent = NULL)` ####

#### `add_footer($footer, $parent = NULL)` ####

#### `delete_footer($name, $parent = NULL)` ####

#### `update_links($raw_links)` ####

#### `delete_link($target)` ####

#### `add_link($newLink)` ####

#### `force_refresh($target = 'ALL')` ####

#### `refresh_session()` ####
