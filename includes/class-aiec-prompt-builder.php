<?php
/**
 * Prompt construction and cleanup for the AI Editorial Calendar.
 *
 * Pure text transforms: turns settings plus context into the prompts sent to the
 * provider, and normalizes the raw model output back into a clean outline.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIEC_Prompt_Builder {

    /**
     * Build the blog-post-idea suggestion prompt.
     *
     * @param string $context       Site context option.
     * @param string $tone          Voice/tone option.
     * @param string $avoid         Topics-to-avoid option.
     * @param array  $recent_titles Recent post titles for de-duplication.
     * @param string $date          Target date (Y-m-d).
     * @return string
     */
    public static function build_suggestions($context, $tone, $avoid, $recent_titles, $date) {
        // Sanitize date
        $date = sanitize_text_field($date);

        // Locale/context options
        $country = sanitize_text_field(get_option('aiec_country', ''));
        $region = sanitize_text_field(get_option('aiec_region', ''));
        $culture = sanitize_text_field(get_option('aiec_culture', ''));
        $belief = sanitize_text_field(get_option('aiec_belief', ''));
        $focus_type = sanitize_text_field(get_option('aiec_focus_type', 'mix')); // trends | evergreen | mix

        // Parse and format date for better context
        $date_obj = DateTime::createFromFormat('Y-m-d', $date);
        $formatted_date = $date;
        $date_context = '';
        $season = '';
        if ($date_obj) {
            $today = new DateTime();
            $diff = $today->diff($date_obj);
            $days_diff = (int) $diff->format('%r%a');

            if ($days_diff === 0) {
                $date_context = 'today';
            } elseif ($days_diff === 1) {
                $date_context = 'tomorrow';
            } elseif ($days_diff > 1 && $days_diff <= 7) {
                $date_context = sprintf('in %d days', $days_diff);
            } elseif ($days_diff < 0 && $days_diff >= -7) {
                $date_context = sprintf('%d days ago', abs($days_diff));
            } else {
                $date_context = 'on ' . $date_obj->format('F j, Y');
            }

            $formatted_date = $date_obj->format('l, F j, Y');

            // Derive a simple season from month (Northern Hemisphere approximation)
            $month = (int) $date_obj->format('n');
            if (in_array($month, [12, 1, 2], true)) {
                $season = 'Winter';
            } elseif (in_array($month, [3, 4, 5], true)) {
                $season = 'Spring';
            } elseif (in_array($month, [6, 7, 8], true)) {
                $season = 'Summer';
            } else {
                $season = 'Autumn';
            }
        }

        // Sanitize recent titles and provide context
        $sanitized_titles = array_map(function ($title) {
            return sanitize_text_field($title);
        }, array_slice(array_values($recent_titles), 0, 5));

        $titles_list = '';
        $recent_context = '';
        if (!empty($sanitized_titles)) {
            $titles_list = implode(', ', $sanitized_titles);
            $count = count($sanitized_titles);
            $recent_context = sprintf(
                ' The site has recently published these %d post%s: %s. Use these to understand the content themes and avoid duplication, but suggest fresh angles or complementary topics.',
                $count,
                $count > 1 ? 's' : '',
                $titles_list
            );
        }

        // Build prompt with sanitized inputs (context, tone, avoid are already sanitized via settings)
        $prompt = sprintf(
            'Suggest 3 unique blog post ideas for %s (%s).',
            $formatted_date,
            $date_context
        );

        if ($context) {
            $prompt .= sprintf(' Site context: %s.', $context);
        }

        if ($country || $region || $culture) {
            $locale_parts = array_slice(array_filter([$country, $region, $culture]), 0, 5);
            $prompt .= ' Locale: ' . implode(', ', $locale_parts) . '.';
        }
        if ($belief) {
            $belief_list = array_slice(array_filter(array_map('trim', explode(',', $belief))), 0, 5);
            if (!empty($belief_list)) {
                $prompt .= ' Belief/Cultural context: ' . implode(', ', $belief_list) . '.';
            }
        }

        if ($tone) {
            $prompt .= sprintf(' Writing tone: %s.', $tone);
        }
        if ($recent_context) {
            $prompt .= $recent_context;
        }
        if ($avoid) {
            $prompt .= sprintf(' Avoid these topics/approaches: %s.', $avoid);
        }

        if ($season) {
            $prompt .= sprintf(' Season: %s. Consider events/holidays within 4 weeks of the target date.', $season);
        }

        // Trends vs Evergreen focus
        if ($focus_type === 'trends') {
            $prompt .= ' Emphasize timely/trending topics tied to the target date and season.';
        } elseif ($focus_type === 'evergreen') {
            $prompt .= ' Emphasize evergreen topics that remain relevant year-round.';
        } else {
            $prompt .= ' Provide a balanced mix of timely/trending and evergreen angles.';
        }

        // Diversity and timeliness instructions (concise, no repeats)
        $prompt .= ' Return 3 suggestions: how-to; list/roundup; opinion/analysis. Avoid duplicates of recent titles; one-line Descs with a concrete hook and timely angle if relevant. Format: Title: X | Desc: Y (one line, no markup).';

        return $prompt;
    }

    /**
     * Build the outline/writing-guide prompt for a single post.
     *
     * @param string $title      Post title.
     * @param string $suggestion Stored AI suggestion description.
     * @param string $context    Site context option.
     * @param string $tone       Voice/tone option.
     * @return string
     */
    public static function build_outline($title, $suggestion, $context, $tone) {
        $prompt = "Create a writing guide for this blog post:\n\n";
        $prompt .= "Title: " . sanitize_text_field($title) . "\n";
        $prompt .= "Description: " . sanitize_textarea_field($suggestion) . "\n";

        if ($context) {
            $prompt .= "Context: " . sanitize_textarea_field($context) . "\n";
        }

        if ($tone) {
            $prompt .= "Tone: " . sanitize_text_field($tone) . "\n";
        }

        $prompt .= "\nFormat: Plain text only. Use markdown-style headings (## for main sections, ### for subsections).\n";
        $prompt .= "Structure: Introduction, 3 main sections with 2-3 bullet points each, Conclusion with CTA.\n\n";
        $prompt .= "- Writing instructions (e.g., 'Write an introduction that hooks the reader by...')\n";
        $prompt .= "- Content guidance (e.g., 'Introduction: Focus on explaining why this topic matters to the reader...')\n";
        $prompt .= "Each section should guide the author on:\n";
        $prompt .= "- What to write about (the content focus)\n";
        $prompt .= "- How to approach it (the writing style/angle)\n";
        $prompt .= "- What to accomplish (the goal of that section)\n\n";
        $prompt .= "Make headings action-oriented and guidance specific/actionable.\n";
        $prompt .= "Do NOT repeat the title or description in your output. Start directly with the Introduction section heading (## Introduction). Output only the writing guide, no explanations or metadata.";

        return $prompt;
    }

    /**
     * Normalize raw model output into a clean plain-text outline.
     *
     * @param string $outline Raw model output.
     * @return string
     */
    public static function clean_outline($outline) {
        // Remove HTML tags
        $outline = strip_tags($outline);

        // Remove markdown code blocks if present
        $outline = preg_replace('/```[\s\S]*?```/', '', $outline);

        // Remove markdown formatting characters that might interfere
        $outline = preg_replace('/\*\*(.*?)\*\*/', '$1', $outline); // Bold
        $outline = preg_replace('/\*(.*?)\*/', '$1', $outline); // Italic
        $outline = preg_replace('/`(.*?)`/', '$1', $outline); // Inline code

        // Clean up extra whitespace
        $outline = preg_replace('/\n{3,}/', "\n\n", $outline); // Max 2 consecutive newlines
        $outline = preg_replace('/[ \t]+/', ' ', $outline); // Multiple spaces to single space

        // Remove common AI response prefixes/suffixes.
        // Match only within the first line so a later heading like "## Introduction:"
        // can't be swallowed up to its colon ([^\n]*? instead of [\s\S]*?).
        $outline = preg_replace('/^(Here\'s|Here is|Below is|I\'ll create|I\'ve created|This outline|The outline|This writing guide|The writing guide)[^\n]*?:[ \t]*\n*/i', '', $outline);
        $outline = preg_replace('/\n*(Note:|Remember:|Tip:)[\s\S]*$/i', '', $outline);

        // Trim whitespace
        $outline = trim($outline);

        // Ensure it starts with content, not metadata
        $lines = explode("\n", $outline);
        $start_index = 0;
        foreach ($lines as $i => $line) {
            $line = trim($line);
            // Skip empty lines and common AI prefixes at start
            if (empty($line) || preg_match('/^(Title|Description|Context|Tone|Format|Structure|Writing guide|Guide):/i', $line)) {
                $start_index = $i + 1;
                continue;
            }
            // Found actual content (headings or text)
            if (preg_match('/^#|^[A-Z]|^[a-z]|^Write|^Focus|^Explain|^Describe/', $line)) {
                $start_index = $i;
                break;
            }
        }
        $outline = implode("\n", array_slice($lines, $start_index));

        return trim($outline);
    }
}
