=== GeneralStats ===
Contributors: neoxx
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=neo%40neotrinity%2eat&item_name=neotrinity%2eat&no_shipping=1&no_note=1&tax=0&currency_code=USD&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: statistics, stats, counting, count, widget, sidebar
Requires at least: 2.1
Tested up to: 2.2
Stable tag: trunk

Counts the number of users, categories, posts, comments, pages, links, words in posts, words in comments and words in pages.

== Description ==

Counts the number of users, categories, posts, comments, pages, links, words in posts, words in comments and words in pages.

**fully-optionpage-configurable, easy to integrate (now ships with widget functionality), high performing with caching technology and customizable memory usage**

Please find the version for wordpress minor 2.1 [here](http://svn.wp-plugins.org/generalstats/branches/wordpress%20minor%202.1/)

== Installation ==

1. Copy the generalstats directory in your WordPress plugins directory (usually wp-content/plugins).

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

Some attributes, especially the ones which count "words in something", need much memory and cpu-time to execute. Hence, I implemented a work-around in version 0.40. - So, just update and retry. - Now it should work for medium-sized weblogs, too!

For performance optimization, you can play around with the "Rows at once" parameter, which represents the number of database rows processed at once. In other words if your weblog consists of 1,200 comments and you want to count the words in comments with a "Rows at once" value set to 100, it will take 12 sql-queries. This may be much or less, depending on your provider's environment and the size of your weblog. Hence, this setting cannot be automatically calculated, because it is not predictable. So, it is up to you to optimize this setting.

Nevertheless, for smaller weblogs the default value of 100 "Rows at once" should be appropriate.

== Screenshots ==

1. The first screenshot shows the Options/GeneralStats Tab with the available tags in the admin menu.

2. On the second screenshot the available csstags as well as the preview section can be seen.