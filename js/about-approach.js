(function () {
  'use strict';

  var section = document.querySelector('[data-approach-section]');
  var tablist = document.querySelector('[data-approach-tabs]');
  if (!tablist) {
    return;
  }

  var panelsWrap = document.querySelector('[data-approach-panels]');
  var media = document.querySelector('[data-approach-media]');
  var indicator = tablist.querySelector('.about-approach-tab-indicator');
  var tabs = tablist.querySelectorAll('.about-approach-tab');
  var panels = panelsWrap
    ? panelsWrap.querySelectorAll('.about-approach-panel-content')
    : document.querySelectorAll('.about-approach-panel-content');

  var activeIndex = 0;
  var animating = false;
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function tabIndex(tab) {
    return parseInt(tab.getAttribute('data-approach-index') || '0', 10);
  }

  function updateIndicator(tab) {
    if (!indicator || !tab) {
      return;
    }
    indicator.style.width = tab.offsetWidth + 'px';
    indicator.style.left = tab.offsetLeft + 'px';
  }

  function setPanelState(panel, active) {
    panel.classList.toggle('is-active', active);
    panel.hidden = !active;
    panel.setAttribute('aria-hidden', active ? 'false' : 'true');
  }

  function clearPanelMotion(panel) {
    panel.classList.remove('is-entering', 'is-exiting', 'is-entering-forward', 'is-entering-back');
  }

  function activateTab(tab, options) {
    options = options || {};
    var nextIndex = tabIndex(tab);
    var panelId = tab.getAttribute('aria-controls');
    if (!panelId || nextIndex === activeIndex) {
      return;
    }

    var nextPanel = document.getElementById(panelId);
    var currentPanel = panelsWrap
      ? panelsWrap.querySelector('.about-approach-panel-content.is-active')
      : null;

    if (!nextPanel || !currentPanel || currentPanel === nextPanel) {
      return;
    }

    if (animating && !options.force) {
      return;
    }

    var direction = nextIndex > activeIndex ? 'forward' : 'back';
    activeIndex = nextIndex;

    tabs.forEach(function (item) {
      var isActive = item === tab;
      item.classList.toggle('is-active', isActive);
      item.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    updateIndicator(tab);

    if (reducedMotion) {
      clearPanelMotion(currentPanel);
      clearPanelMotion(nextPanel);
      setPanelState(currentPanel, false);
      setPanelState(nextPanel, true);
      return;
    }

    animating = true;
    if (panelsWrap) {
      panelsWrap.setAttribute('data-direction', direction);
    }

    clearPanelMotion(nextPanel);
    clearPanelMotion(currentPanel);

    nextPanel.hidden = false;
    nextPanel.setAttribute('aria-hidden', 'false');
    nextPanel.classList.add('is-active');
    nextPanel.classList.add(
      'is-entering',
      direction === 'forward' ? 'is-entering-forward' : 'is-entering-back'
    );

    currentPanel.classList.add('is-exiting');
    currentPanel.classList.remove('is-active');

    window.setTimeout(function () {
      clearPanelMotion(currentPanel);
      setPanelState(currentPanel, false);

      clearPanelMotion(nextPanel);

      animating = false;
    }, 560);
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      activateTab(tab);
    });

    tab.addEventListener('keydown', function (event) {
      var index = tabIndex(tab);
      var nextIndex = index;

      if (event.key === 'ArrowRight') {
        nextIndex = (index + 1) % tabs.length;
      } else if (event.key === 'ArrowLeft') {
        nextIndex = (index - 1 + tabs.length) % tabs.length;
      } else if (event.key === 'Home') {
        nextIndex = 0;
      } else if (event.key === 'End') {
        nextIndex = tabs.length - 1;
      } else {
        return;
      }

      event.preventDefault();
      tabs[nextIndex].focus();
      activateTab(tabs[nextIndex]);
    });
  });

  function initIndicator() {
    var activeTab = tablist.querySelector('.about-approach-tab.is-active') || tabs[0];
    if (activeTab) {
      activeIndex = tabIndex(activeTab);
      updateIndicator(activeTab);
    }
  }

  initIndicator();

  window.addEventListener('resize', function () {
    var activeTab = tablist.querySelector('.about-approach-tab.is-active');
    updateIndicator(activeTab);
  });

  if (section && media && 'IntersectionObserver' in window && !reducedMotion) {
    var mediaObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          media.classList.toggle('is-in-view', entry.isIntersecting);
          section.classList.toggle('is-in-view', entry.isIntersecting);
        });
      },
      { threshold: 0.25, rootMargin: '0px 0px -8% 0px' }
    );
    mediaObserver.observe(section);
  } else if (media) {
    media.classList.add('is-in-view');
    if (section) {
      section.classList.add('is-in-view');
    }
  }
})();
