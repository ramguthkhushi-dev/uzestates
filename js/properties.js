(function () {
  'use strict';

  var sortSelect = document.getElementById('propsSort');
  var sortForm = document.getElementById('propsSortForm');
  var grid = document.getElementById('propsGrid');
  var viewButtons = document.querySelectorAll('.props-view-btn');
  var storageKey = 'uz_properties_view';

  function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  if (sortSelect && sortForm) {
    sortSelect.addEventListener('change', function () {
      sortForm.submit();
    });
  }

  function setView(mode) {
    if (!grid) {
      return;
    }

    var isList = mode === 'list';
    grid.classList.add('is-switching');
    grid.classList.toggle('is-list', isList);

    viewButtons.forEach(function (btn) {
      var active = btn.getAttribute('data-view') === mode;
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    });

    window.setTimeout(function () {
      grid.classList.remove('is-switching');
    }, 320);

    try {
      localStorage.setItem(storageKey, mode);
    } catch (e) {
      /* ignore */
    }
  }

  if (grid && viewButtons.length) {
    var saved = 'grid';
    try {
      saved = localStorage.getItem(storageKey) || 'grid';
    } catch (e) {
      /* ignore */
    }

    if (saved === 'list') {
      setView('list');
    }

    viewButtons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        setView(btn.getAttribute('data-view') || 'grid');
      });
    });
  }

  function bindSwipe(element, onLeft, onRight) {
    var startX = 0;
    var startY = 0;
    var tracking = false;

    element.addEventListener('touchstart', function (event) {
      if (!event.changedTouches || !event.changedTouches[0]) {
        return;
      }
      tracking = true;
      startX = event.changedTouches[0].clientX;
      startY = event.changedTouches[0].clientY;
    }, { passive: true });

    element.addEventListener('touchend', function (event) {
      if (!tracking || !event.changedTouches || !event.changedTouches[0]) {
        return;
      }

      tracking = false;
      var deltaX = event.changedTouches[0].clientX - startX;
      var deltaY = event.changedTouches[0].clientY - startY;

      if (Math.abs(deltaX) < 40 || Math.abs(deltaX) < Math.abs(deltaY)) {
        return;
      }

      if (deltaX < 0) {
        onLeft();
      } else {
        onRight();
      }
    }, { passive: true });
  }

  function initCardGalleries(root) {
    var scope = root || document;
    scope.querySelectorAll('[data-props-gallery]').forEach(function (gallery) {
      if (gallery.dataset.galleryReady === '1') {
        return;
      }

      var slides = gallery.querySelectorAll('.props-card-slide');
      if (slides.length <= 1) {
        return;
      }

      gallery.dataset.galleryReady = '1';

      var shell = gallery.closest('.props-card-shell');
      var controls = shell ? shell.querySelector('[data-props-gallery-controls]') : gallery;
      var index = 0;
      var prev = controls ? controls.querySelector('.props-card-nav--prev') : null;
      var next = controls ? controls.querySelector('.props-card-nav--next') : null;
      var counter = controls ? controls.querySelector('[data-props-counter]') : null;

      function updateCounter() {
        if (counter) {
          counter.textContent = String(index + 1) + ' / ' + String(slides.length);
        }
      }

      function show(nextIndex) {
        index = (nextIndex + slides.length) % slides.length;
        slides.forEach(function (slide, slideIndex) {
          slide.classList.toggle('is-active', slideIndex === index);
        });
        updateCounter();
      }

      if (prev) {
        prev.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();
          show(index - 1);
        });
        prev.addEventListener('mousedown', function (event) {
          event.preventDefault();
        });
      }

      if (next) {
        next.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();
          show(index + 1);
        });
        next.addEventListener('mousedown', function (event) {
          event.preventDefault();
        });
      }

      gallery.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowLeft') {
          event.preventDefault();
          show(index - 1);
        } else if (event.key === 'ArrowRight') {
          event.preventDefault();
          show(index + 1);
        }
      });

      bindSwipe(gallery, function () {
        show(index + 1);
      }, function () {
        show(index - 1);
      });

      updateCounter();
    });
  }

  function initHeroParallax() {
    var hero = document.querySelector('[data-props-hero]');
    var bg = document.querySelector('[data-props-hero-bg]');
    if (!hero || !bg || prefersReducedMotion()) {
      return;
    }

    var ticking = false;

    function update() {
      ticking = false;
      var rect = hero.getBoundingClientRect();
      if (rect.bottom <= 0 || rect.top >= window.innerHeight) {
        return;
      }

      var progress = Math.min(Math.max((window.innerHeight - rect.top) / (window.innerHeight + rect.height), 0), 1);
      bg.style.transform = 'scale(1.08) translateY(' + String((progress - 0.5) * 24) + 'px)';
    }

    window.addEventListener('scroll', function () {
      if (!ticking) {
        ticking = true;
        window.requestAnimationFrame(update);
      }
    }, { passive: true });

    update();
  }

  function initTabIndicator() {
    var nav = document.querySelector('[data-props-tabs]');
    var indicator = document.querySelector('[data-props-tabs-indicator]');
    if (!nav || !indicator) {
      return;
    }

    var tabs = nav.querySelectorAll('.props-tab');

    function moveIndicator() {
      var active = nav.querySelector('.props-tab.is-active');
      if (!active) {
        indicator.style.opacity = '0';
        return;
      }

      indicator.style.opacity = '1';
      indicator.style.width = active.offsetWidth + 'px';
      indicator.style.transform = 'translateX(' + active.offsetLeft + 'px)';
    }

    tabs.forEach(function (tab) {
      tab.addEventListener('mouseenter', function () {
        indicator.style.width = tab.offsetWidth + 'px';
        indicator.style.transform = 'translateX(' + tab.offsetLeft + 'px)';
      });

      tab.addEventListener('mouseleave', moveIndicator);
    });

    window.addEventListener('resize', moveIndicator);
    moveIndicator();
  }

  function initFilterToggle() {
    var toggle = document.querySelector('[data-props-filter-toggle]');
    var panel = document.querySelector('[data-props-filter-panel]');
    if (!toggle || !panel) {
      return;
    }

    var label = toggle.querySelector('.props-filter-toggle-label');

    function setOpen(open) {
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      panel.classList.toggle('is-open', open);
      if (label) {
        label.textContent = open ? 'Hide filters' : 'Show filters';
      }
    }

    toggle.addEventListener('click', function () {
      setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });

    window.matchMedia('(min-width: 961px)').addEventListener('change', function (event) {
      if (event.matches) {
        panel.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
      }
    });

    if (window.matchMedia('(min-width: 961px)').matches) {
      setOpen(true);
    }
  }

  function initCountPulse() {
    var countEl = document.getElementById('propsCount');
    if (!countEl || prefersReducedMotion()) {
      return;
    }

    countEl.classList.add('is-pulsed');
    window.setTimeout(function () {
      countEl.classList.remove('is-pulsed');
    }, 700);
  }

  initCardGalleries(document);
  initHeroParallax();
  initTabIndicator();
  initFilterToggle();
  initCountPulse();
})();
