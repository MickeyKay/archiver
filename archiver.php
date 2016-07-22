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
 * Version:           1.0.5
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

define( 'ARCHIVER_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'ARCHIVER_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );

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
function archiver_run() {
	$plugin = Archiver::get_instance();
	$plugin->run();
	return $plugin;
}
archiver_run();
