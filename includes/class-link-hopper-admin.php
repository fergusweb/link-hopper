<?php
/**
 * Handles all admin UI: menu, Settings API, and asset enqueueing.
 *
 * @package Link_Hopper
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Admin settings page and Settings API registration.
 */
class Link_Hopper_Admin {

	/**
	 * Cached plugin options.
	 *
	 * @var array|null
	 */
	private $options = null;

	/**
	 * Constructor — registers admin hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_filter(
			'plugin_action_links_' . plugin_basename( LINK_HOPPER_PLUGIN_FILE ),
			array( $this, 'add_plugin_action_links' )
		);
	}

	// =========================================================================
	// Options
	// =========================================================================

	/**
	 * Get plugin options, merged with defaults.
	 *
	 * @return array
	 */
	private function get_options() {
		if ( null === $this->options ) {
			$this->options = wp_parse_args(
				get_option( LINK_HOPPER_OPTION_KEY, array() ),
				array(
					'baseURL' => 'hop',
					'hops'    => array(),
				)
			);
		}

		return $this->options;
	}

	/**
	 * Get the configured base URL slug.
	 *
	 * @return string
	 */
	private function get_base_url() {
		$options = $this->get_options();
		return $options['baseURL'];
	}

	// =========================================================================
	// Admin Menu
	// =========================================================================

	/**
	 * Register the admin page under Tools.
	 */
	public function add_admin_menu() {
		add_management_page(
			__( 'Link Hopper', 'link-hopper' ),
			__( 'Link Hopper', 'link-hopper' ),
			'manage_options',
			'link-hopper',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Add a Settings link to the Plugins list table.
	 *
	 * @param string[] $links Plugin action links.
	 * @return string[]
	 */
	public function add_plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'tools.php?page=link-hopper' ) ),
			__( 'Settings', 'link-hopper' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	// =========================================================================
	// Settings API
	// =========================================================================

	/**
	 * Register settings, sections, and fields.
	 */
	public function register_settings() {
		register_setting(
			'link_hopper',
			LINK_HOPPER_OPTION_KEY,
			array(
				'sanitize_callback' => array( $this, 'sanitize_options' ),
			)
		);

		// Settings section.
		add_settings_section(
			'link_hopper_settings_section',
			__( 'Settings', 'link-hopper' ),
			'__return_false',
			'link-hopper'
		);

		add_settings_field(
			'link_hopper_base_url',
			__( 'Base URL', 'link-hopper' ),
			array( $this, 'render_base_url_field' ),
			'link-hopper',
			'link_hopper_settings_section'
		);

		// Hops section — the full repeater is rendered in the section callback.
		add_settings_section(
			'link_hopper_hops_section',
			__( 'Hops', 'link-hopper' ),
			array( $this, 'render_hops_section' ),
			'link-hopper'
		);
	}

	/**
	 * Sanitize and validate options on save.
	 *
	 * @param mixed $input Raw POST data.
	 * @return array Sanitized options.
	 */
	public function sanitize_options( $input ) {
		$sanitized = array(
			'baseURL' => 'hop',
			'hops'    => array(),
		);

		if ( ! is_array( $input ) ) {
			return $sanitized;
		}

		// Base URL: strip slashes, allow only letters, numbers, hyphens, underscores.
		if ( ! empty( $input['baseURL'] ) ) {
			$base_url = sanitize_text_field( $input['baseURL'] );
			$base_url = trim( $base_url, '/' );
			$base_url = preg_replace( '/[^a-zA-Z0-9_-]/', '', $base_url );

			if ( ! empty( $base_url ) ) {
				$sanitized['baseURL'] = $base_url;
			}
		}

		// Hops: validate each row and store as name => url.
		if ( ! empty( $input['hops'] ) && is_array( $input['hops'] ) ) {
			foreach ( $input['hops'] as $hop ) {
				if ( ! is_array( $hop ) ) {
					continue;
				}

				$name = isset( $hop['name'] ) ? sanitize_text_field( $hop['name'] ) : '';
				$name = trim( $name, '/' );
				$name = preg_replace( '/[^a-zA-Z0-9_-]/', '', $name );

				$url = isset( $hop['url'] ) ? esc_url_raw( trim( $hop['url'] ) ) : '';

				// Skip blank or incomplete rows, and skip duplicates (last write wins replaced by first).
				if ( empty( $name ) || empty( $url ) || isset( $sanitized['hops'][ $name ] ) ) {
					continue;
				}

				$sanitized['hops'][ $name ] = $url;
			}
		}

		// Clear in-memory cache so subsequent reads in this request get fresh data.
		$this->options = null;

		return $sanitized;
	}

	// =========================================================================
	// Admin Assets
	// =========================================================================

	/**
	 * Enqueue admin scripts and styles (only on the plugin page).
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 */
	public function enqueue_admin_assets( $hook_suffix ) {
		if ( 'tools_page_link-hopper' !== $hook_suffix ) {
			return;
		}

		// Inline CSS — no external file required.
		wp_register_style( 'link-hopper-admin', false, array(), LINK_HOPPER_VERSION ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( 'link-hopper-admin' );
		wp_add_inline_style( 'link-hopper-admin', $this->get_admin_css() );

		// Inline JS attached to jQuery.
		wp_register_script( 'link-hopper-admin', false, array( 'jquery' ), LINK_HOPPER_VERSION, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_script( 'link-hopper-admin' );
		wp_add_inline_script(
			'link-hopper-admin',
			'var linkHopperData = ' . wp_json_encode( $this->get_js_data() ) . ';',
			'before'
		);
		wp_add_inline_script( 'link-hopper-admin', $this->get_admin_js() );
	}

	/**
	 * Build the data object passed to the admin JavaScript.
	 *
	 * @return array
	 */
	private function get_js_data() {
		$options = $this->get_options();

		return array(
			'baseUrl'     => trailingslashit( home_url() ) . $this->get_base_url() . '/',
			'rowCount'    => count( $options['hops'] ),
			'testText'    => __( 'Test', 'link-hopper' ),
			'rowTemplate' => $this->get_hop_row_template(),
		);
	}

	/**
	 * Returns the admin page CSS.
	 *
	 * @return string
	 */
	private function get_admin_css() {
		return '
			.link-hopper-base-url { display: flex; align-items: center; gap: 6px; }
			.link-hopper-base-url input { width: 12em; }
			#link-hopper-hops-table { border-collapse: collapse; width: 100%; margin-top: 8px; }
			#link-hopper-hops-table .col-name { width: 20%; }
			#link-hopper-hops-table .col-test,
			#link-hopper-hops-table .col-remove { width: 70px; text-align: center; }
			.link-hopper-remove-row { color: #b32d2e !important; font-size: 18px !important; line-height: 1 !important; cursor: pointer; padding: 2px 8px !important; }
		';
	}

	/**
	 * Returns the admin page JavaScript.
	 *
	 * @return string
	 */
	private function get_admin_js() {
		return <<<'JS'
( function () {
	'use strict';

	var rowIndex = parseInt( linkHopperData.rowCount, 10 );

	function updateTestLink( row ) {
		var hopName  = row.querySelector( '.link-hopper-hop-name' ).value.trim();
		var testCell = row.querySelector( '.col-test' );

		if ( hopName ) {
			var href = linkHopperData.baseUrl + encodeURIComponent( hopName ) + '/';
			testCell.innerHTML = '<a href="' + href + '" target="_blank" rel="noopener">' + linkHopperData.testText + '</a>';
		} else {
			testCell.innerHTML = '&nbsp;';
		}
	}

	document.getElementById( 'link-hopper-add-row' ).addEventListener( 'click', function () {
		var template = document.createElement( 'template' );
		template.innerHTML = linkHopperData.rowTemplate.replace( /__INDEX__/g, rowIndex );
		document.getElementById( 'link-hopper-hops-body' ).appendChild( template.content.firstElementChild );
		rowIndex++;
	} );

	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '.link-hopper-remove-row' );
		if ( btn ) {
			btn.closest( 'tr' ).remove();
		}
	} );

	document.addEventListener( 'input', function ( e ) {
		if ( e.target.classList.contains( 'link-hopper-hop-name' ) ) {
			updateTestLink( e.target.closest( 'tr' ) );
		}
	} );

} () );
JS;
	}

	/**
	 * Returns a blank hop-row HTML template with __INDEX__ as a placeholder.
	 * Passed to JS for cloning when new rows are added.
	 *
	 * @return string
	 */
	private function get_hop_row_template() {
		$option_key = LINK_HOPPER_OPTION_KEY;

		ob_start();
		?>
<tr class="link-hopper-hop-row">
	<td class="col-name">
		<input
			type="text"
			name="<?php echo esc_attr( $option_key ); ?>[hops][__INDEX__][name]"
			value=""
			class="widefat link-hopper-hop-name"
			placeholder="hop-name"
		/>
	</td>
	<td class="col-url">
		<input
			type="url"
			name="<?php echo esc_attr( $option_key ); ?>[hops][__INDEX__][url]"
			value=""
			class="widefat link-hopper-hop-url"
			placeholder="https://example.com"
		/>
	</td>
	<td class="col-test">&nbsp;</td>
	<td class="col-remove">
		<button
			type="button"
			class="button-link link-hopper-remove-row"
			aria-label="<?php esc_attr_e( 'Remove this hop', 'link-hopper' ); ?>"
		>&times;</button>
	</td>
</tr>
		<?php
		return trim( ob_get_clean() );
	}

	// =========================================================================
	// Settings Field / Section Renderers
	// =========================================================================

	/**
	 * Render the Base URL settings field.
	 */
	public function render_base_url_field() {
		$options  = $this->get_options();
		$home_url = trailingslashit( home_url() );
		?>
		<div class="link-hopper-base-url">
			<span><?php echo esc_html( $home_url ); ?></span>
			<input
				type="text"
				id="link_hopper_base_url"
				name="<?php echo esc_attr( LINK_HOPPER_OPTION_KEY ); ?>[baseURL]"
				value="<?php echo esc_attr( $options['baseURL'] ); ?>"
				class="regular-text"
			/>
			<span>/</span>
		</div>
		<p class="description">
			<?php esc_html_e( 'Single word, e.g. "hop" or "out". Allowed characters: letters, numbers, hyphens, underscores.', 'link-hopper' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the Hops repeater table.
	 * Used as the section callback so the table appears under the "Hops" heading.
	 */
	public function render_hops_section() {
		$options  = $this->get_options();
		$hops     = $options['hops'];
		$base_url = trailingslashit( home_url() ) . $this->get_base_url() . '/';
		?>
		<table class="widefat" id="link-hopper-hops-table">
			<thead>
				<tr>
					<th class="col-name"><?php esc_html_e( 'Hop Name', 'link-hopper' ); ?></th>
					<th class="col-url"><?php esc_html_e( 'Destination URL', 'link-hopper' ); ?></th>
					<th class="col-test"><?php esc_html_e( 'Test', 'link-hopper' ); ?></th>
					<th class="col-remove"></th>
				</tr>
			</thead>
			<tbody id="link-hopper-hops-body">
				<?php
				$index = 0;
				foreach ( $hops as $name => $url ) {
					$this->render_hop_row( $index++, $name, $url, $base_url );
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="4">
						<button type="button" id="link-hopper-add-row" class="button button-secondary">
							<?php esc_html_e( '+ Add Hop', 'link-hopper' ); ?>
						</button>
					</td>
				</tr>
			</tfoot>
		</table>
		<?php
	}

	/**
	 * Render a single hop table row.
	 *
	 * @param int    $index    Array index used in input field names.
	 * @param string $name     Hop slug.
	 * @param string $url      Destination URL.
	 * @param string $base_url Full base URL prefix for building the Test link.
	 */
	private function render_hop_row( $index, $name = '', $url = '', $base_url = '' ) {
		$option_key = LINK_HOPPER_OPTION_KEY;
		?>
		<tr class="link-hopper-hop-row">
			<td class="col-name">
				<input
					type="text"
					name="<?php echo esc_attr( $option_key ); ?>[hops][<?php echo absint( $index ); ?>][name]"
					value="<?php echo esc_attr( $name ); ?>"
					class="widefat link-hopper-hop-name"
					placeholder="hop-name"
				/>
			</td>
			<td class="col-url">
				<input
					type="url"
					name="<?php echo esc_attr( $option_key ); ?>[hops][<?php echo absint( $index ); ?>][url]"
					value="<?php echo esc_attr( $url ); ?>"
					class="widefat link-hopper-hop-url"
					placeholder="https://example.com"
				/>
			</td>
			<td class="col-test">
				<?php if ( $name && $base_url ) : ?>
					<a href="<?php echo esc_url( $base_url . $name . '/' ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'Test', 'link-hopper' ); ?>
					</a>
				<?php else : ?>
					&nbsp;
				<?php endif; ?>
			</td>
			<td class="col-remove">
				<button
					type="button"
					class="button-link link-hopper-remove-row"
					aria-label="<?php esc_attr_e( 'Remove this hop', 'link-hopper' ); ?>"
				>&times;</button>
			</td>
		</tr>
		<?php
	}

	// =========================================================================
	// Admin Page
	// =========================================================================

	/**
	 * Render the full admin options page.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( ! get_option( 'permalink_structure' ) ) : ?>
				<div class="notice notice-error">
					<p>
						<strong><?php esc_html_e( 'Warning:', 'link-hopper' ); ?></strong>
						<?php esc_html_e( 'Link Hopper requires a non-default permalink structure to work.', 'link-hopper' ); ?>
						<a href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>" class="button button-small">
							<?php esc_html_e( 'Update Permalink Settings', 'link-hopper' ); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'link_hopper' );
				do_settings_sections( 'link-hopper' );
				submit_button( __( 'Save Settings', 'link-hopper' ) );
				?>
			</form>
		</div>
		<?php
	}
}
