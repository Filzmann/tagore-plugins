<?php
/**
 * Minimaler Smoke-Test für Renderer und Icon-Alternativtexte.
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/../../');
}

if (!function_exists('esc_attr')) {
    function esc_attr($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($value, $domain = 'default')
    {
        return esc_html($value);
    }
}

if (!function_exists('esc_textarea')) {
    function esc_textarea($value)
    {
        return htmlspecialchars((string) $value, ENT_NOQUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($value)
    {
        return (string) $value;
    }
}

if (!function_exists('selected')) {
    function selected($selected, $current, $echo = true)
    {
        $result = (string) $selected === (string) $current ? ' selected="selected"' : '';

        if ($echo) {
            echo esc_attr($result);
        }

        return $result;
    }
}

if (!function_exists('disabled')) {
    function disabled($disabled, $current = true, $echo = true)
    {
        $result = (bool) $disabled === (bool) $current ? ' disabled="disabled"' : '';

        if ($echo) {
            echo esc_attr($result);
        }

        return $result;
    }
}

require_once __DIR__ . '/../includes/class-flz-ui-components-validation-result.php';
require_once __DIR__ . '/../includes/class-flz-ui-components-renderer.php';

function flz_ui_components_renderer_assert($condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$renderer = new Flz_Ui_Components_Renderer();
$thrown = false;

try {
    $renderer->icon_button(
        array(
            'icon'  => 'plus',
            'label' => 'Neu',
        )
    );
} catch (InvalidArgumentException $exception) {
    $thrown = true;
}

flz_ui_components_renderer_assert($thrown, 'Icon-Button ohne icon_alt wurde nicht abgelehnt.');

$button = $renderer->button_new();
flz_ui_components_renderer_assert(false !== strpos($button, 'aria-label="Neu anlegen"'), 'Plus-Button enthält keinen funktionalen Icon-Alternativtext.');
flz_ui_components_renderer_assert(false !== strpos($button, 'title="Neu anlegen"'), 'Icon-only Button enthält keinen Hover-Titel.');

$delete_button = $renderer->button_delete(array('label' => 'Eintrag löschen'));
flz_ui_components_renderer_assert(false !== strpos($delete_button, 'flz-ui-button--danger'), 'Löschen-Button nutzt nicht die Danger-Variante.');
flz_ui_components_renderer_assert(false !== strpos($delete_button, 'aria-label="Eintrag löschen"'), 'Löschen-Button nutzt keinen funktionalen Alt-Text.');
flz_ui_components_renderer_assert(false !== strpos($delete_button, 'type="submit"'), 'Löschen-Button sendet Formulare nicht standardmäßig ab.');
flz_ui_components_renderer_assert(false !== strpos($delete_button, 'window.confirm'), 'Löschen-Button hat keine Sicherheitsabfrage.');

$upload_button = $renderer->button_upload(array('label' => 'CSV importieren'));
flz_ui_components_renderer_assert(false !== strpos($upload_button, 'flz-ui-button--warning'), 'Upload-/Import-Button nutzt nicht die Warning-Variante.');
flz_ui_components_renderer_assert(false !== strpos($upload_button, 'überschrieben'), 'Upload-/Import-Button warnt nicht vor Überschreiben.');

$filter_button = $renderer->button_filter(array('label' => 'Liste filtern'));
flz_ui_components_renderer_assert(false !== strpos($filter_button, 'aria-label="Liste filtern"'), 'Filtern-Button nutzt keinen funktionalen Alt-Text.');

$form = $renderer->form_start(array('method' => 'get', 'hidden' => array('page' => 'flz-ui-components')));
flz_ui_components_renderer_assert(false !== strpos($form, '<form'), 'Formularstart wurde nicht gerendert.');
flz_ui_components_renderer_assert(false !== strpos($form, 'method="get"'), 'Formularmethode wurde nicht übernommen.');
flz_ui_components_renderer_assert(false !== strpos($form, 'name="page"'), 'Hidden Field im Formularstart fehlt.');
flz_ui_components_renderer_assert(false !== strpos($form, 'value="flz-ui-components"'), 'Hidden-Field-Wert wurde nicht gerendert.');

$hidden = $renderer->hidden('selected', 17, array('id' => 'selected'));
flz_ui_components_renderer_assert(false !== strpos($hidden, 'type="hidden"'), 'Hidden Helper rendert kein Hidden Input.');
flz_ui_components_renderer_assert(false !== strpos($hidden, 'name="selected"'), 'Hidden Helper übernimmt den Feldnamen nicht.');

$icon_button = $renderer->icon_button(
    array(
        'icon'     => 'edit',
        'icon_alt' => 'Eintrag bearbeiten',
    )
);
flz_ui_components_renderer_assert(false !== strpos($icon_button, 'flz-ui-sr-only'), 'Icon-Button blendet den Text nicht visuell aus.');
flz_ui_components_renderer_assert(false !== strpos($icon_button, 'aria-label="Eintrag bearbeiten"'), 'Icon-Button nutzt den funktionalen Alt-Text nicht als Accessible Name.');

$radio = $renderer->radio_input(array('name' => 'slot_id', 'value' => 3, 'checked' => true, 'required' => true));
flz_ui_components_renderer_assert(false !== strpos($radio, 'type="radio"'), 'Radio-Input Helper rendert kein Radio Input.');
flz_ui_components_renderer_assert(false !== strpos($radio, 'checked'), 'Radio-Input Helper übernimmt checked nicht.');

$checkbox = $renderer->checkbox_input(array('name' => 'active', 'checked' => true));
flz_ui_components_renderer_assert(false !== strpos($checkbox, 'type="checkbox"'), 'Checkbox-Input Helper rendert kein Checkbox Input.');
flz_ui_components_renderer_assert(false !== strpos($checkbox, 'checked'), 'Checkbox-Input Helper übernimmt checked nicht.');

$field = $renderer->input(
    'email',
    array(
        'name'     => 'email',
        'label'    => 'E-Mail',
        'required' => true,
    )
);

flz_ui_components_renderer_assert(false !== strpos($field, 'type="email"'), 'E-Mail-Feld wurde nicht gerendert.');
flz_ui_components_renderer_assert(false !== strpos($field, 'required'), 'Pflichtfeld-Attribut fehlt.');

$editable_row = $renderer->editable_row(
    array(
        'id'    => 'demo-row-1',
        'form'  => array(
            'hidden' => array('record_id' => 1),
        ),
        'cells' => array(
            array(
                'view'  => 'AG Schach',
                'field' => array(
                    'type'     => 'text',
                    'name'     => 'title',
                    'label'    => 'Titel',
                    'value'    => 'AG Schach',
                    'required' => true,
                ),
            ),
        ),
        'details' => array(
            'fields' => array(
                array(
                    'type'  => 'email',
                    'name'  => 'contact',
                    'label' => 'Kontakt',
                    'value' => 'info@tagore-gymnasium.de',
                ),
            ),
        ),
        'save'  => array(
            'label' => 'Datensatz speichern',
            'attrs' => array('name' => 'save_record'),
        ),
        'extra_actions' => $renderer->button_delete(array('label' => 'Datensatz löschen', 'type' => 'button')),
    )
);

flz_ui_components_renderer_assert(false !== strpos($editable_row, 'data-flz-ui-editable-row'), 'Editable Row enthält kein Zeilen-Datenattribut.');
flz_ui_components_renderer_assert(false !== strpos($editable_row, 'data-flz-ui-edit-row'), 'Editable Row enthält keinen Edit-Trigger.');
flz_ui_components_renderer_assert(false !== strpos($editable_row, 'data-flz-ui-cancel-edit-row'), 'Editable Row enthält keinen Abbrechen-Trigger.');
flz_ui_components_renderer_assert(false !== strpos($editable_row, 'form="demo-row-1-form"'), 'Editable Row bindet Felder nicht an das Zeilenformular.');
flz_ui_components_renderer_assert(false !== strpos($editable_row, 'data-flz-ui-editable-details-for="demo-row-1"'), 'Editable Row rendert keine Detailzeile.');

$new_row = $renderer->editable_row(
    array(
        'id'         => 'demo-row-new',
        'new'        => true,
        'row_hidden' => true,
        'cells'      => array(
            array(
                'view'  => '',
                'field' => array('type' => 'text', 'name' => 'title', 'label' => 'Titel'),
            ),
        ),
        'save'       => array('label' => 'Datensatz speichern'),
    )
);

flz_ui_components_renderer_assert(false !== strpos($new_row, 'data-flz-ui-new-row'), 'Neue Editable Row ist nicht als Einfügezeile markiert.');
flz_ui_components_renderer_assert(false !== strpos($new_row, 'hidden'), 'Neue Editable Row ist nicht initial versteckt.');
flz_ui_components_renderer_assert(false !== strpos($new_row, 'is-editing'), 'Neue Editable Row startet nicht im Bearbeitungsmodus.');

$action_form = $renderer->action_form_button(
    array(
        'preset' => 'delete',
        'label'  => 'Datensatz löschen',
        'hidden' => array('record_id' => 7),
    )
);
flz_ui_components_renderer_assert(false !== strpos($action_form, '<form'), 'Action Form rendert kein Formular.');
flz_ui_components_renderer_assert(false !== strpos($action_form, 'name="record_id"'), 'Action Form übernimmt Hidden Fields nicht.');
flz_ui_components_renderer_assert(false !== strpos($action_form, 'window.confirm'), 'Action Form übernimmt riskantes Button-Preset nicht.');

$csv_panel = $renderer->csv_panel(
    array(
        'title'  => 'CSV-Demo',
        'format' => 'Titel; Datum',
        'export' => array('href' => '#', 'label' => 'CSV herunterladen'),
        'upload' => array('file_name' => 'demo_csv', 'button_label' => 'CSV importieren'),
    )
);
flz_ui_components_renderer_assert(false !== strpos($csv_panel, 'flz-ui-csv-panel'), 'CSV Panel rendert keine Panel-Klasse.');
flz_ui_components_renderer_assert(false !== strpos($csv_panel, 'type="file"'), 'CSV Panel rendert kein Dateifeld.');
flz_ui_components_renderer_assert(false !== strpos($csv_panel, 'multipart/form-data'), 'CSV Panel setzt kein Multipart-Formular.');

$field_matrix = $renderer->field_matrix(
    array(
        'id'      => 'demo-matrix',
        'rows'    => array(array('active' => true, 'start' => '14:30')),
        'columns' => array(
            array('label' => 'Aktiv', 'field' => array('type' => 'checkbox', 'name' => 'rows[{index}][active]', 'label' => 'aktiv', 'checked_key' => 'active')),
            array('label' => 'Beginn', 'field' => array('type' => 'time', 'name' => 'rows[{index}][start]', 'value_key' => 'start', 'aria_label' => 'Beginn')),
        ),
    )
);
flz_ui_components_renderer_assert(false !== strpos($field_matrix, 'data-flz-ui-field-matrix="demo-matrix"'), 'Field Matrix enthält kein Matrix-Datenattribut.');
flz_ui_components_renderer_assert(false !== strpos($field_matrix, 'data-flz-ui-field-matrix-template'), 'Field Matrix enthält keine Template-Zeile.');
flz_ui_components_renderer_assert(false !== strpos($field_matrix, 'data-flz-ui-add-matrix-row="demo-matrix"'), 'Field Matrix enthält keinen Plus-Trigger.');

$card = $renderer->card(
    array(
        'image_url' => 'https://example.test/demo.svg',
        'image_alt' => 'Demo-Karte',
        'title'     => 'AG Schach',
        'text'      => 'Kurzbeschreibung',
        'meta'      => array('Zeit' => '14:30'),
        'actions'   => array(array('href' => '#', 'label' => 'Details anzeigen')),
    )
);
flz_ui_components_renderer_assert(false !== strpos($card, 'flz-ui-card'), 'Card rendert keine Kartenklasse.');
flz_ui_components_renderer_assert(false !== strpos($card, 'alt="Demo-Karte"'), 'Card übernimmt keinen Bild-Alternativtext.');
flz_ui_components_renderer_assert(false !== strpos($card, '<dt>Zeit</dt><dd>14:30</dd>'), 'Card rendert Metadaten nicht.');

$choice_card = $renderer->choice_card(
    array(
        'name'     => 'slot_id',
        'value'    => 4,
        'checked'  => true,
        'title'    => 'Slot wählen',
        'disabled' => true,
    )
);
flz_ui_components_renderer_assert(false !== strpos($choice_card, 'flz-ui-choice-card'), 'Choice Card rendert keine Auswahlkartenklasse.');
flz_ui_components_renderer_assert(false !== strpos($choice_card, 'type="radio"'), 'Choice Card rendert kein Radio Input.');
flz_ui_components_renderer_assert(false !== strpos($choice_card, 'data-disabled="true"'), 'Choice Card markiert deaktivierte Auswahl nicht.');

echo 'OK: flz_ui_components renderer smoke test' . PHP_EOL;
