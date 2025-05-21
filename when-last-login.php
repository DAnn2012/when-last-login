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

	// Add version to options
	add_option( 'wll_version', WLL_VER, '',  'no' );

	if ( ! wp_next_scheduled( 'wll_migrate_login_records_event' ) ) {
		wp_schedule_event( time(), 'wll_migration_schedule', 'wll_migrate_login_records_event' );
	}
}

/**
 * Plugin deactivation
 */
function wll_deactivate() {
	wp_clear_scheduled_hook( 'wll_migrate_login_records_event' );
	delete_option( 'wll_migration_page' );

	error_log( 'delete ' . print_r( When_Last_Login::get_settings( 'delete_data'), true ) );

}


new WLL_Database();
When_Last_Login::get_instance();
new WLL_Admin();
