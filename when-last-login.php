<?php
/**
 * Plugin Name: When Last Login
 * Plugin URI: https://wordpress.org/plugins/when-last-login/
 * Description: See when a user logs into your WordPress site.
 * Version: 1.2.2
 * Author: Yoohoo Plugins
 * Author URI: https://yoohooplugins.com
 * Text Domain: when-last-login
 * Domain Path: /languages
 *
 * @package when-last-login
*/

define( 'WLL_VER', '1.2.2' );
define( 'WLL_BASENAME', plugin_basename( __FILE__ ) );
define( 'WLL_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WLL_PLUGIN', WP_PLUGIN_URL . '/when-last-login' );

require 'vendor/autoload.php';

When_Last_Login::get_instance();
new WLL_Admin();
