/**
 * Mega-menu hover preview — media library picker on Appearance → Menus (submenu rows).
 */
(function ($) {
  'use strict';

  function fieldFromTrigger($btn) {
    return $btn.closest('.culvers-mega-preview-field');
  }

  function previewUrl(attachment) {
    if (!attachment || typeof attachment !== 'object') {
      return '';
    }
    if (attachment.sizes && attachment.sizes.medium && attachment.sizes.medium.url) {
      return attachment.sizes.medium.url;
    }
    return attachment.url || '';
  }

  function setAttachment($field, attachment) {
    const id = attachment && attachment.id ? parseInt(String(attachment.id), 10) : 0;
    const url = id > 0 ? previewUrl(attachment) : '';
    const $input = $field.find('.culvers-mega-preview__input');
    const $img = $field.find('.culvers-mega-preview__preview');
    const $placeholder = $field.find('.culvers-mega-preview__placeholder');
    const $select = $field.find('.culvers-mega-preview__select');
    const $remove = $field.find('.culvers-mega-preview__remove');

    $input.val(id > 0 ? String(id) : '');

    if (url) {
      $img.attr('src', url).removeAttr('hidden');
      $placeholder.attr('hidden', 'hidden');
    } else {
      $img.attr('src', '').attr('hidden', 'hidden');
      $placeholder.removeAttr('hidden');
    }

    const label =
      id > 0 && culversMegaPreview?.i18n?.changeButton
        ? culversMegaPreview.i18n.changeButton
        : culversMegaPreview?.i18n?.selectButton || 'Select image';
    $select.text(label);
    $remove.toggle(id > 0);
  }

  function openFrame($field) {
    const currentId = parseInt($field.find('.culvers-mega-preview__input').val(), 10) || 0;
    const frame = wp.media({
      title: culversMegaPreview?.i18n?.select || 'Mega menu preview image',
      button: { text: culversMegaPreview?.i18n?.use || 'Use image' },
      library: { type: 'image' },
      multiple: false,
    });

    frame.on('open', function () {
      if (currentId <= 0) {
        return;
      }
      const selection = frame.state().get('selection');
      const attachment = wp.media.attachment(currentId);
      attachment.fetch();
      selection.reset([attachment]);
    });

    frame.on('select', function () {
      const attachment = frame.state().get('selection').first().toJSON();
      setAttachment($field, attachment);
    });

    frame.open();
  }

  $(document).on('click', '.culvers-mega-preview__select', function (event) {
    event.preventDefault();
    openFrame(fieldFromTrigger($(this)));
  });

  $(document).on('click', '.culvers-mega-preview__remove', function (event) {
    event.preventDefault();
    setAttachment(fieldFromTrigger($(this)), null);
  });
})(jQuery);
