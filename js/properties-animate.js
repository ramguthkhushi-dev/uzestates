(function () {
  'use strict';

  function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  function initPageReady() {
    document.body.classList.add('is-page-ready');

    var hero = document.querySelector('[data-props-hero]');
    if (hero) {
      hero.classList.add('is-hero-ready');
    }

    var finder = document.querySelector('.props-finder');
    if (finder) {
      finder.classList.add('is-finder-ready');
    }
  }

  function initGalleryThumbs() {
    document.querySelectorAll('.detail-gallery-thumbs').forEach(function (wrap) {
      wrap.querySelectorAll('.detail-gallery-thumb').forEach(function (thumb, index) {
        thumb.style.setProperty('--thumb-delay', String(index));
      });
    });
  }

  function init() {
    initGalleryThumbs();

    if (prefersReducedMotion()) {
      document.body.classList.add('is-page-ready');
      return;
    }

    window.requestAnimationFrame(function () {
      window.requestAnimationFrame(initPageReady);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
