<?php
defined( 'ABSPATH' ) || exit;

class xlwcfg_Input_Product_Select {
	public function __construct() {
		// vars
		$this->type = 'Product_Select';

		$this->defaults = array(
			'multiple'      => 0,
			'allow_null'    => 0,
			'choices'       => array(),
			'default_value' => '',
			'class'         => 'ajax_chosen_select_products'
		);
	}

	public function render( $field, $value = null ) {
		$field = array_merge( $this->defaults, $field );
		if ( ! isset( $field['id'] ) ) {
			$field['id'] = sanitize_title( $field['id'] );
		}
		?>
        <table style="width:100%;">
            <tr>
                <td>
                    <select id="<?php echo $field['id']; ?>" name="<?php echo $field['name']; ?>[products][]" multiple="multiple" class="ajax_chosen_select_products" data-placeholder="<?php _e( 'Look for a product&hellip;', 'woocommerce' ); ?>">
						<?php
						$current     = isset( $value['products'] ) ? $value['products'] : array();
						$product_ids = ! empty( $current ) ? array_map( 'absint', $current ) : null;
						if ( $product_ids ) {
							foreach ( $product_ids as $product_id ) {

								$product      = wc_get_product( $product_id );
								$product_name = XLWCFG_Compatibility::woocommerce_get_formatted_product_name( $product );

								echo '<option value="' . esc_attr( $product_id ) . '" selected="selected">' . esc_html( $product_name ) . '</option>';
							}
						}
						?>
                    </select>
                </td>
            </tr>
        </table>
		<?php
	}
}