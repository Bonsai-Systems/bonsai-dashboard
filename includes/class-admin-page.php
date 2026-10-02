<?php
/**
 * class-admin-page.php — Bonsai Dashboard's settings screen.
 *
 * Settings → Bonsai Dashboard, laid out like the Vision Website plugin's
 * screen: Bonsai header, left-hand tab nav, one tab per page load (?tab=…),
 * each tab with its own form. Tabs are defined in self::tabs():
 *   - Welcome     — logo, heading and text at the top of the dashboard
 *   - Quick links — Analytics/Support URLs, Team post type, custom cards
 *   - Colours     — welcome panel and quick-link card colour overrides
 *   - White Label — admin branding, login screen, admin menus
 *                   (class-white-label.php, class-admin-access.php). Only
 *                   listed for users who pass Admin_Access::can_manage().
 *
 * The first three save through one admin_post handler
 * (handle_save_settings()), which only updates the submitted tab's keys —
 * see Bonsai_Dashboard_Settings::SECTIONS. White Label has its own option
 * and handler.
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

	/**
	 * Every tab, in display order. Each: label, description (lead paragraph
	 * under the tab heading), render callback.
	 *
	 * @return array<string, array> Keyed by tab slug (?tab=…).
	 */
	public static function tabs(): array {
		$tabs = [
			'welcome'     => [
				'label'       => __( 'Welcome', 'bonsai-dashboard' ),
				'description' => __( 'The logo, heading and message at the top of the dashboard.', 'bonsai-dashboard' ),
				'render'      => [ __CLASS__, 'render_welcome_tab' ],
			],
			'quick-links' => [
				'label'       => __( 'Quick links', 'bonsai-dashboard' ),
				'description' => __( 'Pages, Posts and Theme Setup always link to this site\'s own admin screens, so there\'s nothing to configure for those. Everything else is set here.', 'bonsai-dashboard' ),
				'render'      => [ __CLASS__, 'render_quick_links_tab' ],
			],
			'colours'     => [
				'label'       => __( 'Colours', 'bonsai-dashboard' ),
				'description' => __( 'Optional — leave any of these blank to use the dashboard\'s built-in Bonsai colours (or the active theme\'s brand colours, where it exposes them).', 'bonsai-dashboard' ),
				'render'      => [ __CLASS__, 'render_colours_tab' ],
			],
		];

		// Clients shouldn't be able to rebrand the admin or change which
		// menus they can see — see class-admin-access.php.
		if ( Bonsai_Dashboard_Admin_Access::can_manage() ) {
			$tabs['white-label'] = [
				'label'       => __( 'White Label', 'bonsai-dashboard' ),
				'description' => __( 'Admin branding, the login screen, and which menus non-agency users can see. Anything left blank keeps WordPress\'s own default.', 'bonsai-dashboard' ),
				'render'      => [ 'Bonsai_Dashboard_White_Label', 'render_tab' ],
			];
		}

		return $tabs;
	}

	/**
	 * The requested ?tab= slug, falling back to the first tab when it's
	 * missing or unknown (or not available to this user).
	 */
	public static function current_tab(): string {
		$tabs = self::tabs();
		$tab  = sanitize_key( wp_unslash( $_GET['tab'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation.

		return isset( $tabs[ $tab ] ) ? $tab : (string) array_key_first( $tabs );
	}

	/**
	 * Admin URL for one tab, so redirects never hand-build the query string.
	 *
	 * @param string $tab   Tab slug.
	 * @param array  $extra Extra query args (e.g. settings-updated).
	 */
	public static function tab_url( string $tab, array $extra = [] ): string {
		return add_query_arg(
			array_merge( [ 'page' => self::MENU_SLUG, 'tab' => $tab ], $extra ),
			admin_url( 'options-general.php' )
		);
	}

	public static function enqueue_assets( string $hook ): void {
		if ( '' === self::$hook_suffix || $hook !== self::$hook_suffix ) {
			return;
		}
		wp_enqueue_media(); // Media library modal for the image pickers.
		wp_enqueue_style( 'wp-color-picker' );
		Bonsai_Dashboard_Admin_UI::enqueue();
		wp_enqueue_style( 'bonsai-dashboard-admin', BONSAI_DASHBOARD_URL . 'assets/admin-settings.css', [ Bonsai_Dashboard_Admin_UI::HANDLE ], BONSAI_DASHBOARD_VERSION );
		wp_enqueue_script( 'bonsai-dashboard-admin', BONSAI_DASHBOARD_URL . 'assets/admin-settings.js', [ 'jquery', 'wp-color-picker' ], BONSAI_DASHBOARD_VERSION, true );
	}

	/**
	 * Page shell — Bonsai header, tab nav, the active tab's heading and
	 * lead, then that tab's render callback.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'bonsai-dashboard' ) );
		}

		$tabs    = self::tabs();
		$current = self::current_tab();
		$tab     = $tabs[ $current ];
		?>
		<div class="wrap bonsai-ui">
			<?php
			// No "Settings saved." notice here: core's options-head.php already
			// prints one for any Settings sub-page loaded with ?settings-updated.
			Bonsai_Dashboard_Admin_UI::header(
				__( 'Bonsai Dashboard settings', 'bonsai-dashboard' ),
				__( 'Controls the branded wp-admin dashboard (logo, welcome message, quick links, colours) and white-labelling of the admin and login screen.', 'bonsai-dashboard' ),
				[
					[
						'label' => __( 'View dashboard', 'bonsai-dashboard' ),
						'url'   => admin_url( 'index.php' ),
					],
				]
			);
			?>

			<div class="bonsai-dashboard-shell">
				<nav class="bonsai-dashboard-nav" aria-label="<?php esc_attr_e( 'Bonsai Dashboard settings', 'bonsai-dashboard' ); ?>">
					<?php foreach ( $tabs as $slug => $item ) : ?>
						<a href="<?php echo esc_url( self::tab_url( $slug ) ); ?>"<?php echo $slug === $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $item['label'] ); ?></a>
					<?php endforeach; ?>
				</nav>

				<div class="bonsai-dashboard-panel">
					<h2 class="bonsai-dashboard-panel__title"><?php echo esc_html( $tab['label'] ); ?></h2>
					<?php if ( ! empty( $tab['description'] ) ) : ?>
						<p class="bonsai-dashboard-panel__lead"><?php echo esc_html( $tab['description'] ); ?></p>
					<?php endif; ?>
					<?php call_user_func( $tab['render'] ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Opening of a Welcome/Quick links/Colours form — all three post to
	 * handle_save_settings() with their tab slug and a per-tab nonce.
	 */
	private static function open_settings_form( string $tab ): void {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'bonsai_dashboard_save_' . $tab, 'bonsai_dashboard_nonce' ); ?>
			<input type="hidden" name="action" value="bonsai_dashboard_save_settings">
			<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
		<?php
	}

	public static function render_welcome_tab(): void {
		$settings = Bonsai_Dashboard_Settings::get_settings();
		self::open_settings_form( 'welcome' );
		?>
			<section class="bonsai-ui-card" aria-label="<?php esc_attr_e( 'Welcome panel', 'bonsai-dashboard' ); ?>">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Logo', 'bonsai-dashboard' ); ?></th>
						<td>
							<?php self::media_field( 'settings[logo_id]', 'bonsai-dashboard-logo-id', absint( $settings['logo_id'] ) ); ?>
							<p class="bonsai-dashboard-inline-fields">
								<label for="bonsai-dashboard-logo-width"><?php esc_html_e( 'Max width (px)', 'bonsai-dashboard' ); ?></label>
								<input type="number" id="bonsai-dashboard-logo-width" name="settings[logo_width]" class="small-text" min="<?php echo esc_attr( (string) Bonsai_Dashboard_Settings::MIN_LOGO_WIDTH ); ?>" max="<?php echo esc_attr( (string) Bonsai_Dashboard_Settings::MAX_LOGO_WIDTH ); ?>" step="1" value="<?php echo esc_attr( (string) $settings['logo_width'] ); ?>">
							</p>
							<p class="description"><?php esc_html_e( 'Optional — shown above the welcome heading. Uses the image\'s alt text from the media library (or the site name if none is set). Shrinks automatically on narrow screens.', 'bonsai-dashboard' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bonsai-dashboard-welcome-heading"><?php esc_html_e( 'Welcome heading', 'bonsai-dashboard' ); ?></label></th>
						<td>
							<input type="text" id="bonsai-dashboard-welcome-heading" name="settings[welcome_heading]" class="regular-text" value="<?php echo esc_attr( $settings['welcome_heading'] ); ?>" placeholder="<?php echo esc_attr__( 'Welcome back', 'bonsai-dashboard' ); ?>">
							<p class="description"><?php esc_html_e( 'Shown at the top of the dashboard welcome panel. Defaults to a generic greeting if left blank.', 'bonsai-dashboard' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="bonsai_dashboard_welcome_text"><?php esc_html_e( 'Welcome text', 'bonsai-dashboard' ); ?></label></th>
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
				</table>
			</section>

			<?php submit_button( __( 'Save welcome settings', 'bonsai-dashboard' ) ); ?>
		</form>
		<?php
	}

	public static function render_quick_links_tab(): void {
		$settings = Bonsai_Dashboard_Settings::get_settings();
		self::open_settings_form( 'quick-links' );
		?>
			<section class="bonsai-ui-card" aria-labelledby="bonsai-dashboard-links-title">
				<h3 class="bonsai-ui-card__title" id="bonsai-dashboard-links-title"><?php esc_html_e( 'Built-in links', 'bonsai-dashboard' ); ?></h3>
				<table class="form-table" role="presentation">
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
				</table>
			</section>

			<section class="bonsai-ui-card" aria-labelledby="bonsai-dashboard-cards-title">
				<h3 class="bonsai-ui-card__title" id="bonsai-dashboard-cards-title"><?php esc_html_e( 'Custom cards', 'bonsai-dashboard' ); ?></h3>
				<p class="bonsai-ui-card__intro">
					<?php
					printf(
						/* translators: %s: link to the Dashicons reference. */
						esc_html__( 'Extra cards shown after the built-in ones — a client portal, a booking system, a shared drive, anything else this site needs. Icon is a %s class name (e.g. "dashicons-star-filled"); left blank, a generic link icon is used. A card with no label or URL is dropped when you save.', 'bonsai-dashboard' ),
						'<a href="https://developer.wordpress.org/resource/dashicons/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Dashicons', 'bonsai-dashboard' ) . '</a>'
					);
					?>
				</p>

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
			</section>

			<?php submit_button( __( 'Save quick links', 'bonsai-dashboard' ) ); ?>
		</form>
		<?php
	}

	public static function render_colours_tab(): void {
		$settings = Bonsai_Dashboard_Settings::get_settings();
		self::open_settings_form( 'colours' );
		?>
			<section class="bonsai-ui-card" aria-label="<?php esc_attr_e( 'Colour overrides', 'bonsai-dashboard' ); ?>">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Welcome panel', 'bonsai-dashboard' ); ?></th>
						<td>
							<?php
							self::color_field( 'settings[welcome_bg_color]', 'bonsai-dashboard-welcome_bg_color', __( 'Background', 'bonsai-dashboard' ), $settings['welcome_bg_color'] );
							self::color_field( 'settings[welcome_text_color]', 'bonsai-dashboard-welcome_text_color', __( 'Text', 'bonsai-dashboard' ), $settings['welcome_text_color'] );
							?>
							<p class="description"><?php esc_html_e( 'Background and text colour of the welcome heading/message at the top of the dashboard.', 'bonsai-dashboard' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Quick-link cards', 'bonsai-dashboard' ); ?></th>
						<td>
							<?php
							self::color_field( 'settings[icon_color]', 'bonsai-dashboard-icon_color', __( 'Icon', 'bonsai-dashboard' ), $settings['icon_color'] );
							self::color_field( 'settings[text_color]', 'bonsai-dashboard-text_color', __( 'Text', 'bonsai-dashboard' ), $settings['text_color'] );
							?>
							<p class="description"><?php esc_html_e( 'Icon and label colour on the quick-link cards in their normal (non-hover) state.', 'bonsai-dashboard' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Quick-link cards — hover', 'bonsai-dashboard' ); ?></th>
						<td>
							<?php
							self::color_field( 'settings[hover_bg_color]', 'bonsai-dashboard-hover_bg_color', __( 'Background', 'bonsai-dashboard' ), $settings['hover_bg_color'] );
							self::color_field( 'settings[hover_text_color]', 'bonsai-dashboard-hover_text_color', __( 'Icon / text', 'bonsai-dashboard' ), $settings['hover_text_color'] );
							?>
							<p class="description"><?php esc_html_e( 'Background and icon/text colour on the quick-link cards when hovered or focused.', 'bonsai-dashboard' ); ?></p>
						</td>
					</tr>
				</table>
			</section>

			<?php submit_button( __( 'Save colours', 'bonsai-dashboard' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Media library image picker, storing an attachment ID. Shared by the
	 * Welcome tab's logo and the White Label tab's images — the JS
	 * (assets/admin-settings.js) binds to the class names, so every instance
	 * works the same with no per-field code.
	 *
	 * @param string $name          Input name, e.g. "settings[logo_id]".
	 * @param string $id            Hidden input ID.
	 * @param int    $attachment_id Saved attachment ID, or 0.
	 */
	public static function media_field( string $name, string $id, int $attachment_id ): void {
		$preview = $attachment_id ? wp_get_attachment_image( $attachment_id, 'medium' ) : '';
		?>
		<div class="bonsai-dashboard-media-field">
			<div class="bonsai-dashboard-media-field__preview"<?php echo $preview ? '' : ' hidden'; ?>>
				<?php echo $preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built by wp_get_attachment_image(), which escapes its own attributes. ?>
			</div>
			<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $attachment_id ); ?>">
			<p>
				<button type="button" class="button bonsai-dashboard-media-field__select"><?php esc_html_e( 'Select image', 'bonsai-dashboard' ); ?></button>
				<button type="button" class="button-link bonsai-dashboard-media-field__remove"<?php echo $attachment_id ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove', 'bonsai-dashboard' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * One labelled colour-picker input. The "bonsai-dashboard-color-picker"
	 * class turns the plain text input into a swatch picker (see
	 * assets/admin-settings.js, which calls wpColorPicker() on it). An empty
	 * value is valid and means "use the default".
	 *
	 * @param string $name  Input name, e.g. "settings[icon_color]".
	 * @param string $id    Input ID.
	 * @param string $label Visible label shown above the input.
	 * @param string $value Saved hex colour, or '' for "unset".
	 */
	public static function color_field( string $name, string $id, string $label, string $value ): void {
		?>
		<span class="bonsai-dashboard-color-field">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input
				type="text"
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
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
			<input type="text" name="settings[custom_cards][<?php echo esc_attr( (string) $index ); ?>][label]" value="<?php echo esc_attr( $label ); ?>" aria-label="<?php echo esc_attr__( 'Card label', 'bonsai-dashboard' ); ?>" placeholder="<?php echo esc_attr__( 'Label', 'bonsai-dashboard' ); ?>">
			<input type="url" name="settings[custom_cards][<?php echo esc_attr( (string) $index ); ?>][url]" value="<?php echo esc_attr( $url ); ?>" aria-label="<?php echo esc_attr__( 'Card URL', 'bonsai-dashboard' ); ?>" placeholder="https://example.com">
			<input type="text" name="settings[custom_cards][<?php echo esc_attr( (string) $index ); ?>][icon]" value="<?php echo esc_attr( $icon ); ?>" aria-label="<?php echo esc_attr__( 'Card icon (Dashicons class)', 'bonsai-dashboard' ); ?>" placeholder="dashicons-admin-links">
			<label>
				<input type="checkbox" name="settings[custom_cards][<?php echo esc_attr( (string) $index ); ?>][external]" value="1" <?php checked( $external ); ?>>
				<?php esc_html_e( 'Open in new tab', 'bonsai-dashboard' ); ?>
			</label>
			<button type="button" class="button-link bonsai-dashboard-remove-card"><?php esc_html_e( 'Remove', 'bonsai-dashboard' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Saves the Welcome, Quick links or Colours tab — whichever was posted.
	 * Only that tab's keys change (Bonsai_Dashboard_Settings::SECTIONS).
	 */
	public static function handle_save_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to change these settings.', 'bonsai-dashboard' ) );
		}

		$tab = sanitize_key( wp_unslash( $_POST['tab'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce action depends on the tab; checked on the next line.
		if ( ! isset( Bonsai_Dashboard_Settings::SECTIONS[ $tab ] ) ) {
			wp_die( esc_html__( 'Unknown settings tab.', 'bonsai-dashboard' ) );
		}

		check_admin_referer( 'bonsai_dashboard_save_' . $tab, 'bonsai_dashboard_nonce' );

		$input = wp_unslash( $_POST['settings'] ?? [] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised per field in save_settings().
		Bonsai_Dashboard_Settings::save_settings( is_array( $input ) ? $input : [], $tab );

		wp_safe_redirect( self::tab_url( $tab, [ 'settings-updated' => 'true' ] ) );
		exit;
	}
}
