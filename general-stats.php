<?php

/*
Plugin Name: GeneralStats
Plugin URI: http://www.neotrinity.at/projects/
Description: Counts the number of users, categories, posts, comments, pages, links, words in posts, words in comments and words in pages. - Find the options <a href="options-general.php?page=generalstats/general-stats.php">here</a>!
Author: Bernhard Riedl
Version: 0.40 (beta)
Author URI: http://www.neotrinity.at
*/

/*  Copyright 2006-2007  Bernhard Riedl  (email : neo@neotrinity.at)

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
	add_action('wp_head', 'generalstats_wp_head');
	add_action('admin_menu', 'addGeneralStatsOptionPage');
}

/*
called from widget_init hook
*/

function widget_generalstats_init() {
	register_sidebar_widget(array('GeneralStats', 'widgets'), 'widget_generalstats');
	register_widget_control(array('GeneralStats', 'widgets'), 'widget_generalstats_control', 300, 100);
}

/*
adds metainformation - please leave this for stats!
*/

function generalstats_wp_head() {
  echo("<meta name=\"GeneralStats\" content=\"0.40\"/>");
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

	// Get our options and see if we're handling a form submission.
	$options = get_option('widget_generalstats');
	if ( !is_array($options) )
		$options = array('title'=>'', 'buttontext'=>__('GeneralStats', 'widgets'));
		if ( $_POST['generalstats-submit'] ) {
			$options['title'] = strip_tags(stripslashes($_POST['generalstats-title']));
			update_option('widget_generalstats', $options);
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		
		echo '<p style="text-align:right;"><label for="generalstats-title">' . __('Title:') . ' <input style="width: 200px;" id="generalstats-title" name="generalstats-title" type="text" value="'.$title.'" /></label></p>';
		echo '<input type="hidden" id="generalstats-submit" name="generalstats-submit" value="1" />';
		echo '<p style="text-align:left;"><label for="generalstats-options">Find the options <a href="options-general.php?page=generalstats/general-stats.php">here</a>!</label></p>';
	}

/*
echoes stats as defined in the option page
handles the cache-management
*/

function GeneralStatsComplete() {

	$cacheTime = get_option('GeneralStats_Cache_Time');
	$lastCacheTime = get_option('GeneralStats_Last_Cache_Time');

	$cacheAge = time() - $lastCacheTime;

	$cache = get_option('GeneralStats_Cache');
	$forceCacheRefresh = get_option('GeneralStats_Force_Cache_Refresh');

	//the cache is refreshed if cache refreshing is forced, the cache is empty
	//or the age of the cache is older then the defined caching time

	if ( ($forceCacheRefresh > 0) ||
	     (strlen($cache) < 1) ||
	     ($cacheAge>$cacheTime) ) {

		update_option('GeneralStats_Cache', GeneralStatsCreateOutput());
		update_option('GeneralStats_Force_Cache_Refresh','0');
		update_option('GeneralStats_Last_Cache_Time',time());
	}

	echo get_option('GeneralStats_Cache');

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

    $before_list=stripslashes(get_option('GeneralStats_before_List'));
    $after_list=stripslashes(get_option('GeneralStats_after_List'));
    $before_tag=stripslashes(get_option('GeneralStats_before_Tag'));
    $after_tag=stripslashes(get_option('GeneralStats_after_Tag'));
    $before_detail=stripslashes(get_option('GeneralStats_before_Details'));
    $after_detail=stripslashes(get_option('GeneralStats_after_Details'));

    /*
    which order do you like today?
    */

    $orders=array();
    if (get_option('GeneralStats_Users_Position')!="") $orders[0] = get_option('GeneralStats_Users_Position');
    if (get_option('GeneralStats_Categories_Position')!="") $orders[1] = get_option('GeneralStats_Categories_Position');
    if (get_option('GeneralStats_Posts_Position')!="") $orders[2] = get_option('GeneralStats_Posts_Position');
    if (get_option('GeneralStats_Comments_Position')!="") $orders[3] = get_option('GeneralStats_Comments_Position');
    if (get_option('GeneralStats_Pages_Position')!="") $orders[4] = get_option('GeneralStats_Pages_Position');
    if (get_option('GeneralStats_Links_Position')!="") $orders[5] = get_option('GeneralStats_Links_Position');
    if (get_option('GeneralStats_Words_in_Posts_Position')!="") $orders[10] = get_option('GeneralStats_Words_in_Posts_Position');
    if (get_option('GeneralStats_Words_in_Comments_Position')!="") $orders[11] = get_option('GeneralStats_Words_in_Comments_Position');
    if (get_option('GeneralStats_Words_in_Pages_Position')!="") $orders[12] = get_option('GeneralStats_Words_in_Pages_Position');

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

    foreach ($orders as $key => $order) {

        $count=GeneralStatsCounter($key);

        switch($key) {
          case 0;
              $tag=get_option('GeneralStats_Users_Description');
              break;
          case 1;
              $tag=get_option('GeneralStats_Categories_Description');
              break;
          case 2;
              $tag=get_option('GeneralStats_Posts_Description');
              break;
          case 3;
              $tag=get_option('GeneralStats_Comments_Description');
              break;
          case 4;
              $tag=get_option('GeneralStats_Pages_Description');
              break;
          case 5;
              $tag=get_option('GeneralStats_Links_Description');
              break;
          case 10;
              $tag=get_option('GeneralStats_Words_in_Posts_Description');
              break;
          case 11;
              $tag=get_option('GeneralStats_Words_in_Comments_Description');
              break;
          case 12;
              $tag=get_option('GeneralStats_Words_in_Pages_Description');
              break;
        }

        $count=number_format($count,'0','',get_option('GeneralStats_Thousand_Delimiter'));

        $ret.= $before_tag.$tag.$after_tag.$before_detail.$count.$after_detail;

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
		10..words in posts
		11..words in comments
		12..words in pages
*/

function GeneralStatsCounter($option) {
      global $wpdb;

      $statement='';
      $result=0;

      switch ($option) {
        case 0:
        	$statement = "SELECT COUNT(ID) as counter FROM $wpdb->users";
	        break;
        case 1:
          	$statement = "SELECT COUNT(cat_ID) as counter FROM $wpdb->categories";
        	break;
        case 2:
        	$statement = "SELECT COUNT(ID) as counter FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post'";
	        break;
        case 3:
                $statement = "SELECT COUNT(comment_ID) as counter FROM $wpdb->comments WHERE comment_approved = '1'";
        	break;
        case 4:
                $statement = "SELECT COUNT(ID) as counter FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page'";
        	break;
        case 5:
                $statement = "SELECT COUNT(link_id) as counter FROM $wpdb->links WHERE link_visible = 'Y'";
        	break;
        case 10:
        	    $statement = "FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post'";
		    $attribute = "post_content";
		    $countAttribute = "ID";
        	break;
        case 11:
                $statement = "FROM $wpdb->comments WHERE comment_approved = '1'";
		    $attribute = "comment_content";
		    $countAttribute = "comment_ID";
        	break;
        case 12:
                $statement = "FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page'";
		    $attribute = "post_content";
		    $countAttribute = "ID";
        	break;
      }


      /*
      for values between 10 and 20 is a calculation needed
      */

      if ($option>=10 && $option<=20) {
		$result=GeneralStats_WordCount($statement, $attribute, $countAttribute);
      }

      else {
	      $results = $wpdb->get_col($statement);
		$result=$results[0];
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
add GeneralStats to WordPress Option Page
*/

function addGeneralStatsOptionPage() {
    if (function_exists('add_options_page')) {
        add_options_page('GeneralStats', 'GeneralStats', 8, __FILE__, 'createGeneralStatsOptionPage');
    }
}

/*
Option Page
*/

function createGeneralStatsOptionPage() {

    $fields=array("GeneralStats_Users", "GeneralStats_Categories", "GeneralStats_Posts", "GeneralStats_Comments", "GeneralStats_Pages", "GeneralStats_Links", "GeneralStats_Words_in_Posts", "GeneralStats_Words_in_Comments", "GeneralStats_Words_in_Pages");
    $csstags=array("GeneralStats_before_List", "GeneralStats_after_List", "GeneralStats_before_Tag", "GeneralStats_after_Tag", "GeneralStats_before_Details", "GeneralStats_after_Details");

    $fields_position_defaults=array("1", "", "2", "3", "4", "", "", "", "");
    $fields_description_defaults=array("Users", "", "Posts", "Comments", "Pages", "", "", "", "");
    $csstags_defaults=array("<ul>", "</ul>", "<li><em>", "</em>&nbsp;", "", "</li>");

    /*
    configuration changed => store parameters
    */

    if (isset($_POST['info_update'])) {

        foreach ($fields as $field) {
            update_option($field."_Position", $_POST[$field."_Position"]);
            update_option($field."_Description", $_POST[$field."_Description"]);
        }

        foreach ($csstags as $csstag) {
            update_option($csstag, $_POST[$csstag]);
        }

        update_option('GeneralStats_Thousand_Delimiter', $_POST['GeneralStats_Thousand_Delimiter']);

        update_option('GeneralStats_Cache_Time', $_POST['GeneralStats_Cache_Time']);
        update_option('GeneralStats_Rows_at_Once', $_POST['GeneralStats_Rows_at_Once']);

        ?><div class="updated"><p><strong>
        <?php _e('Configuration changed!<br />Cache refreshed!')?><?php GeneralStatsForceCacheRefresh(); ?></strong></p></div>

      <?php }

      elseif (isset($_POST['load_default'])) {

        for ($i = 0; $i < sizeof($csstags); $i++) {
            update_option($csstags[$i], $csstags_defaults[$i]);
        }

        for ($i = 0; $i < sizeof($fields); $i++) {
            update_option($fields[$i]."_Position", $fields_position_defaults[$i]);
        }

        for ($i = 0; $i < sizeof($fields); $i++) {
            update_option($fields[$i]."_Description", $fields_description_defaults[$i]);
        }

	  update_option('GeneralStats_Thousand_Delimiter', ',');

	  update_option('GeneralStats_Cache_Time', '600');
	  update_option('GeneralStats_Rows_at_Once', '100');

        ?><div class="updated"><p><strong>
        <?php _e('Defaults loaded!<br />Cache refreshed!')?><?php GeneralStatsForceCacheRefresh(); ?></strong></p></div>

      <?php } ?>

     <?php
     /*
     options form
     */
     ?>

     <div class="wrap">
       <form method="post">
         <h2>Tags</h2>

     <?php

     foreach ($fields as $field) {
          echo("<fieldset>");
            echo("<legend>");
            _e($field);
            echo("</legend>");
              echo("Position <input type=\"text\" size=\"2\" name=\"".$field."_Position\" value=\"".get_option($field.'_Position')."\" />\n");
              echo("Description <input type=\"text\" size=\"20\" name=\"".$field."_Description\" value=\"".get_option($field.'_Description')."\" />");
          echo("</fieldset>");
     }

     ?>

        <h2>CSS-Tags</h2>

     <?php

     foreach ($csstags as $csstag) {
          echo("<fieldset>");
            echo("<legend>");
            _e($csstag);
            echo("</legend>");
              echo("<input type=\"text\" size=\"30\" name=\"".$csstag."\" value=\"".htmlspecialchars(stripslashes(get_option($csstag)))."\" />");
          echo("</fieldset>");
     }

     ?>

     <fieldset>
        <legend><?php _e('GeneralStats_Thousand Delimiter') ?></legend>
            <input type="text" size="2" name="GeneralStats_Thousand_Delimiter" value="<?php echo get_option('GeneralStats_Thousand_Delimiter'); ?>" />
      </fieldset>

        <h2>Administrative Options</h2>

     <fieldset>
        <legend><?php _e('GeneralStats_Cache Time (in seconds)') ?></legend>
            <input type="text" size="2" name="GeneralStats_Cache_Time" value="<?php echo get_option('GeneralStats_Cache_Time'); ?>" />
      </fieldset>

     <fieldset>
        <legend><?php _e('GeneralStats_Rows at Once (this option effects the Words_in_* attributes: higher value = increased memory usage, but better performing)') ?></legend>
            <input type="text" size="2" name="GeneralStats_Rows_at_Once" value="<?php echo get_option('GeneralStats_Rows_at_Once'); ?>" />
      </fieldset>

    <h2>Preview (call GeneralStatsComplete(); wherever you like!)</h2>
    <?php GeneralStatsComplete(); ?>

    <div class="submit">
      <input type="submit" name="info_update" value="<?php _e('Update options') ?>" />
      <input type="submit" name="load_default" value="<?php _e('Load defaults') ?>" />
    </div>

    </form>
    </div>

<?php
}

add_action('init', 'generalstats_init');
add_action('widgets_init', 'widget_generalstats_init');

?>