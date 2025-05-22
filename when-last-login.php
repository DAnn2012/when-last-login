<?php
/**
 * Plugin Name: When Last Login
 * Plugin URI: https://wordpress.org/plugins/when-last-login/
 * Description: See when a user logs into your WordPress site.
 * Version: 2.0.0
 * Author: Yoohoo Plugins
 * Author URI: https://yoohooplugins.com
 * Text Domain: when-last-login
 * Domain Path: /languages
 *
 * @package when-last-login
*/

define( 'WLL_VER', '2.0.0' );
define( 'WLL_BASENAME', plugin_basename( __FILE__ ) );
define( 'WLL_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WLL_PLUGIN', WP_PLUGIN_URL . '/when-last-login' );

require 'vendor/autoload.php';

register_activation_hook( __FILE__, 'wll_activate' );
register_deactivation_hook( __FILE__, 'wll_deactivate' );

add_filter( 'cron_schedules', function ( $schedules ) {
	$schedules['wll_migration_schedule'] = [
		'interval' => apply_filters( 'wll_migration_interval', 300 ),
		'display'  => __( 'When Last Login Migration' ),
	];
	return $schedules;
} );

/**
 * Plugin activation
 */
function wll_activate() {
	WLL_Database::create_table();
	if ( ! wp_next_scheduled( 'wll_migrate_login_records_event' ) ) {
		wp_schedule_event( time(), 'wll_migration_schedule', 'wll_migrate_login_records_event' );
	}
	update_option( 'wll_version', WLL_VER, '',  'no' );
}

/**
 * Plugin deactivation
 */
function wll_deactivate() {
	wp_clear_scheduled_hook( 'wll_migrate_login_records_event' );
}


new WLL_Database();
When_Last_Login::get_instance();
new WLL_Admin();
