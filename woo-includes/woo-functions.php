<?php
defined( 'ABSPATH' ) || exit;

/**
 * Functions used by plugins
 */
if ( ! class_exists( 'XLWCFG_Dependencies' ) ) {
	require_once XLWCFG_PLUGIN_DIR . 'woo-includes/class-wc-dependencies.php';
}

/**
 * WC Detection
 */
if ( ! function_exists( 'xlwcfg_is_woocommerce_active' ) ) {

	function xlwcfg_is_woocommerce_active() {
		return XLWCFG_Dependencies::xlwcfg_woocommerce_active_check();
	}

}