<?php
/**
 * Minimaler Smoke-Test für flz_ui_components ohne WordPress-Bootstrap.
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../../');
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        if (is_array($value)) {
            return array_map('wp_unslash', $value);
        }

        return is_string($value) ? stripslashes($value) : $value;
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($value)
    {
        return strip_tags((string) $value);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($value)
    {
        return trim(strip_tags((string) $value));
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($value)
    {
        return trim(strip_tags((string) $value));
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($value)
    {
        return filter_var(trim((string) $value), FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($value)
    {
        return filter_var(trim((string) $value), FILTER_SANITIZE_URL);
    }
}

require_once __DIR__ . '/../includes/class-flz-ui-components-validation-result.php';
require_once __DIR__ . '/../includes/class-flz-ui-components-validator.php';

function flz_ui_components_assert($condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$schema = array(
    'name'       => array(
        'type'     => 'text',
        'label'    => 'Name',
        'required' => true,
    ),
    'email'      => array(
        'type'     => 'email',
        'label'    => 'E-Mail',
        'required' => true,
    ),
    'date'       => array(
        'type'     => 'date',
        'label'    => 'Datum',
        'required' => true,
    ),
    'time'       => array(
        'type'     => 'time',
        'label'    => 'Uhrzeit',
        'required' => true,
    ),
    'password'   => array(
        'type'       => 'password',
        'label'      => 'Passwort',
        'required'   => true,
        'min_length' => 8,
    ),
    'course'     => array(
        'type'    => 'select',
        'label'   => 'Kurs',
        'options' => array(
            'a' => 'A',
            'b' => 'B',
        ),
    ),
    'acceptance' => array(
        'type'     => 'checkbox',
        'label'    => 'Einwilligung',
        'required' => true,
    ),
);

$validator = new Flz_Ui_Components_Validator();

$valid = $validator->validate(
    array(
        'name'       => 'Ada Lovelace',
        'email'      => 'ada@example.org',
        'date'       => '2026-09-01',
        'time'       => '13:45',
        'password'   => 'sehr-geheim',
        'course'     => 'a',
        'acceptance' => 'on',
    ),
    $schema
);

flz_ui_components_assert($valid->is_valid(), 'Gültige Eingabe wurde abgelehnt: ' . $valid->first_error());
flz_ui_components_assert('ada@example.org' === $valid->value('email'), 'E-Mail wurde nicht korrekt übernommen.');

$invalid = $validator->validate(
    array(
        'name'       => '',
        'email'      => 'nicht-gueltig',
        'date'       => '2026-99-99',
        'time'       => '25:00',
        'password'   => 'kurz',
        'course'     => 'x',
        'acceptance' => '',
    ),
    $schema
);

flz_ui_components_assert(!$invalid->is_valid(), 'Ungültige Eingabe wurde akzeptiert.');
flz_ui_components_assert(count($invalid->errors()) >= 7, 'Nicht alle erwarteten Fehler wurden erkannt.');

echo 'OK: flz_ui_components validator smoke test' . PHP_EOL;
