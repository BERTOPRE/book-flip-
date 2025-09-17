/*********************
 * RESPONSIVE WARNING *
 *********************/

const responsiveWarning = document.getElementById("responsive-warning");
// "true" if the site is optimized for responsive design, "false" if not.
const responsiveDesign = false;

// Show mobile warning if the user is on mobile and responsive-design is false.
if (!responsiveDesign && window.innerWidth <= 768) {
  responsiveWarning.classList.add("show");
}

/***********************
 * MODE TOGGLE BEHAVIOR *
 ***********************/

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
      if (flipbookPages[0]) flipbookPages[0].checked = true;

      // Luego de COVER_DELAY ms empezar a pasar páginas secuencialmente
      setTimeout(() => {
        flipPagesSequentially(AUTO_OPEN_PAGE);
      }, COVER_DELAY);
    }
  });
});
