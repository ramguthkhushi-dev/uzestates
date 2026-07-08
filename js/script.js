(function () {
  "use strict";

  var header = document.querySelector("[data-site-header]");
  var menuButton = document.getElementById("menuButton");
  var navLinks = document.getElementById("navLinks");

  if (header) {
    header.classList.add("is-loaded");

    var brandName = header.querySelector("[data-brand-name]");
    if (brandName && !brandName.dataset.lettersReady) {
      var letterIndex = 0;

      brandName.querySelectorAll(".brand-part").forEach(function (part) {
        var text = part.textContent || "";
        part.textContent = "";

        text.split("").forEach(function (char) {
          var letter = document.createElement("span");
          letter.className = "brand-letter";
          letter.textContent = char;
          letter.style.setProperty("--i", String(letterIndex));
          part.appendChild(letter);
          letterIndex += 1;
        });
      });

      brandName.dataset.lettersReady = "true";
    }

    var scrollThreshold = header.classList.contains("site-header-hero") ? 60 : 12;

    function updateHeaderOnScroll() {
      var scrolled = window.scrollY > scrollThreshold;
      header.classList.toggle("is-scrolled", scrolled);
      header.classList.toggle("site-header-scrolled", scrolled);
    }

    updateHeaderOnScroll();
    window.addEventListener("scroll", updateHeaderOnScroll, { passive: true });
  }

  function closeMobileNav() {
    if (!navLinks || !menuButton) {
      return;
    }

    navLinks.classList.remove("active");
    menuButton.classList.remove("is-open");
    menuButton.setAttribute("aria-expanded", "false");
  }

  if (menuButton && navLinks) {
    menuButton.addEventListener("click", function () {
      var isOpen = navLinks.classList.toggle("active");
      menuButton.classList.toggle("is-open", isOpen);
      menuButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });

    navLinks.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", closeMobileNav);
    });

    document.addEventListener("click", function (event) {
      if (
        navLinks.classList.contains("active") &&
        !navLinks.contains(event.target) &&
        !menuButton.contains(event.target)
      ) {
        closeMobileNav();
      }
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeMobileNav();
      }
    });
  }
})();
