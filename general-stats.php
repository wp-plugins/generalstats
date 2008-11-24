<?php

/*
Plugin Name: GeneralStats
Plugin URI: http://www.neotrinity.at/projects/
Description: Counts the number of users, categories, posts, comments, pages, links, tags, link-categories, words in posts, words in comments and words in pages.
Author: Bernhard Riedl
Version: 0.80
Author URI: http://www.neotrinity.at
*/

/*  Copyright 2006-2008  Bernhard Riedl  (email : neo@neotrinity.at)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


/*
**********************************************
stop editing here unless you know what you do!
**********************************************
/*

/*
called from init hook
*/

function generalstats_init() {
	DEFINE ('GENERALSTATS_PLUGINURL', get_settings('siteurl'). '/'. str_replace( ABSPATH, '', dirname(__FILE__) . '/'));

	add_action('wp_head', 'generalstats_wp_head');
	add_action('admin_menu', 'addGeneralStatsOptionPage');

	add_filter('plugin_action_links', 'generalstats_adminmenu_plugin_actions', -10, 2);
}

function generalstats_adminmenu_plugin_actions($links, $file) {
	if ($file == 'generalstats/general-stats.php')
		$links[] = "<a href='options-general.php?page=generalstats/general-stats.php'>Settings</a>";

	return $links;
}

/*
loads the necessary java-scripts,
which are all included in wordpress >= 2.1
for the admin-page
*/

function generalstats_admin_print_scripts() {
	wp_enqueue_script('scriptaculous-effects');
	wp_enqueue_script('scriptaculous-dragdrop');
}

/*
loads the necessary css-styles
for the admin-page
*/

function generalstats_admin_head() {

?>

<?php
	global $wp_version;

	$current_wp_admin_css_colors=array();

	/*
	check if wordpress_admin_themes are available
	*/

	if (version_compare($wp_version, "2.5", ">=")) {
		global $_wp_admin_css_colors;

		$current_color = get_user_option('admin_color');
		if ( empty($current_color) )
			$current_color = 'fresh';

		$current_wp_admin_css_colors=$_wp_admin_css_colors[$current_color]->colors;

	}

	/*
	if themes are not available, use default colors
	*/

	if (sizeof($current_wp_admin_css_colors)<4) {
		$current_wp_admin_css_colors=array("#14568a", "#14568a", "", "#c3def1");
	}
?>

     <style type="text/css">

	.generalstats_wrap ul {
		list-style-type : disc;
		padding: 5px 5px 5px 30px;
	}

      li.generalstats_sortablelist {
		background-color: <?php echo $current_wp_admin_css_colors[1]; ?>;
		color: <?php echo $current_wp_admin_css_colors[3]; ?>;
		cursor : move;
		padding: 3px 5px 3px 5px;
      }

      ul.generalstats_sortablelist {
		float: left;
		border: 1px <?php echo $current_wp_admin_css_colors[0]; ?> solid;
		list-style-image : none;
		list-style-type : none;
		margin: 10px 20px 20px 0px;
		padding: 10px;
      }

      #generalstats_DragandDrop {
		cursor : move;
		margin: 10px 100px 0px 0px;
		float: right;
		border: 1px dotted;
		width: 270px;
		padding: 5px;
      }

	#generalstats_DragandDrop_Edit_Label {
		background-color: <?php echo $current_wp_admin_css_colors[1]; ?>;
		color: <?php echo $current_wp_admin_css_colors[3]; ?>;
	}

	#generalstats_DragandDrop_Edit_Message {
		color: <?php echo $current_wp_admin_css_colors[0]; ?>;
	}

	img.generalstats_arrowbutton {
		vertical-align: bottom;
		cursor: pointer;
		margin-left: 5px;
	}

	img.generalstats_sectionbutton {
		cursor: pointer;
	}

      </style>

<?php

}

/*
called from widget_init hook
*/

function widget_generalstats_init() {
	$plugin_name="GeneralStats";
	$widgets="widgets";

	register_sidebar_widget(array($plugin_name, $widgets), 'widget_generalstats');
	register_widget_control(array($plugin_name, $widgets), 'widget_generalstats_control', 300, 100);
}

/*
adds metainformation - please leave this for stats!
*/

function generalstats_wp_head() {
  echo("<meta name=\"GeneralStats\" content=\"0.80\"/>");
}

/*
widget functions
*/

function widget_generalstats($args) {
	extract($args);

	$options = get_option('widget_generalstats');
	$title = $options['title'];

	echo $before_widget;
	echo $before_title . htmlentities($title) . $after_title;
	GeneralStatsComplete();
    	echo $after_widget;
}

/*
widget control
*/

function widget_generalstats_control() {

	$generalstats_title="generalstats-title";
	$generalstats_submit="generalstats-submit";
	$widget_generalstats="widget_generalstats";

	// Get our options and see if we're handling a form submission.
	$options = get_option($widget_generalstats);
	if ( !is_array($options) )
		$options = array('title'=>'', 'buttontext'=>__('GeneralStats', 'widgets'));
		if ( $_POST['generalstats-submit'] ) {
			$options['title'] = strip_tags(stripslashes($_POST[$generalstats_title]));
			update_option($widget_generalstats, $options);
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		
		echo '<p style="text-align:right;"><label for="'.$generalstats_title.'">' . __('Title:') . ' <input style="width: 200px;" id="'.$generalstats_title.'" name="'.$generalstats_title.'" type="text" value="'.$title.'" /></label></p>';
		echo '<input type="hidden" id="'.$generalstats_submit.'" name="'.$generalstats_submit.'" value="1" />';
		echo '<p style="text-align:left;"><label for="generalstats-options">Find the options <a href="options-general.php?page=generalstats/general-stats.php">here</a>!</label></p>';
	}

/*
echoes stats as defined in the option page
handles the cache-management
*/

function GeneralStatsComplete() {

	$fieldsPre="GeneralStats_";
	$cacheTime = get_option($fieldsPre.'Cache_Time');
	$lastCacheTime = get_option($fieldsPre.'Last_Cache_Time');

	$cacheAge = time() - $lastCacheTime;

	$cache = get_option($fieldsPre.'Cache');
	$forceCacheRefresh = get_option($fieldsPre.'Force_Cache_Refresh');

	//the cache is refreshed if cache refreshing is forced, the cache is empty
	//or the age of the cache is older then the defined caching time

	if ( ($forceCacheRefresh > 0) ||
	     (strlen($cache) < 1) ||
	     ($cacheAge>$cacheTime) ) {

		update_option($fieldsPre.'Cache', GeneralStatsCreateOutput());
		update_option($fieldsPre.'Force_Cache_Refresh','0');
		update_option($fieldsPre.'Last_Cache_Time',time());
	}

	echo get_option($fieldsPre.'Cache');

}

/*
force cache refresh
*/

function GeneralStatsForceCacheRefresh() {
	update_option('GeneralStats_Force_Cache_Refresh', '1'); 
}

/*
generates the formated output
*/

function GeneralStatsCreateOutput() {

    $ret="";

    /*
    get general tags
    */

    $fieldsPre="GeneralStats_";

    $before_list=stripslashes(get_option($fieldsPre.'before_List'));
    $after_list=stripslashes(get_option($fieldsPre.'after_List'));
    $before_tag=stripslashes(get_option($fieldsPre.'before_Tag'));
    $after_tag=stripslashes(get_option($fieldsPre.'after_Tag'));
    $before_detail=stripslashes(get_option($fieldsPre.'before_Details'));
    $after_detail=stripslashes(get_option($fieldsPre.'after_Details'));

    $fieldsPost_Position="_Position";
    $fieldsPost_Description="_Description";

    $fields=array(0 => "Users", 1 => "Categories", 2 => "Posts",
	3 => "Comments", 4 => "Pages", 5 => "Links", 6 => "Tags", 7 => "Link-Categories",
	10 => "Words_in_Posts", 11 => "Words_in_Comments", 12 => "Words_in_Pages");

    /*
    which order do you like today?
    */

    $orders=array();

    foreach($fields as $key => $field) {
	if (get_option($fieldsPre.$field.$fieldsPost_Position)!="") $orders[$key] = get_option($fieldsPre.$field.$fieldsPost_Position);
    }

    /*
    sort as wished
    */

    asort($orders);

    /*
    begin list
    */

    $ret.=($before_list);

    /*
    loop through desired stats
    */

    foreach($orders as $key => $order) {
	if (array_key_exists($key, $fields)) {
   	    $count=GeneralStatsCounter($key);
	    $tag=get_option($fieldsPre.$fields[$key].$fieldsPost_Description);

          $count=number_format($count,'0','',get_option($fieldsPre.'Thousand_Delimiter'));
          $ret.= $before_tag.$tag.$after_tag.$before_detail.$count.$after_detail;
	}
    }

    /*
    finish list
    */

    $ret.=($after_list);

    return $ret;

}

/*
GeneralStatsCounter
Param: $option
		0..users
		1..categories
		2..posts
		3..comments
		4..pages
		5..links
		6..tags
		7..link-categories
		10..words in posts
		11..words in comments
		12..words in pages
*/

function GeneralStatsCounter($option) {
      global $wpdb;

	$fields=array(
		0 => "$wpdb->users.ID) FROM $wpdb->users",
		1 => "$wpdb->terms.term_id) FROM $wpdb->terms INNER JOIN $wpdb->term_taxonomy ON $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id WHERE $wpdb->term_taxonomy.taxonomy='category'",
		2 => "$wpdb->posts.ID) FROM $wpdb->posts WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type = 'post'",
		3 => "$wpdb->comments.comment_ID) FROM $wpdb->comments WHERE $wpdb->comments.comment_approved = '1'",
		4 => "$wpdb->posts.ID) FROM $wpdb->posts WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type = 'page'",
		5 => "$wpdb->links.link_id) FROM $wpdb->links WHERE $wpdb->links.link_visible = 'Y'",
		6 => "$wpdb->terms.term_id) FROM $wpdb->terms INNER JOIN $wpdb->term_taxonomy ON $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id WHERE $wpdb->term_taxonomy.taxonomy='post_tag'",
		7 => "$wpdb->terms.term_id) FROM $wpdb->terms INNER JOIN $wpdb->term_taxonomy ON $wpdb->terms.term_id = $wpdb->term_taxonomy.term_id WHERE $wpdb->term_taxonomy.taxonomy='link_category'",
		10 => "FROM $wpdb->posts WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type = 'post'",
		11 => "FROM $wpdb->comments WHERE $wpdb->comments.comment_approved = '1'",
		12 => "FROM $wpdb->posts WHERE $wpdb->posts.post_status = 'publish' AND $wpdb->posts.post_type = 'page'");

	$fieldsAttributes=array(
		10 => "$wpdb->posts.post_content",
		11 => "$wpdb->comments.comment_content",
		12 => "$wpdb->posts.post_content");

 	$fieldsCountAttributes=array(
		10 => "$wpdb->posts.ID",
		11 => "$wpdb->comments.comment_ID",
		12 => "$wpdb->posts.ID");

      $result=0;

	if (array_key_exists($option, $fields)) {

      	/*
      	for values between 10 and 20 is a calculation needed
      	*/

      	if ($option>=10 && $option<=20) {
			$result=GeneralStats_WordCount($fields[$option], $fieldsAttributes[$option], $fieldsCountAttributes[$option]);
      	}

		/*
		for all other attributes use sql select count statement
		*/

      	else {
			/*
			SABRE CORPORATION
			http://wordpress.org/extend/plugins/sabre/
			*/
			if ($option==0 && defined('SABRE_TABLE')) {
				$fields[0]="u.ID) FROM $wpdb->users as u, ".SABRE_TABLE." as s WHERE u.ID=s.user_id AND s.status in ('ok') UNION SELECT COUNT(u.ID) FROM $wpdb->users as u LEFT JOIN ".SABRE_TABLE." as s ON u.ID=s.user_id WHERE s.user_id IS NULL";
			}

		      $results = $wpdb->get_col("SELECT COUNT(" .$fields[$option]);
			$result=$results[0];

			/*
			SABRE CORPORATION
			http://wordpress.org/extend/plugins/sabre/
			*/
			if ($option==0 && defined('SABRE_TABLE')) {
				$result+=$results[1];
			}
      	}

	}

      return $result;
}

/*
counts the words of posts, comments or pages
decreasing memory usage by incrementing limit statements
*/

function GeneralStats_WordCount($statement, $attribute, $countAttribute) {

      global $wpdb;
      $result=0;

	$rows_at_Once=get_option('GeneralStats_Rows_at_Once');

	$countStatement = "SELECT COUNT(".$countAttribute.") " .$statement;
	$counter = $wpdb->get_col($countStatement);
	$counter = $counter[0];
	$startLimit = 0;

	//if rows_at_Once is not or incorrect set
	if ($rows_at_Once<1) {
		$rows_at_Once=$counter;
	}

	$incrementStatement = "SELECT ".$attribute." ".$statement;

	//loop through the sql-statements
	while( $startLimit < $counter) {

		$results = $wpdb->get_col($incrementStatement." LIMIT ".$startLimit.", ".$rows_at_Once);

		//count the words for each statement
		for ($i=0; $i<count($results); $i++) {
			$result += str_word_count($results[$i]);
		}

		$startLimit+=$rows_at_Once;
	}

	return $result;
}

/*
removes values from array
original from php.net, author: admin \x40 uostas.net
*/

function GeneralStats_array_remval($val,$arr){
  $i=array_search($val,$arr);
  $arr=array_merge(array_slice($arr, 0,$i), array_slice($arr, $i+1));
  return $arr;
}

/*
add GeneralStats to WordPress Option Page
*/

function addGeneralStatsOptionPage() {
    if (function_exists('add_options_page')) {
        $page=add_options_page('GeneralStats', 'GeneralStats', 8, __FILE__, 'createGeneralStatsOptionPage');
        add_action('admin_print_scripts-'.$page, 'generalstats_admin_print_scripts');
        add_action('admin_head-'.$page, 'generalstats_admin_head');
    }
}

/*
produce toggle button for showing and hiding sections
*/

function generalstats_open_close_section($section, $default) {

	if ($default==='1') {
		$defaultImage='down';
		$defaultAlt='hide';
	}
	else {
		$defaultImage='right';
		$defaultAlt='show';
	}

	echo("<img class=\"generalstats_sectionbutton\" onclick=\"generalstats_trigger_effect(this, '".$section."', 'blind', '".GENERALSTATS_PLUGINURL."arrow_right_blue.png', '".GENERALSTATS_PLUGINURL."arrow_down_blue.png');\" alt=\"".$defaultAlt." Section\" src=\"".GENERALSTATS_PLUGINURL."arrow_".$defaultImage."_blue.png\" />&nbsp;");

}

/*
Option Page
*/

function createGeneralStatsOptionPage() {

    /*
    define constants
    */

    $fieldsPre="GeneralStats_";

    $fieldsPost_Position="_Position";
    $fieldsPost_Description="_Description";

    $fields=array(0 => "Users", 1 => "Categories", 2 => "Posts",
	3 => "Comments", 4 => "Pages", 5 => "Links", 6 => "Tags", 7 => "Link-Categories",
	10 => "Words_in_Posts", 11 => "Words_in_Comments", 12 => "Words_in_Pages");

    $csstags=array("before_List", "after_List", "before_Tag", "after_Tag", "before_Details", "after_Details");
    $available_Fields=array_keys($fields);

    $fields_position_defaults=array(0 => "1", 1 => "", 2 => "2", 3 => "3", 4 => "4",
	5 => "", 10 => "", 11 => "", 12 => "");
    $fields_description_defaults=array(0 => "Users", 1 => "Categories", 2 => "Posts", 3 => "Comments", 4 => "Pages", 5 => "Links", 6 => "Tags", 7 => "Link-Categories", 10 => "Words in Posts", 11 => "Words in Comments", 12 => "Words in Pages");
    $csstags_defaults=array("<ul>", "</ul>", "<li><strong>", "</strong>&nbsp;", "", "</li>");

    $Thousand_Delimiter="Thousand_Delimiter";
    $Cache_Time="Cache_Time";
    $Rows_at_Once="Rows_at_Once";

    $sections=array('Instructions_Section' => '1', 'Static_Tags_Section' => '0', 'CSS_Tags_Section' => '0', 'Administrative_Options_Section' => '0');

   /*
    configuration changed => store parameters
    */

    if (isset($_POST['info_update'])) {

        foreach ($fields as $field) {
            update_option($fieldsPre.$field.$fieldsPost_Position, $_POST[$fieldsPre.$field.$fieldsPost_Position]);
            update_option($fieldsPre.$field.$fieldsPost_Description, $_POST[$fieldsPre.$field.$fieldsPost_Description]);
        }

        foreach ($csstags as $csstag) {
            update_option($fieldsPre.$csstag, $_POST[$fieldsPre.$csstag]);
        }

        update_option($fieldsPre.$Thousand_Delimiter, $_POST[$fieldsPre.$Thousand_Delimiter]);

	  update_option($fieldsPre.$Cache_Time, $_POST[$fieldsPre.$Cache_Time]);
        update_option($fieldsPre.$Rows_at_Once, $_POST[$fieldsPre.$Rows_at_Once]);

        foreach ($sections as $key => $section) {
            update_option($fieldsPre.$key, $_POST[$fieldsPre.$key.'_Show']);
        }

        ?><div class="updated"><p><strong>
        <?php _e('Configuration changed!<br />Cache refreshed!')?><?php GeneralStatsForceCacheRefresh(); ?></strong></p></div>

      <?php }

      elseif (isset($_POST['load_default'])) {

        for ($i = 0; $i < sizeof($csstags); $i++) {
            update_option($fieldsPre.$csstags[$i], $csstags_defaults[$i]);
        }

        foreach ($fields as $key => $field) {
            update_option($fieldsPre.$fields[$key].$fieldsPost_Position, $fields_position_defaults[$key]);
        }

        foreach ($fields as $key => $field) {
            update_option($fieldsPre.$fields[$key].$fieldsPost_Description, $fields_description_defaults[$key]);
        }

	  update_option($fieldsPre.$Thousand_Delimiter, ',');

	  update_option($fieldsPre.$Cache_Time, '600');
	  update_option($fieldsPre.$Rows_at_Once, '100');

        foreach ($sections as $key => $section) {
            update_option($fieldsPre.$key, $section);
        }

        ?><div class="updated"><p><strong>
        <?php _e('Defaults loaded!<br />Cache refreshed!')?><?php GeneralStatsForceCacheRefresh(); ?></strong></p></div>

      <?php }

      elseif (isset($_GET['cleanup'])) {

	  generalstats_uninstall();

        ?><div class="updated"><p><strong>
        <?php _e('Settings deleted!')?></strong></p></div>

      <?php }

    foreach($sections as $key => $section) {
	if (get_option($fieldsPre.$key)!="") $sections[$key] = get_option($fieldsPre.$key);
    }

    $orders=array();

    foreach($fields as $key => $field) {
	if (get_option($fieldsPre.$field.$fieldsPost_Position)!="") $orders[$key] = get_option($fieldsPre.$field.$fieldsPost_Position);
    }

    asort($orders);

    /*
    begin list
    */

    $listTaken="";
    $listAvailable="";
    $before_tag="<li class=\"generalstats_sortablelist\" id=";
    $after_tag="</li>";

    /*
    build lists
    */

    $beforeKey="Tags_";

    foreach ($orders as $key => $order) {
        $tag=get_option($fieldsPre.$fields[$key].$fieldsPost_Description). ' ('. $fields[$key] .')';
	  $upArrow='<img class="generalstats_arrowbutton" src="'.GENERALSTATS_PLUGINURL.'arrow_up_blue.png" onclick="generalstats_moveElementUp('.$key.');" alt="move element up" />';
	  $downArrow='<img class="generalstats_arrowbutton" style="margin-right:15px" src="'.GENERALSTATS_PLUGINURL.'arrow_down_blue.png" onclick="generalstats_moveElementDown('.$key.');" alt="move element down" />';
        $available_Fields=GeneralStats_array_remval($key, $available_Fields);
        $listTaken.= $before_tag. "\"".$beforeKey.$key."\">".$upArrow.$downArrow.$tag.$after_tag."\n";
    }

    foreach($available_Fields as $key){
        $tag=get_option($fieldsPre.$fields[$key].$fieldsPost_Description). ' ('. $fields[$key]. ')';
	  $upArrow='<img class="generalstats_arrowbutton" src="'.GENERALSTATS_PLUGINURL.'arrow_up_blue.png" onclick="generalstats_moveElementUp('.$key.');" alt="move element up" />';
	  $downArrow='<img class="generalstats_arrowbutton" style="margin-right:15px" src="'.GENERALSTATS_PLUGINURL.'arrow_down_blue.png" onclick="generalstats_moveElementDown('.$key.');" alt="move element down" />';
	  $listAvailable.= $before_tag. "\"".$beforeKey.$key."\">".$upArrow.$downArrow.$tag.$after_tag."\n";
    }

    $listTakenListeners="";
    $listAvailableListeners="";

    /*
    build Listeners
    */

    foreach ($orders as $key => $order) {
       $listTakenListeners.="Event.observe('".$beforeKey.$key."', 'click', function(e){ generalstats_adoptDragandDropEdit('".$key."') });";
    }

    foreach ($available_Fields as $key) {
       $listAvailableListeners.="Event.observe('".$beforeKey.$key."', 'click', function(e){ generalstats_adoptDragandDropEdit('".$key."') });";
    }

    /*
    format list
    */

    $elementHeight=32;

    $sizeListTaken=( sizeof($fields)-sizeof($available_Fields) )*$elementHeight;
    if ($sizeListTaken<=0) $sizeListTaken=$elementHeight;
    $sizeListAvailable=sizeof($available_Fields)*$elementHeight;
    if ($sizeListAvailable<=0) $sizeListAvailable=$elementHeight;

    $listTaken="<div style=\"cursor:move\" id=\"generalstats_listTaken\"><h3>Taken Tags</h3><ul class=\"generalstats_sortablelist\" id=\"listTaken\" style=\"height:".$sizeListTaken."px;width:370px;\">".$listTaken."</ul></div>";
    $listAvailable="<div style=\"cursor:move\" id=\"generalstats_listAvailable\"><h3>Available Tags</h3><ul class=\"generalstats_sortablelist\" id=\"listAvailable\" style=\"height:".$sizeListAvailable."px;width:370px;\">".$listAvailable."</ul></div>";

    /*
    options form
    */

    ?>

    <div class="wrap"><div class="generalstats_wrap">

    <div class="submit">
      <input type="button" id="info_update_click" name="info_update_click" value="<?php _e('Update options') ?>" />
      <input type="button" id="load_default_click" name="load_default_click" value="<?php _e('Load defaults') ?>" />
    </div>

Welcome to the Settings-Page of <a target="_blank" href="http://www.neotrinity.at/projects/">GeneralStats</a>. This plugin counts the number of users, categories, posts, comments, pages, links, tags, link-categories, words in posts, words in comments and words in pages.

<h2><?php generalstats_open_close_section($fieldsPre.'Instructions_Section', $sections['Instructions_Section']); ?>Instructions</h2>

	<div id="<?php echo($fieldsPre); ?>Instructions_Section" <?php if ($sections['Instructions_Section']==='0') { ?>style="display:none"<?php } ?>>

        <ul><li>It may be a good start for GeneralStats first-timers to click on <strong>Load defaults</strong>.</li>
        <li>You can add available or remove taken tags (like posts, users, etc. ) via drag and drop between the lists in the <a href="#<?php echo($fieldsPre); ?>Drag_and_Drop">Drag and Drop Layout Section</a>. To customize the descriptions click on the field which you want to change in any list and edit the output name in the form on the right. After clicking <strong>Change</strong> the selected tag's name is adopted in its list. The tags can be re-orderd within a list either by drag and drop or by clicking on the arrows on the particular tag's left hand side. Don't forget to save all your adjustments by clicking on <strong>Update options</strong>.<br />

Hint: All parameters of GeneralStats can also be changed without the usage of Javascript in the <a href="#<?php echo($fieldsPre); ?>Static_Tags">Static Tags Section</a>.
</li>
        <li>Style-customizations can be made in the <a href="#<?php echo($fieldsPre); ?>CSS_Tags">CSS-Tags Section</a>. The <a href="#<?php echo($fieldsPre); ?>Administrative_Options">Administrative Options Section</a> reflects further settings for experts. (Defaults for both sections are automatically populated via the <strong>Load defaults</strong> button)</li>
        <li>Before you publish the results you can use the <a href="#<?php echo($fieldsPre); ?>Preview">Preview Section</a>.</li>
        <li>Finally, you can publish the previously selected and saved stats either by adding a <a href="widgets.php">Sidebar Widget</a> or by calling the php function <code>GeneralStatsComplete()</code> wherever you like.</li>
    <?php
	if (!function_exists('register_uninstall_hook')) { ?>
        <li>If you decide to uninstall GeneralStats firstly remove the optionally added <a href="widgets.php">Sidebar Widget</a> or the integrated php function call(s) and secondly <a href="?page=generalstats/general-stats.php&amp;cleanup=true" onclick="javascript:return confirm('Are you sure you want to delete all your settings?')">click here</a> to clean up the database. Then disable it in the <a href="plugins.php">Plugins Tab</a> and delete the <code>generalstats</code> directory in your WordPress Plugins directory (usually wp-content/plugins) on your webserver.</li>
    <?php }
	else { ?>
        <li>If you decide to uninstall GeneralStats firstly remove the optionally added <a href="widgets.php">Sidebar Widget</a> or the integrated php function call(s) and secondly disable and delete it in the <a href="plugins.php">Plugins Tab</a>.</li>
    <?php } ?>
</ul></div>

<h2>Support</h2>
        If you like to support the development of this plugin, donations are welcome. <?php echo(convert_smilies(':)')); ?> Maybe you also want to <a href="link-add.php">add a link</a> to <a href="http://www.neotrinity.at/projects/">http://www.neotrinity.at/projects/</a>.<br /><br />

        <form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_xclick" /><input type="hidden" name="business" value="&#110;&#101;&#111;&#64;&#x6E;&#x65;&#x6F;&#x74;&#x72;&#105;&#110;&#x69;&#x74;&#x79;&#x2E;&#x61;t" /><input type="hidden" name="item_name" value="neotrinity.at" /><input type="hidden" name="no_shipping" value="2" /><input type="hidden" name="no_note" value="1" /><input type="hidden" name="currency_code" value="USD" /><input type="hidden" name="tax" value="0" /><input type="hidden" name="bn" value="PP-DonationsBF" /><input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" style="border:0" name="submit" alt="Make payments with PayPal - it's fast, free and secure!" /><img alt="if you like to, you can support me" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" /></form><br /><br />

         <a name="<?php echo($fieldsPre); ?>Drag_and_Drop"></a><h2>Drag and Drop Layout</h2>

     <?php echo($listTaken); ?>

     <div id="generalstats_DragandDrop">

     <table class="form-table" style="margin-bottom:0">
        <tr><td><label for="generalstats_DragandDrop_Edit_Text">Fieldname</label></td>
	  <td><input disabled="disabled" name="generalstats_DragandDrop_Edit_Label" id="generalstats_DragandDrop_Edit_Label" type="text" size="20" maxlength="20" /></td>
     </tr>

     <tr>
        <td><label for="generalstats_DragandDrop_Edit_Text">Value</label></td>
        <td><input onkeyup="if(event.keyCode==13) generalstats_changeDragandDropEdit();" name="generalstats_DragandDrop_Edit_Text" id="generalstats_DragandDrop_Edit_Text" disabled="disabled" type="text" size="20" maxlength="20" /></td>
     </tr>

     <tr style="display:none" id="generalstats_DragandDrop_Edit_Message">
	  <td colspan="2" style="font-weight:bold">Successfully Changed!</td>
     </tr>

     <tr>
        <td colspan="2"><input type="button" id="generalstats_DragandDrop_Change" value="Change" /></td>
     </tr>

     </table>

     </div>

     <br style="clear:both" />

     <?php echo($listAvailable); ?>

     <br style="clear:both" /><br />

       <form action="options-general.php?page=generalstats/general-stats.php" method="post">

          <a name="<?php echo($fieldsPre); ?>Static_Tags"></a><h2><?php generalstats_open_close_section($fieldsPre.'Static_Tags_Section', $sections['Static_Tags_Section']); ?>Static Tags</h2>

	<div id="<?php echo($fieldsPre); ?>Static_Tags_Section" <?php if ($sections['Static_Tags_Section']==='0') { ?>style="display:none"<?php } ?>>

        This static customizing section forms the mirror of the <a href="#<?php echo($fieldsPre); ?>Drag_and_Drop">Drag and Drop Layout Section</a>.
        Changes which you make here are only reflected in the <a href="#<?php echo($fieldsPre); ?>Drag_and_Drop">Drag and Drop Layout Section</a> after pressing <strong>Update options</strong>.

    <table class="form-table"><?php

     foreach ($fields as $field) {
          echo("<tr>");
            echo("<td><label for=\"".$fieldsPre.$field.$fieldsPost_Position."\">");
            _e($field);
            echo("</label></td>");
              echo("<td><label for=\"".$fieldsPre.$field.$fieldsPost_Position."\">Position</label> <input type=\"text\" size=\"2\" name=\"".$fieldsPre.$field.$fieldsPost_Position."\" id=\"".$fieldsPre.$field.$fieldsPost_Position."\" value=\"".get_option($fieldsPre.$field.$fieldsPost_Position)."\" />\n");
              echo("<label for=\"".$fieldsPre.$field.$fieldsPost_Description."\">Description</label> <input type=\"text\" size=\"20\" name=\"".$fieldsPre.$field.$fieldsPost_Description."\" id=\"".$fieldsPre.$field.$fieldsPost_Description."\" value=\"".get_option($fieldsPre.$field.$fieldsPost_Description)."\" /></td>");
          echo("</tr>");
     }

     ?></table></div><br /><br />

          <a name="<?php echo($fieldsPre); ?>CSS_Tags"></a><h2><?php generalstats_open_close_section($fieldsPre.'CSS_Tags_Section', $sections['CSS_Tags_Section']); ?>CSS-Tags</h2>

	<div id="<?php echo($fieldsPre); ?>CSS_Tags_Section" <?php if ($sections['CSS_Tags_Section']==='0') { ?>style="display:none"<?php } ?>>

In this section you can customize the layout of <a href="#<?php echo($fieldsPre); ?>Preview">GeneralStats' output</a> after saving your changes by clicking on <strong>Update options</strong>. The structure of the available fields is as follows:<br /><br />

[before_List]<br />
&nbsp;&nbsp;&nbsp;&nbsp;[before_Tag]<strong>[TAG 1]</strong>[after_Tag][before_Details]<strong>[COUNT 1]</strong>[after_Details]<br />
&nbsp;&nbsp;&nbsp;&nbsp;...<br />
&nbsp;&nbsp;&nbsp;&nbsp;[before_Tag]<strong>[TAG n]</strong>[after_Tag][before_Details]<strong>[COUNT n]</strong>[after_Details]<br />
[after_List]<br /><br />

    <table class="form-table"><?php

     foreach ($csstags as $csstag) {
          echo("<tr>");
            echo("<td><label for=\"".$fieldsPre.$csstag."\">");
            _e($csstag);
            echo("</label></td>");
              echo("<td><input type=\"text\" size=\"30\" name=\"".$fieldsPre.$csstag."\" id=\"".$fieldsPre.$csstag."\" value=\"".htmlspecialchars(stripslashes(get_option($fieldsPre.$csstag)))."\" /></td>");
          echo("</tr>");
     }

     ?>

     <tr>
        <td><label for="<?php echo($fieldsPre.$Thousand_Delimiter); ?>"><?php _e($Thousand_Delimiter) ?></label></td>
            <td><input type="text" size="2" name="<?php echo($fieldsPre.$Thousand_Delimiter); ?>" id="<?php echo($fieldsPre.$Thousand_Delimiter); ?>" value="<?php echo get_option($fieldsPre.$Thousand_Delimiter); ?>" /></td>
      </tr>
    </table></div><br /><br />

          <a name="<?php echo($fieldsPre); ?>Administrative_Options"></a><h2><?php generalstats_open_close_section($fieldsPre.'Administrative_Options_Section', $sections['Administrative_Options_Section']); ?>Administrative Options</h2>

	<div id="<?php echo($fieldsPre); ?>Administrative_Options_Section" <?php if ($sections['Administrative_Options_Section']==='0') { ?>style="display:none"<?php } ?>>

These are the expert settings of GeneralStats. Please consult the <a target="_blank" href="http://wordpress.org/extend/plugins/generalstats/faq/">FAQ</a> for further information.

    <table class="form-table">
     <tr>
        <td><label for="<?php echo($fieldsPre.$Cache_Time); ?>"><?php _e($Cache_Time.' (in seconds)') ?></label></td>
            <td><input type="text" onblur="generalstats_checkNumeric(this,'','','','','',true);" size="2" name="<?php echo($fieldsPre.$Cache_Time); ?>" id="<?php echo($fieldsPre.$Cache_Time); ?>" value="<?php echo get_option($fieldsPre.$Cache_Time); ?>" /></td>
      </tr>

     <tr>
        <td><label for ="<?php echo($fieldsPre.$Rows_at_Once); ?>"><?php _e($Rows_at_Once.' (this option effects the Words_in_* attributes: higher value = increased memory usage, but better performing)') ?></label></td>
            <td><input type="text" onblur="generalstats_checkNumeric(this,'','','','','',true);" size="2" name="<?php echo($fieldsPre.$Rows_at_Once); ?>" id="<?php echo($fieldsPre.$Rows_at_Once); ?>" value="<?php echo get_option($fieldsPre.$Rows_at_Once); ?>" /></td>
      </tr>
    </table></div><br /><br />

    <a name="<?php echo($fieldsPre); ?>Preview"></a><h2>Preview</h2>

You can publish this output either by adding a <a href="widgets.php">Sidebar Widget</a> or by calling the php function <code>GeneralStatsComplete()</code> wherever you like.<br /><br />
    <?php GeneralStatsComplete(); ?>

    <div class="submit">
      <input type="submit" name="info_update" id="info_update" value="<?php _e('Update options') ?>" />
      <input type="submit" name="load_default" id="load_default" value="<?php _e('Load defaults') ?>" />
    </div>

    <?php

	foreach($sections as $key => $section) {
		echo("<input type=\"hidden\" id=\"".$fieldsPre.$key."_Show\" name=\"".$fieldsPre.$key."_Show\" value=\"".$section."\" />");
	}

    ?>

    </form>
    </div></div>

    <script type="text/javascript">

    /* <![CDATA[ */

    var fieldPre = "GeneralStats_";
    var fieldPost = "_Position";
    var keys = [0, 1, 2, 3, 4, 5, 6, 7, 10, 11, 12];
    var fields = ["Users", "Categories", "Posts", "Comments", "Pages", "Links", "Tags", "Link-Categories", "Words_in_Posts", "Words_in_Comments", "Words_in_Pages"];

	/*
	original source from Nannette Thacker
	taken from http://www.shiningstar.net/
	*/
	
	function generalstats_checkNumeric(objName,minval,maxval,comma,period,hyphen,message) {
		var numberfield = objName;

		if (generalstats_chkNumeric(objName,minval,maxval,comma,period,hyphen,message) == false) {
			return false;
		}

		else {
			return true;
		}
	}

	// only allow 0-9 be entered, plus any values passed
	// (can be in any order, and don't have to be comma, period, or hyphen)
	// if all numbers allow commas, periods, hyphens or whatever,
	// just hard code it here and take out the passed parameters

	function generalstats_chkNumeric(objName,minval,maxval,comma,period,hyphen,message) {

		var checkOK = "0123456789" + comma + period + hyphen;
		var checkStr = objName;
		var allValid = true;
		var decPoints = 0;
		var allNum = "";

		for (i = 0;  i < checkStr.value.length;  i++) {
			ch = checkStr.value.charAt(i);

			for (j = 0;  j < checkOK.length;  j++)
			if (ch == checkOK.charAt(j))
			break;

			if (j == checkOK.length) {
				allValid = false;
				break;
			}

			if (ch != ",")
				allNum += ch;
		}

		if (!allValid) {	
			if (message==true) {
				alertsay = "Please enter only these values \""
				alertsay = alertsay + checkOK + "\" in the \"" + checkStr.name + "\" field."
				alert(alertsay);
			}

			return (false);
		}

		// set the minimum and maximum
		var chkVal = allNum;
		var prsVal = parseInt(allNum);

		if (minval != "" && maxval != "") if (!(prsVal >= minval && prsVal <= maxval)) {
			if (message==true) {
				alertsay = "Please enter a value greater than or "
				alertsay = alertsay + "equal to \"" + minval + "\" and less than or "
				alertsay = alertsay + "equal to \"" + maxval + "\" in the \"" + checkStr.name + "\" field."
				alert(alertsay);
			}
			return (false);
		}
	}

    /*
    create drag and drop lists
    */

    Sortable.create("listTaken", {
	dropOnEmpty:true,
	containment:["listTaken","listAvailable"],
	constraint:false,
	onUpdate:function(){ generalstats_updateDragandDropLists(); }
	});

   Sortable.create("listAvailable", {
	dropOnEmpty:true,
	containment:["listTaken","listAvailable"],
	constraint:false
	});

      /*
      drag and drop lists update function
      updates fields and activates edit panel
      */

	function generalstats_updateDragandDropLists() {

	/*
	get current fields order
	*/

	var sequence=Sortable.sequence('listTaken');

	if (sequence.length>0) {
		var list = escape(sequence);
		var sorted_ids = unescape(list).split(',');
	}

	else {
		var sorted_ids = [-1];
	}

	/*
	clear all previously set values
	*/

	for (var i = 0; i < fields.length; i++) {
		document.getElementById(fieldPre+fields[i]+fieldPost).value = "";
	}

	/*
	set new values
	*/

	for (var i = 0; i < sorted_ids.length; i++) {

		/*
		looks up keys array for matching index
		*/

		for (var j = 0; j < keys.length; j++) {
			if (keys[j]==sorted_ids[i]) {
				document.getElementById(fieldPre+fields[j]+fieldPost).value = i+1;
			}
		}
	}

	/*
	dynamically set new list heights
	*/

      var elementHeight=32;

	var listTakenLength=sorted_ids.length*elementHeight;
	if (listTakenLength<=0) listTakenLength=elementHeight;
	document.getElementById('listTaken').style.height = (listTakenLength)+'px';

	list = escape(Sortable.sequence('listAvailable'));
	sorted_ids = unescape(list).split(',');

	listTakenLength=sorted_ids.length*elementHeight;
	if (listTakenLength<=0) listTakenLength=elementHeight;
	document.getElementById('listAvailable').style.height = (listTakenLength)+'px';

	}

	/*
	moves an element in a drag and drop list one position up
	modified by Nikk Folts, http://www.nikkfolts.com/
	*/

	function generalstats_moveElementUpforList(list, row) {
		return generalstats_moveRow(list, row, 1);
	}

	/*
	moves an element in a drag and drop list one position down
	modified by Nikk Folts, http://www.nikkfolts.com/
	*/

	function generalstats_moveElementDownforList(list, row) {
		return generalstats_moveRow(list, row, -1);
	}

	/*
	moves an element in a drag and drop list one position
	modified by Nikk Folts, http://www.nikkfolts.com/
	*/

	function generalstats_moveRow(list, row, dir) {
		var sequence=Sortable.sequence(list);
		var found=false;

		//move only, if there is more than one element in the list
		if (sequence.length>1) for (var j=0; j<sequence.length; j++) {

			//element found
			if (sequence[j]==row) {
				found=true;

				var i = j - dir;
				if (i >= 0 && i <= sequence.length) {
					var temp=sequence[i];
					sequence[i]=row;
					sequence[j]=temp;
					break;
				}
			}
		}

		Sortable.setSequence(list, sequence);
		return found;
	}

	/*
	handles moving up for both lists
	*/

	function generalstats_moveElementUp(key) {
		if (generalstats_moveElementUpforList('listTaken', key)==false)
			generalstats_moveElementUpforList('listAvailable', key);

		generalstats_updateDragandDropLists();
	}

	/*
	handles moving down for both lists
	*/

	function generalstats_moveElementDown(key) {
		if (generalstats_moveElementDownforList('listTaken', key)==false)
			generalstats_moveElementDownforList('listAvailable', key);

		generalstats_updateDragandDropLists();
	}

	/*
	load selected field in edit panel
	*/

	function generalstats_adoptDragandDropEdit (key) {
		document.getElementById('generalstats_DragandDrop_Edit_Message').style.display='none';

		for (var j = 0; j < keys.length; j++) {
			if (keys[j]==key) {
				document.getElementById('generalstats_DragandDrop_Edit_Label').value = fields[j];
				document.getElementById('generalstats_DragandDrop_Edit_Text').value = document.getElementById(fieldPre+fields[j]+'_Description').value; 
				document.getElementById('generalstats_DragandDrop_Edit_Text').disabled=null;
				document.getElementById('generalstats_DragandDrop_Edit_Text').focus();
			}
		}
	}

	/*
	change desired value for selected field in edit panel
	*/

	function generalstats_changeDragandDropEdit () {
		var fieldName= document.getElementById('generalstats_DragandDrop_Edit_Label').value;

		if (fieldName.length>0) {
			document.getElementById( fieldPre + fieldName +'_Description').value = document.getElementById('generalstats_DragandDrop_Edit_Text').value;
			new Effect.Highlight(document.getElementById('generalstats_DragandDrop'),{startcolor:'#30df8b'});
			new Effect.Appear(document.getElementById('generalstats_DragandDrop_Edit_Message'));

			//adopt drag and drop table
			for (var j = 0; j < fields.length; j++) {
				if (fields[j]==fieldName) {
					document.getElementById('Tags_'+keys[j]).childNodes[2].nodeValue= document.getElementById('generalstats_DragandDrop_Edit_Text').value+' ('+fieldName+')';
					new Effect.Highlight(document.getElementById('Tags_'+keys[j]),{startcolor:'#30df8b'});
				}
			}

		}

		else
		{
			alert('Please click on the desired list field to adopt setting!');
		}
	}

	/*
	toggles a div together with an image
	inspired by pnomolos
	http://godbit.com/forum/viewtopic.php?id=1111
	*/

	function generalstats_trigger_effect(src_element, div_id, effect, first_img, second_img){
		Effect.toggle(div_id, effect, {afterFinish:function(){

			if (src_element.src.match(first_img)) {
				src_element.src = second_img;
				src_element.alt = 'hide section';
				document.getElementById(div_id+'_Show').value =  '1';
			}

			else {
				src_element.src = first_img;
				src_element.alt = 'show section';
				document.getElementById(div_id+'_Show').value =  '0';
			}

		}});

		return true;
	}

	new Draggable('generalstats_listTaken');
	new Draggable('generalstats_listAvailable');
	new Draggable('generalstats_DragandDrop');

      Event.observe('generalstats_DragandDrop_Change', 'click', function(e){ generalstats_changeDragandDropEdit(); });

      Event.observe('info_update_click', 'click', function(e){ document.getElementById('info_update').click(); });
      Event.observe('load_default_click', 'click', function(e){ document.getElementById('load_default').click(); });

      <?php echo($listTakenListeners); ?>
      <?php echo($listAvailableListeners); ?>

   /* ]]> */

   </script>

<?php }

add_action('init', 'generalstats_init');
add_action('widgets_init', 'widget_generalstats_init');

if ( function_exists('register_uninstall_hook') )
	register_uninstall_hook( __FILE__, 'generalstats_uninstall' );

/*
database cleanup on uninstall
*/

function generalstats_uninstall() {
	delete_option('widget_generalstats');

	$fieldsPre="GeneralStats_";
	$fieldsPost_Position="_Position";
	$fieldsPost_Description="_Description";

	$fields=array(0 => "Users", 1 => "Categories", 2 => "Posts", 3 => "Comments", 4 => "Pages", 5 => "Links", 6 => "Tags", 7 => "Link-Categories", 10 => "Words_in_Posts", 11 => "Words_in_Comments", 12 => "Words_in_Pages");

	$csstags=array("before_List", "after_List", "before_Tag", "after_Tag", "before_Details", "after_Details");
$sections=array('Static_Tags_Section' => '0', 'CSS_Tags_Section' => '0', 'Administrative_Options_Section' => '0');

	delete_option($fieldsPre.'Cache');
	delete_option($fieldsPre.'Force_Cache_Refresh');
	delete_option($fieldsPre.'Last_Cache_Time');

	foreach ($fields as $field) {
		delete_option($fieldsPre.$field.$fieldsPost_Position);
		delete_option($fieldsPre.$field.$fieldsPost_Description);
	}

	foreach ($csstags as $csstag) {
		delete_option($fieldsPre.$csstag);
	}

	delete_option($fieldsPre."Thousand_Delimiter");

	delete_option($fieldsPre."Cache_Time");
	delete_option($fieldsPre."Rows_at_Once");

	foreach ($sections as $key => $section) {
		delete_option($fieldsPre.$key);
	}

}

?>