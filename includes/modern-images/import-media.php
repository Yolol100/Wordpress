<?php
declare(strict_types=1);

// Blokkeer directe toegang
defined('ABSPATH') || exit;

/**
 * Class Modern_Image_Manager_Import
 *
 * Verwerkt de import van CSV-bestanden voor media.
 */
class Modern_Image_Manager_Import {

    /**
     * Rendert de importpagina en handelt de import af indien nodig.
     *
     * @return void
     */
    public static function render_import_page(): void {
        // Alleen gebruikers met upload-rechten mogen hier in
        if ( ! current_user_can( 'upload_files' ) ) {
            wp_die( esc_html__( 'Onvoldoende rechten om media te importeren.', 'modern-image-manager' ) );
        }

        // Eerst verwerken als er gepost is
        if ( isset( $_POST['import_csv'] ) ) {
            self::handle_import();
        }

        // Daarna het template tonen (nu uit de plugin-root /templates)
        include MODERN_IMAGE_MANAGER_PATH . 'templates/import-page.php';
    }

    /**
     * Verwerkt het geüploade CSV-bestand en werkt bestaande of nieuwe bijlagen bij.
     *
     * @return void
     */
    public static function handle_import(): void {
        // Check nonce & capabilities
        if (
            empty( $_POST['modern_image_import_nonce'] )
            || ! wp_verify_nonce( $_POST['modern_image_import_nonce'], 'modern_image_import' )
            || ! current_user_can( 'upload_files' )
        ) {
            wp_die( esc_html__( 'Beveiligingscheck mislukt.', 'modern-image-manager' ) );
        }

        if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
            wp_die( esc_html__( 'Geen bestand geüpload.', 'modern-image-manager' ) );
        }

        // Lees CSV
        $file    = $_FILES['import_file']['tmp_name'];
        $csvData = @array_map( 'str_getcsv', file( $file ) );
        if ( empty( $csvData ) || ! is_array( $csvData ) ) {
            wp_die( esc_html__( 'Ongeldig CSV-bestand.', 'modern-image-manager' ) );
        }

        // Headers validatie
        $headers = array_shift( $csvData );
        if ( ! is_array( $headers ) || empty( $headers ) ) {
            wp_die( esc_html__( 'Ongeldige CSV headers.', 'modern-image-manager' ) );
        }

        // WP-includes
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        foreach ( $csvData as $row ) {
            // Alleen verwerken als kolom-aantal klopt
            if ( count( $row ) !== count( $headers ) ) {
                continue;
            }

            $data = array_combine( $headers, $row );
            if ( empty( $data['Bestandsnaam'] ) ) {
                continue;
            }

            // Sanitatie van bestandsnamen
            $origFilename = sanitize_file_name( $data['Bestandsnaam'] );
            $newFilename  = '';
            if ( ! empty( $data['Nieuwe bestandsnaam'] ) ) {
                $newFilename = sanitize_file_name( pathinfo( $data['Nieuwe bestandsnaam'], PATHINFO_FILENAME ) );
            }

            $attachment = self::get_attachment_by_filename( $origFilename );
            if ( $attachment ) {
                $attachmentId = (int) $attachment->ID;
                if ( $newFilename ) {
                    self::rename_media_file( $attachmentId, $newFilename );
                }
                self::update_attachment_data( $attachmentId, $data );
            } else {
                self::create_new_attachment( $data, $origFilename );
            }
        }

        echo '<div class="notice notice-success"><p>' 
            . esc_html__( 'Import voltooid en alle waarden overschreven!', 'modern-image-manager' )
            . '</p></div>';
    }

    /**
     * Hernoemt een mediabestand en update metadata.
     *
     * @param int    $attachmentId
     * @param string $newFilename
     * @return void
     */
    private static function rename_media_file( int $attachmentId, string $newFilename ): void {
        $oldPath   = get_attached_file( $attachmentId );
        $uploadDir = wp_upload_dir();

        if ( ! $oldPath || ! file_exists( $oldPath ) ) {
            return;
        }

        $ext              = pathinfo( $oldPath, PATHINFO_EXTENSION );
        $newCleanFilename = $newFilename . '.' . $ext;
        $newFullPath      = trailingslashit( $uploadDir['path'] ) . $newCleanFilename;

        if ( ! function_exists( 'WP_Filesystem' ) ) {
            rename( $oldPath, $newFullPath );
        } else {
            global $wp_filesystem;
            WP_Filesystem();
            $wp_filesystem->move( $oldPath, $newFullPath );
        }

        update_attached_file( $attachmentId, $newFullPath );
        $meta           = wp_get_attachment_metadata( $attachmentId );
        $meta['file']   = ltrim( str_replace( $uploadDir['basedir'], '', $newFullPath ), '/' );
        wp_update_attachment_metadata( $attachmentId, $meta );

        wp_update_post( [
            'ID'         => $attachmentId,
            'guid'       => trailingslashit( $uploadDir['baseurl'] ) . $newCleanFilename,
            'post_name'  => sanitize_title( $newCleanFilename ),
            'post_title' => sanitize_text_field( $newFilename ),
        ] );
    }

    /**
     * Update titel, excerpt, content en alt-tag.
     *
     * @param int   $attachmentId
     * @param array $data
     * @return void
     */
    private static function update_attachment_data( int $attachmentId, array $data ): void {
        $fields = [];

        if ( ! empty( $data['Titel'] ) ) {
            $fields['post_title'] = sanitize_text_field( $data['Titel'] );
        }
        if ( ! empty( $data['Caption'] ) ) {
            $fields['post_excerpt'] = sanitize_text_field( $data['Caption'] );
        }
        if ( ! empty( $data['Description'] ) ) {
            $fields['post_content'] = wp_kses_post( $data['Description'] );
        }
        if ( $fields ) {
            $fields['ID'] = $attachmentId;
            wp_update_post( $fields );
        }
        if ( ! empty( $data['Alt-tag'] ) ) {
            update_post_meta( $attachmentId, '_wp_attachment_image_alt', sanitize_text_field( $data['Alt-tag'] ) );
        }
    }

    /**
     * Haalt bijlage op via GUID LIKE.
     *
     * @param string $filename
     * @return \WP_Post|null
     */
    private static function get_attachment_by_filename( string $filename ): ?WP_Post {
        global $wpdb;
        $like = '%' . $wpdb->esc_like( $filename );
        $row  = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE post_type = 'attachment'
                 AND guid LIKE %s
                 LIMIT 1",
                $like
            )
        );
        return $row ? get_post( (int) $row->ID ) : null;
    }

    /**
     * Bepaal MIME-type, altijd string of null.
     *
     * @param string $filename
     * @return string|null
     */
    private static function get_mime_type( string $filename ): ?string {
        $uploadDir = wp_upload_dir();
        $file      = trailingslashit( $uploadDir['basedir'] ) . $filename;
        $mime      = null;

        if ( function_exists( 'mime_content_type' ) ) {
            $res = @mime_content_type( $file );
            if ( false !== $res ) {
                $mime = $res;
            }
        }
        if ( empty( $mime ) ) {
            $ft   = wp_check_filetype( basename( $filename ), null );
            $mime = $ft['type'] ?? null;
        }

        return $mime;
    }

    /**
     * Maak nieuwe bijlage aan.
     *
     * @param array  $data
     * @param string $filename
     * @return void
     */
    private static function create_new_attachment( array $data, string $filename ): void {
        $mimeType = self::get_mime_type( $filename );
        if ( ! $mimeType ) {
            return;
        }

        $uploadDir = wp_upload_dir();
        $path      = trailingslashit( $uploadDir['basedir'] ) . $filename;
        if ( ! file_exists( $path ) ) {
            return;
        }

        $post = [
            'guid'           => trailingslashit( $uploadDir['baseurl'] ) . $filename,
            'post_mime_type' => $mimeType,
            'post_title'     => sanitize_text_field( $data['Titel'] ?? '' ),
            'post_content'   => wp_kses_post( $data['Description'] ?? '' ),
            'post_excerpt'   => sanitize_text_field( $data['Caption'] ?? '' ),
            'post_status'    => 'inherit',
        ];

        $id = wp_insert_attachment( $post, $path );
        if ( $id ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $meta = wp_generate_attachment_metadata( $id, $path );
            wp_update_attachment_metadata( $id, $meta );
            if ( ! empty( $data['Alt-tag'] ) ) {
                update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $data['Alt-tag'] ) );
            }
        }
    }
}