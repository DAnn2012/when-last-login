<?php
/**
 * When Last Login Database.
 *
 * @package when-last-login
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WLL_Database {

	/**
	 * The single instance of the class.
	 */
	public function __construct() {
		add_action( 'wll_migrate_login_records_event', array( $this, 'migrate_Login_records' ) );
	}

	/**
	 * Create the login records table
	 */
	public static function create_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'wll_login_records';

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			login_time bigint(20) unsigned NOT NULL,
			ip_address varchar(100) NOT NULL,
			PRIMARY KEY (id),
			KEY idx_user_id (user_id),
			KEY idx_login_time (login_time),
			KEY idx_user_time (user_id, login_time)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	/**
	 * Migrate posts to new table.
	 */
	public static function migrate_Login_records() {
		global $wpdb;

		$batch_size = apply_filters( 'wll_migration_batch_size', 50 );
		$page = (int) get_option( 'wll_migration_page', 1 );

		$args = [
			'posts_per_page' => $batch_size,
			'paged'          => $page,
			'post_type'      => 'wll_records',
			'post_status'    => 'any',
		];

		$wll_records = new WP_Query( $args );

		if ( ! $wll_records->have_posts() ) {
			delete_option( 'wll_migration_page' );

			$timestamp = wp_next_scheduled( 'wll_migrate_login_records_event' );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'wll_migrate_login_records_event' );
			}
			return;
		}

		foreach ( $wll_records->posts as $record ) {
			$record_inserted = $wpdb->insert(
				$wpdb->prefix . 'wll_login_records',
				[
					'user_id'    => $record->post_author,
					'login_time' => strtotime( $record->post_date ),
					'ip_address' => get_post_meta( $record->ID, 'wll_user_ip_address', true ),
				],
				[ '%d', '%d', '%s' ]
			);
			if ( $record_inserted ) {
				wp_delete_post( $record->ID, true );
			}
		}

		update_option( 'wll_migration_page', $page + 1 );
	}
}
