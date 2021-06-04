<?php
/*
Plugin Name: Download Plugins and Themes from Dashboard
Plugin URI: https://wpfactory.com/item/download-plugins-and-themes-from-dashboard/
Description: Download installed plugins and themes ZIP files directly from your admin dashboard without using FTP.
Version: 1.7.2
Author: WPFactory
Author URI: https://wpfactory.com
Text Domain: download-plugins-dashboard
Domain Path: /langs
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_Download_Plugins' ) ) :

/**
 * Main Alg_Download_Plugins Class
 *
 * @version 1.7.1
 * @since   1.0.0
 *
 * @class   Alg_Download_Plugins
 */
final class Alg_Download_Plugins {

	/**
	 * Plugin version.
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	public $version = '1.7.2';

	/**
	 * @var   Alg_Download_Plugins The single instance of the class
	 * @since 1.0.0
	 */
	protected static $_instance = null;

	/**
	 * Main Alg_Download_Plugins Instance.
	 *
	 * Ensures only one instance of Alg_Download_Plugins is loaded or can be loaded.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @static
	 * @return  Alg_Download_Plugins - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Alg_Download_Plugins Constructor.
	 *
	 * @version 1.7.1
	 * @since   1.0.0
	 *
	 * @access  public
	 *
	 * @todo    [next] (dev) load everything on `is_admin()` only?
	 */
	function __construct() {

		// Check for active plugin(s)
		if ( 'download-plugins-from-dashboard.php' === basename( __FILE__ ) && $this->is_plugin_active( 'download-plugins-from-dashboard-pro/download-plugins-from-dashboard-pro.php' ) ) {
			return;
		}

		// Plugin file constant
		if ( ! defined( 'ALG_DOWNLOAD_PLUGINS_FILE' ) ) {
			define( 'ALG_DOWNLOAD_PLUGINS_FILE', __FILE__ );
		}

		// Translation file
		add_action( 'init', array( $this, 'localize' ) );

		// Pro
		if ( 'download-plugins-from-dashboard-pro.php' === basename( __FILE__ ) ) {
			require_once( 'includes/pro/class-alg-download-plugins-pro.php' );
		}

		// Includes
		$this->settings = require_once( 'includes/settings/class-alg-download-plugins-settings.php' );
		$this->core     = require_once( 'includes/class-alg-download-plugins-core.php' );

		// Action links
		if ( is_admin() ) {
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
		}

	}

	/**
	 * is_plugin_active.
	 *
	 * @version 1.7.0
	 * @since   1.7.0
	 */
	function is_plugin_active( $plugin ) {
		return ( function_exists( 'is_plugin_active' ) ? is_plugin_active( $plugin ) :
			(
				in_array( $plugin, apply_filters( 'active_plugins', ( array ) get_option( 'active_plugins', array() ) ) ) ||
				( is_multisite() && array_key_exists( $plugin, ( array ) get_site_option( 'active_sitewide_plugins', array() ) ) )
			)
		);
	}

	/**
	 * localize.
	 *
	 * @version 1.7.1
	 * @since   1.7.1
	 */
	function localize() {
		load_plugin_textdomain( 'download-plugins-dashboard', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @version 1.7.0
	 * @since   1.3.0
	 *
	 * @param   mixed $links
	 * @return  array
	 */
	function action_links( $links ) {
		$custom_links   = array();
		$custom_links[] = '<a href="' . admin_url( 'options-general.php?page=download-plugins-dashboard' ) . '">' . __( 'Settings', 'download-plugins-dashboard' ) . '</a>';
		if ( 'download-plugins-from-dashboard.php' === basename( __FILE__ ) ) {
			$custom_links[] = '<a target="_blank" style="font-weight: bold; color: green;" href="https://wpfactory.com/item/download-plugins-and-themes-from-dashboard/">' .
				__( 'Go Pro', 'download-plugins-dashboard' ) . '</a>';
		}
		return array_merge( $custom_links, $links );
	}

	/**
	 * Get the plugin url.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @return  string
	 */
	function plugin_url() {
		return untrailingslashit( plugin_dir_url( __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @return  string
	 */
	function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

}

endif;

if ( ! function_exists( 'alg_download_plugins' ) ) {
	/**
	 * Returns the main instance of Alg_Download_Plugins to prevent the need to use globals.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 *
	 * @return  Alg_Download_Plugins
	 *
	 * @todo    [next] (dev) run on `plugins_loaded`?
	 */
	function alg_download_plugins() {
		return Alg_Download_Plugins::instance();
	}
}

alg_download_plugins();
