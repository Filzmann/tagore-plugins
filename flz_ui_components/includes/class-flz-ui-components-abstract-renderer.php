<?php
/**
 * Gemeinsame interne Helfer für UI-Renderer-Cluster.
 */

defined('ABSPATH') || exit;

/**
 * Bündelt HTML-, Klassen- und Feld-Helfer für die spezialisierten Renderer-Traits.
 */
abstract class Flz_Ui_Components_Abstract_Renderer
{
    /**
     * Rendert Beschreibung und Fehlertexte.
     *
     * @param array<string,mixed> $args   Feldargumente.
     * @param array<int,string>   $errors Fehler.
     */
    protected function description_and_errors(string $id, array $args, array $errors): string
    {
        $html = '';

        if (!empty($args['description'])) {
            $html .= '<p class="flz-ui-help" id="' . esc_attr($id . '-description') . '">' . wp_kses_post((string) $args['description']) . '</p>';
        }

        if (!empty($errors)) {
            $html .= '<ul class="flz-ui-errors" id="' . esc_attr($id . '-errors') . '">';

            foreach ($errors as $message) {
                $html .= '<li>' . esc_html($message) . '</li>';
            }

            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * Verpackt ein Feld.
     *
     * @param array<int,string>   $errors Fehler.
     * @param array<string,mixed> $args   Feldargumente.
     */
    protected function wrap_field(string $type, string $field_html, array $errors, array $args): string
    {
        $classes = array(
            'flz-ui-field',
            'flz-ui-field--' . $this->safe_token($type),
        );

        if (!empty($errors)) {
            $classes[] = 'flz-ui-field--has-error';
        }

        $class = $this->classes($classes, isset($args['class']) ? (string) $args['class'] : '');

        return '<div class="' . esc_attr($class) . '">' . $field_html . '</div>';
    }

    /**
     * Kopiert erlaubte HTML-Feldattribute.
     *
     * @param array<string,mixed> $attrs Attribute, die ergänzt werden.
     * @param array<string,mixed> $args  Feldargumente.
     */
    protected function copy_field_attributes(array &$attrs, array $args): void
    {
        $allowed = array(
            'accept',
            'autocomplete',
            'max',
            'maxlength',
            'min',
            'minlength',
            'multiple',
            'pattern',
            'placeholder',
            'readonly',
            'rows',
            'step',
        );

        foreach ($allowed as $key) {
            if (array_key_exists($key, $args)) {
                $attrs[$key] = $args[$key];
            }
        }
    }

    /**
     * Ergänzt Pflichtfeld-, Fehler- und aria-describedby-Attribute.
     *
     * @param array<string,mixed> $attrs        Attribute.
     * @param array<string,mixed> $args         Feldargumente.
     * @param array<int,string>   $described_by IDs.
     * @param bool                $has_errors   Ob dieses konkrete Feld Fehler hat.
     */
    protected function apply_required_and_error_attributes(array &$attrs, array $args, array $described_by, bool $has_errors): void
    {
        if (!empty($args['required'])) {
            $attrs['required'] = true;
            $attrs['aria-required'] = 'true';
        }

        if ($has_errors) {
            $attrs['aria-invalid'] = 'true';
        }

        if (!empty($described_by)) {
            $attrs['aria-describedby'] = implode(' ', $described_by);
        }
    }

    /**
     * Ermittelt Fehler eines Feldes.
     *
     * @param array<string,mixed> $args Feldargumente.
     *
     * @return array<int,string>
     */
    protected function field_errors(array $args, string $name): array
    {
        if (empty($args['errors'])) {
            return array();
        }

        $errors = $args['errors'];

        if ($errors instanceof Flz_Ui_Components_Validation_Result) {
            return $errors->field_errors($name);
        }

        if (!is_array($errors)) {
            return array();
        }

        if (isset($errors[$name]) && is_array($errors[$name])) {
            return array_map('strval', $errors[$name]);
        }

        if (isset($errors[$name]) && is_scalar($errors[$name])) {
            return array((string) $errors[$name]);
        }

        return array();
    }

    /**
     * Baut aria-describedby IDs.
     *
     * @param array<string,mixed> $args   Feldargumente.
     * @param array<int,string>   $errors Fehler.
     *
     * @return array<int,string>
     */
    protected function described_by(string $id, array $args, array $errors): array
    {
        $ids = array();

        if (!empty($args['description'])) {
            $ids[] = $id . '-description';
        }

        if (!empty($errors)) {
            $ids[] = $id . '-errors';
        }

        return $ids;
    }

    /**
     * Rendert Pflichtfeld-Markierung.
     *
     * @param array<string,mixed> $args Feldargumente.
     */
    protected function required_marker(array $args): string
    {
        if (empty($args['required'])) {
            return '';
        }

        return ' <span class="flz-ui-required" aria-hidden="true">*</span>';
    }

    /**
     * Liest und validiert den Feldnamen.
     *
     * @param array<string,mixed> $args Feldargumente.
     */
    protected function required_name(array $args): string
    {
        $name = isset($args['name']) ? (string) $args['name'] : '';

        if ('' === trim($name)) {
            throw new InvalidArgumentException('flz_ui field benötigt einen nicht-leeren Namen.');
        }

        return $name;
    }

    /**
     * Erzeugt eine stabile Feld-ID.
     *
     * @param array<string,mixed> $args Feldargumente.
     */
    protected function field_id(array $args, string $name): string
    {
        if (!empty($args['id'])) {
            return $this->safe_token((string) $args['id']);
        }

        return 'flz-ui-' . $this->safe_token($name);
    }

    /**
     * Normalisiert CSS-Klassen.
     *
     * @param array<int,string> $base Basis-Klassen.
     */
    protected function classes(array $base, string $extra = ''): string
    {
        $classes = array_filter(array_merge($base, preg_split('/\s+/', $extra) ?: array()));

        return implode(' ', array_unique($classes));
    }

    /**
     * Normalisiert Tokens für Klassen und IDs.
     */
    protected function safe_token(string $value): string
    {
        $token = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $value));
        $token = trim($token, '-_');

        return '' === $token ? 'item' : $token;
    }

    /**
     * Rendert HTML-Attribute.
     *
     * @param array<string,mixed> $attrs Attribute.
     */
    protected function attributes(array $attrs): string
    {
        $html = '';

        foreach ($attrs as $name => $value) {
            $name = $this->safe_token((string) $name);

            if (false === $value || null === $value) {
                continue;
            }

            if ('' === $value && 'value' !== $name) {
                continue;
            }

            if (true === $value) {
                $html .= ' ' . esc_attr($name);
                continue;
            }

            if ('href' === $name || 'action' === $name) {
                $html .= ' ' . esc_attr($name) . '="' . esc_url((string) $value) . '"';
                continue;
            }

            $html .= ' ' . esc_attr($name) . '="' . esc_attr((string) $value) . '"';
        }

        return $html;
    }

    /**
     * Inline-Fallback für reine Icon-Buttons.
     *
     * Normalerweise erledigt .flz-ui-sr-only das Verstecken sichtbarer Labels.
     * Falls die CSS-Datei wegen Cache, Symlink-URL oder Admin-Kontext nicht lädt,
     * bleiben Icon-Buttons dadurch trotzdem visuell ruhig und barrierearm.
     */
    protected function visually_hidden_style(): string
    {
        return 'position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;';
    }
}
