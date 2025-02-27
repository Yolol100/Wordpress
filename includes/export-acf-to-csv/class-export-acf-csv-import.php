<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ACF_Export_Import_Admin {
	private ACF_Exporter $exporter;

	public function __construct() {
		$this->exporter = new ACF_Exporter();
		add_action( 'admin_menu', [ $this, 'add_import_page' ] );
		add_action( 'admin_post_acf_import_csv', [ $this, 'process_import' ] );
	}

	public function add_import_page(): void {
		add_submenu_page(
			'export-acf-csv',
			__( 'Import', 'export-acf-csv' ),
			__( 'Import', 'export-acf-csv' ),
			'manage_options',
			'export-acf-csv-import',
			[ $this, 'import_page_html' ]
		);
	}

	public function import_page_html(): void {
		?>
		<div class="wrap wrap-import" style="max-width:900px;margin:30px auto;padding:25px;background:#fafafa;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);">
			<h1 style="color:#2a2a2a;margin-bottom:25px;font-weight:600;">
				<?php esc_html_e( 'Importeren', 'export-acf-csv' ); ?>
			</h1>

			<?php if ( isset( $_GET['acf_import_success'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Importeren succesvol voltooid.', 'export-acf-csv' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( 'export_update_acf_csv_action', 'export_update_acf_csv_nonce' ); ?>
				<input type="hidden" name="action" value="acf_import_csv">

				<table class="form-table" style="width:100%;font-size:16px;">
					<tr>
						<th style="width:30%;background:#e9ecef;padding:12px;text-align:left;font-weight:600;">
							<label for="csv_file">
								<?php esc_html_e( 'Selecteer CSV Bestand:', 'export-acf-csv' ); ?>
							</label>
						</th>
						<td>
							<input 
								type="file" 
								name="csv_file" 
								id="csv_file" 
								accept=".csv" 
								required 
								style="width:100%;padding:10px;border:1px solid #ced4da;border-radius:8px;transition:border-color 0.2s, box-shadow 0.2s;"
							>
							<p class="description">
								<?php esc_html_e( 'Upload een CSV-bestand om ACF velden te importeren.', 'export-acf-csv' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<button 
					type="submit" 
					class="button" 
					style="background-color:#0073aa;color:#fff;padding:5px 15px;font-size:17px;font-weight:600;border:none;border-radius:8px;cursor:pointer;transition:background-color 0.3s, box-shadow 0.3s;margin-top:20px;"
				>
					<?php esc_html_e( 'Importeer CSV', 'export-acf-csv' ); ?>
				</button>
			</form>
		</div>

		<style>
			.wrap-import input[type="file"]:focus {
				border-color: #0073aa;
				box-shadow: 0 0 0 3px rgba(0,115,170,0.2);
			}
			.wrap-import .button:hover {
				background-color: #005177;
				box-shadow: 0 4px 8px rgba(0,115,170,0.3);
				color: #fff;
			}
			@media (max-width:600px) {
				.wrap-import .form-table th,
				.wrap-import .form-table td {
					display: block;
					width: 100%;
				}
			}
		</style>
		<?php
	}

	public function process_import(): void {
		if (
			! current_user_can( 'manage_options' ) ||
			! isset( $_POST['export_update_acf_csv_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['export_update_acf_csv_nonce'] ) ),
				'export_update_acf_csv_action'
			)
		) {
			wp_die( __( 'Toegang geweigerd of ongeldig nonce.', 'export-acf-csv' ) );
		}

		if (
			empty( $_FILES['csv_file']['tmp_name'] ) ||
			$_FILES['csv_file']['error'] !== UPLOAD_ERR_OK
		) {
			wp_die( __( 'Fout bij het uploaden van het CSV-bestand.', 'export-acf-csv' ) );
		}

		$file = $_FILES['csv_file']['tmp_name'];
		$this->exporter->import_from_csv( $file );

		wp_safe_redirect( add_query_arg( 'acf_import_success', 1, admin_url( 'admin.php?page=export-acf-csv-import' ) ) );
		exit;
	}
}