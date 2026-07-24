<?php
/**
 * Download Plugins and Themes from Dashboard - Core Class
 *
 * @version 2.1.0
 * @since   1.2.0
 *
 * @author WPFactory
 *
 * @package WPFactory\Download_Plugins_Dashboard
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_Download_Plugins_Core' ) ) :

	/**
	 * Alg_Download_Plugins_Core class.
	 */
	class Alg_Download_Plugins_Core {

		/**
		 * System requirements check.
		 *
		 * @since 1.8.6
		 *
		 * @var bool
		 */
		public $system_requirements_check;

		/**
		 * Last error.
		 *
		 * @since 1.8.6
		 *
		 * @var string
		 */
		public $last_error;

		/**
		 * Constructor.
		 *
		 * @version 2.1.0
		 * @since   1.2.0
		 */
		public function __construct() {
			// Links.
			add_filter( 'plugin_action_links', array( $this, 'add_plugin_download_action_links' ), PHP_INT_MAX, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'add_theme_download_links' ) );

			// Download plugin/theme.
			add_action( 'admin_init', array( $this, 'download_plugin' ) );
			add_action( 'admin_init', array( $this, 'download_theme' ) );

			// Download all plugins/themes.
			add_action( 'admin_init', array( $this, 'download_plugin_all' ) );
			add_action( 'admin_init', array( $this, 'download_theme_all' ) );

			// Plugin and theme version.
			add_filter( 'alg_download_plugins_version_separator_char', array( $this, 'change_version_separator' ) );

			// Bulk Action (Pro version message).
			add_filter( 'bulk_actions-plugins', array( $this, 'bulk_action' ) );
			add_filter( 'handle_bulk_actions-plugins', array( $this, 'bulk_action_download_plugins' ), 10, 1 );
			add_action( 'admin_notices', array( $this, 'bulk_action_notices' ) );
		}

		/**
		 * Change version separator.
		 *
		 * @version 1.8.9
		 * @since   1.8.9
		 *
		 * @param string $char Version separator character.
		 *
		 * @return false|mixed|null
		 */
		public function change_version_separator( $char ) {
			return get_option( 'alg_download_plugins_dashboard_version_separator_char', '.' );
		}

		/**
		 * Add theme download links.
		 *
		 * @version 2.1.0
		 * @since   1.1.0
		 *
		 * @param string $hook_suffix The current admin page hook suffix.
		 *
		 * @todo (v2.1.0) the button is displayed only for the first 20 themes?
		 * @todo (dev) add download links to each theme's "Theme Details"
		 */
		public function add_theme_download_links( $hook_suffix ) {
			if ( 'themes.php' !== $hook_suffix ) {
				return;
			}

			$min = ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ? '' : '.min' );
			wp_enqueue_script(
				'alg-download-plugins-theme-download-link',
				alg_download_plugins()->plugin_url() . '/assets/js/alg-download-plugins-theme-download-link' . $min . '.js',
				array( 'jquery' ),
				alg_download_plugins()->version,
				true
			);
			wp_localize_script(
				'alg-download-plugins-theme-download-link',
				'algDownloadPluginsDashboard',
				array(
					'downloadLinkText' => __( 'Download ZIP', 'download-plugins-dashboard' ),
					'themesURL'        => admin_url( 'themes.php' ),
					'nonce'            => array(
						'param' => 'alg_nonce',
						'value' => wp_create_nonce( 'alg_download_item' ),
					),
				)
			);
		}

		/**
		 * Show action links on the plugin screen.
		 *
		 * @version 2.1.0
		 * @since   1.0.0
		 *
		 * @param array  $actions     Existing action links.
		 * @param string $plugin_file The plugin file path.
		 */
		public function add_plugin_download_action_links( $actions, $plugin_file ) {
			$plugin_file       = explode( '/', $plugin_file );
			$download_btn_text = apply_filters(
				'alg_download_plugins_download_plugin_btn_text',
				__( 'Download ZIP', 'download-plugins-dashboard' )
			);
			if ( isset( $plugin_file[0] ) ) {
				$extra_params = (
					isset( $_GET['plugin_status'] ) && in_array( $_GET['plugin_status'], array( 'mustuse', 'dropins' ) ) ? // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'&alg_download_plugin_status=' . sanitize_text_field( wp_unslash( $_GET['plugin_status'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					''
				);
				$link         = add_query_arg(
					array(
						'alg_download_plugin' => $plugin_file[0] . $extra_params,
						'alg_nonce'           => wp_create_nonce( 'alg_download_item' ),
					),
					admin_url( 'plugins.php' )
				);
				$actions      = array_merge(
					$actions,
					array(
						'<a class="alg_download_plugin" href="' . $link . '">' .
						$download_btn_text . '</a>',
					)
				);
			}
			return $actions;
		}

		/**
		 * Get system temporary directory.
		 *
		 * @version 1.7.0
		 * @since   1.7.0
		 *
		 * @todo (dev) check `open_basedir` for `is_writable()`
		 */
		public function get_sys_temp_dir() {
			$dir = sys_get_temp_dir();
			if ( ! empty( $dir ) && is_writable( $dir ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
				return $dir;
			} else {
				$dir = ini_get( 'upload_tmp_dir' );
				if ( ! empty( $dir ) && is_writable( $dir ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
					return $dir;
				} else {
					$dir = wp_upload_dir();
					if ( ! empty( $dir['path'] ) && is_writable( $dir['path'] ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
						return $dir['path'];
					} elseif ( ! empty( $dir['basedir'] ) && is_writable( $dir['basedir'] ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
						return $dir['basedir'];
					} else {
						$dir = ini_get( 'open_basedir' );
						return trailingslashit( $dir );
					}
				}
			}
		}

		/**
		 * Get temporary directory.
		 *
		 * @version 2.1.0
		 * @since   1.4.3
		 */
		public function get_temp_dir() {
			$temp_dir = get_option( 'alg_download_plugins_dashboard_temp_dir', '' );
			return (
				'' !== $temp_dir ?
				$temp_dir :
				$this->get_sys_temp_dir()
			);
		}

		/**
		 * Download all themes.
		 *
		 * @version 2.1.0
		 * @since   1.4.0
		 */
		public function download_theme_all() {
			if (
				isset( $_GET['alg_download_theme_all'] ) &&
				is_user_logged_in() &&
				current_user_can( 'switch_themes' )
			) {
				if ( ! $this->check_system_requirements() ) {
					return false;
				}
				if (
					! isset( $_GET['_wpnonce'] ) ||
					! wp_verify_nonce(
						sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
						'alg_download_theme_all'
					)
				) {
					wp_die( esc_html__( 'Link expired.', 'download-plugins-dashboard' ) );
				}

				$zip_file_name        = 'themes.zip';
				$zip_file_path        = $this->get_temp_dir() . '/' . $zip_file_name;
				$plugin_or_theme_path = get_theme_root();
				$exclude_path         = $plugin_or_theme_path;
				$args                 = array(
					'zip_file_path' => $zip_file_path,
					'exclude_path'  => $exclude_path,
				);
				$files                = $this->get_files( $plugin_or_theme_path );
				if ( $this->create_zip( $args, $files ) ) {
					$this->send_file( $zip_file_name, $zip_file_path );
				} else {
					add_action( 'admin_notices', array( $this, 'create_zip_error_message' ) );
					return false;
				}
			}
		}

		/**
		 * Download all plugins.
		 *
		 * @version 2.1.0
		 * @since   1.4.0
		 *
		 * @todo (dev) `mustuse` and `dropins`
		 */
		public function download_plugin_all() {
			if (
				isset( $_GET['alg_download_plugin_all'] ) &&
				is_user_logged_in() &&
				current_user_can( 'activate_plugins' )
			) {
				if ( ! $this->check_system_requirements() ) {
					return false;
				}
				if (
					! isset( $_GET['_wpnonce'] ) ||
					! wp_verify_nonce(
						sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
						'alg_download_plugin_all'
					)
				) {
					wp_die( esc_html__( 'Link expired.', 'download-plugins-dashboard' ) );
				}

				$zip_file_name        = 'plugins.zip';
				$zip_file_path        = $this->get_temp_dir() . '/' . $zip_file_name;
				$plugin_or_theme_path = $this->get_plugin_dir( 'regular' );
				$exclude_path         = $plugin_or_theme_path;
				$args                 = array(
					'zip_file_path' => $zip_file_path,
					'exclude_path'  => $exclude_path,
				);
				$files                = $this->get_files( $plugin_or_theme_path );
				if ( $this->create_zip( $args, $files ) ) {
					$this->send_file( $zip_file_name, $zip_file_path );
				} else {
					add_action( 'admin_notices', array( $this, 'create_zip_error_message' ) );
					return false;
				}
			}
		}

		/**
		 * Get uploads directory.
		 *
		 * @version 1.4.0
		 * @since   1.4.0
		 *
		 * @param string $subdir The subdirectory within the uploads directory.
		 */
		public function get_uploads_dir( $subdir ) {
			$upload_dir = wp_upload_dir();
			$upload_dir = $upload_dir['basedir'] . '/' . $subdir;
			return str_replace( '\\', '/', $upload_dir );
		}

		/**
		 * Download theme.
		 *
		 * @version 2.1.0
		 * @since   1.1.0
		 *
		 * @todo (dev) extra validation (i.e. check for `$theme_name` in `wp_get_themes()`)
		 */
		public function download_theme() {
			if (
				isset( $_GET['alg_download_theme'] ) &&
				is_user_logged_in() &&
				current_user_can( 'switch_themes' ) &&
				isset( $_GET['alg_nonce'] ) &&
				wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_GET['alg_nonce'] ) ),
					'alg_download_item'
				)
			) {
				$theme_name = basename( sanitize_text_field( wp_unslash( $_GET['alg_download_theme'] ) ) );
				if (
					'' != $theme_name &&
					is_a( ( $_theme = wp_get_theme( $theme_name ) ), 'WP_Theme' ) &&
					$_theme->exists()
				) {
					$theme_root = get_theme_root();
					if ( 'yes' === get_option( 'alg_download_plugins_dashboard_themes_append_version', 'no' ) ) {
						$_theme  = wp_get_theme( $theme_name, $theme_root );
						$version = ( is_object( $_theme ) ? $_theme->get( 'Version' ) : '' );
					} else {
						$version = '';
					}
					$add_main_dir = ( 'yes' === get_option( 'alg_download_plugins_dashboard_themes_add_main_dir', 'yes' ) );
					$this->download_plugin_or_theme( $theme_root, $theme_name, $version, $add_main_dir );
				}
			}
		}

		/**
		 * Get plugins.
		 *
		 * @version 2.1.0
		 * @since   1.5.0
		 *
		 * @param bool|string $status The status of the plugins to retrieve.
		 *
		 * @todo (dev) recheck if we really need `require_once ABSPATH . 'wp-admin/includes/plugin.php'`
		 */
		public function get_plugins( $status = false ) {
			if ( ! $status ) {
				$status = (
					isset( $_GET['alg_download_plugin_status'] ) ? // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					sanitize_text_field( wp_unslash( $_GET['alg_download_plugin_status'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'regular'
				);
			}

			if (
				! function_exists( 'get_plugins' ) ||
				! function_exists( 'get_dropins' ) ||
				! function_exists( 'get_mu_plugins' )
			) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			switch ( $status ) {
				case 'mustuse':
					return get_mu_plugins();
				case 'dropins':
					return get_dropins();
				default: // 'regular'
					return get_plugins();
			}
		}

		/**
		 * Get plugin directory.
		 *
		 * @version 2.1.0
		 * @since   1.5.0
		 *
		 * @param bool|string $status The status of the plugins to retrieve directory.
		 */
		public function get_plugin_dir( $status = false ) {
			if ( ! $status ) {
				$status = (
					isset( $_GET['alg_download_plugin_status'] ) ? // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					sanitize_text_field( wp_unslash( $_GET['alg_download_plugin_status'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'regular'
				);
			}

			switch ( $status ) {
				case 'mustuse':
					return WPMU_PLUGIN_DIR;
				case 'dropins':
					return WP_CONTENT_DIR;
				default: // 'regular'
					return WP_PLUGIN_DIR;
			}
		}

		/**
		 * Download plugin.
		 *
		 * @version 2.1.0
		 * @since   1.0.0
		 */
		public function download_plugin() {
			if (
				isset( $_GET['alg_download_plugin'] ) &&
				is_user_logged_in() &&
				current_user_can( 'activate_plugins' ) &&
				isset( $_GET['alg_nonce'] ) &&
				wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_GET['alg_nonce'] ) ),
					'alg_download_item'
				)
			) {
				$plugin_name = basename( sanitize_text_field( wp_unslash( $_GET['alg_download_plugin'] ) ) );
				if ( '' != $plugin_name ) {
					$all_plugins = $this->get_plugins();
					foreach ( $all_plugins as $plugin_file => $plugin_data ) {
						$plugin_file = explode( '/', $plugin_file );
						if ( isset( $plugin_file[0] ) && $plugin_name === $plugin_file[0] ) {
							// Validated successfully.
							$version      = ( 'yes' === get_option( 'alg_download_plugins_dashboard_plugins_append_version', 'no' ) ) ? $plugin_data['Version'] : '';
							$add_main_dir = ( 'yes' === get_option( 'alg_download_plugins_dashboard_plugins_add_main_dir', 'yes' ) );
							$this->download_plugin_or_theme( $this->get_plugin_dir(), $plugin_name, $version, $add_main_dir, ( isset( $plugin_file[1] ) ) );
							break;
						}
					}
				}
			}
		}

		/**
		 * Check system requirements.
		 *
		 * @version 1.4.0
		 * @since   1.4.0
		 */
		public function check_system_requirements() {
			if ( ! isset( $this->system_requirements_check ) ) {
				$this->system_requirements_check = ( class_exists( 'RecursiveIteratorIterator' ) && function_exists( 'gzopen' ) );
			}
			if ( ! $this->system_requirements_check ) {
				add_action( 'admin_notices', array( $this, 'system_requirements_error_message' ) );
			}
			return $this->system_requirements_check;
		}

		/**
		 * System requirements error message.
		 *
		 * @version 2.1.0
		 * @since   1.1.0
		 */
		public function system_requirements_error_message() {

			/* Translators: %1$s: Plugin name, %2$s: Requirement name. */
			$message = __( 'To use %1$s plugin, %2$s must be available on your server.', 'download-plugins-dashboard' );

			$plugin_name = '<strong>' . __( 'Download Plugins and Themes from Dashboard', 'download-plugins-dashboard' ) . '</strong>';

			if ( ! class_exists( 'RecursiveIteratorIterator' ) ) {
				$required = '<code>RecursiveIteratorIterator</code>';
				echo wp_kses_post(
					'<div class="notice notice-error"><p>' .
					sprintf( $message, $plugin_name, $required ) .
					'</p></div>'
				);
			}

			if ( ! function_exists( 'gzopen' ) ) {
				$required = '<code>zlib</code>';
				echo wp_kses_post(
					'<div class="notice notice-error"><p>' .
					sprintf( $message, $plugin_name, $required ) .
					'</p></div>'
				);
			}
		}

		/**
		 * Create ZIP error message.
		 *
		 * @version 2.1.0
		 * @since   1.4.0
		 */
		public function create_zip_error_message() {
			echo wp_kses_post(
				'<div class="notice notice-error"><p>' .
				(
					! empty( $this->last_error ) ?
					sprintf(
						/* Translators: %s: Error message. */
						__( 'Error: %s', 'download-plugins-dashboard' ),
						$this->last_error
					) :
					__( 'Something went wrong...', 'download-plugins-dashboard' )
				) .
				'</p></div>'
			);
		}

		/**
		 * Download plugin or theme.
		 *
		 * @version 1.8.9
		 * @since   1.1.0
		 *
		 * @param string $plugin_or_theme_dir  The directory of the plugin or theme.
		 * @param string $plugin_or_theme_name The name of the plugin or theme.
		 * @param string $version              The version of the plugin or theme.
		 * @param bool   $add_main_dir         Whether to add the main directory to the ZIP.
		 * @param bool   $is_dir               Whether the plugin or theme is a directory.
		 *
		 * @todo (dev) recheck if themes can be single file (i.e. `$is_dir = false`)
		 */
		public function download_plugin_or_theme( $plugin_or_theme_dir, $plugin_or_theme_name, $version, $add_main_dir, $is_dir = true ) {
			if ( ! $this->check_system_requirements() ) {
				return false;
			}
			$plugin_or_theme_name = basename( $plugin_or_theme_name );
			$version_separator    = sanitize_text_field( apply_filters( 'alg_download_plugins_version_separator_char', '.' ) );
			$version_separator    = strlen( $version_separator ) > 0 ? $version_separator[0] : $version_separator;
			$zip_file_name        = $plugin_or_theme_name . ( '' != $version ? $version_separator : '' ) . $version . '.zip';
			$zip_file_path        = $this->get_temp_dir() . '/' . $zip_file_name;
			$plugin_or_theme_path = $plugin_or_theme_dir . '/' . $plugin_or_theme_name;
			$exclude_path         = ( ! $is_dir || $add_main_dir ? $plugin_or_theme_dir : $plugin_or_theme_path );
			$args                 = array(
				'zip_file_path' => $zip_file_path,
				'exclude_path'  => $exclude_path,
			);
			$files                = ( $is_dir ? $this->get_files( $plugin_or_theme_path ) : array( $plugin_or_theme_path ) );
			if ( $this->create_zip( $args, $files ) ) {
				$this->send_file( $zip_file_name, $zip_file_path );
			} else {
				add_action( 'admin_notices', array( $this, 'create_zip_error_message' ) );
				return false;
			}
		}

		/**
		 * Get files.
		 *
		 * @version 1.8.6
		 * @since   1.3.0
		 *
		 * @param string $plugin_or_theme_path The path to the plugin or theme.
		 */
		public function get_files( $plugin_or_theme_path ) {
			if ( ! file_exists( $plugin_or_theme_path ) ) {
				return array();
			}
			$files       = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $plugin_or_theme_path ),
				RecursiveIteratorIterator::LEAVES_ONLY
			);
			$files_paths = array();
			foreach ( $files as $name => $file ) {
				if ( ! $file->isDir() ) {
					$file_path     = str_replace( '\\', '/', $file->getRealPath() );
					$files_paths[] = $file_path;
				}
			}

			return $files_paths;
		}

		/**
		 * Create ZIP.
		 *
		 * @version 1.8.6
		 * @since   1.3.0
		 *
		 * @param array $args  The arguments for creating the ZIP file.
		 * @param array $files The files to include in the ZIP file.
		 */
		public function create_zip( $args, $files ) {
			if ( empty( $files ) ) {
				return false;
			}
			if ( file_exists( $args['zip_file_path'] ) ) {
				unlink( $args['zip_file_path'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			}
			$zip_library = get_option(
				'alg_download_plugins_dashboard_zip_library',
				( class_exists( 'ZipArchive' ) ? 'ziparchive' : 'pclzip' )
			);
			switch ( $zip_library ) {
				case 'pclzip':
					return $this->create_zip_pclzip( $args, $files );
				default: // 'ziparchive':
					return $this->create_zip_ziparchive( $args, $files );
			}
		}

		/**
		 * Create ZIP using ZipArchive.
		 *
		 * @version 2.1.0
		 * @since   1.3.0
		 *
		 * @param array $args  The arguments for creating the ZIP file.
		 * @param array $files The files to include in the ZIP file.
		 *
		 * @todo (dev) check `new ZipArchive`, `$zip->addFile`, `$zip->close` for errors
		 */
		public function create_zip_ziparchive( $args, $files ) {
			$zip    = new ZipArchive();
			$result = $zip->open( $args['zip_file_path'], ZipArchive::CREATE | ZipArchive::OVERWRITE );
			if ( true !== $result ) {
				$this->last_error = sprintf(
					/* Translators: %1$s: "ZipArchive", %2$s: Error code. */
					__( '%1$s can not open a new zip archive (error code %2$s).', 'download-plugins-dashboard' ),
					'<code>ZipArchive</code>',
					'<code>' . $result . '</code>'
				);
				return false;
			}
			$exclude_from_relative_path = strlen( $args['exclude_path'] ) + 1;
			foreach ( $files as $file_path ) {
				$zip->addFile( $file_path, substr( $file_path, $exclude_from_relative_path ) );
			}
			$zip->close();
			return true;
		}

		/**
		 * Create ZIP using PclZip.
		 *
		 * @version 2.1.0
		 * @since   1.3.0
		 *
		 * @see http://www.phpconcept.net/pclzip
		 *
		 * @param array $args  The arguments for creating the ZIP file.
		 * @param array $files The files to include in the ZIP file.
		 *
		 * @todo (dev) check `new PclZip` for errors
		 */
		public function create_zip_pclzip( $args, $files ) {
			require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
			$zip = new PclZip( $args['zip_file_path'] );
			if ( 0 == $zip->create( $files, PCLZIP_OPT_REMOVE_PATH, $args['exclude_path'] ) ) {
				$this->last_error = sprintf( '%s %s.', '<code>PclZip</code>', $zip->errorInfo( true ) );
				return false;
			}
			return true;
		}

		/**
		 * Send file.
		 *
		 * @version 2.1.0
		 * @since   1.3.0
		 *
		 * @see https://stackoverflow.com/questions/11315951/using-the-browser-prompt-to-download-a-file
		 *
		 * @param string $zip_file_name The name of the ZIP file.
		 * @param string $zip_file_path The path to the ZIP file.
		 */
		public function send_file( $zip_file_name, $zip_file_path ) {
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename=' . urlencode( $zip_file_name ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode
			header( 'Content-Description: File Transfer' );
			header( 'Content-Transfer-Encoding: binary' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate' );
			header( 'Pragma: public' );
			header( 'Content-Length: ' . filesize( $zip_file_path ) );
			flush();
			$fp = fopen( $zip_file_path, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			if ( false !== $fp ) {
				while ( ! feof( $fp ) ) {
					echo fread( $fp, 65536 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread, WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary file output for download, cannot escape.
					flush();
				}
				fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

				// Action.
				do_action( 'alg_download_plugins_after_download', $zip_file_path );

				die();
			} else {
				die( esc_html__( 'Unexpected error', 'download-plugins-dashboard' ) );
			}
		}

		/**
		 * Add "Download ZIP" action to bulk actions.
		 *
		 * @version 1.9.1
		 * @since   1.9.1
		 *
		 * @param array $actions The existing bulk actions.
		 */
		public function bulk_action( $actions ) {
			$actions['download_zip_selected'] = __( 'Download ZIP', 'download-plugins-dashboard' );

			return $actions;
		}

		/**
		 * Modify the URL for bulk action redirection.
		 *
		 * @version 2.1.0
		 * @since   1.9.1
		 *
		 * @param string $redirect_url The redirect URL.
		 */
		public function bulk_action_download_plugins( $redirect_url ) {
			$redirect_url = add_query_arg(
				array(
					'alg_download_plugin_bulk_action' => 'pro-version-message',
				),
				$redirect_url
			);

			return $redirect_url;
		}

		/**
		 * Display bulk action notices based on query parameters.
		 *
		 * @version 2.1.0
		 * @since   1.9.1
		 */
		public function bulk_action_notices() {
			if ( ! isset( $_GET['alg_download_plugin_bulk_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			$action = sanitize_text_field( wp_unslash( $_GET['alg_download_plugin_bulk_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( 'pro-version-message' === $action ) {
				$this->pro_version_message();
			}
		}

		/**
		 * Display a notice that the Pro version is required.
		 *
		 * @version 2.1.0
		 * @since   1.9.1
		 */
		public function pro_version_message() {
			$message = sprintf(
				/* Translators: %s: Plugin link. */
				__( 'To use the Bulk Download ZIP, you will need the %s.', 'download-plugins-dashboard' ),
				'<a target="_blank" href="https://wpfactory.com/item/download-plugins-and-themes-from-dashboard-wordpress-plugin/">' .
				__( 'Pro version', 'download-plugins-dashboard' ) .
				'</a>'
			);

			echo wp_kses_post( '<div class="notice notice-error"><p>' . $message . '</p></div>' );
		}
	}

endif;

return new Alg_Download_Plugins_Core();
