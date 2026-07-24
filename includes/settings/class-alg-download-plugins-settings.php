<?php
/**
 * Download Plugins and Themes from Dashboard - Settings Class
 *
 * @version 2.1.0
 * @since   1.2.0
 *
 * @author WPFactory
 *
 * @package WPFactory\Download_Plugins_Dashboard\Settings
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Alg_Download_Plugins_Settings' ) ) :

	/**
	 * Alg_Download_Plugins_Settings class.
	 */
	class Alg_Download_Plugins_Settings {

		/**
		 * ID.
		 *
		 * @since 1.8.4
		 *
		 * @var string
		 */
		public $id;

		/**
		 * Constructor.
		 *
		 * @version 2.1.0
		 * @since   1.2.0
		 */
		public function __construct() {
			$this->id = 'alg_download_plugins_dashboard';
			add_action( 'admin_menu', array( $this, 'add_plugin_menu' ), 90 );
			add_action( 'admin_init', array( $this, 'save_settings' ) );
		}

		/**
		 * Add plugin menu.
		 *
		 * @version 1.9.3
		 * @since   1.2.0
		 */
		public function add_plugin_menu() {
			$admin_menu = WPFactory\WPFactory_Admin_Menu\WPFactory_Admin_Menu::get_instance();
			\add_submenu_page(
				$admin_menu->get_menu_slug(),
				__( 'Download Plugins and Themes from Dashboard', 'download-plugins-dashboard' ),
				__( 'Download plugins and Themes', 'download-plugins-dashboard' ),
				class_exists( 'WooCommerce' ) ? 'manage_woocommerce' : 'manage_options',
				'download-plugins-dashboard',
				array( $this, 'output_plugin_menu' ),
				30
			);
		}

		/**
		 * Get plugin icon.
		 *
		 * @version 2.1.0
		 * @since   2.1.0
		 */
		public function get_plugin_icon() {
			$plugin_icon = new WPFactory\WPFactory_Admin_Menu\Plugin_Icon();
			$plugin_icon->set_args(
				array(
					'get_url_method'    => 'wporg_plugins_api',
					'wporg_plugin_slug' => 'download-plugins-dashboard',
					'style'             => 'vertical-align:middle;',
					'height'            => 50,
				)
			);
			return $plugin_icon->get_plugin_icon_img_html();
		}

		/**
		 * Output plugin menu.
		 *
		 * @version 2.1.0
		 * @since   1.2.0
		 */
		public function output_plugin_menu() {
			?>
			<div class="wrap">
				<h1>
					<?php echo wp_kses_post( $this->get_plugin_icon() ); ?>
					<?php esc_html_e( 'Download Plugins and Themes in ZIP from Dashboard', 'download-plugins-dashboard' ); ?>
				</h1>
				<form action="" method="post">
					<?php
					echo wp_kses(
						$this->get_fields_html(),
						$this->get_allowed_settings_html()
					);
					?>
					<p class="submit">
						<input
							class="button-primary"
							type="submit"
							name="<?php echo esc_attr( $this->id ); ?>_save_settings"
							value="<?php esc_attr_e( 'Save settings', 'download-plugins-dashboard' ); ?>"
						/>
						<input
							class="button-secondary"
							type="submit"
							name="<?php echo esc_attr( $this->id ); ?>_reset_settings"
							value="<?php esc_attr_e( 'Reset settings', 'download-plugins-dashboard' ); ?>"
							onclick="return confirm('<?php echo esc_js( __( 'Are you sure?', 'download-plugins-dashboard' ) ); ?>');"
						/>
						<?php
						wp_nonce_field(
							$this->id . '_save_settings_nonce',
							$this->id . '_save_settings_nonce'
						);
						?>
					</p>
				</form>
			</div>
			<?php
		}

		/**
		 * Get allowed settings HTML.
		 *
		 * @version 2.1.0
		 * @since   2.1.0
		 */
		public function get_allowed_settings_html() {
			$allowed_html = array_merge(
				wp_kses_allowed_html( 'post' ),
				array(

					'input'  => array(
						'type'        => true,
						'id'          => true,
						'name'        => true,
						'style'       => true,
						'class'       => true,
						'value'       => true,
						'min'         => true,
						'max'         => true,
						'step'        => true,
						'placeholder' => true,
						'maxlength'   => true,
						'disabled'    => true,
					),

					'select' => array(
						'id'       => true,
						'name'     => true,
						'style'    => true,
						'class'    => true,
						'disabled' => true,
					),

					'option' => array(
						'value'    => true,
						'selected' => true,
						'disabled' => true,
					),

				)
			);

			$allowed_html['h2']['for'] = true;

			return $allowed_html;
		}

		/**
		 * Save settings.
		 *
		 * @version 2.1.0
		 * @since   1.2.0
		 */
		public function save_settings() {
			if (
				isset( $_POST[ $this->id . '_save_settings' ] ) ||
				isset( $_POST[ $this->id . '_reset_settings' ] )
			) {
				if (
					! current_user_can( 'manage_options' ) ||
					! check_admin_referer( $this->id . '_save_settings_nonce', $this->id . '_save_settings_nonce' )
				) {
					add_action( 'admin_notices', array( $this, 'admin_notice__error' ) );
					return false;
				}
				foreach ( $this->get_settings() as $field ) {
					$field_id    = $this->id . '_' . $field['id'];
					$field_value = null;
					if ( isset( $_POST[ $this->id . '_save_settings' ] ) && isset( $_POST[ $field_id ] ) ) {
						$field_value = sanitize_text_field( wp_unslash( $_POST[ $field_id ] ) );
					} elseif ( isset( $_POST[ $this->id . '_reset_settings' ] ) && isset( $field['default'] ) ) {
						$field_value = $field['default'];
					}
					$prev_value = get_option( $field_id ); // Passed in the `alg_download_plugins_after_update_option` action.
					if ( null !== $field_value ) {
						update_option( $field_id, stripslashes( $field_value ) );
					}
					do_action(
						'alg_download_plugins_after_update_option',
						$field,
						$field_value,
						$prev_value
					);
				}
				add_action( 'admin_notices', array( $this, 'admin_notice__success' ) );
			}
		}

		/**
		 * Admin notice error.
		 *
		 * @version 2.1.0
		 * @since   1.6.0
		 */
		public function admin_notice__error() {
			echo '<div class="notice notice-error">' .
				'<p>' .
					esc_html__( 'Something went wrong!', 'download-plugins-dashboard' ) .
				'</p>' .
			'</div>';
		}

		/**
		 * Admin notice success.
		 *
		 * @version 2.1.0
		 * @since   1.2.0
		 */
		public function admin_notice__success() {
			echo '<div class="notice notice-success is-dismissible">' .
				'<p>' .
					esc_html__( 'Settings saved.', 'download-plugins-dashboard' ) .
				'</p>' .
			'</div>';
		}

		/**
		 * Generate custom attributes string.
		 *
		 * @see WC_Admin_Settings::output_fields()
		 *
		 * @version 1.8.9
		 * @since   1.8.9
		 *
		 * @param array $field Field.
		 *
		 * @return string
		 */
		public function generate_custom_attributes_string( $field ) {
			$custom_atts = '';
			if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {
				$custom_atts = array();
				foreach ( $field['custom_attributes'] as $attribute => $attribute_value ) {
					$custom_atts[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
				}
				$custom_atts = implode( ' ', $custom_atts );
			} elseif ( ! empty( $field['custom_attributes'] ) && is_string( $field['custom_attributes'] ) ) {
				$custom_atts = $field['custom_attributes'];
			}

			return $custom_atts;
		}

		/**
		 * Get fields HTML.
		 *
		 * @version 1.8.9
		 * @since   1.2.0
		 */
		public function get_fields_html() {
			$table_data = array();
			foreach ( $this->get_settings() as $field ) {
				$field_id           = $this->id . '_' . $field['id'];
				$field_html         = '';
				$use_one_col        = false;
				$default_text_style = empty( $field['custom_attributes']['style'] ) ? 'style="width:100%"' : '';
				if ( 'title' != $field['type'] ) {
					$field_title = '<label for="' . $field_id . '">' . $field['title'] . '</label>';
					$field_value = ( false != get_option( $field_id, false ) ? esc_html( get_option( $field_id, false ) ) : $field['default'] );
					$custom_atts = $this->generate_custom_attributes_string( $field );
					switch ( $field['type'] ) {
						case 'select_yes_no':
							$field_html = '<select name="' . $field_id . '" id="' . $field_id . '"' . $custom_atts . '>' .
								'<option value="yes" ' . selected( $field_value, 'yes', false ) . '>' . __( 'Yes', 'download-plugins-dashboard' ) . '</option>' .
								'<option value="no" ' . selected( $field_value, 'no', false ) . '>' . __( 'No', 'download-plugins-dashboard' ) . '</option>' .
							'</select>';
							break;
						case 'select':
							$options = '';
							foreach ( $field['options'] as $id => $desc ) {
								$options .= '<option value="' . $id . '"' . selected( $field_value, $id, false ) . '>' . $desc . '</option>';
							}
							$field_html = '<select name="' . $field_id . '" id="' . $field_id . '"' . $custom_atts . '>' . $options . '</select>';
							break;
						case 'textarea':
							$field_html = '<textarea name="' . $field_id . '" id="' . $field_id . '"' . $default_text_style . $custom_atts . '>' . $field_value . '</textarea>';
							break;
						case 'tool':
							$field_html = '';
							break;
						default:
							$field_html = '<input type="' . $field['type'] . '" name="' . $field_id . '" id="' . $field_id . '" value="' . $field_value . '" ' . $default_text_style .
								$custom_atts . '>';
							break;
					}
				} else {
					$use_one_col = true;
					$field_title = '<h2 style="" for="' . $field_id . '">' . $field['title'] . '</h2>';
				}
				if ( isset( $field['desc'] ) ) {
					$field_html .= ' ' . $field['desc'];
				}

				$table_data[] = array(
					'title'   => $field_title,
					'value'   => $field_html,
					'one_col' => $use_one_col,
				);
			}

			return $this->get_table_html(
				$table_data,
				array(
					'table_heading_type' => 'vertical',
					'table_class'        => 'form-table',
				)
			);
		}

		/**
		 * Get "available in Pro version" HTML.
		 *
		 * @version 2.1.0
		 * @since   1.9.0
		 *
		 * @return string
		 */
		public function get_available_in_pro_version_html() {
			return '<p><span style="margin-right:3px">&#x1F3C6;</span>' .
				sprintf(
					/* Translators: %s: Link. */
					__( 'Unlock the unavailable options with the %s.', 'download-plugins-dashboard' ),
					'<a target="_blank" href="https://wpfactory.com/item/download-plugins-and-themes-from-dashboard-wordpress-plugin/">' .
						__( 'Pro version', 'download-plugins-dashboard' ) .
					'</a>'
				) .
			'</p>';
		}

		/**
		 * Get settings.
		 *
		 * @version 2.1.0
		 * @since   1.2.0
		 */
		public function get_settings() {
			$period_options = array(
				''           => __( 'Do not download', 'download-plugins-dashboard' ),
				'minutely'   => __( 'Download once a minute', 'download-plugins-dashboard' ),
				'hourly'     => __( 'Download once hourly', 'download-plugins-dashboard' ),
				'twicedaily' => __( 'Download twice daily', 'download-plugins-dashboard' ),
				'daily'      => __( 'Download once daily', 'download-plugins-dashboard' ),
				'weekly'     => __( 'Download once weekly', 'download-plugins-dashboard' ),
				'four_weeks' => __( 'Download every 4 weeks', 'download-plugins-dashboard' ),
			);

			return array(
				// Directory.
				array(
					'title' => __( 'Directory', 'download-plugins-dashboard' ),
					'id'    => 'general_settings_directory_title',
					'type'  => 'title',
				),
				array(
					'title'   => __( 'Add plugin directory', 'download-plugins-dashboard' ),
					'desc'    => sprintf(
						'<p class="description">%s</p>',
						__( 'Adds main plugin directory to ZIP.', 'download-plugins-dashboard' )
					),
					'id'      => 'plugins_add_main_dir',
					'type'    => 'select_yes_no',
					'default' => 'yes',
				),
				array(
					'title'   => __( 'Add theme directory', 'download-plugins-dashboard' ),
					'desc'    => sprintf(
						'<p class="description">%s</p>',
						__( 'Adds main theme directory to ZIP.', 'download-plugins-dashboard' )
					),
					'id'      => 'themes_add_main_dir',
					'type'    => 'select_yes_no',
					'default' => 'yes',
				),

				// Version.
				array(
					'title' => __( 'Version', 'download-plugins-dashboard' ),
					'id'    => 'general_settings_version_title',
					'type'  => 'title',
				),
				array(
					'title'   => __( 'Append plugin version', 'download-plugins-dashboard' ),
					'desc'    => sprintf(
						'<p class="description">%s</p>',
						__( 'Appends plugin version number to ZIP filename.', 'download-plugins-dashboard' )
					),
					'id'      => 'plugins_append_version',
					'type'    => 'select_yes_no',
					'default' => 'no',
				),
				array(
					'title'   => __( 'Append theme version', 'download-plugins-dashboard' ),
					'desc'    => sprintf(
						'<p class="description">%s</p>',
						__( 'Appends theme version number to ZIP filename.', 'download-plugins-dashboard' )
					),
					'id'      => 'themes_append_version',
					'type'    => 'select_yes_no',
					'default' => 'no',
				),
				array(
					'title'             => __( 'Version separator character', 'download-plugins-dashboard' ),
					'id'                => 'version_separator_char',
					'type'              => 'text',
					'custom_attributes' => array(
						'maxlength' => 1,
						'style'     => 'width:54px',
					),
					'default'           => '.',
				),

				// Tools.
				array(
					'title' => __( 'Tools', 'download-plugins-dashboard' ),
					'id'    => 'tools_title',
					'type'  => 'title',
					'desc'  => '<p style="font-weight:400">' .
						sprintf(
							/* Translators: %1$s: URL, %2$s: Memory limit, %3$s: Memory limit. */
							__( 'Please note that if you have large number of plugins or themes, you may need to <a href="%1$s" target="_blank">increase your WP memory limits</a> to use "Download all" tools. Your current memory limits are: %2$s (standard) and %3$s (admin).', 'download-plugins-dashboard' ),
							'https://wordpress.org/support/article/editing-wp-config-php/#increasing-memory-allocated-to-php',
							WP_MEMORY_LIMIT,
							WP_MAX_MEMORY_LIMIT
						) .
					'</p>',
				),
				array(
					'title'   => __( 'Plugins', 'download-plugins-dashboard' ),
					'id'      => 'plugins_tools',
					'type'    => 'tool',
					'default' => '',
					'desc'    => (
					'<a href="' . esc_url(
						add_query_arg(
							array(
								'alg_download_plugin_all' => true,
								'_wpnonce'                => wp_create_nonce( 'alg_download_plugin_all' ),
							)
						)
					) . '" class="button">' .
						__( 'Download all', 'download-plugins-dashboard' ) .
					'</a>' .
					'<br /><p class="description">' .
						__( 'Please note that this won\'t include "Must-Use" and "Drop-in" plugins.', 'download-plugins-dashboard' ) . ' ' .
						__( 'However, you can download them from "Plugins" page directly.', 'download-plugins-dashboard' ) .
					'</p>'
				),
				),
				array(
					'title'   => __( 'Themes', 'download-plugins-dashboard' ),
					'id'      => 'themes_tools',
					'type'    => 'tool',
					'default' => '',
					'desc'    => (
					'<a href="' . esc_url(
						add_query_arg(
							array(
								'alg_download_theme_all' => true,
								'_wpnonce'               => wp_create_nonce( 'alg_download_theme_all' ),
							)
						)
					) . '" class="button">' .
						__( 'Download all', 'download-plugins-dashboard' ) .
					'</a>'
				),
				),

				// Advanced Settings.
				array(
					'title' => __( 'Advanced Settings', 'download-plugins-dashboard' ),
					'id'    => 'advanced_settings_title',
					'type'  => 'title',
					'desc'  => apply_filters( 'alg_download_plugins_settings', $this->get_available_in_pro_version_html() ),
				),
				array(
					'title'   => __( 'ZIP library', 'download-plugins-dashboard' ),
					'desc'    => '<p class="description">' .
						__( 'Sets which ZIP library should be used.', 'download-plugins-dashboard' ) . ' ' .
						__( 'Leave the default value if not sure.', 'download-plugins-dashboard' ) .
					'</p>',
					'id'      => 'zip_library',
					'type'    => 'select',
					'default' => ( class_exists( 'ZipArchive' ) ? 'ziparchive' : 'pclzip' ),
					'options' => array(
						'ziparchive' => 'ZipArchive',
						'pclzip'     => 'PclZip',
					),
				),
				array(
					'title'   => __( 'Temporary directory', 'download-plugins-dashboard' ),
					'desc'    => '<p class="description">' .
						sprintf(
							/* Translators: %s: Path. */
							__( 'Leave blank to use the default system temporary directory: %s.', 'download-plugins-dashboard' ),
							'<code>' . alg_download_plugins()->core->get_sys_temp_dir() . '</code>'
						) .
					'</p>',
					'id'      => 'temp_dir',
					'type'    => 'text',
					'default' => '',
				),
				array(
					'title'             => __( 'Auto-delete ZIP files', 'download-plugins-dashboard' ),
					'id'                => 'delete_zip',
					'desc'              => sprintf(
						'<p class="description">%s</p>',
						__( 'Automatically delete ZIP file from server after download.', 'download-plugins-dashboard' )
					),
					'type'              => 'select_yes_no',
					'default'           => 'no',
					'custom_attributes' => array(
						apply_filters( 'alg_download_plugins_settings', 'disabled' ) => apply_filters( 'alg_download_plugins_settings', 'disabled' ),
					),
				),

				// Download Plugin Link.
				array(
					'title' => __( 'Download Plugin Link', 'download-plugins-dashboard' ),
					'id'    => 'download_plugin_btn_title',
					'type'  => 'title',
					'desc'  => apply_filters( 'alg_download_plugins_settings', $this->get_available_in_pro_version_html() ),
				),
				array(
					'title'             => __( 'Use custom color', 'download-plugins-dashboard' ),
					'id'                => 'download_plugin_btn_use_custom_color',
					'type'              => 'select_yes_no',
					'default'           => 'no',
					'custom_attributes' => array(
						apply_filters( 'alg_download_plugins_settings', 'disabled' ) => apply_filters( 'alg_download_plugins_settings', 'disabled' ),
					),
				),
				array(
					'title'             => __( 'Custom color', 'download-plugins-dashboard' ),
					'id'                => 'download_plugin_btn_color',
					'type'              => 'color',
					'default'           => '#2271b1',
					'custom_attributes' => array(
						apply_filters( 'alg_download_plugins_settings', 'disabled' ) => apply_filters( 'alg_download_plugins_settings', 'disabled' ),
						'style' => 'width:27px;height:27px',
					),
				),
				array(
					'title'             => __( 'Font weight', 'download-plugins-dashboard' ),
					'id'                => 'download_plugin_btn_font_weight',
					'type'              => 'text',
					'default'           => '400',
					'custom_attributes' => array(
						apply_filters( 'alg_download_plugins_settings', 'disabled' ) => apply_filters( 'alg_download_plugins_settings', 'disabled' ),
						'style' => 'width:189px;',
					),
				),
				array(
					'title'             => __( 'Text', 'download-plugins-dashboard' ),
					'id'                => 'download_plugin_btn_text',
					'type'              => 'text',
					'default'           => __( 'Download ZIP', 'download-plugins-dashboard' ),
					'custom_attributes' => array(
						apply_filters( 'alg_download_plugins_settings', 'disabled' ) => apply_filters( 'alg_download_plugins_settings', 'disabled' ),
						'style' => 'width:189px;',
					),
				),

				// Periodical Downloads.
				array(
					'title' => __( 'Periodical Downloads', 'download-plugins-dashboard' ),
					'id'    => 'periodical_downloads_title',
					'type'  => 'title',
					'desc'  => apply_filters( 'alg_download_plugins_settings', $this->get_available_in_pro_version_html() ),
				),
				array(
					'title'             => __( 'Periodical plugins downloads', 'download-plugins-dashboard' ),
					'id'                => 'plugins_bulk_period',
					'type'              => 'select',
					'default'           => '',
					'options'           => $period_options,
					'desc'              => (
						apply_filters(
							'alg_download_plugins_settings',
							'<em>' .
								sprintf(
									/* Translators: %s: Option list. */
									__( 'Possible options: %s.', 'download-plugins-dashboard' ),
									implode(
										'; ',
										$period_options
									)
								) .
							'</em>',
							'plugins_bulk_period'
						) . ' ' .
						'<p class="description">' .
							__( 'Please note that this won\'t include "Must-Use", "Drop-in" and "Single File" plugins.', 'download-plugins-dashboard' ) . ' ' .
							__( 'However, you can download them from "Plugins" page directly.', 'download-plugins-dashboard' ) .
						'</p>'
					),
					'custom_attributes' => apply_filters( 'alg_download_plugins_settings', 'disabled' ),
				),
				array(
					'title'             => __( 'Periodical themes downloads', 'download-plugins-dashboard' ),
					'id'                => 'themes_bulk_period',
					'type'              => 'select',
					'default'           => '',
					'options'           => $period_options,
					'desc'              => apply_filters( 'alg_download_plugins_settings', '', 'themes_bulk_period' ),
					'custom_attributes' => apply_filters( 'alg_download_plugins_settings', 'disabled' ),
				),
				array(
					'title'             => __( 'Plugins downloads path', 'download-plugins-dashboard' ),
					'desc'              => sprintf(
						'<p class="description">%s</p>',
						__( 'Path for periodical plugins downloads.', 'download-plugins-dashboard' )
					),
					'id'                => 'plugins_bulk_dir',
					'type'              => 'text',
					'default'           => alg_download_plugins()->core->get_uploads_dir( 'plugins-archive' ),
					'custom_attributes' => apply_filters( 'alg_download_plugins_settings', 'disabled' ),
				),
				array(
					'title'             => __( 'Themes downloads path', 'download-plugins-dashboard' ),
					'desc'              => sprintf(
						'<p class="description">%s</p>',
						__( 'Path for periodical themes downloads.', 'download-plugins-dashboard' )
					),
					'id'                => 'themes_bulk_dir',
					'type'              => 'text',
					'default'           => alg_download_plugins()->core->get_uploads_dir( 'themes-archive' ),
					'custom_attributes' => apply_filters( 'alg_download_plugins_settings', 'disabled' ),
				),
				array(
					'title'             => __( 'Append plugin date & time', 'download-plugins-dashboard' ),
					'desc'              => sprintf(
						'<p class="description">%s</p>',
						__( 'Appends download date & time to plugin ZIP filename.', 'download-plugins-dashboard' )
					),
					'id'                => 'plugins_append_date_time',
					'type'              => 'select_yes_no',
					'default'           => 'no',
					'custom_attributes' => apply_filters( 'alg_download_plugins_settings', 'disabled' ),
				),
				array(
					'title'             => __( 'Append theme date & time', 'download-plugins-dashboard' ),
					'desc'              => sprintf(
						'<p class="description">%s</p>',
						__( 'Appends download date & time to theme ZIP filename.', 'download-plugins-dashboard' )
					),
					'id'                => 'themes_append_date_time',
					'type'              => 'select_yes_no',
					'default'           => 'no',
					'custom_attributes' => apply_filters( 'alg_download_plugins_settings', 'disabled' ),
				),
				array(
					'title'             => __( 'Plugins output file(s)', 'download-plugins-dashboard' ),
					'id'                => 'plugins_output_files',
					'type'              => 'select',
					'default'           => 'each',
					'options'           => array(
						'each' => __( 'Each plugin\'s zip', 'download-plugins-dashboard' ),
						'all'  => __( 'All plugins in single zip', 'download-plugins-dashboard' ),
						'both' => __( 'Each plugin\'s zip + All plugins in single zip', 'download-plugins-dashboard' ),
					),
					'desc'              => apply_filters(
						'alg_download_plugins_settings',
						'<em>' .
							sprintf(
								/* Translators: %s: Option list. */
								__( 'Possible options: %s.', 'download-plugins-dashboard' ),
								implode(
									'; ',
									array(
										__( 'Each plugin\'s or theme\'s zip', 'download-plugins-dashboard' ),
										__( 'All plugins or themes in single zip', 'download-plugins-dashboard' ),
										__( 'Each plugin\'s or theme\'s zip + All plugins or themes in single zip', 'download-plugins-dashboard' ),
									)
								)
							) .
						'</em>'
					),
					'custom_attributes' => apply_filters( 'alg_download_plugins_settings', 'disabled' ),
				),
				array(
					'title'             => __( 'Themes output file(s)', 'download-plugins-dashboard' ),
					'id'                => 'themes_output_files',
					'type'              => 'select',
					'default'           => 'each',
					'options'           => array(
						'each' => __( 'Each theme\'s zip', 'download-plugins-dashboard' ),
						'all'  => __( 'All themes in single zip', 'download-plugins-dashboard' ),
						'both' => __( 'Each theme\'s zip + All themes in single zip', 'download-plugins-dashboard' ),
					),
					'custom_attributes' => apply_filters( 'alg_download_plugins_settings', 'disabled' ),
				),
				array(
					'title'             => __( 'Plugins single ZIP file name', 'download-plugins-dashboard' ),
					'id'                => 'plugins_single_zip_file_name',
					'type'              => 'text',
					'default'           => 'plugins',
					'custom_attributes' => apply_filters( 'alg_download_plugins_settings', 'disabled' ),
				),
				array(
					'title'             => __( 'Themes single ZIP file name', 'download-plugins-dashboard' ),
					'id'                => 'themes_single_zip_file_name',
					'type'              => 'text',
					'default'           => 'themes',
					'custom_attributes' => apply_filters( 'alg_download_plugins_settings', 'disabled' ),
				),
			);
		}

		/**
		 * Get table HTML.
		 *
		 * @version 2.1.0
		 * @since   1.2.0
		 *
		 * @param array $data Table data.
		 * @param array $args Table arguments.
		 */
		public function get_table_html( $data, $args = array() ) {
			$defaults    = array(
				'table_class'        => '',
				'table_style'        => '',
				'row_styles'         => '',
				'table_heading_type' => 'horizontal',
				'columns_classes'    => array(),
				'columns_styles'     => array(),
			);
			$args        = array_merge( $defaults, $args );
			$table_class = ( '' == $args['table_class'] ? '' : ' class="' . $args['table_class'] . '"' );
			$table_style = ( '' == $args['table_style'] ? '' : ' style="' . $args['table_style'] . '"' );
			$row_styles  = ( '' == $args['row_styles'] ? '' : ' style="' . $args['row_styles'] . '"' );
			$html        = '';
			$html       .= '<table' . $table_class . $table_style . '>';
			$html       .= '<tbody>';
			foreach ( $data as $row ) {
				$html              .= '<tr' . $row_styles . '>';
				$column_class_title = $this->get_column_class( $args['columns_classes'], 'title' );
				$column_class_value = $this->get_column_class( $args['columns_classes'], 'value' );
				$column_style_title = $this->get_column_style( $args['columns_styles'], 'title' );
				$column_style_value = $this->get_column_style( $args['columns_styles'], 'value' );
				if ( $row['one_col'] ) {
					$html .= '<th style="padding-top:0px;padding-bottom:10px" ' . $column_class_title . $column_style_title . ' colspan="2">' . $row['title'] . $row['value'] . '</th>';
				} else {
					$html .= '<th ' . $column_class_title . $column_style_title . '>' . $row['title'] . '</th>';
					$html .= '<td ' . $column_class_value . $column_style_value . '>' . $row['value'] . '</td>';
				}
				$html .= '</tr>';
			}
			$html .= '</tbody>';
			$html .= '</table>';

			return $html;
		}

		/**
		 * Get column class.
		 *
		 * @version 1.8.9
		 * @since   1.8.9
		 *
		 * @param array  $column_classes Column classes.
		 * @param string $type           Type.
		 *
		 * @return string
		 */
		public function get_column_class( $column_classes, $type ) {
			$column_number = 'title' === $type ? $column_number = 0 : 1;
			$column_class  = ( ! empty( $column_classes[ $column_number ] ) ? ' class="' . $column_classes[ $column_number ] . '"' : '' );

			return $column_class;
		}

		/**
		 * Get column style.
		 *
		 * @version 1.8.9
		 * @since   1.8.9
		 *
		 * @param array  $column_styles Column styles.
		 * @param string $type          Type.
		 *
		 * @return string
		 */
		public function get_column_style( $column_styles, $type ) {
			$column_number = 'title' === $type ? $column_number = 0 : 1;
			$column_style  = ( ! empty( $column_styles[ $column_number ] ) ? ' style="' . $column_styles[ $column_number ] . '"' : '' );

			return $column_style;
		}
	}

endif;

return new Alg_Download_Plugins_Settings();
