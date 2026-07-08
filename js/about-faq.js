(function () {
  'use strict';

  var accordion = document.querySelector('[data-faq-accordion]');
  if (!accordion) {
    return;
  }

  var items = accordion.querySelectorAll('.faq-item');

  function closeItem(item) {
    var button = item.querySelector('.faq-question');
    var answer = item.querySelector('.faq-answer');
    if (!button || !answer) {
      return;
    }

    item.classList.remove('is-open');
    button.setAttribute('aria-expanded', 'false');
    answer.style.maxHeight = '0';
    answer.hidden = true;
  }

  function openItem(item) {
    var button = item.querySelector('.faq-question');
    var answer = item.querySelector('.faq-answer');
    if (!button || !answer) {
      return;
    }

    item.classList.add('is-open');
    button.setAttribute('aria-expanded', 'true');
    answer.hidden = false;
    answer.style.maxHeight = answer.scrollHeight + 'px';
  }

  items.forEach(function (item) {
    var button = item.querySelector('.faq-question');
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
  }

  window.addEventListener('resize', function () {
    var open = accordion.querySelector('.faq-item.is-open');
    if (open) {
      var answer = open.querySelector('.faq-answer');
      if (answer && !answer.hidden) {
        answer.style.maxHeight = answer.scrollHeight + 'px';
      }
    }
  });
})();
