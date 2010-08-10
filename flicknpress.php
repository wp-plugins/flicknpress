<?php  
	/* 
	Plugin Name: flicknpress
	Plugin URI: http://www.brianjlink.com/flicknpress
	Description: Add Photos from Flickr to Your Blog Posts
	Author: Brian Link 
	Version: 1.0 
	Author URI: http://www.brianjlink.com
	*/
    
	add_action('admin_menu', 'bjl_flickr_image_add_meta_box');
	add_action('admin_head', 'bjl_flickr_image_javascript_functions');
	add_action('wp_ajax_my_special_action', 'bjl_flickr_image_action_callback');
	add_action('save_post', 'bjl_flickr_image_save_postdata');
	add_filter('the_content', 'bjl_flickr_image_filter');
	add_action('admin_head', 'bjl_flickr_image_style_admin');
	add_action('wp_head', 'bjl_flickr_image_style_theme');
	
	function bjl_flickr_image_style_admin()
	{
		$siteurl = get_option('siteurl');
		$url = get_option('siteurl').'/wp-content/plugins/'.basename(dirname(__FILE__)).'/style_admin.css';
		
		echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
	}
	
	function bjl_flickr_image_style_theme()
	{
		$siteurl = get_option('siteurl');
		$url = get_option('siteurl').'/wp-content/plugins/'.basename(dirname(__FILE__)).'/style_theme.css';
		
		echo "<link rel='stylesheet' type='text/css' href='$url' />\n";
	}


	function bjl_flickr_image_add_meta_box()
	{
		if (function_exists('add_meta_box'))
		{
			add_meta_box('bjl_flickr_images', __('Flickr Image', 'bjl_flickr_images'), 'bjl_flickr_image_inner_custom_box', 'post', 'normal', 'high');
			add_meta_box('bjl_flickr_images', __('Flickr Image', 'bjl_flickr_images'), 'bjl_flickr_image_inner_custom_box', 'page', 'normal', 'high');
		}
	}
	
	function bjl_flickr_image_filter($content)
	{
		global $wpdb, $post;
		
		$bjl_flickr_image_photo_id = get_post_meta($post->ID, "_bjl_flickr_image_photo_id", true);
		$bjl_flickr_image_photo_url = get_post_meta($post->ID, "_bjl_flickr_image_photo_url", true);
		$bjl_flickr_image_owner_id = get_post_meta($post->ID, "_bjl_flickr_image_owner_id", true);
		$bjl_flickr_image_owner_name = get_post_meta($post->ID, "_bjl_flickr_image_owner_name", true);
		$bjl_flickr_image_position = get_post_meta($post->ID, "_bjl_flickr_image_position", true);
		
		if ($bjl_flickr_image_photo_id)
		{
			$pre_content = '<div class="'.($bjl_flickr_image_position == "center" ? "bjl_flickr_image_center" : "bjl_flickr_image_left_right").' caption align'.$bjl_flickr_image_position.'" ';
			$pre_content.= 'style="background-image:url('.$bjl_flickr_image_photo_url.');">';
			$pre_content.= '<p class="wp-caption-text">Photo Credit: <a target="_new" href="http://www.flickr.com/photos/'.$bjl_flickr_image_owner_id.'/">'.$bjl_flickr_image_owner_name.'</a></p>';
			$pre_content.= '</div>';
			
			return $pre_content.$content;
		}
		else
		{
			return $content;
		}
		
		
	}
	
	function bjl_flickr_image_inner_custom_box()
	{
		require_once("phpFlickr.php");
		$f = new phpFlickr("de5d9b78dd8961c780be698a726b7b2d", "3157671718c0818f");
		
		global $wpdb, $post_id;
		
		$bjl_flickr_image_photo_id = get_post_meta($post_id, "_bjl_flickr_image_photo_id", true);
		$bjl_flickr_image_photo_url = get_post_meta($post_id, "_bjl_flickr_image_photo_url", true);
		$bjl_flickr_image_owner_id = get_post_meta($post_id, "_bjl_flickr_image_owner_id", true);
		$bjl_flickr_image_owner_name = get_post_meta($post_id, "_bjl_flickr_image_owner_name", true);
		$bjl_flickr_image_position = get_post_meta($post_id, "_bjl_flickr_image_position", true);
		
		// Use nonce for verification
		echo '<input type="hidden" name="bjl_flickr_image_noncename" id="bjl_flickr_image_noncename" value="'.wp_create_nonce(plugin_basename(__FILE__)).'" />';
		
		echo '<p>'.__("Keyword(s)").'</label> ';
		echo '<input type="text" name="bjl_flickr_image_keywords" size=25 value="" /> ';
		
		echo '<select id="bjl_flickr_image_results" name="bjl_flickr_image_results">';
		echo '<option value="9">9 '.__("Results").'</option>';
		echo '<option value="12">12 '.__("Results").'</option>';
		echo '<option value="15">15 '.__("Results").'</option>';
		echo '<option value="18">18 '.__("Results").'</option>';
		echo '<option value="21">21 '.__("Results").'</option>';
		echo '<option value="24">24 '.__("Results").'</option>';
		echo '<option value="27">27 '.__("Results").'</option>';
		echo '</select> ';
		
		echo ' <input type="button" class="button tagadd" value="'.__("Search").'" onclick="bjl_load_flickr_images(bjl_flickr_image_keywords.value, bjl_flickr_image_results.value);" /></p>';
		
		echo '<div id="bjl_flickr_image_meta"'.($bjl_flickr_image_photo_id ? " style='display:block;'" : "").'>';
		echo '<div id="bjl_flickr_image_box"></div>';
		echo '<div id="bjl_flickr_image_selected" style="';
		if ($bjl_flickr_image_photo_id) echo 'background-image:url('.$bjl_flickr_image_photo_url.'); ';
		echo '">';
		if (!$bjl_flickr_image_photo_id) echo __("No Image Selected");
		echo '</div>';
		
		echo '<p id="bjl_flickr_image_description">';
		if ($bjl_flickr_image_owner_id) echo __("Photo Credit").': <a target="_new" href="http://www.flickr.com/photos/'.$bjl_flickr_image_owner_id.'/">'.$bjl_flickr_image_owner_name.'</a>';
		echo '</p>';
		
		echo '<p id="bjl_flickr_image_options">';
		echo 'Position: ';
		echo '<select name="bjl_flickr_image_position">';
		echo '<option value="center"'.($bjl_flickr_image_position == "center" ? " selected" : "").'>'.__("Center").'</option>';
		echo '<option value="left"'.($bjl_flickr_image_position == "left" ? " selected" : "").'>'.__("Left").'</option>';
		echo '<option value="right"'.($bjl_flickr_image_position == "right" ? " selected" : "").'>'.__("Right").'</option>';
		echo '</select> ';
		echo '<input type="button" class="button tagadd" value="'.__("Remove").'" onclick="bjl_delete_flickr_image();" />';
		echo '<p>';
		echo '</div>';
		
		// HIDDEN FIELDS
		echo '<input type="hidden" id="bjl_flickr_image_photo_id" name="bjl_flickr_image_photo_id" value="'.$bjl_flickr_image_photo_id.'" />';
		echo '<input type="hidden" id="bjl_flickr_image_photo_url" name="bjl_flickr_image_photo_url" />';
		echo '<input type="hidden" id="bjl_flickr_image_owner_id" name="bjl_flickr_image_owner_id" />';
		echo '<input type="hidden" id="bjl_flickr_image_owner_name" name="bjl_flickr_image_owner_name" />';
		echo '<input type="hidden" id="bjl_flickr_image_delete" name="bjl_flickr_image_delete" />';
	}

	function bjl_flickr_image_javascript_functions()
	{
		?>
		<script type="text/javascript">
			function bjl_load_flickr_images($keywords, $results, $cc)
			{
				var data = {
					action: 'my_special_action',
					keywords: $keywords,
					results: $results
				};
				
				document.getElementById("bjl_flickr_image_meta").style.display = 'block';
				document.getElementById("bjl_flickr_image_box").innerHTML = '<p><img src="images/loading.gif" /> <?php echo __("Searching"); ?>...</p>';
	
				jQuery.post(ajaxurl, data, function(response)
				{
					document.getElementById("bjl_flickr_image_box").innerHTML = response;
				});
			}
			
			function bjl_selected_flickr_image($url, $title, $photo_id, $owner_id, $owner_name)
			{
				$description = '<?php echo __("Photo Credit"); ?>: <a target="_new" href="http://www.flickr.com/photos/' + $owner_id + '/">' + $owner_name + '</a>';
				document.getElementById("bjl_flickr_image_description").innerHTML = $description;
				
				/*
				document.getElementById("title").focus();
				document.getElementById("title").value = $title;
				*/
				
				document.getElementById("bjl_flickr_image_selected").innerHTML = '';
				document.getElementById("bjl_flickr_image_selected").style.backgroundImage = "url(" + $url + ")";
				
				// SET HIDDEN INPUTS
				document.getElementById("bjl_flickr_image_photo_id").value = $photo_id;
				document.getElementById("bjl_flickr_image_photo_url").value = $url;
				document.getElementById("bjl_flickr_image_owner_id").value = $owner_id;
				document.getElementById("bjl_flickr_image_owner_name").value = $owner_name;
			}
			
			function bjl_delete_flickr_image()
			{
				document.getElementById("bjl_flickr_image_description").innerHTML = '';
				document.getElementById("bjl_flickr_image_selected").style.backgroundImage = 'none';
				document.getElementById("bjl_flickr_image_selected").innerHTML = '<?php echo __("No Image Selected"); ?>';
				
				document.getElementById("bjl_flickr_image_photo_id").value = "";
				document.getElementById("bjl_flickr_image_photo_url").value = "";
				document.getElementById("bjl_flickr_image_owner_id").value = "";
				document.getElementById("bjl_flickr_image_owner_name").value = "";
				document.getElementById("bjl_flickr_image_delete").value = "true";
			}
		</script>
		<?php
	}

	function bjl_flickr_image_action_callback()
	{
		$keywords = $_POST["keywords"];
		$results = $_POST["results"];
		$cc = $_POST["cc"];
		
		require_once("phpFlickr.php");
		$f = new phpFlickr("de5d9b78dd8961c780be698a726b7b2d", "3157671718c0818f");
		
		$args = array("tags"=>$keywords, "sort"=>"recent-desc", "per_page"=>$results);
		$photos = $f->photos_search($args);
		
		if ($keywords == "")
		{
			$bjl_html = '<p>'.__("Please enter in keyword(s).").'</p>';
		}
		else
		{
			if (count($photos['photo']) > 0)
			{
				$bjl_html.= '<ul>';
				foreach ($photos['photo'] as $photo)
				{
			    		$owner = $f->people_getInfo($photo['owner']);
			    
					$bjl_html.= '<li>';
					$bjl_html.= "<img onclick='bjl_selected_flickr_image(\"".$f->buildPhotoURL($photo, "Medium")."\", \"".$photo["title"]."\", \"".$photo["id"]."\", \"".$photo["owner"]."\", \"".$owner['username']."\");' src='".$f->buildPhotoURL($photo, "Square")."' />";
					$bjl_html.= '</li>';
				}
				$bjl_html.= '</ul>';
			}
			else
			{
				$bjl_html = '<p>'.__("No results found.").'</p>';
			}
		}
	
		echo $bjl_html;
	
		die();
	}

	function bjl_flickr_image_save_postdata($post_id)
	{
		// verify this came from the our screen and with proper authorization, because save_post can be triggered at other times
		if ( !wp_verify_nonce( $_POST['bjl_flickr_image_noncename'], plugin_basename(__FILE__) )) { return $post_id; }
		
		// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;
		
		// Check permissions
		if ('page' == $_POST['post_type']) { if (!current_user_can('edit_page', $post_id)) return $post_id; } else { if (!current_user_can('edit_post', $post_id)) return $post_id; }
		
		if ($_POST["bjl_flickr_image_delete"] == true)
		{
			delete_post_meta($post_id, '_bjl_flickr_image_photo_id');
			delete_post_meta($post_id, '_bjl_flickr_image_photo_url');
			delete_post_meta($post_id, '_bjl_flickr_image_owner_id');
			delete_post_meta($post_id, '_bjl_flickr_image_owner_name');
			delete_post_meta($post_id, '_bjl_flickr_image_position');
		}
		else
		{
			if ($_POST["bjl_flickr_image_photo_id"]) { update_post_meta($post_id, '_bjl_flickr_image_photo_id', $_POST["bjl_flickr_image_photo_id"]); }
			if ($_POST["bjl_flickr_image_photo_url"]) { update_post_meta($post_id, '_bjl_flickr_image_photo_url', $_POST["bjl_flickr_image_photo_url"]); }
			if ($_POST["bjl_flickr_image_owner_id"]) { update_post_meta($post_id, '_bjl_flickr_image_owner_id', $_POST["bjl_flickr_image_owner_id"]); }
			if ($_POST["bjl_flickr_image_owner_name"]) {update_post_meta($post_id, '_bjl_flickr_image_owner_name', $_POST["bjl_flickr_image_owner_name"]); }
			if ($_POST["bjl_flickr_image_photo_id"] && $_POST["bjl_flickr_image_position"]) {update_post_meta($post_id, '_bjl_flickr_image_position', $_POST["bjl_flickr_image_position"]); }
		}
	}
?>