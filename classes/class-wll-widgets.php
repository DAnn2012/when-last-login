<?php
/**
 * When Last Login Dashboard Widgets.
 *
 * @package when-last-login
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WLL_Widgets {


	/**
	* Initializes the plugin by setting localization, filters, and administration functions.
	*/
	public function __construct() {

		//Admin actions.
		add_action( 'wp_dashboard_setup', array( $this, 'admin_dashboard_widget' ) );

		// Multisite support.
		add_action( 'wp_network_dashboard_setup', array( $this, 'admin_dashboard_widget' ) );

	}

	/**
	 * Setup admin backend to display custom meta box for login count for admins
	 */
	public static function admin_dashboard_widget() {

		global $show_widget;

		$show_widget = apply_filters( 'when_last_login_show_admin_widget', true );
		//only show for administrators
		if ( current_user_can( 'manage_options' ) && $show_widget ) {
			wp_add_dashboard_widget( 'when_last_login_top_users', __( 'Most Frequent Logins', 'when-last-login' ), array( 'WLL_Widgets', 'admin_dashboard_widget_display' ) );
		}
	}

	public static function admin_dashboard_widget_display() {

		global $show_widget, $show_login_records;

		if ( $show_widget != true ) {
			return;
		}

		if ( is_network_admin() ) {

			$sites = get_sites();

			if ( is_array( $sites ) ) {

				foreach ( $sites as $site ) {

					$blog_id = $site->blog_id;
					$blog_details = get_blog_details( $blog_id );

					?><table width="100%" text-align="center" class='wp-list-table striped widefat'>
					<tr>
						<th colspan='4' style='text-align: center;'><strong><?php echo esc_html( $blog_details->blogname ) .' (<a href="'. esc_url( $blog_details->siteurl ).'" target="_BLANK">'. esc_html( $blog_details->siteurl ) . ')</a>'; ?></strong></th>
					</tr>
					<?php

					$user_query = new WP_User_Query( array( 'meta_key' => 'when_last_login_count', 'meta_value' => 0, 'meta_compare' => '!=', 'order' => 'DESC', 'orderby' => 'meta_value_num', 'number' => apply_filters( 'wll_top_widget_user_count', 3 ), 'blog_id' => $blog_id, 'role__not_in' => array( 'administrator' ) ) );

					$topusers = $user_query->get_results();

					if ( $topusers ) {
						?>
						<tr>
							<th><strong>#</strong></th>
							<th><strong><?php esc_html_e( 'Users', 'when-last-login' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Login Count', 'when-last-login' ); ?></strong></th>
							<th><strong><?php esc_html_e( 'Last Logged In', 'when-last-login' ); ?></strong></th>
						</tr>
						<?php

						$count = 1;

						foreach ($topusers as $wllusers) {
							echo '<tr><td>' . intval( $count ) . '</td>';
							echo '<td>' . esc_html( $wllusers->display_name ) . '</td>';
							echo '<td>' . get_user_meta( $wllusers->ID, 'when_last_login_count', true ) . '</td>';
							echo '<td>' . date_i18n( 'Y-m-d H:i:s', get_user_meta( $wllusers->ID, 'when_last_login', true ) ) . '</td></tr>';
							$count++;
						}

					} else {

						echo '<tr><td colspan="4">'. esc_html__('No data yet', 'when-last-login').'</td></tr>';

					}

					?></table><br/><?php

				}

				?>

				<a href="<?php echo admin_url( 'users.php?orderby=when_last_login&order=desc' ); ?>"><?php _e( 'View All Users', 'when-last-login' ); ?></a>

				<?php if ( $show_login_records == true ) { ?>
					<a style="float:right" href="<?php echo admin_url( 'edit.php?post_type=wll_records' ); ?>"><?php _e( 'View Login Records', 'when-last-login' ); } //end the if filter check here ?></a>
				<?php

			}

		} else {

			?><table width="100%" text-align="center" class='wp-list-table striped widefat'>

			<?php

			$user_query = new WP_User_Query( array( 'meta_key' => 'when_last_login_count', 'meta_value' => 0, 'meta_compare' => '!=', 'order' => 'DESC', 'orderby' => 'meta_value_num', 'number' => apply_filters( 'wll_top_widget_user_count', 3 ), 'role__not_in' => array( 'administrator' ) ) );

			$topusers = $user_query->get_results();

			if ( $topusers ) {
				?>
					<tr>
						<th><strong>#</strong></th>
						<th><strong><?php esc_html_e( 'Users', 'when-last-login' ); ?></strong></th>
						<th><strong><?php esc_html_e( 'Login Count', 'when-last-login' ); ?></strong></th>
						<th><strong><?php esc_html_e( 'Last Logged In', 'when-last-login' ); ?></strong></th>
					</tr>
				<?php

				$count = 1;

				foreach ($topusers as $wllusers) {
					echo '<tr><td>' . intval( $count ) . '</td>';
					echo '<td>' . $wllusers->display_name . '</td>';
					echo '<td>' . get_user_meta( $wllusers->ID, 'when_last_login_count', true ) . '</td>';
					echo '<td>' . date_i18n( 'Y-m-d H:i:s', get_user_meta( $wllusers->ID, 'when_last_login', true ) ) . '</td></tr>';
					$count++;
				}

			} else {

				echo '<tr><td colspan="4">'. esc_html__( 'No data yet', 'when-last-login' ).'</td></tr>';

			}

			?></table><br/>

			<a href="<?php echo admin_url( 'users.php?orderby=when_last_login&order=desc' ); ?>"><?php esc_html_e( 'View All Users', 'when-last-login' ); ?></a>

			<?php if ( $show_login_records == true ) { ?>
				<a style="float:right" href="<?php echo admin_url( 'edit.php?post_type=wll_records' ); ?>"><?php esc_html_e( 'View Login Records', 'when-last-login' ); } //end the if filter check here ?></a>
			<?php

		}

	}
}
