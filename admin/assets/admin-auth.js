(function () {
  "use strict";

  function errorEl(key) {
    return document.querySelector('[data-error-for="' + key + '"]');
  }

  function setError(key, message) {
    var el = errorEl(key);
    var input = document.querySelector('[data-validate="' + key + '"]');
    if (el) {
      el.textContent = message || "";
    }
    if (input) {
      input.classList.toggle("is-invalid", Boolean(message));
    }
  }

  function clearErrors(form) {
    form.querySelectorAll("[data-validate]").forEach(function (input) {
      input.classList.remove("is-invalid");
    });
    form.querySelectorAll(".admin-field-error").forEach(function (el) {
      el.textContent = "";
    });
  }

  function validateEmail(value) {
    var trimmed = value.trim();
    if (!trimmed) {
      return "Email is required.";
    }
    if (trimmed.length > 150) {
      return "Email is too long.";
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) {
      return "Please enter a valid email address.";
    }
    return "";
  }

  function validatePassword(value) {
    if (!value) {
      return "Password is required.";
    }
    if (value.length < 8) {
      return "Password must be at least 8 characters.";
    }
    if (value.length > 128) {
      return "Password is too long.";
    }
    return "";
  }

  function bindPasswordToggles(root) {
    (root || document).querySelectorAll("[data-toggle-password]").forEach(function (button) {
      if (button.dataset.boundToggle) {
        return;
      }
      button.dataset.boundToggle = "1";
      button.addEventListener("click", function () {
        var wrap = button.closest(".admin-password-wrap");
        var input = wrap ? wrap.querySelector("input") : null;
        if (!input) {
          return;
        }
        var show = input.type === "password";
        input.type = show ? "text" : "password";
        button.classList.toggle("is-visible", show);
        button.setAttribute("aria-label", show ? "Hide password" : "Show password");
      });
    });
  }

  function bindForm(form, validator) {
    if (!form) {
      return;
    }

    form.addEventListener("submit", function (event) {
      if (!validator(form)) {
        event.preventDefault();
      }
    });

    form.querySelectorAll("[data-validate]").forEach(function (input) {
      input.addEventListener("blur", function () {
        validator(form);
      });
      input.addEventListener("input", function () {
        var key = input.dataset.validate;
        if (key && errorEl(key) && errorEl(key).textContent) {
          validator(form);
        }
      });
    });
  }

  function validateLoginForm(form) {
    clearErrors(form);
    var valid = true;
    var user = (form.querySelector('[name="email"]') || {}).value || "";
    if (!user.trim()) {
      setError("login-email", "Username or email is required.");
      valid = false;
    }
    if (!(form.querySelector('[name="password"]') || {}).value) {
      setError("login-password", "Password is required.");
      valid = false;
    }
    return valid;
  }

  function validateForgotForm(form) {
    clearErrors(form);
    var message = validateEmail((form.querySelector('[name="email"]') || {}).value || "");
    if (message) {
      setError("forgot-email", message);
      return false;
    }
    return true;
  }

  function validateResetForm(form) {
    clearErrors(form);
    var valid = true;
    var password = (form.querySelector('[name="password"]') || {}).value || "";
    var confirm = (form.querySelector('[name="confirm_password"]') || {}).value || "";

    var passMsg = validatePassword(password);
    if (passMsg) {
      setError("reset-password", passMsg);
      valid = false;
    }

    if (!confirm) {
      setError("reset-confirm", "Please confirm your password.");
      valid = false;
    } else if (password !== confirm) {
      setError("reset-confirm", "Passwords do not match.");
      valid = false;
    }

    return valid;
  }

  bindPasswordToggles(document);
  bindForm(document.getElementById("adminLoginForm"), validateLoginForm);
  bindForm(document.getElementById("adminForgotForm"), validateForgotForm);
  bindForm(document.getElementById("adminResetForm"), validateResetForm);

  window.adminBindPasswordToggles = bindPasswordToggles;
})();
