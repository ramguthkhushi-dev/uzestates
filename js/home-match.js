(function () {
  'use strict';

  var tablist = document.querySelector('[data-match-tabs]');
  if (!tablist) {
    return;
  }

  var tabs = tablist.querySelectorAll('.match-choice');
  var panels = document.querySelectorAll('.match-panel');
  var bgLayers = document.querySelectorAll('.match-bg-layer');
  var canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  function activateTab(tab) {
    var panelId = tab.getAttribute('aria-controls');
    var matchKey = tab.getAttribute('data-match-key');
    if (!panelId || !matchKey) {
      return;
    }

    tabs.forEach(function (item) {
      var isActive = item === tab;
      item.classList.toggle('is-active', isActive);
      item.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    panels.forEach(function (panel) {
      var isActive = panel.id === panelId;
      panel.classList.toggle('is-active', isActive);
      panel.hidden = !isActive;
    });

    bgLayers.forEach(function (layer) {
      layer.classList.toggle('is-active', layer.getAttribute('data-match-bg') === matchKey);
    });
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      activateTab(tab);
    });

    if (canHover) {
      tab.addEventListener('mouseenter', function () {
        activateTab(tab);
      });
    }

    tab.addEventListener('keydown', function (event) {
      var index = Array.prototype.indexOf.call(tabs, tab);
      var nextIndex = index;

      if (event.key === 'ArrowDown' || event.key === 'ArrowRight') {
        nextIndex = (index + 1) % tabs.length;
      } else if (event.key === 'ArrowUp' || event.key === 'ArrowLeft') {
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
})();
