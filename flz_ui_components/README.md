# FLZ UI Components 0.1.10

`flz_ui_components` stellt gemeinsame UI-Bausteine für die eigenen Tagore-Plugins bereit. Das Plugin ist bewusst klein gehalten: normale PHP-Templates bleiben normale PHP-Templates, bekommen aber zentrale Renderer, einheitliche Klassen, gemeinsame Formularvalidierung und wiederverwendbare Assets.

## Installation lokal

Das Plugin liegt im Plugin-Repository:

```bash
~/projects/tagore-plugins/flz_ui_components
```

In der lokalen WordPress-Instanz wird es per Symlink verfügbar gemacht. Im WordPress-Plugin-Verzeichnis sollen keine Plugin-Dateien direkt bearbeitet werden.

Beispiel:

```bash
ln -s ~/projects/tagore-plugins/flz_ui_components ~/projects/tagore-local/public/wp-content/plugins/flz_ui_components
```

Falls die lokale WordPress-Struktur abweicht, den Zielpfad entsprechend anpassen.

## Backend-Demo

Im WordPress-Backend gibt es nach Aktivierung eine lebende Komponentenübersicht unter:

`FLZ UI Components`

Die Seite zeigt Buttons, Icon-Buttons, Notices, CSV-Panel, Field Matrix, Cards, Editable Rows, Formularfelder und die serverseitige Validierung mit Beispielwerten.

## Gutenberg-Blocks für Shortcodes

Frontend-Plugins können vorhandene Shortcodes als dynamische Gutenberg-Blocks
registrieren. Das Rendering läuft weiter über den Shortcode, der Editor bekommt
aber einen auffindbaren Block mit optionalen Inspector-Feldern:

```php
flz_ui_register_shortcode_block(
    array(
        'name'        => 'flz/beispiel',
        'shortcode'   => 'flz_beispiel',
        'title'       => 'FLZ Beispiel',
        'description' => 'Frontend-Ausgabe des Beispielplugins.',
        'attributes'  => array(
            'danke' => array(
                'type'    => 'string',
                'default' => '',
            ),
        ),
        'fields'      => array(
            'danke' => array(
                'label'       => 'Danke-Seite-ID',
                'description' => 'Optionaler Redirect nach erfolgreicher Aktion.',
            ),
        ),
    )
);
```

Die Blocks erscheinen in der Kategorie `Tagore / FLZ`. Bestehende Shortcodes
bleiben dadurch kompatibel, neue Seiten können aber ohne manuelle
Shortcode-Syntax gepflegt werden.

Der Editor-Placeholder nutzt `useBlockProps()`, damit dynamische
Shortcode-Blöcke im Gutenberg-Editor zuverlässig auswählbar, bearbeitbar und
entfernbar bleiben.

## Admin-Tabellen

Für sortierbare Tabellenköpfe steht ein gemeinsamer Link-Renderer bereit:

```php
echo flz_ui()->admin_table_sort_link(
    array(
        'label'         => 'Name',
        'sort'          => 'name',
        'current_sort'  => $orderby,
        'current_order' => $order,
        'url_args'      => array(
            'page' => 'flz_beispiel',
        ),
    )
);
```

Die Fachplugins bleiben für Sanitizing, erlaubte Sortierschlüssel und die
eigentliche Datenfilterung zuständig. Das UI-Plugin kümmert sich nur um
konsistentes Markup, Umschaltlogik und Escaping.

Filterformulare in Tabellenköpfen nutzen die Klasse
`.flz-ui-table-filter-form`. Das gemeinsame UI-Script sendet solche GET-Filter
automatisch ab: Texteingaben nach kurzer Entprellung, Selects sofort beim
Wechsel. Ein sichtbarer Filterbutton darf als Fallback für deaktiviertes
JavaScript stehen bleiben.

## Floating Action Panel

Für lange Frontend-Seiten mit einer wichtigen Aktion gibt es
`floating_action_panel()`. Die Komponente rendert einen Sticky-Button und ein
seitliches Panel; auf schmalen Displays wird daraus ein Bottom-Sheet. Ohne
JavaScript bleibt das Panel über den Anker-Link erreichbar.

```php
echo flz_ui()->floating_action_panel(
    array(
        'id'           => 'flz-demo-panel',
        'title'        => 'Anmeldung',
        'button_label' => 'Zur Anmeldung',
        'content'      => $already_escaped_form_html,
        'open'         => $has_form_messages,
    )
);
```

`content` wird bewusst nicht durch `wp_kses_post()` gefiltert, damit Formulare
und Nonces erhalten bleiben. Aufrufende Plugins müssen den Inhalt deshalb
bereits sicher rendern und escapen.

## Datumsformat

Sichtbare Datumswerte werden projektweit deutsch als `TT.MM.JJ` ausgegeben:

```php
echo esc_html(flz_ui_format_date($mysql_date));
echo esc_html(flz_ui_format_datetime($mysql_datetime));
echo esc_html(flz_ui_format_time($mysql_time));
```

## Buttons

```php
echo flz_ui()->button_new(
    array(
        'href'  => admin_url('admin.php?page=flz-ags&action=new'),
        'label' => 'Neue AG',
    )
);

echo flz_ui()->button_save(
    array(
        'label' => 'Speichern',
    )
);

echo flz_ui()->button_delete(
    array(
        'label' => 'Eintrag löschen',
    )
);

echo flz_ui()->button_filter(
    array(
        'label' => 'Liste filtern',
    )
);
```

Buttons für riskante Aktionen bekommen zentral eine auffällige Farbe und eine
Sicherheitsabfrage:

- `button_delete()` für Löschen
- `button_clear()` für Entfernen/Leeren
- `button_reset()` für Zurücksetzen/Überschreiben
- `button_upload()` für Upload/Import mit möglicher Datenänderung

Die Standardfrage kann überschrieben oder bewusst deaktiviert werden:

```php
echo flz_ui()->button_reset(
    array(
        'label'   => 'Teilnehmerliste leeren',
        'confirm' => 'Wirklich alle Teilnehmer löschen?',
    )
);

echo flz_ui()->button_delete(
    array(
        'label'   => 'Testeintrag löschen',
        'confirm' => false,
    )
);
```

Icon-Buttons sind im Normalfall icon-only. Der Text wird visuell ausgeblendet, der Browser-Titel erscheint beim Hover. `icon_alt` muss die Funktion beschreiben, nicht die Grafik:

```php
echo flz_ui()->icon_button(
    array(
        'icon'     => 'plus',
        'icon_alt' => 'Neue AG anlegen',
        'href'     => $url,
        'variant'  => 'primary',
    )
);
```

Inline-SVGs haben kein `alt`-Attribut. Der funktionale Alternativtext wird deshalb über `role="img"`, `aria-label` und `title` ausgegeben. Beim icon-only Button bekommt zusätzlich der Button selbst `aria-label` und `title`.

## Formularfelder

Formularhüllen, Nonces und versteckte Felder sollen ebenfalls über die
Komponenten laufen. So bleiben Sicherheitsfelder und Markup zentral
änderbar:

```php
echo flz_ui()->form_start(
    array(
        'method'     => 'post',
        'action'     => admin_url('admin-post.php'),
        'nonce'      => 'flz_ags_save_course',
        'hidden'     => array(
            'action'    => 'flz_ags_save_course',
            'course_id' => $course_id,
        ),
        'class'      => 'flz-ags-admin-form',
    )
);

echo flz_ui()->hidden('selected', $selected_id);
echo flz_ui()->form_end();
```

Für einzelne riskante Aktionen in Tabellen gibt es `action_form_button()`. Die
Komponente rendert Formular, Hidden Fields, Nonce und Button-Preset zusammen:

```php
echo flz_ui()->action_form_button(
    array(
        'preset' => 'delete',
        'label'  => 'Datensatz löschen',
        'method' => 'post',
        'nonce'  => 'flz_admin_action',
        'hidden' => array(
            'record_delete' => $record_id,
        ),
    )
);
```

```php
echo flz_ui()->input(
    'email',
    array(
        'name'        => 'email',
        'label'       => 'E-Mail',
        'value'       => $email,
        'required'    => true,
        'autocomplete'=> 'email',
        'errors'      => $result ?? array(),
    )
);

echo flz_ui()->input(
    'time',
    array(
        'name'     => 'start_time',
        'label'    => 'Beginn',
        'value'    => $start_time,
        'required' => true,
    )
);
```

Unterstützte Feldtypen:

- `text`
- `email`
- `password`
- `date`
- `time`
- `datetime-local`
- `number`
- `integer`
- `url`
- `tel`
- `hidden`
- `textarea`
- `select`
- `checkbox`
- `radio`

Für Speziallayouts wie Karten oder Tabellen gibt es zusätzlich einzelne
Choice-Inputs ohne Wrapper:

```php
echo flz_ui()->radio_input(
    array(
        'name'     => 'slot_id',
        'value'    => $slot_id,
        'checked'  => $is_selected,
        'required' => true,
    )
);
```

## CSV-Panel

Wiederkehrende CSV-Bereiche sollen über `csv_panel()` laufen. Dadurch bleiben
Format-Hinweis, Export, Upload-Formular und riskante Import-Bestätigung zentral:

```php
echo flz_ui()->csv_panel(
    array(
        'title'       => 'Lehrkräfte CSV',
        'description' => 'CSV exportieren oder neue Daten importieren.',
        'format'      => 'Geschlecht(m/f); Name; Vorname; Email',
        'export'      => array(
            'href'  => $csv_url,
            'label' => 'CSV herunterladen',
        ),
        'upload'      => array(
            'file_name'    => 'teacher-csv',
            'button_label' => 'CSV importieren',
            'nonce'        => 'flzest_admin_action',
        ),
    )
);
```

Nicht verarbeitete CSV-Zeilen können zentral angezeigt werden:

```php
echo flz_ui()->csv_unprocessed_notice($unprocessed_rows);
```

## Field Matrix

Für wiederholbare kleine Feldgruppen, z. B. AG-Slots, gibt es `field_matrix()`.
Sie rendert mindestens eine Zeile und ergänzt per Plus-Button weitere leere
Zeilen:

```php
echo flz_ui()->field_matrix(
    array(
        'id'       => 'flz-ags-slots',
        'rows'     => $slot_rows,
        'columns'  => array(
            array(
                'label' => 'Aktiv',
                'field' => array(
                    'type'        => 'checkbox',
                    'name'        => 'slots[{index}][is_active]',
                    'label'       => 'aktiv',
                    'checked_key' => 'is_active',
                ),
            ),
            array(
                'label' => 'Beginn',
                'field' => array(
                    'type'      => 'time',
                    'name'      => 'slots[{index}][start_time]',
                    'value_key' => 'start_time',
                    'aria_label'=> 'Beginn',
                ),
            ),
        ),
    )
);
```

## Cards und Choice Cards

`card()` und `choice_card()` bilden ruhige Inhalts- und Auswahlkarten. Die Optik
orientiert sich an den News-Kacheln der Website: Bild oben, klare Typografie,
Kurztext, Metadaten und dezente Aktionen.

```php
echo flz_ui()->card(
    array(
        'image_url' => $image_url,
        'image_alt' => 'Basketball AG',
        'title'     => 'Basketball AG',
        'text'      => 'Spaß am Basketballspiel für Neulinge und Fortgeschrittene.',
        'meta'      => array(
            'Zeit'   => 'Do, 13:40–15:10',
            'Plätze' => '8 frei von 20',
        ),
        'actions'   => array(
            array('href' => $url, 'label' => 'Details anzeigen'),
        ),
    )
);

echo flz_ui()->choice_card(
    array(
        'name'     => 'slot_id',
        'value'    => $slot_id,
        'checked'  => $is_selected,
        'required' => true,
        'title'    => $slot_title,
        'meta'     => array('Zeit' => $slot_time),
    )
);
```

## Editable Rows

Für Backend-Tabellen, in denen ein Datensatz direkt in der Tabellenzeile
bearbeitet werden soll, gibt es `editable_row()`. Die Komponente setzt sich aus
den vorhandenen Formularfeldern und Buttons zusammen. Das Formular liegt in der
Aktionszelle; die Felder werden über das HTML-Attribut `form` zugeordnet. So
bleibt das Tabellen-Markup gültig.

```php
echo flz_ui()->button_new(
    array(
        'label' => 'Neue Schule anlegen',
        'attrs' => array(
            'data-flz-ui-show-new-row' => 'flz-school-new',
        ),
    )
);

echo flz_ui()->editable_row(
    array(
        'id'         => 'flz-school-new',
        'new'        => true,
        'row_hidden' => true,
        'form'       => array(
            'method' => 'post',
            'nonce'  => 'flz_admin_action',
            'hidden' => array('school_id' => ''),
        ),
        'cells'      => array(
            array(
                'view'  => '',
                'field' => array(
                    'type'     => 'text',
                    'name'     => 'name',
                    'label'    => 'Name',
                    'required' => true,
                ),
            ),
            array(
                'view'  => '',
                'field' => array(
                    'type'  => 'number',
                    'name'  => 'available_seats',
                    'label' => 'Freie Plätze',
                    'min'   => 0,
                ),
            ),
        ),
        'save'       => array(
            'label' => 'Schule speichern',
            'attrs' => array('name' => 'school_submit'),
        ),
    )
);
```

Wenn nicht alle Formularfelder sinnvoll in die Haupttabelle passen, kann eine
Detailzeile ergänzt werden:

```php
'details' => array(
    'label'  => 'Buchung bearbeiten',
    'fields' => array(
        array('type' => 'email', 'name' => 'parent[email]', 'label' => 'E-Mail'),
        array('type' => 'text', 'name' => 'parent[studentClass]', 'label' => 'Klasse Schüler*in'),
    ),
),
```

## Validierung

Serverseitige Validierung bleibt führend. HTML5-Attribute und JS sind nur Komfort.

```php
$schema = array(
    'email' => array(
        'type'     => 'email',
        'label'    => 'E-Mail',
        'required' => true,
    ),
    'start_time' => array(
        'type'     => 'time',
        'label'    => 'Beginn',
        'required' => true,
    ),
);

$result = flz_ui_validate($_POST, $schema);

if (!$result->is_valid()) {
    echo flz_ui()->notice($result->first_error(), 'error');
}

$values = $result->values();
```

Fehlermeldungen sind deutsch und feldbezogen, z. B.:

- `„E-Mail“ muss eine gültige E-Mail-Adresse enthalten.`
- `„Beginn“ muss eine gültige Uhrzeit im Format HH:MM enthalten.`
- `„Name“ ist ein Pflichtfeld.`

## JavaScript

Das Plugin stellt `window.flzUi` bereit:

```js
const button = window.flzUi.createButton({
  icon: 'plus',
  altText: 'Neu anlegen',
  label: 'Neu',
  variant: 'primary'
});
```

Auch in JavaScript gilt: Wenn ein grafisches Icon verwendet wird, muss `altText` gesetzt sein und die Funktion beschreiben.

## Gestaltung

Die Optik ist absichtlich ruhig: dezente Rundungen, klare Fokuszustände, zurückhaltende Schatten und eine primäre Farbe, die sich an der bestehenden Website-/Plugin-Ästhetik orientiert. Alle zentralen Werte liegen als CSS-Variablen in `assets/css/flz-ui-components.css`.

## Interne Struktur

`Flz_Ui_Components_Renderer` bleibt die öffentliche Fassade für Templates.
Die Implementierung ist intern in Cluster aufgeteilt:

- Formular-Rendering
- Button-Rendering mit zentralen Presets
- Feld-Rendering
- Layout- und Verbundkomponenten für Action-Formulare, CSV, Field Matrix und Cards
- Editable-Row-Rendering für Inline-Bearbeitung in Backend-Tabellen
- Icon-Rendering
- Notice-Rendering
- gemeinsame HTML-/Attribut-Helfer

Dadurch bleibt die Pseudo-HTML-Syntax in Templates stabil, während die
Komponenten intern leichter wartbar sind.
