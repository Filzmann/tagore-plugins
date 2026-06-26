<?php
/**
 * Layout- und Verbundkomponenten für die gemeinsame UI.
 */

defined('ABSPATH') || exit;

/**
 * Rendert zusammengesetzte UI-Bausteine aus Buttons, Formularen und Feldern.
 */
trait Flz_Ui_Components_Layout_Rendering
{
    /**
     * Rendert ein kurzes Formular, das nur eine Aktion mit Hidden Fields ausführt.
     *
     * Dadurch bleiben Lösch-, Leeren-, Widerrufs- und ähnliche Aktionsformulare
     * in den Fachplugins gleich aufgebaut: Formular, Nonce, Hidden Fields und
     * Button-Preset kommen aus einer zentralen Stelle.
     *
     * @param array<string,mixed> $args Komponentenargumente.
     */
    public function action_form_button(array $args): string
    {
        $form = isset($args['form']) && is_array($args['form']) ? $args['form'] : array();
        $button = isset($args['button']) && is_array($args['button']) ? $args['button'] : array();

        foreach (array('method', 'action', 'nonce', 'nonce_name', 'hidden', 'hidden_fields', 'enctype') as $key) {
            if (array_key_exists($key, $args) && !array_key_exists($key, $form)) {
                $form[$key] = $args[$key];
            }
        }

        $form_attrs = isset($form['attrs']) && is_array($form['attrs']) ? $form['attrs'] : array();
        $form_attrs['style'] = isset($form_attrs['style'])
            ? trim((string) $form_attrs['style'] . ' display:inline;')
            : 'display:inline;';
        $form['attrs'] = $form_attrs;
        $form['method'] = isset($form['method']) ? $form['method'] : 'post';

        if (!empty($args['label']) && empty($button['label'])) {
            $button['label'] = (string) $args['label'];
        }

        $preset = isset($args['preset']) ? (string) $args['preset'] : 'button';
        $html = $this->form_start($form);
        $html .= $this->preset_button($preset, $button);
        $html .= $this->form_end();

        return $html;
    }

    /**
     * Rendert einen CSV-Download-/Upload-Block.
     *
     * @param array<string,mixed> $args Komponentenargumente.
     */
    public function csv_panel(array $args): string
    {
        $classes = array('flz-ui-panel', 'flz-ui-csv-panel');
        $html = '<section class="' . esc_attr($this->classes($classes, isset($args['class']) ? (string) $args['class'] : '')) . '">';

        if (!empty($args['title'])) {
            $html .= '<h3 class="flz-ui-panel__title">' . esc_html((string) $args['title']) . '</h3>';
        }

        if (!empty($args['description'])) {
            $html .= '<p class="flz-ui-panel__description">' . wp_kses_post((string) $args['description']) . '</p>';
        }

        if (!empty($args['format'])) {
            $html .= '<p class="flz-ui-csv-panel__format"><strong>' . esc_html__('Format:', 'flz-ui-components') . '</strong> <code>' . esc_html((string) $args['format']) . '</code></p>';
        }

        $html .= '<div class="flz-ui-csv-panel__actions">';

        if (!empty($args['export']) && is_array($args['export'])) {
            $export = $args['export'];
            if (!empty($export['href'])) {
                $html .= $this->button_export(
                    array(
                        'href'  => (string) $export['href'],
                        'label' => isset($export['label']) ? (string) $export['label'] : 'CSV herunterladen',
                    )
                );
            }
        }

        if (!empty($args['upload']) && is_array($args['upload'])) {
            $html .= $this->csv_upload_form($args['upload']);
        }

        $html .= '</div>';

        if (!empty($args['unprocessed'])) {
            $html .= $this->csv_unprocessed_notice($args['unprocessed'], isset($args['unprocessed_args']) && is_array($args['unprocessed_args']) ? $args['unprocessed_args'] : array());
        }

        $html .= '</section>';

        return $html;
    }

    /**
     * Rendert nicht verarbeitete CSV-Zeilen als Notice mit read-only Textarea.
     *
     * @param array<int,mixed>|string $rows Nicht verarbeitete Zeilen oder Text.
     * @param array<string,mixed>     $args Anzeigeoptionen.
     */
    public function csv_unprocessed_notice($rows, array $args = array()): string
    {
        $text = is_array($rows)
            ? implode(
                '',
                array_map(
                    static function ($line): string {
                        return is_array($line) ? implode(';', array_map('strval', $line)) . "\n" : (string) $line . "\n";
                    },
                    $rows
                )
            )
            : (string) $rows;

        if ('' === trim($text)) {
            return '';
        }

        $html = $this->notice(
            isset($args['message']) ? (string) $args['message'] : 'Folgende Datensätze konnten nicht verarbeitet werden:',
            'error'
        );
        $html .= $this->field(
            array(
                'type'  => 'textarea',
                'name'  => isset($args['name']) ? (string) $args['name'] : 'flz_ui_unprocessed_csv_rows',
                'label' => isset($args['label']) ? (string) $args['label'] : 'Nicht verarbeitete Datensätze',
                'value' => $text,
                'rows'  => isset($args['rows']) ? (int) $args['rows'] : 5,
                'attrs' => array('readonly' => true),
            )
        );

        return $html;
    }

    /**
     * Rendert eine wiederholbare Feldtabelle mit Plus-Button für neue Leerzeilen.
     *
     * @param array<string,mixed> $args Komponentenargumente.
     */
    public function field_matrix(array $args): string
    {
        $id = !empty($args['id']) ? $this->safe_token((string) $args['id']) : 'flz-ui-field-matrix';
        $columns = isset($args['columns']) && is_array($args['columns']) ? array_values($args['columns']) : array();
        $rows = isset($args['rows']) && is_array($args['rows']) ? array_values($args['rows']) : array();
        $min_rows = isset($args['min_rows']) ? max(0, (int) $args['min_rows']) : 1;

        if (empty($columns)) {
            throw new InvalidArgumentException('flz_ui field_matrix benötigt mindestens eine Spalte.');
        }

        while (count($rows) < $min_rows) {
            $rows[] = array();
        }

        $hidden_fields = isset($args['hidden_fields']) && is_array($args['hidden_fields']) ? array_values($args['hidden_fields']) : array();
        $table_class = $this->classes(
            array('flz-ui-field-matrix'),
            isset($args['class']) ? (string) $args['class'] : ''
        );
        $next_index = count($rows);

        $html = '<div class="flz-ui-field-matrix-wrap" id="' . esc_attr($id) . '">';
        $html .= '<table class="' . esc_attr($table_class) . '">';
        $html .= '<thead><tr>';

        foreach ($columns as $column) {
            $html .= '<th scope="col">' . esc_html(isset($column['label']) ? (string) $column['label'] : '') . '</th>';
        }

        $html .= '</tr></thead>';
        $html .= '<tbody data-flz-ui-field-matrix="' . esc_attr($id) . '" data-flz-ui-next-index="' . esc_attr((string) $next_index) . '">';

        foreach ($rows as $index => $row) {
            $html .= $this->field_matrix_row($columns, $hidden_fields, (array) $row, (string) $index, false);
        }

        $html .= $this->field_matrix_row($columns, $hidden_fields, array(), '__index__', true);
        $html .= '</tbody></table>';
        $html .= '<p class="flz-ui-field-matrix__actions">';
        $html .= $this->button_new(
            array(
                'label' => isset($args['add_label']) ? (string) $args['add_label'] : 'Zeile hinzufügen',
                'type'  => 'button',
                'attrs' => array(
                    'data-flz-ui-add-matrix-row' => $id,
                ),
            )
        );
        $html .= '</p></div>';

        return $html;
    }

    /**
     * Rendert eine ruhige Kartenkomponente, angelehnt an die News-Kacheln.
     *
     * @param array<string,mixed> $args Komponentenargumente.
     */
    public function card(array $args): string
    {
        $tag = !empty($args['href']) ? 'a' : 'article';
        $attrs = isset($args['attrs']) && is_array($args['attrs']) ? $args['attrs'] : array();
        $attrs['class'] = $this->classes(
            array('flz-ui-card'),
            isset($args['class']) ? (string) $args['class'] : ''
        );

        if (!empty($args['href'])) {
            $attrs['href'] = (string) $args['href'];
        }

        return '<' . $tag . $this->attributes($attrs) . '>' . $this->card_inner($args) . '</' . $tag . '>';
    }

    /**
     * Rendert eine auswählbare Karte mit Radio- oder Checkbox-Input.
     *
     * @param array<string,mixed> $args Komponentenargumente.
     */
    public function choice_card(array $args): string
    {
        $type = isset($args['type']) && 'checkbox' === $args['type'] ? 'checkbox' : 'radio';
        $input_args = array(
            'name'    => $this->required_name($args),
            'value'   => array_key_exists('value', $args) ? $args['value'] : '1',
            'checked' => !empty($args['checked']),
            'required' => !empty($args['required']),
            'attrs'   => isset($args['input_attrs']) && is_array($args['input_attrs']) ? $args['input_attrs'] : array(),
        );

        if (!empty($args['disabled'])) {
            $input_args['attrs']['disabled'] = true;
        }

        $attrs = isset($args['attrs']) && is_array($args['attrs']) ? $args['attrs'] : array();
        $attrs['class'] = $this->classes(
            array('flz-ui-choice-card'),
            isset($args['class']) ? (string) $args['class'] : ''
        );

        if (!empty($args['disabled'])) {
            $attrs['data-disabled'] = 'true';
        }

        $html = '<label' . $this->attributes($attrs) . '>';
        $html .= '<span class="flz-ui-choice-card__input">';
        $html .= 'checkbox' === $type ? $this->checkbox_input($input_args) : $this->radio_input($input_args);
        $html .= '</span>';
        $html .= '<span class="flz-ui-card flz-ui-card--choice">' . $this->card_inner($args) . '</span>';
        $html .= '</label>';

        return $html;
    }

    /**
     * Rendert ein Formular mit CSV-Dateifeld und Upload-Button.
     *
     * @param array<string,mixed> $upload Uploadargumente.
     */
    private function csv_upload_form(array $upload): string
    {
        $file_name = isset($upload['file_name']) ? (string) $upload['file_name'] : 'csv-file';
        $file_id = isset($upload['file_id']) ? (string) $upload['file_id'] : $file_name;
        $form = isset($upload['form']) && is_array($upload['form']) ? $upload['form'] : array();
        $form['method'] = 'post';
        $form['enctype'] = 'multipart/form-data';

        foreach (array('action', 'nonce', 'nonce_name', 'hidden', 'hidden_fields') as $key) {
            if (array_key_exists($key, $upload) && !array_key_exists($key, $form)) {
                $form[$key] = $upload[$key];
            }
        }

        $html = $this->form_start($form);
        $html .= $this->input(
            'file',
            array(
                'name'   => $file_name,
                'id'     => $file_id,
                'label'  => isset($upload['file_label']) ? (string) $upload['file_label'] : 'CSV-Datei',
                'accept' => '.csv',
            )
        );
        $html .= $this->button_upload(
            array(
                'label' => isset($upload['button_label']) ? (string) $upload['button_label'] : 'CSV hochladen',
                'attrs' => array(
                    'name' => isset($upload['submit_name']) ? (string) $upload['submit_name'] : 'submit_csv',
                ),
            )
        );
        $html .= $this->form_end();

        return $html;
    }

    /**
     * Rendert eine einzelne Matrix-Zeile.
     *
     * @param array<int,array<string,mixed>> $columns       Spalten.
     * @param array<int,array<string,mixed>> $hidden_fields Hidden Fields.
     * @param array<string,mixed>            $row           Zeilendaten.
     */
    private function field_matrix_row(array $columns, array $hidden_fields, array $row, string $index, bool $template): string
    {
        $attrs = array(
            'data-flz-ui-field-matrix-row' => true,
        );

        if ($template) {
            $attrs['data-flz-ui-field-matrix-template'] = true;
            $attrs['hidden'] = true;
        }

        $html = '<tr' . $this->attributes($attrs) . '>';
        $first = true;

        foreach ($columns as $column) {
            $html .= '<td>';

            if ($first) {
                foreach ($hidden_fields as $hidden_field) {
                    $html .= $this->field_matrix_hidden($hidden_field, $row, $index, $template);
                }
                $first = false;
            }

            if (!empty($column['field']) && is_array($column['field'])) {
                $html .= $this->field_matrix_field((array) $column['field'], $row, $index, $template);
            }

            $html .= '</td>';
        }

        $html .= '</tr>';

        return $html;
    }

    /**
     * Rendert ein Matrix-Feld.
     *
     * @param array<string,mixed> $field Felddefinition.
     * @param array<string,mixed> $row   Zeilendaten.
     */
    private function field_matrix_field(array $field, array $row, string $index, bool $template): string
    {
        $value_key = isset($field['value_key']) ? (string) $field['value_key'] : '';
        $checked_key = isset($field['checked_key']) ? (string) $field['checked_key'] : '';
        $default = array_key_exists('default', $field) ? $field['default'] : '';

        unset($field['value_key'], $field['checked_key'], $field['default']);

        if (!empty($field['name'])) {
            $field['name'] = str_replace('{index}', $index, (string) $field['name']);
        }

        if (!empty($field['id'])) {
            $field['id'] = str_replace('{index}', $index, (string) $field['id']);
        }

        if ('' !== $value_key && !array_key_exists('value', $field)) {
            $field['value'] = $this->row_value($row, $value_key, $default);
        }

        if ('' !== $checked_key && !array_key_exists('checked', $field)) {
            $field['checked'] = (bool) $this->row_value($row, $checked_key, false);
        }

        if ($template) {
            $attrs = isset($field['attrs']) && is_array($field['attrs']) ? $field['attrs'] : array();
            $attrs['disabled'] = true;
            $field['attrs'] = $attrs;
        }

        if (empty($field['label']) && !empty($field['aria_label'])) {
            $attrs = isset($field['attrs']) && is_array($field['attrs']) ? $field['attrs'] : array();
            $attrs['aria-label'] = (string) $field['aria_label'];
            $field['attrs'] = $attrs;
            unset($field['aria_label']);
        }

        return $this->field($field);
    }

    /**
     * Rendert ein Hidden Field in einer Matrix.
     *
     * @param array<string,mixed> $field Felddefinition.
     * @param array<string,mixed> $row   Zeilendaten.
     */
    private function field_matrix_hidden(array $field, array $row, string $index, bool $template): string
    {
        $name = isset($field['name']) ? str_replace('{index}', $index, (string) $field['name']) : '';
        $value_key = isset($field['value_key']) ? (string) $field['value_key'] : '';
        $value = '' !== $value_key ? $this->row_value($row, $value_key, $field['default'] ?? '') : ($field['value'] ?? '');
        $attrs = isset($field['attrs']) && is_array($field['attrs']) ? $field['attrs'] : array();

        if ($template) {
            $attrs['disabled'] = true;
        }

        return $this->hidden($name, $value, array('attrs' => $attrs));
    }

    /**
     * Gibt einen Wert aus Array oder Objekt zurück.
     *
     * @param array<string,mixed>|object $row Zeilendaten.
     * @param mixed                      $default Fallback.
     */
    private function row_value($row, string $key, $default = '')
    {
        if (is_array($row) && array_key_exists($key, $row)) {
            return $row[$key];
        }

        if (is_object($row) && isset($row->{$key})) {
            return $row->{$key};
        }

        return $default;
    }

    /**
     * Rendert den inneren Karteninhalt.
     *
     * @param array<string,mixed> $args Kartenargumente.
     */
    private function card_inner(array $args): string
    {
        $html = '';
        $title = isset($args['title']) ? (string) $args['title'] : '';

        if (!empty($args['image_url'])) {
            $image_alt = isset($args['image_alt']) && '' !== trim((string) $args['image_alt'])
                ? (string) $args['image_alt']
                : $title;
            $html .= '<span class="flz-ui-card__image-wrap"><img class="flz-ui-card__image" src="' . esc_url((string) $args['image_url']) . '" alt="' . esc_attr($image_alt) . '" /></span>';
        }

        $html .= '<span class="flz-ui-card__body">';

        if (!empty($args['kicker'])) {
            $html .= '<span class="flz-ui-card__kicker">' . esc_html((string) $args['kicker']) . '</span>';
        }

        if ('' !== $title) {
            $html .= '<span class="flz-ui-card__title">' . esc_html($title) . '</span>';
        }

        if (!empty($args['text'])) {
            $html .= '<span class="flz-ui-card__text">' . esc_html((string) $args['text']) . '</span>';
        }

        if (!empty($args['meta']) && is_array($args['meta'])) {
            $html .= '<dl class="flz-ui-card__meta">';
            foreach ($args['meta'] as $label => $value) {
                if ('' === (string) $value) {
                    continue;
                }
                $html .= '<dt>' . esc_html((string) $label) . '</dt><dd>' . esc_html((string) $value) . '</dd>';
            }
            $html .= '</dl>';
        }

        if (!empty($args['badge'])) {
            $html .= '<span class="flz-ui-card__badge">' . esc_html((string) $args['badge']) . '</span>';
        }

        if (!empty($args['actions']) && is_array($args['actions'])) {
            $html .= '<span class="flz-ui-card__actions">';
            foreach ($args['actions'] as $action) {
                $html .= is_array($action) ? $this->button($action) : (string) $action;
            }
            $html .= '</span>';
        }

        $html .= '</span>';

        return $html;
    }

    /**
     * Rendert einen Button aus einem bekannten Presetnamen.
     *
     * @param array<string,mixed> $args Buttonargumente.
     */
    private function preset_button(string $preset, array $args): string
    {
        return match ($preset) {
            'new' => $this->button_new($args),
            'save' => $this->button_save($args),
            'delete' => $this->button_delete($args),
            'edit' => $this->button_edit($args),
            'filter' => $this->button_filter($args),
            'upload' => $this->button_upload($args),
            'export' => $this->button_export($args),
            'reset' => $this->button_reset($args),
            'view' => $this->button_view($args),
            'media' => $this->button_media($args),
            'clear' => $this->button_clear($args),
            default => $this->button($args),
        };
    }
}
