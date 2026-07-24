<?php
/**
 * Plugin Name: Download Plugins and Themes in ZIP from Dashboard
 * Plugin URI: https://wpfactory.com/item/download-plugins-and-themes-from-dashboard-wordpress-plugin/
 * Description: Download installed plugins and themes ZIP files directly from your admin dashboard without using FTP.
 * Version: 2.1.0
 * Author: WPFactory
 * Author URI: https://wpfactory.com
 * Text Domain: download-plugins-dashboard
 * Domain Path: /langs
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WPFactory\Download_Plugins_Dashboard
 */

defined( 'ABSPATH' ) || exit;

if ( 'download-plugins-from-dashboard.php' === basename( __FILE__ ) ) {
	/**
	 * Check if Pro plugin version is activated.
	 *
	 * @version 1.8.0
	 * @since   1.8.0
	 *
	 * @see https://developer.wordpress.org/reference/functions/is_plugin_active/
	 * @see https://developer.wordpress.org/reference/functions/is_plugin_active_for_network/
	 */
	$plugin = 'download-plugins-from-dashboard-pro/download-plugins-from-dashboard-pro.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	if (
		in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) ||
		(
			is_multisite() &&
			array_key_exists( $plugin, (array) get_site_option( 'active_sitewide_plugins', array() ) )
		)
	) {
		return;
	}
}

/**
 * Plugin version constant.
 */
if ( ! defined( 'ALG_DOWNLOAD_PLUGINS_VERSION' ) ) {
	define( 'ALG_DOWNLOAD_PLUGINS_VERSION', '2.1.0' );
}

/**
 * Plugin file constant.
 */
if ( ! defined( 'ALG_DOWNLOAD_PLUGINS_FILE' ) ) {
	define( 'ALG_DOWNLOAD_PLUGINS_FILE', __FILE__ );
}

/**
 * Composer autoload.
 */
if ( ! class_exists( 'Alg_Download_Plugins' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
}

/**
 * Load main plugin class.
 *
 * @version 2.1.0
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-alg-download-plugins.php';

if ( ! function_exists( 'alg_download_plugins' ) ) {
	/**
	 * Returns the main instance of Alg_Download_Plugins to prevent the need to use globals.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @return Alg_Download_Plugins
	 */
	function alg_download_plugins() {
		return Alg_Download_Plugins::instance();
	}
}

/**
 * Init.
 *
 * @version 2.1.0
 */
add_action( 'plugins_loaded', 'alg_download_plugins' );
