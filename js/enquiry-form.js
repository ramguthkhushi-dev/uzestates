(function () {
  const INTL_VERSION = "25.3.1";
  const UTILS_MODULE =
    "https://cdn.jsdelivr.net/npm/intl-tel-input@" +
    INTL_VERSION +
    "/build/js/utils.js";

  function phoneValidationMessage(iti, utilsReady) {
    if (!iti) {
      return "Please enter a valid phone number.";
    }

    if (!utilsReady) {
      return "Phone validation is still loading. Please try again.";
    }

    if (iti.isValidNumber()) {
      return "";
    }

    if (typeof intlTelInputUtils === "undefined") {
      return "Please enter a valid phone number.";
    }

    const code = iti.getValidationError();

    switch (code) {
      case intlTelInputUtils.validationError.TOO_SHORT:
        return "Phone number is too short.";
      case intlTelInputUtils.validationError.TOO_LONG:
        return "Phone number is too long.";
      case intlTelInputUtils.validationError.INVALID_COUNTRY_CODE:
        return "Invalid country code.";
      case intlTelInputUtils.validationError.INVALID_LENGTH:
        return "Please enter a valid phone number.";
      default:
        return "Please enter a valid phone number.";
    }
  }

  function getApiUrl(form) {
    const fromData = form.getAttribute("data-enquiry-api");
    if (fromData) {
      return fromData;
    }

    const base = document.documentElement.getAttribute("data-base-url") || "";
    if (base) {
      return base.replace(/\/$/, "") + "/api/enquiry.php";
    }

    return "api/enquiry.php";
  }

  function getOrCreateFeedback(form) {
    const card = form.closest(".contact-form-card, .detail-aside-inner");
    if (!card) {
      return null;
    }

    let feedback = card.querySelector(".enquiry-form-feedback");
    if (!feedback) {
      feedback = document.createElement("div");
      feedback.className = "enquiry-form-feedback";
      feedback.setAttribute("aria-live", "polite");
      feedback.hidden = true;
      card.insertBefore(feedback, form);
    }

    return feedback;
  }

  function showFormNotice(form, message, type) {
    const feedback = getOrCreateFeedback(form);
    if (!feedback) {
      return;
    }

    cardClearStaticNotices(form);

    feedback.className =
      "contact-notice contact-notice-" +
      (type === "ok" ? "ok" : "err") +
      " enquiry-form-feedback";
    feedback.setAttribute("role", type === "ok" ? "status" : "alert");
    feedback.textContent = message;
    feedback.hidden = false;
    feedback.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  function cardClearStaticNotices(form) {
    const card = form.closest(".contact-form-card, .detail-aside-inner");
    if (!card) {
      return;
    }

    card.querySelectorAll(".contact-notice:not(.enquiry-form-feedback)").forEach(function (el) {
      el.remove();
    });
  }

  function setSubmitting(form, submitting) {
    const btn = form.querySelector('[type="submit"]');
    if (!btn) {
      return;
    }

    if (submitting) {
      if (!btn.dataset.originalText) {
        btn.dataset.originalText = btn.textContent;
      }
      btn.disabled = true;
      btn.textContent = "Sending…";
      form.classList.add("is-submitting");
      return;
    }

    btn.disabled = false;
    if (btn.dataset.originalText) {
      btn.textContent = btn.dataset.originalText;
    }
    form.classList.remove("is-submitting");
  }

  function resetFormState(form, phoneIti, fields) {
    form.reset();
    if (phoneIti && fields.phone) {
      phoneIti.setNumber("");
    }
    form.querySelectorAll(".has-error").forEach(function (wrap) {
      wrap.classList.remove("has-error");
      wrap.querySelectorAll(".contact-field-error, .enquiry-field-error").forEach(function (el) {
        el.remove();
      });
    });
    form.querySelectorAll("[aria-invalid]").forEach(function (el) {
      el.setAttribute("aria-invalid", "false");
    });
  }

  function submitEnquiry(form, phoneIti, fields) {
    const apiUrl = getApiUrl(form);
    const formData = new FormData(form);

    setSubmitting(form, true);

    fetch(apiUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then(function (response) {
        return response.json().catch(function () {
          return { ok: false, error: "Unexpected response from server." };
        }).then(function (data) {
          if (!response.ok && data.ok !== false) {
            data = { ok: false, error: "Request failed." };
          }
          return data;
        });
      })
      .then(function (data) {
        if (!data.ok) {
          throw new Error(data.error || "Your enquiry could not be sent.");
        }

        showFormNotice(form, data.message || "Thank you. Your enquiry has been sent.", "ok");
        resetFormState(form, phoneIti, fields);
        if (window.grecaptcha && form.querySelector(".g-recaptcha")) {
          grecaptcha.reset();
        }
      })
      .catch(function (err) {
        showFormNotice(
          form,
          err.message || "Your enquiry could not be sent. Please try again.",
          "err"
        );
      })
      .finally(function () {
        setSubmitting(form, false);
      });
  }

  function initEnquiryForm(form) {
    const fields = {
      name: form.querySelector('[name="name"]'),
      phone: form.querySelector('[name="phone"]'),
      email: form.querySelector('[name="email"]'),
      message: form.querySelector('[name="message"]'),
      subject: form.querySelector('[name="interested_property"]'),
    };

    let phoneIti = null;
    let phoneUtilsReady = false;
    let phoneUtilsFailed = false;

    if (fields.phone && window.intlTelInput) {
      phoneIti = window.intlTelInput(fields.phone, {
        initialCountry: "mu",
        countryOrder: ["mu", "gb", "fr", "re", "za", "ae", "in", "au", "us"],
        separateDialCode: true,
        nationalMode: true,
        formatAsYouType: true,
        autoPlaceholder: "aggressive",
        countrySearch: true,
        countrySelectorMode: "AUTO",
        dropdownParent: document.body,
        loadUtils: function () {
          return import(UTILS_MODULE);
        },
      });

      if (phoneIti && phoneIti.promise) {
        phoneIti.promise
          .then(function () {
            phoneUtilsReady = true;
          })
          .catch(function () {
            phoneUtilsFailed = true;
          });
      } else {
        phoneUtilsReady = typeof intlTelInputUtils !== "undefined";
      }

      fields.phone.addEventListener("countrychange", function () {
        if (fieldWrap(fields.phone)?.classList.contains("has-error")) {
          validateField("phone");
        }
      });
    }

    const rules = {
      name(value) {
        const v = value.trim();
        if (v.length < 2) return "Please enter your full name.";
        if (v.length > 150) return "Name is too long.";
        return "";
      },
      phone(value) {
        const v = value.trim();
        if (v === "") return "Please enter your phone number.";

        if (phoneUtilsFailed) {
          return "Phone validation is unavailable. Check your connection and try again.";
        }

        if (phoneIti) {
          return phoneValidationMessage(phoneIti, phoneUtilsReady);
        }

        if (/^\+[1-9]\d{6,14}$/.test(v.replace(/\s/g, ""))) {
          return "";
        }

        const digits = v.replace(/\D/g, "");
        if (digits.length >= 7 && digits.length <= 15) {
          return "";
        }

        return "Please enter a valid phone number.";
      },
      email(value) {
        const v = value.trim();
        if (v === "") return "Please enter your email address.";
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) {
          return "Please enter a valid email address.";
        }
        if (v.length > 150) return "Email is too long.";
        return "";
      },
      message(value) {
        const v = value.trim();
        if (v.length === 0) return "Please enter a message.";
        if (v.length < 10) return "Please add a little more detail to your message.";
        if (v.length > 5000) return "Message is too long.";
        return "";
      },
      subject(value) {
        if (value.trim().length > 255) return "Subject is too long.";
        return "";
      },
    };

    function fieldWrap(input) {
      if (!input) return null;
      return input.closest(".contact-form-field, .form-field, .detail-field");
    }

    function showError(input, message) {
      const wrap = fieldWrap(input);
      if (!wrap) return;

      wrap.classList.add("has-error");
      input.setAttribute("aria-invalid", message ? "true" : "false");

      let hint = wrap.querySelector(".contact-field-error, .enquiry-field-error");
      if (message) {
        if (!hint) {
          hint = document.createElement("span");
          hint.className = wrap.classList.contains("contact-form-field")
            ? "contact-field-error"
            : "enquiry-field-error";
          hint.setAttribute("role", "alert");
          wrap.appendChild(hint);
        }
        hint.textContent = message;
      } else if (hint) {
        hint.remove();
      }
    }

    function clearErrors() {
      form.querySelectorAll(".has-error").forEach(function (wrap) {
        wrap.classList.remove("has-error");
        wrap.querySelectorAll(".contact-field-error, .enquiry-field-error").forEach(function (el) {
          el.remove();
        });
      });
      form.querySelectorAll("[aria-invalid]").forEach(function (el) {
        el.setAttribute("aria-invalid", "false");
      });
    }

    function validateField(key) {
      const input = fields[key === "subject" ? "subject" : key];
      if (!input || !rules[key]) return true;

      const message = rules[key](input.value);
      showError(input, message);
      return message === "";
    }

    function validateAll() {
      clearErrors();
      let ok = true;
      ["name", "phone", "email", "message"].forEach(function (key) {
        if (!validateField(key)) ok = false;
      });
      if (fields.subject && !validateField("subject")) ok = false;
      return ok;
    }

    Object.entries(fields).forEach(function (entry) {
      const key = entry[0];
      const input = entry[1];
      if (!input) return;
      const ruleKey = key === "subject" ? "subject" : key;
      input.addEventListener("blur", function () {
        validateField(ruleKey);
      });
      input.addEventListener("input", function () {
        if (fieldWrap(input)?.classList.contains("has-error")) {
          validateField(ruleKey);
        }
      });
    });

    form.addEventListener("submit", async function (event) {
      if (phoneIti && phoneIti.promise && !phoneUtilsReady && !phoneUtilsFailed) {
        try {
          await phoneIti.promise;
          phoneUtilsReady = true;
        } catch (err) {
          phoneUtilsFailed = true;
        }
      }

      if (!validateAll()) {
        event.preventDefault();
        const firstInvalid = form.querySelector(
          ".has-error input, .has-error textarea"
        );
        if (firstInvalid) firstInvalid.focus();
        return;
      }

      const recaptchaEl = form.querySelector(".g-recaptcha");
      if (recaptchaEl && window.grecaptcha) {
        const widgetId = recaptchaEl.getAttribute("data-widget-id");
        const response =
          widgetId !== null && widgetId !== ""
            ? grecaptcha.getResponse(Number(widgetId))
            : grecaptcha.getResponse();
        if (!response) {
          event.preventDefault();
          showFormNotice(
            form,
            "Please complete the security check.",
            "err"
          );
          return;
        }
      }

      if (phoneIti && fields.phone) {
        const international = phoneIti.getNumber();
        if (international) {
          fields.phone.value = international;
        }
      }

      if (!window.fetch || form.hasAttribute("data-no-ajax")) {
        return;
      }

      event.preventDefault();
      submitEnquiry(form, phoneIti, fields);
    });
  }

  document
    .querySelectorAll(".contact-enquiry-form, .property-enquiry-form, .detail-form")
    .forEach(initEnquiryForm);
})();
