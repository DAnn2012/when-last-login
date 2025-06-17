<?php
/**
 * When Last Login Admin Class
 *
 * @package when-last-login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WLL_Admin
 */
class WLL_Admin {
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 9 );
		add_action( 'admin_notices', array( $this, 'wll_admin_notices' ) );
		add_action( 'admin_init', array( $this, 'add_admin_settings' ) );
	}

	/**
	 * Add admin menu.
	 *
	 * If the "hide_menu" setting is enabled we don't add the high level menu items.
	 */
	public function add_admin_menu() {

		$settings  = When_Last_Login::get_settings();
		$hide_menu = isset( $settings['hide_menu'] ) && 1 === $settings['hide_menu'] ? true : false;

		if ( ! $hide_menu ) { // If the setting is not enabled we add the high level menu items.

			add_menu_page(
				__( 'When Last Login', 'when-last-login' ),
				esc_html__( 'When Last Login', 'when-last-login' ),
				'manage_options',
				'when-last-login-settings',
				array( $this, 'wll_settings_callback' ),
				'dashicons-visibility'
			);

			add_submenu_page(
				'when-last-login-settings',
				esc_html__( 'Settings', 'when-last-login' ),
				__( 'Settings', 'when-last-login' ),
				'manage_options',
				'when-last-login-settings',
				array( $this, 'wll_settings_callback' )
			);

			add_submenu_page(
				'when-last-login-settings',
				esc_html__( 'Extensions', 'when-last-login' ),
				__( 'Extensions', 'when-last-login' ),
				'manage_options',
				'admin.php?page=when-last-login-settings&tab=add-ons'
			);

			if ( When_Last_Login::show_login_records() ) {
				add_submenu_page(
					'when-last-login-settings',
					esc_html__( 'Login Records', 'when-last-login' ),
					esc_html__( 'All Login Records', 'when-last-login' ),
					'manage_options',
					'wll-records',
					array( 'WLL_Records', 'render_page' )
				);
			}
		} else {
			add_submenu_page(
				When_Last_Login::get_admin_slug(),
				__( 'When Last Login', 'when-last-login' ),
				esc_html__( 'When Last Login', 'when-last-login' ),
				'manage_options',
				'when-last-login-settings',
				array( $this, 'wll_settings_callback' ),
			);

			if ( When_Last_Login::show_login_records() ) {
				add_submenu_page(
					'users.php',
					esc_html__( 'Login Records', 'when-last-login' ),
					esc_html__( 'All Login Records', 'when-last-login' ),
					'manage_options',
					'wll-records',
					array( 'WLL_Records', 'render_page' )
				);
			}
		}

		do_action( 'wll_settings_admin_menu_item' );
	}

	/**
	 * Admin page callback
	 */
	public function wll_settings_callback() {
		$tabs = array(
			'general' => array(
				'title' => __( 'General', 'when-last-login' ),
				'icon'  => '',
			),
			'add-ons' => array(
				'title' => __( 'Add-ons', 'when-last-login' ),
				'icon'  => '',
			),
		);

		$tabs        = apply_filters( 'wll_settings_page_tabs', $tabs );
		$current_tab = isset( $_GET['tab'] ) && isset( $tabs[ $_GET['tab'] ] ) ? $_GET['tab'] : array_key_first( $tabs );
		?>
		<div class="wrap">
			<div id="wll-setting-header">
				<img src="<?php echo esc_attr( WLL_PLUGIN ) . '/includes/images/whenlastlogin.png'; ?>" width="300px" height="auto" style="margin-top:0%;"/><span style="position:relative;top:-15px;"><?php echo 'v' . esc_html( WLL_VER ); ?></span>
			</div>
			<form method="post" action="options.php">
				<nav class="nav-tab-wrapper">
					<?php foreach ( $tabs as $key => $tab ) :
						$current = $key === $current_tab ? ' nav-tab-active' : '';
						$url = add_query_arg( array( 'page' => 'when-last-login-settings', 'tab' => $key ), admin_url( When_Last_Login::get_admin_slug() ) );
						echo "<a class=\"nav-tab{$current}\" href=\"{$url}\">{$tab['title']}</a>";
					endforeach;
					?>
				</nav>

				<?php
				settings_fields( 'wll_settings_group' );
				do_settings_sections( "wll_settings_page_{$current_tab}" );
				submit_button( __( 'Save settings', 'when-last-login' ), "wll-button-{$current_tab} button-primary" );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Admin Notices.
	 */
	public function wll_admin_notices() {
		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
			add_settings_error( 'wll_settings', 'wll_settings_updated', __( 'Settings saved.', 'when-last-login' ), 'updated' );
		}
		settings_errors( 'wll_settings' );
	}

	/**
	 * Register settings
	 */
	public function add_admin_settings() {
		$options = When_Last_Login::get_settings();
		// Register single option array.
		register_setting( 'wll_settings_group', 'wll_settings', array( $this, 'sanitize_settings' ) );

		// General settings.
		add_settings_section( 'wll_section_general', __( 'Options', 'when-last-login' ), '', 'wll_settings_page_general' );
		add_settings_field(
			'record_ip_address',
			__( "Record user's IP address", 'when-last-login' ),
			array( $this, 'admin_checkbox_field' ),
			'wll_settings_page_general',
			'wll_section_general',
			array(
				'options' => $options,
				'name'    => 'record_ip_address',
				'label'   => __( 'This will anonymize the IP address to support GDPR regulations.', 'when-last-login' ),
			)
		);

		add_settings_field(
			'show_all_login_records',
			__( 'Enable "All Login Records"', 'when-last-login' ),
			array( $this, 'admin_checkbox_field' ),
			'wll_settings_page_general',
			'wll_section_general',
			array(
				'options' => $options,
				'name'    => 'show_all_login_records',
				'label'   => __( 'Please enable this option if using the', 'when-last-login' ) . " <a href='https://yoohooplugins.com/plugins/when-last-login-user-statistics/' target='_blank'><strong>" . esc_html( 'When Last Login - User Statistics Add On', 'when-last-login' ) . "</strong></a>",
			)
		);

		add_settings_field(
			'hide_menu',
			__( 'Top Level Menu', 'when-last-login' ),
			array( $this, 'admin_checkbox_field' ),
			'wll_settings_page_general',
			'wll_section_general',
			array(
				'options' => $options,
				'name'    => 'hide_menu',
				'label'   => __( 'Enabling this will move the When Last Login menu iten under the WordPress Tools menu.', 'when-last-login' ),
			)
		);

		add_settings_field(
			'delete_data',
			__( 'Delete data on uninstall', 'when-last-login' ),
			array( $this, 'admin_checkbox_field' ),
			'wll_settings_page_general',
			'wll_section_general',
			array(
				'options' => $options,
				'name'    => 'delete_data',
				'label'   => __( 'Permanently delete all settings and data when uninstalling the plugin.', 'when-last-login' ),
			)
		);

		add_settings_section( 'wll_section_tools', __( 'Tools', 'when-last-login' ), array( $this, 'wll_settings_page_tools_calback' ), 'wll_settings_page_general' );

		// Adons settings.
		add_settings_section( 'wll_section_add-ons', '', array( $this, 'wll_settings_page_addons_calback' ), 'wll_settings_page_add-ons' );
	}

	/**
	 * Checkbox field callback
	 *
	 * @param array $args Field arguments.
	 */
	public function admin_checkbox_field( $args ) {
		$value = isset( $args['options'][ $args['name'] ] ) ? $args['options'][ $args['name'] ] : '';
		$checked = in_array( $value, array( 'yes', 1 ) ) ? 'checked' : '';
		?>
		<label>
			<input type="checkbox" name="wll_settings[<?php echo esc_attr( $args['name'] ); ?>]" value="yes" <?php echo $checked; ?> />
			<?php echo $args['label']; ?>
		</label>
		<?php
	}

	/**
	 * Sanitize settings callback
	 */
	public function sanitize_settings( $input ) {
		$saved_options = When_Last_Login::get_settings();

		if ( ! isset( $_POST['wll_settings'] ) ) {
			return $saved_options;
		}

		// Define checkbox fields you want to track.
		$checkbox_fields = array(
			'record_ip_address',
			'show_all_login_records',
			'hide_menu',
			'delete_data',
		);

		if ( ! is_array( $saved_options ) ) {
			$saved_options = array();
		}

		// Handle checkboxes (checked or unchecked).
		foreach ( $checkbox_fields as $field ) {
			$saved_options[ $field ] = isset( $input[ $field ] ) ? 1 : 0;
		}

		// Handle other fields dynamically (text fields etc.)
		foreach ( $input as $key => $value ) {
			if ( ! in_array( $key, $checkbox_fields ) ) {
				$saved_options[ $key ] = sanitize_text_field( $value );
			}
		}

		return $saved_options;
	}

	/**
	 * Tools callback
	 */
	public function wll_settings_page_tools_calback() {
		?>

			<table class="form-table">
				<?php
					$old_records_message = esc_html__( 'Are you sure you want to remove all records older than 3 months?', 'when-last-login' );
					$all_records_message = esc_html__( 'Are you sure you want to remove all login records?', 'when-last-login' );
					$all_ip_message      = esc_html__( 'Are you sure you want to remove all IP addresses?', 'when-last-login' );


					$remove_records_nonce     = wp_create_nonce( 'wll_remove_records_nonce' );
					$remove_all_records_nonce = wp_create_nonce( 'wll_remove_all_records_nonce' );
					$remove_ip_nonce          = wp_create_nonce( 'wll_remove_ip_nonce' );
				?>
				<tr>
					<th><?php esc_html_e( 'Clear old logs', 'when-last-login' ); ?></th>
					<td><a href="javascript:void(0);" onclick="wll_remove_old_records(); return false;" class="button-primary"><?php esc_html_e( 'Run Now', 'when-last-login' ); ?></a></td>
				</tr>

				<tr>
					<th><?php esc_html_e( 'Clear all logs', 'when-last-login' ); ?></th>
					<td><a href="javascript:void(0);" onclick="wll_remove_all_records(); return false;" class="button-primary"><?php esc_html_e( 'Run Now', 'when-last-login' ); ?></a></td>
				</tr>

				<tr>
					<th><?php esc_html_e( 'Clear all IP Addresses', 'when-last-login' ); ?></th>
					<td><a href="javascript:void(0);" onclick="wll_remove_all_ips(); return false;" class="button-primary"><?php esc_html_e( 'Run Now', 'when-last-login' ); ?></a></td>
				</tr>
			</table>

			<script>
				function wll_remove_old_records(){
					if( window.confirm('<?php echo $old_records_message; ?>')) {
						window.location.href = "<?php echo add_query_arg( array( 'remove_wll_records' => '1', 'wll_remove_records_nonce' => $remove_records_nonce ), admin_url( 'admin.php?page=when-last-login-settings' ) ); ?>";
					}
				}

				function wll_remove_all_records(){
					if( window.confirm('<?php echo $all_records_message; ?>')) {
						window.location.href = "<?php echo add_query_arg( array( 'remove_all_wll_records' => '1', 'wll_remove_all_records_nonce' => $remove_all_records_nonce ), admin_url( 'admin.php?page=when-last-login-settings' ) ); ?>";
					}
				}

				function wll_remove_all_ips(){
					if( window.confirm('<?php echo $all_ip_message; ?>')) {
						window.location.href = "<?php echo add_query_arg( array( 'remove_wll_ip_addresses' => '1', 'wll_remove_ip_nonce' => $remove_ip_nonce ), admin_url( 'admin.php?page=when-last-login-settings' ) ); ?>";
					}
				}
			</script>

		<?php
	}

	public function wll_settings_page_addons_calback() {
		$content = get_transient( 'when_last_login_add_ons_page' );

		if ( false === $content || $content == '' ) {

			$url = 'https://yoohooplugins.com/api/add-ons-when-last-login/v1/products.php';

			$add_ons_request = wp_remote_get( esc_url_raw( $url ), array( 'sslverify' => false ) );

			if ( ! is_wp_error( $add_ons_request ) ) {

				if ( isset( $add_ons_request['body'] ) && strlen( $add_ons_request['body'] ) > 0 ) {

					$content = wp_remote_retrieve_body( $add_ons_request );

					set_transient( 'when_last_login_add_ons_page', $content, 3600 );

				}

			} else {

				$content = '<div class="error"><p>' . __( 'An error occurred while retrieving the extensions list from the server. Please try again later.', 'when-last-login' ) . '</div>';

			}

		}

		echo $content;
	}

}
