<?php
/**
 * Plugin Name: Flipbook
 * Description: Flipbook para donaciones (dinámico desde Elementor Submissions).
 * Version: 1.0.0
 * Author: solucionweb.com | MP
 * Author URI: https://solucionweb.com
 */

if (!defined('ABSPATH'))
    exit; // seguridad básica

/* ==============================
 * 0. Hooks de activación/desactivación
 * ============================== */
register_activation_hook(__FILE__, function () {
    global $wpdb;
    $table = $wpdb->prefix . 'e_submissions';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('El plugin Flipbook requiere que Elementor Submissions esté activo.');
    }
});

/* ==============================
 * 1. Consulta datos (DB)
 * ============================== */
if (!function_exists('flipbook_build_pages_data')) {
    function flipbook_build_pages_data()
    {
        global $wpdb;

        $FORM_NAME = 'formulario libro';
        $KEY_NAME = 'book_name';

        $t_subm = $wpdb->prefix . 'e_submissions';
        $t_values = $wpdb->prefix . 'e_submissions_values';

        $rows = $wpdb->get_results($wpdb->prepare("
            SELECT s.created_at, v.value
            FROM $t_subm s
            INNER JOIN $t_values v ON v.submission_id = s.id
            WHERE s.form_name = %s
              AND v.key = %s
              AND v.value <> ''
            ORDER BY s.created_at ASC
        ", $FORM_NAME, $KEY_NAME));

        if (!$rows)
            return [];

        $groups = [];
        foreach ($rows as $r) {
            $ts = strtotime($r->created_at);
            $month = wp_date('F', $ts);
            $year = wp_date('Y', $ts);
            $key = $month . '-' . $year;

            if (!isset($groups[$key])) {
                $groups[$key] = ['month' => $month, 'year' => $year, 'items' => []];
            }
            $groups[$key]['items'][] = sanitize_text_field($r->value);
        }

        $pagesData = [];
        $pageId = 2;
        foreach ($groups as $g) {
            $items = $g['items'];
            $i = 0;
            $n = count($items);

            while ($i < $n) {
                $front = array_slice($items, $i, 16);
                $i += count($front);
                $back = array_slice($items, $i, 16);
                $i += count($back);

                $pagesData[] = [
                    'id' => $pageId,
                    'month' => $g['month'],
                    'year' => $g['year'],
                    'front' => $front,
                    'back' => $back
                ];
                $pageId++;
            }
        }
        return $pagesData;
    }
}

/* ==============================
 * 2. Construcción del Flipbook
 * ============================== */
if (!function_exists('flipbook_build_book')) {
    function flipbook_build_book($pagesData)
    {
        $URL_COVER = plugin_dir_url(__FILE__) . 'assets/portada.jpg';
        $URL_FRONT_CONTENT = plugin_dir_url(__FILE__) . 'assets/contra-portada.jpg';
        $URL_BACK_CONTENT = plugin_dir_url(__FILE__) . 'assets/hoja-blanco.jpg';
        $URL_EDGE_FRONT = plugin_dir_url(__FILE__) . 'assets/front_page_edge_shading.webp';
        $URL_EDGE_BACK = plugin_dir_url(__FILE__) . 'assets/back_page_edge_shading.webp';

        $numberPages = count($pagesData) + 1;

        // detectar última página/side con contenido real
        $lastPageId = 1;
        $lastSide = 'front';
        foreach ($pagesData as $p) {
            if (!empty($p['front'])) {
                $lastPageId = $p['id'];
                $lastSide = 'front';
            }
            if (!empty($p['back'])) {
                $lastPageId = $p['id'];
                $lastSide = 'back';
            }
        }

        // === HTML ===
        $out = '<div class="flipbook-widget" data-last-page-id="' . esc_attr($lastPageId) . '" data-last-side="' . esc_attr($lastSide) . '">';
        $out .= '<input type="checkbox" id="cover_checkbox" />';
        $out .= '<input type="checkbox" id="page1_checkbox" />';
        foreach ($pagesData as $p) {
            $out .= '<input type="checkbox" id="page' . $p['id'] . '_checkbox" />';
        }

        // contenedor
        $out .= '<div id="flip_book">';

        // portada
        $out .= '<div class="front_cover">
            <label for="cover_checkbox" id="cover"></label>
            <img class="cover_image" src="' . esc_url($URL_COVER) . '" alt="Portada" />
            <div class="cover_back"></div>
        </div>';

        // página fija
        $out .= '<div class="page" id="page1">
            <div class="front_page">
                <label for="page1_checkbox"></label>
                <img class="front_content" src="' . esc_url($URL_FRONT_CONTENT) . '" alt="Front content" />
            </div>
            <div class="back_page">
                <label for="page1_checkbox"></label>
                <img class="back_content" src="' . esc_url($URL_BACK_CONTENT) . '" alt="Back content" />
            </div>
        </div>';

        // páginas dinámicas
        foreach ($pagesData as $p) {
            $title = esc_html(mb_strtoupper($p['month'], 'UTF-8')) . ' ' . esc_html($p['year']);
            $out .= '<div class="page" id="page' . $p['id'] . '">';

            // FRONT
            $out .= '<div class="front_page">
                <label for="page' . $p['id'] . '_checkbox"></label>
                <h2>' . $title . '</h2><ul>';
            foreach ($p['front'] as $name) {
                $out .= '<li>' . esc_html($name) . '</li>';
            }
            $out .= '</ul>
                <img class="front_content" src="' . esc_url($URL_EDGE_FRONT) . '" alt="Front shade" />
            </div>';

            // BACK
            $out .= '<div class="back_page">
                <label for="page' . $p['id'] . '_checkbox"></label>
                <h2>' . $title . '</h2><ul>';
            foreach ($p['back'] as $name) {
                $out .= '<li>' . esc_html($name) . '</li>';
            }
            $out .= '</ul>
                <img class="back_content" src="' . esc_url($URL_EDGE_BACK) . '" alt="Back shade" />
            </div>';

            $out .= '</div>'; // cierre .page
        }

        $out .= '<div class="back_cover"></div></div>'; // cierre flip_book

        // Barra de controles
        $out .= '<div class="flipbook-toolbar">
                <button class="flip-prev" type="button">&#10094;</button>
                <span class="flip-status"><span class="current-page">1</span> / <span class="total-pages">' . ($numberPages + 1) . '</span></span>
                <button class="flip-next" type="button">&#10095;</button>
            </div>';

        // CSS dinámico inline (solo z-index + rotación)
        $out .= '<style>';
        for ($i = 1; $i <= $numberPages; $i++) {
            $baseZ = ($numberPages + 1) - $i;
            $checkedZ = ($i == $numberPages) ? $numberPages + 4 : $i + 2;
            $out .= ".flipbook-widget #page{$i}{z-index:{$baseZ};}";
            $out .= ".flipbook-widget #page{$i}_checkbox:checked ~ #flip_book #page{$i}{transform:rotateY(-180deg); z-index:{$checkedZ};}";
        }
        $out .= '</style>';

        return $out;
    }
}

/* ==============================
 * 3. Shortcode
 * ============================== */
if (!function_exists('flipbook_shortcode')) {
    function flipbook_shortcode()
    {
        $pagesData = flipbook_build_pages_data();
        if (empty($pagesData)) {
            return '<p>No hay registros.</p>';
        }
        return flipbook_build_book($pagesData);
    }
    add_shortcode('flipbook_donantes', 'flipbook_shortcode');
}

/* ==============================
 * 4. Encolar CSS y JS
 * ============================== */
add_action('wp_enqueue_scripts', function () {
    $url = plugin_dir_url(__FILE__);
    wp_enqueue_style('flipbook-css', $url . 'assets/flipbook.css', [], '1.0.0');
    wp_enqueue_script('flipbook-js', $url . 'assets/flipbook.js', ['jquery'], '1.0.0', true);
});
