<?php
/*
Plugin Name: Add Keywords to Single Post
Plugin URI: https://www.irakli.life
Description: Adds meta tag for keywords to the head of a single post and allows adding the meta keyword from the post edit page.
Version: 2.2
Author: Irakli Antidze
Author URI: https://www.irakli.life
Text Domain: add-keywords-to-single-post
*/

// Add meta tag for keywords to the head of a single post
function ianti_add_keywords_to_post_head() {
    if (is_single()) {
        $post_id = get_the_ID();
        $keywords = get_post_meta($post_id, '_ianti_meta_keyword_value', true);

        // Fetch post tags if no custom keywords exist
        if (empty($keywords)) {
            $post_tags = get_the_tags();
            if ($post_tags) {
                $keywords_array = array_map(function($tag) {
                    return $tag->name;
                }, $post_tags);
                $keywords = implode(', ', $keywords_array);
            }
        }

        // Allow filtering of keywords
        $keywords = apply_filters('ianti_custom_meta_keywords', $keywords, $post_id);

        if (!empty($keywords)) {
            echo '<meta name="keywords" content="' . esc_attr($keywords) . '">' . "\n";
        }
    }
}
add_action('wp_head', 'ianti_add_keywords_to_post_head');

// Add meta keyword field to the post edit page
function ianti_add_meta_keyword_field() {
    $screen = get_current_screen();
    add_meta_box(
        'ianti_meta_keyword', 
        __('Meta Keywords', 'add-keywords-to-single-post'), 
        'ianti_meta_keyword_callback', 
        $screen->post_type, 
        'normal', 
        'high'
    );
}
add_action('add_meta_boxes', 'ianti_add_meta_keyword_field');

// Callback function for the meta keyword field
function ianti_meta_keyword_callback($post) {
    wp_nonce_field('ianti_save_meta_keyword', 'ianti_meta_keyword_nonce');
    $meta_keyword_value = get_post_meta($post->ID, '_ianti_meta_keyword_value', true);
    echo '<label for="ianti_meta_keyword_field">' . esc_html__('Enter meta keywords:', 'add-keywords-to-single-post') . '</label>';
    echo '<input type="text" id="ianti_meta_keyword_field" name="ianti_meta_keyword_field" value="' . esc_attr($meta_keyword_value) . '" class="widefat">';
}

// Save meta keyword value when the post is saved
function ianti_save_meta_keyword_value($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['ianti_meta_keyword_nonce']) || !wp_verify_nonce($_POST['ianti_meta_keyword_nonce'], 'ianti_save_meta_keyword')) {
        return;
    }
    
    // Check dynamic user permissions based on post type
    $post_type = get_post_type($post_id);
    $post_type_object = get_post_type_object($post_type);
    
    if (!$post_type_object || !current_user_can($post_type_object->cap->edit_post, $post_id)) {
        return;
    }

    // Save or delete the metadata
    if (isset($_POST['ianti_meta_keyword_field'])) {
        $meta_keyword_value = sanitize_text_field($_POST['ianti_meta_keyword_field']);
        update_post_meta($post_id, '_ianti_meta_keyword_value', $meta_keyword_value);
    } else {
        delete_post_meta($post_id, '_ianti_meta_keyword_value');
    }
}
add_action('save_post', 'ianti_save_meta_keyword_value');
?>
