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
                        <option value="grok" <?php selected(get_option('aiec_ai_provider'), 'grok'); ?>>xAI Grok</option>
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
                        <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener noreferrer">Google AI</a> |
                        <a href="https://console.x.ai/" target="_blank" rel="noopener noreferrer">xAI Grok</a>
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
            <tr>
                <th colspan="2">
                    <details class="aiec-advanced-options">
                        <summary><?php esc_html_e('Advanced Options (Country, Culture, Content Focus)', 'ai-editorial-calendar'); ?></summary>
                        <p class="description" style="margin: 8px 0 12px 0;"><?php esc_html_e('Note: Selecting many advanced options can increase prompt length and token usage.', 'ai-editorial-calendar'); ?></p>
                        <table class="form-table aiec-form-table aiec-advanced-table">
                            <tr>
                                <th scope="row">
                                    <label><?php esc_html_e('Country (optional)', 'ai-editorial-calendar'); ?></label>
                                </th>
                                <td>
                                    <?php
                                    $countries = [
                                        'United States','Canada','Mexico','United Kingdom','Ireland','Australia','New Zealand','Germany','France','Spain','Italy','Portugal','Netherlands','Belgium','Sweden','Norway','Denmark','Finland','Switzerland','Austria','Poland','Czech Republic','Hungary','Greece','Turkey','Israel','United Arab Emirates','Saudi Arabia','South Africa','Nigeria','Kenya','Egypt','India','Pakistan','Bangladesh','Sri Lanka','Singapore','Malaysia','Indonesia','Philippines','Vietnam','Thailand','Japan','South Korea','China','Hong Kong','Taiwan','Brazil','Argentina','Chile','Colombia','Peru'
                                    ];
                                    $saved_countries = array_filter(array_map('trim', explode(',', get_option('aiec_country', ''))));
                                    ?>
                                    <input type="search" class="aiec-filter-input" data-target="#aiec-country-list" placeholder="<?php esc_attr_e('Search countries...', 'ai-editorial-calendar'); ?>" style="margin-bottom:6px; width: 260px;">
                                    <div id="aiec-country-list" class="aiec-filter-list" data-max="5">
                                        <?php foreach ($countries as $country): ?>
                                            <label class="aiec-filter-item">
                                                <input type="checkbox" name="aiec_country[]" value="<?php echo esc_attr($country); ?>" <?php checked(in_array($country, $saved_countries, true)); ?> />
                                                <?php echo esc_html($country); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="description"><?php esc_html_e('Select one or more countries for localized suggestions. (Optional)', 'ai-editorial-calendar'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php esc_html_e('Cultural Lens (optional)', 'ai-editorial-calendar'); ?></label>
                                </th>
                                <td>
                                    <?php
                                    $cultures = [
                                        'English - US','English - UK','English - Australia/NZ','English - Canada','English - India',
                                        'Spanish - LATAM','Spanish - Spain','French - France','French - Canada','German','Italian','Portuguese - Brazil','Portuguese - Europe',
                                        'Chinese - Mainland','Chinese - HK/Taiwan','Japanese','Korean','Hindi','Arabic - Gulf','Arabic - Levant','Arabic - North Africa',
                                        'Russian','Polish','Dutch','Swedish','Norwegian','Danish','Finnish',
                                        'Hebrew','Turkish','Greek',
                                        'APAC (general)','LATAM (general)','Middle East (general)','Africa - Anglophone','Africa - Francophone',
                                        'Nordic','Benelux','Iberian','Mediterranean','Slavic','Central Europe','Eastern Europe','Caribbean','Oceania','ASEAN','South Asia','North America - General','Europe - General'
                                    ];
                                    $saved_cultures = array_filter(array_map('trim', explode(',', get_option('aiec_culture', ''))));
                                    ?>
                                    <input type="search" class="aiec-filter-input" data-target="#aiec-culture-list" placeholder="<?php esc_attr_e('Search cultural lenses...', 'ai-editorial-calendar'); ?>" style="margin-bottom:6px; width: 260px;">
                                    <div id="aiec-culture-list" class="aiec-filter-list" data-max="5">
                                        <?php foreach ($cultures as $culture): ?>
                                            <label class="aiec-filter-item">
                                                <input type="checkbox" name="aiec_culture[]" value="<?php echo esc_attr($culture); ?>" <?php checked(in_array($culture, $saved_cultures, true)); ?> />
                                                <?php echo esc_html($culture); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="description"><?php esc_html_e('Select cultural/linguistic lenses to tailor tone, references, and holidays. (Optional)', 'ai-editorial-calendar'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php esc_html_e('Belief/Religious Context (optional)', 'ai-editorial-calendar'); ?></label>
                                </th>
                                <td>
                                    <?php
                                    $beliefs = [
                                        'Christian - Catholic','Christian - Protestant','Christian - Orthodox','Christian - Evangelical',
                                        'Judaism - Orthodox','Judaism - Conservative','Judaism - Reform',
                                        'Islam - Sunni','Islam - Shia','Islam - Sufi',
                                        'Hinduism','Buddhism - Theravada','Buddhism - Mahayana','Sikhism','Jainism',
                                        'Indigenous/Folk traditions','Spiritual but not religious','Secular/Nonreligious',
                                        'Chinese traditional','Shinto','Zoroastrianism','Baháʼí'
                                    ];
                                    $saved_beliefs = array_filter(array_map('trim', explode(',', get_option('aiec_belief', ''))));
                                    ?>
                                    <input type="search" class="aiec-filter-input" data-target="#aiec-belief-list" placeholder="<?php esc_attr_e('Search beliefs...', 'ai-editorial-calendar'); ?>" style="margin-bottom:6px; width: 260px;">
                                    <div id="aiec-belief-list" class="aiec-filter-list" data-max="5">
                                        <?php foreach ($beliefs as $belief): ?>
                                            <label class="aiec-filter-item">
                                                <input type="checkbox" name="aiec_belief[]" value="<?php echo esc_attr($belief); ?>" <?php checked(in_array($belief, $saved_beliefs, true)); ?> />
                                                <?php echo esc_html($belief); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="description"><?php esc_html_e('Optional: add religious or belief context for more relevant references and observances.', 'ai-editorial-calendar'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="aiec_focus_type"><?php esc_html_e('Content Focus', 'ai-editorial-calendar'); ?></label>
                                </th>
                                <td>
                                    <select name="aiec_focus_type" id="aiec_focus_type">
                                        <option value="mix" <?php selected(get_option('aiec_focus_type', 'mix'), 'mix'); ?>><?php esc_html_e('Balanced (mix of trends and evergreen)', 'ai-editorial-calendar'); ?></option>
                                        <option value="trends" <?php selected(get_option('aiec_focus_type', 'mix'), 'trends'); ?>><?php esc_html_e('Trends (timely/seasonal)', 'ai-editorial-calendar'); ?></option>
                                        <option value="evergreen" <?php selected(get_option('aiec_focus_type', 'mix'), 'evergreen'); ?>><?php esc_html_e('Evergreen (always relevant)', 'ai-editorial-calendar'); ?></option>
                                    </select>
                                    <p class="description"><?php esc_html_e('Choose whether suggestions should lean toward timely/trending topics, evergreen topics, or a balanced mix.', 'ai-editorial-calendar'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </details>
                </th>
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

<style>
.aiec-filter-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 8px;
    max-width: 700px;
    padding: 8px 0;
}
.aiec-filter-item {
    display: flex;
    align-items: center;
    gap: 6px;
}
.aiec-filter-input {
    max-width: 280px;
}
.aiec-advanced-options {
    display: block;
}
.aiec-advanced-options > summary {
    cursor: pointer;
    font-weight: 600;
    margin-bottom: 8px;
}
.aiec-advanced-table {
    margin-top: 8px;
}
</style>

<script>
(function() {
    const filterInputs = document.querySelectorAll('.aiec-filter-input');
    filterInputs.forEach((input) => {
        input.addEventListener('input', () => {
            const targetSelector = input.getAttribute('data-target');
            const list = document.querySelector(targetSelector);
            if (!list) return;
            const query = input.value.toLowerCase();
            list.querySelectorAll('.aiec-filter-item').forEach((item) => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });

    // Enforce max selections per list (default 5)
    document.querySelectorAll('.aiec-filter-list').forEach((list) => {
        const max = parseInt(list.getAttribute('data-max') || '5', 10);
        list.addEventListener('change', (event) => {
            const checked = list.querySelectorAll('input[type="checkbox"]:checked');
            if (checked.length > max) {
                const target = event.target;
                if (target && target.type === 'checkbox') {
                    target.checked = false;
                }
                alert(`Please select at most ${max} options in this list.`);
            }
        });
    });
})();
</script>
