(function () {
  'use strict';

  function requireText(value, message) {
    if (!value || String(value).trim() === '') {
      throw new Error(message);
    }

    return String(value);
  }

  function createIcon(name, altText) {
    var icon = document.createElement('span');

    requireText(altText, 'flzUi Icons benötigen funktionalen altText.');

    icon.className = 'flz-ui-icon flz-ui-icon--' + String(name || 'item').replace(/[^a-z0-9_-]/gi, '-').toLowerCase();
    icon.setAttribute('role', 'img');
    icon.setAttribute('aria-label', altText);
    icon.setAttribute('title', altText);

    if (name === 'plus') {
      icon.textContent = '+';
    } else if (name === 'save') {
      icon.textContent = '✓';
    } else if (name === 'delete') {
      icon.textContent = '×';
    } else if (name === 'edit') {
      icon.textContent = '✎';
    } else if (name === 'filter') {
      icon.textContent = '⌄';
    } else if (name === 'upload') {
      icon.textContent = '↑';
    } else if (name === 'export') {
      icon.textContent = '↓';
    } else if (name === 'reset') {
      icon.textContent = '↻';
    } else if (name === 'view') {
      icon.textContent = '◉';
    } else if (name === 'image') {
      icon.textContent = '▧';
    } else if (name === 'close') {
      icon.textContent = '×';
    } else {
      icon.textContent = '•';
    }

    return icon;
  }

  function createButton(options) {
    var settings = options || {};
    var label = settings.label || settings.altText;
    var iconAltText = settings.altText;
    var element = settings.href ? document.createElement('a') : document.createElement('button');
    var variant = settings.variant || 'secondary';
    var labelElement = document.createElement('span');

    label = requireText(label, 'flzUi Buttons benötigen label oder altText.');
    element.className = 'flz-ui-button flz-ui-button--' + String(variant).replace(/[^a-z0-9_-]/gi, '-').toLowerCase();

    if (settings.href) {
      element.href = settings.href;
    } else {
      element.type = settings.type || 'button';
    }

    if (settings.icon) {
      element.className += ' flz-ui-button--has-icon';
      iconAltText = requireText(iconAltText, 'flzUi Icon-Buttons benötigen funktionalen altText.');
      element.appendChild(createIcon(settings.icon, iconAltText));
    }

    if (settings.iconOnly) {
      element.className += ' flz-ui-button--icon-only';
      element.setAttribute('aria-label', iconAltText || label);
      element.setAttribute('title', settings.title || iconAltText || label);
      labelElement.className = 'flz-ui-button__label flz-ui-sr-only';
    } else {
      labelElement.className = 'flz-ui-button__label';
    }

    labelElement.textContent = label;
    element.appendChild(labelElement);

    return element;
  }

  function validateForm(form) {
    if (!form || typeof form.checkValidity !== 'function') {
      return true;
    }

    if (typeof form.reportValidity === 'function') {
      return form.reportValidity();
    }

    return form.checkValidity();
  }

  function submitFilterForm(form) {
    if (!form || form.getAttribute('data-flz-ui-submitting') === 'true') {
      return;
    }

    if (!validateForm(form)) {
      return;
    }

    form.setAttribute('data-flz-ui-submitting', 'true');

    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
    } else {
      form.submit();
    }
  }

  function scheduleFilterSubmit(form, delay) {
    if (!form) {
      return;
    }

    window.clearTimeout(form._flzUiFilterTimer);
    form._flzUiFilterTimer = window.setTimeout(function () {
      submitFilterForm(form);
    }, delay);
  }

  function floatingPanelFor(element) {
    return element && element.closest && element.closest('[data-flz-ui-floating-panel]');
  }

  function focusablePanelElements(panel) {
    if (!panel) {
      return [];
    }

    return Array.prototype.slice.call(panel.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'));
  }

  function setFloatingPanel(panel, open) {
    var trigger;
    var drawer;
    var focusTarget;

    if (!panel) {
      return;
    }

    trigger = panel.querySelector('[data-flz-ui-floating-panel-open]');
    drawer = panel.querySelector('.flz-ui-floating-panel__drawer');
    panel.classList.toggle('is-open', Boolean(open));

    if (trigger) {
      trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    document.body.classList.toggle('flz-ui-floating-panel-open', Boolean(document.querySelector('.flz-ui-floating-panel.is-open')));

    if (open) {
      focusTarget = drawer && (drawer.querySelector('.flz-ui-notice--error, .notice-error') || drawer.querySelector('input:not([type="hidden"]), select, textarea, button, a[href]'));
      if (!focusTarget) {
        focusTarget = drawer;
      }
    } else {
      focusTarget = trigger;
    }

    if (focusTarget && typeof focusTarget.focus === 'function') {
      window.setTimeout(function () {
        focusTarget.focus({preventScroll: false});
      }, 0);
    }
  }

  function closeOpenFloatingPanels() {
    document.querySelectorAll('.flz-ui-floating-panel.is-open').forEach(function (panel) {
      setFloatingPanel(panel, false);
    });
  }

  function updateChoiceCardState(scope) {
    (scope || document).querySelectorAll('.flz-ui-choice-card').forEach(function (card) {
      var input = card.querySelector('input[type="radio"], input[type="checkbox"]');
      card.classList.toggle('is-checked', Boolean(input && input.checked));
    });
  }

  function detailsFor(row) {
    if (!row || !row.id) {
      return null;
    }

    return document.querySelector('[data-flz-ui-editable-details-for="' + row.id + '"]');
  }

  function firstEditableField(row) {
    if (!row) {
      return null;
    }

    return row.querySelector('.flz-ui-editable-row__edit input:not([type="hidden"]), .flz-ui-editable-row__edit select, .flz-ui-editable-row__edit textarea');
  }

  function setEditableRowMode(row, isEditing) {
    var details = detailsFor(row);

    if (!row) {
      return;
    }

    row.classList.toggle('is-editing', Boolean(isEditing));

    if (details) {
      details.hidden = Boolean(row.hidden);
    }

    if (isEditing) {
      var field = firstEditableField(row) || (details && details.querySelector('input:not([type="hidden"]), select, textarea'));

      if (field && typeof field.focus === 'function') {
        field.focus();
      }
    }
  }

  function resetForms(row) {
    var forms = [];
    var details = detailsFor(row);

    if (!row) {
      return;
    }

    forms = Array.prototype.slice.call(row.querySelectorAll('form'));

    if (details) {
      forms = forms.concat(Array.prototype.slice.call(details.querySelectorAll('form')));
    }

    forms.forEach(function (form) {
      if (typeof form.reset === 'function') {
        form.reset();
      }
    });
  }

  function showNewRow(rowId) {
    var row = document.getElementById(rowId);
    var details;

    if (!row) {
      row = document.querySelector('[data-flz-ui-editable-row="' + rowId + '"]');
    }

    if (!row) {
      return;
    }

    details = detailsFor(row);
    row.hidden = false;

    if (details) {
      details.hidden = false;
    }

    setEditableRowMode(row, true);
  }

  function enableControls(element) {
    Array.prototype.forEach.call(element.querySelectorAll('[disabled]'), function (control) {
      control.disabled = false;
    });
  }

  function replaceIndex(element, index) {
    Array.prototype.forEach.call(element.querySelectorAll('[name], [id], [for]'), function (node) {
      ['name', 'id', 'for'].forEach(function (attribute) {
        var value = node.getAttribute(attribute);

        if (value) {
          node.setAttribute(attribute, value.replace(/__index__/g, String(index)));
        }
      });
    });
  }

  function addMatrixRow(matrixId) {
    var body = document.querySelector('[data-flz-ui-field-matrix="' + matrixId + '"]');
    var template;
    var row;
    var nextIndex;
    var firstField;

    if (!body) {
      return;
    }

    template = body.querySelector('[data-flz-ui-field-matrix-template]');

    if (!template) {
      return;
    }

    nextIndex = parseInt(body.getAttribute('data-flz-ui-next-index') || '0', 10);
    row = template.cloneNode(true);
    row.removeAttribute('data-flz-ui-field-matrix-template');
    row.hidden = false;
    replaceIndex(row, nextIndex);
    enableControls(row);
    body.setAttribute('data-flz-ui-next-index', String(nextIndex + 1));
    body.insertBefore(row, template);

    firstField = row.querySelector('input:not([type="hidden"]), select, textarea');
    if (firstField && typeof firstField.focus === 'function') {
      firstField.focus();
    }
  }

  document.addEventListener('invalid', function (event) {
    var field = event.target.closest && event.target.closest('.flz-ui-field');

    if (field) {
      field.classList.add('flz-ui-field--has-error');
    }
  }, true);

  document.addEventListener('input', function (event) {
    var target = event.target;
    var field = target.closest && target.closest('.flz-ui-field');
    var filterForm = target.closest && target.closest('.flz-ui-table-filter-form');

    if (field && typeof target.checkValidity === 'function' && target.checkValidity()) {
      field.classList.remove('flz-ui-field--has-error');
      target.removeAttribute('aria-invalid');
    }

    if (filterForm && !event.isComposing && target.type !== 'hidden') {
      scheduleFilterSubmit(filterForm, 350);
    }
  });

  document.addEventListener('change', function (event) {
    var target = event.target;
    var filterForm = target.closest && target.closest('.flz-ui-table-filter-form');
    var choiceCard = target.closest && target.closest('.flz-ui-choice-card');

    if (!filterForm || target.type === 'hidden') {
      if (choiceCard) {
        updateChoiceCardState(document);
      }
      return;
    }

    if (target.tagName === 'SELECT' || target.type === 'checkbox' || target.type === 'radio') {
      scheduleFilterSubmit(filterForm, 0);
    }

    if (choiceCard) {
      updateChoiceCardState(document);
    }
  });

  document.addEventListener('click', function (event) {
    var target = event.target;
    var editButton = target.closest && target.closest('[data-flz-ui-edit-row]');
    var cancelButton = target.closest && target.closest('[data-flz-ui-cancel-edit-row]');
    var newButton = target.closest && target.closest('[data-flz-ui-show-new-row]');
    var matrixButton = target.closest && target.closest('[data-flz-ui-add-matrix-row]');
    var panelOpenButton = target.closest && target.closest('[data-flz-ui-floating-panel-open]');
    var panelCloseButton = target.closest && target.closest('[data-flz-ui-floating-panel-close]');
    var row;
    var details;
    var panel;

    if (panelOpenButton) {
      event.preventDefault();
      setFloatingPanel(floatingPanelFor(panelOpenButton), true);
      return;
    }

    if (panelCloseButton) {
      event.preventDefault();
      setFloatingPanel(floatingPanelFor(panelCloseButton), false);
      return;
    }

    if (matrixButton) {
      event.preventDefault();
      addMatrixRow(matrixButton.getAttribute('data-flz-ui-add-matrix-row'));
      return;
    }

    if (newButton) {
      event.preventDefault();
      showNewRow(newButton.getAttribute('data-flz-ui-show-new-row'));
      return;
    }

    if (editButton) {
      event.preventDefault();
      row = editButton.closest('[data-flz-ui-editable-row]');
      setEditableRowMode(row, true);
      return;
    }

    if (cancelButton) {
      event.preventDefault();
      row = cancelButton.closest('[data-flz-ui-editable-row]');

      if (!row) {
        return;
      }

      resetForms(row);

      if (row.hasAttribute('data-flz-ui-new-row')) {
        details = detailsFor(row);
        row.hidden = true;

        if (details) {
          details.hidden = true;
        }
      } else {
        setEditableRowMode(row, false);
      }
    }
  });

  document.addEventListener('keydown', function (event) {
    var panel;
    var focusable;
    var first;
    var last;

    if (event.key === 'Escape') {
      closeOpenFloatingPanels();
      return;
    }

    if (event.key !== 'Tab') {
      return;
    }

    panel = document.querySelector('.flz-ui-floating-panel.is-open');
    if (!panel || !panel.contains(document.activeElement)) {
      return;
    }

    focusable = focusablePanelElements(panel);
    if (focusable.length === 0) {
      return;
    }

    first = focusable[0];
    last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
    updateChoiceCardState(document);
    document.querySelectorAll('.flz-ui-floating-panel.is-open').forEach(function (panel) {
      var trigger = panel.querySelector('[data-flz-ui-floating-panel-open]');

      if (trigger) {
        trigger.setAttribute('aria-expanded', 'true');
      }
      document.body.classList.add('flz-ui-floating-panel-open');
    });
  });

  window.flzUi = window.flzUi || {};
  window.flzUi.createButton = createButton;
  window.flzUi.createIcon = createIcon;
  window.flzUi.validateForm = validateForm;
  window.flzUi.showNewEditableRow = showNewRow;
  window.flzUi.addMatrixRow = addMatrixRow;
  window.flzUi.updateChoiceCardState = updateChoiceCardState;
}());
