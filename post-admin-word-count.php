<?php

/*
  Plugin Name: Post Admin Word Count
  Plugin URI: http://www.jonbishop.com/downloads/wordpress-plugins/post-word-count/
  Description: Adds a sortable column to the admin's post manager, displaying the word count for each post.
  Version: 1.2
  Author: Jon Bishop
  Author URI: http://www.jonbishop.com
  License: GPL2
 */

class PostAdminWordCount {

    function init() {
        if (is_admin()) {
            add_filter('manage_edit-post_sortable_columns', array(&$this, 'pwc_column_register_sortable'));
            add_filter('posts_orderby', array(&$this, 'pwc_column_orderby'), 10, 2);
            add_filter("manage_posts_columns", array(&$this, "pwc_columns"));
            add_action("manage_posts_custom_column", array(&$this, "pwc_column"));
            add_action("admin_footer-edit.php",array(&$this, "pwc_update_date"));
            add_action("admin_head-edit.php",array(&$this, "pwc_get_date"));
            
        }
    }

    //=============================================
    // Add new columns to action post type
    //=============================================
    function pwc_columns($columns) {
        $columns["post_word_count"] = "Word Count";
        return $columns;
    }

    //=============================================
    // Add data to new columns of action post type
    //=============================================
    function pwc_column($column) {
        global $post, $pwc_last;
        if ("post_word_count" == $column) {
            // Grab a fresh word count
            $word_count = str_word_count($post->post_content);
            echo $word_count;
        }
    }

    //=============================================
    // Queries to run when sorting
    // new columns of action post type
    //=============================================
    function pwc_column_orderby($orderby, $wp_query) {
        global $wpdb;

        if ('post_word_count' == @$wp_query->query['orderby'])
            $orderby = "(SELECT CAST(meta_value as decimal) FROM $wpdb->postmeta WHERE post_id = $wpdb->posts.ID AND meta_key = '_post_word_count') " . $wp_query->get('order');

        return $orderby;
    }

    //=============================================
    // Make new columns to action post type sortable
    //=============================================
    function pwc_column_register_sortable($columns) {
        $columns['post_word_count'] = 'post_word_count';
        return $columns;
    }
    
    function pwc_get_date(){
        global $post, $pwc_last;
        // Check last updated
        $pwc_last = get_option('pwc_last_checked');

        // Check to make sure we have a post and post type
		if ( $post && $post->post_type ){
			
			// Grab all posts with post type
			$args = array(
				'post_type' => $post->post_type,
				'posts_per_page' => -1
				);

			// Grab the posts
			$post_list = new WP_Query($args);
			if ( $post_list->have_posts() ) : while ( $post_list->have_posts() ) : $post_list->the_post(); 
				
				// Grab a fresh word count
	            $word_count = str_word_count($post->post_content);

	            // If post has been updated since last check
	            if ($post->post_modified > $pwc_last || $pwc_last == "") {
	            	// Grab word count from post meta
	                $saved_word_count = get_post_meta($post->ID, '_post_word_count', true);
	                // Check if new wordcount is different than old word count
	                if ($saved_word_count != $word_count || $saved_word_count == "") {
	                	// Update word count in post meta
	                    update_post_meta($post->ID, '_post_word_count', $word_count, $saved_word_count);
	                }
	            }
			endwhile; 
			endif;

			// Let WordPress do it's thing
			wp_reset_query();
		}
    }
    
    function pwc_update_date(){
    	// Save the last time this page was generated
        $current_date = current_time('mysql');
        update_option('pwc_last_checked', $current_date);
    }

}

$postAdminWordCount = new PostAdminWordCount();
$postAdminWordCount->init();
?>