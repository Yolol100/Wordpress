<?php
declare(strict_types=1);

// File: admin/class-export-acf-csv-pagina.php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class ACF_Export_Page_Admin {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_page_submenu' ] );
		add_action( 'admin_post_create_multiple_pages', [ $this, 'create_multiple_pages' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Voeg het submenu Pagina toe onder het Export menu.
	 */
	public function add_page_submenu(): void {
		add_submenu_page(
			'export-acf-csv', // Parent slug (hoofdmenu)
			esc_html__( 'Pagina', 'export-acf-csv' ), // Pagina titel
			esc_html__( 'Pagina', 'export-acf-csv' ), // Menu titel
			'manage_options', // Capability
			'export-acf-csv-pagina', // Slug
			[ $this, 'pagina_submenu_html' ] // Callback voor de inhoud van de pagina
		);
	}

	/**
	 * Voeg de benodigde scripts en stijlen toe aan de adminpagina.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		// Controleer of we ons op de juiste adminpagina bevinden
		if ( 'export_page_export-acf-csv-pagina' === $hook ) {
			wp_enqueue_style(
				'export-acf-csv-admin-css',
				EXPORT_ACF_CSV_PLUGIN_URL . 'assets/css/export-acf-csv-admin.css',
				[],
				EXPORT_ACF_CSV_VERSION,
				'all'
			);

			wp_enqueue_script(
				'export-acf-csv-admin-js',
				EXPORT_ACF_CSV_PLUGIN_URL . 'assets/js/export-acf-csv-admin.js',
				[ 'jquery' ],
				EXPORT_ACF_CSV_VERSION,
				true
			);

			wp_localize_script(
				'export-acf-csv-admin-js',
				'ajax_object',
				[
					'ajax_url'   => admin_url( 'admin-ajax.php' ),
					'ajax_nonce' => wp_create_nonce( 'create_pages_nonce' ),
				]
			);
		}
	}

	/**
	 * Toon de pagina voor het aanmaken van meerdere pagina's.
	 */
	public function pagina_submenu_html(): void {
		$message      = isset( $_GET['create_pages_message'] ) ? urldecode( $_GET['create_pages_message'] ) : '';
		$message_type = isset( $_GET['message_type'] ) ? sanitize_text_field( $_GET['message_type'] ) : '';
		?>
		<div class="wrap export-acf-csv">
			<h1 class="export-acf-csv-title"><?php esc_html_e( 'Pagina\'s aanmaken', 'export-acf-csv' ); ?></h1>

			<?php if ( $message ) : ?>
				<div class="notice notice-<?php echo esc_attr( $message_type ); ?> is-dismissible">
					<p><?php echo esc_html( $message ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="export-acf-csv-form">
				<?php wp_nonce_field( 'create_pages_action', 'create_pages_nonce' ); ?>
				<input type="hidden" name="action" value="create_multiple_pages">

				<table class="form-table export-acf-csv-table">
					<tr>
						<th class="export-acf-csv-label">
							<label for="page_names"><?php esc_html_e( 'Voer paginanamen in:', 'export-acf-csv' ); ?></label>
						</th>
						<td>
							<textarea name="page_names" id="page_names" rows="5" class="export-acf-csv-textarea" required></textarea>
							<p class="description"><?php esc_html_e( 'Gebruik een nieuwe regel of komma om meerdere paginanamen in te voeren.', 'export-acf-csv' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Aanmaken', 'export-acf-csv' ), 'primary', 'submit', true, [ 'class' => 'button' ] ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Maak meerdere pagina's aan op basis van de ingevoerde namen.
	 */
	public function create_multiple_pages(): void {
		if (
			! current_user_can( 'manage_options' ) ||
			! wp_verify_nonce( $_POST['create_pages_nonce'] ?? '', 'create_pages_action' )
		) {
			wp_die( __( 'Toegang geweigerd of ongeldig nonce.', 'export-acf-csv' ) );
		}

		$page_names_input = sanitize_textarea_field( $_POST['page_names'] ?? '' );
		if ( empty( $page_names_input ) ) {
			wp_die( __( 'Geen paginanamen ingevoerd.', 'export-acf-csv' ) );
		}

		$page_names    = preg_split( '/[\r\n,]+/', $page_names_input );
		$created_pages = [];

		foreach ( $page_names as $page_name ) {
			$page_name = trim( $page_name );
			if ( ! empty( $page_name ) ) {
				$new_page_id = wp_insert_post(
					[
						'post_title'   => $page_name,
						'post_status'  => 'publish',
						'post_type'    => 'page',
						'post_content' => '',
					]
				);
				if ( ! is_wp_error( $new_page_id ) ) {
					$created_pages[] = $page_name;
				}
			}
		}

		if ( ! empty( $created_pages ) ) {
			$message      = sprintf( __( 'De volgende pagina\'s zijn aangemaakt: %s', 'export-acf-csv' ), implode( ', ', $created_pages ) );
			$message_type = 'success';
		} else {
			$message      = __( 'Er zijn geen pagina\'s aangemaakt.', 'export-acf-csv' );
			$message_type = 'error';
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'create_pages_message' => urlencode( $message ),
					'message_type'         => $message_type,
				],
				admin_url( 'admin.php?page=export-acf-csv-pagina' )
			)
		);
		exit;
	}
}