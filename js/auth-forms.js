(function () {
  function qs(form, key) {
    return form.querySelector(`[data-validate="${key}"]`);
  }

  function errorEl(key) {
    return document.querySelector(`[data-error-for="${key}"]`);
  }

  function setError(key, message) {
    const el = errorEl(key);
    const input = document.querySelector(`[data-validate="${key}"]`);
    if (el) {
      el.textContent = message || "";
    }
    if (input) {
      input.classList.toggle("is-invalid", Boolean(message));
    }
  }

  function clearErrors(form) {
    form.querySelectorAll("[data-validate]").forEach((input) => {
      input.classList.remove("is-invalid");
    });
    form.querySelectorAll(".auth-field-error").forEach((el) => {
      el.textContent = "";
    });
  }

  function validateEmail(value) {
    const trimmed = value.trim();
    if (!trimmed) return "Email is required.";
    if (trimmed.length > 150) return "Email is too long.";
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) {
      return "Please enter a valid email address.";
    }
    return "";
  }

  function validatePassword(value) {
    if (!value) return "Password is required.";
    if (value.length < 8) return "Password must be at least 8 characters.";
    if (value.length > 128) return "Password is too long.";
    return "";
  }

  function validateForgotForm(form) {
    clearErrors(form);
    const message = validateEmail(form.email?.value || "");
    if (message) {
      setError("forgot-email", message);
      return false;
    }
    return true;
  }

  function validateResetForm(form) {
    clearErrors(form);
    let valid = true;

    const passMsg = validatePassword(form.password?.value || "");
    if (passMsg) {
      setError("reset-password", passMsg);
      valid = false;
    }

    const password = form.password?.value || "";
    const confirm = form.confirm_password?.value || "";
    if (!confirm) {
      setError("reset-confirm", "Please confirm your password.");
      valid = false;
    } else if (password !== confirm) {
      setError("reset-confirm", "Passwords do not match.");
      valid = false;
    }

    return valid;
  }

  function bindForm(form, validator) {
    if (!form) return;

    form.addEventListener("submit", (event) => {
      if (!validator(form)) {
        event.preventDefault();
      }
    });

    form.querySelectorAll("[data-validate]").forEach((input) => {
      input.addEventListener("blur", () => validator(form));
      input.addEventListener("input", () => {
        const key = input.dataset.validate;
        if (key && errorEl(key)?.textContent) {
          validator(form);
        }
      });
    });
  }

  document.querySelectorAll("[data-toggle-password]").forEach((button) => {
    button.addEventListener("click", () => {
      const wrap = button.closest(".auth-password-wrap");
      const input = wrap?.querySelector("input");
      if (!input) return;

      const show = input.type === "password";
      input.type = show ? "text" : "password";
      button.classList.toggle("is-visible", show);
      button.setAttribute("aria-label", show ? "Hide password" : "Show password");
    });
  });

  bindForm(document.getElementById("forgotForm"), validateForgotForm);
  bindForm(document.getElementById("resetForm"), validateResetForm);
})();
