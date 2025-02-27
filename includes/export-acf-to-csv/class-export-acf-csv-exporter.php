<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ACF_Exporter {

    /**
     * Exporteer ACF-gegevens naar CSV.
     *
     * @param string $post_type         Het post type.
     * @param array  $page_ids          Geselecteerde pagina-ID's.
     * @param array  $fields_to_export  Velden die geëxporteerd moeten worden.
     * @param string $export_format     Exportformaat (standaard 'csv').
     */
    public function export(string $post_type, array $page_ids = [], array $fields_to_export = [], string $export_format = 'csv'): void {
        // Haal de berichten op die overeenkomen met de geselecteerde pagina's
        $posts = get_posts([
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'post__in'       => $page_ids, // Filter op geselecteerde pagina-ID's
        ]);

        if (empty($posts)) {
            wp_die(__('Geen berichten gevonden voor het geselecteerde post type of de geselecteerde pagina\'s.', 'export-acf-csv'));
        }

        // Filter velden om niet-relevante typen (zoals "tab") uit te sluiten
        $fields = array_filter($fields_to_export, function ($key) {
            $field_object = get_field_object($key);
            return $field_object && (($field_object['type'] ?? '') !== 'tab');
        });

        // Bouw headers voor de CSV met veldnamen
        $headers = array_merge(
            ['Post ID', 'Titel'],
            array_map(function ($key) {
                $field_object = get_field_object($key);
                return $field_object['name'] ?? $key; // Gebruik veldnaam of sleutel
            }, $fields)
        );

        $data = [$headers];

        // Bouw rijen voor de CSV
        foreach ($posts as $post_id) {
            $row = [$post_id, get_the_title($post_id)];

            foreach ($fields as $field_key) {
                $value     = get_field($field_key, $post_id);
                $formatted = $this->format_field_value($value);
                $row[]     = $formatted;
            }

            $data[] = $row;
        }

        if (count($data) <= 1) {
            wp_die(__('Geen gegevens gevonden voor de geselecteerde velden.', 'export-acf-csv'));
        }

        $this->export_to_csv('acf_export_' . current_time('Y_m_d_H_i_s'), $data);
    }

    /**
     * Formatteer de veldwaarde.
     *
     * @param mixed $value De oorspronkelijke waarde.
     * @return string De geformatteerde waarde.
     */
    private function format_field_value($value): string {
        if (is_array($value)) {
            // Controleer op URL
            if (isset($value['url'])) {
                return $value['url'];
            }
            return wp_json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        if (is_object($value)) {
            return wp_json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return sanitize_text_field($value ?? '');
    }

    /**
     * Exporteer de data naar een CSV-bestand.
     *
     * @param string $filename Naam van het bestand.
     * @param array  $data     De te exporteren data.
     */
    private function export_to_csv(string $filename, array $data): void {
        // Stuur CSV-headers
        set_time_limit(0);
        nocache_headers();

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . esc_attr($filename) . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

        // Schrijf rijen naar CSV
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Importeer ACF-velden vanuit een CSV-bestand.
     *
     * @param string $filename Het pad naar het CSV-bestand.
     */
    public function import_from_csv(string $filename): void {
        if (!is_readable($filename)) {
            wp_die(__('Het CSV-bestand bestaat niet of kan niet worden gelezen.', 'export-acf-csv'));
        }

        if (($file = fopen($filename, 'r')) === false) {
            wp_die(__('Kan het CSV-bestand niet openen.', 'export-acf-csv'));
        }

        $header = fgetcsv($file);
        if (!$header || count($header) < 2) {
            wp_die(__('Leeg of ongeldig CSV-bestand.', 'export-acf-csv'));
        }

        $fields = array_slice($header, 2);

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            $post_id = intval($row[0]);
            if ($post_id <= 0) {
                continue;
            }

            foreach ($fields as $index => $field_key) {
                $value = $row[$index + 2] ?? '';
                if ($value === '' && $value !== '0') {
                    continue;
                }

                $attachment_id = filter_var($value, FILTER_VALIDATE_URL) ? $this->get_attachment_id_from_url($value) : false;
                $decoded_value = json_decode($value, true);

                update_field(
                    $field_key,
                    $attachment_id ?: (json_last_error() === JSON_ERROR_NONE ? $decoded_value : $value),
                    $post_id
                );
            }
        }

        fclose($file);

        wp_safe_redirect(add_query_arg('acf_import_success', 1, admin_url('admin.php?page=export-acf-csv-import')));
        exit;
    }

    /**
     * Haal het attachment-ID op aan de hand van een URL.
     *
     * @param string $url De URL van de attachment.
     * @return int Het attachment-ID.
     */
    private function get_attachment_id_from_url(string $url): int {
        global $wpdb;
        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE guid = %s AND post_type = 'attachment'",
            $url
        )));
    }
}