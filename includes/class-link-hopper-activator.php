<?php
/**
 * Handles plugin activation and deactivation.
 *
 * @package Link_Hopper
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Fired during plugin activation and deactivation.
 */
class Link_Hopper_Activator {

	/**
	 * Register the rewrite rule and flush on activation.
	 */
	public static function activate() {
		$options  = get_option( LINK_HOPPER_OPTION_KEY, array() );
		$base_url = ! empty( $options['base_url'] ) ? $options['base_url'] : 'hop';

		add_rewrite_rule(
			'^' . preg_quote( $base_url, '/' ) . '/([^/]+)/?$',
			'index.php?link_hopper_hop=$matches[1]',
			'top'
		);

		flush_rewrite_rules();
	}

	/**
	 * Remove the rewrite rule on deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}

register_activation_hook( LINK_HOPPER_PLUGIN_FILE, array( 'Link_Hopper_Activator', 'activate' ) );
register_deactivation_hook( LINK_HOPPER_PLUGIN_FILE, array( 'Link_Hopper_Activator', 'deactivate' ) );
