(function () {
  'use strict';

  var form = document.querySelector('[data-enquiry-form]');
  if (!form) {
    return;
  }

  var apiUrl = form.getAttribute('data-enquiry-api');
  var enquiryId = form.getAttribute('data-enquiry-id');
  var statusBadge = document.querySelector('[data-enquiry-status]');

  function getAlertHost() {
    var host = document.querySelector('[data-enquiry-alert]');
    if (host) {
      return host;
    }

    host = document.createElement('div');
    host.setAttribute('data-enquiry-alert', '');
    var panel = form.closest('.panel');
    if (panel) {
      panel.insertBefore(host, panel.firstChild);
    }
    return host;
  }

  function showAlert(message, type) {
    var host = getAlertHost();
    host.innerHTML =
      '<div class="alert alert-' +
      (type === 'error' ? 'error' : 'success') +
      '" role="' +
      (type === 'error' ? 'alert' : 'status') +
      '">' +
      message +
      '</div>';

    var alert = host.querySelector('.alert');
    if (alert && alert.classList.contains('alert-success')) {
      window.setTimeout(function () {
        if (alert.parentNode) {
          alert.parentNode.removeChild(alert);
        }
      }, 6000);
    }
  }

  function setLoading(action) {
    form.classList.add('is-submitting');
    form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
      if (!btn.dataset.originalText) {
        btn.dataset.originalText = btn.textContent;
      }
      btn.disabled = true;
      if (btn.value === action || btn.name === 'action') {
        btn.textContent = action === 'delete' ? 'Deleting…' : 'Saving…';
      }
    });
  }

  function clearLoading() {
    form.classList.remove('is-submitting');
    form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
      btn.disabled = false;
      if (btn.dataset.originalText) {
        btn.textContent = btn.dataset.originalText;
      }
    });
  }

  function updateStatusBadge(status, statusClass) {
    if (!statusBadge || !status) {
      return;
    }

    statusBadge.textContent = status;
    statusBadge.className = 'status-badge ' + (statusClass || '');
  }

  form.addEventListener('submit', function (event) {
    if (!window.fetch) {
      return;
    }

    var submitter = event.submitter;
    var action = submitter && submitter.value ? submitter.value : 'save';

    if (action === 'delete' && !window.confirm('Delete this enquiry?')) {
      event.preventDefault();
      return;
    }

    event.preventDefault();

    var formData = new FormData(form);
    formData.set('action', action);
    formData.set('id', enquiryId);

    setLoading(action);

    fetch(apiUrl, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
      .then(function (response) {
        return response.json().catch(function () {
          return { ok: false, error: 'Unexpected response from server.' };
        }).then(function (data) {
          if (!response.ok && data.ok !== false) {
            return { ok: false, error: 'Request failed.' };
          }
          return data;
        });
      })
      .then(function (data) {
        if (!data.ok) {
          throw new Error(data.error || 'Request failed.');
        }

        if (action === 'delete' && data.redirect) {
          window.location.href = data.redirect;
          return;
        }

        updateStatusBadge(data.status, data.statusClass);
        showAlert(data.message || 'Enquiry updated.', 'success');
      })
      .catch(function (err) {
        showAlert(err.message || 'Could not save enquiry.', 'error');
      })
      .finally(function () {
        clearLoading();
      });
  });
})();
