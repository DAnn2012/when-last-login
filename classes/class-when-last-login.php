<?php
/**
 * When Last Login.
 *
 * @package when-last-login
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use geertw\IpAnonymizer\IpAnonymizer;

class When_Last_Login {

	/**
	 * Refers to a single instance of this class.
	 */
	private static $instance = null;

	/**
	* Initializes the plugin by setting localization, filters, and administration functions.
	*/
	private function __construct() {

		include WLL_DIR_PATH . '/includes/lib/IpAnonymizer.php';
		include WLL_DIR_PATH . '/includes/privacy-policy.php';

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'plugins_loaded', array( $this, 'text_domain' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_js_for_notice' ) );
		add_action( 'admin_notices', array( $this, 'update_notice' ) );
		add_action( 'wp_ajax_wll_hide_subscription_notice', array( $this, 'wll_hide_subscription_notice' ) );
		add_action( 'admin_head', array( $this, 'wll_settings_page_head' ) );
		add_action( 'admin_init', array( $this, 'wll_automatically_remove_logs' ) );
	}

	/**
	* Creates or returns an instance of this class.
	*
	* @return  When_Last_Login A single instance of this class.
	*/
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Get the plugins settings.
	 *
	 * @param string $key The key to get.
	 *
	 * @return array|string The settings or the value of the key.
	 */
	public static function get_settings( $key = '' ) {
		$settings = get_option( 'wll_settings', true );
		if ( ! empty( $key ) && isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}
		return $settings;
	}

	public static function get_admin_slug() {
		if ( ! self::get_settings('hide_menu') ) {
			return 'admin.php';
		}
		return 'tools.php';
	}

	/**
	* When Last plugin functions.
	*/
	public static function admin_init() {
		//init function.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		do_action( 'wll_upgrade_check' );

		$current_version = floatval( get_option( 'wll_current_version' ) );

		// Clean up stuff for version 1.0.
		if ( $current_version < 1.0 || empty( $current_version ) ) {

			global $wpdb;

			$delete_table = $wpdb->prefix . 'wll_login_attempts' ;
			$sql = "DROP TABLE IF EXISTS `$delete_table`";
			$wpdb->query( $sql );

			delete_transient( 'when_last_login_add_ons_page' );

			// on upgrade remove the notice save.
			delete_option( 'wll_notice_hide' );
			delete_option( 'wll_notice_hide_1' );
			delete_option( 'wll_notice_hide_2' );

			// update version number to 1.0.
			update_option( 'wll_current_version', 1.2 );
		}
	}

	/**
	 * Load the plugin text domain for translations.
	 */
	public static function text_domain() {
		load_plugin_textdomain( 'when-last-login', false, dirname( 'WLL_BASE_NAME' ) . '/languages' );
	}

	/**
	 * Display an update notice in the admin area.
	 */
	public static function update_notice() {

		if ( get_option( 'wll_notice_hide' ) != '1' && ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] == 'when-last-login-settings' ) ) {
			?>
			<div class="notice notice-success  wll-update-notice-newsletter is-dismissible" >
			<h3><?php _e('Thank you for using When Last Login', 'when-last-login'); ?></h3>
			<p><?php  _e( sprintf( 'Please consider leaving an honest review for When Last Login by visiting %s', '<a href="'. esc_url( 'https://wordpress.org/support/plugin/when-last-login/reviews/#new-post' ) . '" target="_blank">this link</a>' ), 'when-last-login' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Hide the subscription notice.
	 */
	public function wll_hide_subscription_notice() {
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'wll_hide_notice_nonce' ) ) {
			wp_die( __( 'Nonce is invalid', 'pmpro-pdf-invoices' ) );
		}
		update_option( 'wll_notice_hide', '1' );
	}

	/**
	 * Load the JavaScript for the notice.
	 */
	public static function load_js_for_notice() {
		if ( get_option( 'wll_notice_hide' ) !== '1') {
			wp_enqueue_script( 'wll_notice_update', plugins_url( '../js/notice-update.js', __FILE__ ), array( 'jquery' ), '1.0', false );

			wp_localize_script( 'wll_notice_update', 'wll_notice_update', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'wll_hide_notice_nonce' )
			) );
		}
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'when-last-login-settings' ) {
			wp_enqueue_style( 'wll_admin_settings_styles', plugins_url( '../css/admin.css', __FILE__ ) );
		}
	}

	public function wll_settings_page_head() {

		$wll_settings = array();

		if ( isset( $_POST['wll_save_settings'] ) ) {

			if ( wp_verify_nonce( $_POST['_nonce'], 'wll_settings_nonce' ) ) {

				$wll_settings['user_access'] = isset( $_POST['wll_login_record_user_access'] ) ? sanitize_text_field( $_POST['wll_login_record_user_access'] ) : "";
				$wll_settings['record_ip_address'] = isset( $_POST['wll_record_user_ip_address'] ) && sanitize_text_field( $_POST['wll_record_user_ip_address'] ) == '1'  ? 1 : 0;
				$wll_settings['show_all_login_records'] = isset( $_POST['wll_all_login_records'] ) && sanitize_text_field( $_POST['wll_all_login_records'] ) == '1'  ? 1 : 0;

				$wll_settings = apply_filters( 'wll_settings_filter', $wll_settings );

				if ( update_option( 'wll_settings', $wll_settings ) ) {
					//show admin notice here.
					add_action( 'admin_notices', array( $this, 'wll_admin_notices' ) );
				}
			} else {
				die( 'nonce not valid' );
			}

		}

	}

	public function wll_admin_notices() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Settings saved successfully.', 'when-last-login' ); ?></p>
		</div>
		<?php
	}

	public function wll_remove_records_notice__success() {
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Records have been removed successfully.', 'when-last-login' ); ?></p>
		</div>
		<?php
	}

	public function wll_remove_records_notice__warning() {
		?>
		<div class="notice notice-warning is-dismissible">
			<p><?php esc_html_e( 'No old records to remove.', 'when-last-login' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Function to remove logs automatically older than 3 months.
	 * @since 1.0.0
	 */
	public function wll_automatically_remove_logs() {
		global $pagenow, $wpdb;

		// Bail if there is not ?page=xxx parameter
		if ( empty( $_GET['page'] ) ) {
			return;
		}

		// Bail if not on our settings page
		if ( 'admin.php' == $pagenow && 'when-last-login-settings' != $_GET['page'] ) {
			return;
		}

		$sql = "DELETE p, pm FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pm ON pm.post_id = p.ID WHERE p.post_type = 'wll_records'";

		if ( isset( $_REQUEST['remove_all_wll_records'] ) ) {

			$nonce = $_REQUEST['wll_remove_all_records_nonce'];
			if ( wp_verify_nonce( $nonce, 'wll_remove_all_records_nonce' ) ) {

				if ( $wpdb->query( $sql ) > 0 ) {
					add_action( 'admin_notices', array( $this, 'wll_remove_records_notice__success' ) );
				} else {
					add_action( 'admin_notices', array( $this, 'wll_remove_records_notice__warning' ) );
				}
			} else {
				die( 'nonce not valid.' );
			}
		}

		if ( isset( $_REQUEST['remove_wll_records'] ) ) {

			$nonce = $_REQUEST['wll_remove_records_nonce'];
			if ( wp_verify_nonce( $nonce, 'wll_remove_records_nonce' ) ) {

				$date = apply_filters( 'wll_automatically_remove_logs_date', date( 'Y-m-d', strtotime( '-3 months' ) ) );

				$sql .= " AND p.post_date <= '$date'";

				if ( $wpdb->query( $sql ) > 0 ) {
					add_action( 'admin_notices', array( $this, 'wll_remove_records_notice__success' ) );
				} else {
					add_action( 'admin_notices', array( $this, 'wll_remove_records_notice__warning' ) );
				}
			} else {
				die( 'nonce not valid.' );
			}
		}

		if ( isset( $_REQUEST['remove_wll_ip_addresses'] ) ) {

			$nonce = $_REQUEST['wll_remove_ip_nonce'];
			if ( wp_verify_nonce( $nonce, 'wll_remove_ip_nonce' ) ) {

				$sql = "DELETE FROM $wpdb->usermeta WHERE meta_key = 'wll_user_ip_address'";

				if ( $wpdb->query( $sql ) > 0 ) {
					add_action( 'admin_notices', array( $this, 'wll_remove_records_notice__success' ) );
				} else {
					add_action( 'admin_notices', array( $this, 'wll_remove_records_notice__warning' ) );
				}
			} else {
				die( 'nonce not valid.' );
			}
		}
	}

	public static function wll_get_user_ip_address() {

		if ( !empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} else if ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		$ip = apply_filters( 'wll_user_ip_address', $ip );

		if ( apply_filters( 'wll_force_anon_ip', false ) ) {
			return $ip;
		} else {
			return IpAnonymizer::anonymizeIp( $ip );
		}

		return IpAnonymizer::anonymizeIp( $ip );
	}

} // end class
