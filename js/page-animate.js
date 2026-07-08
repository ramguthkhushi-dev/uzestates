(function () {
  'use strict';

  function revealAll() {
    document.querySelectorAll('[data-reveal]').forEach(function (el) {
      el.classList.add('is-revealed');
    });
  }

  function initStaggerGroups() {
    document.querySelectorAll('[data-reveal-group], [data-reveal-stagger]').forEach(function (group) {
      var children = group.hasAttribute('data-reveal-stagger')
        ? group.querySelectorAll('[data-reveal]')
        : group.querySelectorAll(':scope > [data-reveal]');

      children.forEach(function (child, index) {
        if (!child.dataset.revealDelay) {
          child.style.setProperty('--reveal-delay', String(index));
        }
      });
    });
  }

  function applyManualDelays() {
    document.querySelectorAll('[data-reveal][data-reveal-delay]').forEach(function (el) {
      var delay = parseInt(el.dataset.revealDelay, 10);
      if (!Number.isNaN(delay)) {
        el.style.setProperty('--reveal-delay', String(delay));
      }
    });
  }

  function isInViewport(el) {
    var rect = el.getBoundingClientRect();
    var viewHeight = window.innerHeight || document.documentElement.clientHeight;

    return rect.top < viewHeight * 0.94 && rect.bottom > 0;
  }

  function init() {
    initStaggerGroups();
    applyManualDelays();

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      revealAll();
      return;
    }

    var items = document.querySelectorAll('[data-reveal]');
    if (!items.length) {
      return;
    }

    if (!('IntersectionObserver' in window)) {
      revealAll();
      return;
    }

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) {
            return;
          }
          entry.target.classList.add('is-revealed');
          observer.unobserve(entry.target);
        });
      },
      {
        threshold: 0.08,
        rootMargin: '0px 0px -4% 0px',
      }
    );

    items.forEach(function (el) {
      if (isInViewport(el)) {
        window.requestAnimationFrame(function () {
          el.classList.add('is-revealed');
        });
        return;
      }

      observer.observe(el);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
