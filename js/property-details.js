(function () {
  'use strict';

  function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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

  function initGallery(gallery) {
    var slides = gallery.querySelectorAll('.detail-gallery-slide');
    var thumbs = gallery.querySelectorAll('[data-detail-thumb]');
    var lightboxApi = window.__detailMediaLightbox || null;

    if (slides.length === 1 && lightboxApi) {
      slides[0].addEventListener('click', function () {
        lightboxApi.open(0, gallery);
      });
      return;
    }

    if (slides.length <= 1) {
      return;
    }

    var index = 0;
    var stage = gallery.querySelector('[data-detail-gallery-stage]');
    var prev = gallery.querySelector('.detail-gallery-nav--prev');
    var next = gallery.querySelector('.detail-gallery-nav--next');
    var counter = gallery.querySelector('[data-detail-gallery-counter]');
    var expandBtn = gallery.querySelector('[data-detail-gallery-expand]');

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
      thumbs.forEach(function (thumb, thumbIndex) {
        thumb.classList.toggle('is-active', thumbIndex === index);
      });
      updateCounter();
    }

    if (prev) {
      prev.addEventListener('click', function (event) {
        event.stopPropagation();
        show(index - 1);
      });
    }

    if (next) {
      next.addEventListener('click', function (event) {
        event.stopPropagation();
        show(index + 1);
      });
    }

    thumbs.forEach(function (thumb) {
      thumb.addEventListener('click', function () {
        var target = parseInt(thumb.getAttribute('data-detail-thumb') || '0', 10);
        show(target);
      });
    });

    if (stage) {
      stage.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowLeft') {
          event.preventDefault();
          show(index - 1);
        } else if (event.key === 'ArrowRight') {
          event.preventDefault();
          show(index + 1);
        } else if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          if (lightboxApi) {
            lightboxApi.open(index, gallery);
          }
        }
      });

      bindSwipe(stage, function () {
        show(index + 1);
      }, function () {
        show(index - 1);
      });

      stage.addEventListener('dblclick', function () {
        if (lightboxApi) {
          lightboxApi.open(index, gallery);
        }
      });
    }

    slides.forEach(function (slide, slideIndex) {
      slide.addEventListener('click', function () {
        if (lightboxApi) {
          lightboxApi.open(slideIndex, gallery);
        }
      });
    });

    if (expandBtn) {
      expandBtn.addEventListener('click', function (event) {
        event.stopPropagation();
        if (lightboxApi) {
          lightboxApi.open(index, gallery);
        }
      });
    }

    updateCounter();

    return {
      getIndex: function () {
        return index;
      },
      show: show,
    };
  }

  function getActiveGallery() {
    return document.querySelector('[data-detail-gallery]');
  }

  function initMediaLightbox() {
    var lightbox = document.querySelector('[data-detail-media-lightbox]');
    if (!lightbox) {
      return null;
    }

    var img = lightbox.querySelector('[data-detail-media-img]');
    var caption = lightbox.querySelector('[data-detail-media-caption]');
    var closeButtons = lightbox.querySelectorAll('[data-detail-media-close]');
    var prevBtn = lightbox.querySelector('[data-detail-media-prev]');
    var nextBtn = lightbox.querySelector('[data-detail-media-next]');
    var items = [];
    var index = 0;
    var lastFocus = null;
    var sourceGallery = null;

    function rebuildItems() {
      items = [];
      var gallery = sourceGallery || getActiveGallery();

      if (gallery) {
        gallery.querySelectorAll('.detail-gallery-slide').forEach(function (slide) {
          items.push({
            src: slide.getAttribute('src') || '',
            caption: slide.getAttribute('alt') || '',
          });
        });
      }

      document.querySelectorAll('[data-detail-lightbox-open]').forEach(function (btn) {
        var src = btn.getAttribute('data-detail-lightbox-src') || '';
        if (!src) {
          return;
        }

        var exists = items.some(function (item) {
          return item.src === src;
        });

        if (!exists) {
          items.push({
            src: src,
            caption: btn.getAttribute('data-detail-lightbox-caption') || '',
          });
        }
      });
    }

    function render() {
      var item = items[index];
      if (!item || !img) {
        return;
      }

      img.src = item.src;
      img.alt = item.caption || 'Property image';
      if (caption) {
        caption.textContent = item.caption || '';
        caption.hidden = item.caption === '';
      }

      var hasMany = items.length > 1;
      if (prevBtn) {
        prevBtn.hidden = !hasMany;
      }
      if (nextBtn) {
        nextBtn.hidden = !hasMany;
      }
    }

    function open(openIndex, gallery) {
      sourceGallery = gallery || getActiveGallery();
      rebuildItems();
      if (!items.length) {
        return;
      }

      index = openIndex;
      if (index < 0 || index >= items.length) {
        index = 0;
      }

      render();
      lastFocus = document.activeElement;
      lightbox.hidden = false;
      lightbox.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      lightbox.querySelector('.detail-media-lightbox-close').focus();
    }

    function close() {
      lightbox.hidden = true;
      lightbox.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      sourceGallery = null;
      if (lastFocus && typeof lastFocus.focus === 'function') {
        lastFocus.focus();
      }
    }

    function step(delta) {
      if (items.length <= 1) {
        return;
      }
      index = (index + delta + items.length) % items.length;
      render();
    }

    closeButtons.forEach(function (btn) {
      btn.addEventListener('click', close);
    });

    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        step(-1);
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        step(1);
      });
    }

    document.querySelectorAll('[data-detail-lightbox-open]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        rebuildItems();
        var src = btn.getAttribute('data-detail-lightbox-src') || '';
        var targetIndex = items.findIndex(function (item) {
          return item.src === src;
        });
        open(targetIndex >= 0 ? targetIndex : 0);
      });
    });

    document.addEventListener('keydown', function (event) {
      if (lightbox.hidden) {
        return;
      }

      if (event.key === 'Escape') {
        close();
      } else if (event.key === 'ArrowLeft') {
        step(-1);
      } else if (event.key === 'ArrowRight') {
        step(1);
      }
    });

    return { open: open, close: close };
  }

  function initLotSelection() {
    var lotItems = document.querySelectorAll('[data-lot-item]');
    var messageField = document.querySelector('.detail-form textarea[name="message"]');
    if (!lotItems.length || !messageField) {
      return;
    }

    var defaultMessage = messageField.value;

    lotItems.forEach(function (item) {
      var button = item.querySelector('[data-lot-select]');
      if (!button) {
        return;
      }

      button.addEventListener('click', function () {
        var isSelected = item.classList.contains('is-selected');

        lotItems.forEach(function (other) {
          other.classList.remove('is-selected');
          var otherBtn = other.querySelector('[data-lot-select]');
          if (otherBtn) {
            otherBtn.setAttribute('aria-pressed', 'false');
          }
        });

        if (isSelected) {
          messageField.value = defaultMessage;
          return;
        }

        item.classList.add('is-selected');
        button.setAttribute('aria-pressed', 'true');
        messageField.value = item.getAttribute('data-lot-message') || defaultMessage;
        messageField.focus();
      });
    });
  }

  function initVillaLotAccordion() {
    var root = document.querySelector('[data-villa-units]');
    if (!root) {
      return;
    }

    var items = root.querySelectorAll('[data-villa-lot-item]');
    var messageField = document.querySelector('.detail-form textarea[name="message"]');
    var propertyDefaultMessage = messageField ? messageField.value : '';

    function pauseClosedVideos() {
      root.querySelectorAll('.villa-lot:not(.is-open) video').forEach(function (video) {
        if (!video.paused) {
          video.pause();
        }
      });
    }

    function setOpen(index) {
      items.forEach(function (item) {
        var itemIndex = parseInt(item.getAttribute('data-villa-lot') || '0', 10);
        var isOpen = itemIndex === index;
        item.classList.toggle('is-open', isOpen);

        var body = item.querySelector('.villa-lot-body');
        var trigger = item.querySelector('[data-villa-lot-toggle]');
        if (body) {
          body.hidden = !isOpen;
        }
        if (trigger) {
          trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
      });

      pauseClosedVideos();

      if (messageField) {
        if (index >= 0) {
          var activeItem = root.querySelector('[data-villa-lot="' + String(index) + '"]');
          messageField.value = activeItem
            ? activeItem.getAttribute('data-lot-message') || propertyDefaultMessage
            : propertyDefaultMessage;
        } else {
          messageField.value = propertyDefaultMessage;
        }
      }

      window.dispatchEvent(new Event('resize'));

      var url = new URL(window.location.href);
      if (index >= 0) {
        url.searchParams.set('lot', String(index));
      } else {
        url.searchParams.delete('lot');
      }
      window.history.replaceState({}, '', url.toString());
    }

    items.forEach(function (item) {
      var trigger = item.querySelector('[data-villa-lot-toggle]');
      if (!trigger) {
        return;
      }

      trigger.addEventListener('click', function () {
        var index = parseInt(item.getAttribute('data-villa-lot') || '0', 10);
        var isCurrentlyOpen = item.classList.contains('is-open');
        setOpen(isCurrentlyOpen ? -1 : index);
      });
    });
  }

  function initAsideStickyState() {
    var asideInner = document.querySelector('[data-detail-aside-inner]');
    var intro = document.querySelector('.detail-intro');
    if (!asideInner || !intro || prefersReducedMotion()) {
      return;
    }

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          asideInner.classList.toggle('is-floating', !entry.isIntersecting);
        });
      },
      { threshold: 0, rootMargin: '-80px 0px 0px 0px' }
    );

    observer.observe(intro);
  }

  function initSmoothAnchors() {
    document.querySelectorAll('a[href^="#"]').forEach(function (link) {
      link.addEventListener('click', function (event) {
        var id = link.getAttribute('href').slice(1);
        if (!id) {
          return;
        }

        var target = document.getElementById(id);
        if (!target) {
          return;
        }

        event.preventDefault();
        target.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth', block: 'start' });
        if (typeof target.focus === 'function') {
          target.setAttribute('tabindex', '-1');
          target.focus({ preventScroll: true });
        }
      });
    });
  }

  function initPeekCarousel(root) {
    var viewport = root.querySelector('.detail-showcase-viewport');
    var track = root.querySelector('.detail-showcase-track');
    var slides = root.querySelectorAll('.detail-showcase-slide');
    if (!viewport || !track || slides.length <= 1) {
      return;
    }

    var index = 0;
    slides.forEach(function (slide, slideIndex) {
      if (slide.classList.contains('is-active')) {
        index = slideIndex;
      }
    });

    var prev = root.querySelector('.detail-showcase-nav--prev');
    var next = root.querySelector('.detail-showcase-nav--next');
    var isVideo = root.getAttribute('data-peek-type') === 'video';

    function pauseVideos() {
      if (!isVideo) {
        return;
      }

      slides.forEach(function (slide) {
        var video = slide.querySelector('video');
        if (video && !video.paused) {
          video.pause();
        }
      });
    }

    function updateClasses() {
      slides.forEach(function (slide, slideIndex) {
        slide.classList.toggle('is-active', slideIndex === index);
      });
    }

    function layout() {
      var slide = slides[index];
      if (!slide) {
        return;
      }

      var slideWidth = slide.offsetWidth;
      var slideLeft = slide.offsetLeft;
      var viewportWidth = viewport.clientWidth;
      var offset = slideLeft - (viewportWidth - slideWidth) / 2;

      track.style.transform = 'translate3d(' + (-offset) + 'px, 0, 0)';
      updateClasses();
    }

    function show(nextIndex) {
      if (slides.length <= 1) {
        return;
      }

      index = (nextIndex + slides.length) % slides.length;
      pauseVideos();
      layout();
    }

    if (prev) {
      prev.addEventListener('click', function () {
        show(index - 1);
      });
    }

    if (next) {
      next.addEventListener('click', function () {
        show(index + 1);
      });
    }

    bindSwipe(viewport, function () {
      show(index + 1);
    }, function () {
      show(index - 1);
    });

    var resizeTimer;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(layout, 120);
    });

    root.querySelectorAll('img').forEach(function (img) {
      if (!img.complete) {
        img.addEventListener('load', layout, { once: true });
      }
    });

    if (document.readyState === 'complete') {
      layout();
    } else {
      window.addEventListener('load', layout, { once: true });
    }

    requestAnimationFrame(layout);
  }

  function initPlansViewer(section) {
    var scrollEl = section.querySelector('[data-plans-scroll]');
    var cards = section.querySelectorAll('.detail-plans-card');
    var progressFill = section.querySelector('[data-plans-progress]');
    var indexLabel = section.querySelector('[data-plans-index]');
    var lightbox = document.querySelector('[data-plans-lightbox]');
    var lightboxImg = lightbox ? lightbox.querySelector('[data-plans-lightbox-img]') : null;
    var lightboxCaption = lightbox ? lightbox.querySelector('[data-plans-lightbox-caption]') : null;
    var openButtons = section.querySelectorAll('[data-plan-open]');
    var closeButtons = lightbox ? lightbox.querySelectorAll('[data-plans-close]') : [];
    var prevBtn = lightbox ? lightbox.querySelector('[data-plans-prev]') : null;
    var nextBtn = lightbox ? lightbox.querySelector('[data-plans-next]') : null;

    var planUrls = [];
    openButtons.forEach(function (btn) {
      planUrls.push(btn.getAttribute('data-plan-src') || '');
    });

    var lightboxIndex = 0;
    var lastFocus = null;

    function updateScrollUi() {
      if (!scrollEl) {
        return;
      }

      var maxScroll = scrollEl.scrollWidth - scrollEl.clientWidth;
      if (progressFill) {
        var pct = maxScroll > 0 ? scrollEl.scrollLeft / maxScroll : 1;
        progressFill.style.width = String(Math.round(pct * 100)) + '%';
      }
    }

    if (scrollEl && cards.length) {
      var observer = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (entry.isIntersecting) {
              entry.target.classList.add('is-in-view');
              var cardIndex = Array.prototype.indexOf.call(cards, entry.target);
              if (cardIndex >= 0 && indexLabel) {
                indexLabel.textContent = String(cardIndex + 1) + ' / ' + String(cards.length);
              }
            } else {
              entry.target.classList.remove('is-in-view');
            }
          });
        },
        { root: scrollEl, threshold: 0.55 }
      );

      cards.forEach(function (card) {
        observer.observe(card);
      });

      scrollEl.addEventListener('scroll', updateScrollUi, { passive: true });
      updateScrollUi();

      var isDown = false;
      var startX = 0;
      var startScroll = 0;

      scrollEl.addEventListener('mousedown', function (event) {
        if (event.button !== 0 || event.target.closest('.detail-plans-zoom')) {
          return;
        }
        isDown = true;
        scrollEl.classList.add('is-dragging');
        startX = event.pageX;
        startScroll = scrollEl.scrollLeft;
      });

      window.addEventListener('mouseup', function () {
        isDown = false;
        scrollEl.classList.remove('is-dragging');
      });

      scrollEl.addEventListener('mousemove', function (event) {
        if (!isDown) {
          return;
        }
        event.preventDefault();
        scrollEl.scrollLeft = startScroll - (event.pageX - startX);
      });
    }

    function openLightbox(openIndex) {
      if (!lightbox || !lightboxImg || !planUrls[openIndex]) {
        return;
      }

      lightboxIndex = openIndex;
      lightboxImg.src = planUrls[openIndex];
      lightboxImg.alt = lightboxImg.alt || 'Sitemap';
      if (lightboxCaption) {
        lightboxCaption.textContent = 'Plan ' + String(openIndex + 1);
      }

      lastFocus = document.activeElement;
      lightbox.hidden = false;
      lightbox.classList.add('is-open');
      lightbox.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      document.body.classList.add('detail-plans-lightbox-open');
      var closeBtn = lightbox.querySelector('.detail-plans-lightbox-close');
      if (closeBtn) {
        closeBtn.focus();
      }
    }

    function closeLightbox() {
      if (!lightbox) {
        return;
      }

      lightbox.hidden = true;
      lightbox.classList.remove('is-open');
      lightbox.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      document.body.classList.remove('detail-plans-lightbox-open');
      if (lastFocus && typeof lastFocus.focus === 'function') {
        lastFocus.focus();
      }
    }

    function stepLightbox(delta) {
      if (planUrls.length <= 1) {
        return;
      }
      openLightbox((lightboxIndex + delta + planUrls.length) % planUrls.length);
    }

    openButtons.forEach(function (btn) {
      btn.addEventListener('mousedown', function (event) {
        event.stopPropagation();
      });

      btn.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        var openIndex = parseInt(btn.getAttribute('data-plan-open') || '0', 10);
        openLightbox(openIndex);
      });
    });

    closeButtons.forEach(function (btn) {
      btn.addEventListener('click', closeLightbox);
    });

    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        stepLightbox(-1);
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        stepLightbox(1);
      });
    }

    document.addEventListener('keydown', function (event) {
      if (!lightbox || lightbox.hidden || !lightbox.classList.contains('is-open')) {
        return;
      }

      if (event.key === 'Escape') {
        closeLightbox();
      } else if (event.key === 'ArrowLeft') {
        stepLightbox(-1);
      } else if (event.key === 'ArrowRight') {
        stepLightbox(1);
      }
    });
  }

  window.__detailMediaLightbox = initMediaLightbox();

  document.querySelectorAll('[data-detail-gallery]').forEach(function (galleryEl) {
    initGallery(galleryEl);
  });

  document.querySelectorAll('[data-detail-peek-carousel]').forEach(function (carousel) {
    initPeekCarousel(carousel);
  });

  document.querySelectorAll('[data-detail-plans]').forEach(function (plansSection) {
    initPlansViewer(plansSection);
  });

  initLotSelection();
  initVillaLotAccordion();
  initAsideStickyState();
  initSmoothAnchors();
})();
