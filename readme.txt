=== GeneralStats ===
Contributors: neoxx
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=neo%40neotrinity%2eat&item_name=neotrinity%2eat&no_shipping=1&no_note=1&tax=0&currency_code=USD&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: statistics, stats, analytics, count, user, category, post, comment, page, link, tag, link-category, widget, dashboard, sidebar, ajax, javascript, prototype
Requires at least: 2.3
Tested up to: 2.9
Stable tag: trunk

Counts the number of users, categories, posts, comments, pages, links, tags, link-categories, words in posts, words in comments and words in pages.

== Description ==

Counts the number of users, categories, posts, comments, pages, links, tags, link-categories, words in posts, words in comments and words in pages.

**since version 1.00 with an optional AJAX refresh and standard triggers for cache-refreshing**

* fully optionpage-configurable
* easy to integrate (ships with sidebar- and dashboard-widget functionality)
* high performing with caching technology and customizable memory usage
* drag and drop admin menu page
* clean uninstall
* collaborates with [Sabre](http://wordpress.org/extend/plugins/sabre/) on user counts

Please find the version for WordPress

* 2.3 and higher [here](http://downloads.wordpress.org/plugin/generalstats.zip)
* 2.1 and 2.2 [here](http://downloads.wordpress.org/plugin/generalstats.wordpress2.1-2.2.zip)
* minor 2.1 [here](http://downloads.wordpress.org/plugin/generalstats.wordpressminor2.1.zip)

== Installation ==

1. Copy the `generalstats` directory into your WordPress Plugins directory (usually wp-content/plugins). Hint: With WordPress 2.7 and higher you can conduct this step within your Admin Menu.

2. In the WordPress Admin Menu go to the Plugins tab and activate the GeneralStats plugin.

3. Navigate to the Settings/GeneralStats tab and customize the stats according to your desires.

4. If you got widget functionality just drag and drop GeneralStats on your dynamic sidebar in the Appearance Menu. Otherwise, put this code `<?php GeneralStatsComplete(); ?>` into your sidebar menu (sidebar.php) or where you want the results to be published.

5. Be happy and celebrate! (and maybe you want to add a link to [http://www.neotrinity.at/projects/](http://www.neotrinity.at/projects/))

== Frequently Asked Questions ==

= I get the error message 'Fatal error: Allowed memory size of n bytes exhausted (tried to allocate n bytes) in /wp-includes/wp-db.php on line n'. - What's wrong? =

Some attributes, especially the ones which count "words in something", need much memory and CPU-time to execute.

For performance optimization, you can play around with the "Rows at once" parameter, which represents the number of database rows processed at once. In other words, if your weblog consists of 1,200 comments and you want to count the words in comments with a "Rows at once" value set to 100, it will take 12 sql-queries. This may be much or less, depending on your provider's environment and the size of your weblog. Hence, this setting cannot be automatically calculated, because it is not predictable. So, it is up to you to optimize this setting.

Nevertheless, for smaller weblogs the default value of 100 "Rows at once" should be appropriate.

= Why is the 'Drag and Drop Layout' not working? =

This section is based on internal WordPress Javascript-libraries, which means that it is only working with WordPress 2.1 or higher. In addition you have to have Javascript enabled in your browser (this is a default setting in a common browser like Firefox). The plugin is still fully functional without these constraints, but you need to customize your stats manually as in older versions of GeneralStats.

= How can I adopt the color scheme in the GeneralStats Settings Tab for WordPress 2.5 and higher? =

If you select one of the two default color schemes (`classic = Blue` or `fresh = Gray`) in your Profile Page, GeneralStats automatically adopts its colors to this scheme.

In case you use a custom color scheme, this cannot be done automatically, because WordPress still doesn't provide any proper functions to find out, which colors of your scheme are used for background, font, etc. - Nevertheless, you can set up your preferred colors manually: Just add the [filter](http://codex.wordpress.org/Function_Reference/add_filter) `generalstats_available_admin_colors` in for example generalstats.php or in your custom-colors-plugin.

Array-Structure:

* 1 -> border-color of drag and drop lists
* 2 -> background-color of drag and drop menu items and edit-label
* 4 -> text-color of drag and drop menu items and edit-label

Example:

`function my_generalstats_available_admin_colors($colors=array()) {
	$colors["custom_scheme"] = array("#14568A", "#14568A", "", "#C3DEF1");
	return $colors;
}

add_filter('generalstats_available_admin_colors', 'my_generalstats_available_admin_colors');`

== Screenshots ==

1. This screenshot shows the Settings/GeneralStats Tab with the Drag and Drop Section in the Admin Menu.

2. This picture presents the Preview Section of the GeneralStats Tab in the Admin Menu.