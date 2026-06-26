<?php
/**
 * Zentrale Servervalidierung für gemeinsame UI-Formulare.
 */

defined('ABSPATH') || exit;

/**
 * Validiert und bereinigt Formularwerte anhand eines einfachen Schemas.
 */
class Flz_Ui_Components_Validator
{
    /**
     * Validiert Eingabedaten anhand eines Schemas.
     *
     * Beispiel:
     *
     * array(
     *     'email' => array(
     *         'type'     => 'email',
     *         'label'    => 'E-Mail',
     *         'required' => true,
     *     ),
     * )
     *
     * @param array<string,mixed> $input  Eingabedaten.
     * @param array<string,array> $schema Feldschema.
     */
    public function validate(array $input, array $schema): Flz_Ui_Components_Validation_Result
    {
        $values = array();
        $errors = array();

        foreach ($schema as $name => $rules) {
            $field_name = (string) $name;
            $field_rules = is_array($rules) ? $rules : array();
            $type = $this->normalize_type(isset($field_rules['type']) ? (string) $field_rules['type'] : 'text');
            $label = $this->label($field_name, $field_rules);
            $required = !empty($field_rules['required']);

            $raw_value = array_key_exists($field_name, $input) ? $this->unslash($input[$field_name]) : null;
            $value = $this->sanitize($raw_value, $type, $field_rules);
            $values[$field_name] = $value;

            if ($this->is_empty($value, $type)) {
                if ($required) {
                    $errors[$field_name][] = sprintf('„%s“ ist ein Pflichtfeld.', $label);
                }
                continue;
            }

            $type_error = $this->validate_type($value, $type, $label);
            if ('' !== $type_error) {
                $errors[$field_name][] = $type_error;
                continue;
            }

            $range_errors = $this->validate_ranges($value, $type, $label, $field_rules);
            foreach ($range_errors as $message) {
                $errors[$field_name][] = $message;
            }

            $choice_error = $this->validate_choices($value, $label, $field_rules);
            if ('' !== $choice_error) {
                $errors[$field_name][] = $choice_error;
            }

            $pattern_error = $this->validate_pattern($value, $label, $field_rules);
            if ('' !== $pattern_error) {
                $errors[$field_name][] = $pattern_error;
            }

            $callback_error = $this->validate_callback($value, $values, $label, $field_rules);
            if ('' !== $callback_error) {
                $errors[$field_name][] = $callback_error;
            }
        }

        return new Flz_Ui_Components_Validation_Result($values, $errors);
    }

    /**
     * Normalisiert bekannte HTML-Feldtypen.
     */
    private function normalize_type(string $type): string
    {
        $type = strtolower($type);

        if ('datetime' === $type) {
            return 'datetime-local';
        }

        if ('int' === $type) {
            return 'integer';
        }

        $allowed = array(
            'checkbox',
            'date',
            'datetime-local',
            'email',
            'hidden',
            'integer',
            'number',
            'password',
            'radio',
            'select',
            'tel',
            'text',
            'textarea',
            'time',
            'url',
        );

        return in_array($type, $allowed, true) ? $type : 'text';
    }

    /**
     * Gibt den Anzeigenamen eines Feldes zurück.
     *
     * @param array<string,mixed> $rules Feldregeln.
     */
    private function label(string $name, array $rules): string
    {
        if (!empty($rules['label']) && is_scalar($rules['label'])) {
            return (string) $rules['label'];
        }

        return ucfirst(str_replace(array('_', '-'), ' ', $name));
    }

    /**
     * Entfernt WordPress-Slashes, falls die Funktion verfügbar ist.
     *
     * @param mixed $value Eingabewert.
     *
     * @return mixed
     */
    private function unslash($value)
    {
        if (function_exists('wp_unslash')) {
            return wp_unslash($value);
        }

        if (is_array($value)) {
            return array_map(array($this, 'unslash'), $value);
        }

        return is_string($value) ? stripslashes($value) : $value;
    }

    /**
     * Bereinigt einen Wert passend zum Feldtyp.
     *
     * @param mixed               $value Eingabewert.
     * @param array<string,mixed> $rules Feldregeln.
     *
     * @return mixed
     */
    private function sanitize($value, string $type, array $rules)
    {
        if (isset($rules['sanitize']) && is_callable($rules['sanitize'])) {
            return call_user_func($rules['sanitize'], $value, $rules);
        }

        if ('checkbox' === $type) {
            return $this->is_truthy($value);
        }

        $scalar = $this->scalar_string($value);

        if ('password' === $type) {
            // Passwörter nicht per Text-Sanitizer verändern; nur in Stringform bringen.
            return $scalar;
        }

        if ('textarea' === $type) {
            return function_exists('sanitize_textarea_field') ? sanitize_textarea_field($scalar) : trim(wp_strip_all_tags($scalar));
        }

        if ('email' === $type) {
            return function_exists('sanitize_email') ? sanitize_email($scalar) : filter_var(trim($scalar), FILTER_SANITIZE_EMAIL);
        }

        if ('url' === $type) {
            return function_exists('esc_url_raw') ? esc_url_raw($scalar) : filter_var(trim($scalar), FILTER_SANITIZE_URL);
        }

        if ('number' === $type) {
            return str_replace(',', '.', trim($scalar));
        }

        if ('integer' === $type) {
            return trim($scalar);
        }

        return function_exists('sanitize_text_field') ? sanitize_text_field($scalar) : trim(wp_strip_all_tags($scalar));
    }

    /**
     * Wandelt skalare Werte robust in Strings um.
     *
     * @param mixed $value Eingabewert.
     */
    private function scalar_string($value): string
    {
        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

    /**
     * Prüft, ob ein Wert fachlich leer ist.
     *
     * @param mixed $value Wert.
     */
    private function is_empty($value, string $type): bool
    {
        if ('checkbox' === $type) {
            return true !== $value;
        }

        if (is_array($value)) {
            return empty($value);
        }

        return null === $value || '' === trim((string) $value);
    }

    /**
     * Prüft typische boolesche Formularwerte.
     *
     * @param mixed $value Wert.
     */
    private function is_truthy($value): bool
    {
        if (true === $value || 1 === $value) {
            return true;
        }

        if (!is_scalar($value)) {
            return false;
        }

        return in_array(strtolower((string) $value), array('1', 'true', 'yes', 'on'), true);
    }

    /**
     * Validiert das Format nach Feldtyp.
     *
     * @param mixed $value Wert.
     */
    private function validate_type($value, string $type, string $label): string
    {
        $string_value = (string) $value;

        if ('email' === $type && !$this->is_email($string_value)) {
            return sprintf('„%s“ muss eine gültige E-Mail-Adresse enthalten.', $label);
        }

        if ('date' === $type && !$this->is_date($string_value)) {
            return sprintf('„%s“ muss ein gültiges Datum im Format JJJJ-MM-TT enthalten.', $label);
        }

        if ('time' === $type && !$this->is_time($string_value)) {
            return sprintf('„%s“ muss eine gültige Uhrzeit im Format HH:MM enthalten.', $label);
        }

        if ('datetime-local' === $type && !$this->is_datetime_local($string_value)) {
            return sprintf('„%s“ muss ein gültiges Datum mit Uhrzeit enthalten.', $label);
        }

        if ('number' === $type && !is_numeric($string_value)) {
            return sprintf('„%s“ muss eine Zahl enthalten.', $label);
        }

        if ('integer' === $type && false === filter_var($string_value, FILTER_VALIDATE_INT)) {
            return sprintf('„%s“ muss eine ganze Zahl enthalten.', $label);
        }

        if ('url' === $type && false === filter_var($string_value, FILTER_VALIDATE_URL)) {
            return sprintf('„%s“ muss eine gültige URL enthalten.', $label);
        }

        if ('tel' === $type && 1 !== preg_match('/^[0-9+() .\/-]+$/', $string_value)) {
            return sprintf('„%s“ darf nur eine Telefonnummer enthalten.', $label);
        }

        return '';
    }

    /**
     * Validiert Längen- und Wertebereiche.
     *
     * @param mixed               $value Wert.
     * @param array<string,mixed> $rules Feldregeln.
     *
     * @return array<int,string>
     */
    private function validate_ranges($value, string $type, string $label, array $rules): array
    {
        $errors = array();
        $string_value = (string) $value;

        if (isset($rules['min_length']) && $this->string_length($string_value) < (int) $rules['min_length']) {
            $errors[] = sprintf('„%s“ muss mindestens %d Zeichen enthalten.', $label, (int) $rules['min_length']);
        }

        if (isset($rules['max_length']) && $this->string_length($string_value) > (int) $rules['max_length']) {
            $errors[] = sprintf('„%s“ darf höchstens %d Zeichen enthalten.', $label, (int) $rules['max_length']);
        }

        if (in_array($type, array('number', 'integer'), true)) {
            if (isset($rules['min']) && is_numeric($string_value) && (float) $string_value < (float) $rules['min']) {
                $errors[] = sprintf('„%s“ muss mindestens %s sein.', $label, (string) $rules['min']);
            }

            if (isset($rules['max']) && is_numeric($string_value) && (float) $string_value > (float) $rules['max']) {
                $errors[] = sprintf('„%s“ darf höchstens %s sein.', $label, (string) $rules['max']);
            }
        }

        if (in_array($type, array('date', 'time', 'datetime-local'), true)) {
            if (isset($rules['min']) && $string_value < (string) $rules['min']) {
                $errors[] = sprintf('„%s“ liegt vor dem erlaubten Mindestwert.', $label);
            }

            if (isset($rules['max']) && $string_value > (string) $rules['max']) {
                $errors[] = sprintf('„%s“ liegt nach dem erlaubten Höchstwert.', $label);
            }
        }

        return $errors;
    }

    /**
     * Validiert Auswahlwerte.
     *
     * @param mixed               $value Wert.
     * @param array<string,mixed> $rules Feldregeln.
     */
    private function validate_choices($value, string $label, array $rules): string
    {
        $choices = null;

        if (isset($rules['choices']) && is_array($rules['choices'])) {
            $choices = $rules['choices'];
        } elseif (isset($rules['options']) && is_array($rules['options'])) {
            $choices = $rules['options'];
        }

        if (null === $choices) {
            return '';
        }

        $allowed = $this->choice_values($choices);

        if (!in_array((string) $value, $allowed, true)) {
            return sprintf('„%s“ enthält keinen erlaubten Auswahlwert.', $label);
        }

        return '';
    }

    /**
     * Extrahiert erlaubte Werte aus einer Optionsliste.
     *
     * @param array<mixed> $choices Optionsliste.
     *
     * @return array<int,string>
     */
    private function choice_values(array $choices): array
    {
        $keys = array_keys($choices);
        $is_list = $keys === range(0, count($choices) - 1);
        $values = $is_list ? array_values($choices) : $keys;

        return array_map('strval', $values);
    }

    /**
     * Validiert ein optionales Regex-Muster.
     *
     * @param mixed               $value Wert.
     * @param array<string,mixed> $rules Feldregeln.
     */
    private function validate_pattern($value, string $label, array $rules): string
    {
        if (empty($rules['pattern']) || !is_string($rules['pattern'])) {
            return '';
        }

        $result = @preg_match($rules['pattern'], (string) $value);

        if (1 !== $result) {
            return sprintf('„%s“ hat nicht das erwartete Format.', $label);
        }

        return '';
    }

    /**
     * Führt eine projektspezifische Callback-Validierung aus.
     *
     * @param mixed               $value  Wert.
     * @param array<string,mixed> $values Bereits bereinigte Werte.
     * @param array<string,mixed> $rules  Feldregeln.
     */
    private function validate_callback($value, array $values, string $label, array $rules): string
    {
        if (empty($rules['validate']) || !is_callable($rules['validate'])) {
            return '';
        }

        $result = call_user_func($rules['validate'], $value, $values, $rules);

        if (true === $result || null === $result || '' === $result) {
            return '';
        }

        if (is_string($result)) {
            return $result;
        }

        return sprintf('„%s“ ist ungültig.', $label);
    }

    /**
     * Prüft E-Mail-Adressen mit WordPress-Funktion oder PHP-Fallback.
     */
    private function is_email(string $value): bool
    {
        if (function_exists('is_email')) {
            return (bool) is_email($value);
        }

        return false !== filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Ermittelt die Zeichenlänge mit mbstring-Fallback.
     */
    private function string_length(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($value);
        }

        return strlen($value);
    }

    /**
     * Prüft ISO-Datum JJJJ-MM-TT.
     */
    private function is_date(string $value): bool
    {
        if (1 !== preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
            return false;
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
    }

    /**
     * Prüft Uhrzeiten im Format HH:MM oder HH:MM:SS.
     */
    private function is_time(string $value): bool
    {
        if (1 !== preg_match('/^(\d{2}):(\d{2})(?::(\d{2}))?$/', $value, $matches)) {
            return false;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];
        $second = isset($matches[3]) ? (int) $matches[3] : 0;

        return $hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59 && $second >= 0 && $second <= 59;
    }

    /**
     * Prüft datetime-local im üblichen Browserformat.
     */
    private function is_datetime_local(string $value): bool
    {
        $parts = explode('T', $value);

        if (2 !== count($parts)) {
            return false;
        }

        return $this->is_date($parts[0]) && $this->is_time($parts[1]);
    }
}
