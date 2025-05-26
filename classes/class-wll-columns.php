<?php
/**
 * When Last Login columns.
 *
 * @package when-last-login
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WLL_Columns {

	public function __construct() {

		//Setting up columns.
		add_filter( 'manage_users_columns', array( $this, 'column_header'), 10, 1 );
		add_action( 'manage_users_custom_column', array( $this, 'column_data'), 15, 3 );
		add_filter( 'manage_users_sortable_columns', array( $this, 'column_sortable' ) );
		add_action( 'pre_get_users', array( $this, 'sort_by_login_date') );

		add_filter( 'manage_wll_records_posts_columns' , array( $this, 'wll_records_columns'), 10, 1 );
		add_action( 'manage_wll_records_posts_custom_column' , array( $this, 'wll_records_column_contents' ), 10, 2 );

		// Multisite support.
		add_filter( 'wpmu_users_columns', array( $this, 'column_header'), 10, 1 );
		add_action( 'wpmu_users_custom_column', array( $this, 'column_data'), 15, 3 );
	}


	/**
	 * Setup Column and data for users page with sortable.
	 *
	 * @param array $column The existing columns.
	 * @return array
	 */
	public function column_header( $column ) {
		$settings = When_Last_Login::get_settings();

		$column['when_last_login'] = esc_html__( 'Last Login', 'when-last-login' );

		if ( ! empty( $settings['record_ip_address'] ) ) {
			$column['when_last_login_ip_address'] = esc_html__( 'IP Address', 'when-last-login' );
		}

		return $column;
	}

	/**
	 * Display data in the custom column.
	 *
	 * @param string $value The value of the column.
	 * @param string $column_name The name of the column.
	 * @param int    $id The user ID.
	 * @return string
	 */
	public function column_data( $value, $column_name, $id ) {

		$settings = When_Last_Login::get_settings();

		if ( $column_name == 'when_last_login' ) {

			$when_last_login_meta = get_the_author_meta( 'when_last_login', $id );

			if ( ! empty( $when_last_login_meta ) ) {
				return human_time_diff( $when_last_login_meta );
			} else {
				if ( get_the_author_meta( 'when_last_login', $id ) === 0 ) {
					return esc_html__( 'Never', 'when-last-login' );
				} else {
					update_user_meta( $id, 'when_last_login', 0 );
					return esc_html__( 'Never', 'when-last-login' );
				}
			}
		} else if ( $column_name == 'when_last_login_ip_address' ) {

			$when_last_login_ip_address = get_user_meta( $id, 'wll_user_ip_address', true );

			if ( $when_last_login_ip_address && $when_last_login_ip_address != "" && $settings['record_ip_address'] != "") {
				return "<a href='http://www.ip-adress.com/ip_tracer/". esc_attr( $when_last_login_ip_address ) ."' target='_BLANK' title='".__( 'Lookup', 'when-last-login' )."'>" . esc_html( $when_last_login_ip_address ) . "</a>";
			} else {
				return esc_html__( 'IP Address Not Recorded', 'when-last-login' );
			}
		}
		return $value;
	}

	/**
	 * Add sortable column for last login date.
	 *
	 * @param array $columns The existing columns.
	 * @return array
	 */
	public function column_sortable( $columns ) {
		$columns['when_last_login'] = 'when_last_login';
		return $columns;
	}

	/**
	 * Sort users by last login date.
	 *
	 * @param WP_User_Query $query The user query object.
	 */
	public function sort_by_login_date( $query ) {
		if ( 'when_last_login' == $query->get( 'orderby' ) ) {
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'meta_key', 'when_last_login' );
		}
	}

	/**
	 * Add IP address column to WLL records.
	 *
	 * @param array $columns The existing columns.
	 * @return array
	 */
	public function wll_records_columns( $columns ) {
		return array_merge( $columns, array( 'wll-ip-address' => __( 'IP Address', 'when-last-login' ) ) );
	}

	/**
	 * Display IP address in WLL records column.
	 *
	 * @param string $column The column name.
	 * @param int    $post_id The post ID.
	 */
	public function wll_records_column_contents( $column, $post_id ) {
		switch ( $column ) {
			case 'wll-ip-address':
				$ip_address = get_post_meta( $post_id, 'wll_user_ip_address', true );
				if ( ! empty( $ip_address ) && $ip_address != "" ) {
					echo "<a href='http://www.ip-adress.com/ip_tracer/". esc_attr( $ip_address ) ."' target='_BLANK' title='".__( 'Lookup', 'when-last-login' )."'>" . esc_html( $ip_address ) . "</a>";
				} else {
					esc_html_e( 'IP Address Not Recorded', 'when-last-login' );
				}
			break;
		}
	}

} // end class.
