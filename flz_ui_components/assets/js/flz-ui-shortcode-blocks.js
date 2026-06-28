(function (wp, window) {
  'use strict';

  if (!wp || !wp.blocks || !wp.element) {
    return;
  }

  var blocks = window.flzUiShortcodeBlocks || [];
  var createElement = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var InspectorControls = wp.blockEditor && wp.blockEditor.InspectorControls;
  var useBlockProps = wp.blockEditor && wp.blockEditor.useBlockProps;
  var PanelBody = wp.components && wp.components.PanelBody;
  var TextControl = wp.components && wp.components.TextControl;
  var ToggleControl = wp.components && wp.components.ToggleControl;

  function shortcodePreview(config, attributes) {
    var parts = ['[', config.shortcode || 'shortcode'];

    Object.keys(config.attributes || {}).forEach(function (name) {
      var value = attributes && attributes[name];

      if (value === undefined || value === null || value === '') {
        return;
      }

      parts.push(' ', name, '="', String(value), '"');
    });

    parts.push(']');
    return parts.join('');
  }

  function transformAttributes(config) {
    var result = {};

    Object.keys(config.attributes || {}).forEach(function (name) {
      var schema = config.attributes[name] || {};

      result[name] = {
        type: schema.type || 'string',
        shortcode: function (attrs) {
          var named = attrs && attrs.named ? attrs.named : {};
          var value = named[name];

          if (schema.type === 'boolean') {
            return value === true || value === '1' || value === 'true' || value === name;
          }

          return value === undefined || value === null ? '' : String(value);
        }
      };
    });

    return result;
  }

  function renderInspectorControls(config, props) {
    var fields = config.fields || [];

    if (!InspectorControls || !PanelBody || !fields.length) {
      return null;
    }

    return createElement(
      InspectorControls,
      {},
      createElement(
        PanelBody,
        {title: 'Shortcode-Einstellungen', initialOpen: true},
        fields.map(function (field) {
          var value = props.attributes ? props.attributes[field.name] : undefined;

          if (field.control === 'toggle' && ToggleControl) {
            return createElement(ToggleControl, {
              key: field.name,
              label: field.label || field.name,
              help: field.description || '',
              checked: Boolean(value),
              onChange: function (nextValue) {
                var update = {};
                update[field.name] = Boolean(nextValue);
                props.setAttributes(update);
              }
            });
          }

          if (!TextControl) {
            return null;
          }

          return createElement(TextControl, {
            key: field.name,
            label: field.label || field.name,
            help: field.description || '',
            value: value === undefined || value === null ? '' : String(value),
            onChange: function (nextValue) {
              var update = {};
              update[field.name] = nextValue;
              props.setAttributes(update);
            }
          });
        })
      )
    );
  }

  function renderPlaceholder(config, props, blockProps) {
    blockProps = blockProps || {};
    blockProps.className = [
      blockProps.className || '',
      'flz-ui-shortcode-block-placeholder'
    ].join(' ').trim();

    return createElement(
      'div',
      blockProps,
      createElement('strong', {}, config.title || config.name),
      config.description ? createElement('p', {}, config.description) : null,
      createElement('code', {}, shortcodePreview(config, props.attributes || {}))
    );
  }

  blocks.forEach(function (config) {
    if (!config || !config.name || !config.shortcode || wp.blocks.getBlockType(config.name)) {
      return;
    }

    wp.blocks.registerBlockType(config.name, {
      title: config.title || config.name,
      description: config.description || '',
      category: config.category || 'flz-tagore',
      icon: config.icon || 'shortcode',
      keywords: config.keywords || ['flz'],
      attributes: config.attributes || {},
      transforms: {
        from: [
          {
            type: 'shortcode',
            tag: config.shortcode,
            attributes: transformAttributes(config)
          }
        ]
      },
      supports: {
        html: false
      },
      edit: function (props) {
        var blockProps = useBlockProps ? useBlockProps() : {};

        return createElement(
          Fragment,
          {},
          renderInspectorControls(config, props),
          renderPlaceholder(config, props, blockProps)
        );
      },
      save: function () {
        return null;
      }
    });
  });
}(window.wp, window));
