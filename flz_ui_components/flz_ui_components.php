<?php
/**
 * Plugin Name: FLZ UI Components
 * Description: Gemeinsame UI-Komponenten, Formularfelder und Validierung für eigene Tagore-Plugins.
 * Version: 0.1.11
 * Author: Tagore-Gymnasium / Simon
 * Text Domain: flz-ui-components
 */

defined('ABSPATH') || exit;

define('FLZ_UI_COMPONENTS_VERSION', '0.1.11');
define('FLZ_UI_COMPONENTS_FILE', __FILE__);
define('FLZ_UI_COMPONENTS_DIR', plugin_dir_path(__FILE__));
define('FLZ_UI_COMPONENTS_URL', plugins_url('flz_ui_components/'));

require_once FLZ_UI_COMPONENTS_DIR . 'includes/class-flz-ui-components-validation-result.php';
require_once FLZ_UI_COMPONENTS_DIR . 'includes/class-flz-ui-components-validator.php';
require_once FLZ_UI_COMPONENTS_DIR . 'includes/class-flz-ui-components-renderer.php';
require_once FLZ_UI_COMPONENTS_DIR . 'includes/shortcode-blocks.php';
require_once FLZ_UI_COMPONENTS_DIR . 'includes/admin-demo.php';

add_action('wp_enqueue_scripts', 'flz_ui_components_enqueue_assets');
add_action('admin_enqueue_scripts', 'flz_ui_components_enqueue_assets');
add_action('admin_menu', 'flz_ui_components_register_admin_demo_page');
add_filter('block_categories_all', 'flz_ui_components_register_block_category');
add_filter('block_categories', 'flz_ui_components_register_block_category');

/**
 * Lädt die gemeinsamen UI-Assets.
 *
 * Das CSS ist bewusst komponentenbezogen und nutzt nur flz-ui-* Klassen, damit
 * es vorhandene WordPress- oder Theme-Styles möglichst wenig beeinflusst.
 */
function flz_ui_components_enqueue_assets(): void
{
    wp_enqueue_style(
        'flz-ui-components',
        FLZ_UI_COMPONENTS_URL . 'assets/css/flz-ui-components.css',
        array(),
        FLZ_UI_COMPONENTS_VERSION
    );

    wp_enqueue_script(
        'flz-ui-components',
        FLZ_UI_COMPONENTS_URL . 'assets/js/flz-ui-components.js',
        array(),
        FLZ_UI_COMPONENTS_VERSION,
        true
    );
}

/**
 * Gibt den zentralen Renderer für Templates zurück.
 */
function flz_ui_components(): Flz_Ui_Components_Renderer
{
    static $renderer = null;

    if (!$renderer instanceof Flz_Ui_Components_Renderer) {
        $renderer = new Flz_Ui_Components_Renderer();
    }

    return $renderer;
}

/**
 * Kurzer Alias für Templates.
 */
function flz_ui(): Flz_Ui_Components_Renderer
{
    return flz_ui_components();
}

/**
 * Validiert Eingaben anhand eines Schemas.
 *
 * @param array<string,mixed> $input  Eingabedaten, z. B. $_POST.
 * @param array<string,array> $schema Validierungsschema.
 */
function flz_ui_validate(array $input, array $schema): Flz_Ui_Components_Validation_Result
{
    return (new Flz_Ui_Components_Validator())->validate($input, $schema);
}

/**
 * Liest einen erlaubten Sortierschlüssel aus der Admin-URL.
 *
 * @param array<int,string> $allowed Erlaubte Sortierschlüssel.
 */
function flz_ui_admin_orderby(array $allowed, string $default): string
{
    $orderby = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : $default;

    return in_array($orderby, $allowed, true) ? $orderby : $default;
}

/**
 * Liest die Sortierrichtung aus der Admin-URL.
 */
function flz_ui_admin_order(): string
{
    $order = isset($_GET['order']) ? strtolower(sanitize_key(wp_unslash($_GET['order']))) : 'asc';

    return 'desc' === $order ? 'desc' : 'asc';
}

/**
 * Liest einen kurzen Textfilter aus der Admin-URL.
 */
function flz_ui_admin_filter_text(string $key): string
{
    return isset($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : '';
}

/**
 * Vergleicht zwei skalare Werte stabil für Admin-Tabellen.
 *
 * @param int|string $left  Erster Wert.
 * @param int|string $right Zweiter Wert.
 */
function flz_ui_admin_compare($left, $right, string $order): int
{
    if (is_numeric($left) && is_numeric($right)) {
        $result = (int) $left <=> (int) $right;
    } else {
        $result = strnatcasecmp((string) $left, (string) $right);
    }

    return 'desc' === $order ? -$result : $result;
}

/**
 * Formatiert ein Datum einheitlich deutsch als TT.MM.JJ.
 *
 * Akzeptiert Unix-Zeitstempel, ISO-Datumsstrings und MySQL-Datetime-Werte.
 *
 * @param int|string|null $value    Datum/Zeitwert.
 * @param string          $fallback Rückgabe bei leerem oder ungültigem Wert.
 */
function flz_ui_format_date($value, string $fallback = ''): string
{
    if (null === $value || '' === $value) {
        return $fallback;
    }

    if (is_numeric($value)) {
        return gmdate('d.m.y', (int) $value);
    }

    $timestamp = strtotime((string) $value);
    if (false === $timestamp) {
        return $fallback;
    }

    return gmdate('d.m.y', $timestamp);
}

/**
 * Formatiert Datum und Uhrzeit einheitlich deutsch als TT.MM.JJ HH:MM.
 *
 * @param int|string|null $value    Datum/Zeitwert.
 * @param string          $fallback Rückgabe bei leerem oder ungültigem Wert.
 */
function flz_ui_format_datetime($value, string $fallback = ''): string
{
    if (null === $value || '' === $value) {
        return $fallback;
    }

    $timestamp = is_numeric($value) ? (int) $value : strtotime((string) $value);
    if (false === $timestamp) {
        return $fallback;
    }

    return gmdate('d.m.y H:i', $timestamp);
}

/**
 * Formatiert Uhrzeiten einheitlich als HH:MM.
 *
 * Akzeptiert reine Uhrzeiten (`14:30`, `14:30:00`) und parsebare Zeitstrings.
 *
 * @param int|string|null $value    Uhrzeitwert.
 * @param string          $fallback Rückgabe bei leerem oder ungültigem Wert.
 */
function flz_ui_format_time($value, string $fallback = ''): string
{
    if (null === $value || '' === $value) {
        return $fallback;
    }

    $value = (string) $value;
    if (preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $value)) {
        return substr($value, 0, 5);
    }

    $timestamp = is_numeric($value) ? (int) $value : strtotime($value);
    if (false === $timestamp) {
        return $fallback;
    }

    return gmdate('H:i', $timestamp);
}
