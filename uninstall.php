<?php

/*
http://codex.wordpress.org/Function_Reference/register_uninstall_hook#uninstall.php

Please note: Due to the uninstall procedure of WordPress you have to delete left-over database entries in multisite environments manually for each blog.
*/

/*
security check
*/

if (!defined( 'WP_UNINSTALL_PLUGIN'))
	wp_die(__('Cheatin&#8217; uh?'), '', array('response' => 403));

if (!current_user_can('manage_options'))
	wp_die(__('You do not have sufficient permissions to manage options for this site.'), '', array('response' => 403));

/*
delete option-array
*/

delete_option('generalstats');

/*
delete widget-options
*/

delete_option('widget_generalstats');

/*
invalidate cache-block
*/

set_transient('generalstats', '', -1);

/*
delete transient cache-block
*/

delete_transient('generalstats');

$stats=array(0, 1, 2, 3, 4, 5, 6, 7, 10, 11, 12);

foreach($stats as $stat) {
	$transient='generalstats_stat_'.$stat;

	/*
	invalidate individual stat-cache
	*/

	set_transient($transient, '', -1);

	/*
	delete individual stat-transient
	*/

	delete_transient($transient);
}

/*
remove cron-entries for mail_stats
*/

wp_clear_scheduled_hook('generalstats_mail_stats');
