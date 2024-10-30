<?php
defined( 'ABSPATH' ) || exit;

class xlwcfg_Input_Html_All_Products {
	public function __construct() {
		// vars
		$this->type = 'Html_All_Products';

		$this->defaults = array(
			'default_value' => '',
			'class'         => '',
			'placeholder'   => ''
		);
	}

	public function render( $field, $value = null ) {
		_e( 'This Gift campaign would work for all the products.', RULE_TEXTDOMAIN );
	}

}