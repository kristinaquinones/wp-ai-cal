<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields('aiec_settings'); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="aiec_ai_provider"><?php esc_html_e('AI Provider', 'ai-editorial-calendar'); ?></label>
                </th>
                <td>
                    <select name="aiec_ai_provider" id="aiec_ai_provider">
                        <option value="openai" <?php selected(get_option('aiec_ai_provider'), 'openai'); ?>>OpenAI (GPT)</option>
                        <option value="anthropic" <?php selected(get_option('aiec_ai_provider'), 'anthropic'); ?>>Anthropic (Claude)</option>
                        <option value="google" <?php selected(get_option('aiec_ai_provider'), 'google'); ?>>Google (Gemini)</option>
                    </select>
                    <p class="description"><?php esc_html_e('Select your AI provider. You\'ll need an API key from your chosen provider.', 'ai-editorial-calendar'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="aiec_api_key"><?php esc_html_e('API Key', 'ai-editorial-calendar'); ?></label>
                </th>
                <td>
                    <input type="password" name="aiec_api_key" id="aiec_api_key" class="regular-text" placeholder="<?php echo get_option('aiec_api_key') ? '••••••••••••••••' : ''; ?>">
                    <p class="description">
                        <?php esc_html_e('Enter your API key. It will be encrypted before storage.', 'ai-editorial-calendar'); ?>
                        <br>
                        <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer">OpenAI</a> |
                        <a href="https://console.anthropic.com/settings/keys" target="_blank" rel="noopener noreferrer">Anthropic</a> |
                        <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener noreferrer">Google AI</a>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="aiec_site_context"><?php esc_html_e('Site Context', 'ai-editorial-calendar'); ?></label>
                </th>
                <td>
                    <textarea name="aiec_site_context" id="aiec_site_context" rows="4" class="large-text"><?php echo esc_textarea(get_option('aiec_site_context', '')); ?></textarea>
                    <p class="description"><?php esc_html_e('Describe your site, audience, and content goals. This helps the AI provide better suggestions.', 'ai-editorial-calendar'); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
