(function () {
  'use strict';

  var items = window.galleryLightboxItems || [];
  var lightbox = document.getElementById('galleryLightbox');
  var stage = document.getElementById('lightboxStage');
  var caption = document.getElementById('lightboxCaption');
  var closeBtn = document.getElementById('lightboxClose');
  var prevBtn = document.getElementById('lightboxPrev');
  var nextBtn = document.getElementById('lightboxNext');
  var currentIndex = 0;

  function pauseStageMedia() {
    if (!stage) return;
    stage.querySelectorAll('video, iframe').forEach(function (el) {
      if (el.tagName === 'VIDEO') {
        el.pause();
      }
      if (el.tagName === 'IFRAME') {
        el.src = '';
      }
    });
  }

  function closeLightbox() {
    if (!lightbox || !stage) return;
    pauseStageMedia();
    lightbox.hidden = true;
    lightbox.setAttribute('aria-hidden', 'true');
    stage.innerHTML = '';
    if (caption) {
      caption.hidden = true;
      caption.textContent = '';
    }
    document.body.style.overflow = '';
  }

  function renderItem(index) {
    if (!stage || !items.length) return;
    var item = items[index];
    if (!item) return;

    pauseStageMedia();
    stage.innerHTML = '';
    currentIndex = index;

    if (item.type === 'video' && item.embed) {
      var iframe = document.createElement('iframe');
      iframe.src = item.url;
      iframe.allow = 'autoplay; fullscreen; picture-in-picture';
      iframe.allowFullscreen = true;
      iframe.title = item.title || 'Video';
      stage.appendChild(iframe);
    } else if (item.type === 'video') {
      var video = document.createElement('video');
      video.src = item.url;
      video.controls = true;
      video.autoplay = true;
      video.playsInline = true;
      stage.appendChild(video);
    } else {
      var img = document.createElement('img');
      img.src = item.url;
      img.alt = item.title || '';
      stage.appendChild(img);
    }

    if (caption) {
      if (item.title) {
        caption.textContent = item.title;
        caption.hidden = false;
      } else {
        caption.hidden = true;
        caption.textContent = '';
      }
    }

    if (prevBtn) prevBtn.disabled = items.length <= 1;
    if (nextBtn) nextBtn.disabled = items.length <= 1;
  }

  function openLightbox(index) {
    if (!lightbox || !items.length) return;
    renderItem(index);
    lightbox.hidden = false;
    lightbox.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    if (closeBtn) closeBtn.focus();
  }

  function step(delta) {
    if (!items.length) return;
    var next = (currentIndex + delta + items.length) % items.length;
    renderItem(next);
  }

  document.querySelectorAll('[data-gallery-item]').forEach(function (el) {
    el.addEventListener('click', function () {
      var index = parseInt(el.getAttribute('data-gallery-index') || '0', 10);
      openLightbox(index);
    });

    el.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        var index = parseInt(el.getAttribute('data-gallery-index') || '0', 10);
        openLightbox(index);
      }
    });
  });

  if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
  if (prevBtn) prevBtn.addEventListener('click', function () { step(-1); });
  if (nextBtn) nextBtn.addEventListener('click', function () { step(1); });

  if (lightbox) {
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) closeLightbox();
    });
  }

  document.addEventListener('keydown', function (e) {
    if (!lightbox || lightbox.hidden) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') step(-1);
    if (e.key === 'ArrowRight') step(1);
  });
})();
