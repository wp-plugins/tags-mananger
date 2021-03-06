<?php

function tagman_post_save($postid, $tagids) {
	global $wpdb;
	$sql_terms = "SELECT a.term_id, a.name, b.term_taxonomy_id, b.count, a.slug FROM $wpdb->terms a, $wpdb->term_taxonomy b, $wpdb->term_relationships c WHERE b.term_taxonomy_id = c.term_taxonomy_id AND a.term_id=b.term_id AND b.taxonomy='post_tag' AND c.object_id=$postid ORDER BY a.name";
	$res_terms = $wpdb->get_results($sql_terms);
	foreach ($res_terms as $row_term) {
		if (tagman_is_post_tag_only($row_term->term_id)) {
			tagman_tag_removepost($row_term->term_taxonomy_id,$postid);
		}
	}
	foreach ($tagids as $id) {
		if (is_numeric($id)) {
			tagman_tag_addpost($id,$postid);
		} else {			
			$newslug = $wpdb->escape(sanitize_title($id));
			$newvalue = $wpdb->escape($id);				
			$terminsert = "INSERT INTO $wpdb->terms (name, slug) VALUES ('$newvalue', '$newslug')";
			$wpdb->query($terminsert);
			$term_id= $wpdb->insert_id;
			$term_taxonomyinsert = "INSERT INTO $wpdb->term_taxonomy (term_id, taxonomy, description) VALUES ($term_id, 'post_tag', '')";
			$wpdb->query($term_taxonomyinsert);
			$term_taxonomy_id = $wpdb->insert_id;
			tagman_tag_addpost($term_taxonomy_id,$postid);
		}
	}
	return true;
}

function tagman_option_getpostform($id) {
	global $wpdb;
	$alltags = '<select multiple="multiple" id="alltags" name="tagman_alltags[]" size="20" style="width: 250px;" >';
	$sql_terms = "SELECT a.term_id, a.name, b.term_taxonomy_id, b.count, a.slug FROM $wpdb->terms a, $wpdb->term_taxonomy b WHERE a.term_id=b.term_id AND b.taxonomy='post_tag' ORDER BY a.name";
	$res_terms = $wpdb->get_results($sql_terms);
	foreach ($res_terms as $row_term) {
		if (tagman_is_post_tag_only($row_term->term_id)) {
			$alltags .= '<option value="'.$row_term->term_taxonomy_id.'">'.$row_term->name.'</option>';
		}
	}
	$alltags .= '</select>';

	$posttags = '<select multiple="multiple" id="posttags" name="tagman_posttags[]" size="20" style="width: 250px;" >';
	$sql_terms = "SELECT a.term_id, a.name, b.term_taxonomy_id, b.count, a.slug FROM $wpdb->terms a, $wpdb->term_taxonomy b, $wpdb->term_relationships c WHERE b.term_taxonomy_id = c.term_taxonomy_id AND a.term_id=b.term_id AND b.taxonomy='post_tag' AND c.object_id=$id ORDER BY a.name";
	$res_terms = $wpdb->get_results($sql_terms);
	foreach ($res_terms as $row_term) {
		if (tagman_is_post_tag_only($row_term->term_id)) {
			$posttags .= '<option value="'.$row_term->term_taxonomy_id.'">'.$row_term->name.'</option>';
		}
	}
	$posttags .= '</select>';	
	
	$sql_posts = "SELECT ID, post_title, post_author, post_date, post_status FROM $wpdb->posts WHERE ID=$id AND post_type='post' ORDER BY post_title, post_date DESC";
	$res_posts = $wpdb->get_results($sql_posts);
	foreach($res_posts as $row_post) {
		$sql_tagcount = "SELECT COUNT(*) AS TagCount FROM $wpdb->term_relationships a, $wpdb->term_taxonomy b WHERE b.taxonomy='post_tag' AND a.term_taxonomy_id=b.term_taxonomy_id AND object_id=$row_post->ID";
		$res_tagcount = $wpdb->get_var($sql_tagcount);
		$title = str_replace('"','&quot;',$row_post->post_title);		
		if ($row_post->post_status=="publish") {
			$displaytime = mysql2date(__('Y-m-d g:i:s a'), $row_post->post_date);	 
		} else {
			$displaytime = $row_post->post_status;
		}						
		
	?>
		<script type="text/javascript">
			function add() {
				var alltags = document.getElementById("alltags");
				var posttags = document.getElementById("posttags");
				var tagexist = false;
				for (var i=0; i<alltags.length; i++) {
					if (alltags.options[i].selected == true) {
						tagexist = false;
						for (var j=0; j<posttags.length; j++) {
							if (alltags.options[i].value==posttags.options[j].value) {
								tagexist = true;
								break;
							}
						}
						if (tagexist == false) {
							posttags.options[posttags.length] = new Option(alltags.options[i].text, alltags.options[i].value, false, false);																											
						}
					}
				}
			}

			function remove(all) {
				var posttags = document.getElementById("posttags");
				var tagexist = false;
				for (var i=posttags.length-1; i>=0; i--) {												
					if (all || posttags.options[i].selected == true) {
						posttags.options[i] = null;
					}
				}
			}
			
			function selectAll() {
				var posttags = document.getElementById("posttags");						
				for (var i=posttags.length-1; i>=0; i--) {												
					posttags.options[i].selected=true;
				}				
			}
			
			function addNew() {
				var alltags = document.getElementById("alltags");
				var posttags = document.getElementById("posttags");			
				var newValue = prompt("Enter your new tag:","");
				if (newValue.length > 0) {
					var tagexist = false;
					for (var i=0; i<posttags.length; i++) {
						if (posttags.options[i].text.toLowerCase()==newValue.toLowerCase()) {
							tagexist=true;
							break;
						}	
					}
					
					if (tagexist == false) {					
						for (var j=0; j<alltags.length; j++) {
							if (alltags.options[j].text.toLowerCase()==newValue.toLowerCase()) {
								alltags.selectedIndex=j;
								add();
								tagexist=true;
								break;
							}						
						}
					} else {
						alert("Tag already exists!");
					}
											
					if (tagexist == false) {
						posttags.options[posttags.length] = new Option(newValue, newValue, false, false);
					}
				}
			}
			
		</script>		
		<form method="post" action="edit.php?page=tagsmanager.php" onsubmit="selectAll()">
			<div class="wrap">	
				<h2>Edit Post</h2>
				<table class="editform" cellspacing="2" cellpadding="5" width="100%" id="editform">
					<tr>
						<th valign="top" style="padding-top: 10px;" width="30%">
							Post ID / Count:
						</th>
						<td width="70%" colspan="2">
							<input type="text" size="5" readonly="readonly" name="tagman_ro_postid" style="text-align: center;" value="<?php echo $row_post->ID; ?>"> / <input type="text" size="5" readonly="readonly" name="tagman_postcount" style="text-align: center" value="<?php echo $res_tagcount; ?>">
							<input type="hidden" name="tagman_postid" value="<?php echo $row_post->ID; ?>">							
						</td>
					</tr>	
					<tr>
						<th valign="top" style="padding-top: 10px;" width="30%">
							Post Title:
						</th>
						<td width="70%" colspan="2">
							<input type="text" size="80" readonly="readonly" name="tagman_posttitle" value="<?php echo $title; ?>">							
						</td>
					</tr>
					<tr>
						<th valign="top" style="padding-top: 10px;" width="30%">
							Post Date:
						</th>
						<td width="70%" colspan="2">
							<input type="text" size="80" readonly="readonly" name="tagman_postdate" value="<?php echo $displaytime; ?>">							
						</td>
					</tr>
					<tr>
						<th valign="top" style="padding-top: 10px;" width="30%">
							Post Tags:
						</th>
						<td width="70%" colspan="2">
							<table cellpadding="0" cellspacing="0" border="0">
								<tr>
									<td valign="top">								
										<?php echo $posttags; ?>
									</td>									
									<td valign="top">						
										<input type="button" value="&lt;" onclick="add()" style="width: 25px;" /><br />
										<input type="button" value="&gt;" onclick="remove(false)" style="width: 25px;"  /><br />										
										<input type="button" value="»" onclick="remove(true)" style="width: 25px;"  /><br />										
										<input type="button" value="*" onclick="addNew()" style="width: 25px;"  />
									</td>
									<td valign="top">								
										<?php echo $alltags; ?>
									</td>
								</tr>
							</table>							
						</td>
					</tr>	
					<tr>
						<td colspan="2"><p class="submit"><input type="hidden" name="actiontype" value="editpost" /><input type='submit' name='tagman_postsave' value='Save Post' /></p></td>														
					</tr>				
				</table>
			</div>
		</form>								
	<?
	}
}

function tagman_option_postlist() {
	global $wpdb;
	$sql_posts = "SELECT ID, post_title, post_author, post_date, post_status FROM $wpdb->posts WHERE post_type='post' ORDER BY post_title, post_date DESC";
	$res_posts = $wpdb->get_results($sql_posts);
	$editpost = ""; 
	foreach($res_posts as $row_post) {
		$sql_tagcount = "SELECT COUNT(*) AS TagCount FROM $wpdb->term_relationships a, $wpdb->term_taxonomy b WHERE b.taxonomy='post_tag' AND a.term_taxonomy_id=b.term_taxonomy_id AND object_id=$row_post->ID";
		$res_tagcount = $wpdb->get_var($sql_tagcount);		
		if ( current_user_can('edit_post',$row_post->post_author) ) {
			if ($row_post->post_status=="publish") {
				$displaytime = mysql2date(__('Y-m-d g:i:s a'), $row_post->post_date);	 
			} else {
				$displaytime = $row_post->post_status;
			}						
			$editpost .= '<option value="'.$row_post->ID.'">'.$row_post->post_title.' ('.$res_tagcount.' / '.$displaytime.')</option>';
		}					
	}
	if (strlen($editpost)>0) {
		echo '<p class="submit"><select name="edit_post" size="1" style="width:100%;">';
		echo $editpost;
		echo '</select><br /><input type="submit" name="edit_postnow" value="Edit Now" /></td></tr>';
	}	
}

function tagman_tag_addpost($id, $postid) {
	global $wpdb;
	tagman_tag_removepost($id, $postid);
	$sql = "INSERT INTO $wpdb->term_relationships (term_taxonomy_id, object_id) VALUES ($id, $postid)";
	$wpdb->query($sql);
	$sql_count = "SELECT COUNT(*) FROM $wpdb->term_relationships a, $wpdb->posts b WHERE a.term_taxonomy_id=$id AND a.object_id=b.ID AND b.post_status='publish' AND b.post_type='post'";
	$res_count = $wpdb->get_var($sql_count);
	$sql = "UPDATE $wpdb->term_taxonomy SET count=$res_count WHERE term_taxonomy_id=$id";
	$wpdb->query($sql);	
	return true;
}

function tagman_tag_removepost($id, $postid) {
	global $wpdb;
	$sql = "DELETE FROM $wpdb->term_relationships WHERE term_taxonomy_id=$id AND object_id=$postid";
	$wpdb->query($sql);
	$sql_count = "SELECT COUNT(*) FROM $wpdb->term_relationships a, $wpdb->posts b WHERE a.term_taxonomy_id=$id AND a.object_id=b.ID AND b.post_status='publish' AND b.post_type='post'";
	$res_count = $wpdb->get_var($sql_count);
	$sql = "UPDATE $wpdb->term_taxonomy SET count=$res_count WHERE term_taxonomy_id=$id";
	$wpdb->query($sql);	
	return true;
}

function tagman_option_gettagposts($id) {
	global $wpdb;
	$sql_posts = "SELECT ID, post_title, post_author, post_date, post_status FROM $wpdb->posts WHERE post_type='post' ORDER BY post_title, post_date DESC";
	$res_posts = $wpdb->get_results($sql_posts); 
	?>
	<h3>Posts</h3>
	<table class="widefat" style="border: 1px solid #ccc;">
		<thead>
			<tr>
				<th scope="col"><div style="text-align: center;"><?php echo __('ID'); ?></div></th>
				<th scope="col"><?php echo __('When'); ?></th>
				<th scope="col"><?php echo __('Title'); ?></th>
				<th scope="col"><?php echo __('Author'); ?></th>
				<th scope="col"><?php echo __('Edit'); ?></th>
				<th scope="col">Edit tags</th>
				<th scope="col">Remove</th>
			</tr>
		</thead>
		<tbody id="the-list">		
			<?php
				$count_posts=0;	
				$class = '';
				$addpost = "";
				foreach($res_posts as $row_post) {
					$sql_tagcount = "SELECT COUNT(*) AS TagCount FROM $wpdb->term_relationships a, $wpdb->term_taxonomy b WHERE b.taxonomy='post_tag' AND a.term_taxonomy_id=b.term_taxonomy_id AND object_id=$row_post->ID AND b.term_taxonomy_id=$id";
					$res_tagcount = $wpdb->get_var($sql_tagcount);
					if ($res_tagcount>0) {
						if ($row_post->post_status=="publish") {
							$displaytime = mysql2date(__('Y-m-d \<\b\r \/\> g:i:s a'), $row_post->post_date);	 
						} else {
							$displaytime = $row_post->post_status;
						}											
						
						$count_posts++;
						$class = ('alternate' == $class) ? '' : 'alternate';
						$author = get_userdata($row_post->post_author);
						echo '<tr id="post-" class="'.$class.'"><td><div style="text-align: center;">'.$row_post->ID.'</div></td><td>'.$displaytime.'</td><td>'.$row_post->post_title.'</td><td>'.$author->display_name.'</td>';
						echo '<td>';
						if ( current_user_can('edit_post',$row_post->post_author) ) { 
							echo '<a href="post.php?action=edit&amp;post='.$row_post->ID.'" class="edit">'. __('Edit').'</a>'; 
						} 												
						echo '</td><td>';
						if ( current_user_can('edit_post',$row_post->post_author) ) { 
							echo '<a href="edit.php?page=tagsmanager.php&edit_post='.$row_post->ID.'" class="edit">Edit tags</a>';
						} 						
						echo '</td><td>';	
						if ( current_user_can('edit_post',$row_post->post_author) ) {					
							echo '<a href="edit.php?page=tagsmanager.php&tagman_id='.$id.'&tagman_postid='.$row_post->ID.'&actiontype=removepost" class="edit">Remove</a>';
						}
						echo '</td></tr>';				
					} else {
						if ($row_post->post_status=="publish") {
							$displaytime = mysql2date(__('Y-m-d g:i:s a'), $row_post->post_date);	 
						} else {
							$displaytime = $row_post->post_status;
						}										
						if ( current_user_can('edit_post',$row_post->post_author) ) {
							$addpost .= '<option value="'.$row_post->ID.'">'.$row_post->post_title.' ('.$displaytime.')</option>';
						}			
					}
				}			
				if (strlen($addpost)>0) {
					$class = ('alternate' == $class) ? '' : 'alternate';
					echo '<tr class="'.$class.'"><th colspan="2">Add Post</th><td colspan="4"><select name="tagman_postadd" size="1" style="width:100%;">';
					echo $addpost;
					echo '</select><br /><input type="submit" name="tagman_postaddnow" value="Add Now" /></td></tr>';
				}	
			?>	
		</tbody>
	</table>	
	<?php
}


function tagman_tag_merge($oldid, $newid) {
	global $wpdb;
	$sql_old = "SELECT term_taxonomy_id, object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id=$oldid";
	$res_old = $wpdb->get_results($sql_old);
	foreach ($res_old AS $row_old) {
		$objectid = $row_old->object_id;
		$sql_count = "SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id=$newid AND object_id=$objectid";
		$res_count = $wpdb->get_var($sql_count);
		if ($res_count == 0) {
			$sql = "INSERT INTO $wpdb->term_relationships (term_taxonomy_id, object_id) VALUES ($newid, $objectid)";
			$wpdb->query($sql);
		}
	}
	$sql_count = "SELECT COUNT(*) FROM $wpdb->term_relationships a, $wpdb->posts b WHERE a.term_taxonomy_id=$newid AND a.object_id=b.ID AND b.post_status='publish' AND b.post_type='post'";
	$res_count = $wpdb->get_var($sql_count);
	$sql = "UPDATE $wpdb->term_taxonomy SET count=$res_count WHERE term_taxonomy_id=$newid";
	$wpdb->query($sql);
	return tagman_tag_delete($oldid);
}


function tagman_tag_rename($id, $newvalue) {
	global $wpdb;
	$newslug = $wpdb->escape(sanitize_title($newvalue));
	$newvalue = $wpdb->escape($newvalue);	
	$sql_count = "SELECT COUNT(*) FROM $wpdb->terms a, $wpdb->term_taxonomy b WHERE a.term_id=b.term_id AND b.taxonomy='post_tag' AND b.term_taxonomy_id <> $id AND (a.name LIKE '$newvalue' OR a.slug='$newslug')";
	$res_count = $wpdb->get_var($sql_count);
	if ($res_count > 0) {
		return false;		
	} else {
		$sql = "UPDATE $wpdb->terms a, $wpdb->term_taxonomy b SET a.name='$newvalue', a.slug='$newslug' WHERE a.term_id=b.term_id AND b.taxonomy='post_tag' AND b.term_taxonomy_id=$id";
		$wpdb->query($sql);
		return true;
	}
}

function tagman_tag_delete($id) {
	global $wpdb;
	$sql = "SELECT term_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id=$id";
	$res = $wpdb->get_results($sql);
	foreach ($res as $row) {
		$sql = "DELETE FROM $wpdb->terms WHERE term_id = $row->term_id";
		$wpdb->query($sql);
	}
	$sql = "DELETE FROM $wpdb->term_taxonomy WHERE term_taxonomy_id=$id";
	$wpdb->query($sql);
	$sql = "DELETE FROM $wpdb->term_relationships WHERE term_taxonomy_id=$id";
	$wpdb->query($sql);	
	return true;
}

function tagman_option_gettagform($id) {
	global $wpdb;
	$sql_terms = "SELECT a.term_id, a.name, b.term_taxonomy_id, b.count, a.slug FROM $wpdb->terms a, $wpdb->term_taxonomy b WHERE a.term_id=b.term_id AND b.taxonomy='post_tag' AND b.term_taxonomy_id=$id";
	$res_terms = $wpdb->get_results($sql_terms);
	foreach ($res_terms AS $row_term)
	{
		?>
			<h2>Edit Tag</h2>
				<table class="editform" cellspacing="2" cellpadding="5" width="100%">
					<tr>
						<th valign="top" style="padding-top: 10px;" width="30%">
							Tag ID / Count:
						</th>
						<td width="70%" colspan="2">
							<input type="text" size="5" readonly="readonly" name="tagman_ro_id" style="text-align: center;" value="<?php echo $row_term->term_taxonomy_id; ?>"> / <input type="text" size="5" readonly="readonly" name="tagman_count" style="text-align: center" value="<?php echo $row_term->count; ?>">
							<input type="hidden" name="tagman_id" value="<?php echo $row_term->term_taxonomy_id; ?>">							
						</td>
					</tr>	
					<tr>
						<th valign="top" style="padding-top: 10px;" width="30%">
							Tag Name:
						</th>
						<td width="70%" colspan="2">
							<input type="text" size="40" readonly="readonly" name="tagman_ro_name" value="<?php echo $row_term->name; ?>">							
							<input type="hidden" name="tagman_name" value="<?php echo $row_term->name; ?>">
						</td>
					</tr>
					<tr>
						<th valign="top" style="padding-top: 10px;" width="30%">
							Tag Slug:
						</th>
						<td width="70%" colspan="2">
							<input type="text" size="40" readonly="readonly" name="tagman_ro_slog" value="<?php echo $row_term->slug; ?>">							
							<input type="hidden" name="tagman_slug" value="<?php echo $row_term->slug; ?>">
						</td>
					</tr>
					<tr>
						<th valign="top" style="padding-top: 10px;" width="30%">
							Rename:
						</th>
						<td width="20%">
							<input type="text" size="40" name="tagman_rename" value="<?php echo $row_term->name; ?>">																			
						</td>
						<td width="50%">
							<input type="submit" name="tagman_renamenow" value="Rename Now" />
						</td>
					</tr>										
					<tr>
						<th valign="top" style="padding-top: 10px;" width="30%">
							Merge to:
						</th>
						<td width="20%">
							<select name="tagman_merge" size="1">
							<?php
								$sql_mergeterms = "SELECT a.term_id, a.name, b.term_taxonomy_id, b.count FROM $wpdb->terms a, $wpdb->term_taxonomy b WHERE a.term_id=b.term_id AND b.taxonomy='post_tag' AND b.term_taxonomy_id<>$row_term->term_taxonomy_id ORDER BY a.name";
								$res_mergeterms = $wpdb->get_results($sql_mergeterms);
								foreach ($res_mergeterms as $row_mergeterm) {
									echo '<option value="'.$row_mergeterm->term_taxonomy_id.'">'.$row_mergeterm->name.' ('.$row_mergeterm->count.')</option>';
								}
							?>
							</select>
						</td>
						<td width="50%">
							<input type="submit" name="tagman_mergenow" value="Merge Now" />												
						</td>
					</tr>					
					<tr>
						<th valign="top" style="padding-top: 10px;" width="30%">
							Delete:
						</th>
						<td width="20%">
							Do you really want to delete this tag?
						</td>					
						<td width="50%">
							</select><input type="submit" name="tagman_deletenow" value="Delete Now" />												
						</td>
					</tr>					
				</table>								
		<?php
	}
	
}

function tagman_option_gettags() {
	global $wpdb;
	$stars = 0;
	if (tagman_isseturl_param("tagorder")) {
		update_option(key_tagman_tagorder, tagman_geturl_param("tagorder")); 
	}
	switch (get_option(key_tagman_tagorder))
	{
		case "name": 
			$order = "a.slug ASC";
			$orderoption = "n";
			break;
		default:
			$order = "b.count DESC, a.slug ASC";
			$orderoption = "c";
			break;
	}
	$sql_terms = "SELECT a.term_id, a.name, b.term_taxonomy_id, b.count, a.slug FROM $wpdb->terms a, $wpdb->term_taxonomy b WHERE a.term_id=b.term_id AND b.taxonomy='post_tag' ORDER BY $order";
	$res_terms = $wpdb->get_results($sql_terms);
	$class = '';
	$columns = array();
	$current="";
	switch ($orderoption) {
		case "n":
			echo '<p class="submit">Change order to <input type="submit" name="tagorder" value="count" /></p>';
			break;
		default:
			echo '<p class="submit">Change order to <input type="submit" name="tagorder" value="name" /></p>';
			break;
	}
	?>
	<table class="widefat" style="border: 1px solid #ccc;">
	<tbody id="the-list">
	<?php 
		foreach ($res_terms as $row_term) {
			//Zeilenheader bei neuer Anzahl
			switch ($orderoption)
				{
					case "n":
						$actual = strtolower(substr($row_term->slug,0,1)); 
						break;
					default:
						$actual = $row_term->count;
						$orderoption = "c";
						break;
				}
			if 	($current != $actual) {		
				$current = $actual;
				if ($ct > 0) {
					for ($i=$ct; $i<7; $i++) {
						echo '<td class="'.$class.'" style="width:14%;"></td>';					
					}				
				}				
				echo '</tr>';
				$class = ('alternate' == $class) ? '' : 'alternate';
				$ct=0;
				echo '<tr id="post-"><td class="'.$class.'" style="width:2%;"><div style="text-align: center; font-weight: bold;">'.$actual.'</div></td>';
			} else {
				if ($ct==0) {
					echo '<tr id="post-"><td class="'.$class.'" style="width:2%;"></td>';			
				}		
			}	
			$tagname = $row_term->name;
			$tagcount = $row_term->count;
			if (tagman_is_post_tag_only($row_term->term_id)) {
				echo '<td class="'.$class.'" style="width:14%;"><a href="edit.php?page=tagsmanager.php&edit_tag='.$row_term->term_taxonomy_id.'">'.$tagname.' ('.$tagcount.')</a></td>';
			} else {
				echo '<td class="'.$class.'" style="width:14%;">'.$tagname.' ('.$tagcount.')*</td>';
				$stars = 1;
			}
			$ct++;
			if ($ct==7) {
				echo '</tr>';
				$ct=0;
			}			
		}	
		if ($ct!=0) { 
			echo '</tr>';
		}
	?>	
	</tbody>
	</table> <?php
	if ($stars>0) { ?>
		<p>* Post Tags that shares the term with categories or links, won't be supported at moment (word for category or blogroll equals tag word)!</p>
		<?php
	}
}

function tagman_option_getnewestposts($count=5) {
	global $wpdb;
	$sql_posts = "SELECT ID, post_title, post_author, post_date FROM $wpdb->posts WHERE post_status='publish' AND post_type='post' ORDER BY post_date DESC";
	$res_posts = $wpdb->get_results($sql_posts); 
	?>
	<table class="widefat" style="border: 1px solid #ccc;">
		<thead>
			<tr>
				<th scope="col"><div style="text-align: center;"><?php echo __('ID'); ?></div></th>
				<th scope="col"><?php echo __('When'); ?></th>
				<th scope="col"><?php echo __('Title'); ?></th>
				<th scope="col"><?php echo __('Author'); ?></th>
				<th scope="col"><?php echo __('Edit'); ?></th>
				<th scope="col">Edit tags</th>
			</tr>
		</thead>
		<tbody id="the-list">		
			<?php
				$count_posts=0;	
				$class = '';
				foreach($res_posts as $row_post) {
					$sql_tagcount = "SELECT COUNT(*) AS TagCount FROM $wpdb->term_relationships a, $wpdb->term_taxonomy b WHERE b.taxonomy='post_tag' AND a.term_taxonomy_id=b.term_taxonomy_id AND object_id=$row_post->ID";
					$res_tagcount = $wpdb->get_var($sql_tagcount);
					if ($res_tagcount==0) {
						$count_posts++;
						if ($count_posts<=$count) {
							$class = ('alternate' == $class) ? '' : 'alternate'; 
							$displaytime = mysql2date(__('Y-m-d \<\b\r \/\> g:i:s a'), $row_post->post_date);
							$author = get_userdata($row_post->post_author);
							echo '<tr id="post-" class="'.$class.'"><td><div style="text-align: center;">'.$row_post->ID.'</div></td><td>'.$displaytime.'</td><td>'.$row_post->post_title.'</td><td>'.$author->display_name.'</td>';
							echo '<td>';
							if ( current_user_can('edit_post',$row_post->post_author) ) { 
								echo '<a href="post.php?action=edit&amp;post='.$row_post->ID.'" class="edit">'. __('Edit').'</a>'; 
							} 												
							echo '</td><td>';
							if ( current_user_can('edit_post',$row_post->post_author) ) { 
								echo '<a href="edit.php?page=tagsmanager.php&edit_post='.$row_post->ID.'" class="edit">Edit tags</a>';
							} 						
							echo '</td></tr>';
						}				
					}			
				}			
			?>	
		</tbody>
	</table>

	<p>There are <?php echo Max($count_posts-$count,0); ?> more posts without tags!</p>	
	<?php
}

function tagman_convert_2_lower() {
	global $wpdb;
	$sql = "SELECT term_id, name FROM $wpdb->terms";
	$res = $wpdb->get_results($sql);
	foreach ($res as $row) {
		if (tagman_is_post_tag_only($row->term_id)) {			
			$newname = $wpdb->escape(strtolower($row->name));
			$sql = "UPDATE $wpdb->terms SET name='$newname' WHERE term_id=$row->term_id";
			$wpdb->query($sql);
		}		
	}
	return true;
}

function tagman_is_post_tag_only($termid) {
	global $wpdb;
	$sql = "SELECT taxonomy FROM $wpdb->term_taxonomy WHERE term_id=$termid";
	$res = $wpdb->get_results($sql);
	$ret = false;
	foreach ($res as $row) {
		if ($row->taxonomy == "post_tag") {
			$ret = true;
		}
	}
	if ($ret && (count($res)>1)) {
		$ret = false;
	}
	return $ret;
}

function tagman_isseturl_param($key) {
	return isset($_GET[$key]) || isset($_POST[$key]);
}

function  tagman_geturl_param($key) {
	if (isset($_GET[$key])) {
		return $_GET[$key];
	} else {
		if (isset($_POST[$key])) {
			return $_POST[$key];
		} else {
			return null;		
		}
	}	
}

?>