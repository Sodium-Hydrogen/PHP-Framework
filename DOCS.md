Documentation on all the function provided by `/resources/phpScripts/functions.php`

## Change Log ##
v0.2 - v1
#### Removed ####
* `load_content`
* `load_footer`
* `breakup_file`
* `break_to_end`

#### Added ####
* ``
* ``

## Functions ##

`function get_url(){`

`function request_page_head($second = null){`

`function queue_header($string){`

`function queue_body($string){`

`function load_page_head($second = NULL){`

`function load_logged_header(){`

`function fetch_content($page, $post=null){`

`function get_all_footers($everything=true){`

`function fetch_footer($footer, $link=null){`

`function login($username, $password){`

`function view_users(){`

`function create_account($username, $password, $privileges){`

`function admin_change_password($username, $newPassword){`

`function change_password($username, $oldPassword, $newPassword){`

`function delete_account($username, $privileges){`

`function save_fail(){`

`function check_attemps(){`

`function clear_fails(){`

`function setup_database(){`

`function get_error_message($code){`

`function load_variables_from_database(){`

`function secure_key($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'){`

`function update_configs($config_dataset){`

`function delete_config($config_name){`

`function create_config($setting, $value, $type, $desc){`

`function get_configs(){`

`function clean_long_session_table($database){`

`function create_long_session(){`

`function destroy_long_session(){`

`function login_extended(){`

`function logout_all_extended($username, $privileges){`

`function get_all_extended($username=null, $privileges=null, $key=null){`

`function update_order($new_order, $tablename="pages", $parent=null){`

`function save_page($pageContent){`

`function add_page($newPage){`

`function delete_page($pageName){`

`function add_post($post, $parent){`

`function save_post($post, $parent){`

`function delete_post($post, $parent){`

`function save_footer($footer, $parent=null){`

`function add_footer($footer, $parent=null){`

`function delete_footer($name, $parent=null){`

`function update_links($raw_links){`

`function delete_link($target){`

`function add_link($newLink){`

`function force_refresh($target='ALL'){`

`function refresh_session(){`
