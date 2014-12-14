=== GeneralStats ===
Contributors: neoxx
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=TGPC4W9DUSWUS
Tags: statistics, stats, analytics, count, user, category, post, comment, page, link, tag, link-category, seo, widget, dashboard, sidebar, shortcode, multisite, multi-site, ajax, javascript, jquery
Requires at least: 3.8
Tested up to: 4.1
Stable tag: trunk
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Counts the number of users, categories, posts, comments, pages, links, tags, link-categories, words in posts, words in comments and words in pages.

== Description ==

* fully settings page configurable with GUI or manually
* easy to integrate (ships with multi/sidebar- and dashboard-widget functionality)
* possible to integrate in "Right Now" box or to display as widget on the dashboard
* high performing with caching technology and customizable memory usage
* optional Ajax refresh with jQuery
* fully compatible with [https/SSL/TLS-sites](https://codex.wordpress.org/Administration_Over_SSL)
* stats-update by e-mail
* [API for developers](https://wordpress.org/plugins/generalstats/other_notes/)
* fully multisite network compatible
* clean uninstall

Please find the version for WordPress

* 3.8 and higher [here](https://downloads.wordpress.org/plugin/generalstats.zip)
* 3.3 to 3.7 [here](https://downloads.wordpress.org/plugin/generalstats.wordpress3.3-3.7.zip)
* 2.8 to 3.2 [here](https://downloads.wordpress.org/plugin/generalstats.wordpress2.8-3.2.zip)
* 2.3 to 2.7 [here](https://downloads.wordpress.org/plugin/generalstats.wordpress2.3-2.7.zip)
* 2.1 and 2.2 [here](https://downloads.wordpress.org/plugin/generalstats.wordpress2.1-2.2.zip)
* minor 2.1 [here](https://downloads.wordpress.org/plugin/generalstats.wordpressminor2.1.zip)

**Plugin's website:** [http://www.bernhard-riedl.com/projects/](http://www.bernhard-riedl.com/projects/)

**Author's website:** [http://www.bernhard-riedl.com/](http://www.bernhard-riedl.com/)

== Installation ==

1. Copy the `generalstats` directory into your WordPress Plugins directory (usually wp-content/plugins). Hint: You can also conduct this step within your Admin Menu.

2. In the WordPress Admin Menu go to the Plugins tab and activate the GeneralStats plugin.

3. Navigate to the Settings/GeneralStats tab and customize the stats according to your desires.

4. If you have widget functionality just drag and drop GeneralStats on your widget area in the Appearance Menu. Add additional [function and shortcode calls](https://wordpress.org/plugins/generalstats/other_notes/) according to your desires.

5. Be happy and celebrate! (and maybe you want to add a link to [http://www.bernhard-riedl.com/projects/](http://www.bernhard-riedl.com/projects/))

== Frequently Asked Questions ==

= I get the error message `Fatal error: Allowed memory size of n bytes exhausted (tried to allocate n bytes) in /wp-includes/wp-db.php on line n`. - What's wrong? =

Some attributes, especially the ones which count `words in ...`, need more memory and CPU-time to be executed.

For performance optimization, you can play around with the `Rows at Once` parameter in the `Performance` tab, which represents the number of database rows processed at once. In other words, if your weblog consists of 1,200 comments and you want to count the words in comments with a `Rows at once` value set to 100, it will take 12 sql-queries. This may be much or less, depending on your provider's environment and the size of your weblog. Hence, this setting cannot be automatically calculated because it is not easily predictable. So, it is up to you to optimize this setting.

Nevertheless, for smaller weblogs the default value of 100 "Rows at once" should be appropriate.

= Why do the Links & Link-Categories stats not work? =

In [WordPress 3.5 and higher the Link Manager (aka Blogroll) has been deactivated by default](https://core.trac.wordpress.org/ticket/21307). - In order to re-activate it you need to download the [Link Manager Plugin](https://wordpress.org/plugins/link-manager/).

= Why can't I see the 'Selection GUI' section? =

This section is based on JavaScript. Thus, you have to enable JavaScript in your browser (this is a default setting in modern browsers like [Mozilla Firefox](https://en.wikipedia.org/wiki/Firefox) or [Google Chrome](https://en.wikipedia.org/wiki/Google_Chrome)). GeneralStats is still fully functional without JavaScript, but you need to customize your stats manually. If you use a device with a smaller display (e.g. mobile phone), this section will also be hidden.

== Other Notes ==

**Attention! - Geeks' stuff ahead! ;)**

= API =

With GeneralStats 2.00 and higher you can use a function-call to display individual stat(-blocks) on different positions on your page.

Parameters can either be passed [as an array or a URL query type string (e.g. "display=0&format=0")](https://codex.wordpress.org/Function_Reference/wp_parse_args). Please note that WordPress parses all arguments as strings, thus booleans have to be 0 or 1 if used in query type strings whereas for arrays [real booleans](https://php.net/manual/en/language.types.boolean.php) should be used. Furthermore you have to use the prefix `stats_` to select different stats in a query_string. - For example: `stat_0=Community&stat_12=Pages Word-Count`.

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

[How-to for shortcodes](https://codex.wordpress.org/Shortcode_API)

**General Example:**

Enter the following text anywhere in a post or page to show your current pages-count:

`There are [generalstats_count stat=4] pages on my weblog`

**Available Shortcodes:**

`generalstats_output`

Invokes `$generalstats->output($params)`. Please note that you have to use the prefix `stats_` to select stats. - For example: `stat_0="Community" stat_12="Pages Word-Count"`

`generalstats_count`

Invokes `$generalstats->count($params)`.

= Filters =

[How-To for filters](https://codex.wordpress.org/Function_Reference/add_filter)

**Available Filters:**

`generalstats_defaults`

In case you want to set the default parameters globally rather than handing them over on every function call, you can add the [filter](https://codex.wordpress.org/Function_Reference/add_filter) `generalstats_defaults` in for example generalstats.php or your [own customization plugin](https://codex.wordpress.org/Writing_a_Plugin) (recommended).

Please note that parameters which you hand over to a function call (`$generalstats->output` or `$generalstats->count`) will always override the defaults parameters, even if they have been set by a filter or in the admin menu.

`generalstats_dashboard_widget`

Receives an array which is used for the dashboard-widget-function call to `$generalstats->output($params)`. `display` and `use_container` will automatically be set to true.

`generalstats_dashboard_right_now`

Receives an array which is used for the dashboard-right-now-box-function call to `$generalstats->output($params)`. `display` and `use_container` will automatically be set to true.

`generalstats_mail_stats_content`

Receives an array which is used for the mail-stats-function call to `$generalstats->output($params)`. `display` and `use_container` will automatically be set to false.

== Screenshots ==

1. This screenshot shows the Settings/GeneralStats Tab with the Selection GUI Section in the Admin Menu.

2. This picture presents the Preview Section of the GeneralStats Tab in the Admin Menu.

== Upgrade Notice ==

= 3.20 =

GeneralStats v3.20 needs at least WordPress 3.8.

= 3.00 =

This is a general code clean-up. - Please note that for GeneralStats v3.00 you need at minimum WordPress 3.3.

= 2.00 =

This is not only a feature but also a security update. - Thus, I'd strongly recommend all users of GeneralStats which have at least an environment of WordPress 2.8 or higher and PHP 5 to install this version!

== Changelog ==

= 3.21 =

* marked settings-page semantically
* enhanced uninstall procedure
* set appropriate http-status codes for wp_die()-calls

= 3.20 =

* made settings-page retina/hdpi-display ready
* adopted settings-page to be fully touch-display enabled
* renamed settings-page sections
* added "Auto-Submitted: auto-generated" to mail-headers
* moved vendor-code to separate directory
* fixed small potential bug
* cleaned-up code
* implemented minor design changes
* SSLified links
* added assets/icons

= 3.10 =

* implemented responsive web design on settings-page
* removed calls to screen_icon()
* extended length of format-parameters to provide space for example for mobile css-classes
* fixed some bugs
* removed filter generalstats_available_admin_colors
* cleaned-up code

= 3.00 =
* changed settings-page to jQuery
* improved usability
* discontinued support for Prototype
* updated jshashtable to 3.0
* removed legacy-code -> minimum-version of WordPress necessary is now 3.3
* removed option ajax_refresh_lib
* removed deprecated function GeneralStatsComplete()
* applied PHP 5 constructor in widget
* tested with PHP 5.4
* removed PHP closing tag before EOF
* removed reference sign on function calls
* adopted plugin-links to the new structure of wordpress.org
* cleaned-up code

= 2.35 =
* added message to settings-page about link manager
* made add-link to [link manager for WordPress 3.5 and higher optional](https://core.trac.wordpress.org/ticket/21307)

= 2.34 =

* adopted 'Defaults'-string to use WordPress internal i18n
* updated support section
* updated project-information

= 2.33 =

* changed handling of contextual help for WordPress 3.3
* adopted handling of default settings
* external files are now registered in init-hook

= 2.32 =

* added some WordPress 3.2 hooks

= 2.31 =

* fixed a bug with Ajax-update functionality in a SSL-environment. Thanks to huyz who has mentioned this in the forum https://wordpress.org/support/topic/plugin-generalstats-makes-https-call-to-admin-ajax-even-if-site-is-http

= 2.30 =

* revised the security model (replaced option `Allow anonymous Ajax Refresh Requests` with `All users can view stats` and added the option `Capability to view stats` to define the capability of a certain user to access the stats)
* de-coupling of Ajax-refresh-functions and output of `wp_localize_script` (GeneralStats is now compatible with [WP Minify](https://wordpress.org/plugins/wp-minify/))
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