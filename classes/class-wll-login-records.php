<?php
/**
 * When Last Login Records Table
 *
 * @package when-last-login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WLL_Records extends WP_List_Table {

	/**
	 * Constructor for the WLL_Records class.
	 *
	 * This constructor initializes the WP_List_Table with the necessary parameters
	 * and ensures that the WP_List_Table class is loaded.
	 */
	public function __construct() {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}
		parent::__construct( [
			'singular' => 'record',
			'plural'   => 'records',
			'ajax'     => false,
		] );
	}

	/**
	 * Register the admin page for viewing login records.
	 *
	 * This method adds a new menu item to the WordPress admin dashboard under the "Users" menu.
	 * It allows administrators to view all login records stored in the database.
	 */
	public static function register_page() {
		add_menu_page(
			'All Login Records',
			'All Login Records',
			'manage_options',
			'wll-records',
			[ self::class, 'render_page' ],
			'dashicons-database',
			26
		);
	}

	/**
	 * Render the login records page.
	 *
	 * This method is called when the admin page is accessed. It initializes the table,
	 * prepares the items, and displays the table within a WordPress admin page.
	 */
	public static function render_page() {
		$table = new self();
		$table->prepare_items();

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . __( 'Login Records', 'when-last-login' ) . '</h1>';
		echo '<form method="post">';
		$table->display();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Get the sortable columns for the table.
	 *
	 * @return array Associative array of sortable columns.
	 */
	public function get_sortable_columns() {
		return [
			'user_id'    => [ 'user_id', false ],
			'login_time' => [ 'login_time', true ],
			'ip_address' => [ 'ip_address', false ],
		];
	}

	/**
	 * Prepare the items for display.
	 *
	 * This method retrieves the login records from the database, applies sorting and pagination,
	 * and sets the items to be displayed in the table.
	 */
	public function prepare_items() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wll_login_records';

		$columns  = $this->get_columns();
		$hidden   = [];
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = [ $columns, $hidden, $sortable ];

		// Pagination variables
		$per_page     = 10;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// Get orderby and order from URL, default to login_time DESC
		$valid_columns = array_keys( $columns );
		$orderby = ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], $valid_columns, true ) ) ? sanitize_text_field( $_GET['orderby'] ) : 'login_time';

		$order = ( isset( $_GET['order'] ) && strtolower( $_GET['order'] ) === 'asc' ) ? 'ASC' : 'DESC';

		// Get total items count
		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );

		// Prepare query with orderby and order
		// IMPORTANT: whitelist columns to avoid SQL injection
		if ( ! in_array( $orderby, $valid_columns, true ) ) {
			$orderby = 'login_time';
		}

		// Build SQL safely
		$sql = $wpdb->prepare( "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $offset );

		$items = $wpdb->get_results( $sql, ARRAY_A );

		$this->items = $items;

		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		] );
	}

	/**
	 * Get the columns for the table.
	 *
	 * @return array Associative array of column names and their labels.
	 */
	public function get_columns() {
		return [
			'user_id'    => 'Username',
			'login_time' => 'Login Time',
			'ip_address' => 'IP Address',
		];
	}

	/**
	 * Render a single column in the table.
	 *
	 * @param array  $item The item data.
	 * @param string $column_name The name of the column to render.
	 * @return string The formatted column value.
	 */
	public function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
	}

	/**
	 * Format the user ID for display.
	 *
	 * @param array $item The item data.
	 * @return string Formatted user link or 'Unknown' if user not found.
	 */
	public function column_user_id( $item ) {
		$user = get_userdata( $item['user_id'] );
		if ( ! $user ) return 'Unknown';

		$url = get_edit_user_link( $user->ID );
		return '<a href="' . esc_url( $url ) . '">' . esc_html( $user->user_login ) . '</a>';
	}

	/**
	 * Format the IP address for display.
	 *
	 * @param array $item The item data.
	 * @return string Formatted IP address or a message if not recorded.
	 */
	public function column_ip_address( $item ) {
		$value = ! empty( $item['ip_address'] ) ? $item['ip_address'] : __( 'IP Address Not Recorded', 'when-last-login' );
		// If you meant IP address
		return esc_html( $value );
	}

	/**
	 * Format the login time for display.
	 *
	 * @param array $item The item data.
	 * @return string Formatted login time.
	 */
	public function column_login_time( $item ) {
		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		return wp_date( $format, $item['login_time'] );
	}
}

// Register the admin page at the proper time
add_action( 'admin_menu', [ 'WLL_Records', 'register_page' ] );
