<?php
/**
 * uninstall.php — removes this plugin's one settings option. Nothing else
 * is stored (dashboard widget removal/registration is runtime-only, not
 * persisted state).
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'bonsai_dashboard_settings' );
