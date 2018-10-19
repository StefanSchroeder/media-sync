<?php

/**
 * Plugin Name: Media Sync
 * Plugin URI: https://wordpress.org/plugins/media-sync/
 * Description: Simple plugin to scan uploads directory and bring files to Media Library
 * Version: 0.1.1
 * Author: Erol Živina
 * Author URI: https://github.com/erolsk8
 * License: GPLv2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: media-sync
 * Domain Path:
 *
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;



add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'media_sync_link_to_main_plugin_page' );

/**
 * Add link below plugin name on 'Plugins' page
 *
 * @since 0.1.0
 */
function media_sync_link_to_main_plugin_page( $links ) {
    $links[] = '<a href="'. esc_url( get_admin_url(null, 'upload.php?page=media-sync-page') ) .'">Media Sync</a>';
    return $links;
}



add_action( 'admin_menu', 'media_sync_add_menu_items' );

/**
 * Add menu item for this plugin
 *
 * @since 0.1.0
 */
function media_sync_add_menu_items() {
    // Add sub item to Media menu
    add_media_page( 'Media Sync', 'Media Sync', 'activate_plugins', 'media-sync-page', 'media_sync_main_page' );
}



include( plugin_dir_path(__FILE__) . 'includes/MediaSync.class.php');



add_action( 'admin_enqueue_scripts', 'media_sync_load_admin_scripts', 100 );

/**
 * Load Admin CSS and JS files
 *
 * @since 0.1.0
 * @return void
 */
function media_sync_load_admin_scripts( $hook ) {

    $js_dir  = plugin_dir_url( __FILE__ ) . 'admin/js/';
    $css_dir = plugin_dir_url( __FILE__ ) . 'admin/css/';

    wp_register_script( 'media-sync-js-admin-script', $js_dir . 'script.js', ['jquery'], false, true );
    wp_enqueue_script( 'media-sync-js-admin-script' );

    wp_enqueue_script( 'media-sync-js-admin-ajax-script', $js_dir . 'ajax_script.js', ['jquery'] );
    wp_localize_script( 'media-sync-js-admin-ajax-script', 'ajax_data', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'security' => wp_create_nonce( "media_sync_import_files" )
    ]);

    wp_register_style( 'media-sync-css-admin-style', $css_dir . 'style.css');
    wp_enqueue_style( 'media-sync-css-admin-style' );
}



add_action( 'wp_ajax_media_sync_import_files', 'media_sync_import_files' );

/**
 * Ajax action to import selected file
 *
 * @since 0.1.0
 * @return void
 */
function media_sync_import_files() {
    MediaSync::media_sync_import_files();
}



/**
 * Main function for "Media Sync" page
 *
 * @since 0.1.0
 * @return void
 */
function media_sync_main_page() {
    MediaSync::media_sync_main_page();
}
?>