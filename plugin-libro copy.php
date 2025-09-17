<?php
/**
 * Plugin Name: Flipbook Donantes (dinámico)
 * Description: Genera flipbook con donantes de Elementor Submissions. 25 nombres por cara. Crea páginas automáticamente (front/back) por mes. Incluye portada y contraportada estáticas.
 * Version: 1.0.0
 */


if (!defined('ABSPATH'))
    exit;

add_shortcode('flipbook_donantes', function () {
    global $wpdb;

    // === CONFIGURACIÓN BÁSICA ===
    $FORM_NAME = 'formulario libro';     // Nombre EXACTO del formulario Elementor
    $KEY_NAME = 'book_name';            // Campo a listar
    // Imágenes (ajusta rutas si cambian)
    $URL_COVER = '/wp-content/uploads/2025/09/Portada-scaled.jpg';
    $URL_FRONT_CONTENT = '/wp-content/uploads/2025/09/Contraportada-scaled.jpg';
    $URL_BACK_CONTENT = '/wp-content/uploads/2025/09/Hoja-interior-scaled.jpg';
    $URL_EDGE_FLIPBOOK_BG = '/wp-content/uploads/2025/09/flip_book_edge_shading.webp';
    $URL_EDGE_FRONT_SHADE = '/wp-content/uploads/2025/09/front_page_edge_shading.webp';
    $URL_EDGE_BACK_SHADE = '/wp-content/uploads/2025/09/back_page_edge_shading.webp';

    // Tablas
    $t_subm = $wpdb->prefix . 'e_submissions';
    $t_values = $wpdb->prefix . 'e_submissions_values';

    // === CONSULTA ===
    $rows = $wpdb->get_results($wpdb->prepare("
        SELECT s.created_at, v.value
        FROM $t_subm s
        INNER JOIN $t_values v ON v.submission_id = s.id
        WHERE s.form_name = %s
          AND v.key = %s
          AND v.value <> ''
        ORDER BY s.created_at ASC
    ", $FORM_NAME, $KEY_NAME));

    if (!$rows) {
        return '<div class="flipbook-widget"><p>No hay registros.</p></div>';
    }

    // === AGRUPAR POR MES-AÑO ===
    $groups = []; // key => ['month'=>'Septiembre','year'=>'2025','items'=>[]]
    foreach ($rows as $r) {
        $ts = strtotime($r->created_at);
        // Usar wp_date para respetar locale de WP
        $month = wp_date('F', $ts);
        $year = wp_date('Y', $ts);
        $key = $month . '-' . $year;
        if (!isset($groups[$key]))
            $groups[$key] = ['month' => $month, 'year' => $year, 'items' => []];
        $groups[$key]['items'][] = sanitize_text_field($r->value);
    }

    // === PREPARAR PÁGINAS DINÁMICAS ===
    // page1 es estática. Dinámicas empiezan en page2.
    $pages = []; // cada item: ['id'=>2, 'month'=>'Septiembre','year'=>'2025','front'=>[], 'back'=>[]]
    $pageId = 1;

    foreach ($groups as $g) {
        $items = $g['items'];
        $i = 0;
        $n = count($items);
        while ($i < $n) {
            $front = array_slice($items, $i, 25);
            $i += count($front);
            $back = array_slice($items, $i, 25);
            $i += count($back);
            $pageId++;
            $pages[] = [
                'id' => $pageId,
                'month' => $g['month'],
                'year' => $g['year'],
                'front' => $front,
                'back' => $back,
            ];
        }
    }

    // === CONSTRUIR SALIDA ===
    ob_start();

    // 1) CONTENEDOR
    ?>
    <div class="flipbook-widget light-mode">
        <!-- CHECKBOXES: deben ir ANTES de #flip_book -->
        <input type="checkbox" id="cover_checkbox" />
        <input type="checkbox" id="page1_checkbox" />
        <?php foreach ($pages as $p): ?>
            <input type="checkbox" id="page<?php echo (int) $p['id']; ?>_checkbox" />
        <?php endforeach; ?>

        <!-- FLIP BOOK -->
        <div id="flip_book">
            <!-- PORTADA (no tocar) -->

            <!-- PORTADA 3D CON DOS CARAS -->
            <div class="cover3d">
                <div class="cover3d__face cover3d__face--front">
                    <img src="<?php echo esc_url($URL_COVER); ?>" alt="Portada">
                </div>

                <div class="cover3d__face cover3d__face--back"></div>

                <!-- Clic para ABRIR (cara frontal) -->
                <label class="cover3d__hit cover3d__hit--front" for="cover_checkbox" aria-label="Abrir portada"></label>

                <!-- Clic para CERRAR (cara trasera) -->
                <label class="cover3d__hit cover3d__hit--back" for="cover_checkbox" aria-label="Cerrar portada"></label>
            </div>

            <!-- PÁGINA 1 (estática tal como la tenía -->
            <div class="page" id="page1">
                <div class="front_page">
                    <label for="page1_checkbox"></label>
                    <h2>SEPTIEMBRE</h2>
                    <p>Salomon Miguel.</p>
                    <img class="front_content" src="<?php echo esc_url($URL_FRONT_CONTENT); ?>" alt="Front content" />
                </div>
                <div class="back_page">
                    <label for="page1_checkbox"></label>
                    <h2>SEPTIEMBRE</h2>
                    <p>Salomon Miguel .</p>
                    <img class="edge_shading" src="<?php echo esc_url($URL_EDGE_BACK_SHADE); ?>"
                        alt="Front page edge shading" />
                    <img class="back_content" src="<?php echo esc_url($URL_BACK_CONTENT); ?>" alt="Back content" />
                </div>
            </div>

            <!-- PÁGINAS DINÁMICAS -->
            <?php foreach ($pages as $p): ?>
                <div class="page" id="page<?php echo (int) $p['id']; ?>">
                    <div class="front_page">
                        <label for="page<?php echo (int) $p['id']; ?>_checkbox"></label>
                        <h2><?php echo esc_html(mb_strtoupper($p['month'], 'UTF-8')); ?></h2>
                        <h3><?php echo esc_html($p['year']); ?></h3>
                        <ul>
                            <?php foreach ($p['front'] as $name): ?>
                                <li><?php echo esc_html($name); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <img class="front_content" src="<?php echo esc_url($URL_EDGE_FRONT_SHADE); ?>" alt="Front shade" />
                    </div>
                    <div class="back_page">
                        <label for="page<?php echo (int) $p['id']; ?>_checkbox"></label>
                        <h2><?php echo esc_html(mb_strtoupper($p['month'], 'UTF-8')); ?></h2>
                        <h3><?php echo esc_html($p['year']); ?></h3>
                        <ul>
                            <?php foreach ($p['back'] as $name): ?>
                                <li><?php echo esc_html($name); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <img class="back_content" src="<?php echo esc_url($URL_EDGE_BACK_SHADE); ?>" alt="Back shade" />
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- CONTRAPORTADA -->
            <div class="back_cover"></div>
        </div>

        <!-- BOTÓN MODO -->
        <button id="toggle-mode-btn">
            <i class="bi bi-moon-stars-fill"></i>
        </button>

        <!-- ADVERTENCIA RESPONSIVE -->
        <div id="responsive-warning">
            <i class="ri-error-warning-line warning-icons"></i>
            <p>This web application is not optimized for mobile.</p>
            <p>Please visit it on a desktop computer for the best experience.</p>
        </div>

        <style>
            /* ====== RESETEO ====== */
            .flipbook-widget *,
            .flipbook-widget *::before,
            .flipbook-widget *::after {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            /* ====== CONTENEDOR ====== */
            .flipbook-widget {
                --dark-color: rgb(2, 4, 8);
                --dark-hover: rgba(255, 255, 255, 0.1);
                --light-color: rgb(245, 245, 245);
                --light-hover: rgba(0, 0, 0, 0.1);
                width: 100%;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                font-family: monospace, sans-serif;
                font-size: .5rem;
                transition: background-color .3s;
                position: relative;
            }

            .flipbook-widget.light-mode {
                color: var(--dark-color);
                background: var(--light-color);
            }

            .flipbook-widget.light-mode #toggle-mode-btn:hover {
                background: var(--light-hover);
            }

            .flipbook-widget.dark-mode {
                color: var(--light-color);
                background: var(--dark-color);
            }

            .flipbook-widget.dark-mode #toggle-mode-btn:hover {
                background: var(--dark-hover);
            }

            .flipbook-widget a {
                text-decoration: none;
                color: var(--dark-color);
            }

            .flipbook-widget input {
                display: none;
            }

            /* Botón modo */
            .flipbook-widget #toggle-mode-btn {
                width: 2rem;
                height: 2rem;
                position: absolute;
                top: 1rem;
                right: 1rem;
                display: flex;
                justify-content: center;
                align-items: center;
                font-size: 1rem;
                border: none;
                border-radius: .5rem;
                background: transparent;
                transition: background-color .3s;
                cursor: pointer;
                z-index: 1000;
            }

            /* Aviso responsive */
            .flipbook-widget #responsive-warning {
                width: 100%;
                height: 100%;
                padding: 2rem;
                position: absolute;
                top: 0;
                left: 0;
                display: none;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 2rem;
                font-size: 1rem;
                text-align: center;
                z-index: 9999;
            }

            .flipbook-widget .warning-icons {
                font-size: 10rem;
            }

            @media (max-width: 768px) {
                .flipbook-widget #responsive-warning.show {
                    display: flex;
                }
            }

            /* ====== LIBRO ====== */
            .flipbook-widget #flip_book {
                width: 298px;
                height: 420px;
                position: relative;
                transition-duration: 1s;
                perspective: 2000px;
                /* clave para 3D */
            }

            /* ====== PÁGINAS ====== */
            .flipbook-widget .page {
                width: 288px;
                height: 400px;
                position: absolute;
                top: 10px;
                left: 1px;
                border-radius: 0 5px 5px 0;
                background: #fff;
                transform-origin: left;
                transform-style: preserve-3d;
                transform: rotateY(0deg);
                transition-duration: .5s;
            }

            .flipbook-widget .front_page,
            .flipbook-widget .back_page {
                position: absolute;
                inset: 0;
                -webkit-backface-visibility: hidden;
                backface-visibility: hidden;
            }

            .flipbook-widget .front_page label,
            .flipbook-widget .back_page label {
                position: absolute;
                inset: 0;
                cursor: pointer;
                z-index: 100;
            }

            .flipbook-widget .back_page {
                transform: rotateY(180deg);
            }

            .flipbook-widget .edge_shading {
                width: 288px;
                height: 400px;
                position: absolute;
                z-index: 98;
            }

            .flipbook-widget .front_content,
            .flipbook-widget .back_content {
                width: 287px;
                height: 398px;
                position: absolute;
                top: 1px;
                border-radius: 0 5px 5px 0;
                z-index: 97;
            }

            .flipbook-widget .back_content {
                left: 1px;
                border-radius: 5px 0 0 5px;
            }

            /* Contraportada (simple) */
            .flipbook-widget .back_cover {
                width: 100%;
                height: 100%;
                position: relative;
                z-index: -1;
                border-radius: 2.5px 5px 5px 2.5px;
                background-image: url(<?php echo esc_url($URL_EDGE_FLIPBOOK_BG); ?>);
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                background-color: rgb(220, 20, 60);
                box-shadow: 0 0 5px 0 rgba(25, 25, 25, .25);
            }

            /* z-index base de la página 1 */
            .flipbook-widget #page1 {
                z-index: 8;
            }

            /* ====== INTERACCIÓN GLOBAL ====== */
            .flipbook-widget #cover_checkbox:checked~#flip_book {
                transform: translateX(144px);
            }

            .flipbook-widget #page1_checkbox:checked~#flip_book #page1 {
                transform: rotateY(-180deg);
                z-index: 99;
            }

            /* Títulos y listas */
            .front_page h2,
            .back_page h2 {
                padding-top: 20px;
                text-align: center;
            }

            .front_page h3,
            .back_page h3 {
                text-align: center;
            }

            .front_page ul,
            .back_page ul {
                margin-top: 10px;
                padding: 0 10px;
                max-height: 330px;
                overflow: hidden;
            }

            .front_page li,
            .back_page li {
                list-style: none;
                text-align: center;
                line-height: 1.35;
            }

            /* Sombreado en back por encima */
            .flipbook-widget .back_page .edge_shading {
                position: absolute;
                inset: 0;
                z-index: 99;
                pointer-events: none;
            }

            /* ====== TAPA 3D CON DOS CARAS ====== */
            /* Marco/soporte de la tapa */
            .flipbook-widget .cover3d {
                position: absolute;
                inset: 0;
                transform-origin: center left;
                transform-style: preserve-3d;
                transition: transform .8s ease;
                z-index: 99;
                /* SIEMPRE arriba para poder cerrar */
                pointer-events: none;
                /* no bloquea clicks salvo en las "hits" */

                border-radius: 2.5px 5px 5px 2.5px;
                background-image: url(<?php echo esc_url($URL_EDGE_FLIPBOOK_BG); ?>);
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                box-shadow: 0 0 5px 0 rgba(25, 25, 25, .25);
            }

            /* Caras de la tapa */
            .flipbook-widget .cover3d__face {
                position: absolute;
                inset: 0;
                border-radius: inherit;
                -webkit-backface-visibility: hidden;
                backface-visibility: hidden;
            }

            .flipbook-widget .cover3d__face--front {
                transform: rotateY(0deg) translateZ(0.1px);
                display: flex;
            }

            .flipbook-widget .cover3d__face--front img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                border-radius: inherit;
            }

            .flipbook-widget .cover3d__face--back {
                background: crimson;
                /* 🔴 la cara interior roja */
                transform: rotateY(180deg) translateZ(0.1px);
            }

            /* Zonas clickeables (una por cada cara) */
            .flipbook-widget .cover3d__hit {
                position: absolute;
                inset: 0;
                pointer-events: auto;
                /* activas aunque el padre tenga none */
                cursor: pointer;
                -webkit-backface-visibility: hidden;
                backface-visibility: hidden;
            }

            /* Clic para ABRIR (frontal) */
            .flipbook-widget .cover3d__hit--front {
                transform: rotateY(0deg) translateZ(.3px);
            }

            /* Clic para CERRAR (trasera) */
            .flipbook-widget .cover3d__hit--back {
                transform: rotateY(180deg) translateZ(.3px);
            }

            /* (Opcional) Si no querés tapar la página al abrir, usá una franja angosta:
                                                                                                    .flipbook-widget .cover3d__hit{ inset:0 auto 0 0; width:24px; }
                                                                                                    */

            /* Abrir/Cerrar tapa (sin tocar z-index) */
            .flipbook-widget #cover_checkbox:checked~#flip_book .cover3d {
                transform: rotateY(-180deg);
            }

            /* Cuando se abre la tapa, mándala al fondo después de la animación */
            .flipbook-widget #cover_checkbox:checked~#flip_book .cover3d {
                transform: rotateY(-180deg);
                z-index: 1;
                transition: transform .8s ease, z-index 0s .8s;
                /* baja el z-index al terminar el giro */
            }

            /* ===== Candado de páginas ===== */
            .flipbook-widget.pages-locked .front_page label,
            .flipbook-widget.pages-locked .back_page label {
                pointer-events: none !important;
                /* evita clics en páginas bloqueadas */
            }

            /* Bloquea toda interacción mientras algo está animando (tapa o páginas) */
            .flipbook-widget.is-animating .cover3d__hit,
            .flipbook-widget.is-animating .front_page label,
            .flipbook-widget.is-animating .back_page label {
                pointer-events: none !important;
            }

            /* Bloqueo de páginas cuando la tapa está cerrada o mientras hay animación */
            .flipbook-widget.pages-locked .front_page label,
            .flipbook-widget.pages-locked .back_page label {
                pointer-events: none !important;
            }

            .flipbook-widget.is-animating .cover3d__hit,
            .flipbook-widget.is-animating .front_page label,
            .flipbook-widget.is-animating .back_page label {
                pointer-events: none !important;
            }
        </style>

        <?php
        // 2) CSS dinámico: z-index base y regla de flip para cada página dinámica
        // Asignamos z-index base decreciente para que queden apiladas detrás de page1
        $dynamic_css = '';
        $base = 7; // debajo de page1 (que es 8)
        foreach ($pages as $idx => $p) {
            $id = (int) $p['id'];
            $z = max(1, $base - $idx); // si hay MUCHAS páginas, no importa que llegue a 1
            $zOn = 200 + $idx;           // cuando se gira, que suba al frente
    
            $dynamic_css .= "
            .flipbook-widget #page{$id} { z-index: {$z}; }
            .flipbook-widget #page{$id}_checkbox:checked ~ #flip_book #page{$id} {
                transform: rotateY(-180deg);
                z-index: {$zOn};
            }";
        }
        echo '<style id="flipbook-dynamic-css">' . $dynamic_css . '</style>';
        ?>

        <script>
            /*********************
             * RESPONSIVE WARNING
             *********************/
            (function () {
                const responsiveWarning = document.getElementById("responsive-warning");
                const responsiveDesign = false;
                if (!responsiveDesign && window.innerWidth <= 768) {
                    responsiveWarning.classList.add("show");
                }
            })();

            /***********************
             * MODO CLARO/OSCURO
             ***********************/
            (function () {
                const toggleModeBtn = document.getElementById("toggle-mode-btn");
                const body = document.body;
                const responsiveWarning = document.getElementById("responsive-warning");

                function applyMode(mode) {
                    body.classList.remove("light-mode", "dark-mode");
                    body.classList.add(mode);
                    if (mode === "dark-mode") {
                        toggleModeBtn.style.color = "rgb(245,245,245)";
                        toggleModeBtn.innerHTML = '<i class="bi bi-sun-fill"></i>';
                        responsiveWarning.style.backgroundColor = "rgb(2,4,8)";
                    } else {
                        toggleModeBtn.style.color = "rgb(2,4,8)";
                        toggleModeBtn.innerHTML = '<i class="bi bi-moon-stars-fill"></i>';
                        responsiveWarning.style.backgroundColor = "rgb(245,245,245)";
                    }
                }

                let savedMode = localStorage.getItem("mode");
                if (savedMode === null) savedMode = "light-mode";
                applyMode(savedMode);

                toggleModeBtn.addEventListener("click", function () {
                    const newMode = body.classList.contains("light-mode") ? "dark-mode" : "light-mode";
                    applyMode(newMode);
                    localStorage.setItem("mode", newMode);
                });
            })();


        </script>
    </div>
    <script>
        (function () {
            const widget = document.querySelector('.flipbook-widget');
            if (!widget) return;

            const coverCB = document.getElementById('cover_checkbox');
            const coverEl = document.querySelector('#flip_book .cover3d');
            const flipbook = document.getElementById('flip_book');

            // Todos los checkboxes de páginas (excluye la tapa)
            const pageCBs = Array.from(
                document.querySelectorAll('.flipbook-widget > input[type="checkbox"]')
            ).filter(cb => cb.id !== 'cover_checkbox');

            // === Helpers de estado ===
            const lockPages = (lock) => {
                widget.classList.toggle('pages-locked', !!lock);
                pageCBs.forEach(cb => cb.disabled = !!lock); // doble seguro
            };
            const setAnimating = (on) => widget.classList.toggle('is-animating', !!on);

            // Espera segura a fin de transición de transform (con timeout fallback)
            const waitTransformEnd = (el, timeoutMs, done) => {
                let finished = false;
                const off = () => {
                    if (finished) return;
                    finished = true;
                    el.removeEventListener('transitionend', onEnd, true);
                    clearTimeout(tid);
                    done && done();
                };
                const onEnd = (ev) => {
                    if (ev.target === el && ev.propertyName === 'transform') off();
                };
                const tid = setTimeout(off, timeoutMs);
                el.addEventListener('transitionend', onEnd, true);
            };

            // Lee duración real desde CSS (soporta "0.8s" y "500ms", múltiples)
            const parseMs = (str) => (str || '').split(',')
                .map(s => s.trim())
                .reduce((max, part) => {
                    if (part.endsWith('ms')) return Math.max(max, parseFloat(part));
                    if (part.endsWith('s')) return Math.max(max, parseFloat(part) * 1000);
                    return max;
                }, 0);

            const COVER_MS = Math.max(900, parseMs(getComputedStyle(coverEl).transitionDuration)); // .8s en tu CSS
            const anyPage = document.querySelector('.page');
            const PAGE_MS = anyPage ? Math.max(600, parseMs(getComputedStyle(anyPage).transitionDuration)) : 600;

            // ===== Estado inicial: tapa cerrada => páginas bloqueadas (o abiertas => habilitadas)
            lockPages(!coverCB.checked);

            // ===== Abrir/Cerrar tapa manual (clic en tapa)
            if (coverCB && coverEl) {
                coverCB.addEventListener('change', () => {
                    setAnimating(true);

                    if (coverCB.checked) {
                        // Abrir: habilitar páginas SOLO al terminar el giro
                        waitTransformEnd(coverEl, COVER_MS, () => {
                            lockPages(false);
                            setAnimating(false);
                        });
                    } else {
                        // Cerrar: bloquear y desmarcar todas las páginas inmediatamente
                        lockPages(true);
                        pageCBs.forEach(cb => { cb.checked = false; });
                        waitTransformEnd(coverEl, COVER_MS, () => setAnimating(false));
                    }
                });
            }

            // ===== AUTO-ABRIR coordinado con el candado (sin interferencias) =====
            // Índice de páginas a abrir automáticamente CONTANDO desde 0: 0=tapa, 1=page1, 2=page2, 3=page3...
            const AUTO_OPEN_TARGET = 3;        // igual que usabas antes
            const AFTER_COVER_DELAY = 200;     // pequeño colchón tras la tapa
            const BETWEEN_PAGES_MS = PAGE_MS + 40; // no empalmar transiciones
            let autoRan = false, autoCanceled = false;

            // Anula auto si el usuario interactúa
            const cancelAuto = () => { autoCanceled = true; };
            ['click', 'keydown', 'wheel', 'touchstart'].forEach(evt =>
                widget.addEventListener(evt, cancelAuto, { once: true, passive: true })
            );

            const isHalfVisible = (el) => {
                const r = el.getBoundingClientRect();
                const vh = window.innerHeight || document.documentElement.clientHeight;
                const mid = r.top + r.height / 2;
                return mid >= 0 && mid <= vh;
            };

            const flipPageByIndex = (i, done) => {
                // i=1 => page1_checkbox, i=2 => page2_checkbox, etc.
                const id = (i === 1) ? 'page1_checkbox' : `page${i}_checkbox`;
                const cb = document.getElementById(id);
                if (!cb) return done && done();
                setAnimating(true);
                cb.checked = true;
                setTimeout(() => { setAnimating(false); done && done(); }, BETWEEN_PAGES_MS);
            };

            const autoOpen = () => {
                if (autoRan || autoCanceled || !flipbook) return;
                if (!isHalfVisible(flipbook)) return;

                autoRan = true; // solo una vez
                // 1) Abrir tapa
                setAnimating(true);
                coverCB.checked = true;
                waitTransformEnd(coverEl, COVER_MS, () => {
                    lockPages(false);
                    setAnimating(false);

                    // 2) Pasar páginas de 1 hasta AUTO_OPEN_TARGET (si existe)
                    let i = 1;
                    const limit = Math.max(1, Math.min(AUTO_OPEN_TARGET, pageCBs.length)); // seguridad
                    const step = () => {
                        if (autoCanceled || i > limit) return;
                        setTimeout(() => flipPageByIndex(i++, step), (i === 1 ? AFTER_COVER_DELAY : 0));
                    };
                    step();
                });

                window.removeEventListener('scroll', onScroll, { passive: true });
            };

            const onScroll = () => { if (!autoRan && !autoCanceled) autoOpen(); };
            window.addEventListener('scroll', onScroll, { passive: true });

            // Si ya está visible al cargar (sin hacer scroll)
            if (isHalfVisible(flipbook)) autoOpen();
        })();
    </script>



    <?php

    return ob_get_clean();
});

