<?php
/**
 * Plugin Name: Bonsai Dashboard
 * Plugin URI:  https://github.com/Bonsai-Systems/bonsai-dashboard
 * Description: Replaces the default wp-admin dashboard with a branded welcome panel and a quick-links grid (Pages, Posts, Team, Theme Setup, Analytics, Support). Analytics/Support links and the Team post type are configured per site under Settings → Bonsai Dashboard.
 * Version:     1.1.0
 * Author:      Ben Ervine / The Bonsai Digital Collective
 * Author URI:  https://thebonsaidigitalcollective.co.uk
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License:     GPL-2.0-or-later
 * Text Domain: bonsai-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
|--------------------------------------------------------------------------
| Plugin Update Checker (via Composer)
|--------------------------------------------------------------------------
| Updates served from this GitHub repo's tagged releases. Tag a release
| whose version matches the plugin header and WordPress will offer the
| update.
*/
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$bonsai_dashboard_update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/Bonsai-Systems/bonsai-dashboard',
	__FILE__,
	'bonsai-dashboard'
);

$bonsai_dashboard_update_checker->setBranch( 'main' );
$bonsai_dashboard_update_checker->getVcsApi()->enableReleaseAssets();

define( 'BONSAI_DASHBOARD_VERSION', '1.1.0' );
define( 'BONSAI_DASHBOARD_FILE', __FILE__ );
define( 'BONSAI_DASHBOARD_DIR', plugin_dir_path( __FILE__ ) );
define( 'BONSAI_DASHBOARD_URL', plugin_dir_url( __FILE__ ) );

require_once BONSAI_DASHBOARD_DIR . 'includes/class-settings.php';
require_once BONSAI_DASHBOARD_DIR . 'includes/class-admin-page.php';
require_once BONSAI_DASHBOARD_DIR . 'includes/class-dashboard.php';

add_action( 'plugins_loaded', [ 'Bonsai_Dashboard_Plugin', 'init' ] );

final class Bonsai_Dashboard_Plugin {

	public static function init(): void {
		Bonsai_Dashboard_Admin_Page::init();
		Bonsai_Dashboard_Widgets::init();
	}
}
