<?php
/**
 * Feld-Rendering für die gemeinsame UI.
 */

defined('ABSPATH') || exit;

/**
 * Rendert Inputs, Selects, Textareas, Checkboxen und Radiogruppen.
 */
trait Flz_Ui_Components_Field_Rendering
{
    /**
     * Rendert ein Formularfeld.
     *
     * @param array<string,mixed> $args Feldargumente.
     */
    public function field(array $args): string
    {
        $type = isset($args['type']) ? (string) $args['type'] : 'text';

        if ('hidden' === $type) {
            return $this->hidden(
                $this->required_name($args),
                array_key_exists('value', $args) ? $args['value'] : '',
                $args
            );
        }

        if ('select' === $type) {
            return $this->select_field($args);
        }

        if ('textarea' === $type) {
            return $this->textarea_field($args);
        }

        if ('checkbox' === $type) {
            return $this->checkbox_field($args);
        }

        if ('radio' === $type) {
            return $this->radio_field($args);
        }

        return $this->input_field($type, $args);
    }

    /**
     * Rendert ein Input-Feld mit kurzer Syntax.
     *
     * @param array<string,mixed> $args Feldargumente.
     */
    public function input(string $type, array $args): string
    {
        $args['type'] = $type;

        return $this->field($args);
    }

    /**
     * Rendert ein einzelnes Radio-Input ohne Feld-Wrapper.
     *
     * Für Karten- oder Tabellen-Layouts ist eine komplette Radiogruppe oft zu
     * schwergewichtig. Dieser Helper hält trotzdem Attribute, Required-Status
     * und Escaping zentral.
     *
     * @param array<string,mixed> $args Feldargumente.
     */
    public function radio_input(array $args): string
    {
        return $this->choice_input('radio', $args);
    }

    /**
     * Rendert ein einzelnes Checkbox-Input ohne Feld-Wrapper.
     *
     * Nützlich für zusammengesetzte Komponenten, in denen die sichtbare
     * Beschriftung bereits durch Karten- oder Tabellenmarkup entsteht.
     *
     * @param array<string,mixed> $args Feldargumente.
     */
    public function checkbox_input(array $args): string
    {
        return $this->choice_input('checkbox', $args);
    }

    /**
     * Rendert ein normales Input-Feld.
     *
     * @param array<string,mixed> $args Feldargumente.
     */
    private function input_field(string $type, array $args): string
    {
        $name = $this->required_name($args);
        $id = $this->field_id($args, $name);
        $label = isset($args['label']) ? (string) $args['label'] : '';
        $errors = $this->field_errors($args, $name);
        $described_by = $this->described_by($id, $args, $errors);

        $field_html = '';

        if ('' !== $label) {
            $field_html .= '<label class="flz-ui-label" for="' . esc_attr($id) . '">' . esc_html($label) . $this->required_marker($args) . '</label>';
        }

        $field_html .= $this->input_element($type, $args, $id, $described_by, !empty($errors));
        $field_html .= $this->description_and_errors($id, $args, $errors);

        return $this->wrap_field($type, $field_html, $errors, $args);
    }

    /**
     * Rendert das eigentliche Input-Element.
     *
     * @param array<string,mixed> $args         Feldargumente.
     * @param array<int,string>   $described_by aria-describedby IDs.
     * @param bool                $has_errors   Ob dieses konkrete Feld Fehler hat.
     */
    private function input_element(string $type, array $args, string $id, array $described_by, bool $has_errors): string
    {
        $name = $this->required_name($args);
        $attrs = isset($args['attrs']) && is_array($args['attrs']) ? $args['attrs'] : array();
        $attrs['type'] = $type;
        $attrs['name'] = $name;
        $attrs['class'] = $this->classes(array('flz-ui-input'), isset($args['input_class']) ? (string) $args['input_class'] : '');

        if ('' !== $id) {
            $attrs['id'] = $id;
        }

        if (array_key_exists('value', $args)) {
            $attrs['value'] = (string) $args['value'];
        }

        $this->copy_field_attributes($attrs, $args);
        $this->apply_required_and_error_attributes($attrs, $args, $described_by, $has_errors);

        return '<input' . $this->attributes($attrs) . ' />';
    }

    /**
     * Rendert ein einzelnes Choice-Input ohne Wrapper.
     *
     * @param array<string,mixed> $args Feldargumente.
     */
    private function choice_input(string $type, array $args): string
    {
        $name = $this->required_name($args);
        $attrs = isset($args['attrs']) && is_array($args['attrs']) ? $args['attrs'] : array();
        $attrs['type'] = $type;
        $attrs['name'] = $name;
        $attrs['value'] = array_key_exists('value', $args) ? (string) $args['value'] : '1';
        $attrs['class'] = $this->classes(array('flz-ui-' . $this->safe_token($type) . '__input'), isset($args['input_class']) ? (string) $args['input_class'] : '');

        if (!empty($args['id'])) {
            $attrs['id'] = $this->safe_token((string) $args['id']);
        }

        if (!empty($args['checked'])) {
            $attrs['checked'] = true;
        }

        $this->apply_required_and_error_attributes($attrs, $args, array(), false);

        return '<input' . $this->attributes($attrs) . ' />';
    }

    /**
     * Rendert ein Textarea-Feld.
     *
     * @param array<string,mixed> $args Feldargumente.
     */
    private function textarea_field(array $args): string
    {
        $name = $this->required_name($args);
        $id = $this->field_id($args, $name);
        $label = isset($args['label']) ? (string) $args['label'] : '';
        $value = isset($args['value']) ? (string) $args['value'] : '';
        $errors = $this->field_errors($args, $name);
        $described_by = $this->described_by($id, $args, $errors);
        $attrs = isset($args['attrs']) && is_array($args['attrs']) ? $args['attrs'] : array();
        $attrs['name'] = $name;
        $attrs['id'] = $id;
        $attrs['class'] = $this->classes(array('flz-ui-input', 'flz-ui-textarea'), isset($args['input_class']) ? (string) $args['input_class'] : '');

        $this->copy_field_attributes($attrs, $args);
        $this->apply_required_and_error_attributes($attrs, $args, $described_by, !empty($errors));

        $field_html = '';

        if ('' !== $label) {
            $field_html .= '<label class="flz-ui-label" for="' . esc_attr($id) . '">' . esc_html($label) . $this->required_marker($args) . '</label>';
        }

        $field_html .= '<textarea' . $this->attributes($attrs) . '>' . esc_textarea($value) . '</textarea>';
        $field_html .= $this->description_and_errors($id, $args, $errors);

        return $this->wrap_field('textarea', $field_html, $errors, $args);
    }

    /**
     * Rendert ein Select-Feld.
     *
     * @param array<string,mixed> $args Feldargumente.
     */
    private function select_field(array $args): string
    {
        $name = $this->required_name($args);
        $id = $this->field_id($args, $name);
        $label = isset($args['label']) ? (string) $args['label'] : '';
        $value = isset($args['value']) ? (string) $args['value'] : '';
        $errors = $this->field_errors($args, $name);
        $described_by = $this->described_by($id, $args, $errors);
        $attrs = isset($args['attrs']) && is_array($args['attrs']) ? $args['attrs'] : array();
        $attrs['name'] = $name;
        $attrs['id'] = $id;
        $attrs['class'] = $this->classes(array('flz-ui-input', 'flz-ui-select'), isset($args['input_class']) ? (string) $args['input_class'] : '');

        $this->copy_field_attributes($attrs, $args);
        unset($attrs['placeholder']);
        $this->apply_required_and_error_attributes($attrs, $args, $described_by, !empty($errors));

        $field_html = '';

        if ('' !== $label) {
            $field_html .= '<label class="flz-ui-label" for="' . esc_attr($id) . '">' . esc_html($label) . $this->required_marker($args) . '</label>';
        }

        $field_html .= '<select' . $this->attributes($attrs) . '>';

        if (isset($args['placeholder'])) {
            $field_html .= '<option value="">' . esc_html((string) $args['placeholder']) . '</option>';
        }

        $options = isset($args['options']) && is_array($args['options']) ? $args['options'] : array();

        foreach ($options as $option_value => $option_label) {
            $option_value = (string) $option_value;
            $disabled = false;

            if (is_array($option_label)) {
                $disabled = !empty($option_label['disabled']);
                $option_value = isset($option_label['value']) ? (string) $option_label['value'] : $option_value;
                $option_label = isset($option_label['label']) ? (string) $option_label['label'] : $option_value;
            }

            $field_html .= '<option value="' . esc_attr($option_value) . '"' . selected($value, $option_value, false) . disabled($disabled, true, false) . '>' . esc_html((string) $option_label) . '</option>';
        }

        $field_html .= '</select>';
        $field_html .= $this->description_and_errors($id, $args, $errors);

        return $this->wrap_field('select', $field_html, $errors, $args);
    }

    /**
     * Rendert eine Checkbox.
     *
     * @param array<string,mixed> $args Feldargumente.
     */
    private function checkbox_field(array $args): string
    {
        $name = $this->required_name($args);
        $id = $this->field_id($args, $name);
        $label = isset($args['label']) ? (string) $args['label'] : '';
        $errors = $this->field_errors($args, $name);
        $described_by = $this->described_by($id, $args, $errors);
        $attrs = isset($args['attrs']) && is_array($args['attrs']) ? $args['attrs'] : array();
        $attrs['type'] = 'checkbox';
        $attrs['name'] = $name;
        $attrs['id'] = $id;
        $attrs['value'] = isset($args['checkbox_value']) ? (string) $args['checkbox_value'] : '1';
        $attrs['class'] = $this->classes(array('flz-ui-checkbox__input'), isset($args['input_class']) ? (string) $args['input_class'] : '');

        if (!empty($args['checked']) || (isset($args['value']) && (string) $args['value'] === (string) $attrs['value'])) {
            $attrs['checked'] = true;
        }

        $this->apply_required_and_error_attributes($attrs, $args, $described_by, !empty($errors));

        $field_html = '';

        if (array_key_exists('unchecked_value', $args)) {
            $hidden_attrs = array(
                'type'  => 'hidden',
                'name'  => $name,
                'value' => (string) $args['unchecked_value'],
            );

            if (!empty($attrs['form'])) {
                $hidden_attrs['form'] = $attrs['form'];
            }

            $field_html .= '<input' . $this->attributes($hidden_attrs) . ' />';
        }

        $field_html .= '<label class="flz-ui-checkbox" for="' . esc_attr($id) . '">';
        $field_html .= '<input' . $this->attributes($attrs) . ' />';
        $field_html .= '<span class="flz-ui-checkbox__label">' . esc_html($label) . $this->required_marker($args) . '</span>';
        $field_html .= '</label>';
        $field_html .= $this->description_and_errors($id, $args, $errors);

        return $this->wrap_field('checkbox', $field_html, $errors, $args);
    }

    /**
     * Rendert eine Radiogruppe.
     *
     * @param array<string,mixed> $args Feldargumente.
     */
    private function radio_field(array $args): string
    {
        $name = $this->required_name($args);
        $id = $this->field_id($args, $name);
        $label = isset($args['label']) ? (string) $args['label'] : '';
        $value = isset($args['value']) ? (string) $args['value'] : '';
        $errors = $this->field_errors($args, $name);
        $options = isset($args['options']) && is_array($args['options']) ? $args['options'] : array();
        $described_by = $this->described_by($id, $args, $errors);
        $group_attrs = isset($args['attrs']) && is_array($args['attrs']) ? $args['attrs'] : array();
        $radio_attrs = array();

        if (!empty($group_attrs['form'])) {
            $radio_attrs['form'] = (string) $group_attrs['form'];
            unset($group_attrs['form']);
        }

        $group_attrs['class'] = $this->classes(
            array('flz-ui-radio-group'),
            isset($group_attrs['class']) ? (string) $group_attrs['class'] : ''
        );

        if (!empty($described_by)) {
            $group_attrs['aria-describedby'] = implode(' ', $described_by);
        }

        $field_html = '<fieldset' . $this->attributes($group_attrs) . '>';

        if ('' !== $label) {
            $field_html .= '<legend class="flz-ui-label">' . esc_html($label) . $this->required_marker($args) . '</legend>';
        }

        foreach ($options as $option_value => $option_label) {
            $option_id = $id . '-' . $this->safe_token((string) $option_value);
            $attrs = array_merge($radio_attrs, array(
                'type'  => 'radio',
                'name'  => $name,
                'id'    => $option_id,
                'value' => (string) $option_value,
                'class' => 'flz-ui-radio__input',
            ));

            if ($value === (string) $option_value) {
                $attrs['checked'] = true;
            }

            if (!empty($args['required'])) {
                $attrs['required'] = true;
            }

            $field_html .= '<label class="flz-ui-radio" for="' . esc_attr($option_id) . '">';
            $field_html .= '<input' . $this->attributes($attrs) . ' />';
            $field_html .= '<span class="flz-ui-radio__label">' . esc_html((string) $option_label) . '</span>';
            $field_html .= '</label>';
        }

        $field_html .= '</fieldset>';
        $field_html .= $this->description_and_errors($id, $args, $errors);

        return $this->wrap_field('radio', $field_html, $errors, $args);
    }
}
