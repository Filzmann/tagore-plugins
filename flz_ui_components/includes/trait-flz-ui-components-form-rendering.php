<?php
/**
 * Formular-Rendering für die gemeinsame UI.
 */

defined('ABSPATH') || exit;

/**
 * Rendert Formularhüllen, Nonces und Hidden Fields.
 */
trait Flz_Ui_Components_Form_Rendering
{
    /**
     * Öffnet ein Formular.
     *
     * Unterstützt bewusst Nonce und Hidden Fields, damit Templates nicht mehr
     * rohes Formular-Markup mit Sicherheitsfeldern mischen müssen.
     *
     * @param array<string,mixed> $args Formularargumente.
     */
    public function form_start(array $args = array()): string
    {
        $method = isset($args['method']) && 'get' === strtolower((string) $args['method']) ? 'get' : 'post';
        $attrs = isset($args['attrs']) && is_array($args['attrs']) ? $args['attrs'] : array();
        $attrs['method'] = $method;
        $attrs['action'] = isset($args['action']) ? (string) $args['action'] : '';
        $attrs['class'] = $this->classes(
            array('flz-ui-form'),
            isset($args['class']) ? (string) $args['class'] : ''
        );

        if (!empty($args['enctype'])) {
            $attrs['enctype'] = (string) $args['enctype'];
        }

        $html = '<form' . $this->attributes($attrs) . '>';

        if (!empty($args['nonce']) && function_exists('wp_nonce_field')) {
            $nonce_name = !empty($args['nonce_name']) ? (string) $args['nonce_name'] : '_wpnonce';
            $html .= wp_nonce_field((string) $args['nonce'], $nonce_name, true, false);
        }

        if (!empty($args['hidden']) && is_array($args['hidden'])) {
            $html .= $this->hidden_fields($args['hidden']);
        }

        if (!empty($args['hidden_fields']) && is_array($args['hidden_fields'])) {
            $html .= $this->hidden_fields($args['hidden_fields']);
        }

        return $html;
    }

    /**
     * Schließt ein Formular.
     */
    public function form_end(): string
    {
        return '</form>';
    }

    /**
     * Rendert ein einzelnes Hidden Field.
     *
     * @param int|float|string|bool|null $value Feldwert.
     * @param array<string,mixed>        $args  Optionale Attribute.
     */
    public function hidden(string $name, $value = '', array $args = array()): string
    {
        if ('' === trim($name)) {
            throw new InvalidArgumentException('flz_ui hidden benötigt einen nicht-leeren Namen.');
        }

        $attrs = isset($args['attrs']) && is_array($args['attrs']) ? $args['attrs'] : array();
        $attrs['type'] = 'hidden';
        $attrs['name'] = $name;
        $attrs['value'] = null === $value ? '' : (string) $value;

        if (!empty($args['id'])) {
            $attrs['id'] = (string) $args['id'];
        }

        return '<input' . $this->attributes($attrs) . ' />';
    }

    /**
     * Rendert mehrere Hidden Fields.
     *
     * Unterstützt sowohl einfache Maps (`name => value`) als auch detaillierte
     * Einträge (`array('name' => '...', 'value' => '...', 'attrs' => array())`).
     *
     * @param array<mixed,mixed> $fields Hidden-Field-Definitionen.
     */
    public function hidden_fields(array $fields): string
    {
        $html = '';

        foreach ($fields as $name => $field) {
            if (is_array($field) && isset($field['name'])) {
                $value = array_key_exists('value', $field) ? $field['value'] : '';
                $html .= $this->hidden((string) $field['name'], $value, $field);
                continue;
            }

            $html .= $this->hidden((string) $name, $field);
        }

        return $html;
    }
}
