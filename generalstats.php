<?php

/*
Plugin Name: GeneralStats
Plugin URI: http://www.neotrinity.at/projects/
Description: Counts the number of users, categories, posts, comments, pages, links, tags, link-categories, words in posts, words in comments and words in pages.
Author: Bernhard Riedl
Version: 1.10
Author URI: http://www.neotrinity.at
*/

/*  Copyright 2006-2009  Bernhard Riedl  (email : neo@neotrinity.at)
    Inspirations & Proof-Reading by Veronika Grascher

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

	/*
	GeneralStats Constant
	*/

	DEFINE ('GENERALSTATS_PLUGINURL', plugins_url('', __FILE__) . '/');

	/* check for ajax-refresh-call */

	if (isset($_POST['generalstats-refresh'])) {
		GeneralStatsComplete();
		exit;
	}

	if (get_option('GeneralStats_Use_Ajax_Refresh')=='1') {
		wp_enqueue_script('prototype');
	}

}

/*
add hooks for forcing a cache refresh;
unfortunately the list on http://codex.wordpress.org/Plugin_API/Action_Reference
is not complete, so i walked through the wp source to find the appropriate triggers
*/

function generalstats_add_refresh_hooks() {

	/*
	users
	*/

	add_action('user_register', 'GeneralStatsForceCacheRefresh');
	add_action('deleted_user', 'GeneralStatsForceCacheRefresh');

	/*
	posts & pages
	*/

	add_action('save_post', 'GeneralStatsSavePostForceCacheRefresh', 10, 2);
	add_action('deleted_post', 'GeneralStatsForceCacheRefresh');

	/*
	comments
	*/

	add_action('comment_post', 'GeneralStatsCommentStatusForceCacheRefresh', 10, 2);
	add_action('edit_comment', 'GeneralStatsForceCacheRefresh');

	/* deleted_comment can be realized by
	using wp_set_comment_status
	*/

	add_action('wp_set_comment_status', 'GeneralStatsForceCacheRefresh');

	/*
	links
	*/

	add_action('add_link', 'GeneralStatsForceCacheRefresh'); 
	add_action('edit_link', 'GeneralStatsForceCacheRefresh');
	add_action('deleted_link', 'GeneralStatsForceCacheRefresh');

	/*
	terms (tags, categories & link-categories)
	*/

	add_action('created_term', 'GeneralStatsForceCacheRefresh');
	add_action('edited_term', 'GeneralStatsForceCacheRefresh');
	add_action('delete_term', 'GeneralStatsForceCacheRefresh');

	/*
	SABRE CORPORATION
	http://wordpress.org/extend/plugins/sabre/
	*/

	add_action('sabre_accepted_registration', 'GeneralStatsForceCacheRefresh');
	add_action('sabre_cancelled_registration', 'GeneralStatsForceCacheRefresh');

}

/*
adds the javascript code for re-occuring stats-updates
*/

function generalstats_ajax_refresh() {

	$fieldsPre="GeneralStats_";
	if(get_option($fieldsPre.'Use_Ajax_Refresh')=='1') { 
			$refreshTime = get_option($fieldsPre.'Refresh_Time');

			//regex taken from php.net by mark at codedesigner dot nl
			if (!preg_match('@^[-]?[0-9]+$@',$refreshTime) || $refreshTime<1)
				$refreshTime=30;
	?>

<script type="text/javascript" language="javascript">

	/* <![CDATA[ */

	/*
	Generalstats AJAX Refresh
	*/

	var generalstats_divclassname='generalstats-output';

	function generalstats_refresh() {
		var params = 'generalstats-refresh=1';
		new Ajax.Request(
			'<?php echo(get_option('home'). '/'); ?>',
			{
				method: 'post',
				parameters: params,
				onSuccess: generalstats_handleReply
			});
	}

	function generalstats_handleReply(response) {
		if (200 == response.status){
			var resultText=response.responseText;

			if (resultText.indexOf('<div class="'+generalstats_divclassname+'"')>-1) {
				var generalstats_blocks=$$('div.'+generalstats_divclassname);
				for (var i=0;i<generalstats_blocks.length;i++) {
					Element.replace(generalstats_blocks[i], resultText);
				}
			}
		}
	}

	Event.observe(window, 'load', function(e){ if ($$('div.'+generalstats_divclassname).length>0) new PeriodicalExecuter(generalstats_refresh, <?php echo($refreshTime); ?>); });

	/* ]]> */

</script>

	<?php }
}

/*
adds a settings link in the plugin-tab
*/

function generalstats_adminmenu_plugin_actions($links, $file) {
	if ($file == plugin_basename(__FILE__))
		$links[] = "<a href='options-general.php?page=".plugin_basename(__FILE__)."'>" . __('Settings') . "</a>";

	return $links;
}

/*
loads the necessary java-scripts,
which are all included in wordpress
for the admin-page
*/

function generalstats_admin_print_scripts() {
	wp_enqueue_script('prototype');
	wp_enqueue_script('scriptaculous-effects');
	wp_enqueue_script('scriptaculous-dragdrop');
}

/*
process the admin_color-array
*/

function generalstats_get_admin_colors() {

	/*
	default colors = fresh
	*/

	$available_admin_colors=array("fresh" => array("#464646", "#6D6D6D", "#F1F1F1", "#DFDFDF"), "classic" => array("#073447", "#21759B", "#EAF3FA", "#BBD8E7") );

	$current_color = get_user_option('admin_color');
	if (strlen($current_color)<1)
		$current_color="fresh";

	/*
	include user-defined color schemes
	*/

	$generalstats_available_admin_colors = apply_filters('generalstats_available_admin_colors', array());

	if (!empty($generalstats_available_admin_colors) && is_array($generalstats_available_admin_colors))
		foreach($generalstats_available_admin_colors as $key => $available_admin_color)
			if (is_array($available_admin_color) && sizeof($available_admin_color)==4)
				if (!array_key_exists($key, $available_admin_colors))
					$available_admin_colors[$key]=$generalstats_available_admin_colors[$key];

	if (!array_key_exists($current_color, $available_admin_colors))
		return $available_admin_colors["fresh"];
	else
		return $available_admin_colors[$current_color];
}

/*
loads the necessary css-styles
for the admin-page
*/

function generalstats_admin_head() {

	$generalstats_admin_css_colors=generalstats_get_admin_colors();
?>

     <style type="text/css">

	.generalstats_wrap ul {
		list-style-type : disc;
		padding: 5px 5px 5px 30px;
	}

	ul.subsubsub.generalstats {
		list-style: none;
		margin: 8px 0 5px;
		padding: 0;
		white-space: nowrap;
		float: left;
		float: none;
		display: block;
	}
 
	ul.subsubsub.generalstats a {
		line-height: 2;
		padding: .2em;
		text-decoration: none;
	}

	ul.subsubsub.generalstats li {
		display: inline;
		margin: 0;
		padding: 0;
		border-left: 1px solid #ccc;
		padding: 0 .5em;
	}

	ul.subsubsub.generalstats li:first-child {
		padding-left: 0;
		border-left: none;
	}

      li.generalstats_sortablelist {
		background-color: <?php echo $generalstats_admin_css_colors[1]; ?>;
		color: <?php echo $generalstats_admin_css_colors[3]; ?>;
		cursor : move;
		padding: 3px 5px 3px 5px;
      }

      ul.generalstats_sortablelist {
		float: left;
		border: 1px <?php echo $generalstats_admin_css_colors[0]; ?> solid;
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
		background-color: <?php echo $generalstats_admin_css_colors[1]; ?>;
		color: <?php echo $generalstats_admin_css_colors[3]; ?>;
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
adds some css to format generalstats on the dashboard
*/

function generalstats_add_dashboard_widget_css() {

?>

	<style type="text/css">

	.generalstats-output {
		font-size:11px;
		line-height:140%;
	}

      </style>

<?php

}

/*
add dashboard widget
*/

function generalstats_add_dashboard_widget() {
	wp_add_dashboard_widget('generalstats_dashboard_widget', 'GeneralStats', 'GeneralStatsComplete');
}

/*
add output to dashboard's right now box
inspired by Stephanie Leary
http://sillybean.net/
*/

function generalstats_add_right_now_box() {
	echo('<p></p>');
	GeneralStatsComplete();
}

/*
called from widget_init hook
*/

function widget_generalstats_init() {
	register_widget('WP_Widget_GeneralStats');
}

/*
adds metainformation - please leave this for stats!
*/

function generalstats_wp_head() {
  echo("<meta name=\"GeneralStats\" content=\"1.10\"/>");
}

/*
echoes stats as defined in the option page
handles the cache-management
*/

function GeneralStatsComplete() {

	$fieldsPre="GeneralStats_";
	$cacheTime = get_option($fieldsPre.'Cache_Time');
	$lastCacheTime = get_option($fieldsPre.'Last_Cache_Time');

	//regex taken from php.net by mark at codedesigner dot nl
	//if cacheTime is not or incorrect set
	if (!preg_match('@^[-]?[0-9]+$@',$cacheTime) || $cacheTime<0)
		$cacheTime=0;

	$cacheAge = gmdate('U') - $lastCacheTime;

	$cache = get_option($fieldsPre.'Cache');
	$forceCacheRefresh = get_option($fieldsPre.'Force_Cache_Refresh');

	//the cache is refreshed if cache refreshing is forced, the cache is empty
	//or the age of the cache is older then the defined caching time

	if ( ($forceCacheRefresh > 0) ||
	     (strlen($cache) < 1) ||
	     ($cacheAge>$cacheTime) ) {

		update_option($fieldsPre.'Cache', GeneralStatsCreateOutput());
		update_option($fieldsPre.'Force_Cache_Refresh','0');
		update_option($fieldsPre.'Last_Cache_Time',gmdate('U'));
	}

	echo get_option($fieldsPre.'Cache');

}

/*
check comment-status before force cache refresh
*/

function GeneralStatsCommentStatusForceCacheRefresh($id=-1, $status=false) {
	if ($status==1 || $status===false)
		GeneralStatsForceCacheRefresh();
}

/*
check post-status and visibility before force cache refresh
*/

function GeneralStatsSavePostForceCacheRefresh($id=-1, $post=false) {
	$autosave=false;
	if (function_exists('wp_is_post_autosave'))
		if ($post && is_object($post) && wp_is_post_autosave($post)>0)
			$autosave=true;

	$post_status='publish';
	if ($post && is_object($post) )
		$post_status=$post->post_status;

	if ($autosave===false && $post_status=='publish')
		GeneralStatsForceCacheRefresh();
}

/*
force cache refresh
*/

function GeneralStatsForceCacheRefresh($arg1='', $arg2='', $arg3='') {
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

    $before_list='<div class="generalstats-output">'.stripslashes(get_option($fieldsPre.'before_List'));
    $after_list=stripslashes(get_option($fieldsPre.'after_List')).'</div>';
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

    if (sizeof($orders)>0) {

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
    }

    else {
	    $ret.=$before_tag.'No stats to display yet...'.$after_tag;
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

	//regex taken from php.net by mark at codedesigner dot nl
	//if rows_at_Once is not or incorrect set
	if (!preg_match('@^[-]?[0-9]+$@',$rows_at_Once) || $rows_at_Once<1) {
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
	$page=add_options_page('GeneralStats', 'GeneralStats', 8, __FILE__, 'createGeneralStatsOptionPage');
	add_action('admin_print_scripts-'.$page, 'generalstats_admin_print_scripts');
	add_action('admin_head-'.$page, 'generalstats_admin_head');
}

/*
produce toggle button for showing and hiding sections
*/

function generalstats_open_close_section($section, $default) {

	$sectionPost='_Section';

	if ($default==='1') {
		$defaultImage='down';
		$defaultAlt='hide';
	}
	else {
		$defaultImage='right';
		$defaultAlt='show';
	}

	echo("<img id=\"".$section.$sectionPost."_Button\" class=\"generalstats_sectionbutton\" onclick=\"generalstats_toggleSectionDiv(this, '".$section."');\" alt=\"".$defaultAlt." Section\" src=\"".GENERALSTATS_PLUGINURL."arrow_".$defaultImage."_blue.png\" />&nbsp;");

}

/*
creates section-toogle link
use js to open section automatically,
if closed
*/

function generalstats_get_section_link($section, $allSections, $section_nicename='') {
	if (!array_key_exists($section, $allSections))
		return;

	$fieldsPre="GeneralStats_";
	$sectionPost='_Section';

	$menuitem_onclick='';

	if (strlen($section_nicename)<1)
		$section_nicename=str_replace('_', ' ', $section);

	if ($allSections[$section]=='1')
		$menuitem_onclick=" onclick=\"generalstats_assure_open_section('".$section."');\"";

	return '<a'.$menuitem_onclick.' href="#'.$fieldsPre.$section.'">'.$section_nicename.'</a>';
}

/*
Output JS
*/

function GeneralStatsOptionPageActionButtons($num) { ?>
	<div id="generalstats_actionbuttons_<?php echo($num); ?>" class="submit" style="display:none">
		<input type="button" id="info_update_click<?php echo($num); ?>" name="info_update_click<?php echo($num); ?>" value="<?php echo('Update options') ?>" />
		<input type="button" id="load_default_click<?php echo($num); ?>" name="load_default_click<?php echo($num); ?>" value="<?php echo('Load defaults') ?>" />
	</div>
<?php }

/*
Option Page
*/

function createGeneralStatsOptionPage() {

    /*
    define constants
    */

    $fieldsPre="GeneralStats_";
    $sectionPost="_Section";

    $fieldsPost_Position="_Position";
    $fieldsPost_Description="_Description";

    $fields=array(0 => "Users", 1 => "Categories", 2 => "Posts",
	3 => "Comments", 4 => "Pages", 5 => "Links", 6 => "Tags", 7 => "Link-Categories",
	10 => "Words_in_Posts", 11 => "Words_in_Comments", 12 => "Words_in_Pages");

    $csstags=array("before_List", "after_List", "before_Tag", "after_Tag", "before_Details", "after_Details");
    $available_Fields=array_keys($fields);

    $Use_Ajax_Refresh="Use_Ajax_Refresh";
    $Refresh_Time="Refresh_Time";

    $Use_Action_Hooks="Use_Action_Hooks";

    $Integrate_Right_Now="Integrate_Right_Now";

    $fields_position_defaults=array(0 => "1", 1 => "", 2 => "2", 3 => "3", 4 => "4",
	5 => "", 10 => "", 11 => "", 12 => "");
    $fields_description_defaults=array(0 => "Users", 1 => "Categories", 2 => "Posts", 3 => "Comments", 4 => "Pages", 5 => "Links", 6 => "Tags", 7 => "Link-Categories", 10 => "Words in Posts", 11 => "Words in Comments", 12 => "Words in Pages");
    $csstags_defaults=array("<ul>", "</ul>", "<li><strong>", "</strong>&nbsp;", "", "</li>");

    $Thousand_Delimiter="Thousand_Delimiter";
    $Cache_Time="Cache_Time";
    $Rows_at_Once="Rows_at_Once";

    $sections=array('Instructions' => '1', 'Static_Tags' => '1', 'CSS_Tags' => '1', 'Administrative_Options' => '1');

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

	  if (isset($_POST[$fieldsPre.$Use_Action_Hooks])) {
	    update_option($fieldsPre.$Use_Action_Hooks, '1');
	  }
	  else {
	    update_option($fieldsPre.$Use_Action_Hooks, '0');
	  }

	  if (isset($_POST[$fieldsPre.$Integrate_Right_Now])) {
	    update_option($fieldsPre.$Integrate_Right_Now, '1');
	  }
	  else {
	    update_option($fieldsPre.$Integrate_Right_Now, '0');
	  }

	  if (isset($_POST[$fieldsPre.$Use_Ajax_Refresh])) {
	    update_option($fieldsPre.$Use_Ajax_Refresh, '1');
	    update_option($fieldsPre.$Refresh_Time, $_POST[$fieldsPre.$Refresh_Time]);
	  }
	  else {
	    update_option($fieldsPre.$Use_Ajax_Refresh, '0');
	    update_option($fieldsPre.$Refresh_Time, '');
	  }

	  update_option($fieldsPre.$Cache_Time, $_POST[$fieldsPre.$Cache_Time]);
        update_option($fieldsPre.$Rows_at_Once, $_POST[$fieldsPre.$Rows_at_Once]);

        foreach ($sections as $key => $section) {
            update_option($fieldsPre.$key.$sectionPost, $_POST[$fieldsPre.$key.$sectionPost.'_Show']);
        }

        ?><div class="updated"><p><strong>
        <?php echo('Configuration changed and Cache refreshed!')?><?php GeneralStatsForceCacheRefresh(); ?><?php echo('<br /><br />Have a look at <a href="#'.$fieldsPre.'Preview">the preview</a>!')?></strong></p></div>

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

        update_option($fieldsPre.$Use_Ajax_Refresh, '0');
        update_option($fieldsPre.$Refresh_Time, '');

        update_option($fieldsPre.$Use_Action_Hooks, '1');

        update_option($fieldsPre.$Integrate_Right_Now, '0');

 	  update_option($fieldsPre.$Cache_Time, '600');
	  update_option($fieldsPre.$Rows_at_Once, '100');

        foreach ($sections as $key => $section) {
            update_option($fieldsPre.$key.$sectionPost, $section);
        }

        ?><div class="updated"><p><strong>
        <?php echo('Defaults loaded and Cache refreshed!')?><?php GeneralStatsForceCacheRefresh(); ?></strong></p></div>

      <?php }

      elseif (isset($_GET['cleanup'])) {

	  generalstats_uninstall();

        ?><div class="updated"><p><strong>
        <?php echo('Settings deleted!')?></strong></p></div>

      <?php }

    foreach($sections as $key => $section) {
		if (get_option($fieldsPre.$key.$sectionPost)!="") $sections[$key] = get_option($fieldsPre.$key.$sectionPost);
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

    $listTaken="<div style=\"cursor:move\" id=\"generalstats_listTaken\"><h3>Taken Tags</h3><ul class=\"generalstats_sortablelist\" id=\"gs_listTaken\" style=\"height:".$sizeListTaken."px;width:370px;\">".$listTaken."</ul></div>";
    $listAvailable="<div style=\"cursor:move\" id=\"generalstats_listAvailable\"><h3>Available Tags</h3><ul class=\"generalstats_sortablelist\" id=\"gs_listAvailable\" style=\"height:".$sizeListAvailable."px;width:370px;\">".$listAvailable."</ul></div>";

    /*
    options form
    */

    ?>

    <div class="wrap">
	<ul class="subsubsub generalstats">
	<?php
	$allSections=array();

	foreach ($sections as $key => $section) {
		$allSections[$key]='1';

		if ($key=='Instructions')
			$allSections['Drag_and_Drop']='0';
	}

	$allSections['Preview']='0';

	$generalstats_menu='';

	foreach ($allSections as $key => $section)
		$generalstats_menu.='<li>'.generalstats_get_section_link($key, $allSections).'</li>';

	echo($generalstats_menu);
	?>
	</ul>

	<div class="generalstats_wrap">

Welcome to the Settings-Page of <a target="_blank" href="http://www.neotrinity.at/projects/">GeneralStats</a>. This plugin counts the number of users, categories, posts, comments, pages, links, tags, link-categories, words in posts, words in comments and words in pages.

<h2><?php generalstats_open_close_section($fieldsPre.'Instructions', $sections['Instructions']); ?>Instructions</h2>

	<div id="<?php echo($fieldsPre); ?>Instructions<?php echo($sectionPost); ?>" <?php if ($sections['Instructions']==='0') { ?>style="display:none"<?php } ?>>

        <ul><li>It may be a good start for GeneralStats first-timers to click on <strong>Load defaults</strong>.</li>
        <li>You can add available or remove taken tags (like posts, users, etc.) via drag and drop between the lists in the <?php echo(generalstats_get_section_link('Drag_and_Drop', $allSections, 'Drag and Drop Layout Section')); ?>. To customize the descriptions click on the field which you want to change in any list and edit the output name in the form on the right. After clicking <strong>Change</strong> the selected tag's name is adopted in its list. The tags can be re-orderd within a list either by drag and drop or by clicking on the arrows on the particular tag's left hand side. Don't forget to save all your adjustments by clicking on <strong>Update options</strong>.<br />

Hint: All parameters of GeneralStats can also be changed without the usage of Javascript in the <?php echo(generalstats_get_section_link('Static_Tags', $allSections, 'Static Tags Section')); ?>.
</li>
        <li>Style-customizations can be made in the <?php echo(generalstats_get_section_link('CSS_Tags', $allSections, 'CSS-Tags Section')); ?>. (Defaults are automatically populated via the <strong>Load defaults</strong> button)</li>
	  <li>You can activate an optional Ajax refresh for automatical updates of your stats-output in the <?php echo(generalstats_get_section_link('Administrative_Options', $allSections, 'Administrative Options Section')); ?>. In this section you can also find the caching and performance options of GeneralStats.</li>
        <li>Before you publish the results you can use the <?php echo(generalstats_get_section_link('Preview', $allSections, 'Preview Section')); ?>.</li>
        <li>Finally, you can publish the previously selected and saved stats either by adding a <a href="widgets.php">Sidebar Widget</a> or by calling the php function <code>GeneralStatsComplete()</code> wherever you like. Moreover you can also display your current stats-selection as <a href="index.php">Dashboard Widget</a>.</li>
        <li>If you decide to uninstall GeneralStats firstly remove the optionally added <a href="widgets.php">Sidebar Widget</a> or the integrated php function call(s) and secondly disable and delete it in the <a href="plugins.php">Plugins Tab</a>.</li>
</ul>

<?php GeneralStatsOptionPageActionButtons(1); ?>

</div>

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

<?php GeneralStatsOptionPageActionButtons(2); ?>

       <form action="options-general.php?page=<?php echo(plugin_basename(__FILE__)); ?>" method="post">

          <a name="<?php echo($fieldsPre); ?>Static_Tags"></a><h2><?php generalstats_open_close_section($fieldsPre.'Static_Tags', $sections['Static_Tags']); ?>Static Tags</h2>

	<div id="<?php echo($fieldsPre); ?>Static_Tags<?php echo($sectionPost); ?>" <?php if ($sections['Static_Tags']==='0') { ?>style="display:none"<?php } ?>>

        This static customizing section forms the mirror of the <?php echo(generalstats_get_section_link('Drag_and_Drop', $allSections, 'Drag and Drop Layout Section')); ?>.
        Changes which you make here are only reflected in the <?php echo(generalstats_get_section_link('Drag_and_Drop', $allSections, 'Drag and Drop Layout Section')); ?> after pressing <strong>Update options</strong>.

    <table class="form-table"><?php

     foreach ($fields as $field) {
          echo("<tr>");
            echo("<td><label for=\"".$fieldsPre.$field.$fieldsPost_Position."\">");
            echo($field);
            echo("</label></td>");
              echo("<td><label for=\"".$fieldsPre.$field.$fieldsPost_Position."\">Position</label> <input type=\"text\" size=\"2\" maxlength=\"2\" name=\"".$fieldsPre.$field.$fieldsPost_Position."\" id=\"".$fieldsPre.$field.$fieldsPost_Position."\" value=\"".get_option($fieldsPre.$field.$fieldsPost_Position)."\" />\n");
              echo("<label for=\"".$fieldsPre.$field.$fieldsPost_Description."\">Description</label> <input type=\"text\" size=\"20\" maxlength=\"20\" name=\"".$fieldsPre.$field.$fieldsPost_Description."\" id=\"".$fieldsPre.$field.$fieldsPost_Description."\" value=\"".get_option($fieldsPre.$field.$fieldsPost_Description)."\" /></td>");
          echo("</tr>");
     }

     ?></table>

<?php GeneralStatsOptionPageActionButtons(3); ?>

</div><br /><br />

          <a name="<?php echo($fieldsPre); ?>CSS_Tags"></a><h2><?php generalstats_open_close_section($fieldsPre.'CSS_Tags', $sections['CSS_Tags']); ?>CSS-Tags</h2>

	<div id="<?php echo($fieldsPre); ?>CSS_Tags<?php echo($sectionPost); ?>" <?php if ($sections['CSS_Tags']==='0') { ?>style="display:none"<?php } ?>>

In this section you can customize the layout of <?php echo(generalstats_get_section_link('Preview', $allSections, 'GeneralStats output')); ?> after saving your changes by clicking on <strong>Update options</strong>. The structure of the available fields is as follows:<br /><br />

[before_List]<br />
&nbsp;&nbsp;&nbsp;&nbsp;[before_Tag]<strong>[TAG 1]</strong>[after_Tag][before_Details]<strong>[COUNT 1]</strong>[after_Details]<br />
&nbsp;&nbsp;&nbsp;&nbsp;...<br />
&nbsp;&nbsp;&nbsp;&nbsp;[before_Tag]<strong>[TAG n]</strong>[after_Tag][before_Details]<strong>[COUNT n]</strong>[after_Details]<br />
[after_List]<br /><br />

    <table class="form-table"><?php

     foreach ($csstags as $csstag) {
          echo("<tr>");
            echo("<td><label for=\"".$fieldsPre.$csstag."\">");
            echo($csstag);
            echo("</label></td>");
              echo("<td><input type=\"text\" size=\"30\" maxlength=\"50\" name=\"".$fieldsPre.$csstag."\" id=\"".$fieldsPre.$csstag."\" value=\"".htmlspecialchars(stripslashes(get_option($fieldsPre.$csstag)))."\" /></td>");
          echo("</tr>");
     }

     ?>

     <tr>
        <td><label for="<?php echo($fieldsPre.$Thousand_Delimiter); ?>"><?php echo($Thousand_Delimiter) ?></label></td>
            <td><input type="text" size="2" maxlength="4" name="<?php echo($fieldsPre.$Thousand_Delimiter); ?>" id="<?php echo($fieldsPre.$Thousand_Delimiter); ?>" value="<?php echo get_option($fieldsPre.$Thousand_Delimiter); ?>" /></td>
      </tr>
    </table><br /><br />

Moreover you can add style attributes for the container <code>div</code>-element by modifying the class <code>generalstats-output</code> in your <a href="themes.php">Theme</a>, e.g. with the WordPress <a href="theme-editor.php">Theme-Editor</a>.<br /><br />

<strong>Syntax</strong><br /><br />
<code>.generalstats-output { yourstyle }</code>

<?php GeneralStatsOptionPageActionButtons(4); ?>

</div><br /><br />

          <a name="<?php echo($fieldsPre); ?>Administrative_Options"></a><h2><?php generalstats_open_close_section($fieldsPre.'Administrative_Options', $sections['Administrative_Options']); ?>Administrative Options</h2>

	<div id="<?php echo($fieldsPre); ?>Administrative_Options<?php echo($sectionPost); ?>" <?php if ($sections['Administrative_Options']==='0') { ?>style="display:none"<?php } ?>>

In this section you can enable and customize the Ajax-Refresh of GeneralStats. After activating Use_Ajax_Refresh you can specify the seconds for the update interval.<br /><br />

As all stats are retrieved from the server on every refresh, a Refresh_Time of one second is mostly not realizable for the average server out there. Moreover, please remember that every update causes bandwith usage for your readers and your host.
    <table class="form-table">
     <tr>
        <td><label for ="<?php echo($fieldsPre.$Use_Ajax_Refresh); ?>"><?php echo($Use_Ajax_Refresh.'') ?></label></td>
            <td><input type="checkbox" onclick="generalstats_toggleAjaxRefreshFields(this, '<?php echo($Refresh_Time); ?>');" name="<?php echo($fieldsPre.$Use_Ajax_Refresh); ?>" id="<?php echo($fieldsPre.$Use_Ajax_Refresh); ?>" <?php if(get_option($fieldsPre.$Use_Ajax_Refresh)==1) echo('checked="checked"'); ?> /></td>
      </tr>

     <tr>
        <td><label for="<?php echo($fieldsPre.$Refresh_Time); ?>"><?php echo($Refresh_Time.' (in seconds)') ?></label></td>
            <td><input type="text" onblur="generalstats_checkNumeric(this,1,3600,'','','',true);" size="8" maxlength="8" name="<?php echo($fieldsPre.$Refresh_Time); ?>" <?php if(get_option($fieldsPre.$Use_Ajax_Refresh)!=1) echo('disabled="disabled"'); ?> id="<?php echo($fieldsPre.$Refresh_Time); ?>" value="<?php echo get_option($fieldsPre.$Refresh_Time); ?>" /></td>
      </tr>
    </table><br /><br />

If you activate the next option, GeneralStats will integrate your stats into the "Right Now"-Box on your <a href="index.php">Dashboard</a>.

    <table class="form-table">
     <tr>
        <td><label for ="<?php echo($fieldsPre.$Integrate_Right_Now); ?>"><?php echo($Integrate_Right_Now.'') ?></label></td>
            <td><input type="checkbox" name="<?php echo($fieldsPre.$Integrate_Right_Now); ?>" id="<?php echo($fieldsPre.$Integrate_Right_Now); ?>" <?php if(get_option($fieldsPre.$Integrate_Right_Now)==1) echo('checked="checked"'); ?> /></td>
      </tr>
    </table><br /><br />

With the following options you can influence the caching behaviour of GeneralStats. If you activate Use_Action_Hooks, the cache-cycle will be interrupted for events like editing a post or publishing a new comment. Thus, your stats should be updated automatically even if you have defined a longer caching time.

    <table class="form-table">
     <tr>
        <td><label for="<?php echo($fieldsPre.$Cache_Time); ?>"><?php echo($Cache_Time.' (in seconds)') ?></label></td>
            <td><input type="text" onblur="generalstats_checkNumeric(this,'','','','','',true);" size="2" maxlength="5" name="<?php echo($fieldsPre.$Cache_Time); ?>" id="<?php echo($fieldsPre.$Cache_Time); ?>" value="<?php echo get_option($fieldsPre.$Cache_Time); ?>" /></td>
      </tr>

     <tr>
        <td><label for ="<?php echo($fieldsPre.$Use_Action_Hooks); ?>"><?php echo($Use_Action_Hooks.'') ?></label></td>
            <td><input type="checkbox" name="<?php echo($fieldsPre.$Use_Action_Hooks); ?>" id="<?php echo($fieldsPre.$Use_Action_Hooks); ?>" <?php if(get_option($fieldsPre.$Use_Action_Hooks)==1) echo('checked="checked"'); ?> /></td>
      </tr>
    </table><br /><br />

Rows_at_Once is a performance-related expert setting of GeneralStats. Please consult the <a target="_blank" href="http://wordpress.org/extend/plugins/generalstats/faq/">FAQ</a> for further information.

    <table class="form-table">

     <tr>
        <td><label for ="<?php echo($fieldsPre.$Rows_at_Once); ?>"><?php echo($Rows_at_Once.' (this option effects the Words_in_* attributes: higher value = increased memory usage, but better performance)') ?></label></td>
            <td><input type="text" onblur="generalstats_checkNumeric(this,1,10000,'','','',true);" size="2" maxlength="5" name="<?php echo($fieldsPre.$Rows_at_Once); ?>" id="<?php echo($fieldsPre.$Rows_at_Once); ?>" value="<?php echo get_option($fieldsPre.$Rows_at_Once); ?>" /></td>
      </tr>
    </table>

<?php GeneralStatsOptionPageActionButtons(5); ?>

</div><br /><br />

    <a name="<?php echo($fieldsPre); ?>Preview"></a><h2>Preview</h2>

You can publish this output either by adding a <a href="widgets.php">Sidebar Widget</a> or by calling the php function <code>GeneralStatsComplete()</code> wherever you like.<br /><br />
    <?php if (!isset($_GET['cleanup'])) GeneralStatsComplete(); ?>

    <div class="submit">
      <input type="submit" name="info_update" id="info_update" value="<?php echo('Update options') ?>" />
      <input type="submit" name="load_default" id="load_default" value="<?php echo('Load defaults') ?>" />
    </div>

    <?php

	foreach($sections as $key => $section) {
		echo("<input type=\"hidden\" id=\"".$fieldsPre.$key.$sectionPost."_Show\" name=\"".$fieldsPre.$key.$sectionPost."_Show\" value=\"".$section."\" />");
	}

    ?>

    </form>
    </div></div>

    <script type="text/javascript">

    /* <![CDATA[ */

    var generalstats_fieldPre = "GeneralStats_";
    var generalstats_fieldPost = "_Position";
    var generalstats_keys = [0, 1, 2, 3, 4, 5, 6, 7, 10, 11, 12];
    var generalstats_fields = ["Users", "Categories", "Posts", "Comments", "Pages", "Links", "Tags", "Link-Categories", "Words_in_Posts", "Words_in_Comments", "Words_in_Pages"];

	/*
	original source from Nannette Thacker
	taken from http://www.shiningstar.net/
	*/
	
	function generalstats_checkNumeric(objName,minval,maxval,comma,period,hyphen,message) {
		var numberfield = objName;

		if (generalstats_chkNumeric(objName,minval,maxval,comma,period,hyphen,message) == false) {
			objName.value='';
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

    Sortable.create("gs_listTaken", {
	dropOnEmpty:true,
	containment:["gs_listTaken","gs_listAvailable"],
	constraint:false,
	onUpdate:function(){ generalstats_updateDragandDropLists(); }
	});

   Sortable.create("gs_listAvailable", {
	dropOnEmpty:true,
	containment:["gs_listTaken","gs_listAvailable"],
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

	var sequence=Sortable.sequence('gs_listTaken');

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

	for (var i = 0; i < generalstats_fields.length; i++) {
		document.getElementById(generalstats_fieldPre+generalstats_fields[i]+generalstats_fieldPost).value = "";
	}

	/*
	set new values
	*/

	for (var i = 0; i < sorted_ids.length; i++) {

		/*
		looks up keys array for matching index
		*/

		for (var j = 0; j < generalstats_keys.length; j++) {
			if (generalstats_keys[j]==sorted_ids[i]) {
				document.getElementById(generalstats_fieldPre+generalstats_fields[j]+generalstats_fieldPost).value = i+1;
			}
		}
	}

	/*
	dynamically set new list heights
	*/

      var elementHeight=32;

	var listTakenLength=sorted_ids.length*elementHeight;
	if (listTakenLength<=0) listTakenLength=elementHeight;
	document.getElementById('gs_listTaken').style.height = (listTakenLength)+'px';

	list = escape(Sortable.sequence('gs_listAvailable'));
	sorted_ids = unescape(list).split(',');

	listTakenLength=sorted_ids.length*elementHeight;
	if (listTakenLength<=0) listTakenLength=elementHeight;
	document.getElementById('gs_listAvailable').style.height = (listTakenLength)+'px';

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
		if (generalstats_moveElementUpforList('gs_listTaken', key)==false)
			generalstats_moveElementUpforList('gs_listAvailable', key);

		generalstats_updateDragandDropLists();
	}

	/*
	handles moving down for both lists
	*/

	function generalstats_moveElementDown(key) {
		if (generalstats_moveElementDownforList('gs_listTaken', key)==false)
			generalstats_moveElementDownforList('gs_listAvailable', key);

		generalstats_updateDragandDropLists();
	}

	/*
	load selected field in edit panel
	*/

	function generalstats_adoptDragandDropEdit (key) {
		document.getElementById('generalstats_DragandDrop_Edit_Message').style.display='none';

		for (var j = 0; j < generalstats_keys.length; j++) {
			if (generalstats_keys[j]==key) {
				document.getElementById('generalstats_DragandDrop_Edit_Label').value = generalstats_fields[j];
				document.getElementById('generalstats_DragandDrop_Edit_Text').value = document.getElementById(generalstats_fieldPre+generalstats_fields[j]+'_Description').value; 
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
			document.getElementById( generalstats_fieldPre+ fieldName +'_Description').value = document.getElementById('generalstats_DragandDrop_Edit_Text').value;
			new Effect.Highlight(document.getElementById('generalstats_DragandDrop'),{startcolor:'#30df8b'});
			new Effect.Appear(document.getElementById('generalstats_DragandDrop_Edit_Message'));

			//adopt drag and drop table
			for (var j = 0; j < generalstats_fields.length; j++) {
				if (generalstats_fields[j]==fieldName) {
					document.getElementById('Tags_'+generalstats_keys[j]).childNodes[2].nodeValue= document.getElementById('generalstats_DragandDrop_Edit_Text').value+' ('+fieldName+')';
					new Effect.Highlight(document.getElementById('Tags_'+generalstats_keys[j]),{startcolor:'#30df8b'});
				}
			}

		}

		else
		{
			alert('Please click on the desired list field to adopt setting!');
		}
	}

	/*
	enables/disables the associated fields of a checkbox input
	*/

	function generalstats_toggleAjaxRefreshFields(element, field) {
		var generalstats_newentry="GeneralStats_";
		var isChecked=element.checked;

		if (isChecked) {
			document.getElementById(generalstats_newentry+field).value='30';
			document.getElementById(generalstats_newentry+field).disabled=null;
		}
		else {
			document.getElementById(generalstats_newentry+field).value='';
			document.getElementById(generalstats_newentry+field).disabled='disabled';
		}
	}

	/*
	assures, that the section is opened, if clicked
	*/

	function generalstats_assure_open_section(section) {
		if ($('<?php echo($fieldsPre."'+section+'".$sectionPost); ?>_Show').value=='0')
			generalstats_toggleSectionDiv($('<?php echo($fieldsPre."'+section+'".$sectionPost); ?>_Button'), '<?php echo($fieldsPre."'+section+'"); ?>');
	}

	/*
	toggles a section (div and img)
	*/

	function generalstats_toggleSectionDiv(src_element, div_id) {
		generalstats_toggle_div_and_image(src_element, div_id+'<?php echo($sectionPost) ?>', 'blind', '<?php echo(GENERALSTATS_PLUGINURL) ?>arrow_right_blue.png', '<?php echo(GENERALSTATS_PLUGINURL) ?>arrow_down_blue.png');
	}

	/*
	toggles a div together with an image
	inspired by pnomolos
	http://godbit.com/forum/viewtopic.php?id=1111
	*/

	function generalstats_toggle_div_and_image(src_element, div_id, effect, first_img, second_img){
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

	 for (var i=1;i<6;i++) {
	       Event.observe('info_update_click'+i, 'click', function(e){ document.getElementById('info_update').click(); });
       	 Event.observe('load_default_click'+i, 'click', function(e){ document.getElementById('load_default').click(); });
		 new Effect.Appear(document.getElementById('generalstats_actionbuttons_'+i), {duration:0, from:1, to:1});
	 }

      <?php echo($listTakenListeners); ?>
      <?php echo($listAvailableListeners); ?>

   /* ]]> */

   </script>

<?php }

add_action('init', 'generalstats_init');
add_action('widgets_init', 'widget_generalstats_init');

add_action('wp_head', 'generalstats_ajax_refresh');
add_action('wp_head', 'generalstats_wp_head');

add_action('admin_footer', 'generalstats_ajax_refresh');
add_action('admin_head', 'generalstats_wp_head');
add_action('admin_menu', 'addGeneralStatsOptionPage');

if (get_option('GeneralStats_Use_Action_Hooks')=='1') {
	generalstats_add_refresh_hooks();
}

add_action('wp_dashboard_setup', 'generalstats_add_dashboard_widget' );

if (get_option('GeneralStats_Integrate_Right_Now')=='1') {
	add_action('activity_box_end', 'generalstats_add_right_now_box');
}

add_action('admin_head-index.php', 'generalstats_add_dashboard_widget_css');

add_filter('plugin_action_links', 'generalstats_adminmenu_plugin_actions', 10, 2);

register_uninstall_hook( __FILE__, 'generalstats_uninstall' );

/*
widget class
*/

class WP_Widget_GeneralStats extends WP_Widget {

	/*
	constructor
	*/

	function WP_Widget_GeneralStats() {
		$widget_ops = array('classname' => 'widget_generalstats', 'description' => 'Counts the number of users, categories, posts, comments, pages, links, tags, link-categories, words in posts, words in comments and words in pages.');
		$this->WP_Widget('generalstats', 'GeneralStats', $widget_ops);
	}

	/*
	produces the widget-output
	*/

	function widget($args, $instance) {
		extract($args);

		$title = empty($instance['title']) ? '&nbsp;' : apply_filters('widget_title', $instance['title']);

		echo $before_widget;
		echo $before_title . $title . $after_title;
		GeneralStatsComplete();
	    	echo $after_widget;
	}

	/*
	the backend-form with widget-title and settings-link
	*/

	function form($instance) {
		$title = attribute_escape($instance['title']);
		?>

		<p><label for="<?php echo $this->get_field_id('title'); ?>">
		<?php _e('Title:'); ?>

		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>

		<p><a href="options-general.php?page=<?php echo(plugin_basename(__FILE__)); ?>"><?php _e('Settings') ?></a></p>

		<?php
	}

	/*
	saves an updated title
	*/

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);

		return $instance;
	}

}

/*
database cleanup on uninstall
*/

function generalstats_uninstall() {
	delete_option('widget_generalstats');

	$fieldsPre="GeneralStats_";
	$sectionPost="_Section";
	$fieldsPost_Position="_Position";
	$fieldsPost_Description="_Description";

	$fields=array(0 => "Users", 1 => "Categories", 2 => "Posts", 3 => "Comments", 4 => "Pages", 5 => "Links", 6 => "Tags", 7 => "Link-Categories", 10 => "Words_in_Posts", 11 => "Words_in_Comments", 12 => "Words_in_Pages");

	$csstags=array("before_List", "after_List", "before_Tag", "after_Tag", "before_Details", "after_Details");

	$sections=array('Instructions' => '1', 'Static_Tags' => '1', 'CSS_Tags' => '1', 'Administrative_Options' => '1');

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

	delete_option($fieldsPre.'Use_Action_Hooks');

	delete_option($fieldsPre.'Integrate_Right_Now');

	delete_option($fieldsPre."Cache_Time");
	delete_option($fieldsPre."Rows_at_Once");

	delete_option($fieldsPre.'Use_Ajax_Refresh');
	delete_option($fieldsPre.'Refresh_Time');

	foreach ($sections as $key => $section) {
		delete_option($fieldsPre.$key.$sectionPost);
	}

}

?>