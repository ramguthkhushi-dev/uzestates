(function () {
  'use strict';

  var featureContainer = document.getElementById('feature-rows');
  var lotContainer = document.getElementById('lot-rows');
  var mediaContainer = document.getElementById('media-rows');
  var featureTemplate = document.getElementById('feature-row-template');
  var lotTemplate = document.getElementById('lot-row-template');
  var mediaTemplate = document.getElementById('media-row-template');
  var addFeatureBtn = document.getElementById('add-feature-row');
  var addLotBtn = document.getElementById('add-lot-row');
  var addVillaLotBtn = document.getElementById('add-villa-lot-row');
  var villaLotCards = document.getElementById('villa-lot-cards');
  var villaLotTemplate = document.getElementById('villa-lot-card-template');
  var simpleLotsPanel = document.getElementById('simpleLotsPanel');
  var villaLotsPanel = document.getElementById('villaLotsPanel');
  var propertyTypeSelect = document.querySelector('select[name="property_type"]');
  var propertyTypeHint = document.getElementById('propertyTypeHint');
  var propertyForm = document.querySelector('.property-form');
  var typeHints = window.propertyAdminTypeHints || {};
  var defaultTypeHint = 'Choose a type — the form will show the sections you need.';
  var mediaIndex = 0;

  var mediaLabels = {
    image: 'Photo',
    video: 'Video',
    sitemap: 'Sitemap'
  };

  var mediaCategories = {
    image: 'actual',
    video: 'video',
    sitemap: 'sitemap'
  };

  function isVillaType() {
    if (!propertyTypeSelect) {
      return false;
    }

    return propertyTypeSelect.value.toLowerCase().indexOf('villa') !== -1;
  }

  function setFieldsDisabled(container, disabled) {
    if (!container) {
      return;
    }

    container.querySelectorAll('input, select, textarea, button').forEach(function (field) {
      if (field.type === 'hidden') {
        field.disabled = disabled;
        return;
      }

      if (field.classList.contains('row-remove') || field.id === 'add-lot-row' || field.id === 'add-villa-lot-row') {
        field.disabled = disabled;
        return;
      }

      field.disabled = disabled;
    });
  }

  function toggleEmptyHint(container, hintId) {
    var hint = document.getElementById(hintId);
    if (!hint || !container) {
      return;
    }

    hint.hidden = container.children.length > 0;
  }

  function reindexOrders(container) {
    if (!container) {
      return;
    }

    container.querySelectorAll('[data-order-field]').forEach(function (input, index) {
      input.value = String(index);
    });
  }

  function renumberVillaCards() {
    if (!villaLotCards) {
      return;
    }

    villaLotCards.querySelectorAll('[data-villa-lot-card]').forEach(function (card, index) {
      var title = card.querySelector('.villa-lot-card-head h3');
      if (title) {
        title.textContent = 'Unit ' + String(index + 1);
      }

      var upload = card.querySelector('[data-lot-upload-input], input[type="file"][name^="lot_upload"]');
      if (upload) {
        upload.name = 'lot_upload[' + String(index) + '][]';
      }
    });

    reindexOrders(villaLotCards);
    toggleEmptyHint(villaLotCards, 'villaLotsEmpty');
  }

  function usesSimpleLots() {
    if (!propertyTypeSelect) {
      return false;
    }

    var value = propertyTypeSelect.value.toLowerCase();
    return value.indexOf('plot') !== -1 || value === 'land';
  }

  function updateTypeHint() {
    if (!propertyTypeHint || !propertyTypeSelect) {
      return;
    }

    var selected = propertyTypeSelect.value;
    propertyTypeHint.textContent = typeHints[selected] || defaultTypeHint;
  }

  function configureMediaRow(row, mediaType) {
    var type = mediaType || 'image';
    var typeInput = row.querySelector('[data-media-type]');
    var categoryInput = row.querySelector('[data-media-category]');
    var badge = row.querySelector('[data-media-badge]');
    var mainField = row.querySelector('.media-row-main');

    if (typeInput) {
      typeInput.value = type;
    }

    if (categoryInput) {
      categoryInput.value = mediaCategories[type] || 'actual';
    }

    if (badge) {
      badge.textContent = mediaLabels[type] || 'File';
    }

    if (mainField) {
      mainField.hidden = type !== 'image';
      if (type !== 'image') {
        var checkbox = mainField.querySelector('input');
        if (checkbox) {
          checkbox.checked = false;
        }
      }
    }

    var fileInput = row.querySelector('input[type="file"]');
    if (fileInput) {
      if (type === 'video') {
        fileInput.accept = 'video/mp4,video/webm';
      } else if (type === 'sitemap') {
        fileInput.accept = 'image/jpeg,image/png,image/webp,application/pdf';
      } else {
        fileInput.accept = 'image/jpeg,image/png,image/webp';
      }
    }
  }

  function syncLotPanels() {
    var villaMode = isVillaType();
    var simpleMode = usesSimpleLots();

    if (villaLotsPanel) {
      villaLotsPanel.hidden = !villaMode;
    }

    if (simpleLotsPanel) {
      simpleLotsPanel.hidden = !simpleMode || villaMode;
    }

    setFieldsDisabled(villaLotsPanel, !villaMode);
    setFieldsDisabled(simpleLotsPanel, !simpleMode || villaMode);

    if (villaMode && villaLotCards && villaLotCards.children.length === 0) {
      addVillaLotRow();
    }

    updateTypeHint();
  }

  function bindRemoveButtons(scope) {
    (scope || document).querySelectorAll('.row-remove').forEach(function (btn) {
      if (btn.dataset.bound) {
        return;
      }
      btn.dataset.bound = '1';
      btn.addEventListener('click', function () {
        var row = btn.closest('.dynamic-row, .villa-lot-card');
        if (!row) {
          return;
        }
        var parent = row.parentElement;
        row.remove();
        if (!parent) {
          return;
        }
        if (parent.id === 'feature-rows') {
          reindexOrders(parent);
          toggleEmptyHint(parent, 'featureRowsEmpty');
        }
        if (parent.id === 'lot-rows') {
          reindexOrders(parent);
          toggleEmptyHint(parent, 'lotRowsEmpty');
        }
        if (parent.id === 'villa-lot-cards') {
          renumberVillaCards();
        }
        if (parent.id === 'media-rows') {
          reindexOrders(parent);
          toggleEmptyHint(parent, 'mediaRowsEmpty');
        }
      });
    });
  }

  function openFold(element) {
    if (!element) {
      return;
    }
    element.setAttribute('open', '');
    if (typeof element.scrollIntoView === 'function') {
      element.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  function addFeatureRow() {
    if (!featureContainer || !featureTemplate) {
      return;
    }
    var clone = featureTemplate.content.cloneNode(true);
    featureContainer.appendChild(clone);
    bindRemoveButtons(featureContainer.lastElementChild);
    reindexOrders(featureContainer);
    toggleEmptyHint(featureContainer, 'featureRowsEmpty');
    openFold(featureContainer.closest('details'));
  }

  function addLotRow() {
    if (!lotContainer || !lotTemplate) {
      return;
    }
    var clone = lotTemplate.content.cloneNode(true);
    lotContainer.appendChild(clone);
    bindRemoveButtons(lotContainer.lastElementChild);
    reindexOrders(lotContainer);
    toggleEmptyHint(lotContainer, 'lotRowsEmpty');
    openFold(simpleLotsPanel);
  }

  function addVillaLotRow() {
    if (!villaLotCards || !villaLotTemplate) {
      return;
    }
    var clone = villaLotTemplate.content.cloneNode(true);
    villaLotCards.appendChild(clone);
    renumberVillaCards();
    bindRemoveButtons(villaLotCards.lastElementChild);
    openFold(villaLotsPanel);
  }

  function addMediaRow(presetType) {
    if (!mediaContainer || !mediaTemplate) {
      return;
    }
    var clone = mediaTemplate.content.cloneNode(true);
    var checkbox = clone.querySelector('.new-main-checkbox');
    if (checkbox) {
      checkbox.value = String(mediaIndex);
    }
    mediaContainer.appendChild(clone);
    var row = mediaContainer.lastElementChild;
    configureMediaRow(row, presetType || 'image');
    bindRemoveButtons(row);
    reindexOrders(mediaContainer);
    toggleEmptyHint(mediaContainer, 'mediaRowsEmpty');
    openFold(mediaContainer.closest('details'));
    mediaIndex += 1;
  }

  function prepareFormSubmit() {
    reindexOrders(featureContainer);
    reindexOrders(lotContainer);
    reindexOrders(mediaContainer);
    reindexOrders(document.querySelector('.existing-media--compact'));
    renumberVillaCards();
  }

  if (addFeatureBtn) {
    addFeatureBtn.addEventListener('click', addFeatureRow);
  }

  if (addLotBtn) {
    addLotBtn.addEventListener('click', addLotRow);
  }

  if (addVillaLotBtn) {
    addVillaLotBtn.addEventListener('click', addVillaLotRow);
  }

  document.querySelectorAll('[data-add-media]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      addMediaRow(btn.getAttribute('data-add-media') || 'image');
    });
  });

  if (propertyTypeSelect) {
    propertyTypeSelect.addEventListener('change', syncLotPanels);
  }

  if (propertyForm) {
    propertyForm.addEventListener('submit', prepareFormSubmit);
  }

  bindRemoveButtons(document);
  renumberVillaCards();
  syncLotPanels();
  toggleEmptyHint(featureContainer, 'featureRowsEmpty');
  toggleEmptyHint(lotContainer, 'lotRowsEmpty');
  toggleEmptyHint(mediaContainer, 'mediaRowsEmpty');
})();
