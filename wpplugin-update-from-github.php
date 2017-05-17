<?php
/**
 * Plugin Name:     Update from Github
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
        new \UpdateFromGithub\updater\GitHub_Plugin_Updater( __FILE__, 'mwender', 'wpplugin-update-from-github' );
    } );
}