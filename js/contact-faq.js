(function () {
  'use strict';

  var accordion = document.querySelector('[data-faq-accordion]');
  if (!accordion) {
    return;
  }

  var items = accordion.querySelectorAll('.faq-item');

  function closeItem(item) {
    var button = item.querySelector('.faq-trigger');
    var panel = item.querySelector('.faq-panel');
    if (!button || !panel) {
      return;
    }

    item.classList.remove('is-open');
    button.setAttribute('aria-expanded', 'false');
    panel.style.maxHeight = '0';
    panel.hidden = true;
  }

  function openItem(item) {
    var button = item.querySelector('.faq-trigger');
    var panel = item.querySelector('.faq-panel');
    if (!button || !panel) {
      return;
    }

    item.classList.add('is-open');
    button.setAttribute('aria-expanded', 'true');
    panel.hidden = false;
    panel.style.maxHeight = panel.scrollHeight + 'px';
  }

  items.forEach(function (item) {
    var button = item.querySelector('.faq-trigger');
    if (!button) {
      return;
    }

    button.addEventListener('click', function () {
      var isOpen = item.classList.contains('is-open');
      items.forEach(closeItem);
      if (!isOpen) {
        openItem(item);
      }
    });
  });

  var initiallyOpen = accordion.querySelector('.faq-item.is-open');
  if (initiallyOpen) {
    openItem(initiallyOpen);
  } else if (items[0]) {
    openItem(items[0]);
  }

  window.addEventListener('resize', function () {
    var open = accordion.querySelector('.faq-item.is-open');
    if (open) {
      var panel = open.querySelector('.faq-panel');
      if (panel && !panel.hidden) {
        panel.style.maxHeight = panel.scrollHeight + 'px';
      }
    }
  });
})();
