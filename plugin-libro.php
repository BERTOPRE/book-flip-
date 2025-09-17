<?php
/**
 * Plugin Name: Flipbook Widget Shortcode
 * Description: Genera un flipbook dinámico con shortcode [flipbook_donantes].
 * Version: 1.0.0
 */

if (!defined('ABSPATH'))
    exit;

/* ==============================
 * 1. Consulta datos (DB)
 * ============================== */
function buildPagesData()
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

/* ==============================
 * 2. Construye Flipbook (HTML + CSS dinámico)
 * ============================== */
function buildBook($pagesData)
{

    $URL_COVER = plugin_dir_url(__FILE__) . 'assets/portada.jpg';
    $URL_FRONT_CONTENT = plugin_dir_url(__FILE__) . 'assets/contra-portada.jpg';
    $URL_BACK_CONTENT = plugin_dir_url(__FILE__) . 'assets/hoja-blanco.jpg';
    $URL_EDGE_FLIPBOOK_BG = '/wp-content/uploads/2025/09/flip_book_edge_shading.webp';
    $URL_EDGE_FRONT_SHADE = '/wp-content/uploads/2025/09/front_page_edge_shading.webp';
    $URL_EDGE_BACK_SHADE = '/wp-content/uploads/2025/09/back_page_edge_shading.webp';
    $numberPages = count($pagesData) + 1;
    $out = '<div class="flipbook-widget">';

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
        <img class="edge_shading" src="' . esc_url($URL_EDGE_BACK_SHADE) . '" alt="Edge shading" />
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
        <h2>' . $title . '</h2>
        <ul>';
        foreach ($p['front'] as $name) {
            $out .= '<li>' . esc_html($name) . '</li>';
        }
        $out .= '</ul>
        <img class="front_content" src="' . esc_url($URL_EDGE_FRONT_SHADE) . '" alt="Front shade" />
    </div>';

        // BACK
        $out .= '<div class="back_page">
        <label for="page' . $p['id'] . '_checkbox"></label>
        <h2>' . $title . '</h2>
        <ul>';
        foreach ($p['back'] as $name) {
            $out .= '<li>' . esc_html($name) . '</li>';
        }
        $out .= '</ul>
        <img class="back_content" src="' . esc_url($URL_EDGE_BACK_SHADE) . '" alt="Back shade" />
    </div>';

        $out .= '</div>'; // cierre .page
    }


    $out .= '<div class="back_cover"></div></div></div>';

    // CSS dinámico
    $out .= '<style>';
    for ($i = 1; $i <= $numberPages; $i++) {
        $baseZ = ($numberPages + 1) - $i;
        $out .= ".flipbook-widget #page{$i}{z-index:{$baseZ};}";
        $checkedZ = ($i == $numberPages) ? $numberPages + 4 : $i + 2;
        $out .= ".flipbook-widget #page{$i}_checkbox:checked ~ #flip_book #page{$i}{
            transform:rotateY(-180deg); z-index:{$checkedZ};
        }";
    }
    $out .= '</style>';

    return $out;
}

/* ==============================
 * 3. Shortcode
 * ============================== */
add_shortcode('flipbook_donantes', function () {
    $pagesData = buildPagesData();
    if (empty($pagesData))
        return '<p>No hay registros.</p>';

    // aquí construimos HTML+CSS dinámico
    $html = buildBook($pagesData);

    // y aquí pegamos CSS/JS estático (una sola vez)
    ob_start(); ?>
    <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__); ?>assets/flipbook.css">
    <script src="<?php echo plugin_dir_url(__FILE__); ?>assets/flipbook.js"></script>
    <?php
    return $html . ob_get_clean();
});
