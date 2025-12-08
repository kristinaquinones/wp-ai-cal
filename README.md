# AI Editorial Calendar

A lightweight WordPress plugin that provides an editorial calendar with personalized AI content suggestions. Connect your own AI account to get intelligent post ideas based on your site's context and existing content.

## Features

- **Visual Calendar** - Monthly grid view of all posts and pages with color-coded status indicators
- **Drag and Drop Scheduling** - Reschedule drafts and scheduled posts by dragging them to a new date
- **AI Content Suggestions** - Get 3 personalized post ideas for any date based on your site context
- **Multi-Provider Support** - Works with OpenAI, Anthropic, or Google AI
- **Secure API Storage** - API keys are encrypted using WordPress authentication salts
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
- **Google** - Uses Gemini 1.5 Flash

### 2. Enter Your API Key

Get an API key from your chosen provider:
- OpenAI: https://platform.openai.com/api-keys
- Anthropic: https://console.anthropic.com/settings/keys
- Google AI: https://aistudio.google.com/apikey

### 3. Add Site Context

Describe your website, target audience, and content goals. This helps the AI generate more relevant suggestions. Example:

> A tech blog for small business owners covering productivity tools, automation, and digital marketing tips. Our audience is non-technical but eager to leverage technology.

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

1. Click on any day in the calendar
2. Click **Get AI Suggestions**
3. The AI will analyze your recent content and generate 3 unique post ideas
4. Use these suggestions to create new content that complements your existing posts

## Supported AI Models

| Provider | Model | Cost |
|----------|-------|------|
| OpenAI | gpt-4o-mini | ~$0.001 per suggestion |
| Anthropic | claude-3-5-haiku-latest | ~$0.001 per suggestion |
| Google | gemini-1.5-flash | Free tier available |

## Security

- API keys are encrypted before storage using WordPress LOGGED_IN_KEY and LOGGED_IN_SALT
- All AJAX requests are protected with nonce verification
- Capability checks ensure only authorized users can access features

## License

GPL v2 or later

## Contributing

Contributions are welcome! Please open an issue or submit a pull request.
