# Copilot Instructions: AI Editorial Calendar

## Repository Overview

**AI Editorial Calendar** is a lightweight WordPress plugin (v1.0.0) that provides an editorial calendar with personalized AI content suggestions. The plugin integrates with multiple AI providers (OpenAI, Anthropic, Google, xAI) to generate blog post ideas and writing outlines based on site context and existing content.

**Repository Stats:**
- **Size:** ~488KB (12 files excluding .git)
- **Primary Language:** PHP (1,485 lines in main file)
- **Frontend:** JavaScript (1,269 total lines), CSS (1,535 lines)
- **Type:** WordPress Plugin (not a standalone application)
- **Requirements:** WordPress 5.0+, PHP 7.4+ (tested with PHP 8.3.6)

## Project Structure

### Key Files
```
wp-ai-editorial-calendar.php  # Main plugin file (1,485 lines) - all backend logic
templates/
  ├── calendar.php            # Calendar view template (188 lines)
  └── settings.php            # Settings page template (284 lines)
assets/
  ├── css/calendar.css        # All plugin styles (1,535 lines)
  └── js/
      ├── calendar.js         # Calendar UI logic (998 lines)
      ├── meta-box.js         # Post editor meta box (159 lines)
      └── editor-notice.js    # Editor navigation notice (112 lines)
uninstall.php                 # Cleanup script for plugin deletion (29 lines)
```

### Architecture
**Single-file plugin architecture:** All backend functionality is in `wp-ai-editorial-calendar.php` using a singleton pattern (`AI_Editorial_Calendar` class). No external dependencies - uses WordPress core functions and PHP's built-in cURL for API calls.

## Build & Validation

### Important: No Build Step Required
This is a pure PHP/JavaScript WordPress plugin with **NO build process, bundler, or compilation step**. Files are used directly as-is.

### Validation Commands

**1. PHP Syntax Check (Always run before committing PHP changes):**
```bash
# Check all PHP files for syntax errors
find . -name "*.php" -not -path "./.git/*" -exec php -l {} \;
```
Expected: "No syntax errors detected" for each file. PHP 7.4+ compatible.

**2. File Structure Validation:**
```bash
# Verify all required files exist
ls -1 wp-ai-editorial-calendar.php templates/calendar.php templates/settings.php assets/css/calendar.css assets/js/calendar.js uninstall.php
```
Expected: All files listed with no errors.

### No Tests
There is no test suite (no PHPUnit, Jest, or other testing frameworks). Manual testing requires a WordPress installation.

### No Linters Configured
No PHPCS, ESLint, or other linting tools are configured. Follow WordPress Coding Standards manually when making changes.

## Development Guidelines

### Making Code Changes

**1. PHP Changes (wp-ai-editorial-calendar.php, templates/, uninstall.php):**
- Always validate PHP syntax after changes: `php -l <filename>`
- Use WordPress core functions (never raw SQL unless using $wpdb properly)
- All AJAX handlers must: check nonces, verify capabilities, sanitize inputs
- API keys are stored in wp_options table via `get_option()`/`update_option()`
- Text must be internationalized: `__()`, `esc_html__()`, `esc_attr__()`

**2. JavaScript Changes (assets/js/):**
- `calendar.js`: Calendar rendering, drag-drop, AJAX calls, modal UI
- All AJAX calls use jQuery and must include `aiecData.nonce` for security
- Localized data available via `aiecData` global (see `enqueue_assets()` method)
- No JSX, no build step - vanilla JavaScript with jQuery

**3. CSS Changes (assets/css/calendar.css):**
- Uses CSS custom properties (`:root` variables) for theming
- No preprocessor (Sass/Less) - direct CSS only
- Mobile-first responsive design

### WordPress Integration Points

**Hooks Used:**
- `admin_menu` - Registers calendar and settings pages
- `admin_init` - Registers settings
- `admin_enqueue_scripts` - Loads assets on plugin pages
- `wp_ajax_*` - 9 AJAX endpoints (see lines 35-44 in main file)
- `add_meta_boxes` - AI suggestion meta box in post editor
- `admin_bar_menu` - Quick access link in admin bar

**Database Storage:**
- Options: `aiec_ai_provider`, `aiec_api_key`, `aiec_site_context`, `aiec_tone`, `aiec_avoid`, `aiec_country`, `aiec_region`, `aiec_culture`, `aiec_belief`, `aiec_focus_type`
- Post meta: `_aiec_ai_suggestion`, `_aiec_from_calendar`
- User meta: `aiec_*` (dismissed notices)

### API Integration

**Supported Providers:** OpenAI (GPT-4o-mini), Anthropic (Claude 3.5 Haiku), Google (Gemini 2.5 Flash Lite), xAI (Grok-2)

**API Call Methods:**
- `call_ai_api()` - Main wrapper with retry logic (lines 1102-1144)
- `call_openai()`, `call_anthropic()`, `call_google()`, `call_grok()` - Provider-specific implementations
- All calls use `wp_remote_post()` (WordPress HTTP API)
- Max tokens capped at 2000 to prevent excessive costs
- Retry logic for transient failures (429, 5xx errors, network issues)

**Testing APIs:** Requires valid API key from chosen provider. No mock/test mode available.

## Common Pitfalls & Workarounds

1. **API Key Sanitization:** Use `trim()` only, NOT `sanitize_text_field()` which can corrupt API keys (see line 148)
2. **WordPress Required:** Cannot test without a WordPress installation - this is not a standalone app
3. **No Build Process:** Do NOT add npm/composer build steps - files work as-is
4. **Published Posts:** Cannot be dragged/dropped to prevent accidental changes (intentional limitation)
5. **Post Type Filter:** Only `post` type shown in calendar, pages excluded (lines 569-576)

## Security Considerations

- All AJAX endpoints verify nonces (`check_ajax_referer()`)
- Capability checks on all admin operations (`current_user_can()`)
- Input sanitization: `sanitize_text_field()`, `sanitize_textarea_field()`, `wp_kses_post()`
- Output escaping: `esc_html()`, `esc_attr()`, `esc_url()`
- API keys stored in wp_options (secured by WordPress database security)
- Character limits prevent token abuse (500 chars context, 100 chars tone)

## Key Configuration Files

**None.** No package.json, composer.json, webpack config, or other build configs. Plugin uses WordPress's built-in systems for everything.

## CI/CD

**GitHub Workflow:** One workflow exists (`Copilot coding agent`) but it's for Copilot automation, not CI/CD validation. There are no automated tests, builds, or deployment pipelines to worry about.

## File Locations Quick Reference

- **Main logic:** `/wp-ai-editorial-calendar.php` (lines 1-1485)
- **Settings page:** `/templates/settings.php` (rendered via `render_settings_page()`)
- **Calendar UI:** `/templates/calendar.php` + `/assets/js/calendar.js`
- **AI API calls:** Lines 1102-1334 in main file
- **AJAX handlers:** Lines 448-1482 in main file (search for `public function ajax_`)
- **Sanitization:** Lines 110-149 in main file
- **Helper methods:** `get_calendar_url()` (160), `get_provider_name()` (170), `get_primary_category()` (186), `build_post_event()` (211)

## Trust These Instructions

These instructions are comprehensive and validated. Only search for additional information if:
- You need to understand specific WordPress core functions (refer to WordPress Codex)
- You encounter a behavior not documented here (check CHANGELOG.md for recent changes)
- You need specific API provider documentation for integration details

Otherwise, trust this document and make your changes confidently. The codebase is small, straightforward, and well-contained.
