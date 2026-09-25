# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] - 2026-09-25

### Added
- Optional logo above the welcome heading, chosen from the media library
  under Settings → Bonsai Dashboard (stored as an attachment ID, so it
  survives staging → live domain moves). Configurable max width (40–600px,
  default 200px), scales down on narrow screens. Alt text comes from the
  media library, falling back to the site name.

## [1.1.0] - 2026-09-22

### Added
- Colour overrides under Settings → Bonsai Dashboard: welcome panel
  background/text, quick-link card icon/text, and quick-link card
  hover background/icon-text. All optional (colour picker, clearable) —
  left blank, the dashboard falls back to its existing behaviour (Bonsai
  brand colours, or the active theme's ACF brand colours where set).

## [1.0.0] - 2026-09-22

### Added
- Initial release as **Bonsai Dashboard**. Replaces the default wp-admin
  dashboard with a single branded panel (welcome message + quick-links grid:
  Pages, Posts, Theme Setup, Team, Analytics, Support) — every default
  dashboard widget and WordPress's own welcome banner are removed, not hidden.
- Settings → Bonsai Dashboard: per-site welcome heading/text, Analytics URL,
  Support URL (defaults to the Bonsai Digital Collective support desk,
  editable per site), and the Team post type slug (defaults to `team`,
  editable for themes that register it under a different name). Welcome
  heading/text default to Bonsai Digital Collective boilerplate copy,
  editable/clearable per site. Welcome text is a `wp_editor()` (teeny
  toolbar) field — paragraphs, bold/italic, links and lists are supported,
  sanitised with `wp_kses_post()` on save and re-escaped the same way at
  render.
- Self-updates via Plugin Update Checker, served from
  `Bonsai-Systems/bonsai-dashboard`.
- Widget forced to the full dashboard width (other, now-empty postbox
  columns hidden) rather than sitting in WordPress's narrower default column.
- Quick-link cards: fixed 3-column grid (3x2 for the full 6-link set,
  dropping to 2 then 1 column on narrow admin viewports), square
  (`aspect-ratio: 1/1`) tiles with a `container-type` responsive icon size.
  Icon colour and hover background pull from `--bonsai-primary`/
  `--bonsai-secondary` (defaulting to Bonsai brand colours) — overridden per
  site from the active theme's Site Settings brand colours when that
  theme/ACF is active.
- Theme Setup quick link points at `admin.php?page=theme-general-settings`.
- Custom cards: a repeater under Settings → Bonsai Dashboard for arbitrary
  extra quick-link cards (label, URL, dashicon class, optional new-tab) —
  e.g. a client portal or booking system that doesn't fit the built-in
  links. Shown after Analytics, before Support. Rows with no label or URL
  are dropped on save rather than persisted as dead cards.

### Changed
- Rebranded from the earlier TTNG-specific build: dropped the Testimonials
  quick link and TTNG/Cascade-specific conditionals in favour of a
  configurable Team post type slug, and dropped the Support phone field.
