---
name: create-wordpress-extension
description: Scaffold and register a new custom Tagore WordPress plugin or theme in this workspace. Use when creating a new `flz_` plugin or `flz-` classic/block theme, including local rules, tests, inventory entry, and runtime-link plan; do not use to modify an existing component or copy an installed third-party theme.
---

# Create a WordPress extension

## Decide before scaffolding

1. Determine plugin versus theme from responsibility: durable behavior/data
   belongs in a plugin; presentation/templates belong in a theme.
2. For a theme, decide Classic or Block Theme and document the reason.
3. Confirm slug and textdomain: `flz_<name>` for plugins,
   `flz-<name>` for themes. Confirm ownership, supported WordPress/PHP range,
   data model, capabilities, external dependencies and delivery expectations.
4. Stop if the request depends on copying `deep-light`, a production theme or
   another third-party component until license, provenance and update model
   are approved.

## Scaffold

For a plugin, create `repositories/<slug>/<slug>.php` with a valid WordPress
plugin header, guard direct access, keep bootstrap/hook registration thin, and
add `tests/`.

For a Classic Theme, create `repositories/<slug>/style.css`, `functions.php`,
`index.php`, `README.md`, `AGENTS.md` and `tests/`. For a Block Theme, create
`style.css`, valid `theme.json`, `templates/index.html`, `README.md`,
`AGENTS.md` and `tests/`. Add only assets and template parts required by the
concrete request.

Initialize the component directory as its own Git repository on `main`. The
local `AGENTS.md` must be self-contained because the coordinator's root rules
do not apply inside that repository; preserve the same safety and verification
contracts without relying on an unavailable parent rule.

## Register and connect

1. Add exactly one row to `config/workspace-components.tsv` with source type,
   slug and expected relative runtime link.
2. Do not hand-maintain a second component list.
3. Plan the symlink into `../tagore-local/public/wp-content/plugins/` or
   `themes/`. Run `scripts/link-local-components` only when local runtime
   mutation is explicitly authorized; never overwrite a real directory or an
   unexpected link.
4. Do not activate the plugin, switch the theme, import data or change DDEV
   merely as part of scaffolding without explicit authorization.

## Verify

- Run the component's initial header/structure tests and PHP/JSON syntax.
- Run `scripts/check-workspace-structure` and `scripts/check-fast`.
- If runtime work is authorized, verify link, WP-CLI recognition, assets and
  visible UI. Theme switching is a separate state-changing step.
- Report unresolved product, privacy, permission, migration and delivery
  decisions. Do not call the scaffold complete if its inventory, local rules,
  tests or structural checks are missing.
