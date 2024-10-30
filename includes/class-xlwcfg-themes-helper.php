<?php
defined( 'ABSPATH' ) || exit;

add_action( 'wp', 'xlwcfg_theme_helper_functions', 100 );

/**
 * Here we have popular themes fallback functions to support our plugin
 * @global type $post
 */
function xlwcfg_theme_helper_functions() {

	if ( class_exists( 'Flatsome_Option' ) ) {
		/**
		 * Flatsome
		 */
		include_once XLWCFG_PLUGIN_DIR . "theme-support/flatsome/flatsome.php";
	}
}