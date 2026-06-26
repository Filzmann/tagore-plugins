<?php
/**
 * Notice-Rendering für die gemeinsame UI.
 */

defined('ABSPATH') || exit;

/**
 * Rendert Status- und Fehlermeldungen.
 */
trait Flz_Ui_Components_Notice_Rendering
{
    /**
     * Rendert eine Notice.
     *
     * @param array<string,mixed> $args Noticeargumente.
     */
    public function notice(string $message, string $type = 'info', array $args = array()): string
    {
        $classes = $this->classes(
            array('flz-ui-notice', 'flz-ui-notice--' . $this->safe_token($type)),
            isset($args['class']) ? (string) $args['class'] : ''
        );

        return '<div class="' . esc_attr($classes) . '" role="status">' . wp_kses_post($message) . '</div>';
    }
}
