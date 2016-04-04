<?php

/**
 * Archiver
 *
 * This plugin was generated using Mickey Kay's wp-plugin grunt-init
 * template: https://github.com/MickeyKay/wp-plugin
 *
 * @link              http://wordpress.org/plugins/archiver
 * @since             1.0.0
 * @package           Archiver
 *
 * @wordpress-plugin
 * Plugin Name:       Archiver
 * Plugin URI:        http://wordpress.org/plugins/archiver
 * Description:       Archive your content using the Wayback Machine.
 * Version:           0.0.1
 * Author:            Mickey Kay
 * Author URI:        http://mickeykaycreative.com?utm_source=archiver&utm_medium=plugin-repo&utm_campaign=WordPress%20Plugins/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       archiver
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

register_activation_hook( __FILE__, 'activate_archiver' );
/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-archiver-activator.php
 */
function activate_archiver() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-archiver-activator.php';
	Archiver_Activator::activate();
}

register_deactivation_hook( __FILE__, 'deactivate_archiver' );
/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-archiver-deactivator.php
 */
function deactivate_archiver() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-archiver-deactivator.php';
	Archiver_Deactivator::deactivate();
}

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-archiver.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_archiver() {

	// Pass main plugin file through to plugin class for later use.
	$args = array(
		'plugin_file' => __FILE__,
	);

	$plugin = Archiver::get_instance( $args );

}
run_archiver();
