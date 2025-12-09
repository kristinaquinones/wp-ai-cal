# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-12-08

- Monthly calendar view displaying posts with color-coded status indicators
- List view with table format, search, status filtering, and pagination
- Drag and drop scheduling for draft, pending, and scheduled posts (calendar and list views)
- AI Suggestion meta box in post editor for drafts created from calendar
- "Generate an Outline" button with automatic outline generation (plain text, block-editor friendly)
- Multi-provider AI support (OpenAI, Anthropic, Google)
  - Outline generation optimized (~62% token reduction, 2000→1500 max tokens)
- Settings page for AI provider, API key, site context, voice & tone, and topics to avoid
- Modal view for daily post details, responsive design, and uninstall cleanup
- AI-powered content suggestions with one-click draft creation
  - AI suggestion descriptions now visible in post editor meta box
  - Outline generation uses plain text format (markdown-style headings) instead of HTML for better block editor compatibility
  - Outline output automatically cleaned to remove HTML tags, markdown formatting, and extraneous AI response text
  - Confirmation prompt before regenerating outlines warns users about API credit usage

### Fixed

- Drag and drop in list view now correctly updates post dates (was using wrong data array and render method)

### Code Quality

- Refactored duplicate code: extracted primary category logic and post event building into reusable helper methods
- Consolidated repeated calendar URL references into single helper method
- Extracted provider name mapping to reusable helper method
- Fixed API method signature consistency (call_google now accepts max_tokens parameter)
- Added PHPDoc comments for helper methods
