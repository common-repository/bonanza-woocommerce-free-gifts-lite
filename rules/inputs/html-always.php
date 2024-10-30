<?php
defined( 'ABSPATH' ) || exit;

class xlwcfg_Input_Html_Always {
	public function __construct() {
		// vars
		$this->type = 'Html_Always';

		$this->defaults = array(
			'default_value' => '',
			'class'         => '',
			'placeholder'   => ''
		);
	}

	public function render( $field, $value = null ) {
		_e( 'This Gift campaign would work for all the shoppers on this Store.', RULE_TEXTDOMAIN );
	}

}