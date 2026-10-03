# Bonsai Dashboard

WordPress admin plugin for The Bonsai Digital Collective — replaces the
default wp-admin dashboard with a single branded panel: a welcome message
plus a quick-links grid (Pages, Posts, Theme Setup, Team, Analytics, Support).

- **Maintained by:** Ben Ervine / The Bonsai Digital Collective
- **Version:** 1.2.0
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
| Custom cards | Any rows added under Bonsai → Dashboard | Whatever URL each card specifies, new tab if set |
| Support | Always — defaults to the Bonsai support desk | That URL, new tab |

Team is hidden automatically on a site that doesn't have a matching post type
registered, rather than linking to a 404 or permission error.

## Settings

**Bonsai → Dashboard** is split into tabs (the hub's tab bar, one tab
per page load, each with its own Save button): **Welcome**, **Quick links**,
**Colours** and **White Label**. Saving one tab never touches another's
values.

Welcome / Quick links: welcome heading/text (defaults to Bonsai
Digital Collective boilerplate copy, editable or clearable per site),
Analytics URL, Support
URL (defaults to `https://bonsaidigitalcollective.zendesk.com/hc/en-gb/requests/new`,
editable per site), the Team post type slug (in case a theme registers it
under a different name, e.g. `staff`), and a **custom cards** repeater for
anything else a site needs on its dashboard (a client portal, a booking
system, a shared drive) — each row is a label, URL, dashicon class, and an
"open in new tab" checkbox. A row with no label or URL is dropped when
settings are saved. See `includes/class-settings.php`.

**Colours** tab, all optional: welcome panel background/text, quick-link card icon/text, and
quick-link card hover background/icon-text (colour picker, clearable). Left
blank, each falls back to the Bonsai brand colours (or the active theme's ACF
brand colour fields, where set) — see
`Bonsai_Dashboard_Widgets::print_brand_colour_overrides()` in
`includes/class-dashboard.php`.

### White Label

Ported from the Vision Website plugin (TTNG) to replace White Label CMS.
Every field starts blank, and blank leaves WordPress as it is, so nothing
Bonsai-branded appears on a site until someone sets it up.

- **Branding**: hide WordPress branding (admin bar logo menu, footer credit,
  version number), a custom admin bar logo and link, custom admin footer
  text and link.
- **Login screen**: logo (optional width/height; otherwise the image's own
  proportions, up to 320px wide), background colour and image, and login box,
  label, button, button text, button hover and link colours.
- **Admin menus**: tick top-level menus to hide from non-agency users. Each
  user profile gets an **Agency user** checkbox under "Bonsai access". Agency
  users see the full admin. Everyone else, whatever their role, gets the
  trimmed menu, and hidden pages are blocked by URL as well.

Lockout safety: nothing is hidden until at least one agency user exists.
Until then any administrator can tick the box and see the White Label tab.
After that, only agency users can. The Dashboard and Profile are never
hidden. **Settings is hidden by default**, so once an agency user exists,
client admins lose access to Bonsai → Dashboard too. Untick
Settings on the White Label tab if a client should keep editing their
welcome message or quick links.

Deactivate White Label CMS once this is set up (the tab warns while it's
active). Don't run it alongside Vision Website's own White Label tab either.

## Bonsai menu

This plugin's screens live in the shared **Bonsai** admin menu, provided by [Bonsai Hub](https://github.com/Bonsai-Systems/bonsai-hub). A copy of the hub is bundled in `lib/bonsai-hub/`, so this plugin sets up the menu on its own. Other Bonsai plugins appear alongside it, and **Bonsai → Plugins** installs, activates and deactivates the rest of the suite.

- Don't edit `lib/bonsai-hub/` by hand. Change the bonsai-hub repo and run its `bin/sync.sh`.
- Old `options-general.php?page=bonsai-dashboard` links redirect to the new screen. The White Label tab's menu hiding hides **Bonsai** from non-agency users by default.
- Release zips must include `lib/`.

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

`uninstall.php` removes this plugin's options (`bonsai_dashboard_settings`,
`bonsai_dashboard_white_label`, `bonsai_dashboard_hidden_menus`) and the
`bonsai_dashboard_agency_user` user meta from every user.

## Development notes

See `CLAUDE.md` for architecture and working conventions.
