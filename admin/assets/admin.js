(function () {
  'use strict';

  var body = document.body;
  var sidebar = document.querySelector('.admin-sidebar');
  var overlay = document.querySelector('.sidebar-overlay');
  var toggle = document.querySelector('.sidebar-toggle');
  var topbar = document.querySelector('.admin-topbar');

  function setSidebarOpen(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('is-open', open);
    if (overlay) overlay.classList.toggle('is-visible', open);
    body.classList.toggle('sidebar-open', open);
    if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      setSidebarOpen(!sidebar.classList.contains('is-open'));
    });
  }

  if (overlay) {
    overlay.addEventListener('click', function () {
      setSidebarOpen(false);
    });
  }

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') setSidebarOpen(false);
  });

  if (topbar) {
    var onScroll = function () {
      topbar.classList.toggle('is-scrolled', window.scrollY > 8);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  document.querySelectorAll('.alert').forEach(function (alert) {
    if (alert.querySelector('.alert-dismiss')) return;

    var dismiss = document.createElement('button');
    dismiss.type = 'button';
    dismiss.className = 'alert-dismiss';
    dismiss.setAttribute('aria-label', 'Dismiss');
    dismiss.innerHTML = '&times;';
    dismiss.addEventListener('click', function () {
      alert.classList.add('is-dismissed');
      window.setTimeout(function () {
        if (alert.parentNode) alert.parentNode.removeChild(alert);
      }, 280);
    });
    alert.appendChild(dismiss);

    if (alert.classList.contains('alert-success')) {
      window.setTimeout(function () {
        if (!alert.parentNode) return;
        alert.classList.add('is-dismissed');
        window.setTimeout(function () {
          if (alert.parentNode) alert.parentNode.removeChild(alert);
        }, 280);
      }, 6000);
    }
  });

  document.querySelectorAll('.admin-form, .delete-form, form[method="post"]').forEach(function (form) {
    if (form.hasAttribute('data-ajax-form')) {
      return;
    }

    form.addEventListener('submit', function () {
      form.classList.add('is-submitting');
      form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (btn) {
        if (!btn.dataset.originalText) {
          btn.dataset.originalText = btn.tagName === 'INPUT' ? btn.value : btn.textContent;
        }
        btn.disabled = true;
        var isDelete = btn.classList.contains('btn-danger') || btn.value === 'delete';
        var label = isDelete ? 'Deleting…' : 'Saving…';
        if (btn.tagName === 'BUTTON') {
          btn.textContent = label;
        } else {
          btn.value = label;
        }
      });
    });
  });

  var revealItems = document.querySelectorAll(
    '.stat-card, .panel, .admin-subnav, .alert, .panel-text a[href], .property-item, .properties-header, .properties-filter, .dash-hero, .dash-stat-card, .dash-panel, .dash-guide-panel'
  );
  revealItems.forEach(function (el, index) {
    el.classList.add('admin-reveal');
    el.style.setProperty('--reveal-delay', Math.min(index * 45, 360) + 'ms');
  });

  if (body.classList.contains('admin-body')) {
    window.requestAnimationFrame(function () {
      body.classList.add('admin-ready');
    });
  }

  document.querySelectorAll('.data-table tbody tr').forEach(function (row) {
    row.addEventListener('mouseenter', function () {
      row.classList.add('is-hovered');
    });
    row.addEventListener('mouseleave', function () {
      row.classList.remove('is-hovered');
    });
  });

  document.querySelectorAll('.dash-table-row[data-href]').forEach(function (row) {
    row.addEventListener('click', function (event) {
      if (event.target.closest('a')) return;
      window.location.href = row.getAttribute('data-href');
    });
    row.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        window.location.href = row.getAttribute('data-href');
      }
    });
    row.setAttribute('tabindex', '0');
    row.setAttribute('role', 'link');
  });

  document.querySelectorAll('.topbar-view-site, .dash-status-link').forEach(function (el) {
    el.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      if (el.tagName === 'A') {
        event.preventDefault();
        el.click();
      }
    });
  });

  document.querySelectorAll('.sidebar-link').forEach(function (link) {
    link.addEventListener('click', function () {
      if (window.matchMedia('(max-width: 960px)').matches) {
        setSidebarOpen(false);
      }
    });
  });
})();
