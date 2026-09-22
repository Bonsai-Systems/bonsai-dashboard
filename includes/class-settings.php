<?php
/**
 * class-settings.php — Bonsai Dashboard's per-site configurable links.
 *
 * Analytics differs per site (each client has its own GA4/Looker Studio
 * dashboard), so it's entered once under Settings → Bonsai Dashboard.
 * Support defaults to the Bonsai Digital Collective Zendesk, but is
 * overridable per site. The Team quick link's post type slug is also
 * configurable, since not every theme names it the same way — see
 * class-dashboard.php. Custom cards are a free-form repeater for anything
 * else a given site wants on its dashboard (a client portal, a booking
 * system, a shared drive — whatever doesn't fit the built-in links).
 *
 * @package Bonsai_Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Bonsai_Dashboard_Settings {

	const OPTION = 'bonsai_dashboard_settings';

	const DEFAULT_SUPPORT_URL = 'https://bonsaidigitalcollective.zendesk.com/hc/en-gb/requests/new';

	const DEFAULT_TEAM_CPT_SLUG = 'team';

	const DEFAULT_CARD_ICON = 'dashicons-admin-links';

	const DEFAULT_WELCOME_HEADING = 'Welcome to your WordPress admin area, brought to you by The Bonsai Digital Collective';

	const DEFAULT_WELCOME_TEXT = <<<'HTML'
<p>The Bonsai Digital Collective is a team of experienced freelancers - specialists in design, web development, and marketing - collaborating to deliver the creativity and flexibility of a full-service agency, without the overhead.</p>
<p>From custom WordPress sites to branding, social content, digital marketing, and more, we provide tailored solutions to help your business grow with clarity and creativity. Whether you need a new website, a brand refresh, or ongoing support, our team is here for you.</p>
<p>Exceptional results, seamless support.</p>
HTML;

	public static function default_settings(): array {
		return [
			'analytics_url'   => '',
			'support_url'     => self::DEFAULT_SUPPORT_URL,
			'team_cpt_slug'   => self::DEFAULT_TEAM_CPT_SLUG,
			'welcome_heading' => self::DEFAULT_WELCOME_HEADING,
			'welcome_text'    => self::DEFAULT_WELCOME_TEXT,
			'custom_cards'    => [],
		];
	}

	public static function get_settings(): array {
		$saved = get_option( self::OPTION, [] );
		return array_merge( self::default_settings(), is_array( $saved ) ? $saved : [] );
	}

	public static function get( string $key ) {
		$settings = self::get_settings();
		return $settings[ $key ] ?? '';
	}

	/**
	 * Sanitises and saves every field. URL fields go through esc_url_raw()
	 * (input sanitisation for a value that gets echoed through esc_url() on
	 * output — see ~/.claude/rules/common/security.md), the team post type
	 * slug through sanitize_key(), other text fields through
	 * sanitize_text_field(). `welcome_text` is the one rich-content field
	 * (admin-page.php renders it
	 * with wp_editor(), so it can hold paragraphs/bold/links/lists) — sanitised
	 * with wp_kses_post() instead, same allowed-tags list as any other
	 * WYSIWYG field in this codebase, and re-run through wp_kses_post() again
	 * at render time in class-dashboard.php (defence in depth on the escaping
	 * side, not a substitute for sanitising here).
	 *
	 * An empty Support URL falls back to the Bonsai Zendesk default rather
	 * than being saved as blank, since Support should always link somewhere.
	 *
	 * `custom_cards` always resolves against `$input` directly rather than
	 * falling back to `$current` when absent — this settings screen is a
	 * single full-page POST (not a partial/AJAX update), so a missing key
	 * genuinely means every custom card row was removed in the browser
	 * before submitting, not that the field wasn't sent.
	 *
	 * @param array $input Raw $_POST['settings'] (already wp_unslash()'d by the caller).
	 */
	public static function save_settings( array $input ): void {
		$current = self::get_settings();

		$support_url = esc_url_raw( (string) ( $input['support_url'] ?? $current['support_url'] ) );

		update_option( self::OPTION, [
			'analytics_url'   => esc_url_raw( (string) ( $input['analytics_url'] ?? $current['analytics_url'] ) ),
			'support_url'     => $support_url ?: self::DEFAULT_SUPPORT_URL,
			'team_cpt_slug'   => sanitize_key( (string) ( $input['team_cpt_slug'] ?? $current['team_cpt_slug'] ) ) ?: self::DEFAULT_TEAM_CPT_SLUG,
			'welcome_heading' => sanitize_text_field( (string) ( $input['welcome_heading'] ?? $current['welcome_heading'] ) ),
			'welcome_text'    => wp_kses_post( (string) ( $input['welcome_text'] ?? $current['welcome_text'] ) ),
			'custom_cards'    => self::sanitize_custom_cards( $input['custom_cards'] ?? [] ),
		], false );
	}

	/**
	 * Sanitises the custom cards repeater. Rows missing a label or URL are
	 * dropped rather than saved as a dead/blank card — the admin UI only
	 * submits rows the user actually filled in or left as an untouched
	 * template, so this is the one place that filters those out.
	 *
	 * @param mixed $raw $_POST['settings']['custom_cards'], already wp_unslash()'d.
	 * @return array[] Each: label, url, icon (dashicon class), external (bool).
	 */
	private static function sanitize_custom_cards( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return [];
		}

		$cards = [];

		foreach ( $raw as $card ) {
			if ( ! is_array( $card ) ) {
				continue;
			}

			$label = sanitize_text_field( (string) ( $card['label'] ?? '' ) );
			$url   = esc_url_raw( (string) ( $card['url'] ?? '' ) );

			if ( '' === $label || '' === $url ) {
				continue;
			}

			$icon = sanitize_html_class( (string) ( $card['icon'] ?? '' ) );

			$cards[] = [
				'label'    => $label,
				'url'      => $url,
				'icon'     => $icon ?: self::DEFAULT_CARD_ICON,
				'external' => ! empty( $card['external'] ),
			];
		}

		return $cards;
	}
}
