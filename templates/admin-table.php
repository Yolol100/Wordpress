<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

// Haal media-items op
$media_items    = modern_image_manager_get_media_items();
$current_filter = $_GET['pageFilter'] ?? 'all';
?>

<div class="wrap unique-wrap">
    <form method="POST" id="mim-overview-bulk-form">
        <?php wp_nonce_field( 'modern_image_manager_bulk_delete', 'modern_image_manager_bulk_delete_nonce' ); ?>
        <input type="hidden" name="pageFilter" value="<?php echo esc_attr( $current_filter ); ?>" />

        <!-- Filter balk bovenaan -->
        <div class="filter-top-container">
            <div class="filter-header filter-top-header">
                <div class="nav-controls filter-top-nav-controls">
                    <select
                        name="pageFilter"
                        id="pageFilter"
                        class="page-filter-select filter-top-page-filter"
                    >
                        <option value="all"      <?php selected( $current_filter, 'all' ); ?>>
                            <?php echo esc_html__( "Alle pagina's", "modern-image-manager" ); ?>
                        </option>
                        <option value="linked"   <?php selected( $current_filter, 'linked' ); ?>>
                            <?php echo esc_html__( "Gekoppelde pagina's", "modern-image-manager" ); ?>
                        </option>
                        <option value="unlinked" <?php selected( $current_filter, 'unlinked' ); ?>>
                            <?php echo esc_html__( "Niet gekoppelde pagina's", "modern-image-manager" ); ?>
                        </option>
                    </select>

                    <button
                        type="button"
                        id="applyFilter"
                        class="page-button filter-top-apply-button"
                    >
                        <?php echo esc_html__( "Filter toepassen", "modern-image-manager" ); ?>
                    </button>

                    <button
                        type="submit"
                        name="bulk_delete"
                        id="mim-bulk-delete"
                        class="page-button filter-top-apply-button"
                        disabled
                    >
                        <?php esc_html_e( 'Verwijder geselecteerde afbeeldingen', 'modern-image-manager' ); ?>
                    </button>

                    <div class="pagination-navigation filter-top-pagination">
                        <button id="prevPage" class="page-button filter-top-prev-button">
                            <?php echo esc_html__( "Vorige", "modern-image-manager" ); ?>
                        </button>
                        <span id="currentPage" class="filter-top-current-page">1</span> /
                        <span id="totalPages" class="filter-top-total-pages">50</span>
                        <button id="nextPage" class="page-button filter-top-next-button">
                            <?php echo esc_html__( "Volgende", "modern-image-manager" ); ?>
                        </button>
                    </div>
                </div>

                <div class="search-container filter-top-search-container">
                    <input
                        type="text"
                        id="search-input"
                        class="search-box filter-top-search-box"
                        placeholder="<?php echo esc_attr__( "Zoeken…", "modern-image-manager" ); ?>"
                        onkeyup="filterTable()"
                    />
                </div>

                <div class="rows-controls filter-top-rows-controls">
                    <div class="rows-per-page-container filter-top-rows-per-page-container">
                        <label for="rowsPerPage" class="filter-top-rows-per-page-label">
                            <?php echo esc_html__( "Rijen per pagina:", "modern-image-manager" ); ?>
                        </label>
                        <select
                            id="rowsPerPage"
                            name="per_page"
                            class="rows-per-page-select filter-top-rows-per-page-select"
                            onchange="filterTable()"
                        >
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="30" selected>30</option>
                            <option value="40">40</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped unique-table media-items-table">
            <thead class="unique-thead media-items-thead">
                <tr class="unique-tr-head media-items-tr-head">
                    <th style="width:2%;"><input type="checkbox" id="mim-select-all" /></th>
                    <th><?php echo esc_html__( "Bestandsnaam", "modern-image-manager" ); ?></th>
                    <th><?php echo esc_html__( "Nieuwe bestandsnaam", "modern-image-manager" ); ?></th>
                    <th><?php echo esc_html__( "Pagina", "modern-image-manager" ); ?></th>
                    <th><?php echo esc_html__( "Titel", "modern-image-manager" ); ?></th>
                    <th><?php echo esc_html__( "Caption", "modern-image-manager" ); ?></th>
                    <th><?php echo esc_html__( "Description", "modern-image-manager" ); ?></th>
                    <th><?php echo esc_html__( "Alt-tag", "modern-image-manager" ); ?></th>
                    <th><?php echo esc_html__( "Opslaan", "modern-image-manager" ); ?></th>
                    <th><?php echo esc_html__( "Verwijderen", "modern-image-manager" ); ?></th>
                </tr>
            </thead>
            <tbody class="unique-tbody media-items-tbody">
                <?php if ( $media_items->have_posts() ) : ?>
                    <?php while ( $media_items->have_posts() ) : $media_items->the_post(); ?>
                        <?php
                        $attachment_id = (int) get_the_ID();
                        $attachment    = get_post( $attachment_id );
                        if ( ! $attachment ) {
                            continue;
                        }
                        $alt_text    = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                        $caption     = $attachment->post_excerpt;
                        $description = $attachment->post_content;
                        $title       = $attachment->post_title;
                        $page_title  = modern_image_manager_get_attached_post_title( $attachment_id );
                        $path        = get_attached_file( $attachment_id );
                        $filename    = pathinfo( $path, PATHINFO_FILENAME );
                        $status      = ( strpos( $page_title, 'Niet gekoppeld' ) !== false ) ? 'unlinked' : 'linked';
                        ?>
                        <tr
                            class="unique-tr-item media-items-tr-item unique-tr-<?php echo esc_attr( $attachment_id ); ?>"
                            data-status="<?php echo esc_attr( $status ); ?>"
                        >
                            <td>
                                <input
                                    type="checkbox"
                                    class="mim-select-row"
                                    name="delete_ids[]"
                                    value="<?php echo esc_attr( $attachment_id ); ?>"
                                />
                            </td>
                            <td class="unique-td-bestandsnaam media-items-td-bestandsnaam">
                                <input
                                    type="text"
                                    class="filename-input media-items-filename-input"
                                    data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
                                    value="<?php echo esc_attr( $filename ); ?>"
                                    readonly
                                />
                            </td>
                            <td class="unique-td-nieuwe-bestandsnaam media-items-td-nieuwe-bestandsnaam">
                                <input
                                    type="text"
                                    class="new-filename-input media-items-new-filename-input"
                                    data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
                                    placeholder="<?php echo esc_attr__( "Voer nieuwe bestandsnaam in", "modern-image-manager" ); ?>"
                                />
                            </td>
                            <td class="unique-td-pagina media-items-td-pagina">
                                <?php echo esc_html( $page_title ); ?>
                            </td>
                            <td class="unique-td-titel media-items-td-titel">
                                <input
                                    type="text"
                                    class="title-input media-items-title-input"
                                    data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
                                    value="<?php echo esc_attr( $title ); ?>"
                                />
                            </td>
                            <td class="unique-td-caption media-items-td-caption">
                                <input
                                    type="text"
                                    class="caption-input media-items-caption-input"
                                    data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
                                    value="<?php echo esc_attr( $caption ); ?>"
                                />
                            </td>
                            <td class="unique-td-beschrijving media-items-td-beschrijving">
                                <textarea
                                    class="description-input media-items-description-input"
                                    data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
                                ><?php echo esc_textarea( $description ); ?></textarea>
                            </td>
                            <td class="unique-td-alttag media-items-td-alttag">
                                <input
                                    type="text"
                                    class="alt-input media-items-alt-input"
                                    data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
                                    value="<?php echo esc_attr( $alt_text ); ?>"
                                />
                            </td>
                            <td class="unique-td-acties media-items-td-acties">
                                <button
                                    class="save-meta page-button media-items-save-meta"
                                    data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
                                >
                                    <?php echo esc_html__( "Opslaan", "modern-image-manager" ); ?>
                                </button>
                            </td>
                            <td class="unique-td-verwijderen media-items-td-verwijderen">
                                <button
                                    class="delete-media page-button media-items-delete-media"
                                    data-attachment-id="<?php echo esc_attr( $attachment_id ); ?>"
                                >
                                    <?php echo esc_html__( "Verwijderen", "modern-image-manager" ); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; wp_reset_postdata(); ?>
                <?php else : ?>
                    <tr class="unique-tr-no-media media-items-no-media">
                        <td colspan="10" class="unique-td-no-media media-items-td-no-media">
                            <?php echo esc_html__( "Geen media gevonden.", "modern-image-manager" ); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Filter balk onderaan -->
        <div class="filter-bottom-container">
            <div class="filter-footer filter-bottom-footer">
                <div class="nav-controls filter-bottom-nav-controls">
                    <select
                        name="pageFilter"
                        id="pageFilterBottom"
                        class="page-filter-select filter-bottom-page-filter"
                    >
                        <option value="all"      <?php selected( $current_filter, 'all' ); ?>>
                            <?php echo esc_html__( "Alle pagina's", "modern-image-manager" ); ?>
                        </option>
                        <option value="linked"   <?php selected( $current_filter, 'linked' ); ?>>
                            <?php echo esc_html__( "Gekoppelde pagina's", "modern-image-manager" ); ?>
                        </option>
                        <option value="unlinked" <?php selected( $current_filter, 'unlinked' ); ?>>
                            <?php echo esc_html__( "Niet gekoppelde pagina's", "modern-image-manager" ); ?>
                        </option>
                    </select>

                    <button
                        type="button"
                        id="applyFilterBottom"
                        class="page-button filter-bottom-apply-button"
                    >
                        <?php echo esc_html__( "Filter toepassen", "modern-image-manager" ); ?>
                    </button>

                    <button
                        type="submit"
                        name="bulk_delete"
                        id="mim-bulk-delete-bottom"
                        class="page-button filter-bottom-apply-button"
                        disabled
                    >
                        <?php esc_html_e( 'Verwijder geselecteerde afbeeldingen', 'modern-image-manager' ); ?>
                    </button>

                    <div class="pagination-navigation filter-bottom-pagination">
                        <button id="prevPageBottom" class="page-button filter-bottom-prev-button">
                            <?php echo esc_html__( "Vorige", "modern-image-manager" ); ?>
                        </button>
                        <span id="currentPageBottom" class="filter-bottom-current-page">1</span> /
                        <span id="totalPagesBottom" class="filter-bottom-total-pages">50</span>
                        <button id="nextPageBottom" class="page-button filter-bottom-next-button">
                            <?php echo esc_html__( "Volgende", "modern-image-manager" ); ?>
                        </button>
                    </div>
                </div>

                <div class="search-container filter-bottom-search-container">
                    <input
                        type="text"
                        id="search-input-bottom"
                        class="search-box filter-bottom-search-box"
                        placeholder="<?php echo esc_attr__( "Zoeken…", "modern-image-manager" ); ?>"
                        onkeyup="filterTable()"
                    />
                </div>

                <div class="rows-controls filter-bottom-rows-controls">
                    <div class="rows-per-page-container filter-bottom-rows-per-page-container">
                        <label for="rowsPerPage" class="filter-bottom-rows-per-page-label">
                            <?php echo esc_html__( "Rijen per pagina:", "modern-image-manager" ); ?>
                        </label>
                        <select
                            id="rowsPerPage"
                            name="per_page"
                            class="rows-per-page-select filter-bottom-rows-per-page-select"
                            onchange="filterTable()"
                        >
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="30" selected>30</option>
                            <option value="40">40</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>