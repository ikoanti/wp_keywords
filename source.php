<?php
/*
Plugin Name: Add Keywords to Single Post
Plugin URI: https://www.irakli.life
Description: Adds meta tag for keywords to the head of a single post and allows adding the meta keyword from the post edit page.
Version: 2.1
Author: Irakli Antidze
Author URI: https://www.irakli.life
*/

// Add meta tag for keywords to the head of a single post
function add_keywords_to_post_head() {
    if (is_single()) {
        $post_id = get_the_ID();
        $keywords = get_post_meta($post_id, '_meta_keyword_value', true);

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
        $keywords = apply_filters('custom_meta_keywords', $keywords, $post_id);

        if (!empty($keywords)) {
            echo '<meta name="keywords" content="' . esc_attr($keywords) . '">' . "\n";
        }
    }
}
add_action('wp_head', 'add_keywords_to_post_head');

// Add meta keyword field to the post edit page
function add_meta_keyword_field() {
    $screen = get_current_screen();
    add_meta_box('meta_keyword', __('Meta Keywords', 'textdomain'), 'meta_keyword_callback', $screen->post_type, 'normal', 'high');
}
add_action('add_meta_boxes', 'add_meta_keyword_field');

// Callback function for the meta keyword field
function meta_keyword_callback($post) {
    wp_nonce_field(basename(__FILE__), 'meta_keyword_nonce');
    $meta_keyword_value = get_post_meta($post->ID, '_meta_keyword_value', true);
    echo '<label for="meta_keyword_field">' . esc_html__('Enter meta keywords:', 'textdomain') . '</label>';
    echo '<input type="text" id="meta_keyword_field" name="meta_keyword_field" value="' . esc_attr($meta_keyword_value) . '" class="widefat">';
}

// Save meta keyword value when the post is saved
function save_meta_keyword_value($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['meta_keyword_nonce']) || !wp_verify_nonce($_POST['meta_keyword_nonce'], basename(__FILE__))) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['meta_keyword_field'])) {
        $meta_keyword_value = sanitize_text_field($_POST['meta_keyword_field']);
        update_post_meta($post_id, '_meta_keyword_value', $meta_keyword_value);
    } else {
        delete_post_meta($post_id, '_meta_keyword_value');
    }
}
add_action('save_post', 'save_meta_keyword_value');
?>
