# PHP-Framework #
A lite PHP 7 framework developed for content management and easy plug and play with
authentication for custom PHP scripts.

Just add `require_once("resources/phpScripts/load.php");` and `$_SESSION['user']` will report if
someone is logged in as well as `$_SESSION['permissions']` to check for a high enough
privilege level.

## v1.0.1 ##
### What's new? ###
* Added message for the Accounts page so an account from an external pluggin will see a message about their account being external instead of change username/password box
* Added new default setting `login_link` which will tell the theme to show a link to the login page.

## v1.0 ##
### What's new? ###
* Complete revamp of content storage in favor of using a database.
* New permissions settings on a scale 0-100 instead of hard coded `ADIMN` or `BASIC`
* Added content and setting management pages for easier access to change values.
* Redesigned the logged in header to move the page down and insert at the top instead of covering the top of the page.
* Created fall back error pages for when they are not provided by a theme.
* Designed functions to make the framework W3 valid by queuing information.
* Implemented extended session logins to overcome the short php session timeout.


----
## Important Info ##

__v1 is not compatible with previous versions__

__Due to [Mozilla's lack of effort] some functionality won't work in Firefox__

### Initial Setup ###
For database setup please copy `example-database_conf.php` to `database_conf.php` and set the following 3 values
```
$sql_database = "database name";
$sql_user_name = "database user";
$sql_password = "database password";
```
You can get these values by running the following commands. replace databaseName, username, and password.
```
CREATE DATABASE databaseName;
GRANT ALL PRIVILEGES ON databaseName.* to 'username'@'localhost' identified by 'password';
```
To setup the first user account you will need to run `/config.php` which will ask for the
database password to verify you as you create your account.

### User Permissions ###
By default the user management page will try to force you to only set a users permissions
as an __even number 0-100__. If you have a custom login script ex. _(O-Auth)_ please set their
permissions to an __odd value 1-99__. This is an easy way for the framework to tell the
difference between users stored in the accounts database and other users so it will not
present actions such as changing their password.

### `$_SESSION` ###
For general storing information into `$_SESSION` to guarantee maximum compatibility
use `$_SESSION['vars']`

### Themes ###
This framework is designed to use a theme installed in `resources/theme` that
has two required files. `page/index.php` and `page/error.php`.

One can be found __[here][themes]__ and more info can be found in [DOCS.md].

### Error Document ###
This framework provides auto error document generation

### Font Awesome ###
By default this framework wants to use font awesome for footer links.
If you would like to as well [download a free version] >= v5 and place it
in `/resources/fontawesome` for plug and play action. If you would like to
reference a different font awesome css file just change the first line in
`/resources/login.js` to reflect that url.

----
## Preset login areas ##

### Account ###
This page is used for general account actions (change password, logout on all devices).

### Header Link ###
This manages the links and their minimum permissions that appear in the drop down menu for
logged in users. You can add links to your own custom scripts here.

### Manage Users ###
This area gives you access to view all users and manage their accounts
(permissions, reset password, delete accounts).

### Settings ###
This is where you can manage and create your own constants for use in the framework and in your
own custom scripts. These values will be stored by the settings name as the index in `$_SESSION`.

### Update Content ###
You can manage the content (pages, posts, footers) that will be displayed by the framework
from this page.

----
## Documentation ##

Read the [DOCS.md] for documentation.

[Mozilla's lack of effort]: https://bugzilla.mozilla.org/show_bug.cgi?id=505521
[themes]: https://github.com/NaH012/framework-themes/
[download a free version]: https://fontawesome.com/how-to-use/on-the-web/setup/hosting-font-awesome-yourself
[DOCS.md]: /DOCS.md
