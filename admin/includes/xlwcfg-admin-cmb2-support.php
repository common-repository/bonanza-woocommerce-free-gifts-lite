<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class XLWCFG_Admin_CMB2_Support
 * @package Bonanza-Lite
 * @author XlPlugins
 */
class XLWCFG_Admin_CMB2_Support {

	/**
	 * Hooked over `xl_cmb2_add_conditional_script_page` so that we can load conditional logic scripts
	 *
	 * @param $options Pages
	 *
	 * @return mixed
	 */
	public static function xlwcfg_push_support_form_cmb_conditionals( $pages ) {

		return $pages;
	}

	public static function render_trigger_nav() {
		$get_campaign_statuses = apply_filters( 'xlwcfg_admin_trigger_nav', XLWCFG_Common::get_campaign_statuses() );
		$html                  = '<ul class="subsubsub subsubsub_xlwcfg">';
		$html_inside           = array();
		$html_inside[]         = sprintf( '<li><a href="%s" class="%s">%s</a></li>', admin_url( 'admin.php?page=wc-settings&tab=' . XLWCFG_Common::get_wc_settings_tab_slug() . '&section=all' ), self::active_class( 'all' ), __( "All", 'bonanza-woocommerce-free-gifts-lite' ) );
		foreach ( $get_campaign_statuses as $status ) {
			$html_inside[] = sprintf( '<li><a href="%s" class="%s">%s</a></li>', admin_url( 'admin.php?page=wc-settings&tab=' . XLWCFG_Common::get_wc_settings_tab_slug() . '&section=' . $status['slug'] ), self::active_class( $status['slug'] ), $status['name'] );
		}

		if ( is_array($html_inside) && count( $html_inside ) > 0 ) {
			$html .= implode( "", $html_inside );
		}
		$html .= '</ul>';

		echo $html;
	}

	public static function active_class( $trigger_slug ) {

		if ( self::get_current_trigger() == $trigger_slug ) {
			return "current";
		}

		return "";
	}

	public static function get_current_trigger() {
		if ( isset( $_GET['page'] ) && $_GET['page'] == "wc-settings" && isset( $_GET['section'] ) ) {
			return $_GET['section'];
		}

		return "all";
	}

	public static function cmb_opt_groups( $args, $defaults, $field_object, $field_types_object ) {

		// Only do this for the field we want (vs all select fields)
		if ( '_xlwcfg_data_choose_trigger' != $field_types_object->_id() ) {
			return $args;
		}

		$option_array = XLWCFG_Common::get_campaign_status_select();

		$saved_value = $field_object->escaped_value();
		$value       = $saved_value ? $saved_value : $field_object->args( 'default' );

		$options_string = '';

		$args = array(
			'label'   => __( 'Select an Option', 'bonanza-woocommerce-free-gifts-lite' ),
			'value'   => '',
			'checked' => ! $value
		);

		if ( $field_object->args["show_option_none"] ) {
			$options_string .= $field_types_object->select_option( $args );
		}

		foreach ( $option_array as $group_label => $group ) {

			$options_string .= '<optgroup label="' . $group_label . '">';

			foreach ( $group as $key => $label ) {

				$args = array(
					'label'   => $label,
					'value'   => $key,
					'checked' => $value == $key
				);
				$options_string .= $field_types_object->select_option( $args );
			}
			$options_string .= '</optgroup>';
		}

		// Ok, replace the options value
		$defaults['options'] = $options_string;

		return $defaults;
	}
}
