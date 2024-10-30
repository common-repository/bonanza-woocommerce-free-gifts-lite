<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class XLWCFG_Rules
 * @package Bonanza-Lite
 * @author XlPlugins
 */
class XLWCFG_Rules {
	private static $ins = null;
	private static $rules_dir = '';
	private static $rules_url = '';
	public $is_executing_rule = false;
	public $excluded_rules = array();
	public $excluded_rules_categories = array();
	public $processed = array();
	public $record = array();
	public $skipped = array();
	public $env_product = '';
	public $env_content_id = '';
	public $env_product_gift = array();

	public function __construct() {
		define( 'RULE_TEXTDOMAIN', XLWCFG_SLUG );

		self::$rules_dir = XLWCFG_PLUGIN_DIR . 'rules/';
		self::$rules_url = plugin_dir_url( XLWCFG_PLUGIN_FILE ) . 'rules/';

		$this->load_hooks();

		/** Ajax call */
		add_action( 'wp_ajax_xlwcfg_change_rule_type', array( __CLASS__, 'ajax_render_rule_choice' ) );
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function load_hooks() {
		add_action( 'init', array( $this, 'load_rules_classes' ), 20 );

		/** Save rules */
		add_action( 'init', array( $this, 'maybe_save_rules' ), 30 );

		add_action( 'admin_enqueue_scripts', array( $this, 'rules_enqueue_scripts' ) );

		add_filter( 'xlwcfg_xlwcfg_rule_get_rule_types', array( $this, 'default_rule_types' ), 1 );
		add_filter( 'xlwcfg_xlwcfg_rule_get_rule_types_product', array( $this, 'rule_types_product' ), 1 );
		add_action( 'xlwcfg_before_rules', array( $this, 'reset_skipped' ) );
	}

	public function rules_enqueue_scripts() {
		if ( XLWCFG_Common::xlwcfg_valid_admin_pages() ) {
			wp_enqueue_style( 'xlwcfg-admin-app', self::$rules_url . 'assets/admin-rules.css', array(), XLWCFG_VER );
			wp_register_script( 'xlwcfg-rules-app', self::$rules_url . 'assets/admin-rules.js', array( 'jquery', 'jquery-ui-datepicker', 'underscore', 'backbone' ), XLWCFG_VER );
			wp_enqueue_script( 'xlwcfg-rules-app' );
			wp_register_script( 'jquery-masked-input', self::$rules_url . 'assets/jquery.maskedinput.min.js', array( 'jquery' ), XLWCFG_VER );
			wp_enqueue_script( 'jquery-masked-input' );
		}
	}

	/**
	 * Match the rules groups based on the environment its called on
	 * Iterate over the setof rules set against each offer and validates for the rules set
	 * Now this function also powered in a way that it can hold some rule for the next environment to run on
	 *
	 * @param $content_id: Id of the funnel
	 *
	 * @return bool|mixed|void
	 */
	public function match_groups( $content_id ) {

		$this->is_executing_rule = true;

		//allowing rules to get manipulated using external logic
		$external_rules = apply_filters( 'xlwcfg_before_rules', true, $content_id );
		if ( ! $external_rules ) {

			$this->is_executing_rule = false;

			return false;
		}

		$content_meta = XLWCFG_Common::get_post_meta_data( $content_id );
		$groups       = isset( $content_meta['rules'] ) ? $content_meta['rules'] : array();
		$result       = $this->_validate( $groups, $content_id );

		$get_skipped_rules = $this->skipped;

		if ( $get_skipped_rules && count( $get_skipped_rules ) > 0 ) {

			/**
			 * If we have any rules that skipped because they belong to next upcoming environment.
			 * We got to save these rules and process them in correct environment
			 * Assigning sustained rules
			 * returning as false, to prevent any success further
			 */
			$display                        = false;
			$this->processed[ $content_id ] = $get_skipped_rules;
		} else {
			$display                        = apply_filters( 'xlwcfg_after_rules', $result, $content_id, $this );
			$this->processed[ $content_id ] = $display;
		}

		$this->is_executing_rule = false;

		return $display;
	}

	/**
	 * Validates and group whole block
	 *
	 * @param $groups
	 *
	 * @return bool
	 */
	protected function _validate( $groups, $content_id ) {

		if ( $groups && is_array( $groups ) && count( $groups ) ) {
			foreach ( $groups as $type => $groups_category ) {

				if ( in_array( $type, $this->excluded_rules_categories ) ) {
					continue;
				}
				$result = $this->_validate_rule_block( $groups_category, $type, $content_id );
				if ( false === $result ) {
					return false;
				}
			}
		}

		return true;
	}

	public function get_processed_rules() {
		return $this->processed;
	}

	public function find_match() {

		$get_processed = $this->get_processed_rules();

		foreach ( $get_processed as $id => $results ) {

			if ( false === is_bool( $results ) ) {
				return false;
			}
			if ( true === $results ) {
				return $id;
			}
		}

		return false;
	}

	public function sustain_results() {
		$get_processed = $this->get_processed_rules();
		XLWCFG_Core()->data->set( 'processed', $get_processed, 'rules' );
		XLWCFG_Core()->data->save( 'rules' );
	}

	protected function _validate_rule_block( $groups_category, $type, $content_id ) {
		$iteration_results = array();
		if ( $groups_category && is_array( $groups_category ) && count( $groups_category ) ) {

			foreach ( $groups_category as $group_id => $group ) {
				$result        = null;
				$group_skipped = array();
				foreach ( $group as $rule_id => $rule ) {

					//just skipping the rule if excluded, so that it wont play any role in final judgement
					if ( in_array( $rule['rule_type'], $this->excluded_rules ) ) {
						continue;
					}
					$rule_object = self::woocommerce_xlwcfg_rule_get_rule_object( $rule['rule_type'] );

					if ( is_object( $rule_object ) ) {
						$this->env_content_id = $content_id;

						$match = $rule_object->is_match( $rule );

						$this->env_content_id = '';

						//assigning values to the array.
						//on false, as this is single group (bind by AND), one false would be enough to declare whole result as false so breaking on that point
						if ( false === $match ) {
							$iteration_results[ $group_id ] = 0;
							break;
						} else {
							$iteration_results[ $group_id ] = 1;
						}
					}
				}

				//checking if current group iteration combine returns true, if its true, no need to iterate other groups
				if ( isset( $iteration_results[ $group_id ] ) && $iteration_results[ $group_id ] === 1 ) {

					/**
					 * Making sure the skipped rule is only taken into account when we have status TRUE by executing rest of the rules.
					 */
					if ( $group_skipped && count( $group_skipped ) > 0 ) {
						$this->skipped = array_merge( $this->skipped, $group_skipped );
					}
					break;
				}
			}

			//checking count of all the groups iteration
			if ( count( $iteration_results ) > 0 ) {

				//checking for the any true in the groups
				if ( array_sum( $iteration_results ) > 0 ) {
					$display = true;
				} else {
					$display = false;
				}
			} else {

				//handling the case where all the rules got skipped
				$display = true;
			}
		} else {
			$display = true; //Always display the content if no rules have been configured.
		}

		return $display;
	}

	/**
	 * Creates an instance of a rule object
	 * @global array $woocommerce_xlwcfg_rule_rules
	 *
	 * @param $rule_type: The slug of the rule type to load.
	 *
	 * @return xlwcfg_Rule_Base or superclass of xlwcfg_Rule_Base
	 */
	public static function woocommerce_xlwcfg_rule_get_rule_object( $rule_type ) {
		global $woocommerce_xlwcfg_rule_rules;
		if ( isset( $woocommerce_xlwcfg_rule_rules[ $rule_type ] ) ) {
			return $woocommerce_xlwcfg_rule_rules[ $rule_type ];
		}
		$class = 'XLWCFG_Rule_' . $rule_type;
		if ( class_exists( $class ) ) {
			$woocommerce_xlwcfg_rule_rules[ $rule_type ] = new $class;

			return $woocommerce_xlwcfg_rule_rules[ $rule_type ];
		} else {
			return null;
		}
	}

	protected function _push_to_skipped( $rule ) {
		array_push( $this->skipped, $rule );
	}

	public static function load_rules_classes() {
		//Include our default rule classes
		include self::$rules_dir . 'rules/base.php';
		include self::$rules_dir . 'rules/general.php';
		include self::$rules_dir . 'rules/cart.php';
		include self::$rules_dir . 'rules/customer.php';
		include self::$rules_dir . 'rules/product.php';
		include self::$rules_dir . 'rules/day.php';

		if ( is_admin() || defined( 'DOING_AJAX' ) ) {
			//Include the admin interface builder
			include self::$rules_dir . 'class-xlwcfg-input-builder.php';
			include self::$rules_dir . 'inputs/html-always.php';
			include self::$rules_dir . 'inputs/html-all-products.php';
			include self::$rules_dir . 'inputs/text.php';
			include self::$rules_dir . 'inputs/select.php';
			include self::$rules_dir . 'inputs/chosen-select.php';
			include self::$rules_dir . 'inputs/product-select.php';
		}
	}


	public static function default_rule_types( $types ) {
		$types = array(
			__( 'General', RULE_TEXTDOMAIN )  => array(
				'general_always' => __( 'Always', RULE_TEXTDOMAIN ),
			),
			__( 'Cart', RULE_TEXTDOMAIN )     => array(
				'cart_total'           => __( 'Cart Total', RULE_TEXTDOMAIN ),
				'cart_payment_gateway' => __( 'Cart Payment Gateway', RULE_TEXTDOMAIN ),
				'cart_coupon'          => __( 'Cart Coupon', RULE_TEXTDOMAIN ),
			),
			__( 'Customer', RULE_TEXTDOMAIN ) => array(
				'customer_order_count' => __( 'Customer Order Count', RULE_TEXTDOMAIN ),
			),
			__( 'Day', RULE_TEXTDOMAIN ) => array(
				'day' => __( 'Day', RULE_TEXTDOMAIN ),
			),
		);

		return $types;
	}

	public static function rule_types_product( $types ) {
		$types = array(
			__( 'Product', RULE_TEXTDOMAIN ) => array(
				'all_products'     => __( 'Any Product', RULE_TEXTDOMAIN ),
				'product_item'     => __( 'Product(s)', RULE_TEXTDOMAIN ),
				'product_category' => __( 'Product Category(s)', RULE_TEXTDOMAIN ),
				'product_tags'     => __( 'Product Tag(s)', RULE_TEXTDOMAIN ),
			),
		);

		return $types;
	}

	/**
	 * Save rules in post
	 */
	public function maybe_save_rules() {

		if ( null !== filter_input( INPUT_POST, 'xlwcfg_rule' ) ) {
			$post_id = filter_input( INPUT_POST, 'post_ID' );
			do_action( "xlwcfg_before_saving_rules", $post_id, $_POST['xlwcfg_rule'] );
			update_post_meta( $post_id, '_xlwcfg_rules', $_POST['xlwcfg_rule'] );
		}

	}

	public function set_environment_var( $key = 'order', $value = '' ) {

		if ( '' === $value ) {
			return;
		}
		$this->environments[ $key ] = $value;

	}

	public function reset_skipped( $result ) {
		$this->skipped = array();

		return $result;
	}

	public function get_environment_var( $key = 'order' ) {
		return isset( $this->environments[ $key ] ) ? $this->environments[ $key ] : false;
	}

	public function rule_views_path() {
		return XLWCFG_PLUGIN_DIR . '/rules/views';
	}

	public function render_basic_rules() {
		ob_start();
		include_once( $this->rule_views_path() . '/rules-basic.php' );

		return ob_get_clean();
	}

	public function render_product_rules() {
		ob_start();
		include_once( $this->rule_views_path() . '/rules-product.php' );

		return ob_get_clean();
	}

	public function get_entity_rules( $funnel_id, $rule_type = 'basic' ) {

		$data = get_post_meta( $funnel_id, '_xlwcfg_rules', true );

		return apply_filters( 'get_funnel_xlwcfg_rules', ( isset( $data[ $rule_type ] ) ) ? $data[ $rule_type ] : array(), $funnel_id, $rule_type );
	}

	public function woocommerce_xlwcfg_rule_get_input_object( $input_type ) {
		global $woocommerce_xlwcfg_rule_inputs;
		if ( isset( $woocommerce_xlwcfg_rule_inputs[ $input_type ] ) ) {
			return $woocommerce_xlwcfg_rule_inputs[ $input_type ];
		}
		$class = 'xlwcfg_Input_' . str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $input_type ) ) );
		if ( class_exists( $class ) ) {
			$woocommerce_xlwcfg_rule_inputs[ $input_type ] = new $class;
		} else {
			$woocommerce_xlwcfg_rule_inputs[ $input_type ] = apply_filters( 'woocommerce_xlwcfg_rule_get_input_object', $input_type );
		}

		return $woocommerce_xlwcfg_rule_inputs[ $input_type ];
	}

	public static function ajax_render_rule_choice( $options ) {

		$defaults = array(
			'group_id'  => 0,
			'rule_id'   => 0,
			'rule_type' => null,
			'condition' => null,
			'operator'  => null,
		);
		$is_ajax  = false;

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'xlwcfg_change_rule_type' ) {
			$is_ajax = true;
		}
		if ( $is_ajax ) {
			$options = array_merge( $defaults, $_POST );
		} else {
			$options = array_merge( $defaults, $options );
		}
		$rule_object = self::woocommerce_xlwcfg_rule_get_rule_object( $options['rule_type'] );
		if ( ! empty( $rule_object ) ) {
			$values               = $rule_object->get_possible_rule_values();
			$operators            = $rule_object->get_possible_rule_operators();
			$condition_input_type = $rule_object->get_condition_input_type();
			// create operators field
			$operator_args = array(
				'input'   => 'select',
				'name'    => 'xlwcfg_rule[' . $options['rule_category'] . '][' . $options['group_id'] . '][' . $options['rule_id'] . '][operator]',
				'choices' => $operators,
			);
			echo '<td class="operator">';
			if ( ! empty( $operators ) ) {
				xlwcfg_Input_Builder::create_input_field( $operator_args, $options['operator'] );
			} else {
				echo '<input type="hidden" name="' . $operator_args['name'] . '" value="==" />';
			}
			echo '</td>';
			// create values field
			$value_args = array(
				'input'   => $condition_input_type,
				'name'    => 'xlwcfg_rule[' . $options['rule_category'] . '][' . $options['group_id'] . '][' . $options['rule_id'] . '][condition]',
				'choices' => $values,
			);
			echo '<td class="condition">';
			xlwcfg_Input_Builder::create_input_field( $value_args, $options['condition'] );
			echo '</td>';
		}
		// ajax?
		if ( $is_ajax ) {
			die();
		}
	}

	public static function render_rule_choice_template( $options ) {
		// defaults
		$defaults             = array(
			'group_id'  => 0,
			'rule_id'   => 0,
			'rule_type' => null,
			'condition' => null,
			'operator'  => null,
			'category'  => 'basic',
		);
		$options              = array_merge( $defaults, $options );
		$rule_object          = self::woocommerce_xlwcfg_rule_get_rule_object( $options['rule_type'] );
		$values               = $rule_object->get_possible_rule_values();
		$operators            = $rule_object->get_possible_rule_operators();
		$condition_input_type = $rule_object->get_condition_input_type();
		// create operators field
		$operator_args = array(
			'input'   => 'select',
			'name'    => 'xlwcfg_rule[' . $options["category"] . '][<%= groupId %>][<%= ruleId %>][operator]',
			'choices' => $operators,
		);
		echo '<td class="operator">';
		if ( ! empty( $operators ) ) {
			xlwcfg_Input_Builder::create_input_field( $operator_args, $options['operator'] );
		} else {
			echo '<input type="hidden" name="' . $operator_args['name'] . '" value="==" />';
		}
		echo '</td>';
		// create values field
		$value_args = array(
			'input'   => $condition_input_type,
			'name'    => 'xlwcfg_rule[basic][<%= groupId %>][<%= ruleId %>][condition]',
			'choices' => $values,
		);
		echo '<td class="condition">';
		xlwcfg_Input_Builder::create_input_field( $value_args, $options['condition'] );
		echo '</td>';
	}


}

if ( class_exists( 'XLWCFG_Rules' ) ) {
	XLWCFG_Core::register( 'rules', 'XLWCFG_Rules' );
}
