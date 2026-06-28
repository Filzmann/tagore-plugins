<?php
/**
 * Gemeinsame Gutenberg-Blocks für bestehende Shortcodes.
 *
 * Fachplugins behalten ihre Shortcodes als stabile Rendering-Grenze. Dieser
 * Helper stellt nur den Gutenberg-Einstieg bereit: Block im Editor auswählen,
 * Frontend-Ausgabe weiter zentral über den vorhandenen Shortcode.
 */

defined('ABSPATH') || exit;

/**
 * Registriert einen dynamischen Gutenberg-Block, der einen Shortcode rendert.
 *
 * @param array<string,mixed> $args Blockargumente.
 */
function flz_ui_register_shortcode_block(array $args): void
{
    $name = isset($args['name']) ? (string) $args['name'] : '';
    $shortcode = isset($args['shortcode']) ? (string) $args['shortcode'] : '';

    if (!preg_match('/^[a-z0-9-]+\/[a-z0-9-]+$/', $name)) {
        throw new InvalidArgumentException('flz_ui shortcode block benötigt einen gültigen Blocknamen wie flz/beispiel.');
    }

    if (!preg_match('/^[A-Za-z0-9_-]+$/', $shortcode)) {
        throw new InvalidArgumentException('flz_ui shortcode block benötigt einen gültigen Shortcode-Namen.');
    }

    $config = array(
        'name'        => $name,
        'shortcode'   => $shortcode,
        'title'       => isset($args['title']) ? (string) $args['title'] : $name,
        'description' => isset($args['description']) ? (string) $args['description'] : '',
        'category'    => isset($args['category']) ? (string) $args['category'] : 'flz-tagore',
        'icon'        => isset($args['icon']) ? (string) $args['icon'] : 'shortcode',
        'keywords'    => isset($args['keywords']) && is_array($args['keywords']) ? array_values($args['keywords']) : array('flz'),
        'attributes'  => isset($args['attributes']) && is_array($args['attributes']) ? $args['attributes'] : array(),
        'fields'      => isset($args['fields']) && is_array($args['fields']) ? $args['fields'] : array(),
    );

    if (did_action('init')) {
        flz_ui_register_shortcode_block_now($config);
        return;
    }

    add_action(
        'init',
        static function () use ($config): void {
            flz_ui_register_shortcode_block_now($config);
        }
    );
}

/**
 * Fügt die gemeinsame Tagore-Kategorie im Block-Inserter hinzu.
 *
 * @param array<int,array<string,string>> $categories Bisherige Block-Kategorien.
 *
 * @return array<int,array<string,string>>
 */
function flz_ui_components_register_block_category(array $categories): array
{
    foreach ($categories as $category) {
        if (isset($category['slug']) && 'flz-tagore' === $category['slug']) {
            return $categories;
        }
    }

    $categories[] = array(
        'slug'  => 'flz-tagore',
        'title' => __('Tagore / FLZ', 'flz-ui-components'),
        'icon'  => null,
    );

    return $categories;
}

/**
 * Registriert den Block bei WordPress.
 *
 * @param array<string,mixed> $config Normalisierte Block-Konfiguration.
 */
function flz_ui_register_shortcode_block_now(array $config): void
{
    if (!function_exists('register_block_type')) {
        return;
    }

    if (
        class_exists('WP_Block_Type_Registry')
        && WP_Block_Type_Registry::get_instance()->is_registered($config['name'])
    ) {
        return;
    }

    flz_ui_register_shortcode_block_editor_script();
    flz_ui_add_shortcode_block_editor_config($config);

    register_block_type(
        $config['name'],
        array(
            'api_version'     => 2,
            'editor_script'   => 'flz-ui-shortcode-blocks',
            'attributes'      => flz_ui_shortcode_block_attribute_schema($config['attributes']),
            'render_callback' => static function (array $attributes = array()) use ($config): string {
                return flz_ui_render_shortcode_block($config, $attributes);
            },
        )
    );
}

/**
 * Registriert das gemeinsame Editor-Script für Shortcode-Blocks.
 */
function flz_ui_register_shortcode_block_editor_script(): void
{
    if (wp_script_is('flz-ui-shortcode-blocks', 'registered')) {
        return;
    }

    wp_register_script(
        'flz-ui-shortcode-blocks',
        FLZ_UI_COMPONENTS_URL . 'assets/js/flz-ui-shortcode-blocks.js',
        array('wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n'),
        FLZ_UI_COMPONENTS_VERSION,
        true
    );
}

/**
 * Übergibt eine Block-Konfiguration an das gemeinsame Editor-Script.
 *
 * @param array<string,mixed> $config Normalisierte Block-Konfiguration.
 */
function flz_ui_add_shortcode_block_editor_config(array $config): void
{
    $payload = array(
        'name'        => $config['name'],
        'shortcode'   => $config['shortcode'],
        'title'       => $config['title'],
        'description' => $config['description'],
        'category'    => $config['category'],
        'icon'        => $config['icon'],
        'keywords'    => $config['keywords'],
        'attributes'  => flz_ui_shortcode_block_attribute_schema($config['attributes']),
        'fields'      => flz_ui_shortcode_block_editor_fields($config['attributes'], $config['fields']),
    );

    wp_add_inline_script(
        'flz-ui-shortcode-blocks',
        'window.flzUiShortcodeBlocks = window.flzUiShortcodeBlocks || []; window.flzUiShortcodeBlocks.push(' . wp_json_encode($payload) . ');',
        'before'
    );
}

/**
 * Rendert einen registrierten Shortcode-Block im Frontend.
 *
 * @param array<string,mixed> $config     Normalisierte Block-Konfiguration.
 * @param array<string,mixed> $attributes Block-Attribute.
 */
function flz_ui_render_shortcode_block(array $config, array $attributes): string
{
    $shortcode = '[' . $config['shortcode'];
    $definitions = isset($config['attributes']) && is_array($config['attributes']) ? $config['attributes'] : array();

    foreach ($definitions as $name => $definition) {
        if (!array_key_exists($name, $attributes)) {
            continue;
        }

        $value = $attributes[$name];
        if (is_array($value) || is_object($value) || null === $value || '' === $value) {
            continue;
        }

        $shortcode .= ' ' . sanitize_key((string) $name) . '="' . esc_attr(sanitize_text_field((string) $value)) . '"';
    }

    $shortcode .= ']';

    return do_shortcode($shortcode);
}

/**
 * Reduziert Felddefinitionen auf die für WordPress gültige Attribut-Schemaform.
 *
 * @param array<string,array<string,mixed>> $attributes Attributdefinitionen.
 *
 * @return array<string,array<string,mixed>>
 */
function flz_ui_shortcode_block_attribute_schema(array $attributes): array
{
    $allowed_keys = array('type', 'default', 'enum', 'source', 'selector', 'attribute', 'query', 'items');
    $schema = array();

    foreach ($attributes as $name => $definition) {
        if (!is_array($definition)) {
            continue;
        }

        $schema[(string) $name] = array_intersect_key($definition, array_flip($allowed_keys));
        if (!isset($schema[(string) $name]['type'])) {
            $schema[(string) $name]['type'] = 'string';
        }
    }

    return $schema;
}

/**
 * Baut generische Inspector-Felder für Shortcode-Block-Attribute.
 *
 * @param array<string,array<string,mixed>> $attributes Attributdefinitionen.
 * @param array<string,array<string,mixed>> $fields     Optionale Editor-Felddefinitionen.
 *
 * @return array<int,array<string,mixed>>
 */
function flz_ui_shortcode_block_editor_fields(array $attributes, array $fields): array
{
    $result = array();

    foreach ($attributes as $name => $definition) {
        if (!is_array($definition)) {
            continue;
        }

        $field = isset($fields[$name]) && is_array($fields[$name]) ? $fields[$name] : array();
        $type = isset($definition['type']) ? (string) $definition['type'] : 'string';

        $result[] = array(
            'name'        => (string) $name,
            'label'       => isset($field['label']) ? (string) $field['label'] : ucwords(str_replace('_', ' ', (string) $name)),
            'description' => isset($field['description']) ? (string) $field['description'] : '',
            'control'     => isset($field['control']) ? (string) $field['control'] : ('boolean' === $type ? 'toggle' : 'text'),
        );
    }

    return $result;
}
