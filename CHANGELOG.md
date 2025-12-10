# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

### Removed
- Drag and drop functionality in list view (deferred to future update due to technical complexity)

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
