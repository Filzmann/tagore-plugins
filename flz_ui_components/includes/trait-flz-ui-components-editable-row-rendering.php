<?php
/**
 * Tabellenzeilen-Rendering für Inline-Bearbeitung.
 */

defined('ABSPATH') || exit;

/**
 * Rendert Tabellenzeilen, die von der Ansicht direkt in Eingabefelder wechseln.
 */
trait Flz_Ui_Components_Editable_Row_Rendering
{
    /**
     * Rendert eine Tabellenzeile mit optionaler Detailzeile zum direkten Bearbeiten.
     *
     * Die Komponente nutzt bewusst normale Formulare mit `form`-Attributen auf
     * den Eingabefeldern. So bleibt das Tabellen-Markup gültig: Das Formular
     * liegt in der Aktionszelle, die Felder dürfen trotzdem in anderen Zellen
     * stehen und werden gemeinsam abgeschickt.
     *
     * @param array<string,mixed> $args Zeilenargumente.
     */
    public function editable_row(array $args): string
    {
        $cells = isset($args['cells']) && is_array($args['cells']) ? $args['cells'] : array();
        if (empty($cells)) {
            throw new InvalidArgumentException('flz_ui editable_row benötigt mindestens eine Tabellenzelle.');
        }

        $row_id = $this->editable_row_id($args);
        $form_id = !empty($args['form_id'])
            ? $this->safe_token((string) $args['form_id'])
            : $row_id . '-form';
        $is_new = !empty($args['new']);
        $starts_editing = $is_new || !empty($args['start_editing']);
        $row_is_hidden = !empty($args['row_hidden']);
        $row_classes = array('flz-ui-editable-row');

        if ($starts_editing) {
            $row_classes[] = 'is-editing';
        }

        if ($is_new) {
            $row_classes[] = 'flz-ui-editable-row--new';
        }

        $row_attrs = isset($args['attrs']) && is_array($args['attrs']) ? $args['attrs'] : array();
        $row_attrs['id'] = $row_id;
        $row_attrs['class'] = $this->classes(
            $row_classes,
            isset($args['class']) ? (string) $args['class'] : ''
        );
        $row_attrs['data-flz-ui-editable-row'] = true;

        if ($is_new) {
            $row_attrs['data-flz-ui-new-row'] = true;
        }

        if ($row_is_hidden) {
            $row_attrs['hidden'] = true;
        }

        $html = '<tr' . $this->attributes($row_attrs) . '>';

        foreach (array_values($cells) as $index => $cell) {
            $html .= $this->editable_row_cell((array) $cell, $form_id, $row_id, $index);
        }

        $html .= $this->editable_row_actions_cell($args, $form_id);
        $html .= '</tr>';

        if (!empty($args['details']) && is_array($args['details'])) {
            $html .= $this->editable_row_details((array) $args['details'], $form_id, $row_id, count($cells) + 1, $row_is_hidden);
        }

        return $html;
    }

    /**
     * Rendert eine einzelne Tabellenzelle.
     *
     * @param array<string,mixed> $cell Zellargumente.
     */
    private function editable_row_cell(array $cell, string $form_id, string $row_id, int $index): string
    {
        $attrs = isset($cell['attrs']) && is_array($cell['attrs']) ? $cell['attrs'] : array();
        $attrs['class'] = $this->classes(
            array('flz-ui-editable-row__cell'),
            isset($attrs['class']) ? (string) $attrs['class'] : ''
        );

        $html = '<td' . $this->attributes($attrs) . '>';

        if (isset($cell['field']) && is_array($cell['field'])) {
            $html .= '<span class="flz-ui-editable-row__view">' . $this->editable_row_view($cell) . '</span>';
            $html .= '<span class="flz-ui-editable-row__edit">';
            $html .= $this->editable_row_field((array) $cell['field'], $form_id, $row_id, $index, true);
            $html .= '</span>';
        } else {
            $html .= '<span class="flz-ui-editable-row__static">' . $this->editable_row_view($cell) . '</span>';
        }

        $html .= '</td>';

        return $html;
    }

    /**
     * Rendert die Aktionszelle mit eingebettetem Formular.
     *
     * @param array<string,mixed> $args Zeilenargumente.
     */
    private function editable_row_actions_cell(array $args, string $form_id): string
    {
        $attrs = isset($args['actions_attrs']) && is_array($args['actions_attrs']) ? $args['actions_attrs'] : array();
        $attrs['class'] = $this->classes(
            array('flz-ui-editable-row__actions'),
            isset($attrs['class']) ? (string) $attrs['class'] : ''
        );

        $html = '<td' . $this->attributes($attrs) . '>';
        $html .= $this->editable_row_form($args, $form_id);
        $html .= '<span class="flz-ui-editable-row__view-actions">';

        if (empty($args['new'])) {
            $edit_args = isset($args['edit']) && is_array($args['edit']) ? $args['edit'] : array();
            $edit_args = array_replace_recursive(
                array(
                    'label' => 'Eintrag bearbeiten',
                    'type'  => 'button',
                    'attrs' => array(
                        'data-flz-ui-edit-row' => true,
                    ),
                ),
                $edit_args
            );
            $edit_args['attrs']['data-flz-ui-edit-row'] = true;
            $html .= $this->button_edit($edit_args);
        }

        if (!empty($args['extra_actions'])) {
            $html .= (string) $args['extra_actions'];
        }

        $html .= '</span>';
        $html .= '<span class="flz-ui-editable-row__edit-actions">';

        $save_args = isset($args['save']) && is_array($args['save']) ? $args['save'] : array();
        $save_args = array_replace_recursive(
            array(
                'label' => 'Eintrag speichern',
                'attrs' => array(
                    'form' => $form_id,
                ),
            ),
            $save_args
        );
        $save_args['attrs']['form'] = $form_id;
        $html .= $this->button_save($save_args);

        $cancel_args = isset($args['cancel']) && is_array($args['cancel']) ? $args['cancel'] : array();
        $cancel_args = array_replace_recursive(
            array(
                'label'      => 'Bearbeitung abbrechen',
                'icon'       => 'close',
                'icon_alt'   => 'Bearbeitung abbrechen',
                'hide_label' => true,
                'type'       => 'button',
                'variant'    => 'secondary',
                'attrs'      => array(
                    'data-flz-ui-cancel-edit-row' => true,
                ),
            ),
            $cancel_args
        );
        $cancel_args['attrs']['data-flz-ui-cancel-edit-row'] = true;
        $html .= $this->button($cancel_args);

        $html .= '</span>';
        $html .= '</td>';

        return $html;
    }

    /**
     * Rendert die Detailzeile für Felder, die in der Haupttabelle zu viel Platz brauchen.
     *
     * @param array<string,mixed> $details Detailargumente.
     */
    private function editable_row_details(array $details, string $form_id, string $row_id, int $default_colspan, bool $hidden): string
    {
        $attrs = isset($details['attrs']) && is_array($details['attrs']) ? $details['attrs'] : array();
        $attrs['class'] = $this->classes(
            array('flz-ui-editable-row__details'),
            isset($attrs['class']) ? (string) $attrs['class'] : ''
        );
        $attrs['data-flz-ui-editable-details-for'] = $row_id;

        if ($hidden) {
            $attrs['hidden'] = true;
        }

        $colspan = !empty($details['colspan']) ? max(1, (int) $details['colspan']) : $default_colspan;
        $html = '<tr' . $this->attributes($attrs) . '>';
        $html .= '<td colspan="' . esc_attr((string) $colspan) . '">';

        if (!empty($details['label'])) {
            $html .= '<strong class="flz-ui-editable-row__details-title">' . esc_html((string) $details['label']) . '</strong>';
        }

        $html .= '<div class="flz-ui-editable-row__details-grid">';

        if (!empty($details['fields']) && is_array($details['fields'])) {
            foreach (array_values($details['fields']) as $index => $field) {
                if (!is_array($field)) {
                    continue;
                }

                $html .= $this->editable_row_field($field, $form_id, $row_id, $index, false);
            }
        }

        if (!empty($details['html'])) {
            $html .= wp_kses_post((string) $details['html']);
        }

        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';

        return $html;
    }

    /**
     * Rendert ein Feld und bindet es über das form-Attribut an das Zeilenformular.
     *
     * @param array<string,mixed> $field Feldargumente.
     */
    private function editable_row_field(array $field, string $form_id, string $row_id, int $index, bool $compact): string
    {
        $name = isset($field['name']) ? (string) $field['name'] : '';
        $label = isset($field['label']) ? (string) $field['label'] : '';
        $attrs = isset($field['attrs']) && is_array($field['attrs']) ? $field['attrs'] : array();
        $attrs['form'] = $form_id;

        if ($compact && '' !== $label && empty($attrs['aria-label'])) {
            $attrs['aria-label'] = $label;
        }

        $field['attrs'] = $attrs;

        if (empty($field['id']) && '' !== $name) {
            $field['id'] = $row_id . '-' . $index . '-' . $this->safe_token($name);
        }

        if ($compact && empty($field['show_label'])) {
            $field['label'] = '';
            $field['class'] = $this->classes(
                array('flz-ui-field--table-edit'),
                isset($field['class']) ? (string) $field['class'] : ''
            );
        }

        return $this->field($field);
    }

    /**
     * Rendert das unsichtbare Formular der Zeile.
     *
     * @param array<string,mixed> $args Zeilenargumente.
     */
    private function editable_row_form(array $args, string $form_id): string
    {
        $form = isset($args['form']) && is_array($args['form']) ? $args['form'] : array();
        $attrs = isset($form['attrs']) && is_array($form['attrs']) ? $form['attrs'] : array();
        $attrs['id'] = $form_id;

        $form['attrs'] = $attrs;
        $form['class'] = $this->classes(
            array('flz-ui-editable-row__form'),
            isset($form['class']) ? (string) $form['class'] : ''
        );
        $form['method'] = isset($form['method']) ? $form['method'] : 'post';

        return $this->form_start($form) . $this->form_end();
    }

    /**
     * Gibt die Ansichtsseite einer Zelle zurück.
     *
     * @param array<string,mixed> $cell Zellargumente.
     */
    private function editable_row_view(array $cell): string
    {
        if (array_key_exists('view_html', $cell)) {
            return wp_kses_post((string) $cell['view_html']);
        }

        return esc_html(array_key_exists('view', $cell) ? (string) $cell['view'] : '');
    }

    /**
     * Erzeugt eine stabile, CSS-/HTML-taugliche Zeilen-ID.
     *
     * @param array<string,mixed> $args Zeilenargumente.
     */
    private function editable_row_id(array $args): string
    {
        $id = isset($args['id']) ? (string) $args['id'] : '';
        if ('' === trim($id)) {
            throw new InvalidArgumentException('flz_ui editable_row benötigt eine nicht-leere id.');
        }

        return $this->safe_token($id);
    }
}
