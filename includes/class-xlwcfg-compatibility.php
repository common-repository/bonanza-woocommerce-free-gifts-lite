<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class XLWCFG_Compatibility
 * @package Bonanza-Lite
 * @author XlPlugins
 */
if ( ! class_exists( 'XLWCFG_Compatibility' ) ) :

	class XLWCFG_Compatibility {

		public static function wc_placeholder_img_src() {
			if ( self::is_wc_version_gte_2_1() ) {
				return wc_placeholder_img_src();
			} else {
				return woocommerce_placeholder_img_src();
			}
		}

		public static function woocommerce_get_formatted_product_name( $product ) {
			if ( self::is_wc_version_gte_2_1() ) {
				return strip_tags( $product->get_formatted_name() );
			} else {
				return strip_tags( woocommerce_get_formatted_product_name( $product ) );
			}
		}

		/**
		 * Compatibility function to get the version of the currently installed WooCommerce
		 *
		 * @since 1.0
		 * @return string woocommerce version number or null
		 */
		public static function get_wc_version() {

			// WOOCOMMERCE_VERSION is now WC_VERSION, though WOOCOMMERCE_VERSION is still available for backwards compatibility, we'll disregard it on 2.1+
			if ( defined( 'WC_VERSION' ) && WC_VERSION ) {
				return WC_VERSION;
			}
			if ( defined( 'WOOCOMMERCE_VERSION' ) && WOOCOMMERCE_VERSION ) {
				return WOOCOMMERCE_VERSION;
			}

			return null;
		}

		/**
		 * Returns true if the installed version of WooCommerce is 2.1 or greater
		 *
		 * @since 1.0
		 * @return boolean true if the installed version of WooCommerce is 2.1 or greater
		 */
		public static function is_wc_version_gte_2_1() {

			// can't use gte 2.1 at the moment because 2.1-BETA < 2.1
			return self::is_wc_version_gt( '2.0.20' );
		}

		/**
		 * Returns true if the installed version of WooCommerce is 2.6 or greater
		 *
		 * @since 1.0
		 * @return boolean true if the installed version of WooCommerce is 2.1 or greater
		 */
		public static function is_wc_version_gte_2_6() {

			return version_compare( self::get_wc_version(), '2.6.0', 'ge' );
		}

		/**
		 * Returns true if the installed version of WooCommerce is 3.0 or greater
		 *
		 * @since 1.0
		 * @return boolean true if the installed version of WooCommerce is 3.0 or greater
		 */
		public static function is_wc_version_gte_3_0() {

			return version_compare( self::get_wc_version(), '3.0', 'ge' );
		}

		/**
		 * Returns true if the installed version of WooCommerce is greater than $version
		 *
		 * @since 1.0
		 *
		 * @param string $version the version to compare
		 *
		 * @return boolean true if the installed version of WooCommerce is > $version
		 */
		public static function is_wc_version_gt( $version ) {

			return self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>' );
		}

		/**
		 * Returns true if the installed version of WooCommerce is greater than $version
		 *
		 * @since 1.0
		 *
		 * @param string $version the version to compare
		 *
		 * @return boolean true if the installed version of WooCommerce is > $version
		 */
		public static function is_wc_version_gt_eq( $version ) {

			return self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>=' );
		}

	}

endif; // Class exists check
