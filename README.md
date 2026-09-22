# Bonsai Dashboard

WordPress admin plugin for The Bonsai Digital Collective — replaces the
default wp-admin dashboard with a single branded panel: a welcome message
plus a quick-links grid (Pages, Posts, Theme Setup, Team, Analytics, Support).

- **Maintained by:** Ben Ervine / The Bonsai Digital Collective
- **Version:** 1.0.0
- **Requires:** WordPress 6.0+, PHP 8.0+
- **Dependencies:** `yahnis-elsts/plugin-update-checker` (bundled in `vendor/`).

## What it does

Every default dashboard widget (At a Glance, Activity, Quick Draft, WordPress
Events & News, Site Health Status — plus anything a third-party plugin has
added) is removed, not hidden, so the Bonsai panel is the only thing on the
dashboard. WordPress's own dismissible welcome banner is disabled too, since
this plugin's own welcome panel replaces it.

## Quick links

| Link | Shown when | Target |
|---|---|---|
| Pages | Always | `edit.php?post_type=page` |
| Posts | Always | `edit.php` |
| Theme Setup | Always | `admin.php?page=theme-general-settings` |
| Team | A post type matching the configured slug (default `team`) is registered | `edit.php?post_type={slug}` |
| Analytics | An Analytics URL is set (below) | That URL, new tab |
| Custom cards | Any rows added under Settings → Bonsai Dashboard | Whatever URL each card specifies, new tab if set |
| Support | Always — defaults to the Bonsai support desk | That URL, new tab |

Team is hidden automatically on a site that doesn't have a matching post type
registered, rather than linking to a 404 or permission error.

## Settings

**Settings → Bonsai Dashboard**: welcome heading/text (defaults to Bonsai
Digital Collective boilerplate copy, editable or clearable per site),
Analytics URL, Support
URL (defaults to `https://bonsaidigitalcollective.zendesk.com/hc/en-gb/requests/new`,
editable per site), the Team post type slug (in case a theme registers it
under a different name, e.g. `staff`), and a **custom cards** repeater for
anything else a site needs on its dashboard (a client portal, a booking
system, a shared drive) — each row is a label, URL, dashicon class, and an
"open in new tab" checkbox. A row with no label or URL is dropped when
settings are saved. See `includes/class-settings.php`.

Also under **Settings → Bonsai Dashboard**, an optional **colour overrides**
section: welcome panel background/text, quick-link card icon/text, and
quick-link card hover background/icon-text (colour picker, clearable). Left
blank, each falls back to the Bonsai brand colours (or the active theme's ACF
brand colour fields, where set) — see
`Bonsai_Dashboard_Widgets::print_brand_colour_overrides()` in
`includes/class-dashboard.php`.

## Updates

Self-updates from its GitHub repo (`Bonsai-Systems/bonsai-dashboard`) using
[Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker).
Updates appear in **Dashboard → Updates**.

To release a new version:

1. Bump `Version:` header and `BONSAI_DASHBOARD_VERSION` in `bonsai-dashboard.php`.
2. Add a `CHANGELOG.md` entry and push to `main`.
3. Create a GitHub Release tagged `vX.Y.Z` and attach a zip of the plugin
   folder (include `vendor/`, exclude `.git`).

`vendor/` is committed so the plugin works as a plain ZIP install with no
build step.

## Uninstall

`uninstall.php` removes this plugin's one option (`bonsai_dashboard_settings`).
No other persisted state exists.

## Development notes

See `CLAUDE.md` for architecture and working conventions.
