(function ($) {
  'use strict';

  $(document).on('click', '[data-tg-ags-media-button]', function (event) {
    event.preventDefault();

    var $field = $(this).closest('.tg-ags-image-field');
    var $input = $field.find('[data-tg-ags-image-input]');
    var $preview = $field.find('[data-tg-ags-image-preview]');

    var frame = wp.media({
      title: 'Vorschaubild auswählen',
      button: { text: 'Bild verwenden' },
      multiple: false,
      library: { type: 'image' }
    });

    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();
      var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
      $input.val(url).trigger('change');
      $preview.attr('src', url);
    });

    frame.open();
  });

  $(document).on('click', '[data-tg-ags-clear-image]', function (event) {
    event.preventDefault();
    var $field = $(this).closest('.tg-ags-image-field');
    $field.find('[data-tg-ags-image-input]').val('').trigger('change');
  });

  $(document).on('change', '[data-tg-ags-image-input]', function () {
    var value = $(this).val();
    var $preview = $(this).closest('.tg-ags-image-field').find('[data-tg-ags-image-preview]');
    if (value) {
      $preview.attr('src', value);
    }
  });
}(jQuery));
