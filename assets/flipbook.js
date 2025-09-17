document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".flipbook-widget").forEach((widget) => {
    const checkboxes = Array.from(
      widget.querySelectorAll('input[type="checkbox"][id$="_checkbox"]')
    );

    const btnPrev = widget.querySelector(".flip-prev");
    const btnNext = widget.querySelector(".flip-next");
    const btnFs = widget.querySelector(".flip-fullscreen");
    const btnZoom = widget.querySelector(".flip-zoom");

    const currentPageSpan = widget.querySelector(".current-page");
    const totalPagesSpan = widget.querySelector(".total-pages");

    let currentIndex = -1; // -1 = portada cerrada
    const total = checkboxes.length;
    if (totalPagesSpan) totalPagesSpan.textContent = total;

    const FLIP_DELAY = 400;
    let isFlipping = false;

    // === Función para ir a una página ===
    function goTo(index) {
      if (isFlipping) return;
      if (index < -1) index = -1;
      if (index > total - 1) index = total - 1;

      isFlipping = true;

      checkboxes.forEach((cb, i) => {
        cb.checked = i <= index;
      });

      currentIndex = index;
      if (currentPageSpan) currentPageSpan.textContent = currentIndex + 1;

      setTimeout(() => {
        isFlipping = false;
      }, FLIP_DELAY);
    }

    // Botones
    btnNext?.addEventListener("click", () => goTo(currentIndex + 1));
    btnPrev?.addEventListener("click", () => goTo(currentIndex - 1));
    btnFs?.addEventListener("click", () =>
      widget.classList.toggle("fullscreen")
    );

    // Zoom
    btnZoom?.addEventListener("click", () => {
      widget.classList.toggle("zoomed");
      btnZoom.textContent = widget.classList.contains("zoomed") ? "🔎-" : "🔍";
    });

    // Click manual
    checkboxes.forEach((cb, i) =>
      cb.addEventListener("change", () => {
        currentIndex = cb.checked ? i : i - 1;
        if (currentPageSpan) currentPageSpan.textContent = currentIndex + 1;
      })
    );

    // Arrancar cerrado
    goTo(-1);

    /*************
     * AUTO OPEN AL 60%
     *************/
    let autoOpened = false;
    const COVER_DELAY = 1000; // ms antes de empezar
    const PAGE_FLIP_INTERVAL = 300; // ms entre páginas

    function flipToLastPage() {
      let i = 0;
      const total = checkboxes.length;

      // 🔒 desactivar botones e interacción
      btnPrev?.setAttribute("disabled", "true");
      btnNext?.setAttribute("disabled", "true");
      btnFs?.setAttribute("disabled", "true");
      btnZoom?.setAttribute("disabled", "true");
      widget.classList.add("animating"); // opcional para bloquear clicks en labels vía CSS

      function next() {
        if (checkboxes[i]) checkboxes[i].checked = true;
        currentIndex = i;
        if (currentPageSpan) currentPageSpan.textContent = currentIndex + 1;

        i++;
        if (i < total) {
          // easing: rápido al inicio, más lento al final
          const progress = i / total;
          const delay = 60 + progress * 340;

          setTimeout(next, delay);
        } else {
          // ✅ cuando termina, reactivar botones e interacción
          btnPrev?.removeAttribute("disabled");
          btnNext?.removeAttribute("disabled");
          btnFs?.removeAttribute("disabled");
          btnZoom?.removeAttribute("disabled");
          widget.classList.remove("animating");
        }
      }

      next();
    }

    function is60PercentVisible(el) {
      const rect = el.getBoundingClientRect();
      const windowHeight =
        window.innerHeight || document.documentElement.clientHeight;

      const visibleTop = Math.max(0, 0 - rect.top);
      const visibleBottom = Math.min(rect.height, windowHeight - rect.top);
      const visibleHeight = visibleBottom - visibleTop;

      const visibleRatio = visibleHeight / rect.height;
      return visibleRatio >= 0.6;
    }

    window.addEventListener("scroll", () => {
      if (autoOpened) return;

      if (is60PercentVisible(widget)) {
        autoOpened = true;

        // abre portada
        if (checkboxes[0]) checkboxes[0].checked = true;

        // luego avanza hasta el último
        setTimeout(() => {
          flipToLastPage();
        }, COVER_DELAY);
      }
    });
  });
});
