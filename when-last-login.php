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

add_filter( 'plugin_row_meta', 'wll_plugin_row_meta', 10, 2 );
add_filter( 'plugin_action_links_' . WLL_BASENAME, 'wll_plugin_action_links', 10, 2 );

add_filter( 'cron_schedules', function ( $schedules ) {
	$schedules['wll_migration_schedule'] = [
		'interval' => apply_filters( 'wll_migration_interval', 300 ),
		'display'  => __( 'When Last Login Migration' ),
	];
	return $schedules;
} );

add_action( 'plugins_loaded', function ( $schedules ) {
	if ( version_compare( get_option( 'wll_version' ), WLL_VER, '<' ) ) {
		wll_activate();
	}
} );

/**
 * Plugin activation.
 */
function wll_activate() {
	WLL_Database::create_table();
	if ( ! wp_next_scheduled( 'wll_migrate_login_records_event' ) ) {
		wp_schedule_event( time(), 'wll_migration_schedule', 'wll_migrate_login_records_event' );
	}
	update_option( 'wll_version', WLL_VER, '',  'no' );
}

/**
 * Plugin deactivation.
 */
function wll_deactivate() {
	wp_clear_scheduled_hook( 'wll_migrate_login_records_event' );
}

/**
 * Add meta links to the plugin row.
 *
 * @param array  $links The existing links.
 * @param string $file The plugin file.
 * @return array
 */
function wll_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'when-last-login.php' ) !== false ) {
		$new_links = array(
		'<a href="' . admin_url('admin.php?page=when-last-login-settings') . '" title="' . esc_attr( __( 'View Settings', 'when-last-login' ) ) . '">' . __( 'Settings', 'when-last-login' ) . '</a>',
		'<a href="' . esc_url( 'https://yoohooplugins.com/?s=when+last+login' ) . '" title="' . esc_attr__( 'View Documentation', 'when-last-login' ) . '">' . esc_html__( 'Docs', 'when-last-login' ) . '</a>',
		'<a href="' . esc_url( 'https://yoohooplugins.com/support/' ) . '" title="' . esc_attr__( 'Visit Customer Support Forum', 'when-last-login' ) . '">' . esc_html__( 'Support', 'when-last-login' ) . '</a>',
		);

		$new_links = apply_filters( 'wll_plugin_row_meta', $new_links );
		$links = array_merge( $links, $new_links );
	}
	return $links;
}

/**
 * Add action links to the plugin.
 *
 * @param array $links The existing links.
 * @return array
 */
function wll_plugin_action_links( $links ) {
	$new_links = array(
		'<a href="' . admin_url('admin.php?page=when-last-login-settings') . '" title="' . esc_attr( __( 'View Settings', 'when-last-login' ) ) . '">' . __( 'Settings', 'when-last-login' ) . '</a>'
	);

	$new_links = apply_filters( 'wll_plugin_action_links', $new_links );

	return array_merge( $new_links, $links );
}

new WLL_Database();
When_Last_Login::get_instance();
new WLL_Admin();
new WLL_User_Registration_Login();
new WLL_Widgets();
new WLL_Columns();

// Load PMPRO integration if PMPRO is active.
if ( defined( 'PMPRO_VERSION' ) ) {
	new WLL_PMPRO();
}
