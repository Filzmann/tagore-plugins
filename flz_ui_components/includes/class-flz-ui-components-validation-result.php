<?php
/**
 * Ergebnisobjekt für die zentrale Formularvalidierung.
 */

defined('ABSPATH') || exit;

/**
 * Transportiert bereinigte Werte und aussagekräftige Feldfehler.
 */
class Flz_Ui_Components_Validation_Result
{
    /**
     * @var array<string,mixed>
     */
    private $values;

    /**
     * @var array<string,array<int,string>>
     */
    private $errors;

    /**
     * @param array<string,mixed>             $values Bereinigte Werte.
     * @param array<string,array<int,string>> $errors Fehler pro Feld.
     */
    public function __construct(array $values, array $errors)
    {
        $this->values = $values;
        $this->errors = $errors;
    }

    /**
     * Prüft, ob alle Felder gültig sind.
     */
    public function is_valid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Gibt alle bereinigten Werte zurück.
     *
     * @return array<string,mixed>
     */
    public function values(): array
    {
        return $this->values;
    }

    /**
     * Gibt einen bereinigten Wert zurück.
     *
     * @param mixed $default Rückgabewert, falls das Feld nicht existiert.
     *
     * @return mixed
     */
    public function value(string $name, $default = null)
    {
        return array_key_exists($name, $this->values) ? $this->values[$name] : $default;
    }

    /**
     * Gibt alle Fehler zurück.
     *
     * @return array<string,array<int,string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Gibt die Fehler eines Feldes zurück.
     *
     * @return array<int,string>
     */
    public function field_errors(string $name): array
    {
        return isset($this->errors[$name]) ? $this->errors[$name] : array();
    }

    /**
     * Gibt die erste Fehlermeldung zurück, optional für ein bestimmtes Feld.
     */
    public function first_error(string $name = ''): string
    {
        if ('' !== $name) {
            return isset($this->errors[$name][0]) ? $this->errors[$name][0] : '';
        }

        foreach ($this->errors as $messages) {
            if (isset($messages[0])) {
                return $messages[0];
            }
        }

        return '';
    }
}
