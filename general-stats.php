<?php

/*
Plugin Name: GeneralStats
Plugin URI: http://www.neotrinity.at/projects/
Description: Count the number of users, categories, posts, comments, pages, words in posts, words in comments and words in pages.
Author: Bernhard Riedl
Version: 0.21 (beta)
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

An example-configuration for a weblog with 27598 Users:

== Configuration-Page ==

= Tags =
 * Users:
   - Position: 1
   - Description: Users

= CSS-Tags =
 * before_List: <ul>
 * after_List: </ul>

 * before_Tag: <li><em>
 * after_Tag: </em>:

 * before_Details: <strong>
 * after_Details: </strong></li>

 * Thousand Delimiter: ,

== Output ==

<ul>
	<li>
		<em>Users</em>: <strong>27,598</strong>
	</li>
</ul>

*/


add_action('wp_head', 'generalstats_wp_head');

function generalstats_wp_head() {
  echo("<meta name=\"GeneralStats\" content=\"0.21\"/>");
}

/*
print stats as defined in the option page
*/

function GeneralStatsComplete() {

    /*
    get general tags
    */

    $before_list=stripslashes(get_option('before_List'));
    $after_list=stripslashes(get_option('after_List'));
    $before_tag=stripslashes(get_option('before_Tag'));
    $after_tag=stripslashes(get_option('after_Tag'));
    $before_detail=stripslashes(get_option('before_Details'));
    $after_detail=stripslashes(get_option('after_Details'));

    /*
    which order do you like today?
    */

    $orders=array();
    if (get_option('Users_Position')!="") $orders[0] = get_option('Users_Position');
    if (get_option('Categories_Position')!="") $orders[1] = get_option('Categories_Position');
    if (get_option('Posts_Position')!="") $orders[2] = get_option('Posts_Position');
    if (get_option('Comments_Position')!="") $orders[3] = get_option('Comments_Position');
    if (get_option('Pages_Position')!="") $orders[4] = get_option('Pages_Position');
    if (get_option('Words_in_Posts_Position')!="") $orders[10] = get_option('Words_in_Posts_Position');
    if (get_option('Words_in_Comments_Position')!="") $orders[11] = get_option('Words_in_Comments_Position');
    if (get_option('Words_in_Pages_Position')!="") $orders[12] = get_option('Words_in_Pages_Position');

    /*
    sort as wished
    */

    asort($orders);

    /*
    begin list
    */

    echo($before_list);

    /*
    loop through desired stats
    */

    foreach ($orders as $key => $order) {

        $count=GeneralStatsCounter($key);

        switch($key) {
          case 0;
              $tag=get_option('Users_Description');
              break;
          case 1;
              $tag=get_option('Categories_Description');
              break;
          case 2;
              $tag=get_option('Posts_Description');
              break;
          case 3;
              $tag=get_option('Comments_Description');
              break;
          case 4;
              $tag=get_option('Pages_Description');
              break;
          case 10;
              $tag=get_option('Words_in_Posts_Description');
              break;
          case 11;
              $tag=get_option('Words_in_Comments_Description');
              break;
          case 12;
              $tag=get_option('Words_in_Pages_Description');
              break;
        }

        $count=number_format($count,'0','',get_option('Thousand_Delimiter'));

        echo $before_tag.$tag.$after_tag.$before_detail.$count.$after_detail;

    }

    /*
    finish list
    */

    echo($after_list);

}

/*
GeneralStatsCounter
Param: $option
		0..users
		1..categories
		2..posts
		3..comments
		4..pages
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
        case 10:
        	$statement = "SELECT post_content FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'post'";
        	break;
        case 11:
                $statement = "SELECT comment_content FROM $wpdb->comments WHERE comment_approved = '1'";
        	break;
        case 12:
                $statement = "SELECT post_content FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'page'";
        	break;
      }

      $results = $wpdb->get_col($statement);

      /*
      for values between 10 and 20 is a calculation needed
      */

      if ($option>=10 && $option<=20) {
        	for ($i=0; $i<count($results); $i++) {
        		$result += str_word_count($results[$i]);
                }
      }

      else {
                $result=$results[0];
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

    $fields=array("Users", "Categories", "Posts", "Comments", "Pages", "Words_in_Posts", "Words_in_Comments", "Words_in_Pages");
    $csstags=array("before_List", "after_List", "before_Tag", "after_Tag", "before_Details", "after_Details");

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

        update_option('Thousand_Delimiter', $_POST['Thousand_Delimiter']);

        ?><div class="updated"><p><strong>
        <?php _e('Configuration changed!')?></strong></p></div>
     <?php }?>

     <?php
     /*
     options form
     */
    ?>

     <div class=wrap>
       <form method="post">
         <h2>Tags</h2>

     <?php

     foreach ($fields as $field) {
          echo("<fieldset name=\"".$field."\">");
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
          echo("<fieldset name=\"".$csstag."\">");
            echo("<legend>");
            _e($csstag);
            echo("</legend>");
              echo("<input type=\"text\" size=\"30\" name=\"".$csstag."\" value=\"".htmlspecialchars(stripslashes(get_option($csstag)))."\" />");
          echo("</fieldset>");
     }

     ?>

     <fieldset name="nice_numbers">
        <legend><?php _e('Thousand Delimiter') ?></legend>
            <input type="text" size="2" name="Thousand_Delimiter" value="<?php echo get_option('Thousand_Delimiter'); ?>" />
      </fieldset>

    <h2>Preview (call GeneralStatsComplete(); wherever you like!)</h2>
    <?php GeneralStatsComplete(); ?>

    <div class="submit">
      <input type="submit" name="info_update" value="<?php _e('Update options') ?>" /></div>
    </form>
    </div>

<?php
}

add_action('admin_menu', 'addGeneralStatsOptionPage');

?>