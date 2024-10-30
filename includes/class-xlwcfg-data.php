<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class XLWCFG_Data
 * @package Bonanza-Lite
 * @author XlPlugins
 */
class XLWCFG_Data {

	private static $ins = null;
	public $run_calc_total = false;
	public $gift_repeat = array();
	public $gift_qty = array();

	public function __construct() {

		/** Return if admin ajax calls */
		if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], $this->get_restricted_action() ) ) {
			return;
		}

		add_action( 'wp', array( $this, 'reset_logs' ), 1 );

		/** Allowing more data attributes in the woocommerce post context */
		add_filter( 'wp_kses_allowed_html', array( $this, 'allow_data_attrs' ), 10, 2 );

		add_action( 'wp_head', array( $this, 'page_noindex' ) );

		$this->hook_func();

		/** Actions before or after match rules */
		add_action( 'xlwcfg_before_matching_rules_arithmetic', array( $this, 'exclude_non_arithmetic_rules' ) );
		add_action( 'xlwcfg_after_matching_rules_arithmetic', array( $this, 'clear_exclusions' ) );

		add_action( 'xlwcfg_before_matching_rules_indexing', array( $this, 'exclude_arithmetic_rules' ) );
		add_action( 'xlwcfg_after_matching_rules_indexing', array( $this, 'clear_exclusions' ) );

		/** Modify price of gift to 0 */
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'modify_gifts_price' ), 999999 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'modify_gifts_price_per_session' ), 999999 );

		/** Manage added gifts remove link and qty */
		add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'hide_gifts_remove_link' ), 999999, 2 );
		add_filter( 'woocommerce_cart_item_quantity', array( $this, 'hide_gifts_quantity' ), 999999, 2 );
		add_filter( 'woocommerce_cart_item_price', array( $this, 'display_gifts_price_free' ), 999999, 3 );
		add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'display_gifts_price_free' ), 999999, 3 );

		/** Manage gift product is_purchasable status */
		add_filter( 'woocommerce_is_purchasable', array( $this, 'woocommerce_is_purchasable' ), 999999, 2 );

		/** Saving order item meta for gifts */
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'woocommerce_create_order_line_item' ), 999999, 4 );
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	public function reset_logs() {
		if ( ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) && is_singular( 'product' ) ) {
			if ( ( XLWCFG_Common::$is_force_debug === true ) || ( WP_DEBUG === true && ! is_admin() ) ) {
				xlwcfg_force_log( 'abs', 'force.txt', 'w' );
			}
		}
	}

	public function allow_data_attrs( $allowed, $context ) {
		if ( $context === 'post' ) {
			$allowed['div']['data-type'] = true;
			$allowed['style']            = true;
		}

		return $allowed;
	}

	public function page_noindex() {
		$post_type = XLWCFG_Common::get_free_gift_cpt_slug();
		if ( is_singular( $post_type ) ) {
			echo "<meta name='robots' content='noindex,follow' />\n";
		}
	}

	/**
	 * Add hooks
	 */
	public function hook_func() {
		/** Cart hooks */
		add_filter( 'woocommerce_update_cart_action_cart_updated', array( $this, 'setup_all_gifts' ), 99 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'setup_all_gifts' ), 99 );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'setup_all_gifts' ), 99 );
		add_action( 'woocommerce_cart_item_restored', array( $this, 'setup_all_gifts' ), 99 );

		if ( ! isset( $_GET['wc-ajax'] ) && ! wp_doing_ajax() ) {
			add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'setup_all_gifts' ), 99 );
		}

		/** Checkout hooks */
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'setup_all_gifts' ), 99 );
	}

	/**
	 * Remove hooks
	 */
	public function unhook_func() {
		/** Cart hooks */
		remove_filter( 'woocommerce_update_cart_action_cart_updated', array( $this, 'setup_all_gifts' ), 99 );
		remove_action( 'woocommerce_add_to_cart', array( $this, 'setup_all_gifts' ), 99 );
		remove_action( 'woocommerce_cart_item_removed', array( $this, 'setup_all_gifts' ), 99 );
		remove_action( 'woocommerce_cart_item_restored', array( $this, 'setup_all_gifts' ), 99 );

		if ( ! isset( $_GET['wc-ajax'] ) && ! wp_doing_ajax() ) {
			remove_action( 'woocommerce_cart_loaded_from_session', array( $this, 'setup_all_gifts' ), 99 );
		}

		/** Checkout hooks */
		remove_action( 'woocommerce_checkout_update_order_review', array( $this, 'setup_all_gifts' ), 99 );
	}

	public function setup_all_gifts( $return = '' ) {
		global $woocommerce;

		/** checking 404 page at woocommerce_cart_loaded_from_session hook */
		if ( 'woocommerce_cart_loaded_from_session' == current_action() && is_404() ) {
			return true;
		}

		$cart = WC()->cart->get_cart();

		$gift_posts = array();
		$gift_hash  = array();

		if ( is_array( $cart ) && count( $cart ) > 0 ) {

			XLWCFG_Common::debug( current_action(), 'action' );

			/** Remove all gifts from cart */
			$cart = $this->remove_all_gifts_from_cart( $cart );

			/** Unset all local properties */
			$this->gift_qty    = array();
			$this->gift_repeat = array();

			foreach ( $cart as $product_hash => $item ) {
				$product_id   = $item['product_id'];
				$variation_id = $item['variation_id'];
				$qty          = $item['quantity'];

				$product_gifts = XLWCFG_Common::get_product_valid_gifts( $product_id );

				if ( ! is_array( $product_gifts ) || count( $product_gifts ) == 0 ) {
					continue;
				}

				XLWCFG_Common::debug( $product_gifts, 'product id ' . $product_id . ' gifts' );

				foreach ( $product_gifts as $gift_key => $gift_id ) {

					/** Gift + Arithmetic rule validation */
					if ( false == $this->validate_gift_before_use( $gift_id ) ) {
						unset( $product_gifts[ $gift_key ] );
						XLWCFG_Common::debug( $gift_id . ' arithmetic validation failed', 'product id ' . $product_id . ' gifts' );
						continue;
					}

					/** Validate cart item variation with gift get product */
					if ( $variation_id > 0 && false === $this->validate_variation_in_cart( $gift_id, $variation_id ) ) {
						unset( $product_gifts[ $gift_key ] );
						XLWCFG_Common::debug( $gift_id . ' validation failed', 'product id ' . $product_id . ' has variation' . $variation_id );
						continue;
					}

					$gift_hash[ $gift_id ][] = $product_hash;

					/** Set current cart item quantity in gift_qty property against gift_id */
					$this->gift_qty[ $gift_id ] = ( isset( $this->gift_qty[ $gift_id ] ) && $this->gift_qty[ $gift_id ] > 0 ) ? $this->gift_qty[ $gift_id ] + $qty : $qty;

					$gift_meta = XLWCFG_Common::get_post_meta_data( $gift_id );

					$qty_buy = ( isset( $gift_meta['gift_qty_buy'] ) && absint( $gift_meta['gift_qty_buy'] ) > 0 ) ? absint( $gift_meta['gift_qty_buy'] ) : 1;
					$repeat  = ( isset( $gift_meta['repeat'] ) && 'yes' == $gift_meta['repeat'] ) ? true : false;

					/** Unset gift from product if buy quantity higher than cart product quantity */
					if ( $this->gift_qty[ $gift_id ] < $qty_buy ) {
						unset( $product_gifts[ $gift_key ] );
					} else {
						/** Gift buy qty satisfied */
						if ( true === $repeat ) {
							$this->gift_repeat[ $gift_id ] = absint( $this->gift_qty[ $gift_id ] / $qty_buy );
						}

					}
				}
				if ( count( $product_gifts ) == 0 ) {
					continue;
				}

				$gift_posts = array_merge( $gift_posts, $product_gifts );
			}

			XLWCFG_Common::debug( $this->gift_qty, 'gift qty' );
			XLWCFG_Common::debug( $this->gift_repeat, 'gift repeat' );

			$gift_posts = array_unique( $gift_posts );

			XLWCFG_Common::debug( $gift_posts, 'final gifts ready for deploy' );
		}

		$this->apply_gifts( $gift_posts, $gift_hash );

		if ( in_array( current_action(), array( 'woocommerce_update_order_review_fragments', 'woocommerce_update_cart_action_cart_updated' ) ) ) {
			return $return;
		}
	}

	public function apply_gifts( $gift_posts, $gift_hash ) {

		if ( ! is_array( $gift_posts ) || count( $gift_posts ) == 0 ) {
			return;
		}
		$gift_new_gets = array();

		foreach ( $gift_posts as $gift_id ) {
			$gift_meta = XLWCFG_Common::get_post_meta_data( $gift_id );
			$get_prods = $gift_meta['get_products'];
			/** Get products checking already done above */

			$gift_new_gets[ $gift_id ] = $get_prods;

		}

		$this->unhook_func();

		if ( ! is_array( $gift_new_gets ) || count( $gift_new_gets ) == 0 ) {
			/** No gifts to add */
			XLWCFG_Common::debug( 'no gifts to add' );

			return;
		}

		foreach ( $gift_new_gets as $gift_id => $get_prods ) {

			$gift_meta = XLWCFG_Common::get_post_meta_data( $gift_id );

			/** Keep existing wc notices */
			$wc_notices = wc_get_notices();

			/** Adding gifts to cart */
			foreach ( $get_prods as $product_id ) {
				$args = array(
					'xlwcfg_gift_id' => $gift_id,
				);
				if ( isset( $gift_hash[ $gift_id ] ) && is_array( $gift_hash[ $gift_id ] ) && count( $gift_hash[ $gift_id ] ) > 0 ) {
					$args['xlwcfg_parent_hash'] = $gift_hash[ $gift_id ][0];
				}

				$qty_get  = ( isset( $gift_meta['gift_qty_get'] ) && absint( $gift_meta['gift_qty_get'] ) > 0 ) ? absint( $gift_meta['gift_qty_get'] ) : 1;
				$quantity = $qty_get;

				/** Integrating gifts repeat functionality */
				$gift_repeat = $this->gift_repeat;

				if ( isset( $gift_repeat[ $gift_id ] ) && absint( $gift_repeat[ $gift_id ] ) > 0 ) {
					$quantity = $quantity * absint( $gift_repeat[ $gift_id ] );
				}

				XLWCFG_Common::debug( 'Adding product ' . $product_id . ' with qty ' . $quantity . ' against a gift ' . $gift_id );

				$data = array(
					'id'           => $product_id,
					'quantity'     => $quantity,
					'variation_id' => '',
					'variation'    => '', // variation attributes
				);
				$data = apply_filters( 'xlwcfg_gift_add_to_cart_product_data', $data, $gift_id );

				/** Used in cart to handle non-purchasable products */
				$gift_session = WC()->session->get( '_xlwcfg_added_gifts' );
				if ( is_array( $gift_session ) ) {
					$gift_session[] = $product_id;
				} else {
					$gift_session = array( $product_id );
				}
				$gift_session = array_unique( $gift_session );
				WC()->session->set( '_xlwcfg_added_gifts', $gift_session );

				/** Unset WC notices; added here for debugging purpose only */
				WC()->session->set( 'wc_notices', array() );

				$result = WC()->cart->add_to_cart( $data['id'], $data['quantity'], $data['variation_id'], $data['variation'], $args );

				XLWCFG_Common::debug( wc_get_notices(), 'WC notice after adding gift to cart for ' . $product_id . ' with qty ' . $quantity );

				do_action( 'xlwcfg_after_gift_added_to_cart', $data, $gift_id, $result );
			}

			/** Add all old wc notices back */
			WC()->session->set( 'wc_notices', $wc_notices );

		}

		$this->hook_func();

		return;

	}

	/**
	 * Validate gift before use
	 *
	 * @param $gift_id
	 * @param bool $appearance
	 *
	 * @return bool
	 */
	public function validate_gift_before_use( $gift_id, $appearance = false ) {

		/** Checking if Gift cpt running */
		$gift_status = XLWCFG_Common::gift_schedule_status( $gift_id );
		if ( ! isset( $gift_status['status'] ) || false === $gift_status['status'] ) {
			return false;
		}

		/** If call from appearance then don't validate further */
		if ( true === $appearance ) {
			/** gift buy product in stock */
			$gifts_buy_status = XLWCFG_Common::gift_buy_products_in_stock( $gift_id );
			if ( false === $gifts_buy_status ) {
				return false;
			}

			/** gift get products is valid */
			$all_gifts_status = XLWCFG_Common::gift_get_products_valid( $gift_id );
			if ( false === $all_gifts_status ) {
				return false;
			}

			return true;
		}

		/** Checking if Gift has Get products */
		$get_prod_status = XLWCFG_Common::check_gift_get_product_status( $gift_id );
		if ( false === $get_prod_status ) {
			return false;
		}

		/** Run arithmetic rules */
		do_action( 'xlwcfg_before_matching_rules_arithmetic' );

		$rule_passed = XLWCFG_Core()->rules->match_groups( $gift_id );

		do_action( 'xlwcfg_after_matching_rules_arithmetic' );

		if ( false === $rule_passed ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate cart item variation with gift get product
	 *
	 * @param $gift_id
	 * @param $variation_id
	 *
	 * @return bool
	 */
	public function validate_variation_in_cart( $gift_id, $variation_id ) {

		$item_data = XLWCFG_Common::get_post_meta_data( $gift_id );
		if ( isset( $item_data['variations'] ) && is_array( $item_data['variations'] ) ) {
			if ( ! isset( $item_data['variations'][ $variation_id ] ) && empty( $item_data['variations'][ $variation_id ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array $cart cart array
	 *
	 * @return mixed
	 */
	public function remove_all_gifts_from_cart( $cart ) {

		$this->unhook_func();

		if ( is_array( $cart ) && count( $cart ) > 0 ) {
			foreach ( $cart as $hash_key => $item ) {
				if ( isset( $item['xlwcfg_gift_id'] ) && ! empty( $item['xlwcfg_gift_id'] ) ) {
					WC()->cart->remove_cart_item( $hash_key );
					unset( $cart[ $hash_key ] );
				}
			}
		}

		$this->hook_func();

		return $cart;
	}

	/**
	 * Modify gift products prices to 0
	 *
	 * @param $cart_obj
	 */
	public function modify_gifts_price( $cart_obj ) {
		$cart = $cart_obj->get_cart();
		if ( is_array( $cart ) && count( $cart ) > 0 ) {
			$custom_price = 0;
			foreach ( $cart as $key => $value ) {
				if ( isset( $value['xlwcfg_gift_id'] ) && $value['xlwcfg_gift_id'] > 0 ) {
					if ( XLWCFG_Compatibility::is_wc_version_gte_3_0() ) {
						$value['data']->set_price( $custom_price );
					} else {
						$value['data']->price = $custom_price;
					}
				}
			}
		}
	}

	/**
	 * Modify gift products prices to 0
	 *
	 * @param $session_data
	 *
	 * @return mixed
	 */
	public function modify_gifts_price_per_session( $session_data ) {
		$custom_price = 0;
		if ( isset( $session_data['xlwcfg_gift_id'] ) && $session_data['xlwcfg_gift_id'] > 0 ) {
			if ( XLWCFG_Compatibility::is_wc_version_gte_3_0() ) {
				$session_data['data']->set_price( $custom_price );
			} else {
				$session_data['data']->price = $custom_price;
			}
		}

		return $session_data;
	}

	/**
	 * Hide remove link in cart for gift products
	 *
	 * @param $link
	 * @param $hash_key
	 *
	 * @return string
	 */
	public function hide_gifts_remove_link( $link, $hash_key ) {
		if ( isset( WC()->cart->cart_contents[ $hash_key ]['xlwcfg_gift_id'] ) ) {
			return '';
		}

		return $link;
	}

	/**
	 * Hide quantity html in cart for gift products
	 *
	 * @param $quantity_html
	 * @param $hash_key
	 *
	 * @return mixed
	 */
	public function hide_gifts_quantity( $quantity_html, $hash_key ) {
		if ( isset( WC()->cart->cart_contents[ $hash_key ]['xlwcfg_gift_id'] ) ) {
			return WC()->cart->cart_contents[ $hash_key ]['quantity'];
		}

		return $quantity_html;
	}

	/**
	 * Display free as price if Bonanza free gift
	 *
	 * @param $price_html
	 * @param $cart_item
	 * @param $hash_key
	 *
	 * @return string
	 */
	public function display_gifts_price_free( $price_html, $cart_item, $hash_key ) {
		if ( isset( WC()->cart->cart_contents[ $hash_key ]['xlwcfg_gift_id'] ) && '0' == $cart_item['line_total'] ) {
			return '<span class="woocommerce-Price-amount amount">' . __( 'Free', 'woocommerce' ) . '</span>';
		}

		return $price_html;
	}

	public function exclude_non_arithmetic_rules() {
		XLWCFG_Core()->rules->excluded_rules = array( 'all_products' );
	}

	public function clear_exclusions() {
		XLWCFG_Core()->rules->excluded_rules            = array();
		XLWCFG_Core()->rules->excluded_rules_categories = array();
	}

	public function exclude_arithmetic_rules() {
		XLWCFG_Core()->rules->excluded_rules_categories = array( 'basic' );
	}

	/**
	 * Modify product purchasable status for gifts only
	 *
	 * @param $status
	 * @param $product WC_Product
	 *
	 * @return boolean
	 */
	public function woocommerce_is_purchasable( $status, $product ) {
		global $woocommerce;

		/** Status already true */
		if ( true === $status ) {
			return $status;
		}

		/** Should exists */
		if ( false === $product->exists() ) {
			return $status;
		}

		/** Product should be publish or private */
		if ( ! in_array( $product->get_status(), array( 'publish', 'private' ) ) ) {
			return $status;
		}

		/** Product type should be simple or variation */
		if ( ! in_array( $product->get_type(), array( 'simple', 'variation' ) ) ) {
			return $status;
		}

		/** Check if product is out of stock */
		remove_filter( 'woocommerce_is_purchasable', array( $this, 'woocommerce_is_purchasable' ), 999999, 2 );
		if ( false === $product->is_in_stock() ) {
			add_filter( 'woocommerce_is_purchasable', array( $this, 'woocommerce_is_purchasable' ), 999999, 2 );

			return $status;
		}
		add_filter( 'woocommerce_is_purchasable', array( $this, 'woocommerce_is_purchasable' ), 999999, 2 );

		/** Product is in common property $cart_gift_item */
		$gift = WC()->session->get( '_xlwcfg_added_gifts' );
		if ( ! empty( $gift ) && is_array( $gift ) && in_array( $product->get_id(), $gift ) ) {
			return true;
		}

		return $status;
	}

	/**
	 * Modify order items to include gift meta.
	 *
	 * @param $item
	 * @param $cart_item_key
	 * @param $values
	 * @param $order
	 */
	public function woocommerce_create_order_line_item( $item, $cart_item_key, $values, $order ) {
		if ( isset( $values['xlwcfg_gift_id'] ) ) {
			$key = '_bonanza_free_gift';
			$item->add_meta_data( $key, 'yes' );
		}
	}

	/**
	 * Restrict Bonanza data setup for admin driven ajax calls
	 *
	 * @return mixed|void
	 */
	public function get_restricted_action() {

		$restricted_actions = array(
			'oembed-cache',
			'image-editor',
			'delete-comment',
			'delete-tag',
			'delete-link',
			'delete-meta',
			'delete-post',
			'trash-post',
			'untrash-post',
			'delete-page',
			'dim-comment',
			'add-link-category',
			'add-tag',
			'get-tagcloud',
			'get-comments',
			'replyto-comment',
			'edit-comment',
			'add-menu-item',
			'add-meta',
			'add-user',
			'closed-postboxes',
			'hidden-columns',
			'update-welcome-panel',
			'menu-get-metabox',
			'wp-link-ajax',
			'menu-locations-save',
			'menu-quick-search',
			'meta-box-order',
			'get-permalink',
			'sample-permalink',
			'inline-save',
			'inline-save-tax',
			'find_posts',
			'widgets-order',
			'save-widget',
			'delete-inactive-widgets',
			'set-post-thumbnail',
			'date_format',
			'time_format',
			'wp-remove-post-lock',
			'dismiss-wp-pointer',
			'upload-attachment',
			'get-attachment',
			'query-attachments',
			'save-attachment',
			'save-attachment-compat',
			'send-link-to-editor',
			'send-attachment-to-editor',
			'save-attachment-order',
			'heartbeat',
			'get-revision-diffs',
			'save-user-color-scheme',
			'update-widget',
			'query-themes',
			'parse-embed',
			'set-attachment-thumbnail',
			'parse-media-shortcode',
			'destroy-sessions',
			'install-plugin',
			'update-plugin',
			'crop-image',
			'generate-password',
			'save-wporg-username',
			'delete-plugin',
			'search-plugins',
			'search-install-plugins',
			'activate-plugin',
			'update-theme',
			'delete-theme',
			'install-theme',
			'get-post-thumbnail-html',
			'get-community-events',
			'edit-theme-plugin-file',
			'wp-privacy-export-personal-data',
			'wp-privacy-erase-personal-data',
			'wcct_quick_view_html',
			'wcct_change_rule_type',
			'woocommerce_json_search_products',
			'woocommerce_json_search_products_and_variations',
			'woocommerce_add_attribute',
			'woocommerce_add_new_attribute',
			'woocommerce_save_attributes',
			'woocommerce_add_variation',
			'woocommerce_load_variations',
			'woocommerce_save_variations',
			'woocommerce_remove_variations',
			'woocommerce_link_all_variations',
			'woocommerce_bulk_edit_variations',
			'woocommerce_toggle_gateway_enabled',
			'woocommerce_get_order_details',
			'woocommerce_do_ajax_product_export',
			'woocommerce_do_ajax_product_import',
			'woocommerce_get_customer_details',
			'woocommerce_load_order_items',
			'woocommerce_add_coupon_discount',
			'woocommerce_remove_order_coupon',
			'woocommerce_add_order_fee',
			'woocommerce_add_order_shipping',
			'woocommerce_remove_order_item',
			'woocommerce_remove_order_tax',
			'woocommerce_calc_line_taxes',
			'woocommerce_save_order_items',
			'woocommerce_refund_line_items',
			'woocommerce_delete_refund',
			'woocommerce_add_order_item',
			'woocommerce_add_order_tax',
			'woocommerce_add_order_note',
			'woocommerce_delete_order_note',
			'woocommerce_grant_access_to_download',
			'woocommerce_revoke_access_to_download',
			'wfocu_add_new_funnel',
			'wfocu_add_offer',
			'wfocu_add_product',
			'wfocu_remove_product',
			'wfocu_save_funnel_steps',
			'wfocu_save_funnel_offer_products',
			'wfocu_save_funnel_offer_settings',
			'wfocu_product_search',
			'wfocu_page_search',
			'wfocu_update_offer',
			'wfocu_update_funnel',
			'wfocu_remove_offer_from_funnel',
			'wfocu_get_custom_page',
			'wfocu_save_rules_settings',
			'wfocu_update_template',
			'wfocu_save_funnel_settings',
			'wfocu_save_global_settings',
			'wfocu_preview_details',
			'wfocu_toggle_funnel_state',
			'wfocu_front_charge',
			'wfocu_front_offer_skipped',
			'wfocu_front_calculate_shipping',
			'wfocu_front_calculate_shipping_variation',
			'wfocu_front_register_views',
			'wfocu_front_offer_expired'
		);
		$restricted_actions = array_unique( $restricted_actions );

		return apply_filters( 'xlwcfg_get_restricted_action', $restricted_actions );
	}

}

if ( class_exists( 'XLWCFG_Core' ) ) {
	XLWCFG_Core::register( 'data', 'XLWCFG_Data' );
}