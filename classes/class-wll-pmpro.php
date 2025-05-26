<?php
/**
 * When Last Login related functionality with PMPRO.
 *
 * @package when-last-login
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WLL_PMPRO {

	public function __construct() {

		//Integration for Paid Memberships Pro.
		//TODO: Improve integration with Member List and Paid Memberships Pro.
		add_action( 'pmpro_memberslist_extra_cols_header', array( $this, 'pmpro_memberlist_add_header' ) );
		add_action( 'pmpro_memberslist_extra_cols_body', array( $this, 'pmpro_memberlist_add_column_data' ) );
	}

	/*
	 * Support for Paid Memberships Pro
	 * TODO: use existing PMPro usermeta if installed
	 */
	public static function pmpro_memberlist_add_header( $users ) {
		if ( !defined( 'PMPRO_VERSION' ) ) {
			return;
		}
		?>
		<th><?php esc_html_e( 'Last Login', 'when-last-login' );?></th>
		<?php

	}

	public static function pmpro_memberlist_add_column_data( $users ) {
		if ( !defined( 'PMPRO_VERSION' ) ) {
			return;
		}
		?>
		<td>
			<?php
			if ( ! empty( $users->when_last_login ) ) {
				echo human_time_diff( $users->when_last_login );
			}else{
				return esc_html_e( 'Never', 'when-last-login' );
			}
			?>
		</td>
		<?php
	}

}
