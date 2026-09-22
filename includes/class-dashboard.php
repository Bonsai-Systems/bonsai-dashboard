<?php
/**
 * class-dashboard.php — replaces wp-admin's default dashboard with a single
 * branded panel: a welcome message plus a quick-links grid.
 *
 * Every default dashboard widget (At a Glance, Activity, Quick Draft,
 * WordPress Events & News, Site Health Status) — and anything a third-party
 * plugin has added to the dashboard — is stripped, not just hidden, so the
 * Bonsai panel is the only thing on the page. WordPress's own welcome panel
 * (the dismissible "Welcome to WordPress" banner) is disabled the same way,
 * since this plugin's own welcome panel replaces it.
 *
 * @package Bonsai_Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bonsai_Dashboard_Widgets {

	const WIDGET_ID = 'bonsai_dashboard_welcome';

	public static function init(): void {
		remove_action( 'welcome_panel', 'wp_welcome_panel' );

		// Priority 10: register our own widget. Priority 999: strip every
		// other dashboard widget (core or third-party) that's registered by
		// then — see strip_other_widgets() below for why this ordering matters.
		add_action( 'wp_dashboard_setup', [ __CLASS__, 'add_widget' ], 10 );
		add_action( 'wp_dashboard_setup', [ __CLASS__, 'strip_other_widgets' ], 999 );

		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );

		// admin_head-{hook_suffix} only fires on the dashboard screen itself —
		// same scoping as enqueue_assets()'s 'index.php' check below, so no
		// extra get_current_screen() guard is needed here.
		add_action( 'admin_head-index.php', [ __CLASS__, 'print_brand_colour_overrides' ] );
	}

	public static function add_widget(): void {
		wp_add_dashboard_widget(
			self::WIDGET_ID,
			__( 'Bonsai Dashboard', 'bonsai-dashboard' ),
			[ __CLASS__, 'render_widget' ]
		);
	}

	/**
	 * Removes every dashboard widget except our own. Explicit remove_meta_box()
	 * calls for the core widgets first (documents intent clearly, and covers
	 * older WP versions' widget IDs), then a generic sweep of $wp_meta_boxes
	 * catches anything a plugin (Jetpack, Yoast, WooCommerce, etc.) has added
	 * — this genuinely replaces the dashboard, not just hides WordPress's own
	 * defaults.
	 */
	public static function strip_other_widgets(): void {
		global $wp_meta_boxes;

		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );      // WordPress Events and News
		remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );    // Other WordPress News (older core)
		remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );  // At a Glance
		remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );   // Activity
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );  // Quick Draft
		remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' ); // deprecated, kept as a no-op safety net
		remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' ); // Site Health Status

		if ( empty( $wp_meta_boxes['dashboard'] ) || ! is_array( $wp_meta_boxes['dashboard'] ) ) {
			return;
		}

		foreach ( $wp_meta_boxes['dashboard'] as $context => $priorities ) {
			if ( ! is_array( $priorities ) ) {
				continue;
			}
			foreach ( $priorities as $priority => $widgets ) {
				if ( ! is_array( $widgets ) ) {
					continue;
				}
				foreach ( array_keys( $widgets ) as $widget_id ) {
					if ( self::WIDGET_ID !== $widget_id ) {
						unset( $wp_meta_boxes['dashboard'][ $context ][ $priority ][ $widget_id ] );
					}
				}
			}
		}
	}

	public static function enqueue_assets( string $hook ): void {
		if ( 'index.php' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'bonsai-dashboard', BONSAI_DASHBOARD_URL . 'assets/dashboard.css', [], BONSAI_DASHBOARD_VERSION );
	}

	/**
	 * Overrides dashboard.css's --bonsai-primary/--bonsai-secondary defaults
	 * from the active theme's Site Settings brand colour fields, when those
	 * ACF fields are actually present. Deliberately reads the ACF options
	 * fields directly rather than requiring the theme to also fire its own
	 * front-end hook in admin_head — this plugin has to work (falling back to
	 * dashboard.css's defaults) on a site whose theme doesn't expose brand
	 * colour fields at all, per ~/.claude/rules/error-handling.md's "never
	 * assume a plugin/theme feature is active" rule.
	 */
	public static function print_brand_colour_overrides(): void {
		if ( ! function_exists( 'get_field' ) ) {
			return; // ACF inactive — dashboard.css's hardcoded defaults still apply.
		}

		$primary   = get_field( 'primary_colour_override', 'option' ) ?: get_field( 'primary_colour', 'option' );
		$secondary = get_field( 'secondary_colour_override', 'option' ) ?: get_field( 'secondary_colour', 'option' );

		$overrides = [];
		if ( ! empty( $primary ) ) {
			$overrides['--bonsai-primary'] = $primary;
		}
		if ( ! empty( $secondary ) ) {
			$overrides['--bonsai-secondary'] = $secondary;
		}

		if ( empty( $overrides ) ) {
			return;
		}

		$css = '.bonsai-dashboard{';
		foreach ( $overrides as $property => $value ) {
			$css .= sprintf( '%s:%s;', $property, esc_html( $value ) );
		}
		$css .= '}';

		printf( "<style id=\"bonsai-dashboard-brand-colours\">%s</style>\n", $css ); // phpcs:ignore -- $css is built entirely from esc_html()'d values above.
	}

	public static function render_widget(): void {
		$settings = Bonsai_Dashboard_Settings::get_settings();
		$user     = wp_get_current_user();

		$heading = $settings['welcome_heading']
			? $settings['welcome_heading']
			/* translators: %s: user's display name. */
			: sprintf( __( 'Welcome back, %s', 'bonsai-dashboard' ), $user->display_name );

		$links = self::get_quick_links( $settings );
		?>
		<div class="bonsai-dashboard">
			<div class="bonsai-dashboard__welcome">
				<h2 class="bonsai-dashboard__heading"><?php echo esc_html( $heading ); ?></h2>
				<?php if ( $settings['welcome_text'] ) : ?>
					<div class="bonsai-dashboard__text">
						<?php
						// wp_kses_post() same as save time (defence in depth, not
						// a substitute for it — see class-settings.php). wpautop()
						// is idempotent against content that already has its own
						// <p> tags (the normal case, from wp_editor()'s visual
						// mode), so it's safe to always run — it only matters
						// when quicktags/plain-text mode was used to type raw
						// line breaks instead.
						echo wp_kses_post( wpautop( $settings['welcome_text'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses_post() is the escaping call here, see comment above.
						?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $links ) ) : ?>
				<div class="bonsai-dashboard__grid">
					<?php foreach ( $links as $link ) : ?>
						<a
							class="bonsai-dashboard__card"
							href="<?php echo esc_url( $link['url'] ); ?>"
							<?php echo ! empty( $link['external'] ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
						>
							<span class="dashicons <?php echo esc_attr( $link['icon'] ); ?>" aria-hidden="true"></span>
							<span class="bonsai-dashboard__card-label"><?php echo esc_html( $link['label'] ); ?></span>
							<?php if ( ! empty( $link['sub'] ) ) : ?>
								<span class="bonsai-dashboard__card-sub"><?php echo esc_html( $link['sub'] ); ?></span>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Pages/Posts/Theme Setup always show (core post types and the theme's
	 * general settings screen are always present). Team only shows when its
	 * source post type actually exists on this site — the slug is
	 * configurable under Settings → Bonsai Dashboard since not every theme
	 * names it the same way, per ~/.claude/rules/error-handling.md's "never
	 * assume a plugin/theme feature is active" rule, rather than linking to a
	 * 404/permission-denied screen on a site that doesn't have it. Analytics
	 * only shows once its URL is configured. Custom cards (Settings → Bonsai
	 * Dashboard's repeater) are appended after Analytics, in the order saved,
	 * before Support — Support always shows last since it falls back to the
	 * Bonsai Zendesk default (see class-settings.php).
	 *
	 * @param array $settings Bonsai_Dashboard_Settings::get_settings().
	 * @return array[] Each: label, url, icon (dashicon class), optional
	 *                 external (bool) and sub (secondary line of text).
	 */
	private static function get_quick_links( array $settings ): array {
		$links = [
			[
				'label' => __( 'Pages', 'bonsai-dashboard' ),
				'url'   => admin_url( 'edit.php?post_type=page' ),
				'icon'  => 'dashicons-admin-page',
			],
			[
				'label' => __( 'Posts', 'bonsai-dashboard' ),
				'url'   => admin_url( 'edit.php' ),
				'icon'  => 'dashicons-admin-post',
			],
			[
				'label' => __( 'Theme Setup', 'bonsai-dashboard' ),
				'url'   => admin_url( 'admin.php?page=theme-general-settings' ),
				'icon'  => 'dashicons-admin-tools',
			],
		];

		$team_cpt_slug = $settings['team_cpt_slug'] ?: Bonsai_Dashboard_Settings::DEFAULT_TEAM_CPT_SLUG;

		if ( post_type_exists( $team_cpt_slug ) ) {
			$links[] = [
				'label' => __( 'Team', 'bonsai-dashboard' ),
				'url'   => admin_url( 'edit.php?post_type=' . $team_cpt_slug ),
				'icon'  => 'dashicons-groups',
			];
		}

		if ( $settings['analytics_url'] ) {
			$links[] = [
				'label'    => __( 'Analytics', 'bonsai-dashboard' ),
				'url'      => $settings['analytics_url'],
				'icon'     => 'dashicons-chart-bar',
				'external' => true,
			];
		}

		foreach ( $settings['custom_cards'] as $card ) {
			$links[] = [
				'label'    => $card['label'],
				'url'      => $card['url'],
				'icon'     => $card['icon'] ?: Bonsai_Dashboard_Settings::DEFAULT_CARD_ICON,
				'external' => ! empty( $card['external'] ),
			];
		}

		$links[] = [
			'label'    => __( 'Support', 'bonsai-dashboard' ),
			'url'      => $settings['support_url'] ?: Bonsai_Dashboard_Settings::DEFAULT_SUPPORT_URL,
			'icon'     => 'dashicons-sos',
			'external' => true,
		];

		return $links;
	}
}
