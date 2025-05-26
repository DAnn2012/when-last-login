<?php
/**
 * When Last Login user registration and login functionality.
 *
 * @package when-last-login
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WLL_User_Registration_Login {

	/**
	 * The single instance of the class.
	 */
	public function __construct() {
		add_action( 'wp_login', array( $this, 'last_login'), 10, 2 );
		add_action( 'user_register', array( $this, 'wll_user_register' ), 10, 1 );
	}


	public static function last_login( $user_login, $users ) {

		global $wpdb;

		$record_login = apply_filters( 'wll_record_login', true, $users, $user_login );

		// If filter isn't true, don't record login at all!
		if ( ! $record_login ) {
			return;
		}

		//get/update user meta 'when_last_login' on login and add time() to it.
		update_user_meta( $users->ID, 'when_last_login', time() );

		//get and update user meta 'when_last_login_count' on login for # of login counts. Thanks to Jarryd Long (@jarrydlong) for the assistance
		$wll_count = get_user_meta( $users->ID, 'when_last_login_count', true );

		if ( $wll_count === false ) {
			update_user_meta( $users->ID, 'when_last_login_count', 1 );
		} else {
			$wll_new_value = intval( $wll_count );
			$wll_new_value = $wll_new_value + 1;

			update_user_meta( $users->ID, 'when_last_login_count', $wll_new_value );
		}

		$wll_settings = When_Last_Login::get_settings();

		$ip_address = '';

		if ( isset( $wll_settings['record_ip_address'] ) && intval( $wll_settings['record_ip_address'] ) == 1 ) {
			// call function to anonymize here.
			$ip_address = When_Last_Login::wll_get_user_ip_address();
			update_user_meta( $users->ID, 'wll_user_ip_address', $ip_address );
		}

		if ( When_Last_Login::show_login_records() ) {

			$wpdb->insert(
				$wpdb->prefix . 'wll_login_records',
				[
					'user_id'    => $users->ID,
					'login_time' => strtotime( "now" ),
					'ip_address' => $ip_address,
				],
				[ '%d', '%d', '%s' ]
			);
		}

		do_action( 'wll_logged_in_action', array( 'login_count' => $wll_new_value, 'user' => $users ), $wll_settings );

	}

	/**
	 * Function to handle user registration and store IP address if enabled.
	 */
	public function wll_user_register( $user_id ) {

		$wll_settings = When_Last_Login::get_settings();

		if ( isset( $wll_settings['record_ip_address'] ) && $wll_settings['record_ip_address'] == 1 ) {
			$ip = When_Last_Login::wll_get_user_ip_address();
			update_user_meta( $user_id, 'wll_user_ip_address', $ip );
		}

		do_action( 'wll_register_action', $user_id, $wll_settings );

	}
}
