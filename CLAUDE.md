# Repository Guidelines

This file is the canonical source of contributor and coding-agent guidance for this repository.

## Project structure and module organization

This repository contains the AI Editorial Calendar WordPress plugin. The root `wp-ai-editorial-calendar.php` file bootstraps the plugin, registers hooks, and coordinates admin and AJAX behavior. Keep focused backend logic in `includes/`:

- `class-aiec-settings.php`: settings, sanitization, permissions, and rate limits
- `class-aiec-ai-client.php`: provider requests, retries, and errors
- `class-aiec-prompt-builder.php`: prompt construction and output cleanup

Page markup belongs in `templates/`. Browser code lives in `assets/js/` and `assets/css/`; reuse shared JavaScript helpers from `assets/js/utils.js`. Translation files belong in `languages/`. `uninstall.php` owns deletion-time cleanup, and `tests/smoke-test.php` provides lightweight automated coverage.

## Build, test, and development commands

There is no build step, dependency manager, or bundler. WordPress loads the source files directly.

```bash
php tests/smoke-test.php
find . -name '*.php' -not -path './.git/*' -exec php -l {} \;
```

Run the smoke test after backend changes. Run PHP lint before every commit. For UI or integration work, install the repository under `wp-content/plugins/ai-editorial-calendar`, activate it, and test in WordPress 5.0+ with PHP 7.4+.

## CI/CD workflows

GitHub Actions under `.github/workflows/` validate pull requests and pushes to `main`. `ci.yml` runs PHP syntax checks and the smoke test on PHP 7.4, 8.2, and 8.4, plus JavaScript syntax checks. `plugin-check.yml` runs the official WordPress Plugin Check as an advisory job until the repository has an accepted warning baseline.

Pushing a `v*` tag runs `release.yml`, which packages runtime files as `ai-editorial-calendar.zip` and creates a draft GitHub release. Review the draft before publishing it. Dependabot checks pinned GitHub Actions weekly.

## Coding style and naming conventions

Follow WordPress Coding Standards manually; no PHPCS, ESLint, or formatter is configured. Use four-space indentation, WordPress escaping and sanitization functions, and translatable strings. Prefix plugin identifiers with `aiec_`; PHP classes use names such as `AIEC_AI_Client`. Keep vanilla JavaScript compatible with the existing jQuery-based admin code.

Do not use an em dash in documentation or code comments unless it is practical and necessary.

Before writing code, check for related implementations that can be reused. Consolidate repeated provider handling, escaping, configuration, styles, templates, and markup. Keep functions, classes, and files focused on one responsibility, and prefer centralized configuration over repeated hardcoded values.

## Testing guidelines

Extend `tests/smoke-test.php` when changing hooks, callbacks, access policy, rate limits, or pure prompt logic. The smoke test is not full WordPress integration coverage. Manually verify AJAX flows, drag and drop, settings, and AI-provider behavior in a real installation.

## Work summaries

After each meaningful batch of work, and before committing, write a concise summary file using the format `YYYY_MM_DD-summary.md`. Summaries should capture what changed, why it changed, validation performed, and any follow-up risks or open items. Keep them factual and short enough to scan before a commit.

Every summary must also include an ELI-16 section: two or three sentences in plain language that a smart 16-year-old could follow, explaining what changed and why it matters. No jargon, no internal shorthand.

## Security and configuration

Every AJAX handler must verify a nonce, check the appropriate capability, sanitize input, and escape output. Use WordPress HTTP and options APIs. Keep API keys out of autoloaded options, logs, fixtures, and commits. Preserve the existing paid-AI access policy and request rate limits unless a change explicitly requires otherwise.

## Commit and pull request guidelines

Prefer short, imperative commit subjects with prefixes used in history, such as `fix:`, `refactor:`, and `chore:`. Keep each commit focused. Pull requests should explain behavior changes, list validation performed, link related issues, and include screenshots for admin UI changes. Call out security-sensitive changes involving nonces, capabilities, API keys, sanitization, or output escaping.
