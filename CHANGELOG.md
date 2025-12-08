# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-12-08

- Monthly calendar view displaying posts with color-coded status indicators
- List view with table format, search, status filtering, and pagination
- Drag and drop scheduling for draft, pending, and scheduled posts (calendar and list views)
- AI-powered content suggestions with one-click draft creation
- AI Suggestion meta box in post editor for drafts created from calendar
- "Generate an Outline" button with automatic HTML-formatted outline generation
- Multi-provider AI support (OpenAI, Anthropic, Google)
- Settings page for AI provider, API key, site context, voice & tone, and topics to avoid
- Modal view for daily post details, responsive design, and uninstall cleanup

### Fixed

- Drag and drop not working in list view (fixed event handling and link interference)
- Pages no longer appear in calendar or list view (posts only)

### Changed

- AI suggestion descriptions now visible in post editor meta box
- Outline generation optimized (~62% token reduction, 2000→1500 max tokens)
