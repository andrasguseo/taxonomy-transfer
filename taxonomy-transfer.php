<?php
/**
 * Plugin Name:       Taxonomy Transfer
 * Plugin URI:
 * GitHub Plugin URI: https://github.com/andrasguseo/taxonomy-transfer
 * Description:       Transfer terms from one taxonomy to another within a selected post type.
 * Version:           1.0.0
 * Plugin Class:      AGU_Taxonomy_Transfer
 * Author:            Andras Guseo
 * Author URI:        https://andrasguseo.com
 * License:           GPL version 3 or any later version
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       agu-taxonomy-transfer
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

class AGU_Taxonomy_Transfer {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'process_taxonomy_action' ] );
		add_filter( 'plugin_action_links', [ $this, 'add_settings_plugin_action' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_get_taxonomies_for_post_type', [ $this, 'get_taxonomies_for_post_type' ] );
		add_action( 'admin_notices', [ $this, 'tt_admin_notices' ] );
	}

	/**
	 * Add a menu page.
	 *
	 * @return void
	 */
	public function add_menu(): void {
		add_submenu_page(
			'tools.php',
			'Taxonomy Transfer',
			'Taxonomy Transfer',
			'manage_options',
			'taxonomy-transfer',
			callback: [ $this, 'taxonomy_transfer_page' ]
		);
	}

	/**
	 * Render the Page.
	 *
	 * @return void
	 */
	public function taxonomy_transfer_page(): void {
		$this->render_transfer_taxonomies_form();

		$this->render_delete_taxonomies_form();
	}

	/**
	 * Render the transfer form.
	 *
	 * @return void
	 */
	public function render_transfer_taxonomies_form(): void {
		?>
		<div class="wrap">
			<h1>Transfer Taxonomies</h1>
			<div>
				<p>This tool allows you to transfer all taxonomy values of a taxonomy of a post type to another
					taxonomy.</p>
				<p><strong>Creating a backup before taking any action is recommended as the changes are
						irreversible!</strong></p>
			</div>
			<form method="post" action="">
				<?php
				// Dropdown for selecting post type
				$post_types = $this->get_pubic_post_types();
				?>
				<div class="field">
					<label for="post_type">Select Post Type:</label>
					<select name="post_type" id="post_type" onchange="updateTaxonomiesDropdowns()">
						<option value=''>Select a post type</option>
						<?php
						foreach ( $post_types as $post_type ) {
							echo '<option value="' . $post_type->name . '">' . $post_type->labels->name . ' (' . $post_type->name . ')</option>';
						}
						?>
					</select>
				</div>
				<div class="field">
					<?php
					// Dropdown for selecting origin taxonomy
					?>
					<label for="origin_taxonomy">Select Origin Taxonomy:</label>
					<select name="origin_taxonomy" id="origin_taxonomy">
					</select>
				</div>
				<div class="field">
					<?php
					// Dropdown for selecting destination taxonomy
					?>
					<label for="destination_taxonomy">Select Destination Taxonomy:</label>
					<select name="destination_taxonomy" id="destination_taxonomy">
					</select>
				</div>

				<input type="submit" class="button button-primary" value="Transfer Taxonomy">
			</form>
		</div>
		<?php
	}

	/**
	 * Render the delete form.
	 *
	 * @return void
	 */
	public function render_delete_taxonomies_form(): void {
		?>
		<div class="wrap">
			<h2>Delete Taxonomies</h2>
			<div>
				<p>This tool allows you to delete all taxonomy values of a taxonomy of a post type.</p>
				<p><strong>Creating a backup before taking any action is recommended as the changes are
						irreversible!</strong></p>
			</div>
			<form method="post" action="">
				<?php
				// Dropdown for selecting post type
				$post_types = $this->get_pubic_post_types();
				?>
				<div class="field">
					<label for="delete_post_type">Select Post Type:</label>
					<select name="delete_post_type" id="delete_post_type"
					        onchange="updateTaxonomiesDropdowns('delete')">
						<option value=''>Select a post type</option>
						<?php
						foreach ( $post_types as $post_type ) {
							echo '<option value="' . esc_attr( $post_type->name ) . '">' . esc_html( $post_type->labels->name ) . ' (' . esc_attr( $post_type->name ) . ')</option>';
						}
						?>
					</select>
				</div>
				<div class="field">
					<?php
					// Dropdown for selecting taxonomy
					?>
					<label for="delete_taxonomy">Select Taxonomy:</label>
					<select name="delete_taxonomy" id="delete_taxonomy"></select>
				</div>

				<input type="submit" class="button button-primary" value="Delete Taxonomies">
			</form>
		</div>
		<?php
	}

	/**
	 * Handle form actions.
	 *
	 * @return void
	 */
	public function process_taxonomy_action(): void {
		$redirect = false;
		$results  = [
			'success' => 0,
			'fail'    => 0,
			'delete'  => 0,
		];

		if (
			isset( $_POST[ 'post_type' ] )
			&& isset( $_POST[ 'origin_taxonomy' ] )
			&& isset( $_POST[ 'destination_taxonomy' ] )
		) {
			$post_type            = sanitize_text_field( $_POST[ 'post_type' ] );
			$origin_taxonomy      = sanitize_text_field( $_POST[ 'origin_taxonomy' ] );
			$destination_taxonomy = sanitize_text_field( $_POST[ 'destination_taxonomy' ] );

			// Transfer terms
			$args  = [
				'post_type'      => $post_type,
				'posts_per_page' => - 1,
			];
			$posts = get_posts( $args );

			foreach ( $posts as $post ) {
				$terms  = wp_get_object_terms( $post->ID, $origin_taxonomy, args: [ 'fields' => 'names' ] );
				$action = wp_set_object_terms( $post->ID, $terms, $destination_taxonomy, true );
				if ( is_array( $action ) ) {
					$results[ 'success' ] ++;
				} else {
					$results[ 'fail' ] ++;
				}
			}

			$redirect = true;
		}

		if (
			isset( $_POST[ 'delete_post_type' ] )
			&& isset( $_POST[ 'delete_taxonomy' ] )
		) {
			$delete_post_type = sanitize_text_field( $_POST[ 'delete_post_type' ] );
			$delete_taxonomy  = sanitize_text_field( $_POST[ 'delete_taxonomy' ] );

			// Delete all terms for the selected taxonomy in the selected post type
			$args  = [
				'post_type'      => $delete_post_type,
				'posts_per_page' => - 1,
			];
			$posts = get_posts( $args );

			foreach ( $posts as $post ) {
				wp_delete_object_term_relationships( $post->ID, $delete_taxonomy );
				$results[ 'delete' ] ++;
			}

			$redirect = true;
		}

		if ( $redirect ) {
			$redirect_to = remove_query_arg( array_keys( $results ), $_SERVER[ 'REQUEST_URI' ] );

			foreach ( $results as $result => $value ) {
				if ( $value === 0 ) {
					unset( $results[ $result ] );
				}
			}
			// Redirect back with a success message
			$redirect_to = add_query_arg( $results, $redirect_to );
			$redirect_to = add_query_arg( 'page', 'taxonomy-transfer', $redirect_to );

			// Redirect to the specified URL
			wp_redirect( $redirect_to );
			exit;
		}
	}

	/**
	 * Get taxonomies for the selected post type.
	 *
	 * @return void
	 */
	public function get_taxonomies_for_post_type(): void {
		if ( isset( $_POST[ 'post_type' ] ) ) {
			$post_type  = sanitize_text_field( $_POST[ 'post_type' ] );
			$taxonomies = get_object_taxonomies( $post_type, 'objects' );

			$formatted_taxonomies = array();
			foreach ( $taxonomies as $taxonomy ) {
				$formatted_taxonomies[] = array(
					'label' => $taxonomy->labels->name,
					'slug'  => $taxonomy->name,
				);
			}

			echo json_encode( $formatted_taxonomies );
			die();
		}
	}

	/**
	 * Add Plugin action.
	 *
	 * @param string[] $links An array of plugin action links.
	 * @param string   $file  Path to the plugin file relative to the plugins directory.
	 *
	 * @return array
	 */
	public function add_settings_plugin_action( array $links, string $file ): array {
		if ( plugin_basename( __FILE__ ) === $file ) {
			$settings_url  = admin_url( 'tools.php?page=taxonomy-transfer' );
			$settings_link = '<a href="' . esc_url( $settings_url ) . '">Settings</a>';
			$links[]       = $settings_link;
		}

		return $links;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		if (
			is_admin()
			&& isset( $_GET[ 'page' ] )
			&& $_GET[ 'page' ] === 'taxonomy-transfer'
		) {
			wp_enqueue_script( 'taxonomy-transfer-get-taxonomies', plugins_url( 'src/js/get-taxonomies.js', __FILE__ ), [], '1.0', true );

			wp_enqueue_style( 'taxonomy-transfer-style', plugins_url( 'src/css/style.css', __FILE__ ), [], '1.0' );
		}
	}

	/**
	 * Get the public post types.
	 *
	 * @return string[]|WP_Post_Type[] Array of public post types as object.
	 */
	private function get_pubic_post_types(): array {
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		unset( $post_types[ 'attachment' ] );

		return $post_types;
	}

	/**
	 * Render the admin notices.
	 *
	 * @return void
	 */
	public function tt_admin_notices(): void {
		$msg     = [];
		$results = [
			'success' => 'Updated',
			'fail'    => 'Failed',
			'delete'  => 'Deleted',
		];
		foreach ( $results as $result => $label ) {
			if ( isset( $_REQUEST[ $result ] ) && $_REQUEST[ $result ] > 0 ) {
				$msg[] = $label . ' entries: ' . $_REQUEST[ $result ];
			}
		}

		$msg = array_map( 'esc_html', $msg );

		if ( ! empty( $msg ) ) {
			$msg = implode( '<br>', $msg );
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo $msg; ?></p>
			</div>
			<?php
		}
	}
}

// Instantiate the class
$agu_taxonomy_transfer = new AGU_Taxonomy_Transfer();
