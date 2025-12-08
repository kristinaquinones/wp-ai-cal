# AI Editorial Calendar

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Open Source](https://img.shields.io/badge/Open%20Source-Yes-success.svg)](https://opensource.org/)

A lightweight WordPress plugin that provides an editorial calendar with personalized AI content suggestions. Connect your own AI account to get intelligent post ideas based on your site's context and existing content.

## Features

- **Visual Calendar** - Monthly grid view of all posts and pages with color-coded status indicators
- **List View** - Table view with search, filtering, and pagination for all posts
- **Drag and Drop Scheduling** - Reschedule drafts and scheduled posts by dragging them to a new date (works in both calendar and list views)
- **AI Content Suggestions** - Get 3 personalized post ideas for any future date
- **One-Click Draft Creation** - Create drafts directly from AI suggestions
- **AI Suggestion Meta Box** - View AI suggestions in the post editor for drafts created from the calendar
- **Generate Outline** - Automatically generate a detailed blog post outline from AI suggestions
- **Multi-Provider Support** - Works with OpenAI, Anthropic, or Google AI
- **Minimal Configuration** - Just 3 settings to get started

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

### 1. Choose Your AI Provider

Select from:
- **OpenAI** - Uses GPT-4o-mini
- **Anthropic** - Uses Claude 3.5 Haiku
- **Google** - Uses Gemini 2.5 Flash Lite

### 2. Enter Your API Key

Get an API key from your chosen provider:
- OpenAI: https://platform.openai.com/api-keys
- Anthropic: https://console.anthropic.com/settings/keys
- Google AI: https://aistudio.google.com/apikey

_Review each provider for more information on costs and free credit availability._


### 3. Configure Content Settings

**Site Context** (max 500 characters)
Describe your website, target audience, and content goals. Example:

> A tech blog for small business owners covering productivity tools, automation, and digital marketing tips.

**Voice & Tone** (max 100 characters)
Define the writing style for suggestions. Example:

> professional but approachable, actionable

**Topics to Avoid** (max 500 characters)
List topics, phrases, or approaches the AI should not suggest. Example:

> politics, competitor mentions, overly technical jargon, clickbait titles

## Usage

### Viewing the Calendar

Navigate to **Editorial Calendar** in the WordPress admin menu. You can switch between two views:

**Calendar View:**
- Monthly grid view of all posts and pages
- Color-coded status:
  - **Green** - Published
  - **Yellow** - Draft
  - **Cyan** - Pending Review
  - **Purple** - Scheduled

**List View:**
- Table format showing all posts with pagination
- Search and filter by status or post type
- Sortable columns for date, title, status, and category
- Drag and drop to reorder posts and update dates

### Drag and Drop Scheduling

Easily reschedule your content by dragging posts to a new date. Works in both Calendar and List views:

**Calendar View:**
1. Hover over a draft, pending, or scheduled post (cursor changes to grab icon)
2. Drag the post to your desired date
3. Drop it on the target day - the post date updates instantly

**List View:**
1. Hover over a draggable row (indicated by the drag handle: ⋮⋮)
2. Click and drag the row to another draggable row
3. Drop it - the post date updates to match the target row's date

**Note:** Published posts cannot be dragged to prevent accidental changes to live content.

### Getting AI Suggestions

1. Click on any future date in the calendar
2. Click **Get AI Suggestions**
3. The AI will analyze your recent content and generate 3 unique post ideas
4. Click **Create Draft** on any suggestion to instantly create a draft post

The draft will be scheduled for a random time on the selected date, with the AI's description saved in the post editor.

**Note:** AI suggestions are only available for today and future dates. Past dates are view-only for existing content.

### AI Suggestion Meta Box

When you create a draft from an AI suggestion, a special meta box appears in the post editor:

- **View AI Suggestion** - See the original AI-generated description that inspired the post
- **Generate an Outline** - Click to automatically create a detailed blog post outline with:
  - Introduction section
  - 3-5 main body sections with subheadings
  - Bullet points for key points
  - Conclusion with call-to-action

The outline is formatted in HTML and inserted directly into your post content, ready for you to expand into a full article.

**Note:** The AI Suggestion meta box only appears for drafts that were created from the AI Editorial Calendar.

## Supported AI Models

| Provider | Model | Cost |
|----------|-------|------|
| OpenAI | gpt-4o-mini | ~$0.001 per suggestion |
| Anthropic | claude-3-5-haiku-latest | ~$0.001 per suggestion |
| Google | gemini-2.5-flash-lite | Free tier available |

## Security

- API keys are stored in the WordPress options table (protected by database security)
- All AJAX requests are protected with nonce verification
- Capability checks ensure only authorized users can access features
- Settings page requires `manage_options` capability

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
