# Translations

Drop translation files for the `ai-editorial-calendar` text domain here:

- `ai-editorial-calendar.pot` — the template (generate with `wp i18n make-pot . languages/ai-editorial-calendar.pot`).
- `ai-editorial-calendar-{locale}.po` / `.mo` — per-locale translations (e.g. `ai-editorial-calendar-es_ES.mo`).

The plugin loads this directory via `load_plugin_textdomain()` on `init`.
