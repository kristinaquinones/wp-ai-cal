<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap aiec-wrap">
    <div class="aiec-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p class="aiec-subtitle"><?php esc_html_e('Configure your AI provider and content preferences', 'ai-editorial-calendar'); ?></p>
    </div>

    <?php if (isset($_GET['settings-updated']) && sanitize_text_field(wp_unslash($_GET['settings-updated'])) === 'true'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Settings saved successfully.', 'ai-editorial-calendar'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['aiec-deleted']) && sanitize_text_field(wp_unslash($_GET['aiec-deleted'])) === 'true'): ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php esc_html_e('All settings have been deleted.', 'ai-editorial-calendar'); ?></p>
        </div>
    <?php endif; ?>

    <div class="aiec-settings-card">
        <form method="post" action="options.php">
            <?php settings_fields('aiec_settings'); ?>

            <table class="form-table aiec-form-table">
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
                        <?php esc_html_e('Enter your API key.', 'ai-editorial-calendar'); ?>
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
                    <textarea name="aiec_site_context" id="aiec_site_context" rows="3" class="large-text" maxlength="500"><?php echo esc_textarea(get_option('aiec_site_context', '')); ?></textarea>
                    <p class="description"><?php esc_html_e('Describe your site, audience, and content goals. (Max 500 characters)', 'ai-editorial-calendar'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="aiec_tone"><?php esc_html_e('Voice & Tone', 'ai-editorial-calendar'); ?></label>
                </th>
                <td>
                    <input type="text" name="aiec_tone" id="aiec_tone" class="regular-text" maxlength="100" value="<?php echo esc_attr(get_option('aiec_tone', '')); ?>" placeholder="<?php esc_attr_e('e.g., professional, casual, witty, educational', 'ai-editorial-calendar'); ?>">
                    <p class="description"><?php esc_html_e('The writing style for suggestions. (Max 100 characters)', 'ai-editorial-calendar'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="aiec_avoid"><?php esc_html_e('Topics to Avoid', 'ai-editorial-calendar'); ?></label>
                </th>
                <td>
                    <textarea name="aiec_avoid" id="aiec_avoid" rows="2" class="large-text" maxlength="500"><?php echo esc_textarea(get_option('aiec_avoid', '')); ?></textarea>
                    <p class="description"><?php esc_html_e('Topics, phrases, or approaches the AI should not suggest. (Max 500 characters)', 'ai-editorial-calendar'); ?></p>
                </td>
            </tr>
            </table>

            <?php submit_button(null, 'primary', 'submit', false, ['class' => 'aiec-btn aiec-btn-primary']); ?>
        </form>
    </div>

    <div class="aiec-settings-card aiec-danger-zone">
        <h2><?php esc_html_e('Danger Zone', 'ai-editorial-calendar'); ?></h2>
        <p class="aiec-description"><?php esc_html_e('Remove all plugin data. This cannot be undone.', 'ai-editorial-calendar'); ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="aiec-uninstall-form">
            <?php wp_nonce_field('aiec_uninstall', 'aiec_uninstall_nonce'); ?>
            <input type="hidden" name="action" value="aiec_uninstall">
            <button type="submit" class="aiec-btn aiec-btn-danger" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete all AI Editorial Calendar settings? This cannot be undone.', 'ai-editorial-calendar'); ?>');">
                <?php esc_html_e('Delete All Settings', 'ai-editorial-calendar'); ?>
            </button>
        </form>
    </div>

    <div class="aiec-attribution">
        <p>
            <?php
            printf(
                esc_html__('AI Editorial Calendar by %s', 'ai-editorial-calendar'),
                '<a href="https://github.com/kristinaquiones/wp-ai-cal" target="_blank" rel="noopener noreferrer">KQ</a>'
            );
            ?>
        </p>
    </div>
</div>
