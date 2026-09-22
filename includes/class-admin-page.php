<?php
/**
 * class-admin-page.php — Bonsai Dashboard's settings screen.
 *
 * A single Settings → Bonsai Dashboard page (this plugin has one small
 * settings form, so it doesn't need its own top-level menu or tabs).
 *
 * @package Bonsai_Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bonsai_Dashboard_Admin_Page {

	const MENU_SLUG = 'bonsai-dashboard';

	/**
	 * Hook suffix returned by add_options_page(), used to scope
	 * enqueue_assets() to this screen only rather than a hardcoded string
	 * that would break if this ever moved out from under Settings.
	 */
	private static string $hook_suffix = '';

	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
		add_action( 'admin_post_bonsai_dashboard_save_settings', [ __CLASS__, 'handle_save_settings' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	public static function register_menu(): void {
		self::$hook_suffix = (string) add_options_page(
			__( 'Bonsai Dashboard', 'bonsai-dashboard' ),
			__( 'Bonsai Dashboard', 'bonsai-dashboard' ),
			'manage_options',
			self::MENU_SLUG,
			[ __CLASS__, 'render_page' ]
		);
	}

	public static function enqueue_assets( string $hook ): void {
		if ( '' === self::$hook_suffix || $hook !== self::$hook_suffix ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'bonsai-dashboard-admin', BONSAI_DASHBOARD_URL . 'assets/admin-settings.css', [], BONSAI_DASHBOARD_VERSION );
		wp_enqueue_script( 'bonsai-dashboard-admin', BONSAI_DASHBOARD_URL . 'assets/admin-settings.js', [ 'jquery', 'wp-color-picker' ], BONSAI_DASHBOARD_VERSION, true );
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'bonsai-dashboard' ) );
		}

		$settings = Bonsai_Dashboard_Settings::get_settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Bonsai Dashboard settings', 'bonsai-dashboard' ); ?></h1>

			<?php if ( isset( $_GET['settings-updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'bonsai-dashboard' ); ?></p></div>
			<?php endif; ?>

			<p class="description">
				<?php esc_html_e( 'These control the Analytics/Support quick links, the Team post type used for the Team quick link, any custom cards, and the welcome message shown on this site\'s wp-admin dashboard. Pages/Posts/Theme Setup always link to this site\'s own admin screens, so there\'s nothing to configure for those.', 'bonsai-dashboard' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'bonsai_dashboard_save_settings', 'bonsai_dashboard_nonce' ); ?>
				<input type="hidden" name="action" value="bonsai_dashboard_save_settings">

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="bonsai-dashboard-welcome-heading"><?php esc_html_e( 'Welcome heading', 'bonsai-dashboard' ); ?></label></th>
						<td>
							<input type="text" id="bonsai-dashboard-welcome-heading" name="settings[welcome_heading]" class="regular-text" value="<?php echo esc_attr( $settings['welcome_heading'] ); ?>" placeholder="<?php echo esc_attr__( 'Welcome back', 'bonsai-dashboard' ); ?>">
							<p class="description"><?php esc_html_e( 'Shown at the top of the dashboard welcome panel. Defaults to a generic greeting if left blank.', 'bonsai-dashboard' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bonsai-dashboard-welcome-text"><?php esc_html_e( 'Welcome text', 'bonsai-dashboard' ); ?></label></th>
						<td>
							<?php
							// Teeny toolbar (bold/italic/links/lists only). Falls
							// back to a plain textarea automatically if a user has
							// "Disable the visual editor" checked, or if TinyMCE
							// fails to load — wp_kses_post()+wpautop() at render
							// time (class-dashboard.php) handles both cases the
							// same way either way.
							wp_editor(
								$settings['welcome_text'],
								'bonsai_dashboard_welcome_text',
								[
									'textarea_name' => 'settings[welcome_text]',
									'textarea_rows' => 5,
									'teeny'         => true,
									'media_buttons' => false,
									'quicktags'     => true,
								]
							);
							?>
							<p class="description"><?php esc_html_e( 'Shown under the heading — paragraphs, bold/italic, links and lists are all supported.', 'bonsai-dashboard' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bonsai-dashboard-analytics-url"><?php esc_html_e( 'Analytics URL', 'bonsai-dashboard' ); ?></label></th>
						<td>
							<input type="url" id="bonsai-dashboard-analytics-url" name="settings[analytics_url]" class="regular-text" value="<?php echo esc_attr( $settings['analytics_url'] ); ?>" placeholder="https://lookerstudio.google.com/...">
							<p class="description"><?php esc_html_e( 'This site\'s GA4/Looker Studio dashboard. The Analytics quick link is hidden if left blank.', 'bonsai-dashboard' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bonsai-dashboard-support-url"><?php esc_html_e( 'Support URL', 'bonsai-dashboard' ); ?></label></th>
						<td>
							<input type="url" id="bonsai-dashboard-support-url" name="settings[support_url]" class="regular-text" value="<?php echo esc_attr( $settings['support_url'] ); ?>" placeholder="<?php echo esc_attr( Bonsai_Dashboard_Settings::DEFAULT_SUPPORT_URL ); ?>">
							<p class="description"><?php esc_html_e( 'Defaults to the Bonsai Digital Collective support desk. Update this if the site has its own support contact. Left blank, it falls back to the Bonsai support desk rather than disappearing.', 'bonsai-dashboard' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bonsai-dashboard-team-cpt-slug"><?php esc_html_e( 'Team post type slug', 'bonsai-dashboard' ); ?></label></th>
						<td>
							<input type="text" id="bonsai-dashboard-team-cpt-slug" name="settings[team_cpt_slug]" class="regular-text" value="<?php echo esc_attr( $settings['team_cpt_slug'] ); ?>" placeholder="<?php echo esc_attr( Bonsai_Dashboard_Settings::DEFAULT_TEAM_CPT_SLUG ); ?>">
							<p class="description"><?php esc_html_e( 'The registered post type slug for this site\'s Team members, if it differs from the default "team" (e.g. "staff" or "our-team"). The Team quick link is hidden if no matching post type is registered.', 'bonsai-dashboard' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Custom cards', 'bonsai-dashboard' ); ?></th>
						<td>
							<div id="bonsai-dashboard-custom-cards" data-next-index="<?php echo esc_attr( (string) count( $settings['custom_cards'] ) ); ?>">
								<?php foreach ( $settings['custom_cards'] as $index => $card ) : ?>
									<?php self::render_card_row( (int) $index, $card ); ?>
								<?php endforeach; ?>
							</div>

							<p>
								<button type="button" class="button" id="bonsai-dashboard-add-card"><?php esc_html_e( '+ Add card', 'bonsai-dashboard' ); ?></button>
							</p>

							<script type="text/template" id="bonsai-dashboard-card-template">
								<?php self::render_card_row( '__INDEX__', [] ); ?>
							</script>

							<p class="description">
								<?php
								printf(
									/* translators: %s: link to the Dashicons reference. */
									esc_html__( 'Extra cards shown after the built-in ones — a client portal, a booking system, a shared drive, anything else this site needs. Icon is a %s class name (e.g. "dashicons-star-filled"); left blank, a generic link icon is used. A card with no label or URL is dropped when you save.', 'bonsai-dashboard' ),
									'<a href="https://developer.wordpress.org/resource/dashicons/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Dashicons', 'bonsai-dashboard' ) . '</a>'
								);
								?>
							</p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Colour overrides', 'bonsai-dashboard' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Optional — leave any of these blank to use the dashboard\'s built-in Bonsai colours (or the active theme\'s brand colours, where it exposes them).', 'bonsai-dashboard' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Welcome panel', 'bonsai-dashboard' ); ?></th>
						<td>
							<?php
							self::render_color_field( 'welcome_bg_color', __( 'Background', 'bonsai-dashboard' ), $settings['welcome_bg_color'] );
							self::render_color_field( 'welcome_text_color', __( 'Text', 'bonsai-dashboard' ), $settings['welcome_text_color'] );
							?>
							<p class="description"><?php esc_html_e( 'Background and text colour of the welcome heading/message at the top of the dashboard.', 'bonsai-dashboard' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Quick-link cards', 'bonsai-dashboard' ); ?></th>
						<td>
							<?php
							self::render_color_field( 'icon_color', __( 'Icon', 'bonsai-dashboard' ), $settings['icon_color'] );
							self::render_color_field( 'text_color', __( 'Text', 'bonsai-dashboard' ), $settings['text_color'] );
							?>
							<p class="description"><?php esc_html_e( 'Icon and label colour on the quick-link cards in their normal (non-hover) state.', 'bonsai-dashboard' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Quick-link cards — hover', 'bonsai-dashboard' ); ?></th>
						<td>
							<?php
							self::render_color_field( 'hover_bg_color', __( 'Background', 'bonsai-dashboard' ), $settings['hover_bg_color'] );
							self::render_color_field( 'hover_text_color', __( 'Icon / text', 'bonsai-dashboard' ), $settings['hover_text_color'] );
							?>
							<p class="description"><?php esc_html_e( 'Background and icon/text colour on the quick-link cards when hovered or focused.', 'bonsai-dashboard' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save settings', 'bonsai-dashboard' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders one labelled colour-picker input. Shared by every row in the
	 * "Colour overrides" section above — the "bonsai-dashboard-color-picker"
	 * class turns the plain text input into a swatch picker (see
	 * assets/admin-settings.js, which calls wp.wpColorPicker() on it). An
	 * empty value is valid and means "use the built-in default" (see
	 * class-settings.php::sanitize_color()).
	 *
	 * @param string $field Settings array key, used for both the input name and id.
	 * @param string $label Visible label shown before the input.
	 * @param string $value Saved hex colour, or '' for "unset".
	 */
	private static function render_color_field( string $field, string $label, string $value ): void {
		?>
		<span class="bonsai-dashboard-color-field">
			<label for="bonsai-dashboard-<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $label ); ?></label>
			<input
				type="text"
				id="bonsai-dashboard-<?php echo esc_attr( $field ); ?>"
				name="settings[<?php echo esc_attr( $field ); ?>]"
				class="bonsai-dashboard-color-picker"
				value="<?php echo esc_attr( $value ); ?>"
				data-default-color=""
			>
		</span>
		<?php
	}

	/**
	 * Renders one custom-card repeater row's fields. Shared between the
	 * existing-rows loop and the JS "add card" `<script type="text/template">`
	 * block above, so the two never drift out of sync — `$index` is either a
	 * real array key or the literal placeholder `__INDEX__`, which
	 * assets/admin-settings.js replaces with a fresh integer on clone.
	 *
	 * @param int|string $index Row index for the `settings[custom_cards][{index}][...]` field names.
	 * @param array      $card  label/url/icon/external, or [] for a blank/template row.
	 */
	private static function render_card_row( $index, array $card ): void {
		$label    = $card['label'] ?? '';
		$url      = $card['url'] ?? '';
		$icon     = $card['icon'] ?? '';
		$external = ! empty( $card['external'] );
		?>
		<div class="bonsai-dashboard-card-row">
			<input type="text" name="settings[custom_cards][<?php echo esc_attr( (string) $index ); ?>][label]" value="<?php echo esc_attr( $label ); ?>" placeholder="<?php echo esc_attr__( 'Label', 'bonsai-dashboard' ); ?>">
			<input type="url" name="settings[custom_cards][<?php echo esc_attr( (string) $index ); ?>][url]" value="<?php echo esc_attr( $url ); ?>" placeholder="https://example.com">
			<input type="text" name="settings[custom_cards][<?php echo esc_attr( (string) $index ); ?>][icon]" value="<?php echo esc_attr( $icon ); ?>" placeholder="dashicons-admin-links">
			<label>
				<input type="checkbox" name="settings[custom_cards][<?php echo esc_attr( (string) $index ); ?>][external]" value="1" <?php checked( $external ); ?>>
				<?php esc_html_e( 'Open in new tab', 'bonsai-dashboard' ); ?>
			</label>
			<button type="button" class="button-link bonsai-dashboard-remove-card"><?php esc_html_e( 'Remove', 'bonsai-dashboard' ); ?></button>
		</div>
		<?php
	}

	public static function handle_save_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to change these settings.', 'bonsai-dashboard' ) );
		}

		check_admin_referer( 'bonsai_dashboard_save_settings', 'bonsai_dashboard_nonce' );

		$input = wp_unslash( $_POST['settings'] ?? [] );
		Bonsai_Dashboard_Settings::save_settings( is_array( $input ) ? $input : [] );

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::MENU_SLUG, 'settings-updated' => 'true' ],
			admin_url( 'options-general.php' )
		) );
		exit;
	}
}
