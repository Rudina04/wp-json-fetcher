<?php
/**
 * Plugin Name: WP JSON Fetcher
 * Plugin URI:  https://yourwebsite.com
 * Description: Fetch and display posts from JSONPlaceholder in a responsive grid layout with caching and admin settings.
 * Version:     1.1
 * Author:      Rudina
 */

if (!defined('ABSPATH')) {
    exit; 
}

function wp_json_fetcher_enqueue_styles() {
    wp_enqueue_style('wp-json-fetcher-style', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'wp_json_fetcher_enqueue_styles');

// Fetch posts with caching
function wp_json_fetcher_get_posts() {
    $cache_key = 'wp_json_fetcher_posts';
    $cached_posts = get_transient($cache_key);

    if ($cached_posts !== false) {
        return $cached_posts; 
    }

    $response = wp_remote_get('https://jsonplaceholder.typicode.com/posts');

    if (is_wp_error($response)) {
        return '<p>Error fetching posts.</p>';
    }

    $body = wp_remote_retrieve_body($response);
    $posts = json_decode($body);

    if (empty($posts)) {
        return '<p>No posts found.</p>';
    }

    $cache_duration = get_option('wp_json_fetcher_cache_time', 3600);
    set_transient($cache_key, $posts, $cache_duration);

    return $posts;
}

function wp_json_fetcher_display_posts() {
    $posts = wp_json_fetcher_get_posts();
    $num_posts = get_option('wp_json_fetcher_num_posts', 8); 

    if (!is_array($posts)) {
        return '<p>Error fetching posts.</p>';
    }

    $output = '<div class="wp-json-fetcher-container">';

    foreach (array_slice($posts, 0, $num_posts) as $post) {
        $output .= '<div class="wp-json-fetcher-card">';
        $output .= '<h3>' . esc_html($post->title) . '</h3>';
        $output .= '<p>' . esc_html($post->body) . '</p>';
        $output .= '</div>';
    }

    $output .= '</div>';
    
    return $output;
}

add_shortcode('wp_json_fetcher', 'wp_json_fetcher_display_posts');

function wp_json_fetcher_create_menu() {
    add_options_page(
        'WP JSON Fetcher Settings', 
        'WP JSON Fetcher', 
        'manage_options', 
        'wp-json-fetcher-settings', 
        'wp_json_fetcher_settings_page'
    );
}
add_action('admin_menu', 'wp_json_fetcher_create_menu');

function wp_json_fetcher_settings_page() {
    ?>
    <div class="wrap">
        <h1>WP JSON Fetcher Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wp_json_fetcher_settings_group');
            do_settings_sections('wp-json-fetcher-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function wp_json_fetcher_register_settings() {
    register_setting('wp_json_fetcher_settings_group', 'wp_json_fetcher_num_posts');
    register_setting('wp_json_fetcher_settings_group', 'wp_json_fetcher_cache_time');

    add_settings_section(
        'wp_json_fetcher_main_section',
        'Settings',
        null,
        'wp-json-fetcher-settings'
    );

    add_settings_field(
        'wp_json_fetcher_num_posts',
        'Number of Posts to Display:',
        'wp_json_fetcher_num_posts_callback',
        'wp-json-fetcher-settings',
        'wp_json_fetcher_main_section'
    );

    add_settings_field(
        'wp_json_fetcher_cache_time',
        'Cache Duration (seconds):',
        'wp_json_fetcher_cache_time_callback',
        'wp-json-fetcher-settings',
        'wp_json_fetcher_main_section'
    );
}
add_action('admin_init', 'wp_json_fetcher_register_settings');

// Input field for number of posts
function wp_json_fetcher_num_posts_callback() {
    $value = get_option('wp_json_fetcher_num_posts', 8);
    echo '<input type="number" name="wp_json_fetcher_num_posts" value="' . esc_attr($value) . '" min="1" />';
}

// Input field for cache duration
function wp_json_fetcher_cache_time_callback() {
    $value = get_option('wp_json_fetcher_cache_time', 3600);
    echo '<input type="number" name="wp_json_fetcher_cache_time" value="' . esc_attr($value) . '" min="60" />';
}
