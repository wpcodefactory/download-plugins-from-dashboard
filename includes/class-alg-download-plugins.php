<?php
/**
 * Download Plugins and Themes from Dashboard - Main Plugin Class
 *
 * @version 2.1.0
 * @since   1.0.0
 *
 * @author WPFactory
 *
 * @package WPFactory\Download_Plugins_Dashboard
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_Download_Plugins' ) ) :

	/**
	 * Alg_Download_Plugins class.
	 */
	final class Alg_Download_Plugins {

		/**
		 * Plugin version.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $version = ALG_DOWNLOAD_PLUGINS_VERSION;

		/**
		 * Instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Alg_Download_Plugins The single instance of the class
		 */
		protected static $_instance = null; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

		/**
		 * Settings.
		 *
		 * @since 1.8.4
		 *
		 * @var Alg_Download_Plugins_Settings
		 */
		public $settings;

		/**
		 * Core.
		 *
		 * @since 1.8.4
		 *
		 * @var Alg_Download_Plugins_Core
		 */
		public $core;

		/**
		 * Pro.
		 *
		 * @since 2.1.0
		 *
		 * @var Alg_Download_Plugins_Pro
		 */
		public $pro;

		/**
		 * Main Alg_Download_Plugins Instance.
		 *
		 * Ensures only one instance of Alg_Download_Plugins is loaded or can be loaded.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 *
		 * @static
		 *
		 * @return Alg_Download_Plugins - Main instance
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
		 * @version 2.1.0
		 * @since   1.0.0
		 *
		 * @access public
		 *
		 * @todo (dev) load everything on `is_admin()` only?
		 */
		public function __construct() {

			// Adds cross-selling library.
			add_action( 'init', array( $this, 'add_cross_selling_library' ) );

			// Pro.
			if ( 'download-plugins-from-dashboard-pro.php' === basename( ALG_DOWNLOAD_PLUGINS_FILE ) ) {
				$this->pro = require_once plugin_dir_path( __FILE__ ) . 'pro/class-alg-download-plugins-pro.php';
			}

			// Includes.
			$this->settings = require_once plugin_dir_path( __FILE__ ) . 'settings/class-alg-download-plugins-settings.php';
			$this->core     = require_once plugin_dir_path( __FILE__ ) . 'class-alg-download-plugins-core.php';

			// Action links.
			if ( is_admin() ) {
				add_filter(
					'plugin_action_links_' . plugin_basename( ALG_DOWNLOAD_PLUGINS_FILE ),
					array( $this, 'action_links' )
				);
			}
		}

		/**
		 * Add cross-selling library.
		 *
		 * @version 1.9.2
		 * @since   1.9.2
		 *
		 * @return void
		 */
		public function add_cross_selling_library() {
			if ( ! is_admin() ) {
				return;
			}
			// Cross-selling library.
			$cross_selling = new \WPFactory\WPFactory_Cross_Selling\WPFactory_Cross_Selling();
			$cross_selling->setup( array( 'plugin_file_path' => ALG_DOWNLOAD_PLUGINS_FILE ) );
			$cross_selling->init();
		}

		/**
		 * Show action links on the plugin screen.
		 *
		 * @version 2.1.0
		 * @since   1.3.0
		 *
		 * @param mixed $links Existing plugin action links.
		 *
		 * @return array
		 */
		public function action_links( $links ) {
			$custom_links = array();

			$custom_links[] = '<a href="' . admin_url( 'admin.php?page=download-plugins-dashboard' ) . '">' .
				__( 'Settings', 'download-plugins-dashboard' ) .
			'</a>';

			if ( 'download-plugins-from-dashboard.php' === basename( ALG_DOWNLOAD_PLUGINS_FILE ) ) {
				$custom_links[] = '<a target="_blank" style="font-weight: bold; color: green;" href="https://wpfactory.com/item/download-plugins-and-themes-from-dashboard-wordpress-plugin/">' .
					__( 'Go Pro', 'download-plugins-dashboard' ) .
				'</a>';
			}

			return array_merge( $custom_links, $links );
		}

		/**
		 * Get the plugin url.
		 *
		 * @version 1.8.0
		 * @since   1.0.0
		 *
		 * @return string
		 */
		public function plugin_url() {
			return untrailingslashit( plugin_dir_url( ALG_DOWNLOAD_PLUGINS_FILE ) );
		}

		/**
		 * Get the plugin path.
		 *
		 * @version 1.8.0
		 * @since   1.0.0
		 *
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( ALG_DOWNLOAD_PLUGINS_FILE ) );
		}
	}

endif;
