Documentation on all the functions provided by `/resources/phpScripts/functions.php`

The Wiki pages are generated from DOCS.md.

# Change Log #
v0.2 - v1
## Changed ##
* `get_url($name_of_file)` -> `get_url()`
* `request_page_head()` -> `request_page_head($second = NULL)`
* `load_page_head($page_name)` -> `load_page_head($second = NULL)`
* `viewUsers(` -> `view_users(`
* `createAccount(` -> `create_account(`

## Removed ##
* `load_content`
* `load_footer`
* `breakup_file`
* `break_to_end`
* `is_valid_subpage`

## Added ##
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

# Functions #

## `get_url` ##
`string get_url()`

  It will return the appended url of the current script.
  Ex.. `index.php` = `home`, `index.php/test/test` = `test/test`

## `queue_header` ##
`void queue_header($string)`

  Call this with any html `<head>` tags you want to send in the document before calling
  `request_page_head` or `load_page_head`.

## `queue_body` ##
`void queue_body($string)`

  Call this with any html elements you want in the `<body>` before the page head has
  been sent to prevent data being sent out of order. If the head has already been sent
  by calling `request_page_head` or `load_page_head` you do not need to call this.

## `load_page_head` ##
`void load_page_head($second = NULL)`

  Loads the `<head></head>` and opening `<body>` tags. If `$second` is provided it
  will display that next to the title in the `<title>` tag, otherwise it will just
  display the page title in the head tag.

## `request_page_head` ##
`void request_page_head($second = NULL)`

  If no argument is passed it will try to figure out the target page and pass it
  to load_page_head.

## `load_logged_header` ##
`void load_logged_header()`

  Loads the necessary files for a header to appear when a user is logged in
  It also loads the header navigation header for when someone is logged in

## `fetch_content` ##
`array() fetch_content($page, $post = NULL)`

  For pages if found this will return:
  ```
  Array(
    [name] => "Name",
    [title] => "Page Title",
    [direction] => "row",
    [position] => 0,
    [content] => "Lorem ipsum",
    [protected] => 1,
    [posts] => Array(
      [0] => Array(
        [name] => "postname",
        [title] => "Post Title",
        [picture] => "/url/to/picture.png"
      ),...
    )
  )
  ```
  If `$posts` is provided and valid post:
  ```
  Array(
    [name] => "post",
    [title] => "Post Example",
    [picture] => "/content/img.jpg",
    [content] => "info",
    [parent] => "Parent page name",
    [position] => 0
  )
  ```
  If it cannot find those values it returns `NULL`;

## `get_all_footers` ##
`array() get_all_footers($everything = true)`

By default without providing any arguments this function will return an array of each footer
section and all the link associated with each section. Ex..
```
Array(
  [0] => Array(
    [name] => "Footer Section",
    [position] => 0,
    [content] => "",
    [links] => Array(...)
  ),...
)
```
If `$everything` is set to false it will return the sections minus their links. Ex..
```
Array(
  [0] => Array(
    [name] => "Footer Section",
    [position] => 0,
    [content] => ""
  ),...
)
```

## `fetch_footer` ##
`array() fetch_footer($footer, $link = NULL)`

This can be used to request only one footer section with all of it's links or if `$link` is
set and found then it will return a link array. Ex..
```
Array
(
    [name] => "Link",
    [url] => "https://url.com/link",
    [parent] => "Footer Section",
    [position] => 1,
    [icon] => "code",
    [type] => "solid"
)
```
If it cannot find either `$footer` or `$link` it will return `NULL`.

## `login` ##
`int login($username, $password)`

  It will return the permissions of the user if successful and -1 if unsuccessful.

## `view_users` ##
`array() view_users()`

  It will return an array of all users stored in the accounts table. Ex..
  ```
  Array(
    [0] => Array(
      [username] => "username",
      [privileges] => 100
    ),...
  )
  ```

## `create_account` ##
`bool create_account($username, $password, $privileges)`

  Creates a new user with the specified username, password, and account permissions. Returns
  true if successful, otherwise it will return false.

## `admin_change_password` ##
`bool admin_change_password($username, $newPassword)`

  This function will change a user password with just their username and new password. Returns
  true if successful, otherwise it will return false.

## `change_password` ##
`bool change_password($username, $oldPassword, $newPassword)`

  This is used for users changing their own password it verifies that their old password then
  calls `admin_change_password` and will return true if successful, otherwise it will return false.

## `delete_account` ##
`void delete_account($username, $privileges)`

  This will delete from the accounts table and extendedsession table any row that has a matching
  username and permissions as what is provided.

## `save_fail` ##
`void save_fail()`

  It saves the ip and increments the counter by one for every time it is called.
  It uses `$_SERVER['REMOTE_ADDR']` to get the ip and will set the untilFree column to the
  jail time in hours from when it is called.

## `check_attemps` ##
`int check_attemps()`

  This will return the number of fails for the current ip from `$_SERVER['REMOTE_ADDR']`.
  Default is 0.

## `clear_fails` ##
`void clear_fails()`

  Clears all login fails of the ip address from `$_SERVER['REMOTE_ADDR']`.

## `setup_database` ##
`bool setup_database()`

  This checks to see if the table accounts exists and if it doesn't then it will create all
  the tables needed for the framework and populate default values. If it errors it will return
  false, otherwise it has succeeded or the accounts table already exists so it will return true.

## `get_error_message` ##
`string get_error_message($code)`

  Provide a string for an html code and this will return the default error message for that code.
  Ex. `get_error_message("404") = "Not Found"`. Default value is `"Unknown Status Code"`.

## `load_variables_from_database` ##
`void load_variables_from_database()`

  To get all the values from the database into the `$_SESSION` variable call this function.
  It will load all values from `configs` and `loginlinks` as well as abbreviated version of
  `pages` with `posts` associated.
  Additionally this also sets `$_SESSION['session_start'] = time()` so session refreshing
  can happen.

## `secure_key` ##
`string secure_key($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')`

  This will return a secure randomly generated key of the specified length.
  If `$keyspace` is defined it will use that for choosing characters, otherwise it will default
  to all alpha-numeric characters with both lower and upper case.

## `update_configs` ##
`void update_configs($config_dataset)`

  This expects an associated array with they key being the setting you want to update and the
  value the new value for the configs table. The value is expected to be a string. Ex.
  ```
  $config_dataset = Array(
    [debug] => "true",...
  )
  ```
  This function calls `force_refresh()` to make sure every user gets updated info on their next
  page load.

## `delete_config` ##
`void delete_config($config_name)`

  To remove a config variable from the configs table in the database call this with the setting's
  name and it will remove it if the setting is not protected.

  This function calls `force_refresh()` to make sure every user gets updated info on their next
  page load.

## `create_config` ##
`void create_config($setting, $value, $type, $desc)`

  This enters a new setting into the configs table in the database. All parameters are expected
  to be strings. It will fail on duplicate setting name. `$type` can only be `BOOL`, `STRING`,
  or `INT` and `$value` should be a string representation of that ex. `true = "true"`, `100 = "100"`.

  This function calls `force_refresh()` to make sure every user gets updated info on their next
  page load.

## `get_configs` ##
`array() get_configs()`

  This will return an array of all the values in the configs table in the database. Ex.
  ```
  Array(
    [debug] => Array(
      [value] => "true",
      [type] => "BOOL",
      [description] => "Activates php errors...",
      [protected] => 1
    ),...
  )
  ```

## `clean_long_session_table` ##
`clean_long_session_table($database)`

## `create_long_session` ##
`create_long_session()`

## `destroy_long_session` ##
`destroy_long_session()`

## `login_extended` ##
`login_extended()`

## `logout_all_extended` ##
`logout_all_extended($username, $privileges)`

## `get_all_extended` ##
`get_all_extended($username = NULL, $privileges = NULL, $key = NULL)`

## `update_order` ##
`update_order($new_order, $tablename = "pages", $parent = NULL)`

## `save_page` ##
`save_page($pageContent)`

## `add_page` ##
`add_page($newPage)`

## `delete_page` ##
`delete_page($pageName)`

## `add_post` ##
`add_post($post, $parent)`

## `save_post` ##
`save_post($post, $parent)`

## `delete_post` ##
`delete_post($post, $parent)`

## `save_footer` ##
`save_footer($footer, $parent = NULL)`

## `add_footer` ##
`add_footer($footer, $parent = NULL)`

## `delete_footer` ##
`delete_footer($name, $parent = NULL)`

## `update_links` ##
`update_links($raw_links)`

## `delete_link` ##
`delete_link($target)`

## `add_link` ##
`add_link($newLink)`

## `force_refresh` ##
`force_refresh($target = 'ALL')`

## `refresh_session` ##
`refresh_session()`
