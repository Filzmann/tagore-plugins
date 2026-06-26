<?php
/**
 * Icon-Rendering für die gemeinsame UI.
 */

defined('ABSPATH') || exit;

/**
 * Rendert bekannte Inline-SVG-Icons mit funktionalem Alternativtext.
 */
trait Flz_Ui_Components_Icon_Rendering
{
    /**
     * Rendert ein Icon als inline SVG.
     *
     * SVGs haben kein alt-Attribut; der funktionale Alternativtext wird
     * deshalb über role="img", aria-label und title bereitgestellt.
     */
    protected function icon(string $name, string $alt_text): string
    {
        if ('' === trim($alt_text)) {
            throw new InvalidArgumentException('flz_ui Icons benötigen einen nicht-leeren Alternativtext.');
        }

        $path = $this->icon_path($name);

        return '<svg class="flz-ui-icon flz-ui-icon--' . esc_attr($this->safe_token($name)) . '" role="img" aria-label="' . esc_attr($alt_text) . '" width="18" height="18" viewBox="0 0 24 24" focusable="false" style="width:1.1em;height:1.1em;max-width:1.1em;max-height:1.1em;vertical-align:-0.15em;">'
            . '<title>' . esc_html($alt_text) . '</title>'
            . $path
            . '</svg>';
    }

    /**
     * Gibt den SVG-Pfad eines bekannten Icons zurück.
     */
    protected function icon_path(string $name): string
    {
        switch ($name) {
            case 'plus':
                return '<path d="M11 5h2v6h6v2h-6v6h-2v-6H5v-2h6z" />';
            case 'save':
                return '<path d="M5 3h12l2 2v16H5z" fill="none" stroke="currentColor" stroke-width="2" /><path d="M8 3v6h8V3M8 21v-7h8v7" fill="none" stroke="currentColor" stroke-width="2" />';
            case 'edit':
                return '<path d="M4 17.5V20h2.5L18.8 7.7l-2.5-2.5z" /><path d="M17.6 4l2.4 2.4" fill="none" stroke="currentColor" stroke-width="2" />';
            case 'delete':
                return '<path d="M6 7h12M10 7V5h4v2M8 9l1 11h6l1-11" fill="none" stroke="currentColor" stroke-width="2" />';
            case 'back':
                return '<path d="M11 6l-6 6 6 6v-4h8v-4h-8z" />';
            case 'calendar':
                return '<path d="M6 4h12v16H6zM6 8h12M9 2v4M15 2v4" fill="none" stroke="currentColor" stroke-width="2" />';
            case 'clock':
                return '<path d="M12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16zm0 4v5l3 2" fill="none" stroke="currentColor" stroke-width="2" />';
            case 'mail':
                return '<path d="M4 6h16v12H4z" fill="none" stroke="currentColor" stroke-width="2" /><path d="M4 7l8 6 8-6" fill="none" stroke="currentColor" stroke-width="2" />';
            case 'lock':
                return '<path d="M7 10h10v10H7zM9 10V8a3 3 0 0 1 6 0v2" fill="none" stroke="currentColor" stroke-width="2" />';
            case 'check':
                return '<path d="M5 12l4 4L19 6" fill="none" stroke="currentColor" stroke-width="2.5" />';
            case 'warning':
                return '<path d="M12 4l9 16H3z" fill="none" stroke="currentColor" stroke-width="2" /><path d="M12 9v5M12 17h.01" fill="none" stroke="currentColor" stroke-width="2.5" />';
            case 'filter':
                return '<path d="M4 6h16l-6 7v5l-4 2v-7z" fill="none" stroke="currentColor" stroke-width="2" />';
            case 'upload':
                return '<path d="M12 16V5M8 9l4-4 4 4M5 18h14" fill="none" stroke="currentColor" stroke-width="2" />';
            case 'export':
                return '<path d="M12 5v10M8 11l4 4 4-4M5 19h14" fill="none" stroke="currentColor" stroke-width="2" />';
            case 'reset':
                return '<path d="M7 7a7 7 0 1 1-1 8M7 7H4V4" fill="none" stroke="currentColor" stroke-width="2" />';
            case 'view':
                return '<path d="M3 12s3.5-6 9-6 9 6 9 6-3.5 6-9 6-9-6-9-6z" fill="none" stroke="currentColor" stroke-width="2" /><circle cx="12" cy="12" r="2.5" />';
            case 'image':
                return '<path d="M4 5h16v14H4z" fill="none" stroke="currentColor" stroke-width="2" /><path d="M7 16l4-4 3 3 2-2 3 3M8 9h.01" fill="none" stroke="currentColor" stroke-width="2" />';
            case 'close':
                return '<path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2.5" />';
            default:
                return '<circle cx="12" cy="12" r="7" fill="none" stroke="currentColor" stroke-width="2" />';
        }
    }
}
