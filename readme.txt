=== GeneralStats ===
Contributors: neoxx
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=bernhard%40riedl%2ename&item_name=Donation%20for%20GeneralStats&no_shipping=1&no_note=1&tax=0&currency_code=EUR&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: statistics, stats, analytics, count, user, category, post, comment, page, link, tag, link-category, seo, widget, dashboard, sidebar, shortcode, multisite, multi-site, ajax, javascript, jquery, prototype
Requires at least: 2.8
Tested up to: 3.3
Stable tag: trunk

Counts the number of users, categories, posts, comments, pages, links, tags, link-categories, words in posts, words in comments and words in pages.

== Description ==

Counts the number of users, categories, posts, comments, pages, links, tags, link-categories, words in posts, words in comments and words in pages.

**starting from version 2.00 with a new API and enhanced Ajax functionality**

* fully optionpage-configurable
* easy to integrate (ships with multi/sidebar- and dashboard-widget functionality)
* possible to integrate in "Right Now" box on the dashboard
* high performing with caching technology and customizable memory usage
* optional Ajax refresh (jQuery or Prototype)
* stats-update by e-mail
* drag and drop admin menu page
* fully WP 3.0 multi-site network compatible
* clean uninstall
* collaborates with [Sabre](http://wordpress.org/extend/plugins/sabre/) on user counts

Requirements for current version:

* PHP 5 or higher (find the version for PHP 4 [here](http://downloads.wordpress.org/plugin/generalstats.php4.zip))
* You can check your PHP version with the [Health Check](http://wordpress.org/extend/plugins/health-check/) plugin.

Please find the version for WordPress

* 2.8 and higher [here](http://downloads.wordpress.org/plugin/generalstats.zip)
* 2.3 to 2.7 [here](http://downloads.wordpress.org/plugin/generalstats.wordpress2.3-2.7.zip)
* 2.1 and 2.2 [here](http://downloads.wordpress.org/plugin/generalstats.wordpress2.1-2.2.zip)
* minor 2.1 [here](http://downloads.wordpress.org/plugin/generalstats.wordpressminor2.1.zip)

**Plugin's website:** [http://www.neotrinity.at/projects/](http://www.neotrinity.at/projects/)

**Author's website:** [http://www.bernhard.riedl.name/](http://www.bernhard.riedl.name/)

== Installation ==

1. Copy the `generalstats` directory into your WordPress Plugins directory (usually wp-content/plugins). Hint: You can also conduct this step within your Admin Menu.

2. In the WordPress Admin Menu go to the Plugins tab and activate the GeneralStats plugin.

3. Navigate to the Settings/GeneralStats tab and customize the stats according to your desires.

4. If you have widget functionality just drag and drop GeneralStats on your widget area in the Appearance Menu. Add additional [function and shortcode calls](http://wordpress.org/extend/plugins/generalstats/other_notes/) according to your desires.

5. Be happy and celebrate! (and maybe you want to add a link to [http://www.neotrinity.at/projects/](http://www.neotrinity.at/projects/))

== Frequently Asked Questions ==

= I get the error message `Fatal error: Allowed memory size of n bytes exhausted (tried to allocate n bytes) in /wp-includes/wp-db.php on line n`. - What's wrong? =

Some attributes, especially the ones which count `words in ...`, need more memory and CPU-time to be executed.

For performance optimization, you can play around with the `Rows at Once` parameter in the `Performance` tab, which represents the number of database rows processed at once. In other words, if your weblog consists of 1,200 comments and you want to count the words in comments with a `Rows at once` value set to 100, it will take 12 sql-queries. This may be much or less, depending on your provider's environment and the size of your weblog. Hence, this setting cannot be automatically calculated because it is not predictable. So, it is up to you to optimize this setting.

Nevertheless, for smaller weblogs the default value of 100 "Rows at once" should be appropriate.

= Which Javascript library should I choose for the Ajax refresh in my theme? =

That's [a well-covered topic in the web](https://encrypted.google.com/search?q=prototype+vs.+jquery). GeneralStats provides you with the flexibility to use either [Prototype](http://www.prototypejs.org/) or [jQuery](http://jquery.com/). Thus, your decision merely depends on what your other installed plugins use.

= Why is the 'Drag and Drop Layout' not working? =

This section is based on Javascript. Thus, you have to enable Javascript in your browser (this is a default setting in a [modern browser](http://browsehappy.com/) like [Firefox](http://www.mozilla.com/?from=sfx&uid=313633&t=306)). GeneralStats is still fully functional without these constraints, but you need to customize your stats manually as in older versions of GeneralStats.

= How can I adopt the color scheme in the GeneralStats Settings Tab? =

If you select one of the two default color schemes (`classic = Blue` or `fresh = Gray`) in your Profile Page, GeneralStats automatically adopts its colors to this scheme.

In case you use a custom color scheme, this cannot be done automatically because WordPress still doesn't provide any proper functions to find out which colors of your scheme are used for background, font, etc. - Nevertheless, you can set up your preferred colors manually: Just add the [filter](http://codex.wordpress.org/Function_Reference/add_filter) `generalstats_available_admin_colors` in for example generalstats.php or in your custom-colors-plugin.

= Is there anything I need to know before updating to GeneralStats v2? =

As the majority of the source-code changed with version 2.00, there are two things I would like to mention:

- Your 1.x options will be automatically converted. - Nevertheless, you should make a backup prior to this upgrade!
- `GeneralStatsComplete()` has been deprecated in favor of `$generalstats->output()`

As I've excluded HTML-tags with version 2 of GeneralStats by default, you might experience a change in your Word-Count stats. - If you like to re-include these tags and count them as words, you can turn on the setting "Include HTML-Tags in Word-Counts" in the "Administrative Options" section.

== Other Notes ==

**Attention! - Geeks' stuff ahead! ;)**

= API =

With GeneralStats 2.00 and higher you can use a function-call to display individual stat(-blocks) on different positions on your page.

Parameters can either be passed [as an array or a URL query type string (e.g. "display=0&format=0")](http://codex.wordpress.org/Function_Reference/wp_parse_args). Please note that WordPress parses all arguments as strings, thus booleans have to be 0 or 1 if used in query type strings whereas for arrays [real booleans](http://php.net/manual/en/language.types.boolean.php) should be used. Furthermore you have to use the prefix `stats_` to select different stats in a query_string. - For example: `stat_0=Community&stat_12=Pages Word-Count`.

**`function $generalstats->count($params=array())`**

$params:

- `stat`: a stat-id of the following list

 - 0 => 'Users'
 - 1 => 'Categories'
 - 2 => 'Posts'
 - 3 => 'Comments'
 - 4 => 'Pages'
 - 5 => 'Links'
 - 6 => 'Tags'
 - 7 => 'Link-Categories'
 - 10 => 'Words_in_Posts'
 - 11 => 'Words_in_Comments'
 - 12 => 'Words_in_Pages'

- `thousands_separator`: divides counts by thousand delimiters; default `,` => e.g. 1,386,267

- `display`: if you want to return the stats-information (e.g. for storing in a variable) instead of echoing it with this function-call, set this to `false`; default setting is `true`

- `format_container`: This option can be used to format the `span` container with css. Please note, that it should only be used to provide individual formats in case the class-style itself cannot be changed.

- `no_refresh`: If set to true, GeneralStats will not produce any Ajax-Refresh-code, even if you have enabled the Ajax refresh in the admin menu.

The following example outputs the number of post tags:

`<?php

global $generalstats;

$generalstats->count('stat=6');

?>`

**`function $generalstats->output($params=array())`**

$params:

- `stat_selected`: this array has to hold the id of the selected stat as key and the description as array-value, e.g. `6 => 'Post-Tags'`; fallback to selected stats in Admin Menu

 - 0 => 'Users'
 - 1 => 'Categories'
 - 2 => 'Posts'
 - 3 => 'Comments'
 - 4 => 'Pages'
 - 5 => 'Links'
 - 6 => 'Tags'
 - 7 => 'Link-Categories'
 - 10 => 'Words_in_Posts'
 - 11 => 'Words_in_Comments'
 - 12 => 'Words_in_Pages'

- `before_list`: default `<ul>`

- `after_list`: default `</ul>`

- `format_stat`: default `<li><strong>%name</strong> %count</li>`, %name and %count will be replaced by the attributes of the stat-entry

- `thousands_separator`: divides numbers by thousand delimiters default `,` => e.g. 1,386,267

- `use_container`: if set to `true` (default value) and the same selected stats and format is used as set in the admin menu, GeneralStats wraps the output in a html div with the class `generalstats-refreshable-output` - the class `generalstats-output` will be used for all other output; if you set `use_container` to `false`, no container div will be generated

- `display`: if you want to return the stats-information (e.g. for storing in a variable) instead of echoing it with this function-call, set this to `false`; default setting is `true`

- `format_container`: This option can be used to format the `div` container with css. Please note, that it should only be used to provide individual formats in case the class-style itself cannot be changed.

- `no_refresh`: If set to true, GeneralStats will not produce any Ajax-Refresh-code, even if you have enabled the Ajax refresh in the admin menu.

The following example outputs the users and posts-counts with the title 'Community-Members' and 'My Post-Count':

`<?php

global $generalstats;

$params=array(
	'stats_selected' => array(
		0 => 'Community-Members',
		2 => 'My Post-Count'
	)
);

$generalstats->output($params);

?>`

= Shortcodes =

[How-to for shortcodes](http://codex.wordpress.org/Shortcode_API)

**General Example:**

Enter the following text anywhere in a post or page to show your current pages-count:

`There are [generalstats_count stat=4] pages on my weblog`

**Available Shortcodes:**

`generalstats_output`

Invokes `$generalstats->output($params)`. Please note that you have to use the prefix `stats_` to select stats. - For example: `stat_0="Community" stat_12="Pages Word-Count"`

`generalstats_count`

Invokes `$generalstats->count($params)`.

= Filters =

[How-To for filters](http://codex.wordpress.org/Function_Reference/add_filter)

**General Example:**

`function my_generalstats_available_admin_colors($colors=array()) {
	$colors['custom_scheme'] = array('#14568A', '#14568A', '', '#C3DEF1');
	return $colors;
}

add_filter('generalstats_available_admin_colors', 'my_generalstats_available_admin_colors');`

**Available Filters:**

`generalstats_defaults`

In case you want to set the default parameters globally rather than handing them over on every function call, you can add the [filter](http://codex.wordpress.org/Function_Reference/add_filter) `generalstats_defaults` in for example generalstats.php or your [own customization plugin](http://codex.wordpress.org/Writing_a_Plugin) (recommended).

Please note that parameters which you hand over to a function call (`$generalstats->output` or `$generalstats->count`) will always override the defaults parameters, even if they have been set by a filter or in the admin menu.

`generalstats_dashboard_widget`

Receives an array which is used for the dashboard-widget-function call to `$generalstats->output($params)`. `display` and `use_container` will automatically be set to true.

`generalstats_dashboard_right_now`

Receives an array which is used for the dashboard-right-now-box-function call to `$generalstats->output($params)`. `display` and `use_container` will automatically be set to true.

`generalstats_mail_stats_content`

Receives an array which is used for the mail-stats-function call to `$generalstats->output($params)`. `display` and `use_container` will automatically be set to false.

`generalstats_available_admin_colors`

Receives an array which is appended to the default-color schemes of GeneralStats.

Array-Structure:

- 1 -> border-color
- 2 -> background-color
- 4 -> text-color

== Screenshots ==

1. This screenshot shows the Settings/GeneralStats Tab with the Drag and Drop Section in the Admin Menu.

2. This picture presents the Preview Section of the GeneralStats Tab in the Admin Menu.

== Upgrade Notice ==

= 2.00 =

This is not only a feature but also a security update. - Thus, I'd strongly recommend all users of GeneralStats which have at least an environment of WordPress 2.8 or higher and PHP 5 to install this version!

== Changelog ==

= 2.31 =

* fixed a bug with Ajax-update functionality in a SSL-environment. Thanks to huyz who has mentioned this in the forum http://wordpress.org/support/topic/plugin-generalstats-makes-https-call-to-admin-ajax-even-if-site-is-http

= 2.30 =

* revised the security model (replaced option `Allow anonymous Ajax Refresh Requests` with `All users can view stats` and added the option `Capability to view stats` to define the capability of a certain user to access the stats)
* de-coupling of Ajax-refresh-functions and output of `wp_localize_script` (GeneralStats is now compatible with [WP Minify](http://wordpress.org/extend/plugins/wp-minify/))
* small enhancements

= 2.20 =

* Changed default Ajax library to jQuery (Prototype is by default now only used for the settings-page)
* Code clean-up in the Ajax-refresh-files
* Small bug-fixes and enhancements

= 2.10 =

* added jQuery as alternative to Prototype for the Ajax refresh in the front-end

= 2.01 =

* corrected a few typos and fixed potential bugs

= 2.00 =

* start Changelog
* completely reworked API methods and internal structure
* Security improvements (wp_nonce, capabilities)
* reworked Admin Menu
* extracted JavaScript-code
* use WordPress transient-functionality for cache
* word-counts now exclude common html tags
* offer new functions `$generalstats->output()` and `$generalstats->count()`
* all stats are now Ajax refreshable
* a selection of stats can be emailed to the admin on a regular basis (uses wp_cron and wp_mail)
* added log functionality
* reworked handling of settings in the Admin Menu
* deprecated old function `GeneralStatsComplete()`
* added contextual help to settings menu
* updated license to GPLv3