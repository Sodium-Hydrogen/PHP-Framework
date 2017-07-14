# PHP-Framework #
A PHP 7 framework developed by Michael Julander and Weston Shakespear.
## Important Files ##
### config.php ###
Configuration file containing all of the setup for mysql access.<br>
This file also contains the option for debugging mode.<br>
When $setup is true the main page will say comming soon and this file can be run to set up the websites admin user.<br>
It also has things like the sites title and list of pages in it
### page ###
This file contains the content for all pages.<br>
It also contains the optional subtitle to be displayed in the themes header.
### footer ###
This is where the footer sections are defined.<br>
By adding the tag <em> social </em> it will attempt to find a matching font awesome logo for the social media link
### theme/ ###
This directory is where you would put the theme for the site
### login.php ###
By requesting this page on a browser it will generate built in login page
