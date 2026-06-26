<?php
/**
 * Button-Rendering für die gemeinsame UI.
 */

defined('ABSPATH') || exit;

/**
 * Rendert Buttons und zentrale Button-Presets.
 */
trait Flz_Ui_Components_Button_Rendering
{
    /**
     * Rendert einen Standardbutton oder Link im Button-Stil.
     *
     * @param array<string,mixed> $args Buttonargumente.
     */
    public function button(array $args = array()): string
    {
        $label = isset($args['label']) ? (string) $args['label'] : '';

        if ('' === trim($label)) {
            throw new InvalidArgumentException('flz_ui button benötigt ein nicht-leeres Label.');
        }

        $variant = isset($args['variant']) ? (string) $args['variant'] : 'secondary';
        $icon = isset($args['icon']) ? (string) $args['icon'] : '';
        $icon_alt = isset($args['icon_alt']) ? (string) $args['icon_alt'] : '';
        $hide_label = !empty($args['hide_label']);
        $title = isset($args['title']) ? (string) $args['title'] : '';
        $href = isset($args['href']) ? (string) $args['href'] : '';
        $classes = array(
            'flz-ui-button',
            'flz-ui-button--' . $this->safe_token($variant),
        );

        if ('' !== $icon) {
            $classes[] = 'flz-ui-button--has-icon';
        }

        if ($hide_label) {
            $classes[] = 'flz-ui-button--icon-only';
        }

        $class = $this->classes($classes, isset($args['class']) ? (string) $args['class'] : '');
        $attrs = isset($args['attrs']) && is_array($args['attrs']) ? $args['attrs'] : array();
        $attrs['class'] = $class;
        $this->apply_button_confirmation($attrs, $args, $label);

        if ($hide_label) {
            $accessible_label = '' !== trim($icon_alt) ? $icon_alt : $label;
            $attrs['aria-label'] = $accessible_label;
            $attrs['title'] = '' !== trim($title) ? $title : $accessible_label;
        } elseif ('' !== trim($title)) {
            $attrs['title'] = $title;
        }

        $content = '';

        if ('' !== $icon) {
            $content .= $this->icon($icon, $icon_alt);
        }

        $label_attrs = array(
            'class' => $hide_label ? 'flz-ui-button__label flz-ui-sr-only' : 'flz-ui-button__label',
        );

        if ($hide_label) {
            $label_attrs['style'] = $this->visually_hidden_style();
        }

        $content .= '<span' . $this->attributes($label_attrs) . '>' . esc_html($label) . '</span>';

        if ('' !== $href) {
            $attrs['href'] = $href;
            return '<a' . $this->attributes($attrs) . '>' . $content . '</a>';
        }

        $attrs['type'] = isset($args['type']) ? (string) $args['type'] : 'button';

        return '<button' . $this->attributes($attrs) . '>' . $content . '</button>';
    }

    /**
     * Rendert einen Icon-Button.
     *
     * Grafische Icons müssen immer einen funktionalen Alternativtext erhalten.
     * Der Button ist standardmäßig icon-only; der Titel erscheint als
     * Browser-Hinweis beim Hover.
     *
     * @param array<string,mixed> $args Buttonargumente.
     */
    public function icon_button(array $args = array()): string
    {
        if (empty($args['icon'])) {
            throw new InvalidArgumentException('flz_ui icon_button benötigt ein Icon.');
        }

        if (empty($args['icon_alt'])) {
            throw new InvalidArgumentException('flz_ui icon_button benötigt icon_alt als funktionalen Alternativtext.');
        }

        if (empty($args['label'])) {
            $args['label'] = (string) $args['icon_alt'];
        }

        $args['hide_label'] = empty($args['show_label']);

        return $this->button($args);
    }

    /**
     * Rendert einen Neu-Button mit Plus-Icon.
     *
     * @param array<string,mixed> $args Buttonargumente.
     */
    public function button_new(array $args = array()): string
    {
        return $this->button_from_preset('new', $args);
    }

    /**
     * Rendert einen Speichern-Button.
     *
     * @param array<string,mixed> $args Buttonargumente.
     */
    public function button_save(array $args = array()): string
    {
        return $this->button_from_preset('save', $args);
    }

    /**
     * Rendert einen Löschen-Button.
     *
     * @param array<string,mixed> $args Buttonargumente.
     */
    public function button_delete(array $args = array()): string
    {
        return $this->button_from_preset('delete', $args);
    }

    /**
     * Rendert einen Bearbeiten-Button.
     *
     * @param array<string,mixed> $args Buttonargumente.
     */
    public function button_edit(array $args = array()): string
    {
        return $this->button_from_preset('edit', $args);
    }

    /**
     * Rendert einen Filtern-Button.
     *
     * @param array<string,mixed> $args Buttonargumente.
     */
    public function button_filter(array $args = array()): string
    {
        return $this->button_from_preset('filter', $args);
    }

    /**
     * Rendert einen Upload-/Import-Button.
     *
     * @param array<string,mixed> $args Buttonargumente.
     */
    public function button_upload(array $args = array()): string
    {
        return $this->button_from_preset('upload', $args);
    }

    /**
     * Rendert einen Export-Button.
     *
     * @param array<string,mixed> $args Buttonargumente.
     */
    public function button_export(array $args = array()): string
    {
        return $this->button_from_preset('export', $args);
    }

    /**
     * Rendert einen Zurücksetzen-/Leeren-Button.
     *
     * @param array<string,mixed> $args Buttonargumente.
     */
    public function button_reset(array $args = array()): string
    {
        return $this->button_from_preset('reset', $args);
    }

    /**
     * Rendert einen neutralen Anzeigen-/Details-Button.
     *
     * @param array<string,mixed> $args Buttonargumente.
     */
    public function button_view(array $args = array()): string
    {
        return $this->button_from_preset('view', $args);
    }

    /**
     * Rendert einen Mediathek-/Bildauswahl-Button.
     *
     * @param array<string,mixed> $args Buttonargumente.
     */
    public function button_media(array $args = array()): string
    {
        return $this->button_from_preset('media', $args);
    }

    /**
     * Rendert einen Entfernen-/Leeren-Button.
     *
     * @param array<string,mixed> $args Buttonargumente.
     */
    public function button_clear(array $args = array()): string
    {
        return $this->button_from_preset('clear', $args);
    }

    /**
     * Rendert einen Button aus einem zentral gepflegten Preset.
     *
     * @param array<string,mixed> $args Buttonargumente.
     */
    private function button_from_preset(string $preset, array $args): string
    {
        $presets = $this->button_presets();
        if (!isset($presets[$preset])) {
            throw new InvalidArgumentException('Unbekanntes flz_ui Button-Preset: ' . esc_html($preset));
        }

        $defaults = $presets[$preset];
        if (isset($args['label']) && !isset($args['icon_alt'])) {
            $defaults['icon_alt'] = (string) $args['label'];
        }

        return $this->button(array_merge($defaults, $args));
    }

    /**
     * Zentrale Presets für wiederkehrende Aktionsbuttons.
     *
     * @return array<string,array<string,mixed>>
     */
    private function button_presets(): array
    {
        return array(
            'new'    => array(
                'hide_label' => true,
                'icon'       => 'plus',
                'icon_alt'   => 'Neu anlegen',
                'label'      => 'Neu',
                'variant'    => 'primary',
            ),
            'save'   => array(
                'hide_label' => true,
                'icon'       => 'save',
                'icon_alt'   => 'Speichern',
                'label'      => 'Speichern',
                'type'       => 'submit',
                'variant'    => 'primary',
            ),
            'delete' => array(
                'confirm'    => true,
                'hide_label' => true,
                'icon'       => 'delete',
                'icon_alt'   => 'Löschen',
                'label'      => 'Löschen',
                'type'       => 'submit',
                'variant'    => 'danger',
            ),
            'edit'   => array(
                'hide_label' => true,
                'icon'       => 'edit',
                'icon_alt'   => 'Bearbeiten',
                'label'      => 'Bearbeiten',
                'variant'    => 'secondary',
            ),
            'filter' => array(
                'hide_label' => true,
                'icon'       => 'filter',
                'icon_alt'   => 'Filtern',
                'label'      => 'Filtern',
                'type'       => 'submit',
                'variant'    => 'secondary',
            ),
            'upload' => array(
                'confirm'    => 'Datei wirklich hochladen/importieren? Bestehende Daten können gelöscht oder überschrieben werden.',
                'hide_label' => true,
                'icon'       => 'upload',
                'icon_alt'   => 'Hochladen',
                'label'      => 'Upload',
                'type'       => 'submit',
                'variant'    => 'warning',
            ),
            'export' => array(
                'hide_label' => true,
                'icon'       => 'export',
                'icon_alt'   => 'Exportieren',
                'label'      => 'Exportieren',
                'variant'    => 'secondary',
            ),
            'reset'  => array(
                'confirm'    => 'Diese Aktion setzt Daten zurück oder überschreibt bestehende Angaben. Wirklich fortfahren?',
                'hide_label' => true,
                'icon'       => 'reset',
                'icon_alt'   => 'Zurücksetzen',
                'label'      => 'Zurücksetzen',
                'type'       => 'submit',
                'variant'    => 'danger',
            ),
            'view'   => array(
                'hide_label' => true,
                'icon'       => 'view',
                'icon_alt'   => 'Anzeigen',
                'label'      => 'Anzeigen',
                'variant'    => 'secondary',
            ),
            'media'  => array(
                'hide_label' => true,
                'icon'       => 'image',
                'icon_alt'   => 'Bild auswählen',
                'label'      => 'Bild auswählen',
                'type'       => 'button',
                'variant'    => 'secondary',
            ),
            'clear'  => array(
                'confirm'    => true,
                'hide_label' => true,
                'icon'       => 'close',
                'icon_alt'   => 'Entfernen',
                'label'      => 'Entfernen',
                'type'       => 'button',
                'variant'    => 'danger',
            ),
        );
    }

    /**
     * Ergänzt eine Sicherheitsabfrage für riskante Button-Aktionen.
     *
     * @param array<string,mixed> $attrs Attribute des Buttons oder Links.
     * @param array<string,mixed> $args  Buttonargumente.
     */
    private function apply_button_confirmation(array &$attrs, array $args, string $label): void
    {
        if (!array_key_exists('confirm', $args) || false === $args['confirm']) {
            return;
        }

        $message = true === $args['confirm']
            ? '„' . $label . '“ wirklich ausführen? Diese Aktion kann nicht automatisch rückgängig gemacht werden.'
            : trim((string) $args['confirm']);

        if ('' === $message) {
            return;
        }

        $confirm = 'if (!window.confirm(' . $this->js_string($message) . ')) { return false; }';
        $attrs['onclick'] = isset($attrs['onclick']) && '' !== trim((string) $attrs['onclick'])
            ? $confirm . ' ' . (string) $attrs['onclick']
            : $confirm;
        $attrs['data-flz-ui-confirm'] = $message;
    }

    /**
     * Kodiert Text sicher als JavaScript-String für Inline-Eventhandler.
     */
    private function js_string(string $value): string
    {
        if (function_exists('wp_json_encode')) {
            return (string) wp_json_encode($value);
        }

        return (string) json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}
