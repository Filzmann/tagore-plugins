<?php
/**
 * Backend-Demoseite für die gemeinsamen UI-Komponenten.
 */

defined('ABSPATH') || exit;

/**
 * Registriert die Komponenten-Demo im Backend.
 */
function flz_ui_components_register_admin_demo_page(): void
{
    add_menu_page(
        'FLZ UI Components',
        'FLZ UI Components',
        'manage_options',
        'flz-ui-components',
        'flz_ui_components_render_admin_demo_page',
        'dashicons-layout',
        29
    );
}

/**
 * Rendert die Backend-Demoseite.
 */
function flz_ui_components_render_admin_demo_page(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Du hast keine Berechtigung, diese Seite aufzurufen.', 'flz-ui-components'));
    }

    $renderer = flz_ui();
    $schema = flz_ui_components_demo_schema();
    $result = null;
    $values = flz_ui_components_demo_defaults();
    $submitted = isset($_POST['flz_ui_components_demo_submit']);

    if ($submitted) {
        check_admin_referer('flz_ui_components_demo');

        $posted_values = wp_unslash($_POST); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Die zentrale Demo-Validierung bereinigt feldbezogen anhand des Schemas.
        $result = flz_ui_validate($posted_values, $schema);
        $values = array_merge($values, $result->values());
    }

    echo '<div class="wrap flz-ui-demo">';
    echo '<h1>' . esc_html__('FLZ UI Components', 'flz-ui-components') . '</h1>';
    echo '<p class="flz-ui-demo__intro">' . esc_html__('Gemeinsame, zentral änderbare UI-Bausteine für die eigenen Tagore-Plugins. Die Seite dient als lebende Referenz für Templates und Scripts.', 'flz-ui-components') . '</p>';

    if ($submitted && $result instanceof Flz_Ui_Components_Validation_Result) {
        if ($result->is_valid()) {
            echo $renderer->notice('Die Demo-Eingabe ist gültig. Bereinigte Werte stehen jetzt serverseitig bereit.', 'success'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Notice.
        } else {
            echo $renderer->notice($result->first_error(), 'error'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Notice.
        }
    }

    flz_ui_components_demo_render_buttons($renderer);
    flz_ui_components_demo_render_notices($renderer);
    flz_ui_components_demo_render_composed_components($renderer);
    flz_ui_components_demo_render_editable_rows($renderer);
    flz_ui_components_demo_render_form($renderer, $values, $result);
    flz_ui_components_demo_render_script_reference();

    echo '</div>';
}

/**
 * Gibt Beispielwerte für die Demo zurück.
 *
 * @return array<string,mixed>
 */
function flz_ui_components_demo_defaults(): array
{
    return array(
        'demo_text'     => 'AG Schach',
        'demo_email'    => 'info@tagore-gymnasium.de',
        'demo_password' => '',
        'demo_date'     => gmdate('Y-m-d'),
        'demo_time'     => '14:30',
        'demo_datetime' => gmdate('Y-m-d') . 'T14:30',
        'demo_number'   => '12',
        'demo_integer'  => '7',
        'demo_url'      => 'https://tagore-gymnasium.de/',
        'demo_tel'      => '+49 30 123456',
        'demo_textarea' => 'Kurzer Beschreibungstext für ein Formularfeld.',
        'demo_select'   => 'secondary',
        'demo_radio'    => 'weekly',
        'demo_checkbox' => '',
    );
}

/**
 * Gibt das Validierungsschema der Demo zurück.
 *
 * @return array<string,array>
 */
function flz_ui_components_demo_schema(): array
{
    return array(
        'demo_text'     => array(
            'type'       => 'text',
            'label'      => 'Textfeld',
            'required'   => true,
            'min_length' => 3,
            'max_length' => 80,
        ),
        'demo_email'    => array(
            'type'     => 'email',
            'label'    => 'E-Mail',
            'required' => true,
        ),
        'demo_password' => array(
            'type'       => 'password',
            'label'      => 'Passwort',
            'required'   => true,
            'min_length' => 8,
        ),
        'demo_date'     => array(
            'type'     => 'date',
            'label'    => 'Datum',
            'required' => true,
        ),
        'demo_time'     => array(
            'type'     => 'time',
            'label'    => 'Uhrzeit',
            'required' => true,
        ),
        'demo_datetime' => array(
            'type'     => 'datetime-local',
            'label'    => 'Datum mit Uhrzeit',
            'required' => true,
        ),
        'demo_number'   => array(
            'type'     => 'number',
            'label'    => 'Zahl',
            'required' => true,
            'min'      => 1,
            'max'      => 99,
        ),
        'demo_integer'  => array(
            'type'     => 'integer',
            'label'    => 'Ganze Zahl',
            'required' => true,
            'min'      => 1,
            'max'      => 13,
        ),
        'demo_url'      => array(
            'type'  => 'url',
            'label' => 'URL',
        ),
        'demo_tel'      => array(
            'type'  => 'tel',
            'label' => 'Telefon',
        ),
        'demo_textarea' => array(
            'type'       => 'textarea',
            'label'      => 'Mehrzeiliger Text',
            'max_length' => 500,
        ),
        'demo_select'   => array(
            'type'     => 'select',
            'label'    => 'Auswahl',
            'required' => true,
            'options'  => array(
                'primary'   => 'Primär',
                'secondary' => 'Sekundär',
                'danger'    => 'Warnend',
            ),
        ),
        'demo_radio'    => array(
            'type'     => 'radio',
            'label'    => 'Rhythmus',
            'required' => true,
            'options'  => array(
                'weekly' => 'Wöchentlich',
                'single' => 'Einzeltermin',
            ),
        ),
        'demo_checkbox' => array(
            'type'     => 'checkbox',
            'label'    => 'Einwilligung',
            'required' => true,
        ),
    );
}

/**
 * Rendert die Button-Demo.
 */
function flz_ui_components_demo_render_buttons(Flz_Ui_Components_Renderer $renderer): void
{
    echo '<section class="flz-ui-demo__section">';
    echo '<h2>' . esc_html__('Buttons und Icon-Buttons', 'flz-ui-components') . '</h2>';
    echo '<p>' . esc_html__('Icon-Buttons sind standardmäßig icon-only. Der funktionale Alt-Text beschreibt die Aktion; der Titel wird nur beim Hover sichtbar.', 'flz-ui-components') . '</p>';
    echo '<div class="flz-ui-demo__row">';
    echo $renderer->button_new(array('label' => 'Neu anlegen')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->button_save(array('label' => 'Speichern')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->button_delete(array('label' => 'Eintrag löschen', 'type' => 'button')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->button_edit(array('label' => 'Eintrag bearbeiten')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->button_filter(array('label' => 'Liste filtern')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->button_upload(array('label' => 'CSV importieren')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->button_export(array('label' => 'CSV exportieren')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->button_reset(array('label' => 'Liste zurücksetzen')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->button(array('label' => 'Sekundär', 'variant' => 'secondary')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->icon_button(array('icon' => 'check', 'icon_alt' => 'Aktion bestätigen', 'variant' => 'primary')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo '</div>';
    echo '</section>';
}

/**
 * Rendert die Notice-Demo.
 */
function flz_ui_components_demo_render_notices(Flz_Ui_Components_Renderer $renderer): void
{
    echo '<section class="flz-ui-demo__section">';
    echo '<h2>' . esc_html__('Notices', 'flz-ui-components') . '</h2>';
    echo $renderer->notice('Info-Hinweis: ruhig, sichtbar, ohne Alarmismus.', 'info'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Notice.
    echo $renderer->notice('Erfolg: Die Aktion wurde verarbeitet.', 'success'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Notice.
    echo $renderer->notice('Fehler: Diese Meldung kann direkt aus der Validierung kommen.', 'error'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Notice.
    echo '</section>';
}

/**
 * Rendert zusammengesetzte Layout-Komponenten.
 */
function flz_ui_components_demo_render_composed_components(Flz_Ui_Components_Renderer $renderer): void
{
    echo '<section class="flz-ui-demo__section">';
    echo '<h2>' . esc_html__('Verbundkomponenten', 'flz-ui-components') . '</h2>';
    echo '<p>' . esc_html__('CSV-Panel, Aktionsformular, Floating-Panel, Feld-Matrix und Karten setzen sich aus den bestehenden Feldern und Buttons zusammen.', 'flz-ui-components') . '</p>';

    echo '<div class="flz-ui-demo__row">';
    echo $renderer->action_form_button( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
        array(
            'preset' => 'reset',
            'label'  => 'Demo-Daten zurücksetzen',
            'method' => 'post',
            'nonce'  => 'flz_ui_components_demo',
            'hidden' => array('demo_action' => 'reset'),
        )
    );
    echo '</div>';

    echo $renderer->floating_action_panel( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Demo-Inhalt ist statisch und escaped.
        array(
            'id'           => 'flz-ui-demo-floating-panel',
            'title'        => 'Floating-Panel',
            'description'  => 'Demo für lange Frontend-Seiten: Der Button bleibt erreichbar und öffnet ein ruhiges Panel.',
            'button_label' => 'Demo-Panel öffnen',
            'content'      => '<p>' . esc_html__('Hier könnte ein Formular stehen. Auf Mobilgeräten wird daraus ein Bottom-Sheet.', 'flz-ui-components') . '</p>',
        )
    );

    echo $renderer->csv_panel( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
        array(
            'title'       => 'CSV-Import/-Export',
            'description' => 'Einheitlicher Block für Listen, bei denen CSV-Daten heruntergeladen oder importiert werden.',
            'format'      => 'Titel; Datum; Status',
            'export'      => array(
                'href'  => '#',
                'label' => 'Demo-CSV herunterladen',
            ),
            'upload'      => array(
                'file_name'    => 'demo-csv',
                'file_label'   => 'Demo-CSV-Datei',
                'button_label' => 'Demo-CSV importieren',
                'nonce'        => 'flz_ui_components_demo',
                'hidden'       => array('demo_action' => 'csv_upload'),
            ),
        )
    );

    echo '<h3>' . esc_html__('Field Matrix', 'flz-ui-components') . '</h3>';
    echo '<p>' . esc_html__('Die Matrix rendert eine erste Zeile und ergänzt per Plus-Button weitere leere Zeilen.', 'flz-ui-components') . '</p>';
    echo $renderer->field_matrix( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
        array(
            'id'        => 'flz-ui-demo-matrix',
            'rows'      => array(
                array('active' => true, 'weekday' => '3', 'start' => '14:30', 'room' => 'Aula'),
            ),
            'min_rows'  => 1,
            'add_label' => 'Weitere Zeile hinzufügen',
            'columns'   => array(
                array('label' => 'Aktiv', 'field' => array('type' => 'checkbox', 'name' => 'demo_matrix[{index}][active]', 'label' => 'aktiv', 'checked_key' => 'active')),
                array('label' => 'Wochentag', 'field' => array('type' => 'select', 'name' => 'demo_matrix[{index}][weekday]', 'value_key' => 'weekday', 'options' => array('1' => 'Montag', '2' => 'Dienstag', '3' => 'Mittwoch'), 'aria_label' => 'Wochentag')),
                array('label' => 'Beginn', 'field' => array('type' => 'time', 'name' => 'demo_matrix[{index}][start]', 'value_key' => 'start', 'aria_label' => 'Beginn')),
                array('label' => 'Raum', 'field' => array('type' => 'text', 'name' => 'demo_matrix[{index}][room]', 'value_key' => 'room', 'aria_label' => 'Raum')),
            ),
        )
    );

    echo '<h3>' . esc_html__('Cards', 'flz-ui-components') . '</h3>';
    echo '<p>' . esc_html__('Die Karten orientieren sich an den ruhigen News-Kacheln der Website: Bild, Titel, Kurztext, Metadaten und klare Aktionen.', 'flz-ui-components') . '</p>';
    echo '<div class="flz-ui-card-grid">';
    echo $renderer->card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Karte.
        array(
            'image_url' => esc_url(FLZ_UI_COMPONENTS_URL . 'assets/img/demo-card-news.svg'),
            'image_alt' => 'Abstrakte Vorschaugrafik für eine AG-Karte',
            'title'     => 'Basketball AG',
            'text'      => 'Spaß am Basketballspiel für Neulinge und Fortgeschrittene.',
            'meta'      => array(
                'Zeit'   => 'Do, 13:40–15:10',
                'Plätze' => '8 frei von 20',
            ),
            'actions'   => array(
                array('href' => '#', 'label' => 'Details anzeigen', 'variant' => 'secondary', 'icon' => 'view', 'icon_alt' => 'Details anzeigen'),
            ),
        )
    );
    echo $renderer->choice_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Karte.
        array(
            'name'      => 'demo_card_choice',
            'value'     => 'instrumental',
            'checked'   => true,
            'image_url' => esc_url(FLZ_UI_COMPONENTS_URL . 'assets/img/demo-card-choice.svg'),
            'image_alt' => 'Abstrakte Vorschaugrafik für eine auswählbare Karte',
            'title'     => 'Instrumental AG auswählen',
            'kicker'    => 'Auswahlkarte',
            'meta'      => array(
                'Zeit'   => 'Mi, 13:40–14:40',
                'Plätze' => '12 frei von 20',
            ),
        )
    );
    echo '</div>';
    echo '</section>';
}

/**
 * Rendert die Demo für bearbeitbare Tabellenzeilen.
 */
function flz_ui_components_demo_render_editable_rows(Flz_Ui_Components_Renderer $renderer): void
{
    $demo_new_row = $renderer->editable_row(
        array(
            'id'         => 'flz-ui-demo-editable-new',
            'new'        => true,
            'row_hidden' => true,
            'form'       => array(
                'method' => 'post',
                'nonce'  => 'flz_ui_components_demo',
                'hidden' => array('demo_row[id]' => ''),
            ),
            'cells'      => array(
                array(
                    'view'  => '',
                    'field' => array('type' => 'text', 'name' => 'demo_row[title]', 'label' => 'Titel', 'required' => true),
                ),
                array(
                    'view'  => '',
                    'field' => array('type' => 'date', 'name' => 'demo_row[date]', 'label' => 'Datum', 'required' => true),
                ),
                array(
                    'view'  => 'neu',
                    'field' => array('type' => 'select', 'name' => 'demo_row[status]', 'label' => 'Status', 'value' => 'draft', 'options' => array('draft' => 'Entwurf', 'active' => 'Aktiv')),
                ),
            ),
            'save'       => array('label' => 'Demo-Zeile speichern', 'type' => 'button'),
        )
    );
    $demo_existing_row = $renderer->editable_row(
        array(
            'id'    => 'flz-ui-demo-editable-1',
            'form'  => array(
                'method' => 'post',
                'nonce'  => 'flz_ui_components_demo',
                'hidden' => array('demo_row[id]' => '1'),
            ),
            'cells' => array(
                array(
                    'view'  => 'AG Schach',
                    'field' => array('type' => 'text', 'name' => 'demo_row[title]', 'label' => 'Titel', 'value' => 'AG Schach', 'required' => true),
                ),
                array(
                    'view'  => flz_ui_format_date(time()),
                    'field' => array('type' => 'date', 'name' => 'demo_row[date]', 'label' => 'Datum', 'value' => gmdate('Y-m-d'), 'required' => true),
                ),
                array(
                    'view'  => 'Aktiv',
                    'field' => array('type' => 'select', 'name' => 'demo_row[status]', 'label' => 'Status', 'value' => 'active', 'options' => array('draft' => 'Entwurf', 'active' => 'Aktiv')),
                ),
            ),
            'details' => array(
                'label'  => 'Zusatzdaten',
                'fields' => array(
                    array('type' => 'email', 'name' => 'demo_row[email]', 'label' => 'Kontakt', 'value' => 'info@tagore-gymnasium.de'),
                    array('type' => 'textarea', 'name' => 'demo_row[note]', 'label' => 'Notiz', 'value' => 'Detailfelder brauchen keine eigene Seitenleiste.'),
                ),
            ),
            'save'  => array('label' => 'Demo-Zeile speichern', 'type' => 'button'),
            'extra_actions' => $renderer->button_delete(array('label' => 'Demo-Zeile löschen', 'type' => 'button')),
        )
    );

    echo '<section class="flz-ui-demo__section">';
    echo '<h2>' . esc_html__('Editable Rows', 'flz-ui-components') . '</h2>';
    echo '<p>' . esc_html__('Der Edit-Button macht Tabellenzellen direkt bearbeitbar. Zusätzliche Detailfelder können in einer zweiten Zeile erscheinen; der Neu-Button blendet eine leere Einfügezeile ein.', 'flz-ui-components') . '</p>';
    echo '<p>';
    echo $renderer->button_new(array('label' => 'Neue Demo-Zeile anlegen', 'attrs' => array('data-flz-ui-show-new-row' => 'flz-ui-demo-editable-new'))); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo '</p>';
    echo '<table class="widefat striped">';
    echo '<thead><tr><th>' . esc_html__('Titel', 'flz-ui-components') . '</th><th>' . esc_html__('Datum', 'flz-ui-components') . '</th><th>' . esc_html__('Status', 'flz-ui-components') . '</th><th>' . esc_html__('Aktionen', 'flz-ui-components') . '</th></tr></thead>';
    echo '<tbody>';
    echo $demo_new_row; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Tabellenzeile.
    echo $demo_existing_row; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Tabellenzeile.
    echo '</tbody>';
    echo '</table>';
    echo '</section>';
}

/**
 * Rendert die Formular-Demo.
 *
 * @param array<string,mixed> $values Formularwerte.
 */
function flz_ui_components_demo_render_form(Flz_Ui_Components_Renderer $renderer, array $values, ?Flz_Ui_Components_Validation_Result $result): void
{
    $action_url = esc_url(menu_page_url('flz-ui-components', false));

    echo '<section class="flz-ui-demo__section">';
    echo '<h2>' . esc_html__('Formularfelder und Validierung', 'flz-ui-components') . '</h2>';
    echo '<p>' . esc_html__('Die Demo validiert serverseitig. Einfach ein Pflichtfeld leeren oder eine falsche E-Mail testen. Sichtbare Datumswerte werden im Projekt einheitlich als TT.MM.JJ ausgegeben.', 'flz-ui-components') . '</p>';
    echo '<p><strong>' . esc_html__('Heute formatiert:', 'flz-ui-components') . '</strong> ' . esc_html(flz_ui_format_date(time())) . '</p>';

    $form_start = $renderer->form_start(
        array(
            'action' => $action_url,
            'method' => 'post',
            'nonce'  => 'flz_ui_components_demo',
            'class'  => 'flz-ui-demo__form',
            'hidden' => array(
                'flz_ui_components_demo_submit' => '1',
            ),
        )
    );
    echo $form_start; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped das Formular.

    echo '<div class="flz-ui-demo__grid">';

    echo $renderer->input('text', array('name' => 'demo_text', 'label' => 'Text', 'value' => $values['demo_text'], 'required' => true, 'errors' => $result)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->input('email', array('name' => 'demo_email', 'label' => 'E-Mail', 'value' => $values['demo_email'], 'required' => true, 'autocomplete' => 'email', 'errors' => $result)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->input('password', array('name' => 'demo_password', 'label' => 'Passwort', 'value' => $values['demo_password'], 'required' => true, 'minlength' => 8, 'errors' => $result)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->input('date', array('name' => 'demo_date', 'label' => 'Datum', 'value' => $values['demo_date'], 'required' => true, 'errors' => $result)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->input('time', array('name' => 'demo_time', 'label' => 'Uhrzeit', 'value' => $values['demo_time'], 'required' => true, 'errors' => $result)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->input('datetime-local', array('name' => 'demo_datetime', 'label' => 'Datum mit Uhrzeit', 'value' => $values['demo_datetime'], 'required' => true, 'errors' => $result)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->input('number', array('name' => 'demo_number', 'label' => 'Zahl', 'value' => $values['demo_number'], 'required' => true, 'min' => 1, 'max' => 99, 'errors' => $result)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->input('integer', array('name' => 'demo_integer', 'label' => 'Ganze Zahl', 'value' => $values['demo_integer'], 'required' => true, 'min' => 1, 'max' => 13, 'errors' => $result)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->input('url', array('name' => 'demo_url', 'label' => 'URL', 'value' => $values['demo_url'], 'errors' => $result)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->input('tel', array('name' => 'demo_tel', 'label' => 'Telefon', 'value' => $values['demo_tel'], 'errors' => $result)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->field(array('type' => 'select', 'name' => 'demo_select', 'label' => 'Auswahl', 'value' => $values['demo_select'], 'required' => true, 'options' => array('primary' => 'Primär', 'secondary' => 'Sekundär', 'danger' => 'Warnend'), 'errors' => $result)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->field(array('type' => 'radio', 'name' => 'demo_radio', 'label' => 'Radio-Gruppe', 'value' => $values['demo_radio'], 'required' => true, 'options' => array('weekly' => 'Wöchentlich', 'single' => 'Einzeltermin'), 'errors' => $result)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.

    echo '</div>';

    echo $renderer->field(array('type' => 'textarea', 'name' => 'demo_textarea', 'label' => 'Textarea', 'value' => $values['demo_textarea'], 'description' => 'Für längere Hinweise oder Beschreibungstexte.', 'errors' => $result)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo $renderer->field(array('type' => 'checkbox', 'name' => 'demo_checkbox', 'label' => 'Ich bestätige die Demo-Einwilligung.', 'checked' => !empty($values['demo_checkbox']), 'required' => true, 'errors' => $result)); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo '<p class="flz-ui-demo__actions">';
    echo $renderer->button_save(array('label' => 'Demo validieren')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped die Komponente.
    echo '</p>';
    echo $renderer->form_end(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escaped das Formularende.
    echo '</section>';
}

/**
 * Rendert eine kurze JS-Referenz.
 */
function flz_ui_components_demo_render_script_reference(): void
{
    echo '<section class="flz-ui-demo__section">';
    echo '<h2>' . esc_html__('Script-API', 'flz-ui-components') . '</h2>';
    echo '<p>' . esc_html__('Auch in JavaScript müssen grafische Icons einen Alternativtext erhalten.', 'flz-ui-components') . '</p>';
    echo '<pre class="flz-ui-demo__code"><code>';
    echo esc_html(
        "const button = window.flzUi.createButton({\n"
        . "  icon: 'plus',\n"
        . "  altText: 'Neu anlegen',\n"
        . "  label: 'Neu',\n"
        . "  variant: 'primary'\n"
        . '});'
    );
    echo '</code></pre>';
    echo '</section>';
}
