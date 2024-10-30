<?php
defined( 'ABSPATH' ) || exit;

class XLWCFG_Rule_Cart_Total extends XLWCFG_Rule_Base {

	public function __construct() {
		parent::__construct( 'cart_total' );
	}

	public function get_possible_rule_operators() {
		$operators = array(
			'==' => __( 'is equal to', RULE_TEXTDOMAIN ),
			'!=' => __( 'is not equal to', RULE_TEXTDOMAIN ),
			'>'  => __( 'is greater than', RULE_TEXTDOMAIN ),
			'<'  => __( 'is less than', RULE_TEXTDOMAIN ),
			'>=' => __( 'is greater or equal to', RULE_TEXTDOMAIN ),
			'<=' => __( 'is less or equal to', RULE_TEXTDOMAIN ),
		);

		return $operators;
	}

	public function get_condition_input_type() {
		return 'Text';
	}

	public function is_match( $rule_data ) {

		$price = WC()->cart->get_total( 'edit' );

		$value = (float) $rule_data['condition'];
		switch ( $rule_data['operator'] ) {
			case '==':
				$result = $price == $value;
				break;
			case '!=':
				$result = $price != $value;
				break;
			case '>':
				$result = $price > $value;
				break;
			case '<':
				$result = $price < $value;
				break;
			case '>=':
				$result = $price >= $value;
				break;
			case '<=':
				$result = $price <= $value;
				break;
			default:
				$result = false;
				break;
		}

		return $this->return_is_match( $result, $rule_data );
	}

}


