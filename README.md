# AI Editorial Calendar

A lightweight WordPress plugin that provides an editorial calendar with personalized AI content suggestions. Connect your own AI account to get intelligent post ideas based on your site's context and existing content.

## Features

- **Visual Calendar** - Monthly grid view of all posts and pages with color-coded status indicators
- **Drag and Drop Scheduling** - Reschedule drafts and scheduled posts by dragging them to a new date
- **AI Content Suggestions** - Get 3 personalized post ideas for any future date
- **One-Click Draft Creation** - Create drafts directly from AI suggestions
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

Navigate to **Editorial Calendar** in the WordPress admin menu. The calendar displays:

- All posts and pages for the current month
- Color-coded status:
  - **Green** - Published
  - **Yellow** - Draft
  - **Blue** - Pending Review
  - **Gray** - Scheduled

### Drag and Drop Scheduling

Easily reschedule your content by dragging posts to a new date:

1. Hover over a draft, pending, or scheduled post (cursor changes to grab icon)
2. Drag the post to your desired date
3. Drop it on the target day - the post date updates instantly

**Note:** Published posts cannot be dragged to prevent accidental changes to live content.

### Getting AI Suggestions

1. Click on any future date in the calendar
2. Click **Get AI Suggestions**
3. The AI will analyze your recent content and generate 3 unique post ideas
4. Click **Create Draft** on any suggestion to instantly create a draft post

The draft will be scheduled for a random time on the selected date, with the AI's description saved as a hidden note in the post editor.

**Note:** AI suggestions are only available for today and future dates. Past dates are view-only for existing content.

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
