# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.1] - 2026-06-14

Follow-up fixes from a code review of the 1.1.0 refactor.

### Security

- Bound `per_page` (and `page`) in the "all posts" AJAX endpoint to
  `CALENDAR_MAX_POSTS`, so a client can no longer request `-1` (unlimited) or a
  huge page size and exhaust memory. The sibling calendar endpoint was already
  capped (S5); this closes the same gap on the list endpoint.
- Apply the per-user AI rate limit to the model health-check endpoint, which made
  a real billable provider call with no budget gate.

### Fixed

- Rate limiter now uses a fixed (non-sliding) hourly window and only charges quota
  after a provider call actually succeeds. Previously the window's expiry was
  re-armed on every call (so it never aged out for steady users) and failed calls
  still burned quota.
- Outline cleanup no longer destroys a first heading that contains a colon (for
  example `## Introduction:`) when the model output begins with a prefix phrase;
  the prefix match is now constrained to its own line.
- Enforce the shared max-token cap for Google/Gemini via `maxOutputTokens`, so the
  cost bound now applies to every provider rather than three of four.

### Internal

- Consolidate the four-provider roster (keys + display labels) into a single
  `AIEC_Settings::get_providers()` source of truth, used by the sanitizer, the
  settings dropdown, the provider-name lookup, and the client dispatch.
- Run the API-key autoload hardening only on the key's save hooks instead of on
  every `admin_init`, avoiding a `wp_load_alloptions()` scan on unrelated pages.
- Extract a shared `require_ai_access()` gate for the paid AI AJAX handlers.

## [1.1.0] - 2026-05-31

Security, privacy, and code-quality remediation from a full plugin audit. The
audit IDs (S1–S6, Q1–Q5) are referenced in commit messages and inline comments.

### Security

- Gate AI suggestions and outline generation on the `publish_posts` capability
  instead of `edit_posts`, so Contributors can no longer spend the site owner's
  API credits (S1). **Note:** users with only the Contributor role lose access
  to the AI features.
- Add a per-user hourly rate limit (30 requests) to the paid AI endpoints (S1).
- Stop autoloading the stored API key, so the secret is no longer pulled into
  memory on every request (S2).
- Bound the calendar query at 500 posts instead of loading all matching posts,
  preventing memory exhaustion on busy sites (S5).
- Escape server-provided URLs and titles when interpolated into HTML attributes
  in the calendar UI (S6).

### Changed

- Self-host the DM Sans and Space Mono fonts (SIL OFL 1.1) instead of loading
  them from the Google Fonts CDN, removing an external request (and IP/User-Agent
  leak) from wp-admin (S3).
- Reduce the AI request timeout to 20s and the retry budget to a single retry, so
  a request can no longer block a PHP worker for an extended time (S4).
- Hide AI spend controls in the calendar, dashboard widget, and post editor for
  users who aren't permitted to use them.

### Internal

- Consolidate the four provider clients onto a shared request transport (Q1).
- Extract a shared JavaScript escaping utility, `assets/js/utils.js` (Q2).
- Split the monolithic plugin class into `AIEC_Settings`, `AIEC_AI_Client`, and
  `AIEC_Prompt_Builder` service classes under `includes/` (Q3).
- Load the plugin text domain for translations via `load_plugin_textdomain()` (Q5).
- Add a stub-WordPress smoke test at `tests/smoke-test.php`.

## [1.0.0] - 2025-12-08

### Added

#### Core Features
- Monthly calendar view with color-coded status indicators (green=published, yellow=draft, cyan=pending, indigo=scheduled)
- List view with table format, search, status filtering, and pagination
- Drag and drop scheduling for draft, pending, and scheduled posts in calendar view
- AI-powered content suggestions with one-click draft creation
- Multi-provider AI support: OpenAI (GPT-4o-mini), Anthropic (Claude 3.5 Haiku), Google (Gemini 2.5 Flash Lite), xAI (Grok-2)
- AI Suggestion meta box in post editor for drafts created from the calendar
- "Generate an Outline" button with automatic markdown-formatted outline generation
- Modal view for daily post details with responsive design

#### User Interface Enhancements
- Dashboard widget with quick actions (New Post, Get AI Suggestions, View Calendar)
- Admin bar link for quick access to editorial calendar
- Editor return links and notices for seamless navigation between post editor and calendar
- View toggle between calendar and list modes
- Month navigation controls (Previous, Today, Next)

#### Settings & Configuration
- Basic settings: AI provider selection, API key, site context, voice & tone, topics to avoid
- Advanced settings (optional):
  - Country/regional targeting (50+ countries available)
  - Cultural/linguistic lens customization (40+ options)
  - Belief/religious context (20+ traditions)
  - Content focus (trends, evergreen, or balanced mix)
- Settings page with intuitive form layout and searchable filter lists
- Uninstall functionality with complete data cleanup

#### AI Features
- Context-aware suggestions based on site content and recent posts
- Date-aware prompts with seasonal and timely recommendations
- Outline generation with structured writing guidance (Introduction, 3 main sections, Conclusion with CTA)
- Confirmation prompt before regenerating outlines to warn about API credit usage
- Model health check functionality for troubleshooting
- Retry logic for transient API failures (network issues, rate limits, server errors)

#### Developer Features
- Comprehensive security with nonce verification and capability checks
- Input validation and sanitization throughout
- Error handling and logging (when WP_DEBUG enabled)
- Character limits to control token usage (500 chars for context fields, 100 chars for tone)
- Max token caps (2000 max) to prevent excessive API costs
- Helper methods for code reusability and maintainability

### Changed
- Outline generation optimized for efficiency (~62% token reduction, 2000→1500 max tokens)
- AI suggestion descriptions now visible in post editor meta box
- Improved outline cleaning to remove extraneous AI response text and formatting

### Fixed
- Pages no longer appear in calendar or list view (posts only)
- Drafts created outside the calendar UI can now be dragged and dropped

### Security
- All AJAX requests protected with nonce verification
- Capability checks ensure only authorized users can access features
- API keys stored securely in WordPress options table
- Comprehensive input sanitization and validation
- Safe API key handling (prevents corruption during sanitization)

### Code Quality
- Extracted primary category logic into reusable helper method `get_primary_category()`
- Consolidated post event building into helper method `build_post_event()`
- Centralized calendar URL generation via `get_calendar_url()` helper
- Extracted provider name mapping to `get_provider_name()` helper
- Fixed API method signature consistency (call_google now accepts max_tokens parameter)
- Added comprehensive PHPDoc comments for helper methods
- Implemented clean code practices with DRY principles

## Other
### Deferred
- Drag and drop functionality in list view (deferred to future update due to technical complexity)
