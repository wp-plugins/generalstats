=== GeneralStats ===
Contributors: neoxx
Donate link: http://www.neotrinity.at/projects/
Tags: statistics, stats, counting, count, widget, sidebar
Requires at least: 2.1
Tested up to: 2.1.3
Stable tag: trunk

Counts the number of users, categories, posts, comments, pages, links, words in posts, words in comments and words in pages.

== Description ==

Counts the number of users, categories, posts, comments, pages, links, words in posts, words in comments and words in pages.

**fully-optionpage-configurable, easy to integrate (now ships with widget functionality)**

Please find the version for wordpress minor 2.1 [here](http://svn.wp-plugins.org/generalstats/branches/wordpress%20minor%202.1/)

== Installation ==

1. Put the general-stats.php file in your WordPress plugins directory (usually wp-content/plugins).

2. In the WordPress admin console, go to the Plugins tab, and activate the GeneralStats plugin.

3. Go to the Options/GeneralStats Tab and configure whatever you like. You can add stats to the output by inserting a number into the position field and an optional description next to it.
Feel free to play around and see the result in the section preview at the bottom of the options page. If you are new to GeneralStats, it's a good start to load the defaults by clicking the button in the right lower corner.

4. If you got [widget functionality](http://wordpress.org/extend/plugins/widgets/), just drag and drop GeneralStats on your dynamic sidebar in the presentation menu and name it appropriate. Otherwise, put this code `<?php GeneralStatsComplete(); ?>` into your sidebar menu (sidebar.php), footer (footer.php) or where you want it to appear.

5. Drink a beer, smoke a cigarette or celebrate in a way you like!

**I had to adopt the variable names in the options tab, so in case of an update please be sure to also have a look on the [FAQ](http://wordpress.org/extend/plugins/generalstats/faq/).**

== Frequently Asked Questions ==

= After updating to version 0.30 (or higher) my options are gone. - What the heck? =

Thanks for not swearing. ;) Due to the naming conventions I renamed the form fields to assure unique variables. - You can still roll back to the version installed before and memorize your settings for filling them in again. - Sorry for any inconvenience caused.

= I get the error message 'Fatal error: Allowed memory size of n bytes exhausted (tried to allocate n bytes) in /wp-includes/wp-db.php on line n'. - What's wrong? =

Some attributes, especially the ones which count words in something need much memory and cpu-time to execute. - You should either disable these functions or ask your webhost to allocate more resources for your account. - Generally, all other functions like posts, users, etc. should even work on larger weblogs without problems.

== Screenshots ==

1. The first screenshot shows the Options/GeneralStats Tab with the available tags in the admin menu.

2. On the second screenshot the available csstags as well as the preview section can be seen.