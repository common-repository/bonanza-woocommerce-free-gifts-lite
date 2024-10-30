<?php
defined( 'ABSPATH' ) || exit;

class xlwcfg_Input_Text {

	public function __construct() {
		// vars
		$this->type = 'Text';

		$this->defaults = array(
			'default_value' => '',
			'class'         => '',
			'placeholder'   => ''
		);
	}

	public function render( $field, $value = null ) {
		$field = array_merge( $this->defaults, $field );
		if ( ! isset( $field['id'] ) ) {
			$field['id'] = sanitize_title( $field['id'] );
		}

		echo '<input name="' . $field['name'] . '" type="text" id="' . esc_attr( $field['id'] ) . '" class="' . esc_attr( $field['class'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $value . '" />';
	}

}

class xlwcfg_Input_Text_Customer {

	public function __construct() {
		// vars
		$this->type = 'Text_Customer';

		$this->defaults = array(
			'default_value' => '',
			'class'         => '',
			'placeholder'   => '',
		);
	}

	public function render( $field, $value = null ) {
		$field = array_merge( $this->defaults, $field );
		if ( ! isset( $field['id'] ) ) {
			$field['id'] = sanitize_title( $field['id'] );
		}
		echo '<span>' . __( 'Works with logged in users only.', RULE_TEXTDOMAIN ) . '</span>';
		echo '<input name="' . $field['name'] . '" type="text" id="' . esc_attr( $field['id'] ) . '" class="' . esc_attr( $field['class'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . $value . '" />';
	}

}