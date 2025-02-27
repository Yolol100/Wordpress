<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ACF_Export_Admin {
	private ACF_Exporter $exporter;

	public function __construct() {
		$this->exporter = new ACF_Exporter();

		add_action( 'admin_menu', [ $this, 'add_export_page' ] );
		add_action( 'admin_post_acf_export_csv', [ $this, 'process_export' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'wp_ajax_get_pages_by_post_type', [ $this, 'get_pages_by_post_type_callback' ] );
	}

	/**
	 * Voeg de exportpagina toe aan het admin-menu.
	 */
	public function add_export_page(): void {
		add_menu_page(
			esc_html__( 'Export', 'export-acf-csv' ),
			esc_html__( 'Export', 'export-acf-csv' ),
			'manage_options',
			'export-acf-csv',
			[ $this, 'export_page_html' ],
			'dashicons-download',
			6
		);
	}

	/**
	 * Voeg de benodigde scripts en stijlen toe aan de adminpagina.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( 'toplevel_page_export-acf-csv' === $hook ) {
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
					'ajax_nonce' => wp_create_nonce( 'get_pages_nonce' ),
				]
			);
		}
	}

	/**
	 * Toon de exportpagina.
	 */
	public function export_page_html(): void { ?>
		<div class="wrap export-acf-csv">
			<h1 class="export-acf-csv-title"><?php esc_html_e( 'Exporteren', 'export-acf-csv' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="export-acf-csv-form">
				<?php wp_nonce_field( 'export_acf_csv_action', 'export_acf_csv_nonce' ); ?>
				<input type="hidden" name="action" value="acf_export_csv">
				<table class="form-table export-acf-csv-table">
					<tr>
						<th class="export-acf-csv-label">
							<label for="post_type"><?php esc_html_e( 'Selecteer Post Type:', 'export-acf-csv' ); ?></label>
						</th>
						<td>
							<select name="post_type" id="post_type" class="export-acf-csv-select" required>
								<?php foreach ( get_post_types( [ 'public' => true ], 'objects' ) as $pt ) : ?>
									<option value="<?php echo esc_attr( $pt->name ); ?>">
										<?php echo esc_html( $pt->label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th class="export-acf-csv-label">
							<label for="fields"><?php esc_html_e( 'Selecteer ACF Velden:', 'export-acf-csv' ); ?></label>
						</th>
						<td>
							<select multiple name="fields[]" id="fields" class="export-acf-csv-select" required>
								<?php foreach ( $this->get_acf_fields() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>">
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th class="export-acf-csv-label">
							<label for="pages"><?php esc_html_e( 'Selecteer Items', 'export-acf-csv' ); ?></label>
						</th>
						<td>
							<select multiple name="pages[]" id="pages" class="export-acf-csv-select" required>
								<?php
								$query = new WP_Query(
									[
										'post_type'      => 'post',
										'post_status'    => 'publish',
										'posts_per_page' => -1,
									]
								);

								if ( $query->have_posts() ) :
									while ( $query->have_posts() ) :
										$query->the_post();
										?>
										<option value="<?php echo esc_attr( get_the_ID() ); ?>">
											<?php echo esc_html( get_the_title() ); ?>
										</option>
									<?php endwhile;
								else : ?>
									<option value=""><?php esc_html_e( 'Geen pagina\'s gevonden', 'export-acf-csv' ); ?></option>
								<?php endif;
								wp_reset_postdata();
								?>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Exporteren', 'export-acf-csv' ), 'primary', 'submit', true, [ 'class' => 'button' ] ); ?>
			</form>
		</div>
	<?php }

	/**
	 * Haal ACF velden op.
	 */
	private function get_acf_fields(): array {
		$fields = [];
		if ( function_exists( 'acf_get_field_groups' ) ) {
			foreach ( acf_get_field_groups() as $group ) {
				foreach ( acf_get_fields( $group['key'] ) ?: [] as $field ) {
					if ( ( $field['type'] ?? '' ) !== 'tab' ) {
						$fields[ $field['key'] ] = sprintf( '%s (%s)', $field['label'], $group['title'] );
					}
				}
			}
		}
		return $fields;
	}

	/**
	 * Haal de pagina's op op basis van het post type via AJAX.
	 */
	public function get_pages_by_post_type_callback(): void {
		$post_type = sanitize_text_field( $_POST['post_type'] ?? '' );

		if ( empty( $post_type ) ) {
			wp_send_json_error( __( 'Post type niet opgegeven.', 'export-acf-csv' ) );
		}

		$query = new WP_Query(
			[
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			]
		);

		if ( ! $query->have_posts() ) {
			wp_send_json_error( __( 'Geen pagina\'s gevonden.', 'export-acf-csv' ) );
		}

		$pages = [];
		while ( $query->have_posts() ) {
			$query->the_post();
			$pages[] = [
				'id'    => get_the_ID(),
				'title' => get_the_title(),
			];
		}
		wp_reset_postdata();

		wp_send_json_success( [ 'pages' => $pages ] );
	}

	/**
	 * Verwerk de export van gegevens.
	 */
	public function process_export(): void {
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( $_POST['export_acf_csv_nonce'] ?? '', 'export_acf_csv_action' ) ) {
			wp_die( __( 'Toegang geweigerd of ongeldig nonce.', 'export-acf-csv' ) );
		}

		$post_type = sanitize_text_field( $_POST['post_type'] ?? '' );
		$fields    = array_map( 'sanitize_text_field', $_POST['fields'] ?? [] );
		$page_ids  = array_map( 'intval', $_POST['pages'] ?? [] );

		if ( empty( $fields ) ) {
			wp_die( __( 'Selecteer velden om te exporteren.', 'export-acf-csv' ) );
		}

		if ( empty( $page_ids ) ) {
			wp_die( __( 'Selecteer minstens één pagina om te exporteren.', 'export-acf-csv' ) );
		}

		$this->exporter->export( $post_type, $page_ids, $fields, 'csv' );
		wp_safe_redirect( add_query_arg( 'acf_export_success', 1, admin_url( 'admin.php?page=export-acf-csv' ) ) );
		exit;
	}
}