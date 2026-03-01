<?php
/**
 * Plugin Name:  Link Hopper
 * Plugin URI:   http://www.fergusweb.net/software/linkhopper/
 * Description:  Provides easy outgoing link masking via site.com/hop/name/ URLs. Configure hops via wp-admin.
 * Version:      1.5.0
 * Author:       Anthony Ferguson
 * Author URI:   http://www.fergusweb.net
 * License:      GPLv3 or later
 * License URI:  https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Link_Hopper
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

// Declare constants
define( 'LINK_HOPPER_VERSION', '1.5.0' );
define( 'LINK_HOPPER_OPTION_KEY', 'link_hopper_options' );
define( 'LINK_HOPPER_PLUGIN_FILE', __FILE__ );

// Include required files
require_once plugin_dir_path( __FILE__ ) . 'includes/class-link-hopper-activator.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-link-hopper-redirector.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-link-hopper-admin.php';

// Load the Redirector
new Link_Hopper_Redirector();

// Load the Admin class if we're in the admin area
if ( is_admin() ) {
	new Link_Hopper_Admin();
}
