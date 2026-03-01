<?php
/**
 * Handles rewrite rule registration and hop redirects.
 *
 * @package Link_Hopper
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Registers the custom rewrite rule and performs redirects on matched URLs.
 */
class Link_Hopper_Redirector {

	/**
	 * Cached plugin options.
	 *
	 * @var array|null
	 */
	private $options = null;

	/**
	 * Constructor — registers frontend hooks.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_redirect' ) );
		add_action(
			'update_option_' . LINK_HOPPER_OPTION_KEY,
			array( $this, 'maybe_flush_rewrite_rules' ),
			10,
			2
		);
	}

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
					'base_url' => 'hop',
					'hops'     => array(),
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
		return $options['base_url'];
	}

	/**
	 * Register the rewrite rule that captures hop URLs.
	 * Called on `init` every request so the rule is available when rules are flushed.
	 */
	public function register_rewrite_rules() {
		$base_url = $this->get_base_url();

		add_rewrite_rule(
			'^' . preg_quote( $base_url, '/' ) . '/([^/]+)/?$',
			'index.php?link_hopper_hop=$matches[1]',
			'top'
		);
	}

	/**
	 * Add the custom query var so WordPress passes it through parse_request().
	 *
	 * @param string[] $vars Registered public query variables.
	 * @return string[]
	 */
	public function register_query_vars( $vars ) {
		$vars[] = 'link_hopper_hop';
		return $vars;
	}

	/**
	 * Perform a 302 redirect when a hop URL is matched.
	 */
	public function handle_redirect() {
		$hop_name = get_query_var( 'link_hopper_hop' );

		if ( empty( $hop_name ) ) {
			return;
		}

		$options = $this->get_options();

		foreach ( $options['hops'] as $hop ) {
			if ( ! empty( $hop['name'] ) && $hop['name'] === $hop_name && ! empty( $hop['url'] ) ) {
				wp_redirect( $hop['url'], 302 ); // phpcs:ignore WordPress.Security.SafeRedirect
				exit;
			}
		}
	}

	/**
	 * Flush rewrite rules whenever the base URL slug changes.
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $new_value New option value.
	 */
	public function maybe_flush_rewrite_rules( $old_value, $new_value ) {
		$old_base = isset( $old_value['base_url'] ) ? $old_value['base_url'] : '';
		$new_base = isset( $new_value['base_url'] ) ? $new_value['base_url'] : '';

		if ( $old_base !== $new_base ) {
			$this->options = null; // Clear cache so register_rewrite_rules picks up new slug.
			$this->register_rewrite_rules();
			flush_rewrite_rules();
		}
	}
}
