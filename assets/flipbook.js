// assets/flipbook.js

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".flipbook-widget").forEach((widget) => {
    const checkboxes = Array.from(
      widget.querySelectorAll('input[type="checkbox"][id$="_checkbox"]')
    );

    const btnPrev = widget.querySelector(".flip-prev");
    const btnNext = widget.querySelector(".flip-next");
    const btnFs = widget.querySelector(".flip-fullscreen");

    const currentPageSpan = widget.querySelector(".current-page");
    const totalPagesSpan = widget.querySelector(".total-pages");

    let currentIndex = -1; // -1 = portada cerrada
    const total = checkboxes.length;
    if (totalPagesSpan) totalPagesSpan.textContent = total;

    const FLIP_DELAY = 400; // tiempo de la animación en ms (igual que en tu CSS transition)

    let isFlipping = false; // 🔒 bloqueo mientras rota

    // === Función para ir a una página ===
    function goTo(index) {
      if (isFlipping) return; // evita clicks múltiples
      if (index < -1) index = -1;
      if (index > total - 1) index = total - 1;

      isFlipping = true;

      // Reinicia todo y marca hasta index
      checkboxes.forEach((cb, i) => {
        cb.checked = i <= index;
      });

      currentIndex = index;
      if (currentPageSpan) currentPageSpan.textContent = currentIndex + 1;

      // 🔓 desbloquear después del tiempo de animación
      setTimeout(() => {
        isFlipping = false;
      }, FLIP_DELAY);
    }

    // === Botones ===
    btnNext?.addEventListener("click", () => goTo(currentIndex + 1));
    btnPrev?.addEventListener("click", () => goTo(currentIndex - 1));

    btnFs?.addEventListener("click", () => {
      widget.classList.toggle("fullscreen");
    });

    // === Cuando se hace click manual en el libro (labels) ===
    checkboxes.forEach((cb, i) =>
      cb.addEventListener("change", () => {
        if (cb.checked) {
          currentIndex = i;
        } else {
          currentIndex = i - 1;
        }
        if (currentPageSpan) currentPageSpan.textContent = currentIndex + 1;
      })
    );

    // === Arrancar en portada cerrada ===
    goTo(-1);
  });

  const btnZoom = widget.querySelector(".flip-zoom");

  btnZoom?.addEventListener("click", () => {
    widget.classList.toggle("zoomed");

    // Cambiar ícono según estado
    if (widget.classList.contains("zoomed")) {
      btnZoom.textContent = "🔎-"; // zoom out
    } else {
      btnZoom.textContent = "🔍"; // zoom in
    }
  });
});

// Get elements that change with the mode.
const toggleModeBtn = document.getElementById("toggle-mode-btn");
const portfolioLink = document.getElementById("portfolio-link");
const body = document.body;

// Function to apply mode.
function applyMode(mode) {
  body.classList.remove("light-mode", "dark-mode");
  body.classList.add(mode);

  if (mode === "dark-mode") {
    // Set dark mode styles.
    toggleModeBtn.style.color = "rgb(245, 245, 245)";
    toggleModeBtn.innerHTML = '<i class="bi bi-sun-fill"></i>';

    portfolioLink.style.color = "rgb(245, 245, 245)";

    responsiveWarning.style.backgroundColor = "rgb(2, 4, 8)";
  } else {
    // Set light mode styles.
    toggleModeBtn.style.color = "rgb(2, 4, 8)";
    toggleModeBtn.innerHTML = '<i class="bi bi-moon-stars-fill"></i>';

    portfolioLink.style.color = "rgb(2, 4, 8)";

    responsiveWarning.style.backgroundColor = "rgb(245, 245, 245)";
  }
}

// Check and apply saved mode on page load
let savedMode = localStorage.getItem("mode");

if (savedMode === null) {
  savedMode = "light-mode"; // Default mode.
}
applyMode(savedMode);

// Toggle mode and save preference.
toggleModeBtn.addEventListener("click", function () {
  let newMode;

  if (body.classList.contains("light-mode")) {
    newMode = "dark-mode";
  } else {
    newMode = "light-mode";
  }

  applyMode(newMode);

  // Save choice.
  localStorage.setItem("mode", newMode);
});

document.addEventListener("DOMContentLoaded", () => {
  /*************
   * CONFIGURACIÓN
   *************/
  const AUTO_OPEN_PAGE = 3; // Página hasta la que quieres abrir
  const COVER_DELAY = 2000; // Tiempo en ms antes de empezar a pasar páginas
  const PAGE_FLIP_INTERVAL = 300; // Tiempo entre cada página (ms)

  /*************
   * ELEMENTOS DEL FLIPBOOK
   *************/
  const flipbook = document.getElementById("flip_book");
  const flipbookPages = [
    document.getElementById("cover_checkbox"),
    document.getElementById("page1_checkbox"),
    document.getElementById("page2_checkbox"),
    document.getElementById("page3_checkbox"),
    document.getElementById("page4_checkbox"),
    document.getElementById("page5_checkbox"),
  ];

  let autoOpened = true; // Para que solo se abra una vez

  /*************
   * FUNCIONES
   *************/
  function flipPagesSequentially(targetPage) {
    let i = 0;

    const interval = setInterval(() => {
      if (flipbookPages[i]) flipbookPages[i].checked = true;
      i++;
      if (i > targetPage) clearInterval(interval);
    }, PAGE_FLIP_INTERVAL);
  }

  function isHalfVisible(el) {
    const rect = el.getBoundingClientRect();
    const windowHeight =
      window.innerHeight || document.documentElement.clientHeight;
    const elementHalf = rect.top + rect.height / 2;
    return elementHalf >= 0 && elementHalf <= windowHeight;
  }

  /*************
   * DETECTOR DE SCROLL
   *************/
  window.addEventListener("scroll", () => {
    if (autoOpened) return;

    if (isHalfVisible(flipbook)) {
      autoOpened = true;

      // Primero mostrar la portada
      if (flipbookPages[0]) flipbookPages[1].checked = true;

      // Luego de COVER_DELAY ms empezar a pasar páginas secuencialmente
      setTimeout(() => {
        flipPagesSequentially(AUTO_OPEN_PAGE);
      }, COVER_DELAY);
    }
  });
});
