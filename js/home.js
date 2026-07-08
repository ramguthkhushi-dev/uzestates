(function () {
  'use strict';

  var visual = document.querySelector('.contact-card-visual');
  if (!visual) {
    return;
  }

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    visual.classList.add('is-wa-live');
    return;
  }

  if (!('IntersectionObserver' in window)) {
    visual.classList.add('is-wa-live');
    return;
  }

  var observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        visual.classList.toggle('is-wa-live', entry.isIntersecting);
      });
    },
    {
      threshold: 0.35,
      rootMargin: '0px 0px -5% 0px',
    }
  );

  observer.observe(visual);
})();
