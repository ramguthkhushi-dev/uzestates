(function () {
  'use strict';

  function ensureProgress(form) {
    var bar = form.querySelector('.upload-progress');
    if (bar) {
      return bar;
    }

    bar = document.createElement('div');
    bar.className = 'upload-progress';
    bar.hidden = true;
    bar.innerHTML =
      '<div class="upload-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">' +
      '<div class="upload-progress-fill"></div></div>' +
      '<p class="upload-progress-label">Uploading…</p>';
    form.insertBefore(bar, form.firstChild);
    return bar;
  }

  function setProgress(bar, percent) {
    var fill = bar.querySelector('.upload-progress-fill');
    var track = bar.querySelector('.upload-progress-track');
    var label = bar.querySelector('.upload-progress-label');
    var value = Math.max(0, Math.min(100, percent));

    bar.hidden = false;
    if (fill) {
      fill.style.width = value + '%';
    }
    if (track) {
      track.setAttribute('aria-valuenow', String(Math.round(value)));
    }
    if (label) {
      label.textContent = value >= 100 ? 'Processing…' : 'Uploading… ' + Math.round(value) + '%';
    }
  }

  document.querySelectorAll('form[enctype="multipart/form-data"]').forEach(function (form) {
    if (form.hasAttribute('data-no-upload-progress')) {
      return;
    }

    form.addEventListener('submit', function (event) {
      var hasFile = Array.prototype.some.call(
        form.querySelectorAll('input[type="file"]'),
        function (input) {
          return input.files && input.files.length > 0;
        }
      );

      if (!hasFile || !window.XMLHttpRequest) {
        return;
      }

      event.preventDefault();

      var bar = ensureProgress(form);
      var xhr = new XMLHttpRequest();
      var formData = new FormData(form);
      var action = form.getAttribute('action') || window.location.href;

      setProgress(bar, 0);
      form.classList.add('is-submitting');

      xhr.open(form.method || 'POST', action, true);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

      xhr.upload.addEventListener('progress', function (e) {
        if (!e.lengthComputable) {
          return;
        }
        setProgress(bar, (e.loaded / e.total) * 100);
      });

      xhr.addEventListener('load', function () {
        if (xhr.status >= 200 && xhr.status < 400) {
          window.location.href = xhr.responseURL || action;
          return;
        }

        form.classList.remove('is-submitting');
        bar.hidden = true;
        window.alert('Upload failed. Please try again.');
      });

      xhr.addEventListener('error', function () {
        form.classList.remove('is-submitting');
        bar.hidden = true;
        window.alert('Upload failed. Please check your connection.');
      });

      xhr.send(formData);
    });
  });
})();
