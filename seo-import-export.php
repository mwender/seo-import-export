<?php
/**
 * Plugin Name:     SEO Import/Export
 * Plugin URI:      https://github.com/mwender/seo-import-export
 * Description:     Provides WP-CLI commands for importing/exporting SEO meta data for pages and posts.
 * Author:          Michael Wender
 * Author URI:      https://mwender.com
 * Text Domain:     seo-import-export
 * Domain Path:     /languages
 * Version:         1.1.0
 *
 * @package         seo_import_export
 */

if( defined( 'WP_CLI' ) && WP_CLI ){
    require_once( plugin_dir_path( __FILE__ ) . 'lib/fns/import-export-commands.php' );
}

// Initialize Plugin Updates
require_once ( plugin_dir_path( __FILE__ ) . 'lib/classes/plugin-updater.php' );
if( is_admin() ){
    add_action( 'init', function(){
        // If you're experiencing GitHub API rate limits while testing
        // plugin updates, create a `Personal access token` under your
        // GitHub profile's `Developer Settings`. Then add
        // `define( 'GITHUB_ACCESS_TOKEN', your_access_token );` to
        // your site's `wp-config.php`.
        new GitHub_Plugin_Updater( __FILE__, 'mwender', 'seo-import-export', GITHUB_ACCESS_TOKEN );
    } );
}