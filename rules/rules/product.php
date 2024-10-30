<?php
defined( 'ABSPATH' ) || exit;

class XLWCFG_Rule_Product_Item extends XLWCFG_Rule_Base {

	public function __construct() {
		parent::__construct( 'product_item' );
	}

	public function get_possible_rule_operators() {
		$operators = array(
			'in'    => __( "is", RULE_TEXTDOMAIN ),
			'notin' => __( "is not", RULE_TEXTDOMAIN ),
		);

		return $operators;
	}

	public function get_condition_input_type() {
		return 'Product_Select';
	}

	public function is_match( $rule_data ) {

		$products = $rule_data['condition']['products'];
		$type     = $rule_data['operator'];

		$in = false;

		/** checking if env_product set */
		$product_id = XLWCFG_Core()->rules->env_product;
		if ( $product_id > 0 ) {
			/** Content id (Gift id) */
			$content_id   = XLWCFG_Core()->rules->env_content_id;
			$content_meta = XLWCFG_Common::get_post_meta_data( $content_id );
			$variationID  = 0;
			if ( isset( $content_meta['variations'] ) && is_array( $content_meta['variations'] ) && count( $content_meta['variations'] ) > 0 ) {
				$found_variation = array_search( $product_id, $content_meta['variations'] );
				if ( absint( $found_variation ) > 0 ) {
					$variationID = absint( $found_variation );
				}
			}
			if ( in_array( $product_id, $products ) || ( ( $product_id ) && in_array( $variationID, $products ) ) ) {
				$in = true;
			}
		} else {
			$cart_contents = WC()->cart->get_cart_contents();
			if ( $cart_contents && is_array( $cart_contents ) && count( $cart_contents ) > 0 ) {
				foreach ( $cart_contents as $cart_item ) {
					if ( isset( $cart_item['xlwcfg_gift_id'] ) && ! empty( $cart_item['xlwcfg_gift_id'] ) ) {
						continue;
					}
					$productID   = $cart_item['product_id'];
					$variationID = $cart_item['variation_id'];
					if ( $productID == 0 ) {
						if ( $cart_item['data'] instanceof WC_Product_Variation ) {
							$productID = $cart_item['data']->get_parent_id();
						} elseif ( $cart_item['data'] instanceof WC_Product ) {
							$productID = $cart_item['data']->get_id();
						}
					}
					if ( in_array( $productID, $products ) || ( ( $productID ) && in_array( $variationID, $products ) ) ) {
						$in = true;
						break;
					}
				}
			}
		}

		$result = $type == 'in' ? $in : ! $in;

		return $this->return_is_match( $result, $rule_data );
	}

}

