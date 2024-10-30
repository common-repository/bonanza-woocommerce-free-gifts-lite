<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class XLWCFG_Appearance
 * @package Bonanza-Lite
 * @author XlPlugins
 */
class XLWCFG_Appearance {

	public static $_instance = null;

	public function __construct() {
		add_action( 'wp', array( $this, 'load_hooks' ) );
	}

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/**
	 * Load hooks on `wp` hooks
	 */
	public function load_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'xlwcfg_enqueue_scripts' ) );

		$this->xlwcfg_add_single_product_hooks();

		/* xlwcfg_the_content is the replacement of the_content */
		add_filter( 'xlwcfg_the_content', 'wptexturize' );
		add_filter( 'xlwcfg_the_content', 'convert_smilies', 20 );
		add_filter( 'xlwcfg_the_content', 'wpautop' );
		add_filter( 'xlwcfg_the_content', 'shortcode_unautop' );
		add_filter( 'xlwcfg_the_content', 'prepend_attachment' );
		add_filter( 'xlwcfg_the_content', 'wp_make_content_images_responsive' );
		add_filter( 'xlwcfg_the_content', 'do_shortcode', 11 );
	}

	/**
	 * Enqueue assets on the frontend
	 */
	public function xlwcfg_enqueue_scripts() {
		$min = ( true === SCRIPT_DEBUG ) ? '.min' : '';
		wp_enqueue_style( 'xlwcfg_public_css', XLWCFG_PLUGIN_URL . '/assets/css/xlwcfg-style.css', array(), XLWCFG_VER );

		// store currency
		$xlwcfg_currency                = get_woocommerce_currency_symbol();
		$localize_arr['xlwcfg_version'] = XLWCFG_VERSION;
		$localize_arr['currency']       = $xlwcfg_currency;

		$localize_arr = apply_filters( 'xlwcfg_localize_js_data', $localize_arr );

		wp_enqueue_script( 'jquery' );
		wp_localize_script( 'jquery', 'xlwcfg_data', $localize_arr );
	}

	public function xlwcfg_add_single_product_hooks() {
		add_action( 'woocommerce_after_template_part', array( $this, 'xlwcfg_after_template_part' ), 95 );
		add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'xlwcfg_after_add_to_cart_template' ), 95 );
	}

	public function xlwcfg_after_template_part( $template_name = '', $template_path = '', $located = '', $args = array() ) {
		if ( empty( $template_name ) ) {
			return '';
		}
		if ( $template_name == 'single-product/short-description.php' ) {
			echo $this->xlwcfg_position( 5 );
		} elseif ( $template_name == 'single-product/price.php' ) {
			echo $this->xlwcfg_position( 4 );
		}
	}

	public function xlwcfg_after_add_to_cart_template() {
		ob_start();
		echo $this->xlwcfg_position( 6 );
		$output = ob_get_clean();
		if ( $output !== "" ) {
			echo '<div class="xlwcfg_clear" style="height: 15px;"></div>';
		}
		echo $output;
	}

	public function xlwcfg_position( $position = '', $type = 'single' ) {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		/** Hide Gift messages if the product is out of stock */
		if ( ! $product->is_in_stock() ) {
			return;
		}

		if ( is_user_logged_in() && current_user_can( 'administrator' ) && isset( $_GET['xlwcfg_positions'] ) && $_GET['xlwcfg_positions'] == 'yes' && ! empty( $position ) ) {
			XLWCFG_Common::pr( 'Position: ' . $this->get_position_name( $position ) );
		}

		$gifts = XLWCFG_Common::get_product_valid_gifts( $product->get_id() );

		if ( empty( $gifts ) || ! is_array( $gifts ) || count( $gifts ) == 0 ) {
			return;
		}

		foreach ( $gifts as $gift_id ) {
			$gift_meta = XLWCFG_Common::get_post_meta_data( $gift_id );

			if ( false == XLWCFG_Core()->data->validate_gift_before_use( $gift_id, true ) ) {
				continue;
			}

			if ( $type == 'single' ) {
				/** Single product */
				if ( $position == absint( $gift_meta['single_pro_position'] ) ) {
					$this->xlwcfg_display_single_text( $gift_id, $gift_meta, $product->get_id() );
				}
			}
		}


	}

	/**
	 * Output text on Single product
	 *
	 * @param $gift_id
	 * @param $gift_meta
	 * @param $product_id
	 */
	public function xlwcfg_display_single_text( $gift_id, $gift_meta, $product_id ) {

		if ( ! isset( $gift_meta['single_pro_text'] ) || empty( $gift_meta['single_pro_text'] ) ) {
			return;
		}

		$key = 's_' . $gift_id . '_' . $product_id;

		$xlwcfg_style = array();

		if ( isset( $gift_meta['single_pro_bg_color'] ) && ! empty( $gift_meta['single_pro_bg_color'] ) ) {
			$xlwcfg_style[ '.xlwcfg_text_wrap .xlwcfg_custom_text_' . $key ]['background'] = $gift_meta['single_pro_bg_color'];
		}
		if ( isset( $gift_meta['single_pro_text_color'] ) && ! empty( $gift_meta['single_pro_text_color'] ) ) {
			$xlwcfg_style[ '.xlwcfg_text_wrap .xlwcfg_custom_text_' . $key ]['color']        = $gift_meta['single_pro_text_color'];
			$xlwcfg_style[ '.xlwcfg_text_wrap .xlwcfg_custom_text_' . $key . ' p' ]['color'] = $gift_meta['single_pro_text_color'];
		}
		if ( isset( $gift_meta['single_pro_fs'] ) && ! empty( $gift_meta['single_pro_fs'] ) ) {
			$xlwcfg_style[ '.xlwcfg_text_wrap .xlwcfg_custom_text_' . $key ]['font-size']        = $gift_meta['single_pro_fs'] . 'px';
			$xlwcfg_style[ '.xlwcfg_text_wrap .xlwcfg_custom_text_' . $key . ' p' ]['font-size'] = $gift_meta['single_pro_fs'] . 'px';
		}
		if ( isset( $gift_meta['single_pro_border_style'] ) && $gift_meta['single_pro_border_style'] != 'none' ) {
			$xlwcfg_style[ '.xlwcfg_text_wrap .xlwcfg_custom_text_' . $key ]['border-style'] = $gift_meta['single_pro_border_style'];
			$xlwcfg_style[ '.xlwcfg_text_wrap .xlwcfg_custom_text_' . $key ]['border-color'] = isset( $gift_meta['single_pro_border_color'] ) ? $gift_meta['single_pro_border_color'] : '#ffffff';
			$xlwcfg_style[ '.xlwcfg_text_wrap .xlwcfg_custom_text_' . $key ]['border-width'] = isset( $gift_meta['single_pro_border_width'] ) ? $gift_meta['single_pro_border_width'] . 'px' : '1px';
		}
		if ( isset( $gift_meta['single_pro_padding_l'] ) && ! empty( $gift_meta['single_pro_padding_l'] ) ) {
			$xlwcfg_style[ '.xlwcfg_text_wrap .xlwcfg_custom_text_' . $key ]['padding-left']  = $gift_meta['single_pro_padding_l'] . 'px';
			$xlwcfg_style[ '.xlwcfg_text_wrap .xlwcfg_custom_text_' . $key ]['padding-right'] = $gift_meta['single_pro_padding_l'] . 'px';
		}
		if ( isset( $gift_meta['single_pro_padding_t'] ) && ! empty( $gift_meta['single_pro_padding_t'] ) ) {
			$xlwcfg_style[ '.xlwcfg_text_wrap .xlwcfg_custom_text_' . $key ]['padding-top']    = $gift_meta['single_pro_padding_t'] . 'px';
			$xlwcfg_style[ '.xlwcfg_text_wrap .xlwcfg_custom_text_' . $key ]['padding-bottom'] = $gift_meta['single_pro_padding_t'] . 'px';
		}
		$this->xlwcfg_css_print( $xlwcfg_style );
		?>
        <div class="xlwcfg_text_wrap" data-type="single">
            <div class="xlwcfg_custom_text xlwcfg_custom_text_<?php echo $key; ?>" data-gift-id="<?php echo $gift_id; ?>">
				<?php
				$output = $gift_meta['single_pro_text'];
				echo apply_filters( 'xlwcfg_the_content', $output );
				?>
            </div>
        </div>
		<?php
	}


	/**
	 * Print Gift message CSS
	 *
	 * @param $xlwcfg_style
	 */
	public function xlwcfg_css_print( $xlwcfg_style ) {

		if ( count( $xlwcfg_style ) == 0 ) {
			return;
		}

		echo "<style>\n";
		foreach ( $xlwcfg_style as $elem => $single_css ) {
			echo $elem . '{';
			if ( is_array( $single_css ) && count( $single_css ) > 0 ) {
				foreach ( $single_css as $css_prop => $css_val ) {
					echo $css_prop . ':' . $css_val . ';';
				}
			}
			echo "}\n";
		}
		echo '</style>';
	}

	public function get_position_name( $index ) {

		$locations = array(
			'4' => __( 'Below the Price', 'bonanza-woocommerce-free-gifts-lite' ),
			'5' => __( 'Below Short Description', 'bonanza-woocommerce-free-gifts-lite' ),
			'6' => __( 'Below Add to Cart Button', 'bonanza-woocommerce-free-gifts-lite' ),
		);

		return $locations[ $index ];
	}

}

if ( class_exists( 'XLWCFG_Appearance' ) ) {
	XLWCFG_Core::register( 'appearance', 'XLWCFG_Appearance' );
}
