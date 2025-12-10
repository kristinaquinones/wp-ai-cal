# AI Editorial Calendar

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Open Source](https://img.shields.io/badge/Open%20Source-Yes-success.svg)](https://opensource.org/)

A lightweight WordPress plugin that provides an editorial calendar with personalized AI content suggestions. Connect your own AI account to get intelligent post ideas based on your site's context and existing content.

## Features

### Content Management
- **Visual Calendar** - Monthly grid view of all posts with color-coded status indicators (green=published, yellow=draft, cyan=pending, indigo=scheduled)
- **List View** - Table view with search, filtering, sorting, and pagination for all posts
- **Drag and Drop Scheduling** - Reschedule drafts and scheduled posts by dragging them to a new date (calendar view only)
- **Quick Access** - Dashboard widget, admin bar link, and editor return buttons for seamless navigation

### AI-Powered Features
- **AI Content Suggestions** - Get 3 personalized post ideas for any future date based on your site's context
- **One-Click Draft Creation** - Create drafts directly from AI suggestions with a single click
- **AI Suggestion Meta Box** - View original AI suggestions in the post editor for drafts created from the calendar
- **Generate Outline** - Automatically create a detailed writing guide with structured sections (Introduction, 3 main sections with bullet points, Conclusion with CTA)
- **Multi-Provider Support** - Choose from OpenAI (GPT-4o-mini), Anthropic (Claude 3.5 Haiku), Google (Gemini 2.5 Flash Lite), or xAI (Grok-2)
- **Context-Aware** - AI analyzes your recent posts to avoid duplication and suggest complementary topics
- **Date Intelligence** - Suggestions consider seasons, holidays, and timely trends

### Configuration Options
- **Basic Settings** - AI provider, API key, site context (max 500 chars), voice & tone (max 100 chars), topics to avoid (max 500 chars)
- **Advanced Settings** (Optional) - Country/regional targeting (50+ countries), cultural/linguistic lens (40+ options), belief/religious context (20+ traditions), content focus (trends/evergreen/balanced)
- **Minimal Setup** - Just 3 required settings to get started, with powerful optional customization

## Requirements

- WordPress 5.0+
- PHP 7.4+
- An API key from one of the supported AI providers

## Installation

1. Download or clone this repository
2. Copy the plugin folder to `wp-content/plugins/ai-editorial-calendar`
3. Activate the plugin in WordPress Admin → Plugins
4. Navigate to Editorial Calendar → Settings to configure

## Configuration

### Basic Settings (Required)

#### 1. Choose Your AI Provider

Select from:
- **OpenAI** - Uses GPT-4o-mini
- **Anthropic** - Uses Claude 3.5 Haiku
- **Google** - Uses Gemini 2.5 Flash Lite
- **xAI Grok** - Uses Grok-2

See pricing table below for cost details.

#### 2. Enter Your API Key

Get an API key from your chosen provider:
- OpenAI: https://platform.openai.com/api-keys
- Anthropic: https://console.anthropic.com/settings/keys
- Google AI: https://aistudio.google.com/apikey
- xAI Grok: https://console.x.ai/

_Review each provider for information on costs and free credit availability._

#### 3. Configure Content Settings

**Site Context** (max 500 characters)
Describe your website, target audience, and content goals. Example:

> A tech blog for small business owners covering productivity tools, automation, and digital marketing tips.

**Voice & Tone** (max 100 characters)
Define the writing style for suggestions. Example:

> professional but approachable, actionable

**Topics to Avoid** (max 500 characters)
List topics, phrases, or approaches the AI should not suggest. Example:

> politics, competitor mentions, overly technical jargon, clickbait titles

### Advanced Settings (Optional)

Customize suggestions with additional context:

- **Country/Region** - Select from 50+ countries for localized suggestions and holiday awareness
- **Cultural Lens** - Choose from 40+ cultural/linguistic perspectives to tailor tone and references
- **Belief/Religious Context** - Add from 20+ religious/spiritual traditions for relevant observances
- **Content Focus** - Choose between trends (timely/seasonal), evergreen (always relevant), or balanced mix

_Note: Advanced options increase prompt complexity. Select only what's relevant to your audience._

## Usage

### Viewing the Calendar

Navigate to **Editorial Calendar** in the WordPress admin menu. You can switch between two views:

**Calendar View:**
- Monthly grid view of all posts
- Color-coded status:
  - **Green** - Published
  - **Yellow** - Draft
  - **Cyan** - Pending Review
  - **Indigo** - Scheduled

**List View:**
- Table format showing all posts with pagination
- Search and filter by status
- Sortable columns for date, title, status, and category

### Drag and Drop Scheduling

Easily reschedule your content by dragging posts to a new date in Calendar view:

1. Hover over a draft, pending, or scheduled post (cursor changes to grab icon)
2. Drag the post to your desired date
3. Drop it on the target day - the post date updates instantly

**Note:** Published posts cannot be dragged to prevent accidental changes to live content. Drag and drop is currently only available in Calendar view.

### Getting AI Suggestions

1. Click on any future date in the calendar
2. Click **Get AI Suggestions**
3. The AI will analyze your recent content and generate 3 unique post ideas
4. Click **Create Draft** on any suggestion to instantly create a draft post

The draft will be scheduled for a random time on the selected date, with the AI's description saved in the post editor.

**Note:** AI suggestions are only available for today and future dates. Past dates are view-only for existing content.

### AI Suggestion Meta Box

When you create a draft from an AI suggestion, a special meta box appears in the post editor sidebar:

- **View AI Suggestion** - See the original AI-generated description that inspired the post
- **Generate an Outline** - Click to automatically create a detailed writing guide with:
  - Introduction section with guidance on hooking the reader
  - 3 main body sections with specific content direction
  - Conclusion with call-to-action guidance
  - Markdown format with headings (## and ###) for easy editing

The outline provides structured writing guidance, telling you what to write about, how to approach it, and what to accomplish in each section. It's inserted directly into your post content, ready for you to expand into a full article.

**Important Notes:**
- The AI Suggestion meta box only appears for drafts created from the AI Editorial Calendar
- If you regenerate an outline for a post that already has content, you'll be prompted to confirm (uses API credits)
- Only posts are displayed in the calendar and list views (pages are excluded)
- Published posts cannot be dragged to prevent accidental changes to live content

### Quick Access & Navigation

The plugin provides multiple ways to access your editorial calendar:

- **Dashboard Widget** - Quick actions on your WordPress dashboard (New Post, Get AI Suggestions, View Calendar)
- **Admin Bar Link** - "Editorial Calendar" link in the WordPress admin bar for instant access
- **Editor Return Buttons** - When editing a post, use the "Return to Editorial Calendar" button to navigate back
- **Settings Link** - Quick access to settings from the Plugins page

## Supported AI Models

| Provider | Model |
|----------|-------|------|
| OpenAI | GPT-4o-mini |
| Anthropic | Claude 3.5 Haiku | 
| Google | Gemini 2.5 Flash Lite | 
| xAI Grok | Grok-2 |

## Security & Privacy

- **Secure Storage** - API keys are stored in the WordPress options table (protected by database security)
- **Request Protection** - All AJAX requests are protected with WordPress nonce verification
- **Access Control** - Capability checks ensure only authorized users can access features
- **Settings Security** - Settings page requires `manage_options` capability (administrator level)
- **Input Validation** - Comprehensive sanitization and validation of all user inputs
- **Token Limits** - Character limits prevent excessive API token usage (500 chars for context, 100 for tone)
- **API Cost Protection** - Max token caps (2000) prevent runaway API costs
- **Error Handling** - Robust error handling with retry logic for transient API failures
- **Debug Logging** - Error logging only when WP_DEBUG is enabled (excludes sensitive data)

## Performance & Reliability

- **Retry Logic** - Automatic retry for transient failures (network issues, rate limits, server errors)
- **Optimized Tokens** - Efficient prompts minimize API costs (~62% reduction for outlines)
- **Model Health Check** - Built-in diagnostics to verify AI provider connectivity
- **Clean Uninstall** - Complete data cleanup when plugin is removed

## License

GPL v2 or later

## Contributing

Contributions are welcome! Please open an issue or submit a pull request.

## Attribution

**AI Editorial Calendar**  
Copyright © 2025 [Kristina Quinones](https://github.com/kristinaquiones)

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the [GNU General Public License](https://www.gnu.org/licenses/gpl-2.0.html) for more details.

**Plugin Repository:** [https://github.com/kristinaquiones/wp-ai-cal](https://github.com/kristinaquiones/wp-ai-cal)
