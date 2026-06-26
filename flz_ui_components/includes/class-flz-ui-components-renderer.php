<?php
/**
 * Fassade für gemeinsame UI-Komponenten.
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/class-flz-ui-components-abstract-renderer.php';
require_once __DIR__ . '/trait-flz-ui-components-form-rendering.php';
require_once __DIR__ . '/trait-flz-ui-components-icon-rendering.php';
require_once __DIR__ . '/trait-flz-ui-components-button-rendering.php';
require_once __DIR__ . '/trait-flz-ui-components-field-rendering.php';
require_once __DIR__ . '/trait-flz-ui-components-editable-row-rendering.php';
require_once __DIR__ . '/trait-flz-ui-components-layout-rendering.php';
require_once __DIR__ . '/trait-flz-ui-components-notice-rendering.php';

/**
 * Öffentliche Template-API für Buttons, Formulare, Felder und Notices.
 *
 * Die Klasse bleibt bewusst schlank. Die Implementierung ist nach Clustern in
 * Traits aufgeteilt, damit Templates weiterhin `flz_ui()->button_save(...)`
 * nutzen können, während Wartung und Erweiterung übersichtlich bleiben.
 */
class Flz_Ui_Components_Renderer extends Flz_Ui_Components_Abstract_Renderer
{
    use Flz_Ui_Components_Form_Rendering;
    use Flz_Ui_Components_Icon_Rendering;
    use Flz_Ui_Components_Button_Rendering;
    use Flz_Ui_Components_Field_Rendering;
    use Flz_Ui_Components_Editable_Row_Rendering;
    use Flz_Ui_Components_Layout_Rendering;
    use Flz_Ui_Components_Notice_Rendering;
}
