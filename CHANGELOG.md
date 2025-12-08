# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-12-08

### Added

- Monthly calendar view displaying posts and pages with color-coded status indicators
- Drag and drop scheduling for draft, pending, and scheduled posts
- AI-powered content suggestions based on site context and recent posts
- One-click draft creation from AI suggestions with hidden description notes
- Support for multiple AI providers:
  - OpenAI (GPT-4o-mini)
  - Anthropic (Claude 3.5 Haiku)
  - Google (Gemini 2.5 Flash Lite)
- Settings page with:
  - AI provider selection
  - API key storage
  - Site context configuration (max 500 characters)
  - Voice & tone setting (max 100 characters)
  - Topics to avoid setting (max 500 characters)
  - Delete all settings option
- Post titles wrap and truncate after 2 lines in calendar view
- Modal view for daily post details
- Past dates are view-only (existing posts can be clicked, but no new posts or suggestions)
- Automatic filtering of placeholder posts (e.g., "Hello World") from AI context
- Quick link to create new posts from any future date
- Responsive design for mobile and tablet devices
- Success/warning notices on settings save
- Uninstall cleanup for plugin options
