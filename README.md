# PHP-Framework #
A PHP 7 framework developed by Michael Julander.
## Important Files ##
### config.php ###
Configuration file containing all of the setup for mysql access.<br>
This file also contains the option for debugging mode.<br>
When $setup is true the main page will say comming soon and this file can be run to set up the websites admin user.<br>
It also has things like the sites title and list of pages in it
### page ###
This file contains the content for all pages.<br>
It also contains the optional subtitle to be displayed in the themes header.<br>
Additionally it now supports posts and will display them in a table on the main page and 
posts will be displayed as two column layouts with the header and image on the left side and
any text on the right side.
### footer ###
This is where the footer sections are defined.<br>
By adding the tag <em> social </em> it will attempt to find a matching font awesome logo for the social media link.
### theme/ ###
This directory is where you would put the theme for the site. <br>
Some can be found [here][themes].
### login.php ###
By requesting this page on a browser it will generate built in login page.


[themes]: https://github.com/NaH012/framework-themes/
