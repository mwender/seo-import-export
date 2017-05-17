<?php
/**
 * Plugin Name:     WordPress Plugin with GitHub Updates
 * Plugin URI:      https://github.com/mwender/wpplugin-update-from-github
 * Description:     A WordPress starter plugin with built-in updating from Github
 * Author:          Michael Wender
 * Author URI:      https://mwender.com
 * Text Domain:     wpplugin-update-from-github
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Wpplugin_Update_From_Github
 */

// Initialize Plugin Updates
require_once ( plugin_dir_path( __FILE__ ) . 'lib/classes/plugin-updater.php' );
if( is_admin() ){
    add_action( 'init', function(){
        // If you're experiencing GitHub API rate limits while testing
        // plugin updates, create a `Personal access token` under your
        // GitHub profile's `Developer Settings`. Then add
        // `define( 'GITHUB_ACCESS_TOKEN', your_access_token );` to
        // your site's `wp-config.php`.
        new GitHub_Plugin_Updater( __FILE__, 'mwender', 'wpplugin-update-from-github', GITHUB_ACCESS_TOKEN );
    } );
}