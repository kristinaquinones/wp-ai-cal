<?php
/**
 * Stub-WordPress smoke test for the AI Editorial Calendar plugin.
 *
 * Not a substitute for real WordPress QA, but it catches load/wiring fatals
 * without a WP install: it stubs the WordPress functions used during bootstrap,
 * loads the plugin, and asserts that it boots, registers its hooks, that every
 * hook and sanitize callback resolves to a real method (the failure mode `php -l`
 * misses after refactors), the access policy and rate limit behave, and the pure
 * prompt helpers run.
 *
 * Run from anywhere:  php tests/smoke-test.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ABSPATH', sys_get_temp_dir() . '/');
define('HOUR_IN_SECONDS', 3600);

$GLOBALS['__hooks'] = [];
$GLOBALS['__settings'] = [];
$GLOBALS['__options'] = []; // absent options return their default (matches real WP)
$GLOBALS['__autoload_options'] = [];
$GLOBALS['__option_rewrites'] = 0;
$GLOBALS['__transients'] = [];

function plugin_dir_path($f) { return rtrim(dirname($f), '/') . '/'; }
function plugin_dir_url($f) { return 'http://example.test/wp-content/plugins/aiec/'; }
function plugin_basename($f) { return basename($f); }
function add_action($hook, $cb, $prio = 10, $args = 1) { $GLOBALS['__hooks'][] = ['action', $hook, $cb]; }
function add_filter($hook, $cb, $prio = 10, $args = 1) { $GLOBALS['__hooks'][] = ['filter', $hook, $cb]; }
function register_setting($group, $name, $args = []) { $GLOBALS['__settings'][$name] = $args; }
function __($t, $d = null) { return $t; }
function get_option($k, $d = false) { return $GLOBALS['__options'][$k] ?? $d; }
function current_user_can($c) { return true; }
function get_transient($k) { return $GLOBALS['__transients'][$k] ?? false; }
function set_transient($k, $v, $t) { $GLOBALS['__transients'][$k] = $v; }
function get_current_user_id() { return 1; }
function sanitize_text_field($s) { return is_string($s) ? trim($s) : $s; }
function sanitize_textarea_field($s) { return is_string($s) ? trim($s) : $s; }
function wp_load_alloptions() { return $GLOBALS['__autoload_options']; }
function delete_option($k) {
    unset($GLOBALS['__options'][$k], $GLOBALS['__autoload_options'][$k]);
    $GLOBALS['__option_rewrites']++;
    return true;
}
function add_option($k, $v, $deprecated = '', $autoload = 'yes') {
    $GLOBALS['__options'][$k] = $v;
    if ($autoload === 'yes') {
        $GLOBALS['__autoload_options'][$k] = $v;
    }
    return true;
}

$fail = 0;
function ok($cond, $msg) {
    global $fail;
    if ($cond) { echo "  PASS  $msg\n"; }
    else { echo "  FAIL  $msg\n"; $fail++; }
}

// 1. Load the plugin (runs the singleton bootstrap + constructor).
require dirname(__DIR__) . '/wp-ai-editorial-calendar.php';
echo "Loaded plugin without fatal.\n\n";

// 2. Classes exist.
ok(class_exists('AI_Editorial_Calendar'), 'AI_Editorial_Calendar defined');
ok(class_exists('AIEC_Settings'), 'AIEC_Settings defined');
ok(class_exists('AIEC_AI_Client'), 'AIEC_AI_Client defined');
ok(class_exists('AIEC_Prompt_Builder'), 'AIEC_Prompt_Builder defined');

// 3. Expected hooks registered.
$registered = array_map(fn($h) => $h[1], $GLOBALS['__hooks']);
foreach (['init', 'admin_menu', 'admin_init', 'admin_enqueue_scripts', 'wp_ajax_aiec_get_suggestions',
          'wp_ajax_aiec_generate_outline', 'wp_ajax_aiec_check_model_health', 'wp_dashboard_setup'] as $h) {
    ok(in_array($h, $registered, true), "hook registered: $h");
}
ok(count($GLOBALS['__hooks']) >= 20, 'registered >= 20 hooks (got ' . count($GLOBALS['__hooks']) . ')');

// 4. EVERY hook callback resolves to a real callable (catches moved/renamed methods).
$bad = [];
foreach ($GLOBALS['__hooks'] as $h) {
    $cb = $h[2];
    $callable = is_string($cb) ? function_exists($cb)
        : (is_array($cb) ? method_exists($cb[0], $cb[1]) : false);
    if (!$callable) { $bad[] = $h[1] . ' -> ' . (is_array($cb) ? (is_object($cb[0]) ? get_class($cb[0]) : $cb[0]) . '::' . $cb[1] : $cb); }
}
ok(empty($bad), 'all hook callbacks are callable' . (empty($bad) ? '' : ' (broken: ' . implode(', ', $bad) . ')'));

// 5. admin_init delegates to AIEC_Settings::register and it runs without fatal.
ok(is_callable(['AIEC_Settings', 'register']), 'AIEC_Settings::register callable');
AIEC_Settings::register();
ok(count($GLOBALS['__settings']) === 10, 'register() registered 10 settings (got ' . count($GLOBALS['__settings']) . ')');
$badcb = [];
foreach ($GLOBALS['__settings'] as $name => $args) {
    $cb = $args['sanitize_callback'] ?? null;
    if ($cb && !method_exists($cb[0], $cb[1])) { $badcb[] = $name; }
}
ok(empty($badcb), 'all sanitize callbacks resolve' . (empty($badcb) ? '' : ' (broken: ' . implode(',', $badcb) . ')'));
$GLOBALS['__options']['aiec_api_key'] = 'not-autoloaded';
AIEC_Settings::register();
ok($GLOBALS['__option_rewrites'] === 0, 'fallback leaves non-autoloaded API key untouched');
$GLOBALS['__autoload_options']['aiec_api_key'] = 'autoloaded';
AIEC_Settings::register();
ok($GLOBALS['__option_rewrites'] === 1 && !isset($GLOBALS['__autoload_options']['aiec_api_key']), 'fallback rewrites autoloaded API key once');
unset($GLOBALS['__options']['aiec_api_key']);

// 6. Delegation helpers on the main class work.
$inst = AI_Editorial_Calendar::get_instance();
ok($inst->get_api_key() === '', 'get_api_key() delegates (empty default)');
ok($inst->user_can_use_ai() === true, 'user_can_use_ai() delegates');

// 7. Access policy + rate limit.
ok(AIEC_Settings::user_can_use_ai() === true, 'Settings::user_can_use_ai true');
$allowed = 0;
for ($i = 0; $i < 35; $i++) { if (AIEC_Settings::check_ai_rate_limit()) { $allowed++; } }
ok($allowed === AIEC_Settings::AI_RATE_LIMIT_PER_HOUR, "rate limit caps at " . AIEC_Settings::AI_RATE_LIMIT_PER_HOUR . " (allowed $allowed)");

// 8. Prompt builder pure helpers run and return non-empty strings.
$sugg = AIEC_Prompt_Builder::build_suggestions('A cooking blog', 'casual', 'politics', ['Pasta 101', 'Bread basics'], '2026-06-15');
ok(is_string($sugg) && strlen($sugg) > 50, 'build_suggestions returns prompt');
ok(strpos($sugg, 'Title: X | Desc: Y') !== false, 'build_suggestions includes format directive');
$out = AIEC_Prompt_Builder::build_outline('My Title', 'A description', 'context', 'witty');
ok(is_string($out) && strpos($out, '## Introduction') !== false, 'build_outline returns guide');
$clean = AIEC_Prompt_Builder::clean_outline("Here is the outline:\n\n## Introduction\n**bold** text\n");
ok(strpos($clean, '##') !== false && strpos($clean, '**') === false, 'clean_outline strips markdown emphasis');

echo "\n" . ($fail === 0 ? "ALL SMOKE CHECKS PASSED" : "$fail CHECK(S) FAILED") . "\n";
exit($fail === 0 ? 0 : 1);
