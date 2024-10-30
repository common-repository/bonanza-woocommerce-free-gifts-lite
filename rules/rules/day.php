<?php

class XLWCFG_Rule_Day extends XLWCFG_Rule_Base {


	public function __construct() {
		parent::__construct( 'day' );
	}

	public function get_possible_rule_operators() {

		$operators = array(
			'==' => __( 'is', 'bonanza-woocommerce-free-gifts-lite' ),
			'!=' => __( 'is not', 'bonanza-woocommerce-free-gifts-lite' ),
		);

		return $operators;
	}

	public function get_possible_rule_values() {
		$options = array(
			'0' => __( 'Sunday', 'bonanza-woocommerce-free-gifts-lite' ),
			'1' => __( 'Monday', 'bonanza-woocommerce-free-gifts-lite' ),
			'2' => __( 'Tuesday', 'bonanza-woocommerce-free-gifts-lite' ),
			'3' => __( 'Wednesday', 'bonanza-woocommerce-free-gifts-lite' ),
			'4' => __( 'Thursday', 'bonanza-woocommerce-free-gifts-lite' ),
			'5' => __( 'Friday', 'bonanza-woocommerce-free-gifts-lite' ),
			'6' => __( 'Saturday', 'bonanza-woocommerce-free-gifts-lite' ),
		);

		return $options;
	}

	public function get_condition_input_type() {
		return 'Select';
	}

	public function is_match( $rule_data ) {
		$result    = false;
		$date_time = new DateTime();
		$date_time->setTimezone( new DateTimeZone( XLWCFG_Common::wc_timezone_string() ) );

		$day_today = $date_time->format( 'w' );

		if ( isset( $rule_data['condition'] ) && isset( $rule_data['operator'] ) ) {

			if ( '==' === $rule_data['operator'] ) {
				$result = $day_today === $rule_data['condition'] ? true : false;
			}

			if ( '!=' === $rule_data['operator'] ) {
				$result = $day_today === $rule_data['condition'] ? false : true;
			}
		}

		return $this->return_is_match( $result, $rule_data );
	}

}
