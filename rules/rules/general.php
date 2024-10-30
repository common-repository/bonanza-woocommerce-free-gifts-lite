<?php
defined( 'ABSPATH' ) || exit;

class XLWCFG_Rule_General_Always extends XLWCFG_Rule_Base {
	public function __construct() {
		parent::__construct( 'general_always' );
	}

	public function get_possible_rule_operators() {
		return null;
	}

	public function get_possible_rule_values() {
		return null;
	}

	public function get_condition_input_type() {
		return 'Html_Always';
	}

	public function is_match( $rule_data ) {
		return true;
	}

}

class XLWCFG_Rule_All_Products extends XLWCFG_Rule_Base {
	public function __construct() {
		parent::__construct( 'all_products' );
	}

	public function get_possible_rule_operators() {
		return null;
	}

	public function get_possible_rule_values() {
		return null;
	}

	public function get_condition_input_type() {
		return 'Html_All_Products';
	}

	public function is_match( $rule_data ) {
		return true;
	}

}
