=== GeneralStats ===
Contributors: neoxx
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=neo%40neotrinity%2eat&item_name=neotrinity%2eat&no_shipping=1&no_note=1&tax=0&currency_code=USD&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: statistics, stats, counting, count, widget, sidebar
Requires at least: 2.3
Tested up to: 2.7
Stable tag: trunk

Counts the number of users, categories, posts, comments, pages, links, tags, link-categories, words in posts, words in comments and words in pages.

== Description ==

Counts the number of users, categories, posts, comments, pages, links, tags, link-categories, words in posts, words in comments and words in pages.

* fully optionpage-configurable
* easy to integrate (ships with widget functionality)
* high performing with caching technology and customizable memory usage
* drag and drop admin menu page
* collaborates with [Sabre](http://wordpress.org/extend/plugins/sabre/) on user counts

Please find the version for WordPress

* 2.3 and higher [here](http://downloads.wordpress.org/plugin/generalstats.zip)
* 2.1 and 2.2 [here](http://downloads.wordpress.org/plugin/generalstats.wordpress2.1-2.2.zip)
* minor 2.1 [here](http://downloads.wordpress.org/plugin/generalstats.wordpressminor2.1.zip)

== Installation ==

1. Copy the `generalstats` directory into your WordPress Plugins directory (usually wp-content/plugins). Hint: With WordPress 2.7 and higher you can conduct this step within your Admin Menu.

2. In the WordPress Admin Menu, go to the Plugins tab, and activate the GeneralStats plugin.

3. Navigate to the Settings/GeneralStats tab and customize the stats according to your desires.

4. If you got widget functionality just drag and drop GeneralStats on your dynamic sidebar in the Appearance Menu. Otherwise, put this code `<?php GeneralStatsComplete(); ?>` into your sidebar menu (sidebar.php) or where you want it to be published.

5. Be happy and celebrate! (and maybe you want to add a link to [http://www.neotrinity.at/projects/](http://www.neotrinity.at/projects/))

== Frequently Asked Questions ==

= I get the error message 'Fatal error: Allowed memory size of n bytes exhausted (tried to allocate n bytes) in /wp-includes/wp-db.php on line n'. - What's wrong? =

Some attributes, especially the ones which count "words in something", need much memory and CPU-time to execute.

For performance optimization, you can play around with the "Rows at once" parameter, which represents the number of database rows processed at once. In other words, if your weblog consists of 1,200 comments and you want to count the words in comments with a "Rows at once" value set to 100, it will take 12 sql-queries. This may be much or less, depending on your provider's environment and the size of your weblog. Hence, this setting cannot be automatically calculated, because it is not predictable. So, it is up to you to optimize this setting.

Nevertheless, for smaller weblogs the default value of 100 "Rows at once" should be appropriate.

= Why is the 'Drag and Drop Layout' not working? =

This section is based on internal WordPress Javascript-libraries, which means that it is only working with WordPress 2.1 or higher and you have to have Javascript enabled in your browser (this is a default setting in a common browser like Firefox)! The plugin is still fully functional without these constraints, but you need to customize your stats 'per hand', as in older versions of GeneralStats.

== Screenshots ==

1. The first Screenshot shows the Options/GeneralStats Tab with the Drag and Drop Layout Section in the Admin Menu.

1. The second Screenshot shows the Options/GeneralStats Tab with the available tags in the Admin's Menu Static Section.

2. On the third Screenshot the available CSS Tags as well as the Preview Section can be seen.