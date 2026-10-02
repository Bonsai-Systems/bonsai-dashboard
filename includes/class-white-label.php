<?php
/**
 * class-white-label.php — admin branding and login screen.
 *
 * Ported from the Vision Website plugin (TTNG), which replaced the parts of
 * the White Label CMS plugin those sites actually used. Shown as the
 * "White Label" tab on Settings → Bonsai Dashboard:
 *
 *   Branding     — hide WordPress branding (admin bar logo/links, footer
 *                  credit, version), a custom admin bar logo, custom admin
 *                  footer text.
 *   Login screen — logo (size, links to the site), page background
 *                  colour/image, form/label/button/link colours.
 *   Admin menus  — Bonsai_Dashboard_Admin_Access (class-admin-access.php),
 *                  rendered and saved as part of this tab's form.
 *
 * Unlike Vision, there are no built-in brand defaults: every field starts
 * blank and blank means "leave WordPress as it is". This plugin goes on
 * sites Bonsai white-labels for other agencies, where Bonsai branding
 * appearing on its own would be wrong.
 *
 * Images are stored as attachment IDs, like the dashboard logo
 * (class-settings.php), so they survive a staging → live domain change.
 *
 * White Label CMS should be deactivated once this is set up; both running
 * at once would fight over the same login/admin bar output. The tab shows a
 * warning while it's active.
 *
 * @package Bonsai_Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bonsai_Dashboard_White_Label {

	const OPTION = 'bonsai_dashboard_white_label';

	/** Login form content width in core — the logo's default width cap. */
	const LOGIN_LOGO_MAX_DEFAULT = 320;

	/** Core's own login logo size, used when an image has no dimensions (e.g. SVG). */
	const LOGIN_LOGO_FALLBACK = 84;

	public static function init(): void {
		add_action( 'admin_post_bonsai_dashboard_save_white_label', [ __CLASS__, 'handle_save' ] );

		// Branding.
		// Logo added early so it sits at the far left; the WordPress logo
		// (added by core at priority 10) removed late.
		add_action( 'admin_bar_menu', [ __CLASS__, 'admin_bar_logo' ], 5 );
		add_action( 'admin_bar_menu', [ __CLASS__, 'admin_bar_remove_wp_logo' ], 999 );
		add_action( 'admin_head', [ __CLASS__, 'admin_bar_styles' ] );
		add_action( 'wp_head', [ __CLASS__, 'admin_bar_styles' ] );
		add_filter( 'admin_footer_text', [ __CLASS__, 'footer_text' ], 99 );
		add_filter( 'update_footer', [ __CLASS__, 'footer_version' ], 99 );

		// Login screen.
		add_action( 'login_enqueue_scripts', [ __CLASS__, 'login_styles' ] );
		add_filter( 'login_headerurl', [ __CLASS__, 'login_logo_url' ] );
		add_filter( 'login_headertext', [ __CLASS__, 'login_logo_text' ] );
	}

	/**
	 * Every field, blank. Blank always means "WordPress default".
	 */
	public static function defaults(): array {
		return [
			// Branding.
			'hide_wp_branding'    => '0',
			'admin_bar_logo_id'   => 0,
			'admin_bar_logo_url'  => '',
			'footer_text'         => '',
			'footer_url'          => '',

			// Login screen.
			'login_logo_id'       => 0,
			'login_logo_width'    => '',
			'login_logo_height'   => '',
			'login_bg_color'      => '',
			'login_bg_image_id'   => 0,
			'login_form_bg_color' => '',
			'login_label_color'   => '',
			'login_button_color'  => '',
			'login_button_text'   => '',
			'login_button_hover'  => '',
			'login_link_color'    => '',
		];
	}

	/** Colour fields, in the order they're shown on the tab. */
	private static function colour_fields(): array {
		return [
			'login_bg_color'      => __( 'Page background', 'bonsai-dashboard' ),
			'login_form_bg_color' => __( 'Login box background', 'bonsai-dashboard' ),
			'login_label_color'   => __( 'Form labels', 'bonsai-dashboard' ),
			'login_button_color'  => __( 'Button', 'bonsai-dashboard' ),
			'login_button_text'   => __( 'Button text', 'bonsai-dashboard' ),
			'login_button_hover'  => __( 'Button hover', 'bonsai-dashboard' ),
			'login_link_color'    => __( 'Links', 'bonsai-dashboard' ),
		];
	}

	public static function settings(): array {
		$saved = get_option( self::OPTION, [] );
		return array_merge( self::defaults(), is_array( $saved ) ? $saved : [] );
	}

	/* ── White Label tab ─────────────────────────────────────────────── */

	/**
	 * Tab content. Only called for users who pass
	 * Bonsai_Dashboard_Admin_Access::can_manage() — see
	 * Bonsai_Dashboard_Admin_Page::tabs().
	 */
	public static function render_tab(): void {
		$s = self::settings();
		?>
		<?php if ( defined( 'WLCMS_VERSION' ) ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'White Label CMS is still active. Deactivate it once you\'re happy with these settings — both plugins change the login screen and admin bar, and they\'ll conflict.', 'bonsai-dashboard' ); ?></p></div>
		<?php endif; ?>
		<?php if ( class_exists( 'Vision_Website_White_Label' ) ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'Vision Website\'s own White Label tab is active on this site. Use one or the other — both change the login screen and admin bar.', 'bonsai-dashboard' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'bonsai_dashboard_save_white_label', 'bonsai_dashboard_white_label_nonce' ); ?>
			<input type="hidden" name="action" value="bonsai_dashboard_save_white_label">

			<section class="bonsai-ui-card" aria-labelledby="bonsai-dashboard-branding-title">
				<h3 class="bonsai-ui-card__title" id="bonsai-dashboard-branding-title"><?php esc_html_e( 'Branding', 'bonsai-dashboard' ); ?></h3>
				<p class="bonsai-ui-card__intro"><?php esc_html_e( 'Applies to everyone, agency users included.', 'bonsai-dashboard' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'WordPress branding', 'bonsai-dashboard' ); ?></th>
						<td>
							<input type="hidden" name="white_label[hide_wp_branding]" value="0">
							<label for="bonsai-dashboard-wl-hide_wp_branding">
								<input type="checkbox" class="bonsai-ui-toggle" id="bonsai-dashboard-wl-hide_wp_branding" name="white_label[hide_wp_branding]" value="1" <?php checked( $s['hide_wp_branding'], '1' ); ?>>
								<?php esc_html_e( 'Hide the WordPress logo and links in the admin bar, the "Thank you for creating with WordPress" footer and the version number.', 'bonsai-dashboard' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Admin bar logo', 'bonsai-dashboard' ); ?></th>
						<td>
							<?php Bonsai_Dashboard_Admin_Page::media_field( 'white_label[admin_bar_logo_id]', 'bonsai-dashboard-wl-admin_bar_logo_id', (int) $s['admin_bar_logo_id'] ); ?>
							<p class="description"><?php esc_html_e( 'Optional. Shown at the top-left of the admin bar, 20px tall.', 'bonsai-dashboard' ); ?></p>
						</td>
					</tr>
					<?php
					self::text_row( 'admin_bar_logo_url', __( 'Admin bar logo link', 'bonsai-dashboard' ), $s, 'url', __( 'Leave blank to link to the dashboard.', 'bonsai-dashboard' ) );
					self::text_row( 'footer_text', __( 'Admin footer text', 'bonsai-dashboard' ), $s, 'text', __( 'Replaces "Thank you for creating with WordPress". Leave blank to keep it (or hide it, if WordPress branding is hidden).', 'bonsai-dashboard' ) );
					self::text_row( 'footer_url', __( 'Admin footer link', 'bonsai-dashboard' ), $s, 'url', __( 'Optional. Makes the footer text a link.', 'bonsai-dashboard' ) );
					?>
				</table>
			</section>

			<section class="bonsai-ui-card" aria-labelledby="bonsai-dashboard-login-title">
				<h3 class="bonsai-ui-card__title" id="bonsai-dashboard-login-title"><?php esc_html_e( 'Login screen', 'bonsai-dashboard' ); ?></h3>
				<p class="bonsai-ui-card__intro"><?php esc_html_e( 'Anything left blank keeps the standard WordPress login screen.', 'bonsai-dashboard' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Logo', 'bonsai-dashboard' ); ?></th>
						<td>
							<?php Bonsai_Dashboard_Admin_Page::media_field( 'white_label[login_logo_id]', 'bonsai-dashboard-wl-login_logo_id', (int) $s['login_logo_id'] ); ?>
							<p class="bonsai-dashboard-inline-fields">
								<label for="bonsai-dashboard-wl-login_logo_width"><?php esc_html_e( 'Width (px)', 'bonsai-dashboard' ); ?></label>
								<input type="number" min="1" max="1000" step="1" id="bonsai-dashboard-wl-login_logo_width" class="small-text" name="white_label[login_logo_width]" value="<?php echo esc_attr( (string) $s['login_logo_width'] ); ?>">
								<label for="bonsai-dashboard-wl-login_logo_height"><?php esc_html_e( 'Height (px)', 'bonsai-dashboard' ); ?></label>
								<input type="number" min="1" max="1000" step="1" id="bonsai-dashboard-wl-login_logo_height" class="small-text" name="white_label[login_logo_height]" value="<?php echo esc_attr( (string) $s['login_logo_height'] ); ?>">
							</p>
							<p class="description"><?php esc_html_e( 'Links to this site\'s home page. Size is optional: blank width uses the image\'s own width up to 320px, and blank height keeps its proportions.', 'bonsai-dashboard' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Background image', 'bonsai-dashboard' ); ?></th>
						<td>
							<?php Bonsai_Dashboard_Admin_Page::media_field( 'white_label[login_bg_image_id]', 'bonsai-dashboard-wl-login_bg_image_id', (int) $s['login_bg_image_id'] ); ?>
							<p class="description"><?php esc_html_e( 'Optional. Covers the whole page, over the background colour.', 'bonsai-dashboard' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Colours', 'bonsai-dashboard' ); ?></th>
						<td>
							<?php
							foreach ( self::colour_fields() as $key => $label ) {
								Bonsai_Dashboard_Admin_Page::color_field( 'white_label[' . $key . ']', 'bonsai-dashboard-wl-' . $key, $label, (string) $s[ $key ] );
							}
							?>
						</td>
					</tr>
				</table>
			</section>

			<?php Bonsai_Dashboard_Admin_Access::render_settings_card(); ?>

			<?php submit_button( __( 'Save white label settings', 'bonsai-dashboard' ) ); ?>
		</form>
		<?php
	}

	private static function text_row( string $key, string $label, array $s, string $type, string $help ): void {
		$id = 'bonsai-dashboard-wl-' . $key;
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td>
				<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>" class="regular-text" name="white_label[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) $s[ $key ] ); ?>">
				<p class="description"><?php echo esc_html( $help ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Saves the White Label tab — branding/login here, the menu settings via
	 * Bonsai_Dashboard_Admin_Access::save_settings().
	 */
	public static function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) || ! Bonsai_Dashboard_Admin_Access::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to change these settings.', 'bonsai-dashboard' ) );
		}

		check_admin_referer( 'bonsai_dashboard_save_white_label', 'bonsai_dashboard_white_label_nonce' );

		$input = wp_unslash( $_POST['white_label'] ?? [] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised per field below.
		$input = is_array( $input ) ? $input : [];
		$out   = [];

		foreach ( array_keys( self::defaults() ) as $key ) {
			$raw = $input[ $key ] ?? '';

			switch ( $key ) {
				case 'hide_wp_branding':
					$out[ $key ] = ! empty( $raw ) ? '1' : '0';
					break;
				case 'admin_bar_logo_id':
				case 'login_logo_id':
				case 'login_bg_image_id':
					$out[ $key ] = Bonsai_Dashboard_Settings::sanitize_logo_id( $raw );
					break;
				case 'login_logo_width':
				case 'login_logo_height':
					$out[ $key ] = '' === $raw || ! absint( $raw ) ? '' : min( 1000, absint( $raw ) );
					break;
				case 'admin_bar_logo_url':
				case 'footer_url':
					$out[ $key ] = esc_url_raw( (string) $raw );
					break;
				case 'footer_text':
					$out[ $key ] = sanitize_text_field( (string) $raw );
					break;
				default:
					// Every remaining field is a colour.
					$out[ $key ] = (string) sanitize_hex_color( (string) $raw );
			}
		}

		update_option( self::OPTION, $out, false );
		Bonsai_Dashboard_Admin_Access::save_settings( wp_unslash( $_POST['admin_access'] ?? [] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised in save_settings().

		wp_safe_redirect( Bonsai_Dashboard_Admin_Page::tab_url( 'white-label', [ 'settings-updated' => 'true' ] ) );
		exit;
	}

	/* ── Branding ────────────────────────────────────────────────────── */

	/** Removes the WordPress logo menu (About, WordPress.org, Support…). */
	public static function admin_bar_remove_wp_logo( WP_Admin_Bar $bar ): void {
		if ( '1' === self::settings()['hide_wp_branding'] ) {
			$bar->remove_node( 'wp-logo' );
		}
	}

	/** Adds the custom logo at the far left of the admin bar. */
	public static function admin_bar_logo( WP_Admin_Bar $bar ): void {
		$s   = self::settings();
		$src = $s['admin_bar_logo_id'] ? wp_get_attachment_image_url( (int) $s['admin_bar_logo_id'], 'medium' ) : '';

		if ( ! $src ) {
			return;
		}

		$bar->add_node( [
			'id'    => 'bonsai-dashboard-admin-logo',
			'title' => sprintf( '<img src="%s" alt="%s">', esc_url( $src ), esc_attr( get_bloginfo( 'name' ) ) ),
			'href'  => $s['admin_bar_logo_url'] ? esc_url( $s['admin_bar_logo_url'] ) : admin_url(),
			'meta'  => [ 'class' => 'bonsai-dashboard-admin-logo' ],
		] );
	}

	/** Sizes the admin bar logo. Printed only when there's a logo and a bar. */
	public static function admin_bar_styles(): void {
		if ( ! is_admin_bar_showing() || ! self::settings()['admin_bar_logo_id'] ) {
			return;
		}
		echo '<style id="bonsai-dashboard-admin-logo-css">#wpadminbar .bonsai-dashboard-admin-logo > .ab-item{display:flex;align-items:center;}#wpadminbar .bonsai-dashboard-admin-logo img{display:block;height:20px;width:auto;max-width:160px;}</style>' . "\n";
	}

	/** Admin footer credit, replacing "Thank you for creating with WordPress". */
	public static function footer_text( $text ) {
		$s = self::settings();

		if ( '' === $s['footer_text'] ) {
			return '1' === $s['hide_wp_branding'] ? '' : $text;
		}

		return $s['footer_url']
			? sprintf( '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( $s['footer_url'] ), esc_html( $s['footer_text'] ) )
			: esc_html( $s['footer_text'] );
	}

	/** Hides the WordPress version in the admin footer. */
	public static function footer_version( $text ) {
		return '1' === self::settings()['hide_wp_branding'] ? '' : $text;
	}

	/* ── Login screen ────────────────────────────────────────────────── */

	/** Logo links to the site rather than wordpress.org — only once a logo is set. */
	public static function login_logo_url( $url ) {
		return self::settings()['login_logo_id'] ? home_url( '/' ) : $url;
	}

	public static function login_logo_text( $text ) {
		return self::settings()['login_logo_id'] ? get_bloginfo( 'name' ) : $text;
	}

	/**
	 * Login screen CSS, added inline after core's `login` stylesheet
	 * (enqueued by wp-login.php before login_enqueue_scripts fires). Every
	 * value is sanitised on save (hex colours, attachment IDs, integers) and
	 * escaped again here, so nothing raw reaches the stylesheet. Nothing is
	 * printed when every field is blank.
	 */
	public static function login_styles(): void {
		$s   = self::settings();
		$css = '';

		$logo = $s['login_logo_id'] ? wp_get_attachment_image_src( (int) $s['login_logo_id'], 'full' ) : false;
		if ( $logo ) {
			[ $w, $h ] = self::login_logo_size( (int) $logo[1], (int) $logo[2], absint( $s['login_logo_width'] ), absint( $s['login_logo_height'] ) );
			$css      .= sprintf(
				'.login h1 a{background-image:url("%1$s");background-size:contain;background-position:center;width:%2$dpx;height:%3$dpx;max-width:100%%;}',
				esc_url( $logo[0] ),
				$w,
				$h
			);
		}

		$bg_color = sanitize_hex_color( $s['login_bg_color'] );
		if ( $bg_color ) {
			$css .= 'body.login{background-color:' . $bg_color . ';}';
		}

		$bg_image = $s['login_bg_image_id'] ? wp_get_attachment_image_url( (int) $s['login_bg_image_id'], 'full' ) : '';
		if ( $bg_image ) {
			$css .= sprintf( 'body.login{background-image:url("%s");background-size:cover;background-position:center;background-repeat:no-repeat;background-attachment:fixed;}', esc_url( $bg_image ) );
		}

		$colour_rules = [
			'login_form_bg_color' => '.login form{background-color:%s;}',
			'login_label_color'   => '.login form label,.login form .forgetmenot label{color:%s;}',
			'login_button_color'  => '.login .button-primary{background-color:%1$s;border-color:%1$s;}',
			'login_button_text'   => '.login .button-primary{color:%s;}',
			'login_button_hover'  => '.login .button-primary:hover,.login .button-primary:focus{background-color:%1$s;border-color:%1$s;}',
			'login_link_color'    => '.login #nav a,.login #backtoblog a,.login .privacy-policy-link{color:%s;}',
		];

		foreach ( $colour_rules as $key => $rule ) {
			$colour = sanitize_hex_color( $s[ $key ] );
			if ( $colour ) {
				$css .= sprintf( $rule, $colour );
			}
		}

		if ( '' !== $css ) {
			wp_add_inline_style( 'login', $css );
		}
	}

	/**
	 * Login logo box size. Width: the saved value, else the image's own
	 * width capped at the login form's 320px. Height: the saved value, else
	 * whatever keeps the image's proportions at that width. Images with no
	 * stored dimensions (SVG) fall back to core's 84px square logo box.
	 *
	 * @return int[] [ width, height ]
	 */
	private static function login_logo_size( int $natural_w, int $natural_h, int $width, int $height ): array {
		if ( ! $width ) {
			$width = $natural_w ? min( self::LOGIN_LOGO_MAX_DEFAULT, $natural_w ) : self::LOGIN_LOGO_FALLBACK;
		}
		if ( ! $height ) {
			$height = ( $natural_w && $natural_h ) ? (int) round( $width * $natural_h / $natural_w ) : self::LOGIN_LOGO_FALLBACK;
		}
		return [ $width, max( 1, $height ) ];
	}
}
