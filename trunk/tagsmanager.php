<?php
/*
Plugin Name: Tags Manager
Plugin URI: http://www.matusz.ch/blog/projekte/tags-manager-wordpress-plugin/
Feed URI: http://www.matusz.ch/blog/tags/tags-manager-wp-plugin/feed/
Description: This plugin helps you to manage your tags 
Version: 0.2 Beta
Author: Patrick Matusz
Author URI: http://www.matusz.ch/blog/
*/

define("key_tagman_tagorder", "tagman_tagorder", true);
define("tagman_tagorder_default", "count", true);

add_option(key_tagman_tagorder, tagman_tagorder_default, 'Defines sortorder of tag list!');


require_once("tagsmanager_helpfunctions.php");

add_action('admin_menu', 'add_tagman_option_page');

//Add Option Page
function add_tagman_option_page() {
	global $wpdb;
	add_management_page('Manage Tags', 'Tags', 8, basename(__FILE__), 'tagman_options_page');
}

function tagman_options_page() {
	global $wpdb;
	$success_messages = array();
	$error_messages = array();
	
	$edittagid = intval(tagman_geturl_param("edit_tag"));
	$actiontype = tagman_geturl_param("actiontype");

	//Functions
	if (tagman_isseturl_param("convert2lower")) {
		if (tagman_convert_2_lower()) {
			$success_messages[] = "Successfully converted to lowercase!";
		} else {
			$error_messages[] = "Error in converting to lowercase!";
		}
	}
	if (tagman_isseturl_param("tagman_renamenow")) {		
		if (tagman_tag_rename(intval(tagman_geturl_param("tagman_id")),tagman_geturl_param("tagman_rename"))) {
			$success_messages[] = "Renaming successfully!";
		} else {
			$edittagid = intval(tagman_geturl_param("tagman_id"));
			$error_messages[] = "Error in renaming - another tag with this name already exists. Use merge instead!";			
		}
	}
	if (tagman_isseturl_param("tagman_deletenow")) {
		if (tagman_tag_delete(intval(tagman_geturl_param("tagman_id")))) {
			$success_messages[] = "Deleted successfully!";
		} else {
			$edittagid = intval(tagman_geturl_param("tagman_id"));
			$error_messages[] = "Error in deleting!";			
		}
	}
	if (tagman_isseturl_param("tagman_mergenow")) {
		if (tagman_tag_merge(intval(tagman_geturl_param("tagman_id")),intval(tagman_geturl_param("tagman_merge")))) {
			$success_messages[] = "Merging successfully!";
		} else {
			$edittagid = intval(tagman_geturl_param("tagman_id"));
			$error_messages[] = "Error in merging!";			
		}
	}
	if (tagman_isseturl_param("tagman_postaddnow")) {
		if (tagman_tag_addpost(intval(tagman_geturl_param("tagman_id")),intval(tagman_geturl_param("tagman_postadd")))) {
			$success_messages[] = "Added successfully!";
		} else {			
			$error_messages[] = "Error in adding!";			
		}
		$edittagid = intval(tagman_geturl_param("tagman_id"));
	}
	
	switch (strtolower($actiontype)) {
		case "removepost":
			if (tagman_tag_removepost(intval(tagman_geturl_param("tagman_id")),intval(tagman_geturl_param("tagman_postid")))) {
				$success_messages[] = "Removed successfully!";
			} else {
				$error_messages[] = "Error in removing!";			
			}			
			$edittagid = intval(tagman_geturl_param("tagman_id"));
			break;
	}
	
	
	//Messages
	if (count($success_messages)>0) {
		foreach ($success_messages as $msg) {
			echo "<div id='message' class='updated fade'><p>$msg</p></div>";
		}
	}
	if (count($error_messages)>0) {
		foreach ($error_messages as $msg) {
			echo "<div id='message' class='error fade'><p>$msg</p></div>";
		}
	}
	
	//Content
	if ($edittagid != 0) {
		?>
		<form method="post" action="edit.php?page=tagsmanager.php">
			<div class="wrap">
				<?php tagman_option_gettagform($edittagid); ?>
				<?php tagman_option_gettagposts($edittagid); ?>
			</div>
		</form>		
		<?php	
	} else {
		?>
		<form method="post" action="edit.php?page=tagsmanager.php">
			<div class="wrap">
				<h2>Posts without Tags</h2>
				<?php tagman_option_getnewestposts(3); ?>
				<p>&nbsp;</p>		
				<h2>Manage Tags</h2>
				<?php tagman_option_gettags(); ?>
				<p>&nbsp;</p>
				<h2>Helpfunctions</h2>
				<p class="submit">
					<input type='submit' name='convert2lower' value='Convert tag names to lowercase' />				
				</p>								
			</div>
		</form>
		<?php
	}
}

?>