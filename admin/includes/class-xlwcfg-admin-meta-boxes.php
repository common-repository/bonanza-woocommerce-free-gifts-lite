<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class XLWCFG_Admin_Meta_Boxes
 * @package Bonanza-Lite
 * @author XlPlugins
 */
class XLWCFG_Admin_Meta_Boxes {

	protected static $options_data = false;

	/**
	 * Option key, and option page slug
	 * @var string
	 */
	private static $key = 'xlwcfg_post_option';

	/**
	 * Options page metabox id
	 * @var string
	 */
	private static $metabox_id = 'xlwcfg_post_option_metabox';

	public static function metabox_schedule_fields() {
		$box_options = array(
			'id'           => 'xlwcfg_schedule_box_settings',
			'title'        => __( 'Schedule', 'bonanza-woocommerce-free-gifts-lite' ),
			'classes'      => 'xlwcfg_options_common xlwcfg_schedule_box_settings',
			'object_types' => array( XLWCFG_Common::get_free_gift_cpt_slug() ),
			'show_names'   => true,
			'context'      => 'normal',
			'priority'     => 'high',
		);
		$cmb         = new_cmb2_box( $box_options );
		ob_start();
		?>
        <ul class="cmb2-radio-list cmb2-list">
            <li>
                <input type="radio" class="cmb2-option" name="_xlwcfg_schedule_html" value="forever" checked="checked"/>
                <label><?php echo __( 'Forever', 'bonanza-woocommerce-free-gifts-lite' ) ?></label>
            </li>
            <li class="xlwcfg_round_radio_html">
                <a href="javascript:void(0)" onclick="show_modal_pro('schedule_fixed');"><?php echo __( 'Fixed', 'bonanza-woocommerce-free-gifts-lite' ) ?>
                    <i class="dashicons dashicons-lock"></i>
                </a>
            </li>
        </ul>
		<?php $schedule_before_html = ob_get_clean();
		$fields                     = array();
		$fields[]                   = array(
			'name'        => __( 'Schedule', 'bonanza-woocommerce-free-gifts-lite' ),
			'id'          => '_xlwcfg_schedule',
			'type'        => 'radio_inline',
			'row_classes' => array( 'xlwcfg_no_border', 'cmd-inline' ),
			'before'      => $schedule_before_html,
//			'options'     => array(
//				'fixed'   => __( 'Fixed', 'bonanza-woocommerce-free-gifts-lite' ),
//				'forever' => __( 'Forever', 'bonanza-woocommerce-free-gifts-lite' ),
//			),
		);


		$default_vals = XLWCFG_Common::get_default_settings();
		foreach ( $fields as $field ) {
			if ( isset( $default_vals[ $field['id'] ] ) ) {
				$field['default'] = $default_vals[ $field['id'] ];
			}
			$cmb->add_field( $field );
		}
	}

	public static function metabox_offer_fields() {
		$box_options = array(
			'id'           => 'xlwcfg_buy_box_settings',
			'title'        => __( 'Offer', 'bonanza-woocommerce-free-gifts-lite' ),
			'classes'      => 'xlwcfg_options_common xlwcfg_buy_box_settings',
			'object_types' => array( XLWCFG_Common::get_free_gift_cpt_slug() ),
			'show_names'   => true,
			'context'      => 'normal',
			'priority'     => 'high',
		);
		$cmb         = new_cmb2_box( $box_options );

		$fields   = array();
		$fields[] = array(
			'name'        => __( 'Buy', 'bonanza-woocommerce-free-gifts-lite' ),
			'id'          => '_xlwcfg_gift_qty_buy',
			'type'        => 'text_small',
			'row_classes' => array( 'xlwcfg_field_inline', 'xlwcfg_text_extra_small', 'xlwcfg_no_border' ),
			'attributes'  => array(
				'type'        => 'number',
				'min'         => '1',
				'pattern'     => '\d*',
				'placeholder' => __( '1', 'bonanza-woocommerce-free-gifts-lite' ),
			),
		);
		$fields[] = array(
			'name'        => __( 'Get', 'bonanza-woocommerce-free-gifts-lite' ),
			'desc'        => __( 'Free', 'bonanza-woocommerce-free-gifts-lite' ),
			'id'          => '_xlwcfg_gift_qty_get',
			'type'        => 'text_small',
			'row_classes' => array( 'xlwcfg_field_inline', 'xlwcfg_field_inline_end', 'xlwcfg_text_extra_small', 'xlwcfg_no_border' ),
			'attributes'  => array(
				'type'        => 'number',
				'min'         => '1',
				'pattern'     => '\d*',
				'placeholder' => __( '1', 'bonanza-woocommerce-free-gifts-lite' ),
			),
		);
		$fields[] = array(
			'id'   => '_xlwcfg_offer_mode',
			'type' => 'hidden',
		);
		$fields[] = array(
			'name'        => __( 'Repeat', 'bonanza-woocommerce-free-gifts-lite' ),
			'id'          => '_xlwcfg_repeat',
			'type'        => 'radio_inline',
			'row_classes' => array( 'xlwcfg_gap_inline' ),
			'options'     => array(
				'yes' => __( 'Yes', 'bonanza-woocommerce-free-gifts-lite' ),
				'no'  => __( 'No', 'bonanza-woocommerce-free-gifts-lite' ),
			),
		);

		$default_vals = XLWCFG_Common::get_default_settings();
		foreach ( $fields as $field ) {
			if ( isset( $default_vals[ $field['id'] ] ) ) {
				$field['default'] = $default_vals[ $field['id'] ];
			}
			$cmb->add_field( $field );
		}

	}

	public static function metabox_get_block_fields() {
		$box_options = array(
			'id'           => 'xlwcfg_get_block_settings',
			'title'        => __( 'Customer Gets', 'bonanza-woocommerce-free-gifts-lite' ),
			'classes'      => 'xlwcfg_options_common xlwcfg_get_block_settings',
			'object_types' => array( XLWCFG_Common::get_free_gift_cpt_slug() ),
			'show_names'   => true,
			'context'      => 'normal',
			'priority'     => 'high',
		);
		$cmb         = new_cmb2_box( $box_options );
		// range html
		ob_start();
		?>
        <ul class="cmb2-radio-list cmb2-list">
            <li>
                <input type="radio" class="cmb2-option" name="_xlwcfg_get_mode_html" value="other" checked="checked"/>
                <label><?php echo __( 'Different Product(s)', 'bonanza-woocommerce-free-gifts-lite' ) ?></label>
            </li>
            <li class="xlwcfg_round_radio_html">
                <a href="javascript:void(0)" onclick="show_modal_pro('get_mode_same');"><?php echo __( 'Same As Buy Product', 'bonanza-woocommerce-free-gifts-lite' ) ?>
                    <i class="dashicons dashicons-lock"></i>
                </a>
            </li>
        </ul>
		<?php $get_products_before_html = ob_get_clean();

		$cmb->add_field( array(
			'name'        => __( 'Product(s)', 'bonanza-woocommerce-free-gifts-lite' ),
			'id'          => '_xlwcfg_get_products',
			'type'        => 'xlwcfg_post_select',
			'row_classes' => array( 'xlwcfg_no_border', 'xlwcfg_cmb2_product_chosen', 'xlwcfg_pt0', 'cmb-inline' ),
			'before'      => $get_products_before_html . '<p>Select Product(s)</p>',
			'attributes'  => array(
				'multiple'         => 'multiple',
				'name'             => '_xlwcfg_get_products[]',
				'class'            => 'ajax_chosen_select_products',
				'data-placeholder' => __( 'Search for a product...', 'bonanza-woocommerce-free-gifts-lite' ),

			),
		) );
	}

	public static function metabox_conditions_fields() {
		$box_options = array(
			'id'           => 'xlwcfg_conditions_block_settings',
			'title'        => __( 'Messages', 'bonanza-woocommerce-free-gifts-lite' ),
			'classes'      => 'xlwcfg_options_common xlwcfg_conditions_block_settings',
			'object_types' => array( XLWCFG_Common::get_free_gift_cpt_slug() ),
			'show_names'   => true,
			'context'      => 'normal',
			'priority'     => 'high',
		);

		$cmb = new_cmb2_box( $box_options );

		$fields = array();

		$fields[] = array(
			'name'        => __( 'Single Product Display', 'bonanza-woocommerce-free-gifts-lite' ),
			'desc'        => __( 'Choose Yes if you want to display badge + text on the single product page.', 'bonanza-woocommerce-free-gifts-lite' ),
			'id'          => '_xlwcfg_enable_single_pro',
			'type'        => 'radio_inline',
			'row_classes' => array( 'xlwcfg_no_border' ),
			'options'     => array(
				'yes' => __( 'Yes', 'bonanza-woocommerce-free-gifts-lite' ),
				'no'  => __( 'No', 'bonanza-woocommerce-free-gifts-lite' ),
			),
		);
		$fields[] = array(
			'name'        => 'Position',
			'id'          => '_xlwcfg_single_pro_position',
			'type'        => 'select',
			'row_classes' => array( 'xlwcfg_hide_label', 'xlwcfg_pt0', 'xlwcfg_no_border' ),
			'before'      => '<p>Position</p>',
			'options'     => array(
				'4' => __( 'Below the Price', 'bonanza-woocommerce-free-gifts-lite' ),
				'5' => __( 'Below Short Description', 'bonanza-woocommerce-free-gifts-lite' ),
				'6' => __( 'Below Add to Cart Button', 'bonanza-woocommerce-free-gifts-lite' ),
			),
			'attributes'  => array(
				'data-conditional-id'    => '_xlwcfg_enable_single_pro',
				'data-conditional-value' => 'yes',
			),
		);
		$fields[] = array(
			'name'        => __( 'Text', 'bonanza-woocommerce-free-gifts-lite' ),
			'id'          => '_xlwcfg_single_pro_text',
			'type'        => 'textarea_small',
			'row_classes' => array( 'xlwcfg_hide_label', 'xlwcfg_pt0', 'xlwcfg_no_border' ),
			'before'      => '<p>Text</p>',
			'attributes'  => array(
				'data-conditional-id'    => '_xlwcfg_enable_single_pro',
				'data-conditional-value' => 'yes',
			),
		);
		$fields[] = array(
			'name'        => __( 'BG Color', 'bonanza-woocommerce-free-gifts-lite' ),
			'id'          => '_xlwcfg_single_pro_bg_color',
			'type'        => 'colorpicker',
			'row_classes' => array( 'xlwcfg_hide_label', 'xlwcfg_pt0', 'xlwcfg_combine_2_field_start' ),
			'before'      => '<p>BG Color</p>',
			'attributes'  => array(
				'data-conditional-id'    => '_xlwcfg_enable_single_pro',
				'data-conditional-value' => 'yes',
			),
		);
		$fields[] = array(
			'name'        => __( 'Text Color', 'bonanza-woocommerce-free-gifts-lite' ),
			'id'          => '_xlwcfg_single_pro_text_color',
			'type'        => 'colorpicker',
			'row_classes' => array( 'xlwcfg_hide_label', 'xlwcfg_pt0', 'xlwcfg_combine_2_field_end', 'xlwcfg_pb0', 'xlwcfg_no_border' ),
			'before'      => '<p>Text Color</p>',
			'attributes'  => array(
				'data-conditional-id'    => '_xlwcfg_enable_single_pro',
				'data-conditional-value' => 'yes',
			),
		);
		$fields[] = array(
			'name'        => __( 'Font Size', 'bonanza-woocommerce-free-gifts-lite' ),
			'id'          => '_xlwcfg_single_pro_fs',
			'type'        => 'text_small',
			'row_classes' => array( 'xlwcfg_hide_label', 'xlwcfg_pt0', 'xlwcfg_combine_2_field_start', 'xlwcfg_pb0', 'xlwcfg_no_border' ),
			'before'      => '<p>Font Size (px)</p>',
			'attributes'  => array(
				'data-conditional-id'    => '_xlwcfg_enable_single_pro',
				'data-conditional-value' => 'yes',
				'type'                   => 'number',
				'min'                    => '0',
				'pattern'                => '\d*',
			),
		);
		$fields[] = array(
			'name'        => __( 'Padding Top/ Bottom', 'bonanza-woocommerce-free-gifts-lite' ),
			'id'          => '_xlwcfg_single_pro_padding_t',
			'type'        => 'text_small',
			'row_classes' => array( 'xlwcfg_hide_label', 'xlwcfg_pt0', 'xlwcfg_combine_2_field_middle' ),
			'before'      => '<p>Padding Top/ Bottom (px)</p>',
			'attributes'  => array(
				'data-conditional-id'    => '_xlwcfg_enable_single_pro',
				'data-conditional-value' => 'yes',
				'type'                   => 'number',
				'min'                    => '0',
				'pattern'                => '\d*',
			),
		);
		$fields[] = array(
			'name'        => __( 'Padding Left/ Right', 'bonanza-woocommerce-free-gifts-lite' ),
			'id'          => '_xlwcfg_single_pro_padding_l',
			'type'        => 'text_small',
			'row_classes' => array( 'xlwcfg_hide_label', 'xlwcfg_pt0', 'xlwcfg_combine_2_field_end', 'xlwcfg_pb0', 'xlwcfg_no_border' ),
			'before'      => '<p>Padding Left/ Right (px)</p>',
			'attributes'  => array(
				'data-conditional-id'    => '_xlwcfg_enable_single_pro',
				'data-conditional-value' => 'yes',
				'type'                   => 'number',
				'min'                    => '0',
				'pattern'                => '\d*',
			),
		);
		$fields[] = array(
			'name'        => __( 'Single Border Style', 'bonanza-woocommerce-free-gifts-lite' ),
			'id'          => '_xlwcfg_single_pro_border_style',
			'type'        => 'select',
			'row_classes' => array( 'xlwcfg_combine_2_field_start', 'xlwcfg_pt0', 'xlwcfg_text_color', 'xlwcfg_hide_label' ),
			'before'      => '<p>Border Style</p>',
			'options'     => array(
				'dotted' => __( 'Dotted', 'bonanza-woocommerce-free-gifts-lite' ),
				'dashed' => __( 'Dashed', 'bonanza-woocommerce-free-gifts-lite' ),
				'solid'  => __( 'Solid', 'bonanza-woocommerce-free-gifts-lite' ),
				'double' => __( 'Double', 'bonanza-woocommerce-free-gifts-lite' ),
				'none'   => __( 'None', 'bonanza-woocommerce-free-gifts-lite' ),
			),
			'attributes'  => array(
				'data-conditional-id'    => '_xlwcfg_enable_single_pro',
				'data-conditional-value' => 'yes',
			),
		);
		$fields[] = array(
			'name'        => __( 'Single Border Width', 'bonanza-woocommerce-free-gifts-lite' ),
			'id'          => '_xlwcfg_single_pro_border_width',
			'type'        => 'text_small',
			'row_classes' => array( 'xlwcfg_combine_2_field_middle', 'xlwcfg_pt0', 'xlwcfg_text_color', 'xlwcfg_hide_label' ),
			'before'      => '<p>Border Width (px)</p>',
			'attributes'  => array(
				'type'                   => 'number',
				'min'                    => '0',
				'pattern'                => '\d*',
				'data-conditional-id'    => '_xlwcfg_enable_single_pro',
				'data-conditional-value' => 'yes',
			),
		);
		$fields[] = array(
			'name'        => __( 'Single Border Color', 'bonanza-woocommerce-free-gifts-lite' ),
			'id'          => '_xlwcfg_single_pro_border_color',
			'type'        => 'colorpicker',
			'row_classes' => array( 'xlwcfg_combine_2_field_end', 'xlwcfg_pt0', 'xlwcfg_text_gap', 'xlwcfg_hide_label', 'xlwcfg_no_border' ),
			'before'      => '<p>Border Color</p>',
			'attributes'  => array(
				'data-conditional-id'    => '_xlwcfg_enable_single_pro',
				'data-conditional-value' => 'yes',
			),
		);

		$default_vals = XLWCFG_Common::get_default_settings();
		foreach ( $fields as $field ) {
			if ( isset( $default_vals[ $field['id'] ] ) ) {
				$field['default'] = $default_vals[ $field['id'] ];
			}
			$cmb->add_field( $field );
		}

	}

	/**
	 * Getting Default config from the saved values in wp_options
	 * Getter function for config for each field
	 *
	 * @param $key
	 * @param int $index
	 *
	 * @return string
	 */
	public static function get_default_config( $key, $index = 0 ) {

		if ( is_array( $key ) ) {

			if ( $key[1] == 'mode' ) {
				return '0';
			}

			return ( isset( self::$options_data[ $key[0] ][ $index ][ $key[1] ] ) ) ? self::$options_data[ $key[0] ][ $index ][ $key[1] ] : '';
		} else {
			if ( $key == 'mode' ) {
				return '0';
			}

			return ( isset( self::$options_data[ $key ] ) ) ? self::$options_data[ $key ] : '';
		}
	}

	/**
	 * Setting up property `options_data` by options data saved.
	 */
	public static function prepare_default_config() {
		self::$options_data = XLWCFG_Common::get_default_settings();
	}

}
