<?php
/**
 * uninstall.php — removes this plugin's options and the "Agency user" flag
 * from every user. Dashboard widget removal/registration is runtime-only,
 * not persisted state, so there's nothing else to clean up.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'bonsai_dashboard_settings' );
delete_option( 'bonsai_dashboard_white_label' );
delete_option( 'bonsai_dashboard_hidden_menus' );

// Bonsai_Dashboard_Admin_Access::META — the class isn't loaded during uninstall.
delete_metadata( 'user', 0, 'bonsai_dashboard_agency_user', '', true );
