<?php
/*
 * Plugin Name: Bonanza - WooCommerce Free Gifts Lite
 * Plugin URI: https://xlplugins.com/woocommerce-free-gifts-bonanza/
 * Description: WooCommerce Free Gifts plugin allows you to add gifts on cart.
 * Version: 1.0.0
 * Author: XLPlugins
 * Author URI: https://www.xlplugins.com
 * Text Domain: bonanza-woocommerce-free-gifts-lite
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * XL: True
 * XLTOOLS: True
 * Requires at least: 4.2.1
 * Tested up to: 5.2.2
 * WC requires at least: 3.0.0
 * WC tested up to: 3.7.0
 *
 * Bonanza - WooCommerce Free Gifts Lite is free software.
 * You can redistribute it and/or modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * Bonanza - WooCommerce Free Gifts Lite is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Bonanza - WooCommerce Free Gifts. If not, see <http://www.gnu.org/licenses/>.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'xlwcfg_bonanza_dependency' ) ) {

	/**
	 * Function to check if bonanza pro version is loaded and activated or not?
	 * @return bool True|False
	 */
	function xlwcfg_bonanza_dependency() {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return in_array( 'bonanza-woocommerce-free-gifts/bonanza-woocommerce-free-gifts.php', $active_plugins, true ) || array_key_exists( 'bonanza-woocommerce-free-gifts/bonanza-woocommerce-free-gifts.php', $active_plugins );
	}
}

if ( xlwcfg_bonanza_dependency() ) {
	return;
}

if ( ! class_exists( 'XLWCFG_Core' ) ) :

	class XLWCFG_Core {

		/**
		 * @var XLWCFG_Core
		 */
		public static $_instance = null;
		private static $_registered_entity = array(
			'active'   => array(),
			'inactive' => array(),
		);

		/**
		 * @var XLWCFG_Data
		 */
		public $data;

		/**
		 * @var XLWCFG_Appearance
		 */
		public $appearance;

		/**
		 * @var XLWCFG_XL_Support
		 */
		public $xl_support;

		/**
		 * @var XLWCFG_Rules
		 */
		public $rules;

		/**
		 * @var bool Dependency check property
		 */
		private $is_dependency_exists = true;

		public function __construct() {

			/**
			 * Load important variables and constants
			 */
			$this->define_plugin_properties();

			/**
			 * Load dependency classes like woo-functions.php
			 */
			$this->load_dependencies_support();

			/**
			 * Run dependency check to check if dependency available
			 */
			$this->do_dependency_check();
			if ( $this->is_dependency_exists ) {

				/**
				 * Include common classes
				 */
				$this->include_commons();

				/**
				 * Initialize common hooks and functions
				 */
				$this->initialize_common();

				/**
				 * Initiates and loads XL start file
				 */
				$this->load_xl_core_classes();

				/**
				 * Loads all the hooks
				 */
				$this->load_hooks();

			}
		}

		/**
		 * Defining constants
		 */
		public function define_plugin_properties() {
			define( 'XLWCFG_VERSION', '1.0.0' );
			define( 'XLWCFG_MIN_WC_VERSION', '3.0' );
			define( 'XLWCFG_FULL_NAME', 'Bonanza - WooCommerce Free Gifts Lite' );
			define( 'XLWCFG_PLUGIN_FILE', __FILE__ );
			define( 'XLWCFG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			define( 'XLWCFG_PLUGIN_DIR', plugin_dir_path( XLWCFG_PLUGIN_FILE ) );
			define( 'XLWCFG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			define( 'XLWCFG_PURCHASE', 'xlplugin' );
			define( 'XLWCFG_SHORT_SLUG', 'xlwcfg' );
			define( 'XLWCFG_SLUG', 'bonanza-woocommerce-free-gifts-lite' );
			( ! defined( 'XL_DEV' ) ) ? define( 'XLWCFG_VER', XLWCFG_VERSION ) : define( 'XLWCFG_VER', time() );
		}

		public function load_dependencies_support() {
			/** Setting up WooCommerce Dependency Classes */
			require_once( XLWCFG_PLUGIN_DIR . 'woo-includes/woo-functions.php' );
		}

		public function do_dependency_check() {

			if ( ! xlwcfg_is_woocommerce_active() ) {
				add_action( 'admin_notices', array( $this, 'xlwcfg_wc_not_installed_notice' ) );
				$this->is_dependency_exists = false;
			}
		}


		public function load_hooks() {

			/** Initializing functionality */
			add_action( 'plugins_loaded', array( $this, 'xlwcfg_init' ), 0 );
			add_action( 'plugins_loaded', array( $this, 'xlwcfg_register_classes' ), 1 );

			/** Initialize localization */
			add_action( 'init', array( $this, 'xlwcfg_init_localization' ) );

			/** Redirecting plugin to the settings page after activation */
			add_action( 'activated_plugin', array( $this, 'xlwcfg_settings_redirect' ) );

			/** Admin hooks */
			if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
				require_once( XLWCFG_PLUGIN_DIR . 'admin/class-xlwcfg-admin.php' );
			}

			/** Compatibility */
			add_action( 'plugins_loaded', array( $this, 'xlwcfg_load_compatibility' ), 10 );
		}

		public function xlwcfg_load_compatibility() {
			foreach ( glob( XLWCFG_PLUGIN_DIR . 'compatibility/*.php' ) as $_field_filename ) {
				$file_data = pathinfo( $_field_filename );
				if ( isset( $file_data['basename'] ) && 'index.php' === $file_data['basename'] ) {
					continue;
				}
				require_once( $_field_filename );
			}
		}

		/**
		 * Setting Up XL Core
		 */
		public function load_xl_core_classes() {
			require_once( XLWCFG_PLUGIN_DIR . 'start.php' );
		}

		public function include_commons() {
			require XLWCFG_PLUGIN_DIR . 'includes/class-xlwcfg-common.php';
			require XLWCFG_PLUGIN_DIR . 'includes/class-xlwcfg-compatibility.php';
			require XLWCFG_PLUGIN_DIR . 'includes/class-xlwcfg-xl-support.php';
		}

		/**
		 * Initializing common class construct
		 */
		public function initialize_common() {
			XLWCFG_Common::init();
		}

		/**
		 * Register classes on properties
		 */
		public function xlwcfg_register_classes() {
			$load_classes = self::get_registered_class();

			if ( is_array( $load_classes ) && count( $load_classes ) > 0 ) {
				foreach ( $load_classes as $access_key => $class ) {
					$this->$access_key = $class::get_instance();
				}
			}
		}

		/**
		 * Get registered classes
		 *
		 * @return mixed
		 */
		public static function get_registered_class() {
			return self::$_registered_entity['active'];
		}

		/**
		 * Register classes with short slug
		 *
		 * @param $shortName
		 * @param $class
		 * @param null $overrides
		 */
		public static function register( $shortName, $class, $overrides = null ) {
			/** Ignore classes that have been marked as inactive */
			if ( in_array( $class, self::$_registered_entity['inactive'], true ) ) {
				return;
			}

			/** Mark classes as active. Override existing active classes if they are supposed to be overridden */
			$index = array_search( $overrides, self::$_registered_entity['active'], true );
			if ( false !== $index ) {
				self::$_registered_entity['active'][ $index ] = $class;
			} else {
				self::$_registered_entity['active'][ $shortName ] = $class;
			}

			/** Mark overridden classes as inactive. */
			if ( ! empty( $overrides ) ) {
				self::$_registered_entity['inactive'][] = $overrides;
			}
		}

		/**
		 * @return XLWCFG_Core instance
		 */
		public static function get_instance() {
			if ( null === self::$_instance ) {
				self::$_instance = new self;
			}

			return self::$_instance;
		}

		/**
		 * Initialize plugin localization
		 */
		public function xlwcfg_init_localization() {
			load_plugin_textdomain( 'bonanza-woocommerce-free-gifts-lite', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Added redirection on plugin activation
		 *
		 * @param $plugin
		 */
		public function xlwcfg_settings_redirect( $plugin ) {
			if ( xlwcfg_is_woocommerce_active() && class_exists( 'WooCommerce' ) ) {
				if ( $plugin === plugin_basename( __FILE__ ) ) {
					wp_safe_redirect( add_query_arg( array(
						'page' => 'wc-settings',
						'tab'  => XLWCFG_Common::get_wc_settings_tab_slug(),
					), admin_url( 'admin.php' ) ) );
					exit;
				}
			}
		}

		/**
		 * Checking WooCommerce dependency and then loads further
		 * @return bool false on failure
		 */
		public function xlwcfg_init() {

			if ( xlwcfg_is_woocommerce_active() && class_exists( 'WooCommerce' ) ) {

				global $woocommerce;
				if ( ! version_compare( $woocommerce->version, XLWCFG_MIN_WC_VERSION, '>=' ) ) {
					add_action( 'admin_notices', array( $this, 'xlwcfg_wc_version_check_notice' ) );

					return false;
				}

				if ( isset( $_GET['xlwcfg_disable'] ) && $_GET['xlwcfg_disable'] === 'yes' && is_user_logged_in() && current_user_can( 'administrator' ) ) {
					return false;
				}

				if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
				} else {
					require XLWCFG_PLUGIN_DIR . 'includes/class-xlwcfg-data.php';
					require XLWCFG_PLUGIN_DIR . 'includes/class-xlwcfg-appearance.php';
				}
				require XLWCFG_PLUGIN_DIR . 'includes/class-xlwcfg-themes-helper.php';
				require XLWCFG_PLUGIN_DIR . 'includes/class-xlwcfg-rules.php';
			}
		}

		/** Registering notices */
		public function xlwcfg_wc_version_check_notice() {
			?>
            <div class="error">
                <p>
					<?php
					/* translators: %1$s: Min required woocommerce version */
					printf( __( '<strong> Attention: </strong>Bonanza plugin requires WooCommerce version %1$s or greater. Kindly update the WooCommerce plugin.', 'bonanza-woocommerce-free-gifts-lite' ), XLWCFG_MIN_WC_VERSION );
					?>
                </p>
            </div>
			<?php
		}

		public function xlwcfg_wc_not_installed_notice() {
			?>
            <div class="error">
                <p>
					<?php
					echo __( '<strong> Attention: </strong>WooCommerce is not installed or activated. Bonanza is a WooCommerce Extension and would only work if WooCommerce is activated. Please install the WooCommerce Plugin first.', 'bonanza-woocommerce-free-gifts-lite' );
					?>
                </p>
            </div>
			<?php
		}


	}

endif;

/**
 * Global Common function to load all the classes
 *
 * @param bool $debug
 *
 * @return XLWCFG_Core
 */
if ( ! function_exists( 'XLWCFG_Core' ) ) {

	function XLWCFG_Core( $debug = false ) {
		return XLWCFG_Core::get_instance();
	}
}

require plugin_dir_path( __FILE__ ) . 'includes/xlwcfg-logging.php';

/**
 * Collect PHP fatal errors and save it in the log file so that it can be later viewed
 * @see register_shutdown_function
 */
if ( ! function_exists( 'xlplugins_collect_errors' ) ) {
	function xlplugins_collect_errors() {
		$error = error_get_last();
		if ( E_ERROR === $error['type'] ) {
			xlplugins_force_log( $error['message'] . PHP_EOL, 'fatal-errors.txt' );
		}
	}

	register_shutdown_function( 'xlplugins_collect_errors' );
}

$GLOBALS['XLWCFG_Core'] = XLWCFG_Core();
