(function () {
  'use strict';

  var panel = document.querySelector('[data-enquiries-panel]');
  if (!panel) {
    return;
  }

  var form = panel.querySelector('[data-enquiries-filter]');
  var tbody = panel.querySelector('[data-enquiries-tbody]');
  var countEl = panel.querySelector('[data-enquiries-count]');
  var apiUrl = panel.getAttribute('data-enquiries-api');
  var debounceTimer = null;

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function renderRows(rows) {
    if (!tbody) {
      return;
    }

    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="8">No enquiries yet.</td></tr>';
      return;
    }

    tbody.innerHTML = rows
      .map(function (row) {
        var phone = row.phone
          ? '<a href="tel:' +
            escapeHtml(String(row.phone).replace(/\s+/g, '')) +
            '" class="table-link">' +
            escapeHtml(row.phone) +
            '</a>'
          : '—';
        var email = row.email
          ? '<a href="mailto:' +
            escapeHtml(row.email) +
            '" class="table-link">' +
            escapeHtml(row.email) +
            '</a>'
          : '—';

        return (
          '<tr>' +
          '<td><a href="' +
          escapeHtml(row.view_url) +
          '" class="table-link">' +
          escapeHtml(row.name) +
          '</a></td>' +
          '<td>' +
          phone +
          '</td>' +
          '<td>' +
          email +
          '</td>' +
          '<td>' +
          escapeHtml(row.property_title || '—') +
          '</td>' +
          '<td>' +
          escapeHtml(row.enquiry_type) +
          '</td>' +
          '<td><span class="status-badge ' +
          escapeHtml(row.status_class) +
          '">' +
          escapeHtml(row.status) +
          '</span></td>' +
          '<td>' +
          escapeHtml(row.created_at) +
          '</td>' +
          '<td><a href="' +
          escapeHtml(row.view_url) +
          '" class="btn btn-outline btn-sm">View</a></td>' +
          '</tr>'
        );
      })
      .join('');
  }

  function buildQuery() {
    if (!form) {
      return '';
    }

    var params = new URLSearchParams(new FormData(form));
    return params.toString();
  }

  function loadRows() {
    if (!apiUrl) {
      return;
    }

    panel.classList.add('is-loading');

    fetch(apiUrl + '?' + buildQuery(), {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
      .then(function (response) {
        return response.json();
      })
      .then(function (data) {
        if (!data.ok) {
          throw new Error(data.error || 'Could not load enquiries.');
        }
        if (countEl) {
          countEl.textContent = String(data.count) + ' shown';
        }
        renderRows(data.rows || []);
      })
      .catch(function () {
        if (tbody) {
          tbody.innerHTML =
            '<tr><td colspan="8">Could not load enquiries. Refresh the page.</td></tr>';
        }
      })
      .finally(function () {
        panel.classList.remove('is-loading');
      });
  }

  function scheduleLoad() {
    window.clearTimeout(debounceTimer);
    debounceTimer = window.setTimeout(loadRows, 250);
  }

  if (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      loadRows();
    });

    form.querySelectorAll('select').forEach(function (select) {
      select.addEventListener('change', scheduleLoad);
    });
  }
})();
