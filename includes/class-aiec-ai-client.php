<?php
/**
 * AI provider client for the AI Editorial Calendar.
 *
 * Owns all outbound calls to the AI providers: request transport, retry logic,
 * per-provider request/response shapes, max-token validation, and error logging.
 * Knows nothing about WordPress admin pages or hooks.
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIEC_AI_Client {

    // Per-request HTTP timeout for provider calls, in seconds (see audit S4).
    const REQUEST_TIMEOUT = 20;

    // Hard cap on requested tokens, to bound per-call provider cost.
    const MAX_TOKENS_CAP = 2000;

    /**
     * Generate a completion from the given provider.
     *
     * @param string $provider   Provider key (openai, anthropic, google, grok).
     * @param string $api_key    Provider API key.
     * @param string $prompt     Prompt text.
     * @param int    $max_tokens Requested max tokens (capped internally).
     * @return string|WP_Error Generated text or an error.
     */
    public function generate($provider, $api_key, $prompt, $max_tokens = 500) {
        if (empty($api_key)) {
            return new WP_Error('api_error', __('API key is required', 'ai-editorial-calendar'));
        }

        if (empty($prompt)) {
            return new WP_Error('api_error', __('Prompt cannot be empty', 'ai-editorial-calendar'));
        }

        $max_tokens = $this->validate_max_tokens($max_tokens);

        // Define API call function for retry logic
        $api_call = function () use ($provider, $api_key, $prompt, $max_tokens) {
            switch ($provider) {
                case 'openai':
                    return $this->call_openai($api_key, $prompt, $max_tokens);
                case 'anthropic':
                    return $this->call_anthropic($api_key, $prompt, $max_tokens);
                case 'google':
                    return $this->call_google($api_key, $prompt, $max_tokens);
                case 'grok':
                    return $this->call_grok($api_key, $prompt, $max_tokens);
                default:
                    return new WP_Error('invalid_provider', __('Invalid AI provider', 'ai-editorial-calendar'));
            }
        };

        $response = $this->call_api_with_retry($api_call);

        // Log errors for debugging (only if WP_DEBUG is enabled)
        if (is_wp_error($response)) {
            $this->log_api_error($provider, $response, [
                'prompt_length' => strlen($prompt),
                'max_tokens' => $max_tokens,
            ]);
        }

        return $response;
    }

    /**
     * Shared transport for every provider.
     *
     * Does the POST and all the common envelope handling (transport errors,
     * non-200 status, malformed JSON, provider error objects), then hands the
     * decoded body to a provider-specific extractor that pulls out the text.
     *
     * @param string   $url     Endpoint URL.
     * @param array    $headers Request headers, including auth.
     * @param array    $body    Request payload (JSON-encoded here).
     * @param callable $extract Receives the decoded body array, returns the text.
     * @return string|WP_Error
     */
    private function request_completion($url, $headers, $body, $extract) {
        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body' => wp_json_encode($body),
            'timeout' => self::REQUEST_TIMEOUT,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $decoded = json_decode(wp_remote_retrieve_body($response), true);
        $response_code = wp_remote_retrieve_response_code($response);

        if ($response_code !== 200) {
            $error_message = __('API request failed with status code: ', 'ai-editorial-calendar') . $response_code;
            if (isset($decoded['error']['message'])) {
                $error_message = sanitize_text_field($decoded['error']['message']);
            }
            return new WP_Error('api_error', $error_message);
        }

        if (!is_array($decoded)) {
            return new WP_Error('api_error', __('Invalid API response format', 'ai-editorial-calendar'));
        }

        if (isset($decoded['error'])) {
            $error_message = isset($decoded['error']['message']) ? sanitize_text_field($decoded['error']['message']) : __('API error', 'ai-editorial-calendar');
            return new WP_Error('api_error', $error_message);
        }

        $content = call_user_func($extract, $decoded);
        if (empty($content)) {
            return new WP_Error('api_error', __('Empty response from API', 'ai-editorial-calendar'));
        }

        return $content;
    }

    /**
     * OpenAI-compatible chat completions. OpenAI and xAI Grok share this schema,
     * so they differ only by endpoint and model name.
     */
    private function call_openai_compatible($url, $api_key, $model, $prompt, $max_tokens) {
        return $this->request_completion(
            $url,
            [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'max_tokens' => $max_tokens,
            ],
            function ($body) {
                return $body['choices'][0]['message']['content'] ?? '';
            }
        );
    }

    private function call_openai($api_key, $prompt, $max_tokens = 500) {
        return $this->call_openai_compatible(
            'https://api.openai.com/v1/chat/completions',
            $api_key,
            'gpt-4o-mini', // Cost-effective OpenAI model
            $prompt,
            $max_tokens
        );
    }

    private function call_grok($api_key, $prompt, $max_tokens = 500) {
        return $this->call_openai_compatible(
            'https://api.x.ai/v1/chat/completions',
            $api_key,
            'grok-2', // xAI Grok model
            $prompt,
            $max_tokens
        );
    }

    private function call_anthropic($api_key, $prompt, $max_tokens = 500) {
        return $this->request_completion(
            'https://api.anthropic.com/v1/messages',
            [
                'x-api-key' => $api_key,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ],
            [
                'model' => 'claude-3-5-haiku-latest',
                'max_tokens' => $max_tokens,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ],
            function ($body) {
                return $body['content'][0]['text'] ?? '';
            }
        );
    }

    private function call_google($api_key, $prompt, $max_tokens = 500) {
        // Gemini ignores max_tokens here (no maxOutputTokens set), matching prior behavior.
        unset($max_tokens);
        return $this->request_completion(
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent',
            [
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $api_key,
            ],
            [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
            ],
            function ($body) {
                return $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
            }
        );
    }

    /**
     * Validate max_tokens parameter to prevent abuse.
     *
     * @param int $max_tokens Requested max tokens
     * @return int Validated max tokens (capped)
     */
    private function validate_max_tokens($max_tokens) {
        $max_tokens = absint($max_tokens);
        return min($max_tokens, self::MAX_TOKENS_CAP);
    }

    /**
     * Log API errors for debugging (without sensitive data).
     *
     * @param string          $provider Provider name
     * @param WP_Error|string $error    Error object or message
     * @param array           $context  Additional context (without sensitive data)
     */
    private function log_api_error($provider, $error, $context = []) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return; // Only log if debug mode is enabled
        }

        $error_message = is_wp_error($error) ? $error->get_error_message() : $error;
        $log_data = [
            'provider' => $provider,
            'error' => $error_message,
            'context' => $context,
            'timestamp' => current_time('mysql'),
        ];

        error_log('AI Editorial Calendar API Error: ' . wp_json_encode($log_data));
    }

    /**
     * Make API call with retry logic for transient failures.
     *
     * @param callable $api_call    Function that makes the API call
     * @param int      $max_retries Maximum number of retry attempts
     * @param int      $retry_delay Delay between retries in seconds (uses sleep, which blocks)
     * @return mixed API response or WP_Error
     */
    private function call_api_with_retry($api_call, $max_retries = 1, $retry_delay = 1) {
        $attempt = 0;
        $last_error = null;

        while ($attempt <= $max_retries) {
            $response = call_user_func($api_call);

            // If successful, return response
            if (!is_wp_error($response)) {
                return $response;
            }

            $last_error = $response;
            $error_code = $response->get_error_code();

            // Only retry on transient errors (network issues, rate limits, server errors)
            $transient_errors = [
                'http_request_failed',
                'api_error', // Check if it's a 429, 500, 502, 503, 504
            ];

            // Check if error message indicates a transient error
            $error_message = $response->get_error_message();
            $is_transient = false;

            // Check for rate limiting (429)
            if (strpos($error_message, '429') !== false || strpos($error_message, 'rate limit') !== false) {
                $is_transient = true;
                $retry_delay = 3; // Longer delay for rate limits
            }

            // Check for server errors (5xx)
            if (preg_match('/\b(50[0-9]|502|503|504)\b/', $error_message)) {
                $is_transient = true;
                $retry_delay = 2; // Moderate delay for server errors
            }

            // Check for network errors
            if (in_array($error_code, $transient_errors, true) || $is_transient) {
                $attempt++;

                if ($attempt <= $max_retries) {
                    sleep($retry_delay);
                    continue;
                }
            }

            // Non-transient error or max retries reached
            break;
        }

        return $last_error;
    }
}
