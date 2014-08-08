<?php

/*
Plugin Name: GeneralStats
Plugin URI: http://www.bernhard-riedl.com/projects/
Description: Counts the number of users, categories, posts, comments, pages, links, tags, link-categories, words in posts, words in comments and words in pages.
Author: Dr. Bernhard Riedl
Version: 3.10
Author URI: http://www.bernhard-riedl.com/
*/

/*
Copyright 2006-2014 Dr. Bernhard Riedl

Inspirations & Proof-Reading 2007-2014
by Veronika Grascher

This program is free software:
you can redistribute it and/or modify
it under the terms of the
GNU General Public License as published by
the Free Software Foundation,
either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope
that it will be useful,
but WITHOUT ANY WARRANTY;
without even the implied warranty of
MERCHANTABILITY or
FITNESS FOR A PARTICULAR PURPOSE.

See the GNU General Public License
for more details.

You should have received a copy of the
GNU General Public License
along with this program.

If not, see http://www.gnu.org/licenses/.
*/

/*
create global instance
*/

global $generalstats;

if (empty($generalstats) || !is_object($generalstats) || !$generalstats instanceof GeneralStats)
	$generalstats=new GeneralStats();

/*
Class
*/

class GeneralStats {

	/*
	prefix for fields, options, etc.
	*/

	private $prefix='generalstats';

	/*
	nicename for options-page,
	meta-data, etc.
	*/

	private $nicename='GeneralStats';

	/*
	plugin_url with trailing slash
	*/

	private $plugin_url;

	/*
	all stats
	*/

	private $stats=array();

	/*
	selected stats
	*/

	private $fallback_stats_selected=array();
	private $stats_selected=array();

	/*
	available stats
	*/

	private $fallback_stats_available=array();
	private $stats_available=array();

	private $fallback_defaults=array(
		'before_list' => '<ul>',
		'after_list' => '</ul>',
		'format_stat' => '<li><strong>%name</strong> %count</li>',
		'thousands_separator' => ',',
		'use_container' => true,
		'display' => true
	);

	/*
	current defaults
	(merged database and fallback_defaults)
	*/

	private $defaults=array();

	/*
	fallback options
	*/

	private $fallback_options=array(
		'use_ajax_refresh' => true,
		'ajax_refresh_time' => 30,
		'renew_nonce' => false,

		'cache_time' => 600,
		'use_action_hooks' => true,
		'rows_at_once' => 100,

		'dashboard_widget' => false,
		'dashboard_widget_capability' => 'read',
		'dashboard_right_now' => false,
		'dashboard_right_now_capability' => 'read',

		'mail_stats_schedule' => 'no',
		'count_html_tags' => false,
		'all_users_can_view_stats' => true,
		'view_stats_capability' => 'read',
		'debug_mode' => false,

		'section' => 'drag_and_drop'
	);

	/*
	current options
	(merged database and fallback_options)
	*/

	private $options=array();

	/*
	block_count holds the current number
	of elements which have been processed
	*/

	private $block_count=0;

	/*
	options-page sections/option-groups
	*/

	private $options_page_sections=array(
		'drag_and_drop' => array(
			'nicename' => 'Drag and Drop Layout',
			'callback' => 'drag_and_drop'
		),
		'expert' => array(
			'nicename' => 'Expert Settings',
			'callback' => 'expert',
			'fields' => array(
				'0' => 'Users',
				'1' => 'Categories',
				'2' => 'Posts',
				'3' => 'Comments',
				'4' => 'Pages',
				'5' => 'Links',
				'6' => 'Tags',
				'7' => 'Link-Categories',
				'10' => 'Words in Posts',
				'11' => 'Words in Comments',
				'12' => 'Words in Pages'
			)
		),
		'format' => array(
			'nicename' => 'Format',
			'callback' => 'format',
			'fields' => array(
				'before_list' => 'before List',
				'after_list' => 'after List',
				'format_stat' => 'Format of Stat-Entry',
				'thousands_separator' => 'Thousands Separator',
				'use_container' => 'Wrap output in div-container',
				'display' => 'Display Results'
			)
		),
		'ajax_refresh' => array(
			'nicename' => 'Ajax Refresh',
			'callback' => 'ajax_refresh',
			'fields' => array(
				'use_ajax_refresh' => 'Use Ajax Refresh',
				'ajax_refresh_time' => 'Ajax Refresh Time',
				'renew_nonce' => 'Renew nonce to assure continous updates'
			)
		),
		'performance' => array(
			'nicename' => 'Performance',
			'callback' => 'performance',
			'fields' => array(
				'cache_time' => 'Cache Time',
				'use_action_hooks' => 'Use Action Hooks',
				'rows_at_once' => 'Rows at once'
			)
		),
		'dashboard' => array(
			'nicename' => 'Dashboard',
			'callback' => 'dashboard',
			'fields' => array(
				'dashboard_widget' => 'Enable Dashboard Widget',
				'dashboard_widget_capability' => 'Capability to view Dashboard Widget',
				'dashboard_right_now' => 'Integrate in "Right Now" Box',
				'dashboard_right_now_capability' => 'Capability to integrate in "Right Now" Box'
			)
		),
		'administrative_options' => array(
			'nicename' => 'Administrative Options',
			'callback' => 'administrative_options',
			'fields' => array(
				'mail_stats_schedule' => 'Schedule of Mail with Stats updates',
				'count_html_tags' => 'Include HTML-Tags in Word-Counts',
				'all_users_can_view_stats' => 'All users can view stats',
				'view_stats_capability' => 'Capability to view stats',	
				'debug_mode' => 'Enable Debug-Mode'
			)
		),
		'preview' => array(
			'nicename' => 'Preview',
			'callback' => 'preview'
		)
	);

	/*
	Constructor
	*/

	function __construct() {

		/*
		fill stats arrays

		- stats
		- fallback_stats_selected
		- fallback_stats_available
		*/

		$this->fill_arrays();

		/*
		initialize object
		*/

		$this->set_plugin_url();
		$this->retrieve_settings();
		$this->register_hooks();
	}

	/*
	register js libraries
	*/

	function register_scripts() {

		/*
		jshashtable v3.0 by Tim Down
		http://www.timdown.co.uk/jshashtable/
		*/

		wp_register_script('jshashtable', $this->get_plugin_url().'js/jshashtable/hashtable.js', array(), '3.0');

		/*
		GeneralStats JS
		*/

		wp_register_script($this->get_prefix().'refresh', $this->get_plugin_url().'js/refresh.js', array('jquery', 'jshashtable'), '3.00');

		wp_register_script($this->get_prefix().'utils', $this->get_plugin_url().'js/utils.js', array('jquery'), '3.00');

		wp_register_script($this->get_prefix().'drag_and_drop', $this->get_plugin_url().'js/drag_and_drop.js', array('jquery', 'jquery-ui-sortable', 'jquery-effects-highlight', $this->get_prefix().'utils'), '3.10');

		wp_register_script($this->get_prefix().'settings_page', $this->get_plugin_url().'js/settings_page.js', array('jquery', $this->get_prefix().'drag_and_drop', $this->get_prefix().'utils'), '3.10');
	}

	/*
	register css styles
	*/

	function register_styles() {
		wp_register_style($this->get_prefix().'admin', $this->get_plugin_url().'css/admin.css', array(), '3.10');
	}

	/*
	register WordPress hooks
	*/

	private function register_hooks() {

		/*
		register externals
		*/

		add_action('init', array($this, 'register_scripts'));
		add_action('init', array($this, 'register_styles'));

		/*
		general
		*/

		add_filter('plugin_action_links', array($this, 'plugin_action_links'), 10, 2);

		add_action('admin_menu', array($this, 'admin_menu'));

		/*
		ajax refresh calls
		*/

		if ($this->get_option('use_ajax_refresh')) {

			/*
			include ajax refresh scripts
			*/

			add_action('wp_print_scripts', array($this, 'refresh_print_scripts'));

			/*
			allowed ajax actions
			*/

			add_action('wp_ajax_'.$this->get_prefix().'output', array($this, 'wp_ajax_refresh'));
			add_action('wp_ajax_'.$this->get_prefix().'count', array($this, 'wp_ajax_refresh'));

			/*
			anonymous ajax refresh requests
			can be restricted
			*/

			if ($this->get_option('all_users_can_view_stats')) {
				add_action('wp_ajax_nopriv_'.$this->get_prefix().'output', array($this, 'wp_ajax_refresh'));
				add_action('wp_ajax_nopriv_'.$this->get_prefix().'count', array($this, 'wp_ajax_refresh'));
			}
		}

		/*
		meta-data
		*/

		add_action('wp_head', array($this, 'head_meta'));
		add_action('admin_head', array($this, 'head_meta'));

		/*
		widgets
		*/

		add_action('widgets_init', array($this, 'widgets_init'));

		add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));

		add_action('activity_box_end', array($this, 'add_right_now_box'));

		/*
		shortcodes
		*/

		add_shortcode($this->get_prefix().'output', array($this, 'shortcode_output'));

		add_shortcode($this->get_prefix().'count', array($this, 'shortcode_count'));

		/*
		scheduled mail
		*/

		add_action($this->get_prefix().'mail_stats', array($this, 'mail_stats'));

		/*
		cache refresh hooks
		*/

		if ($this->get_option('use_action_hooks'))
			$this->add_cache_refresh_hooks();

		/*
		whitelist options
		*/

		add_action('admin_init', array($this, 'admin_init'));
	}

	/*
	add hooks for forcing a cache refresh
	*/

	private function add_cache_refresh_hooks() {

		/*
		users
		*/

		add_action('user_register', array($this, 'force_user_cache_refresh'));
		add_action('deleted_user', array($this, 'force_user_cache_refresh'));
		add_action('add_user_to_blog', array($this, 'force_user_cache_refresh'));
		add_action('remove_user_from_blog', array($this, 'force_user_cache_refresh'));

		/*
		user-posts relation
		*/

		add_action('deleted_user', array($this, 'force_post_cache_refresh'));
		add_action('remove_user_from_blog', array($this, 'force_post_cache_refresh'));

		/*
		user-pages relation
		*/

		add_action('deleted_user', array($this, 'force_page_cache_refresh'));
		add_action('remove_user_from_blog', array($this, 'force_page_cache_refresh'));

		/*
		Sabre Cooperation on 'deny early login'
		http://wordpress.org/plugins/sabre/
		*/

		add_action('sabre_accepted_registration', array($this, 'force_user_cache_refresh'));
		add_action('sabre_cancelled_registration', array($this, 'force_user_cache_refresh'));

		/*
		posts & pages
		*/

		add_action('save_post', array($this, 'save_post_force_cache_refresh'), 10, 2);

		/*
		posts
		*/

		add_action('after_delete_post', array($this, 'force_post_cache_refresh'));
		add_action('trashed_post', array($this, 'force_post_cache_refresh'));
		add_action('untrashed_post', array($this, 'force_post_cache_refresh'));

		/*
		pages
		*/

		add_action('after_delete_post', array($this, 'force_page_cache_refresh'));
		add_action('trashed_post', array($this, 'force_page_cache_refresh'));
		add_action('untrashed_post', array($this, 'force_page_cache_refresh'));

		/*
		comments
		*/

		add_action('comment_post', array($this, 'comment_status_force_cache_refresh'), 10, 2);
		add_action('edit_comment', array($this, 'force_comment_cache_refresh'));

		/*
		deleted/trashed comment can be realized
		by using wp_set_comment_status
		*/

		add_action('wp_set_comment_status', array($this, 'force_comment_cache_refresh'));

		/*
		special hooks for trashing/untrashing
		all comments of a certain post
		*/

		add_action('trashed_post_comments', array($this, 'force_comment_cache_refresh'));
		add_action('untrashed_post_comments', array($this, 'force_comment_cache_refresh'));

		/*
		links
		edit is necessary, because a
		link can be set from public
		to private or vice-versa
		*/

		add_action('add_link', array($this, 'force_link_cache_refresh')); 
		add_action('edit_link', array($this, 'force_link_cache_refresh'));
		add_action('deleted_link', array($this, 'force_link_cache_refresh'));

		/*
		terms (tags, categories & link-categories)
		edit is not necessary, because
		it does not influence the count
		*/

		add_action('created_term', array($this, 'force_term_cache_refresh'), 10, 3);
		add_action('delete_term', array($this, 'force_term_cache_refresh'), 10, 3);
	}

	/*
	GETTERS AND SETTERS
	*/

	/*
	getter for prefix
	true with trailing _
	false without trailing _
	*/

	function get_prefix($trailing_=true) {
		if ($trailing_)
			return $this->prefix.'_';
		else
			return $this->prefix;
	}

	/*
	getter for nicename
	*/

	function get_nicename() {
		return $this->nicename;
	}

	/*
	setter for plugin_url
	*/

	private function set_plugin_url() {
		$this->plugin_url=plugins_url('', __FILE__).'/';
	}

	/*
	getter for plugin_url
	*/

	private function get_plugin_url() {
		return $this->plugin_url;
	}

	/*
	increment for block_count
	*/

	private function increment_block_count() {
		$this->block_count++;
	}

	/*
	getter for block_count
	*/

	private function get_block_count() {
		return $this->block_count;
	}

	/*
	fill stats array
	*/

	private function fill_arrays() {
		global $wpdb;

		$this->stats=array(
			0 => array(
				'name' => 'Users',
				'sql_statement' => "$wpdb->users.ID) FROM $wpdb->users, $wpdb->usermeta WHERE $wpdb->users.ID = $wpdb->usermeta.user_id AND meta_key = '".$wpdb->prefix."capabilities'",
				'default' => true
			),
			1 => array(
				'name' => 'Categories',
				'sql_statement' => "$wpdb->terms.term_id) FROM $wpdb->terms INNER JOIN $wpdb->term_taxonomy ON $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id WHERE $wpdb->term_taxonomy.taxonomy='category'"
			),
			2 => array(
				'name' => 'Posts',
				'sql_statement' => "$wpdb->posts.ID) FROM $wpdb->posts WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type = 'post'",
				'default' => true
			),
			3 => array(
				'name' => 'Comments',
				'sql_statement' => "$wpdb->comments.comment_ID) FROM $wpdb->comments WHERE $wpdb->comments.comment_approved = '1'",
				'default' => true
			),
			4 => array(
				'name' => 'Pages',
				'sql_statement' => "$wpdb->posts.ID) FROM $wpdb->posts WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type = 'page'",
				'default' => true
			),
			5 => array(
				'name' => 'Links',
				'sql_statement' => "$wpdb->links.link_id) FROM $wpdb->links WHERE $wpdb->links.link_visible = 'Y'"
			),
			6 => array(
				'name' => 'Tags',
				'sql_statement' => "$wpdb->terms.term_id) FROM $wpdb->terms INNER JOIN $wpdb->term_taxonomy ON $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id WHERE $wpdb->term_taxonomy.taxonomy='post_tag'"
			),
			7 => array(
				'name' => 'Link-Categories',
				'sql_statement' => "$wpdb->terms.term_id) FROM $wpdb->terms INNER JOIN $wpdb->term_taxonomy ON $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id WHERE $wpdb->term_taxonomy.taxonomy='link_category'"
			),
			10 => array(
				'name' => 'Words in Posts',
				'sql_statement' => "FROM $wpdb->posts WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type = 'post'",
				'sql_attribute' => "$wpdb->posts.post_content",
				'sql_count_attribute' => "$wpdb->posts.ID"
			),
			11 => array(
				'name' => 'Words in Comments',
				'sql_statement' => "FROM $wpdb->comments WHERE $wpdb->comments.comment_approved = '1'",
				'sql_attribute' => "$wpdb->comments.comment_content",
				'sql_count_attribute' => "$wpdb->comments.comment_ID"
			),
			12 => array(
				'name' => 'Words in Pages',
				'sql_statement' => "FROM $wpdb->posts WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type = 'page'",
				'sql_attribute' => "$wpdb->posts.post_content",
				'sql_count_attribute' => "$wpdb->posts.ID"
			)
		);

		foreach($this->stats as $key => $stat) {

			/*
			should the stat be
			included by default
			*/

			if (!empty($stat['default']))
				$this->fallback_stats_selected[$key]=$stat['name'];

			/*
			if not, we add it to
			the available array
			*/

			else
				$this->fallback_stats_available[$key]=$stat['name'];
		}
	}

	/*
	getter for stat attribute
	*/

	private function get_stat_attribute($stat, $attribute) {

		/*
		does this stat exist?
		*/

		if (array_key_exists($stat, $this->stats)) {
			$stat_array=$this->stats[$stat];

			/*
			check if desired attribute exists for stat
			*/

			if (array_key_exists($attribute, $stat_array)) {
				return $stat_array[$attribute];
			}

			else {
				$this->log('attribute '.$attribute.' does not exist for stat '.$stat);
				return null;
			}
		}
		else {
			$this->log('stat '.$stat.' does not exist');
			return null;
		}
	}

	/*
	getter for stat name
	*/

	private function get_stat_name($stat) {
		return $this->get_stat_attribute($stat, 'name');
	}

	/*
	getter for stat sql_statement
	*/

	private function get_stat_sql_statement($stat) {
		return $this->get_stat_attribute($stat, 'sql_statement');
	}

	/*
	getter for stat sql_attribute
	*/

	private function get_stat_sql_attribute($stat) {
		return $this->get_stat_attribute($stat, 'sql_attribute');
	}

	/*
	getter for stat sql_count attribute
	*/

	private function get_stat_sql_count_attribute($stat) {
		return $this->get_stat_attribute($stat, 'sql_count_attribute');
	}

	/*
	getter for custom stat name
	*/

	private function get_stat_position($param) {
		$param=str_replace('stat_pos_', '', $param);

		$count=1;

		foreach ($this->stats_selected as $key => $stat_selected) {
			if ($key==$param)
				return $count;

			$count++;
		}
	}

	/*
	getter for custom stat name
	*/

	private function get_custom_stat_name($param) {
		$param=str_replace('stat_desc_', '', $param);

		if (isset($this->stats_selected[$param]))
			return $this->stats_selected[$param];
		else if (isset($this->stats_available[$param]))
			return $this->stats_available[$param];
		else
			return false;
	}

	/*
	getter for default parameter
	*/

	private function get_default($param) {
		if (isset($this->defaults[$param]))
			return $this->defaults[$param];
		else
			return false;
	}

	/*
	getter for default parameter
	*/

	private function get_option($param) {
		if (isset($this->options[$param]))
			return $this->options[$param];
		else
			return false;
	}

	/*
	retrieve settings from database
	and merge with fallback-settings
	*/

	private function retrieve_settings() {
		$settings=get_option($this->get_prefix(false));

		/*
		did we retrieve an non-empty
		settings-array which we can
		merge with the default settings?
		*/

		if (!empty($settings) && is_array($settings)) {

			/*
			process stats
			*/

			if (array_key_exists('stats_selected', $settings) && is_array($settings['stats_selected']) && !empty($settings['stats_selected']) && array_key_exists('stats_available', $settings) && is_array($settings['stats_available'])) {
				$this->stats_selected = $settings['stats_selected'];
				$this->log('setting selected stats to user selection '.var_export($this->stats_selected, true));

				$this->stats_available = $settings['stats_available'];
				$this->log('setting available stats to user selection '.var_export($this->stats_available, true));
			}

			/*
			process options-array
			*/

			if (array_key_exists('options', $settings) && is_array($settings['options'])) {
				$this->options = array_merge($this->fallback_options, $settings['options']);
				$this->log('merging fallback-options '.var_export($this->fallback_options, true).' with database options '.var_export($settings['options'], true));
			}

			/*
			process defaults-array
			*/

			if (array_key_exists('defaults', $settings) && is_array($settings['defaults'])) {
				$this->defaults = array_merge($this->fallback_defaults, $settings['defaults']);
				$this->log('merging fallback-defaults '.var_export($this->fallback_defaults, true).' with database defaults '.var_export($settings['defaults'], true));
			}
		}

		/*
		settings-array does not exist
		*/

		else {

			/*
			are we handling an update?

			check for trigger field
			if this field exist,
			we handle an update
			*/

			$maybe_cache=get_option('GeneralStats_Cache');

			if (!empty($maybe_cache)) {

				/*
				conduct upgrade
				*/

				$this->upgrade_v2();

				/*
				restart retrieval process
				*/

				$this->retrieve_settings();

				/*
				avoid further processing of
				first retrieve_settings
				function call
				*/

				return;
			}

			/*
			we intentionally write
			the fallbacks in the
			database to activate
			built-in caching
			and repair a broken
			settings-array
			*/

			else {
				update_option(
					$this->get_prefix(false),
					array(
						'stats_selected' => $this->fallback_stats_selected,
						'stats_available' => $this->fallback_stats_available,
						'defaults' => $this->fallback_defaults,
						'options' => $this->fallback_options
					)
				);
			}
		}

		/*
		if the stats_selected and the
		stats_available are both empty
		we use the fallback_stats
		for both arrays
		*/

		if (empty($this->stats_selected) && empty($this->stats_available)) {
			$this->stats_selected = $this->fallback_stats_selected;
			$this->log('using fallback-stats-selected '.var_export($this->fallback_stats_selected, true));

			$this->stats_available = $this->fallback_stats_available;
			$this->log('using fallback-stats-available '.var_export($this->fallback_stats_available, true));
		}

		/*
		if the settings have not been set
		we use the fallback-options array instead
		*/

		if (empty($this->options)) {
			$this->options = $this->fallback_options;
			$this->log('using fallback-options '.var_export($this->fallback_options, true));
		}

		/*
		if the settings have not been set
		we use the fallback-defaults array instead
		*/

		if (empty($this->defaults)) {
			$this->defaults = $this->fallback_defaults;
			$this->log('using fallback-defaults '.var_export($this->fallback_defaults, true));
		}

		/*
		maybe upgrade to v2.40?
		*/

		if (array_key_exists('anonymous_ajax_refresh', $this->options))
			$this->upgrade_v24();

		/*
		maybe upgrade to v3.00?
		*/

		if (array_key_exists('ajax_refresh_lib', $this->options))
			$this->upgrade_v30();

		$this->log('setting options to '.var_export($this->options, true));

		$this->log('setting defaults to '.var_export($this->defaults, true));
	}

	/*
	Sanitize and validate input
	Accepts an array, return a sanitized array
	*/

	function settings_validate($input) {

		/*
		options updated =>
		cache-block has to be refreshed
		*/

		set_transient($this->get_prefix(false), '', -1);

		/*
		we handle a reset call
		*/

		if (isset($input['reset'])) {
			return array(
				'stats_selected' => $this->fallback_stats_selected,
				'stats_available' => $this->fallback_stats_available,
				'defaults' => $this->fallback_defaults,
				'options' => $this->fallback_options
			);
		}

		/*
		check-fields will be
		converted to true/false
		*/

		$check_fields=array(
			'use_container',
			'display',
			'use_ajax_refresh',
			'renew_nonce',
			'dashboard_widget',
			'dashboard_right_now',
			'use_action_hooks',
			'count_html_tags',
			'all_users_can_view_stats',
			'debug_mode'
		);

		foreach ($check_fields as $check_field) {
			$input[$check_field] = (isset($input[$check_field]) && $input[$check_field] == 1) ? true : false;
		}

		/*
		these text-fields should not be empty
		*/

		$text_fields=array(
			'ajax_refresh_time',
			'format_stat',
			'cache_time',
			'rows_at_once'
		);

		foreach ($text_fields as $text_field) {
			if (isset($input[$text_field]) && strlen($input[$text_field])<1)
				unset($input[$text_field]);
		}

		/*
		selected capabilities have to be
		within available capabilities
		*/

		$capability_fields=array(
			'dashboard_widget',
			'dashboard_right_now',
			'view_stats',
			'calculator'
		);

		$capabilities=$this->get_all_capabilities();

		foreach ($capability_fields as $capability_field) {
			if (isset($input[$capability_field.'_capability']) && !in_array($input[$capability_field.'_capability'], $capabilities))
				unset($input[$capability_field.'_capability']);
		}

		/*
		selected cron-schedule have to be
		within available schedules
		*/

		$schedules=wp_get_schedules();
		$schedules['no'] = array();

		if (!array_key_exists($input['mail_stats_schedule'], $schedules))
			unset($input['mail_stats_schedule']);

		/*
		1 <= ajax_refresh_time <= 3600 (seconds)
		*/

		if (array_key_exists('ajax_refresh_time', $input))
			if (!$this->is_integer($input['ajax_refresh_time']) || $input['ajax_refresh_time']<1 || $input['ajax_refresh_time']>3600)
				$input['ajax_refresh_time']=$this->fallback_options['ajax_refresh_time'];

		/*
		0 <= cache_time <= 3600 (seconds)
		*/

		if (array_key_exists('cache_time', $input))
			if (!$this->is_integer($input['cache_time']) || $input['cache_time']<0 || $input['cache_time']>3600)
				$input['cache_time']=$this->fallback_options['cache_time'];

		/*
		1 <= rows_at_once <= 10000 (sql-rows)
		*/

		if (array_key_exists('rows_at_once', $input))
			if (!$this->is_integer($input['rows_at_once']) || $input['rows_at_once']<1 || $input['rows_at_once']>10000)
				$input['rows_at_once']=$this->fallback_options['rows_at_once'];

		/*
		loop through all available stats
		to find out which stats have been
		selected by the user
		*/

		$stats_selected=array();
		$stats_available=array();

		/*
		temporary stats array
		*/

		$selected_stats=array();

		foreach($this->stats as $key => $stat) {
			$settings_pos='stat_pos_'.$key;

			/*
			has the stat been set
			in the input?
			*/

			if (array_key_exists($settings_pos, $input) && !empty($input[$settings_pos])) {
				$selected_stats[$key]=$input[$settings_pos];
			}

			/*
			include in available stats array
			*/

			else {
				$settings_desc='stat_desc_'.$key;

				/*
				a description has been set
				by the user
				*/

				if (array_key_exists($settings_desc, $input) && !empty($input[$settings_desc])) {
					$stats_available[$key]=$input[$settings_desc];
				}

				/*
				if no description has been set,
				use default description
				*/

				else {
					$stats_available[$key]=$this->get_stat_name($key);
				}
			}
				
		}

		/*
		sort stats
		*/

		asort($selected_stats);

		/*
		retrieve stat-descriptions
		and build sorted array
		*/

		foreach($selected_stats as $key => $selected_stat) {
			$settings_desc='stat_desc_'.$key;

			/*
			a description has been set
			by the user
			*/

			if (array_key_exists($settings_desc, $input) && !empty($input[$settings_desc])) {
				$stats_selected[$key]=$input[$settings_desc];
			}

			/*
			if no description has been set,
			use default description
			*/

			else {
				$stats_selected[$key]=$this->get_stat_name($key);
			}
		}

		/*
		if the user did not select any stats,
		we use the defaults instead
		*/

		if (empty($stats_selected)) {
			$stats_selected=$this->fallback_stats_selected;
			$stats_available=$this->fallback_stats_available;
		}

		/*
		include options
		*/

		$options=$this->fallback_options;

		foreach($options as $option => $value) {
			if (array_key_exists($option, $input))
				$options[$option]=$input[$option];
		}

		/*
		include defaults
		*/

		$defaults=$this->fallback_defaults;

		foreach($defaults as $default => $value) {
			if (array_key_exists($default, $input))
				$defaults[$default]=$input[$default];
		}

		$ret_val=array();

		$ret_val['stats_selected']=$stats_selected;
		$ret_val['stats_available']=$stats_available;
		$ret_val['options']=$options;
		$ret_val['defaults']=$defaults;

		/*
		if the user has selected to
		receive the stats by email
		*/

		if ($options['mail_stats_schedule']!='no') {

			/*
			check if the user want's to change the schedule
			*/

			if (wp_get_schedule($this->get_prefix().'mail_stats') && wp_get_schedule($this->get_prefix().'mail_stats')!=$options['mail_stats_schedule']) {
				wp_clear_scheduled_hook($this->get_prefix().'mail_stats');
			}

			/*
			so now we check if no event has been scheduled
			*/

			if (!wp_get_schedule($this->get_prefix().'mail_stats')) {

				/*
				if no event has been scheduled so far,
				or the schedule has been updated, we
				insert it into the cron system
				*/

				wp_schedule_event(time(), $options['mail_stats_schedule'], $this->get_prefix().'mail_stats');
			}
		}

		/*
		user selected to remove the schedule
		*/

		else {
			if (wp_get_schedule($this->get_prefix().'mail_stats')) {
				wp_clear_scheduled_hook($this->get_prefix().'mail_stats');
			}
		}

		return $ret_val;
	}

	/*
	upgrade options to GeneralStats v2
	*/

	private function upgrade_v2() {
 
		$this->log('upgrade options to '.$this->get_nicename().' v2');

		$fieldsPre='GeneralStats_';
		$sectionPost='_Section';
		$fieldsPost_Position='_Position';
		$fieldsPost_Description='_Description';

		$fields=array(
			0 => 'Users',
			1 => 'Categories',
			2 => 'Posts',
			3 => 'Comments',
			4 => 'Pages',
			5 => 'Links',
			6 => 'Tags',
			7 => 'Link-Categories',
			10 => 'Words_in_Posts',
			11 => 'Words_in_Comments',
			12 => 'Words_in_Pages'
		);

		/*
		this array will hold all old settings
		as if we would handle a 'save changes'
		call of the options-page
		*/

		$settings=array();

		/*
		loop through all stats
		and store
		position and description		
		*/

		foreach ($fields as $key => $field) {
			$query_pos=$fieldsPre.$field.$fieldsPost_Position;
			$query_desc=$fieldsPre.$field.$fieldsPost_Description;

			$settings['stat_pos_'.$key]=get_option($query_pos);
			$settings['stat_desc_'.$key]=get_option($query_desc);

			/*
			remove old stat-settings
			*/

			delete_option($query_pos);
			delete_option($query_desc);
		}

		/*
		key field holds the old option name
		value the new option name
		*/

		$upgrade_options=array(
			'before_List' => 'before_list',
			'after_List' => 'after_list',
			'before_Tag' => 'before_tag',
			'after_Tag' => 'after_tag',
			'before_Details' => 'before_details',
			'after_Details' => 'after_details',
			'Thousand_Delimiter' => 'thousands_separator',
			'Use_Ajax_Refresh' => 'use_ajax_refresh',
			'Refresh_Time' => 'ajax_refresh_time',
			'Integrate_Right_Now' => 'dashboard_right_now',
			'Cache_Time' => 'cache_time',
			'Use_Action_Hooks' => 'use_action_hooks',
			'Rows_at_Once' => 'rows_at_once'
		);

		/*
		loop through all available old options
		*/

		foreach ($upgrade_options as $old_option => $new_option) {
			$old_option_value=get_option($fieldsPre.$old_option);
			$settings[$new_option]=$old_option_value;

			$this->log('option '.$new_option.' set to '.$old_option_value);

			/*
			remove old option
			*/

			delete_option($fieldsPre.$old_option);
		}

		/*
		old before_Tag, after_Tag,
		before_Details and before_Details
		will be merged to format_stat
		*/

		$settings['format_stat']=$settings['before_tag'].'%name'.$settings['after_tag'].$settings['before_details'].'%count'.$settings['after_details'];

		unset($settings['before_tag']);
		unset($settings['after_tag']);
		unset($settings['before_details']);
		unset($settings['after_details']);

		/*
		delete section settings
		*/

		$sections=array(
			'Instructions',
			'Static_Tags',
			'CSS_Tags',
			'Administrative_Options'
		);

		foreach ($sections as $section) {
			delete_option($fieldsPre.$section.$sectionPost);
		}

		/*
		include check_fields
		which need to be set true
		*/

		$settings['use_container']='1';
		$settings['display']='1';
		$settings['all_users_can_view_stats']='1';

		/*
		validate retrieved settings
		*/

		$settings=$this->settings_validate($settings);

		/*
		store new settings
		*/

		update_option($this->get_prefix(false), $settings);

		/*
		delete obsolete cache files
		(transients are now used instead)
		*/

		delete_option($fieldsPre.'Cache');
		delete_option($fieldsPre.'Force_Cache_Refresh');
		delete_option($fieldsPre.'Last_Cache_Time');

		$this->log('upgrade finished. - retrieved options are: '.var_export($settings, true));
	}

	/*
	upgrade options to GeneralStats v2.40
	*/

	private function upgrade_v24() {

		$this->log('upgrade options to '.$this->get_nicename().' v2.40');

		/*
		rename setting
		*/

		$this->options['all_users_can_view_stats']=$this->options['anonymous_ajax_refresh'];

		unset($this->options['anonymous_ajax_refresh']);

		/*
		combine settings-array
		*/

		$settings=array();

		$settings['stats_selected']=$this->stats_selected;
		$settings['stats_available']=$this->stats_available;
		$settings['defaults']=$this->defaults;
		$settings['options']=$this->options;

		/*
		store new settings
		*/

		update_option($this->get_prefix(false), $settings);

		$this->log('upgrade finished. - retrieved options are: '.var_export($settings, true));
	}

	/*
	upgrade options to GeneralStats v3.00
	*/

	private function upgrade_v30() {

		$this->log('upgrade options to '.$this->get_nicename().' v3.00');

		/*
		remove setting
		*/

		unset($this->options['ajax_refresh_lib']);

		/*
		combine settings-array
		*/

		$settings=array();

		$settings['stats_selected']=$this->stats_selected;
		$settings['stats_available']=$this->stats_available;
		$settings['defaults']=$this->defaults;
		$settings['options']=$this->options;

		/*
		store new settings
		*/

		update_option($this->get_prefix(false), $settings);

		$this->log('upgrade finished. - retrieved options are: '.var_export($settings, true));
	}

	/*
	merges parameter array with defaults array
	defaults-array can be changed with filter
	'generalstats_defaults'
	*/

	private function fill_default_parameters($params) {

		/*
		apply filter generalstats_defaults
		*/

		$filtered_defaults=apply_filters($this->get_prefix().'defaults', $this->defaults);

		/*
		merge filtered defaults with params
		params overwrite merged defaults
		*/

		return wp_parse_args($params, $filtered_defaults);
	}

	/*
	UTILITY FUNCTIONS
	*/

	/*
	checks if a value is an integer

	regex taken from php.net
	by mark at codedesigner dot nl
	*/

	private function is_integer($value) {
		return preg_match('@^[-]?[0-9]+$@', $value);
	}

	/*
	shows log messages on screen

	if debug_mode is set to true
	optionally executes trigger_error
	if we're handling an error
	*/

	private function log($message, $status=0) {
		$date_format = get_option('date_format');
		$time_format = get_option('time_format');

		$log_line=gmdate($date_format.' '.$time_format, current_time('timestamp')).' ';

		/*
		determine the log line's prefix
		*/

		if ($status==0)
			$log_line.='INFO';
		else if ($status==-1)
			$log_line.='<strong>ERROR</strong>';
		else if ($status==-2)
			$log_line.='WARNING';
		else if ($status==1)
			$log_line.='SQL';

		/*
		append message
		*/

		$log_line.=' '.$message.'<br />';

		/*
		output message to screen
		*/

		if ($this->get_option('debug_mode'))
			echo($log_line);

		/*
		output message to file
		*/

		if ($status<0)
			trigger_error($message);
	}

	/*
	called by wp_ajax_* and wp_ajax_nopriv_* hooks
	*/

	private function do_ajax_refresh($_ajax_nonce=true) {

		$action=$_REQUEST['action'];
		$query_string='';

		if (isset($_REQUEST['query_string']))
			$query_string=$_REQUEST['query_string'];

		/*
		security check
		*/

		if (!$this->get_option('all_users_can_view_stats') && !current_user_can($this->get_option('view_stats_capability')))
			die('-1');

		$security_string=$action.str_replace(array('\n', "\n"), '', $query_string);

		check_ajax_referer($security_string);

		/*
		convert query_string to params-array
		*/

		$params=array();

		$ajax_musts=array(
			'use_container' => true,
			'display' => false,
			'no_refresh' => true
		);

		/*
		parse retrieved query_string
		*/

		if (!empty($query_string))
			$params=wp_parse_args($query_string);

		$params=array_merge($params, $ajax_musts);

		/*
		remove leading prefix from action
		*/

		$method=str_replace($this->get_prefix(), '', $action);

		/*
		prepare json object
		*/

		$json_params=array();

		/*
		we only provide a renewed _ajax_nonce
		if the admin has chosen to
		*/

		if ($this->get_option('renew_nonce')) {

			/*
			return updated (2nd tick) _ajax_nonce
			*/

			if ($_ajax_nonce===true)
				$json_params['_ajax_nonce']=wp_create_nonce($security_string);

			/*
			use provided _ajax_nonce
			*/

			else if (!empty($_ajax_nonce))
				$json_params['_ajax_nonce']=$_ajax_nonce;
		}

		/*
		call function output/count
		*/

		$json_params['result']=call_user_func(array($this, $method), $params);

		$this->output_json($json_params);
	}

	/*
	outputs a json-object
	*/

	private function output_json($params) {

		if (!is_array($params)) {
			echo("-1");
			return -1;
		}

		$ret_val='';

		/*
		use built-in function if available
		*/

		if (function_exists('json_encode'))
			$ret_val=json_encode($params);

		/*
		or do our own json-encoding
		*/

		else {

			/*
			prepare json string
			*/

			$ret_val='{';

			foreach ($params as $key => $param)
				$ret_val.='"'.$key.'":"'.str_replace(array('\\', '"'), array('\\\\', '\"'), $param).'",';

			$ret_val=substr($ret_val, 0, -1);

			$ret_val.='}';
		}

		header('Content-type: application/json');
		echo($ret_val);
	}

	/*
	returns all capabilities without 'level_'
	*/

	function get_all_capabilities() {
		$wp_roles=new WP_Roles();
		$names=$wp_roles->get_names();

		$all_caps=array();

		foreach($names as $name_key => $name) {
			$wp_role=$wp_roles->get_role($name_key);
			$role_caps=$wp_role->capabilities;

			foreach($role_caps as $cap_key => $role_cap) {
				if (!in_array($cap_key, $all_caps) && strpos($cap_key, 'level_')===false)
					$all_caps[]=$cap_key;
			}
		}

		asort($all_caps);

		return $all_caps;
	}

	/*
	send Mail to user
	*/

	private function send_mail($subject, $message) {
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		wp_mail(get_option('admin_email'), '['.$blogname.'] '. $subject, $message);
	}

	/*
	CALLED BY HOOKS
	(and therefore public)
	*/

	/*
	called by wp_ajax_* and wp_ajax_nopriv_* hooks
	*/

	function wp_ajax_refresh() {
		$this->do_ajax_refresh();
		exit;
	}

	/*
	Options Page
	*/

	function options_page() {
		$this->settings_page($this->options_page_sections, 'manage_options', 'settings', true);
	}

	/*
	Options Page Help Tab
	*/

	function options_page_help_tab() {
		$this->add_help_tab($this->options_page_help());
	}

	/*
	white list options
	*/

	function admin_init() {
		register_setting($this->get_prefix(false), $this->get_prefix(false), array($this, 'settings_validate'));

		/*
		Sabre Cooperation on 'deny early login'
		http://wordpress.org/plugins/sabre/
		*/

		if (defined('SABRE_TABLE'))
			$this->options_page_sections['expert']['fields']['0'].='<br />(in Cooperation with <a href="'.admin_url('tools.php?page=sabre').'">Sabre</a>)';

		$this->add_settings_sections($this->options_page_sections, 'settings');
	}

	/*
	add GeneralStats to WordPress Settings Menu
	*/

	function admin_menu() {
		$options_page=add_options_page($this->get_nicename(), $this->get_nicename(), 'manage_options', $this->get_prefix(false), array($this, 'options_page'));

		add_action('admin_print_scripts-'.$options_page, array($this, 'settings_print_scripts'));
		add_action('admin_print_styles-'.$options_page, array($this, 'admin_print_styles'));
		add_action('load-'.$options_page, array($this, 'options_page_help_tab'));
	}

	/*
	adds meta-information to HTML header
	*/

	function head_meta() {
		echo("<meta name=\"".$this->get_nicename()."\" content=\"3.10\"/>\n");
	}

	/*
	add dashboard widget
	*/

	function add_dashboard_widget() {
		if ($this->get_option('dashboard_widget') && current_user_can($this->get_option('dashboard_widget_capability')))
			wp_add_dashboard_widget($this->get_prefix().'dashboard_widget', $this->get_nicename(), array($this, 'dashboard_widget_output'));
	}

	/*
	dashboard widget
	*/

	function dashboard_widget_output() {

		/*
		security check
		*/

		if (!current_user_can($this->get_option('dashboard_widget_capability')))
			return;

		$this->current_stats_block('dashboard_widget');
	}

	/*
	add stats to dashboard's right now box
	inspired by Stephanie Leary
	http://sillybean.net/
	*/

	function add_right_now_box() {
		if ($this->get_option('dashboard_right_now') && current_user_can($this->get_option('dashboard_right_now_capability'))) {
			echo('<p></p>');

			$this->current_stats_block('dashboard_right_now');
		}
	}

	/*
	called from widget_init hook
	*/

	function widgets_init() {
		register_widget('WP_Widget_'.$this->get_nicename());
	}

	/*
	adds the javascript code for
	re-occuring stats-updates
	*/

	function refresh_print_scripts() {

		/*
		security check
		*/

		if (!$this->get_option('all_users_can_view_stats') && !current_user_can($this->get_option('view_stats_capability')))
			return;

		wp_enqueue_script($this->get_prefix().'refresh');

		$security_string=$this->get_prefix().'output';
		$_ajax_nonce=wp_create_nonce($security_string);

		/*
		make sure that Ajax-queries use
		the same protocol as the site
		*/

		$ajax_url=admin_url('admin-ajax.php', is_ssl() ? 'https' : 'http');

		wp_localize_script(
			$this->get_prefix().'refresh',
			$this->get_prefix().'refresh_settings',
			array(
				'ajax_url' => $ajax_url,
				'_ajax_nonce' => $_ajax_nonce,
				'refresh_time' => $this->get_option('ajax_refresh_time')
			)
		);
	}

	/*
	loads the necessary java-scripts
	for the options-page
	*/

	function settings_print_scripts() {
		wp_enqueue_script($this->get_prefix().'settings_page');
	}

	/*
	adds a settings link in the plugin-tab
	*/

	function plugin_action_links($links, $file) {
		if ($file == plugin_basename(__FILE__))
			$links[] = '<a href="options-general.php?page='.$this->get_prefix(false).'">' . __('Settings') . '</a>';

		return $links;
	}

	/*
	includes the necessary CSS-styles
	for the admin-page
	*/

	function admin_print_styles() {
		wp_enqueue_style($this->get_prefix().'admin');
	}

	/*
	mails the stats
	*/

	function mail_stats() {
		$unfiltered_params=array(
			'before_list' => '',
			'after_list' => '',
			'format_stat' => "%name: %count\n"
		);

		$filtered_params=apply_filters($this->get_prefix().'mail_stats_content', $unfiltered_params);

		$params=array(
			'use_container' => false,
			'display' => false
		);

		$text=$this->output(array_merge($filtered_params, $params));

		$this->send_mail($this->get_nicename(), $text);
	}

	/*
	LOGIC FUNCTIONS
	*/

	/*
	internal function of output
	*/

	private function _output($params=array()) {

		/*
		log call
		*/

		$this->log('function _output, $params='.var_export($params, true));

		/*
		security check
		*/

		if (!$this->get_option('all_users_can_view_stats') && !current_user_can($this->get_option('view_stats_capability')))
			throw new Exception('You are not authorized to view stats!');

		/*
		fill params with default-values
		*/

		$params=$this->fill_default_parameters($params);

		/*
		try to parse single stats
		or use default stats
		*/

		if (!array_key_exists('stats_selected', $params) || empty($params['stats_selected']) || !is_array($params['stats_selected'])) {
			$maybe_stats_selected=array();

			/*
			try to include single stats
			into array
			*/

			foreach ($params as $key => $param) {

				/*
				we probably found a stat setting
				*/

				if (strpos($key, 'stat_')===0) {

					$maybe_stat_key=substr($key, 5, strlen($param));

					if (strlen($maybe_stat_key)>0)
						$maybe_stats_selected[$maybe_stat_key]=$param;

					unset($params[$key]);
				}
			}

			if (!empty($maybe_stats_selected))
				$params['stats_selected']=$maybe_stats_selected;
			else
				$params['stats_selected']=$this->stats_selected;
		}

		/*
		process stats-array:
		remove optional
		'stat_' prefix from keys
		*/

		else {
			$temp_stats_selected=array();

			foreach($params['stats_selected'] as $key => $stat) {
				$temp_stats_selected[str_replace('stat_', '', $key)]=$stat;
			}

			$params['stats_selected']=$temp_stats_selected;
		}

		/*
		include cache_time
		*/

		$params['cache_time'] = $this->get_option('cache_time');

		$cache=array();

		/*
		do we handle a cache-call?
		*/

		if ($params['cache_time']>0)
			$cache=$this->cache_stats_block($params);

		/*
		log call
		*/

		$this->log('function _output, merged with defaults, $params='.var_export($params, true));

		/*
		create stats output for query
		*/

		$stats_block=$this->format_stats_block($params, $cache);

		$ret_val='';

		$refreshable=$this->is_output_refreshable($params);

		/*
		set block-id
		*/

		if (!isset($params['id']))
			$params['id']=$this->get_block_count();

		/*
		shall we wrap the output in a container?
		*/

		if ($params['use_container']) {
			$ret_val.='<div ';

			if (!$refreshable)
				$ret_val.='id="'.$this->get_prefix().'block_'.$params['id'].'" ';

			$ret_val.='class="'.$this->get_prefix(false).'-';

			if ($refreshable)
				$ret_val.='refreshable-';

			$ret_val.='output"';

			if (!empty($params['format_container']))
				$ret_val.=' style="'.$params['format_container'].'"';

			$ret_val.='>';
		}

		$ret_val.=$stats_block;

		if ($params['use_container'])
			$ret_val.='</div>';

		/*
		produce js refresh code
		*/

		if ($this->get_option('use_ajax_refresh') && $params['use_container'] && !$refreshable && !isset($params['no_refresh'])) {

			/*
			allowed query params for ajax refresh call
			*/

			$refresh_query_params=array(
				'id',
				'before_list',
				'after_list',
				'format_stat',
				'thousands_separator',
				'format_container'
			);

			$query_string='';

			/*
			build ajax query-string
			*/

			foreach ($params as $key => $param)
				if (in_array($key, $refresh_query_params))
					$query_string.=$key.'='.urlencode($param).'&';

			foreach ($params['stats_selected'] as $key => $stat) 				$query_string.='stat_'.$key.'='.urlencode($stat).'&';

			$query_string=substr($query_string, 0, -1);

			?>

			<script type="text/javascript">

			/* <![CDATA[ */

			var <?php echo($this->get_prefix()); ?>params_<?php echo($params['id']); ?>=<?php echo($this->get_prefix()); ?>refresh_create_params('<?php echo($this->get_prefix()); ?>block_<?php echo($params['id']); ?>', '<div id="<?php echo($this->get_prefix().'block_'.$params['id']); ?>" class="<?php echo($this->get_prefix(false)); ?>-output"');

			<?php
			$security_string=$this->get_prefix().'output'.str_replace(array('\n', "\n"), '', $query_string);
			$_ajax_nonce=wp_create_nonce($security_string);
			?>

			var <?php echo($this->get_prefix()); ?>query_params_<?php echo($params['id']); ?>=<?php echo($this->get_prefix()); ?>refresh_create_query_params_output('<?php echo($_ajax_nonce); ?>', '<?php echo($query_string); ?>');

			<?php echo($this->get_prefix()); ?>initiate_refresh(<?php echo($this->get_prefix()); ?>params_<?php echo($params['id']); ?>, <?php echo($this->get_prefix()); ?>query_params_<?php echo($params['id']); ?>);

			/* ]]> */

			</script>

		<?php }

		$this->increment_block_count();

		/*
		echo results
		*/

		if ($params['display'])
			echo $ret_val;

		/*
		return results
		*/

		else
			return $ret_val;

	}

	/*
	count individual stat
	*/

	private function _count($params=array()) {

		/*
		log call
		*/

		$this->log('function _count, $params='.var_export($params, true));

		/*
		security check
		*/

		if (!$this->get_option('all_users_can_view_stats') && !current_user_can($this->get_option('view_stats_capability')))
			throw new Exception('You are not authorized to view stats!');

		/*
		fill params with default-values
		*/

		$params=$this->fill_default_parameters($params);

		/*
		check for stat
		*/

		if (!isset($params['stat']))
			throw new Exception('no stat in parameters!');

		/*
		include cache-time
		*/

		$params['cache_time'] = $this->get_option('cache_time');

		/*
		log call
		*/

		$this->log('function _count, merged with defaults, $params='.var_export($params, true));

		/*
		retrieve count
		*/

		$count=$this->get_count($params['stat'], $params['cache_time']);

		/*
		format count
		*/

		$count=number_format($count, 0, '', $params['thousands_separator']);

		/*
		set block-id
		*/

		if (!isset($params['id']))
			$params['id']=$this->get_block_count();

		$ret_val='';

		/*
		shall we wrap the output in a container?
		*/

		if ($params['use_container']) {
			$ret_val.='<span id="'.$this->get_prefix().'block_'.$params['id'].'" class="'.$this->get_prefix(false).'-count"';

			if (!empty($params['format_container']))
				$ret_val.=' style="'.$params['format_container'].'"';

			$ret_val.='>';
		}

		$ret_val.=$count;

		if ($params['use_container'])
			$ret_val.='</span>';

		/*
		produce js refresh code
		*/

		if ($this->get_option('use_ajax_refresh') && $params['use_container'] && !isset($params['no_refresh'])) {

			/*
			allowed query params for ajax refresh call
			*/

			$refresh_query_params=array(
				'id',
				'stat',
				'thousands_separator',
				'format_container'
			);

			$query_string='';

			/*
			build ajax query-string
			*/

			foreach ($params as $key => $param)
				if (in_array($key, $refresh_query_params))
					$query_string.=$key.'='.urlencode($param).'&';

			$query_string=substr($query_string, 0, -1);

			?>

			<script type="text/javascript">

			/* <![CDATA[ */

			var <?php echo($this->get_prefix()); ?>params_<?php echo($params['id']); ?> = <?php echo($this->get_prefix()); ?>refresh_create_params('<?php echo($this->get_prefix()); ?>block_<?php echo($params['id']); ?>', '<span id="<?php echo($this->get_prefix().'block_'.$params['id']); ?>" class="<?php echo($this->get_prefix(false)); ?>-count"');

			<?php
			$security_string=$this->get_prefix().'count'.str_replace(array('\n', "\n"), '', $query_string);
			$_ajax_nonce=wp_create_nonce($security_string);
			?>

			var <?php echo($this->get_prefix()); ?>query_params_<?php echo($params['id']); ?>=<?php echo($this->get_prefix()); ?>refresh_create_query_params_count('<?php echo($_ajax_nonce); ?>', '<?php echo($query_string); ?>');

			<?php echo($this->get_prefix()); ?>initiate_refresh(<?php echo($this->get_prefix()); ?>params_<?php echo($params['id']); ?>, <?php echo($this->get_prefix()); ?>query_params_<?php echo($params['id']); ?>);

			/* ]]> */

			</script>

		<?php }

		$this->increment_block_count();

		/*
		echo result
		*/

		if ($params['display'])
			echo($ret_val);

		/*
		return result
		*/

		else
			return $ret_val;
	}

	/*
	internal function get_count
	to assure that the cache-time
	is only handled internally
	*/

	private function get_count($stat, $cache_time) {

		$result=0;

		/*
		check if stat exists
		*/

		if (array_key_exists($stat, $this->stats)) {

			/*
			process and if necessary
			update count-cache
			*/

			if ($cache_time>0) {

				/*
				get cache
				*/

				$transient_name=$this->get_prefix().'stat_'.$stat;

				$result = get_transient($transient_name);

				/*
				the cache will be refreshed
				if the cache is empty (either
				because it is outdated or has
				been intentially deleted)
				*/

				if ($result===false || strlen($result)<1 || !$this->is_integer($result) || $result<0) {
					$this->log('update cache for '.$this->get_stat_name($stat).', cache-time='.$cache_time);

					/*
					retrieve stat
					*/

					$result=$this->get_count_fork($stat);

					/*
					store cache
					*/

					$this->log('update transient '.$transient_name);

					set_transient($transient_name, $result, $cache_time);

				}

				/*
				use cached results
				*/

				else {
					$this->log('use cached results for '.$this->get_stat_name($stat));
				}

			}

			/*
			we handle a cache-less environment
			*/

			else {
				$this->log('cache disabled, query for results');

				/*
				process stat
				*/

				$result=$this->get_count_fork($stat);
			}
		}

		else {
			throw new Exception('stat '.$this->get_stat_name($stat). ' does not exist!');
		}

		$this->log('result for '.$this->get_stat_name($stat).'='.$result);

		return $result;
	}

	/*
	returns sql results of all stats
	(word_count and row_count)
	*/

	private function get_count_fork($stat) {

		/*
		for values between 10 and 20
		extra processing is needed
		*/

		if ($stat>=10 && $stat<=20)
			return $this->get_word_count($stat);

		/*
		for all other attributes
		use sql select count statement
		*/

		else
			return $this->get_row_count($stat);
	}

	/*
	count the number of sql-rows
	*/

	private function get_row_count($stat) {

		global $wpdb;

		$result=0;

		$sql_stat=$this->get_stat_sql_statement($stat);

		/*
		Sabre Cooperation on 'deny early login'
		http://wordpress.org/plugins/sabre/
		*/

		if ($stat==0 && defined('SABRE_TABLE'))
			$sql_stat="u.ID) FROM $wpdb->users as u, ".esc_sql(SABRE_TABLE)." as s, $wpdb->usermeta as m WHERE u.ID = m.user_id AND m.meta_key = '".$wpdb->prefix."capabilities' AND u.ID=s.user_id AND s.status in ('ok') UNION SELECT COUNT(u.ID) FROM $wpdb->users as u LEFT JOIN ".esc_sql(SABRE_TABLE)." as s ON u.ID=s.user_id WHERE s.user_id IS NULL";

		/*
		query
		*/

		$query='SELECT COUNT('.$sql_stat;

		$this->log($query, 1);

		/*
		we use get_col because of
		stat users can also return two results
		*/

		$results=$wpdb->get_col($query);

		if ($results===false)
			throw new Exception('error while counting stat '.$this->get_stat_name($stat));

		$result=$results[0];

		/*
		Sabre Cooperation on 'deny early login'
		http://wordpress.org/plugins/sabre/
		*/

		if ($stat==0 && defined('SABRE_TABLE'))
			$result+=$results[1];

		return $result;
	}

	/*
	counts the words of posts,
	comments or pages

	decreased memory usage by
	incrementing limit statements
	*/

	private function get_word_count($stat) {

		global $wpdb;

		$statement=$this->get_stat_sql_statement($stat);
		$attribute=$this->get_stat_sql_attribute($stat);
		$count_attribute=$this->get_stat_sql_count_attribute($stat);

		$result=0;

		$rows_at_once=$this->get_option('rows_at_once');

		$count_statement = 'SELECT COUNT('.$count_attribute.') ' .$statement;

		$this->log($count_statement, 1);

		/*
		retrieve row-count for stats
		*/

		$counter = $wpdb->get_var($count_statement);

		if ($counter===false)
			throw new Exception('error while counting stat '.$this->get_stat_name($stat));

		$this->log('found '.$counter.' entries for '.$this->get_stat_name($stat));

		$start_limit = 0;

		/*
		if rows_at_once
		is not or incorrect set
		*/

		if (!$this->is_integer($rows_at_once) || $rows_at_once<1)
			$rows_at_once=$counter;

		$increment_statement = 'SELECT '.$attribute.' '.$statement;

		/*
		loop through the sql-statements
		*/

		while($start_limit<$counter) {
			$query=$increment_statement.' LIMIT '.$start_limit.', '.$rows_at_once;

			$this->log($query, 1);

			$results = $wpdb->get_col($query);

			/*
			count the words for each statement
			*/

			for ($i=0; $i<sizeof($results); $i++) {

				/*
				decode html
				*/

				$text=html_entity_decode($results[$i], ENT_QUOTES);

				/*
				remove line-breaks
				*/

				$br_variants=array(
					"\n",
					'<br>',
					'<br />',
					'<br/>'
				);

				$text=str_replace($br_variants, ' ', $text);

				/*
				remove html tags if option
				has been set to false
				*/

				if (!$this->get_option('count_html_tags'))
					$text=wp_strip_all_tags($text);

				$result+=str_word_count($text);
			}

			$this->log('subtotal for '.$this->get_stat_name($stat).'='.$result);

			$start_limit+=$rows_at_once;
		}

		return $result;

	}

	/*
	processes the stats for a cache-query
	returns array with stat_key => count

	= caching mechanism =

	goal: avoid too many lookups in transients
	(because they are not autoloaded?)

	cache-structure:
	- store individual stats
	in an atomic transient
	- combine transients of selected_stats
	into cache-block transient

	constraints for retrieval:
	- get counts of cache-block transient
	- get count of individual stat transients
	which are not within the cache-block
	-> combine stats of query into array with structure
	stat_key => count
	*/

	private function cache_stats_block($params=array()) {

		$cache=array();

		/*
		find out if at least
		on of the stats_selected in params
		in within stats_selected
		=> also in block-cache
		*/

		/*
		this array will hold all stats
		which might be within
		the block-cache
		*/

		$block_stats=$params['stats_selected'];

		/*
		this array will hold all stats
		which are not within
		the block-cache
		*/

		$individual_stats=array();

		foreach ($block_stats as $key => $stat) {

			/*
			if stat does no exist
			within global stats_selected
			and therefore not within
			the block-cache

			unset in block_stats
			and add to individual_stats
			*/

			if (!array_key_exists($key, $this->stats_selected)) {
				$individual_stats[$key]=$stat;
				unset($block_stats[$key]);
			}
		}

		$this->log('within block_stats='.var_export($block_stats, true));

		$this->log('individual_stats='.var_export($individual_stats, true));

		/*
		at least one of the chosen stats
		of this function-call is within
		the cache-block =>
		query and eventually update
		stats block
		*/

		if(!empty($block_stats)) {

			/*
			retrieve cache_block from database
			*/

			$cache_block=get_transient($this->get_prefix(false));

			/*
			update cache-block
			*/

			if (empty($cache_block) || !is_array($cache_block))	{	
				$cache_block=array();

				$this->log('update cache-block cache');

				foreach($this->stats_selected as $key => $stat) {

					$this->log('get count for stat '.$this->get_stat_name($key).' (block-stats)');

					/*
					retrieve count
					*/

					$count=$this->get_count($key, $params['cache_time']);

					$cache_block[$key]=$count;	
				}

				$this->log('update cache-block transient');

				set_transient($this->get_prefix(false), $cache_block, $params['cache_time']);

			}

			else {
				$this->log('used cached cache-block');
			}

			$this->log('cached stats-block='.var_export($cache_block, true));

			/*
			set cache_array to
			cache-block-transient
			*/

			$cache=$cache_block;

		}

		/*
		process individual stats
		*/

		if (!empty($individual_stats)) {

			foreach($individual_stats as $key => $stat) {
					
				$this->log('get count for stat '.$this->get_stat_name($key).' (individual-stats)');

				$count=$this->get_count($key, $params['cache_time']);

				$cache[$key]=$count;

			}

		}

		$this->log('retrieved results='.var_export($cache, true));

		return $cache;
	}

	/*
	generates the formatted output
	*/

	private function format_stats_block($params=array(), $cache=array()) {

		/*
		make sure that we handle an array
		*/

		if (!is_array($cache))
			$cache=array();

		$ret_val='';

		/*
		begin list
		*/

		$ret_val.=$params['before_list'];

		/*
		loop through desired stats
		*/

		foreach($params['stats_selected'] as $key => $name) {

			/*
			use cached count
			*/

			if (array_key_exists($key, $cache)) {
				$this->log('get count for stat '.$this->get_stat_name($key).' from retrieved array');

				$count=$cache[$key];
			}

			/*
			retrieve count
			*/

			else {
				$this->log('get count for stat '.$this->get_stat_name($key).' by count function');

				try {
					$count=$this->get_count($key, $params['cache_time']);
				}
				catch(Exception $e) {
					$this->log($e->getMessage());
					$ret_val.=str_replace(array('%name', '%count'), array('', $e->getMessage()), $params['format_stat']);
					$count=-1;
				}
			}

			/*
			did we receive a result?
			*/

			if ($count>-1) {

				/*
				format count
				*/

				$count=number_format($count, 0 , '', $params['thousands_separator']);

				$name=htmlentities($name, ENT_QUOTES, get_option('blog_charset'), false);

				/*
				build tag for html output
				*/

				$ret_val.= str_replace(array('%name', '%count'), array($name, $count), $params['format_stat']);
			}
		}

		/*
		finish list
		*/

		$ret_val.=$params['after_list'];

		return $ret_val;
	}

	/*
	checks if params lead to refreshable div
	check if selected_stats and defaults
	are same as selected in option page
	*/

	private function is_output_refreshable($params) {

		/*
		if a id has been set
		the output is not automatically refreshable
		*/

		if (isset($params['id']) && strlen($params['id'])>0)
			return false;

		/*
		check for different stat setting
		compare if array_keys are equal
		*/

		if (array_key_exists('stats_selected', $params)) {

			$keys_stats_selected=array_keys($this->stats_selected);

			$keys_params_stats_selected=array_keys($params['stats_selected']);

			/*
			do the arrays differ in size?
			*/

			if (sizeof($keys_stats_selected)!=sizeof($keys_params_stats_selected)) {
				$this->log('found different stat setting - disabling refreshable');
				return false;
			}

			/*
			compare order of array_keys of
			global stats_selected
			and function call stats_selected
			*/

			for($i=0; $i<sizeof($keys_stats_selected); $i++) {
				if ($keys_stats_selected[$i]!=$keys_params_stats_selected[$i]) {
					$this->log('found different stat  setting or stat order - disabling refreshable');
					return false;
				}
			}

			/*
			compare values of array_keys of
			global stats_selected
			and function call stats_selected
			*/

			foreach($this->stats_selected as $key => $stat) {
				if($stat!=$params['stats_selected'][$key]) {
					$this->log('found different stat name - disabling refreshable');
					return false;
				}
			}
		}

		/*
		every set fallback-default
		which is not
		'display' or 'use_container'
		will trigger a new creation of
		the stats block and not
		process the cache
		*/

		$trigger_params=$params;

		unset($trigger_params['display']);
		unset($trigger_params['use_container']);

		foreach($trigger_params as $key => $value) {
			if (array_key_exists($key, $this->defaults) && $value!=$this->get_default($key)) {
				$this->log('found trigger option '.$key. ' - disabling refreshable');
				return false;
			}
		}

		return true;
	}

	/*
	output current stats-block
	*/

	private function current_stats_block($filter) {
		$filtered_params=apply_filters($this->get_prefix().$filter, array());

		$params=array(
			'use_container' => true,
			'display' => true
		);

		$this->output(array_merge($filtered_params, $params));
	}

	/*
	CACHE REFRESH
	*/

	/*
	check comment-status before force cache refresh
	*/

	function comment_status_force_cache_refresh($id=-1, $status=false) {
		if ($status==1 || $status===false)
			$this->force_comment_cache_refresh();
	}

	/*
	check post-status and
	visibility before force cache refresh
	*/

	function save_post_force_cache_refresh($id=-1, $post=false) {

		if (is_object($post)) {

			/*
			check if post is autosave
			*/

			if (wp_is_post_autosave($post)>0)
				return;

			/*
			do we handle a post or a page?
			*/

			if ($post->post_type=='post') {
				$this->force_post_cache_refresh();
				return;
			}
			else if ($post->post_type=='page') {
				$this->force_page_cache_refresh();
				return;
			}
		}

		/*
		we didn't receive an object
		and could check for post details
		=> refresh post and page caches
		*/

		$this->force_post_cache_refresh();
		$this->force_page_cache_refresh();
	}

	/*
	force user cache refresh
	*/

	function force_user_cache_refresh() {
		$stats=array(0);
		$this->force_cache_refresh($stats);
	}

	/*
	force post cache refresh
	*/

	function force_post_cache_refresh() {
		$stats=array(2, 10);
		$this->force_cache_refresh($stats);
	}

	/*
	force page cache refresh
	*/

	function force_page_cache_refresh() {
		$stats=array(4, 12);
		$this->force_cache_refresh($stats);
	}

	/*
	force comment cache refresh
	*/

	function force_comment_cache_refresh() {
		$stats=array(3, 11);
		$this->force_cache_refresh($stats);
	}

	/*
	force link cache refresh
	*/

	function force_link_cache_refresh() {
		$stats=array(5);
		$this->force_cache_refresh($stats);
	}

	/*
	force term cache refresh
	*/

	function force_term_cache_refresh($term=null, $tt_id=-1, $taxonomy='') {

		if ($taxonomy=='category')
			$this->force_category_cache_refresh();
		else if ($taxonomy=='post_tag')
			$this->force_tag_cache_refresh();
		else if ($taxonomy=='link_category')
			$this->force_link_category_cache_refresh();
		else {
			$this->force_category_cache_refresh();
			$this->force_link_category_cache_refresh();
			$this->force_tag_cache_refresh();
		}
	}

	/*
	force category cache refresh
	*/

	function force_category_cache_refresh() {
		$stats=array(1);
		$this->force_cache_refresh($stats);
	}

	/*
	force tag cache refresh
	*/

	function force_tag_cache_refresh() {
		$stats=array(6);
		$this->force_cache_refresh($stats);
	}

	/*
	force link-category cache refresh
	*/

	function force_link_category_cache_refresh() {
		$stats=array(7);
		$this->force_cache_refresh($stats);
	}

	/*
	force cache refresh
	*/

	private function force_cache_refresh($stats) {

		/*
		loop through all given fields
		*/

		foreach($stats as $stat) {

			/*
			check existence of stat in global
			stat array to assure that
			we don't set any false positives
			*/

			if (array_key_exists($stat, $this->stats)) {

				/*
				invalidate individual transient
				*/

				set_transient($this->get_prefix().'stat_'.$stat, '', -1);

				/*
				invalidate block transient
				*/

				if (array_key_exists($stat, $this->stats_selected))
					set_transient($this->get_prefix(false), '', -1);
			}
		}
	}

	/*
	ADMIN MENU - UTILITY
	*/

	/*
	register settings sections and fields
	*/

	private function add_settings_sections($settings_sections, $section_prefix) {

		/*
		settings-sections
		*/

		foreach($settings_sections as $section_key => $section) {
			$this->add_settings_section($section_key, $section['nicename'], $section_prefix, $section['callback']);

			/*
			fields for each section
			*/

			if (array_key_exists('fields', $section)) {
				foreach ($section['fields'] as $field_key => $field) {
					$label_for='';

					if ($section_key=='expert') {
						$label_for=$this->get_prefix()."stat_pos_".$field_key;
					}

					$this->add_settings_field($field_key, $field, $section_key, $section_prefix, $label_for);
				}
			}
		}
	}

	/*
	adds a settings section
	*/

	private function add_settings_section($section_key, $section_name, $section_prefix, $callback) {
		add_settings_section('default', $section_name, array($this, 'callback_'.$section_prefix.'_'.$callback), $this->get_prefix().$section_prefix.'_'.$section_key);
	}

	/*
	adds a settings field
	*/

	private function add_settings_field($field_key, $field_name, $section_key, $section_prefix, $label_for='') {
		if (empty($label_for))
			$label_for=$this->get_prefix().$field_key;

		add_settings_field($this->get_prefix().$field_key, $field_name, array($this, 'setting_'.$field_key), $this->get_prefix().$section_prefix.'_'.$section_key, 'default', array('label_for' => $label_for));
	}

	/*
	creates section link
	*/

	private function get_section_link($sections, $section, $section_nicename='', $create_id=false) {
		if (strlen($section_nicename)<1)
			$section_nicename=$sections[$section]['nicename'];

		$id='';
		$class='';
		$section_span='';

		if ($create_id)
			$id=' id="'.$this->get_prefix().$section.'_link"';
		else {
			$class=' class="'.$this->get_prefix().'section_link"';
			$section_span='<span class="'.$this->get_prefix().'section_text">'.$section_nicename.'</span>';
		}

		$menuitem_onclick=" onclick=\"".$this->get_prefix()."open_section('".$section."');\"";

		$section_link='<a'.$id.$class.$menuitem_onclick.' href="javascript:void(0);">'.$section_nicename.'</a>';

		return $section_span.$section_link;
	}

	/*
	returns name="generalstats[setting]" id="generalstats_setting"
	*/

	private function get_setting_name_and_id($setting) {
		return 'name="'.$this->get_prefix(false).'['.$setting.']" id="'.$this->get_prefix().$setting.'"';
	}

	/*
	returns default value for option-field
	*/

	private function get_setting_default_value($field, $type) {
		$default_value=null;

		if ($type=='options')
			$default_value=htmlentities($this->get_option($field), ENT_QUOTES, get_option('blog_charset'), false);
		else if ($type=='defaults')
			$default_value=htmlentities($this->get_default($field), ENT_QUOTES, get_option('blog_charset'), false);
		else if ($type=='stat_pos')
			$default_value=$this->get_stat_position($field);
		else if ($type=='stat_name')
			$default_value=htmlentities($this->get_custom_stat_name($field), ENT_QUOTES, get_option('blog_charset'), false);
		else
			throw new Exception('type '.$type.' does not exist for field '.$field.'!');

		return $default_value;
	}

	/*
	outputs a settings section
	*/

	private function do_settings_sections($section_key, $section_prefix) {
		do_settings_sections($this->get_prefix().$section_prefix.'_'.$section_key);
	}

	/*
	handles adding a help-tab
	*/

	private function add_help_tab($help_text) {
		$current_screen=get_current_screen();

		$help_options=array(
			'id' => $this->get_prefix(),
			'title' => $this->get_nicename(),
			'content' => $help_text
		);

		$current_screen->add_help_tab($help_options);
	}

	/*
	Settings Page
	*/

	private function settings_page($settings_sections, $permissions, $section_prefix, $is_wp_options) {

		/*
		security check
		*/

		if (!current_user_can($permissions))
			wp_die(__('You do not have sufficient permissions to display this page.'));

		/*
		option-page html
		*/

		?><div class="wrap">
		<h2><?php echo($this->get_nicename()); ?></h2>

		<?php call_user_func(array($this, 'callback_'.$section_prefix.'_intro')); ?>

		<div id="<?php echo($this->get_prefix()); ?>menu" style="display:none"><ul class="subsubsub <?php echo($this->get_prefix(false)); ?>">
		<?php

		$menu='';

		foreach ($settings_sections as $key => $section)
			$menu.='<li>'.$this->get_section_link($settings_sections, $key, '', true).' |</li>';

		$menu=substr($menu, 0, strlen($menu)-7).'</li>';

		echo($menu);
		?>
		</ul></div>

		<div id="<?php echo($this->get_prefix()); ?>content" class="<?php echo($this->get_prefix()); ?>wrap">

		<script type="text/javascript">

		/* <![CDATA[ */

		jQuery('#<?php echo($this->get_prefix()); ?>content').css('display', 'none');

		/* ]]> */

		</script>

		<?php if ($is_wp_options) { ?>
			<form id="<?php echo($this->get_prefix().'form_settings'); ?>" method="post" action="<?php echo(admin_url('options.php')); ?>">
			<?php settings_fields($this->get_prefix(false));
		}

		foreach ($settings_sections as $key => $section) {

		?><div id="<?php echo($this->get_prefix().$key); ?>"><?php

			$this->do_settings_sections($key, $section_prefix);
			echo('</div>');
		}

		?>

		<?php if ($is_wp_options) { ?>
			<p class="submit">
			<?php
			$submit_buttons=array(
				'submit' => 'Save Changes',
				'reset' => 'Default'
			);

			foreach ($submit_buttons as $key => $submit_button)
 				$this->setting_submit_button($key, $submit_button);
			?>
			</p>
			</form>
		<?php } ?>

		<?php $this->support(); ?>

		</div>

		</div>

		<?php /*
		JAVASCRIPT
		*/ ?>

		<?php $this->settings_page_js($settings_sections, $is_wp_options); ?>

	<?php }

	/*
	settings pages's javascript
	*/

	private function settings_page_js($settings_sections, $is_wp_options) { ?>

	<script type="text/javascript">

	/* <![CDATA[ */

	/*
	section-divs
	*/

	var <?php echo($this->get_prefix()); ?>sections=[<?php

	$available_sections=array();

	foreach($settings_sections as $key => $section)
		array_push($available_sections, '"'.$key.'"');

	echo(implode(',', $available_sections));
	?>];

	<?php if ($is_wp_options) { ?>

	/*
	media-query needs to be realized
	with javascript
	because of sub-menu selection
	*/

	jQuery(document).ready(function() {
		<?php echo($this->get_prefix()); ?>resize_settings_page();
	});

	jQuery(window).on('resize orientationchange', function() {
		<?php echo($this->get_prefix()); ?>resize_settings_page();
	});

	/*
	submit only without errors
	*/

	jQuery('#<?php echo($this->get_prefix().'form_settings'); ?>').submit(function (e) {
		if (jQuery('#<?php echo($this->get_prefix().'form_settings'); ?>').find('.error').length>0) {
			if (e.preventDefault)
				e.preventDefault();
			else
				e.returnValue=false;
		}
	});

	/*
	disable buttons on error
	*/

	jQuery('#<?php echo($this->get_prefix().'form_settings'); ?> input:text').keyup(function (e) {
		var submit_elements=jQuery('#<?php echo($this->get_prefix().'form_settings'); ?> :submit');

		if (jQuery('#<?php echo($this->get_prefix().'form_settings'); ?>').find('.error').length>0)
			submit_elements.prop('disabled', true);
		else
			submit_elements.prop('disabled', false);
	});

	<?php } ?>

	/*
	display content-block
	*/

	jQuery(document).ready(function() {
		jQuery('#<?php echo($this->get_prefix()); ?>content').css('display', 'block');
	});

	/* ]]> */

	</script>

	<?php }

	/*
	ADMIN MENU - COMPONENTS
	*/

	/*
	generic checkbox
	*/

	private function setting_checkfield($name, $type, $related_fields=array(), $js_checked=true) {

		$javascript_onclick_related_fields='';

		/*
		build javascript function
		to enable/disable related fields
		*/

		if (!empty($related_fields)) {

			/*
			prepare for javascript array
			*/

			foreach($related_fields as &$related_field)
				$related_field='\''.$related_field.'\'';

			/*
			build onclick-js-call
			*/

			$javascript_toggle=$this->get_prefix().'toggle_related_fields(';

			$javascript_fields=', ['.implode(', ', $related_fields).']';

			/*
			check for disabled fields
			on document ready
			*/

			?>

			<script type="text/javascript">

			/* <![CDATA[ */

			jQuery(document).ready(function() { <?php echo($javascript_toggle.'jQuery(\'#'.$this->get_prefix().$name.'\')'.$javascript_fields. ', '.($js_checked == 1 ? '1' : '0').');'); ?> });

			/* ]]> */

			</script>

			<?php

			/*
			build trigger for settings_field
			*/

			$javascript_onclick_related_fields='onclick="'.$javascript_toggle.'jQuery(this)'.$javascript_fields. ', '.($js_checked == 1 ? '1' : '0').');"';
		}

		$checked=$this->get_setting_default_value($name, $type); ?>
		<input <?php echo($this->get_setting_name_and_id($name)); ?> type="checkbox" <?php echo($javascript_onclick_related_fields); ?> value="1" <?php checked('1', $checked); ?> />
	<?php }

	/*
	generic textinput
	*/

	private function setting_textfield($name, $type, $size=30, $javascript_validate='') {
		$default_value=$this->get_setting_default_value($name, $type);
		$size_attribute=($size>40) ? 'class="widefat"' : 'size="'.$size.'"';
		?>

		<input type="text" <?php echo($this->get_setting_name_and_id($name).' '.$javascript_validate); ?> maxlength="<?php echo($size); ?>" <?php echo($size_attribute); ?> value="<?php echo $default_value; ?>" />
	<?php }

	/*
	generic submit-button
	*/

	private function setting_submit_button($field_key, $button) { ?>
		<input type="submit" name="<?php echo($this->get_prefix(false)); ?>[<?php echo($field_key); ?>]" id="<?php echo($this->get_prefix(false)); ?>_<?php echo($field_key); ?>" class="button-primary" value="<?php _e($button) ?>" />
	<?php }

	/*
	generic capability select
	*/

	private function setting_capability($name, $type) {
		?><select <?php echo($this->get_setting_name_and_id($name.'_capability')); ?>>

			<?php
			$capabilities=$this->get_all_capabilities();

			$ret_val='';

			foreach ($capabilities as $capability) {
				$_selected = $capability == $this->get_setting_default_value($name.'_capability', $type) ? " selected='selected'" : '';
				$ret_val.="\t<option value='".$capability."'".$_selected.">" . $capability . "</option>\n";
			}

			echo $ret_val;
			?>

		</select><?php
	}

	/*
	generate cron-schedules select
	*/

	private function setting_schedule($name, $type) {
		?><select <?php echo($this->get_setting_name_and_id($name.'_schedule')); ?>>

			<?php
			$schedules=wp_get_schedules();
			$schedules['no'] = array(
				'interval' => '0',
				'display' => 'not scheduled'
			);

			$ret_val='';

			foreach ($schedules as $key => $schedule) {
				$_selected = $key == $this->get_setting_default_value($name.'_schedule', $type) ? " selected='selected'" : '';
				$ret_val.="\t<option value='".$key."'".$_selected.">" . $schedule['display'] . "</option>\n";
			}

			echo $ret_val;
			?>

		</select><?php
	}

	/*
	add stat field
	position with label
	description with label
	*/

	private function setting_stat_field($key) {
		$stat_name=$this->get_stat_name($key);

		?><label for="<?php echo($this->get_prefix()."stat_pos_".$key); ?>">Position</label>

		<?php $this->setting_textfield('stat_pos_'.$key, 'stat_pos', 2); ?>

		<label for="<?php echo($this->get_prefix().'stat_desc_'.$key); ?>">Description</label>

		<?php $this->setting_textfield('stat_desc_'.$key, 'stat_name');
	}

	/*
	outputs support paragraph
	*/

	private function support() {
		global $user_identity; ?>
		<h3>Support</h3>
		<?php echo($user_identity); ?>, if you would like to support the development of <?php echo($this->get_nicename()); ?>, you can invite me for a <a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=TGPC4W9DUSWUS">virtual pizza</a> for my work. <?php echo(convert_smilies(':)')); ?><br /><br />

		<a class="<?php echo($this->get_prefix()); ?>button_donate" title="Donate to <?php echo($this->get_nicename()); ?>" target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=TGPC4W9DUSWUS">Donate</a><br /><br />

		Maybe you also want to <?php if (current_user_can('manage_links') && ((!has_filter('default_option_link_manager_enabled') || get_option( 'link_manager_enabled')))) { ?><a href="link-add.php"><?php } ?>add a link<?php if (current_user_can('manage_links') && ((!has_filter('default_option_link_manager_enabled') || get_option( 'link_manager_enabled')))) { ?></a><?php } ?> to <a target="_blank" href="http://www.bernhard-riedl.com/projects/">http://www.bernhard-riedl.com/projects/</a>.<br /><br />
	<?php }

	/*
	ADMIN MENU - SECTIONS + HELP
	*/

	/*
	intro callback
	*/

	function callback_settings_intro() { ?>
		Welcome to the Settings-Page of <a target="_blank" href="http://www.bernhard-riedl.com/projects/"><?php echo($this->get_nicename()); ?></a>. This plugin counts the number of users, categories, posts, comments, pages, links, tags, link-categories, words in posts, words in comments and words in pages.
	<?php
		/*
		https://core.trac.wordpress.org/ticket/21307
		*/

		if ((has_filter('default_option_link_manager_enabled') && !get_option( 'link_manager_enabled'))) {
			echo('<br />Please note, that the link-manager is optional since WordPress 3.5. - If you want to use links or link-categories stats you have to <a href="'.wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=link-manager'), 'install-plugin_link-manager').'">install the Link Manager Plugin</a>.');
		}
	}

	/*
	adds help-text to admin-menu contextual help
	*/

	function options_page_help() {
		return "<div class=\"".$this->get_prefix()."wrap\"><ul>

			<li>You can insert new or edit your existing stat-entries in the ".$this->get_section_link($this->options_page_sections, 'drag_and_drop', 'Drag and Drop Layout Section')." or in the ".$this->get_section_link($this->options_page_sections, 'expert', 'Expert Settings').". Latter section also works without the usage of JavaScript. In any way, new entries are only saved after clicking on <strong>Save Changes</strong>.</li>

			<li>Style-customizations can be made in the ".$this->get_section_link($this->options_page_sections, 'format', 'Format Section').".</li>

			<li>You can activate an optional ".$this->get_section_link($this->options_page_sections, 'ajax_refresh', 'Ajax refresh for automatical updates')." of your stats-output.</li>

			<li>Don't forget to adopt the ".$this->get_section_link($this->options_page_sections, 'performance', 'caching and performance options')." to your server environment.</li>

			<li>Before you publish the results you can use the ".$this->get_section_link($this->options_page_sections, 'preview', 'Preview Section').".</li>

			<li>Finally, you can publish the previously selected and saved stats either by adding a <a href=\"widgets.php\">Sidebar Widget</a> or by enabling the ".$this->get_section_link($this->options_page_sections, 'dashboard', 'Dashboard Widget').".</li>

			<li><a target=\"_blank\" href=\"http://wordpress.org/plugins/generalstats/other_notes/\">Geek stuff</a>: You can output your stat-selection by calling the <abbr title=\"PHP: Hypertext Preprocessor\">PHP</abbr> function <code>$".$this->get_prefix(false)."->output(\$params)</code> or <code>$".$this->get_prefix(false)."->count(\$params)</code> wherever you like (don't forget <code>global $".$this->get_prefix(false)."</code>). These functions can also be invoked by the usage of shortcodes.</li>

			<li>If you decide to uninstall ".$this->get_nicename().", firstly remove the optionally added <a href=\"widgets.php\">Sidebar Widget</a>, integrated <abbr title=\"PHP: Hypertext Preprocessor\">PHP</abbr> function or WordPress shortcode call(s). Afterwards, disable and delete ".$this->get_nicename()." in the <a href=\"plugins.php\">Plugins Tab</a>.</li>

			<li><strong>For more information:</strong><br /><a target=\"_blank\" href=\"http://wordpress.org/plugins/".str_replace('_', '-', $this->get_prefix(false))."/\">".$this->get_nicename()." in the WordPress Plugin Directory</a></li>

		</ul></div>";
	}

	/*
	section drag_and_drop
	*/

	function callback_settings_drag_and_drop() { ?>

		<ul>

			<li>You can add available or remove selected stats (like posts, users, etc.) via drag and drop between the lists.</li>

			<li>To customize the descriptions of a stat click on it and edit the output name in the form, which appears either on the right or below the lists. After clicking <strong>Change</strong> the selected stat's name will be adopted.</li>

			<li>To re-order the stats within a list either use drag and drop or click on the arrows on the left side of the particular stat.</li>

			<li>Don't forget to save all your adjustments by clicking on <strong>Save Changes</strong>.</li>

		</ul><?php

		$list_selected='';
		$list_available='';
		$before_tag='<li class="'.$this->get_prefix().'sortablelist" id=';
		$after_tag='</li>';
		$before_key=$this->get_prefix().'stat_';

		/*
		build lists
		*/

		/*
		build list of selected stats
		*/

		foreach ($this->stats_selected as $key => $stat) {
			$stat_name=$this->get_stat_name($key);

			$tag=$stat. ' ('. $stat_name .')';

			/*
			arrows
			*/

			$up_arrow='<img class="'.$this->get_prefix().'arrowbutton" src="'.$this->get_plugin_url().'arrow_up_blue.png" onclick="'.$this->get_prefix().'move_element_up('.$key.');" alt="move element up" />';

			$down_arrow='<img class="'.$this->get_prefix().'arrowbutton" style="margin-right:15px" src="'.$this->get_plugin_url().'arrow_down_blue.png" onclick="'.$this->get_prefix().'move_element_down('.$key.');" alt="move element down" />';

			/*
			add stat to list-selected
			*/

			$list_selected.= $before_tag. '"'.$before_key.$key.'">'.$up_arrow.$down_arrow.'<span>'.htmlentities($tag, ENT_QUOTES, get_option('blog_charset'), false).'</span>'.$after_tag."\n";
		}

		/*
		build list of available stats
		*/

		foreach($this->stats_available as $key => $stat) {
			$tag=$stat. ' ('. $stat. ')';

			/*
			arrows
			*/

			$up_arrow='<img class="'.$this->get_prefix().'arrowbutton" src="'.$this->get_plugin_url().'arrow_up_blue.png" onclick="'.$this->get_prefix().'move_element_up('.$key.');" alt="move element up" />';

			$down_arrow='<img class="'.$this->get_prefix().'arrowbutton" style="margin-right:15px" src="'.$this->get_plugin_url().'arrow_down_blue.png" onclick="'.$this->get_prefix().'move_element_down('.$key.');" alt="move element down" />';

			/*
			add stat to list-available
			*/

			$list_available.= $before_tag. '"'.$before_key.$key.'">'.$up_arrow.$down_arrow.'<span>'.htmlentities($tag, ENT_QUOTES, get_option('blog_charset'), false).'</span>'.$after_tag."\n";
		}

		/*
		add listeners for drag-and-drop edit panel
		*/

		$list_selected_listeners='';
		$list_available_listeners='';

		foreach ($this->stats_selected as $key => $stat) {
			$list_selected_listeners.="jQuery('#".$before_key.$key."').click(function(){ ".$this->get_prefix()."populate_drag_and_drop('".$key."') });";
		}

		foreach ($this->stats_available as $key => $stat) {
			$list_available_listeners.="jQuery('#".$before_key.$key."').click(function(){ ".$this->get_prefix()."populate_drag_and_drop('".$key."') });";
		}

		/*
		format list
		*/

		$element_height=32;

		$sizelist_selected=sizeof($this->stats_selected)*$element_height;

		if ($sizelist_selected<=0) $sizelist_selected=$element_height;

		$sizelist_available=sizeof($this->stats_available)*$element_height;
		if ($sizelist_available<=0) $sizelist_available=$element_height;

		$list_selected='<div><h4 style="margin: 0.8em 0;">Selected Stats</h4><ul class="'.$this->get_prefix().'sortablelist" id="'.$this->get_prefix().'list_selected" style="height:'.$sizelist_selected.'px;width:370px;"><li style="display:none"></li>'.$list_selected.'</ul></div>';

		$list_available='<div><h4>Available Stats</h4><ul class="'.$this->get_prefix().'sortablelist" id="'.$this->get_prefix().'list_available" style="height:'.$sizelist_available.'px;width:370px;"><li style="display:none"></li>'.$list_available.'</ul></div>';

		/*
		lists-container
		*/

		echo('<div id="'.$this->get_prefix().'lists">');

		/*
		output selected stats
		*/

		echo($list_selected);

		/*
		output available stats
		*/

		echo($list_available);

		echo('</div>');

		/*
		output edit form
		*/

		?>

		<div id="<?php echo($this->get_prefix()); ?>edit" style="display:none">

			<input type="hidden" id="<?php echo($this->get_prefix()); ?>edit_selected_stat" />

			<div id="<?php echo($this->get_prefix()); ?>edit_header"><label style="margin-left: 2px; font-weight: 600;" for="<?php echo($this->get_prefix()); ?>edit_text"><span style="width: 99%" id="<?php echo($this->get_prefix()); ?>edit_label"></span></label>
			</div>

			<input id="<?php echo($this->get_prefix()); ?>edit_text" type="text" maxlength="30" style="margin: 3px 1px; width: 99%" />

			<div id="<?php echo($this->get_prefix()); ?>edit_submit">
				<input class="button-secondary" type="button" id="<?php echo($this->get_prefix()); ?>edit_change" value="Change" />
				<input class="button-secondary" type="button" id="<?php echo($this->get_prefix()); ?>edit_default" value="Default" />
			</div>

		</div>

		<br style="clear:both" />

		<?php

		/*
		include javascript
		*/

		?>

		<?php $this->callback_settings_drag_and_drop_js($list_selected_listeners, $list_available_listeners);
	}

	private function callback_settings_drag_and_drop_js($list_selected_listeners, $list_available_listeners) { ?>

	<script type="text/javascript">

	/* <![CDATA[ */

	var <?php echo($this->get_prefix()); ?>keys=[<?php echo(implode(',', array_keys($this->stats))); ?>];
	var <?php echo($this->get_prefix()); ?>fields=[<?php

	$all_stats=array();

	foreach($this->stats as $key => $stat) {
		array_push($all_stats, '"'.$this->get_stat_name($key).'"');
	}

	echo(implode(',', $all_stats)); ?>];

	<?php echo($this->get_prefix()); ?>initialize_drag_and_drop();

	/*
	register listeners for buttons
	*/

	jQuery('#<?php echo($this->get_prefix()); ?>edit_change').click(function(){ <?php echo($this->get_prefix()); ?>change_entry(); });

	jQuery('#<?php echo($this->get_prefix()); ?>edit_default').click(function(){ <?php echo($this->get_prefix()); ?>populate_drag_and_drop_default(); });

	/*
	register listeners for lists
	*/

	<?php echo($list_selected_listeners."\n"); ?>
	<?php echo($list_available_listeners."\n"); ?>

	/*
	register listeners for text-inputs
	*/

	jQuery('#<?php echo($this->get_prefix()); ?>edit').keypress(function(e){
		var keycode=(e.keyCode ? e.keyCode : e.which);

		if (keycode==13) {
			if (e.preventDefault)
				e.preventDefault();
			else
				e.returnValue=false;

			<?php echo($this->get_prefix()); ?>change_entry();
		}
	});

	/* ]]> */

	</script>

	<?php }

	/*
	section expert
	*/

	function callback_settings_expert() { ?>
		In this section you can adopt your stats-selection 'by hand'. Changes you make here are only reflected in the <?php echo($this->get_section_link($this->options_page_sections, 'drag_and_drop', 'Drag and Drop Section')); ?> after clicking on <strong>Save Changes</strong>.
	<?php }

	/*
	stat settings
	*/

	function setting_0($params=array()) {
		$this->setting_stat_field(0);
	}

	function setting_1($params=array()) {
		$this->setting_stat_field(1);
	}

	function setting_2($params=array()) {
		$this->setting_stat_field(2);
	}

	function setting_3($params=array()) {
		$this->setting_stat_field(3);
	}

	function setting_4($params=array()) {
		$this->setting_stat_field(4);
	}

	function setting_5($params=array()) {
		$this->setting_stat_field(5);
	}

	function setting_6($params=array()) {
		$this->setting_stat_field(6);
	}

	function setting_7($params=array()) {
		$this->setting_stat_field(7);
	}

	function setting_10($params=array()) {
		$this->setting_stat_field(10);
	}

	function setting_11($params=array()) {
		$this->setting_stat_field(11);
	}

	function setting_12($params=array()) {
		$this->setting_stat_field(12);
	}

	/*
	section format
	*/

	function callback_settings_format() { ?>
		In this section you can customize the layout of <?php echo($this->get_section_link($this->options_page_sections, 'preview', $this->get_nicename().'\'s output')); ?> after saving your changes by clicking on <strong>Save Changes</strong>. Tutorials, references and examples about <abbr title="HyperText Markup Language">HTML</abbr> and <abbr title="Cascading Style Sheets">CSS</abbr> can be found on <a target="_blank" href="http://www.w3schools.com/">W3Schools</a>.

		<ul>
			<li>The stat-list will be wrapped within <em>before List</em> and <em>after List</em>. Each stat-entry is based on <em>Format of Stat-Entry</em>. The following fields will be replaced by the attributes of each selected stat:<ul>
				<li><em>%name</em></li>
				<li><em>%count</em></li></ul>
			</li>

			<li>You can also customize the format of the <em>Thousands Separator</em>.</li>

			<li>In case you do not need a container, you can disable the option <em>Wrap output in div-container</em>.</li>

			<li>The last option, <em>Display Results</em> only refers to direct function calls with <code>$<?php echo($this->get_prefix(false)); ?>->output($params)</code> or <code>$<?php echo($this->get_prefix(false)); ?>->count($params)</code>.</li>

			<li>Moreover you can add <abbr title="Cascading Style Sheets">CSS</abbr> style attributes for the following <code>div</code> and <code>span</code> elements in your <a href="themes.php">Theme</a>, e.g. with the WordPress <a href="theme-editor.php">Theme-Editor</a>.</li>

		</ul><br />

		<table class="widefat">
			<thead><tr><th>Container</th><th>Type</th><th>Function/Shortcode calls</th><th>used if</th></tr></thead>
			<tbody><tr><td><code><?php echo($this->get_prefix(false)); ?>-refreshable-output</code></td><td>div</td><td><code>$<?php echo($this->get_prefix(false)); ?>->output($params)</code></td><td>same selected stats and format as set in Admin Menu</td></tr>
			<tr><td><code><?php echo($this->get_prefix(false)); ?>-output</code></td><td>div</td><td><code>$<?php echo($this->get_prefix(false)); ?>->output($params)</code></td><td>different stats and format as set in Admin Menu</td></tr>
			<tr><td><code><?php echo($this->get_prefix(false)); ?>-count</code></td><td>span</td><td><code>$<?php echo($this->get_prefix(false)); ?>->count($params)</code></td><td>no constraint</td></tr></tbody>
		</table><br />
	<?php }

	function setting_before_list($params=array()) {
		$this->setting_textfield('before_list', 'defaults');
	}

	function setting_after_list($params=array()) {
		$this->setting_textfield('after_list', 'defaults');
	}

	function setting_format_stat($params=array()) {
		$this->setting_textfield('format_stat', 'defaults', 500);
	}

	function setting_thousands_separator($params=array()) {
		$this->setting_textfield('thousands_separator', 'defaults', 1);
	}

	function setting_use_container($params=array()) {
		$this->setting_checkfield('use_container', 'defaults');
	}

	function setting_display($params=array()) {
		$this->setting_checkfield('display', 'defaults');
	}

	/*
	section ajax refresh
	*/

	function callback_settings_ajax_refresh() { ?>
		In this section you can enable and customize the <abbr title="asynchronous JavaScript and XML">Ajax</abbr>-Refresh of <?php echo($this->get_nicename()); ?>.

		<ul>
			<li>After activating <em>Use Ajax Refresh</em> you can specify the seconds for the update interval (<em>Ajax Refresh Time</em>).</li>

			<li>Especially for re-occuring stat updates you should have a look on the <?php echo($this->get_section_link($this->options_page_sections, 'performance', 'performance parameters')); ?>.</li>

			<li>As all stats are retrieved from the server on every refresh, a <em>Ajax Refresh Time</em> of one second is mostly not realizable for the average server out there. Moreover, please remember that every update causes bandwith usage for your readers and your server.</li>

			<li>Due to security reasons, the time for <abbr title="asynchronous JavaScript and XML">Ajax</abbr> updates will be limited by default. In your installation, the nonce-life-time is defined as <?php $nonce_life=apply_filters('nonce_life', 86400); echo(number_format((float) ($nonce_life/3600), 2).' hours ('.$nonce_life.' seconds)'); ?>. If you activate <em>Renew nonce to assure continous updates</em> you override this security feature (only for <?php echo($this->get_nicename()); ?>) but provide unlimited time for <abbr title="asynchronous JavaScript and XML">Ajax</abbr> updates of your stats.</li>
		</ul>
	<?php }

	/*
	generate ajax_refresh checkbox
	*/

	function setting_use_ajax_refresh($params=array()) {
		$this->setting_checkfield('use_ajax_refresh', 'options', array('ajax_refresh_time', 'renew_nonce', 'ajax_refresh_lib'));
	}

	function setting_ajax_refresh_time($params=array()) {
		$this->setting_textfield('ajax_refresh_time', 'options', 4, 'onkeyup="'.$this->get_prefix().'check_integer(jQuery(this), 1, 3600);"');
	}
	function setting_renew_nonce($params=array()) {
		$this->setting_checkfield('renew_nonce', 'options');
	}

	/*
	section performance
	*/

	function callback_settings_performance() { ?>
		With the following options you can influence the performance of <?php echo($this->get_nicename()); ?>.

		<ul>

			<li>A <em>Cache Time</em> of 0 means that the cache will be refreshed on every function-call.</li>

			<li>If you activate <em>Use Action Hooks</em>, the cache-cycle will be interrupted for events like editing a post or publishing a new comment. Thus, your stats should be updated automatically even if you have defined a longer <em>Cache Time</em>.</li>

			<li><em>Rows at Once</em> is an expert setting of <?php echo($this->get_nicename()); ?>. This option effects the <code>Words in *</code> stats: higher value = increased memory usage, but better performance. Please consult the <a target="_blank" href="http://wordpress.org/plugins/<?php echo($this->get_prefix(false)); ?>/faq/">FAQ</a> for further information.</li>

		</ul>
	<?php }

	function setting_cache_time($params=array()) {
		$this->setting_textfield('cache_time', 'options', 5, 'onkeyup="'.$this->get_prefix().'check_integer(jQuery(this), 0, 86400);"');
	}

	function setting_use_action_hooks($params=array()) {
		$this->setting_checkfield('use_action_hooks', 'options');
	}

	function setting_rows_at_once($params=array()) {
		$this->setting_textfield('rows_at_once', 'options', 5, 'onkeyup="'.$this->get_prefix().'check_integer(jQuery(this), 1, 10000);"');
	}

	/*
	section dashboard
	*/

	function callback_settings_dashboard() { ?>
		If you enable one of the next options, <?php echo($this->get_nicename()); ?> will show your stats either as a <a href="index.php">Dashboard Widget</a> or in the Right-Now-Box on the <a href="index.php">Dashboard</a>. You can also choose the necessary <a target="_blank" href="http://codex.wordpress.org/Roles_and_Capabilities">capability</a>.

	<?php }

	function setting_dashboard_widget($params=array()) {
		$this->setting_checkfield('dashboard_widget', 'options', array('dashboard_widget_capability'));
	}

	function setting_dashboard_widget_capability($params=array()) {
		$this->setting_capability('dashboard_widget', 'options');
	}

	function setting_dashboard_right_now($params=array()) {
		$this->setting_checkfield('dashboard_right_now', 'options', array('dashboard_right_now_capability'));
	}

	function setting_dashboard_right_now_capability($params=array()) {
		$this->setting_capability('dashboard_right_now', 'options');
	}

	/*
	section administrative options
	(also holds hidden section id)
	*/

	function callback_settings_administrative_options() { ?>
		<ul>
			<li>You can opt-in to get your stats by email to <?php echo(get_option('admin_email')); ?> by choosing the interval in <em>Schedule of Mail with Stats updates</em>. Your email-address can be changed <a href="options-general.php">here</a>.<?php
			if (wp_get_schedule($this->get_prefix().'mail_stats')) {
				$date_format = get_option('date_format');
				$time_format = get_option('time_format');

				$cron_timestamp = wp_next_scheduled($this->get_prefix().'mail_stats')+(get_option('gmt_offset')*3600);

				echo(' Next mail will be sent on: '.gmdate($date_format.' '.$time_format, $cron_timestamp));
			} ?></li>

			<li>If you select to <em>Include HTML-Tags in Word-Counts</em>, not only 'real text' but also HTML and JavaScript tags will be counted.</li>

			<li>If you want to keep the stats as a secret, you can deactivate <em>All users can view stats</em>. In that case, only users with the <em><a target="_blank" href="http://codex.wordpress.org/Roles_and_Capabilities">Capability</a> to view stats</em> can access this information.</li>

			<li>The <em>Debug Mode</em> can be used to have a look on the actions undertaken by <?php echo($this->get_nicename()); ?> and to investigate unexpected behaviour.</li>
		</ul>

		<input type="hidden" <?php echo($this->get_setting_name_and_id('section')); ?> value="<?php echo($this->get_option('section')); ?>" />
	<?php }

	function setting_mail_stats_schedule($params=array()) {
		$this->setting_schedule('mail_stats', 'options');
	}

	function setting_count_html_tags($params=array()) {
		$this->setting_checkfield('count_html_tags', 'options');
	}

	function setting_all_users_can_view_stats($params=array()) {
		$this->setting_checkfield('all_users_can_view_stats', 'options', array('view_stats_capability'), false);
	}

	function setting_view_stats_capability($params=array()) {
		$this->setting_capability('view_stats', 'options');
	}

	function setting_debug_mode($params=array()) {
		$this->setting_checkfield('debug_mode', 'options');
	}

	/*
	section preview
	*/

	function callback_settings_preview() { ?>
		You can publish this output either by adding a <a href="widgets.php">Sidebar Widget</a>, <?php echo($this->get_section_link($this->options_page_sections, 'dashboard', 'Dashboard Widget')); ?> or by calling the <abbr title="PHP: Hypertext Preprocessor">PHP</abbr> function <code>$<?php echo($this->get_prefix(false)); ?>->output($params)</code> as well as <code>$<?php echo($this->get_prefix(false)); ?>->count($params)</code> wherever you like.<br /><br />

		<?php
		$params=array(
			'use_container' => true,
			'display' => true
		);

		echo($this->output($params));
	}

	/*
	API FUNCTIONS
	*/

	/*
	this function outputs/returns a stats-block

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
		- 10 => 'Words in Posts'
		- 11 => 'Words in Comments'
		- 12 => 'Words in Pages'

	- `before_list`: default `<ul>`

	- `after_list`: default `</ul>`

	- `format_stat`: default `<li><strong>%name</strong> %count</li>`, %name and %count will be replaced by the attributes of the stat-entry

	- `thousands_separator`: divides numbers by thousand delimiters default `,` => e.g. 1,386,267

	- `use_container`: if set to `true` (default value) and the same selected stats and format is used as set in the admin menu, GeneralStats wraps the output in a html div with the class `generalstats-refreshable-output` - the class `generalstats-output` will be used for all other output; if you set `use_container` to `false`, no container div will be generated

	- `display`: if you want to return the stats-information (e.g. for storing in a variable) instead of echoing it with this function-call, set this to `false`; default setting is `true`

	- `format_container`: This option can be used to format the `div` container with css. Please note, that it should only be used to provide individual formats in case the class-style itself cannot be changed.

	- `no_refresh`: If set to true, GeneralStats will not produce any Ajax-Refresh-code, even if you have enabled the Ajax refresh in the admin menu.
	*/

	function output($params=array()) {
		try {
			return $this->_output($params);
		}
		catch(Exception $e) {
			$this->log($e->getMessage(), -1);
			return false;
		}
	}

	/*
	this function outputs/returns a single stat

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
		- 10 => 'Words in Posts'
		- 11 => 'Words in Comments'
		- 12 => 'Words in Pages'

	- `thousands_separator`: divides counts by thousand delimiters; default `,` => e.g. 1,386,267

	- `display`: if you want to return the stats-information (e.g. for storing in a variable) instead of echoing it with this function-call, set this to `false`; default setting is `true`

	- `format_container`: This option can be used to format the `span` container with css. Please note, that it should only be used to provide individual formats in case the class-style itself cannot be changed.

	- `no_refresh`: If set to true, GeneralStats will not produce any Ajax-Refresh-code, even if you have enabled the Ajax refresh in the admin menu.
	*/

	function count($params=array()) {
		try {
			return $this->_count($params);
		}
		catch(Exception $e) {
			$this->log($e->getMessage(), -1);
			return false;
		}
	}

	/*
	SHORTCODES
	*/

	/*
	shortcode for function output
	*/

	function shortcode_output($params, $content=null) {
		$params['display']=false;

		return $this->output($params);
	}

	/*
	shortcode for function count
	*/

	function shortcode_count($params, $content=null) {
		$params['display']=false;

		return $this->count($params);
	}

}

/*
WIDGET CLASS
*/

class WP_Widget_GeneralStats extends WP_Widget {

	/*
	constructor
	*/

	function __construct() {
		global $generalstats;

		$widget_ops = array(
			'classname' => 'widget_'.$generalstats->get_prefix(false),
			'description' => 'Counts the number of users, categories, posts, comments, pages, links, tags, link-categories, words in posts, words in comments and words in pages.'
		);

		parent::__construct($generalstats->get_prefix(false), $generalstats->get_nicename(), $widget_ops);
	}

	/*
	produces the widget-output
	*/

	function widget($args, $instance) {
		global $generalstats;

		extract($args);

		$title = !isset($instance['title']) ? '&nbsp;' : apply_filters('widget_title', $instance['title']);

		$params=array(
			'use_container' => true,
			'display' => false
		);

		$stats=$generalstats->output($params);

		if (empty($stats))
			return;

		echo $before_widget;
		echo $before_title . $title . $after_title;

		echo $stats;

		echo $after_widget;
	}

	/*
	the backend-form
	*/

	function form($instance) {
		global $generalstats;

		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		?>

		<p><label for="<?php echo $this->get_field_id('title'); ?>">
		<?php _e('Title:'); ?>

		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>

		<p><a href='options-general.php?page=<?php echo($generalstats->get_prefix(false)); ?>'><?php _e('Settings'); ?></a></p>

		<?php
	}

	/*
	saves updated widget-options
	*/

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);

		return $instance;
	}

}

/*
UNINSTALL
*/

function generalstats_uninstall() {
 
		/*
		security check
		*/

		if (!current_user_can('manage_options'))
			wp_die(__('You do not have sufficient permissions to manage options for this blog.'));

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
	}

register_uninstall_hook(__FILE__, 'generalstats_uninstall');
