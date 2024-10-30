<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class XLWCFG_Common
 * Handles Common Functions For Admin as well as front end interface
 * @package Bonanza-Lite
 * @author XlPlugins
 */
class XLWCFG_Common {

	public static $is_force_debug = false;
	protected static $default;

	public static function init() {

		add_action( 'init', array( __CLASS__, 'register_post_status' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_free_gift_post_type' ) );

		/** Loading XL core */
		add_action( 'init', array( __CLASS__, 'xlwcfg_xl_init' ), 8 );

		/** Enable force logging */
		add_action( 'init', array( __CLASS__, 'check_query_params' ), 1 );

		add_action( 'admin_bar_menu', array( __CLASS__, 'toolbar_link_to_xlplugins' ), 999 );

		add_filter( 'woocommerce_json_search_found_products', array( __CLASS__, 'filter_variable_products_from_search' ), 99 );
	}

	public static function register_post_status() {

		register_post_status( XLWCFG_SHORT_SLUG . 'disabled', array(
			'label'                     => __( 'Disabled', 'bonanza-woocommerce-free-gifts-lite' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Disabled <span class="count">(%s)</span>', 'Disabled <span class="count">(%s)</span>', 'bonanza-woocommerce-free-gifts-lite' ),
		) );
	}

	public static function register_free_gift_post_type() {
		$menu_name = _x( XLWCFG_FULL_NAME, 'Admin menu name', 'bonanza-woocommerce-free-gifts-lite' );

		register_post_type( self::get_free_gift_cpt_slug(), apply_filters( 'xlwcfg_post_type_args', array(
			'labels'              => array(
				'name'               => __( 'Free Gift', 'bonanza-woocommerce-free-gifts-lite' ),
				'singular_name'      => __( 'Free Gift', 'bonanza-woocommerce-free-gifts-lite' ),
				'add_new'            => __( 'Add New', 'bonanza-woocommerce-free-gifts-lite' ),
				'add_new_item'       => __( 'Add New Gift', 'bonanza-woocommerce-free-gifts-lite' ),
				'edit'               => __( 'Edit', 'bonanza-woocommerce-free-gifts-lite' ),
				'edit_item'          => __( 'Edit a Gift', 'bonanza-woocommerce-free-gifts-lite' ),
				'new_item'           => __( 'New Gift', 'bonanza-woocommerce-free-gifts-lite' ),
				'view'               => __( 'View Gift', 'bonanza-woocommerce-free-gifts-lite' ),
				'view_item'          => __( 'View Gift', 'bonanza-woocommerce-free-gifts-lite' ),
				'search_items'       => __( 'Search Gift', 'bonanza-woocommerce-free-gifts-lite' ),
				'not_found'          => __( 'No Gift', 'bonanza-woocommerce-free-gifts-lite' ),
				'not_found_in_trash' => __( 'No Gift found in trash', 'bonanza-woocommerce-free-gifts-lite' ),
				'parent'             => __( 'Parent Gift', 'bonanza-woocommerce-free-gifts-lite' ),
				'menu_name'          => $menu_name,
			),
			'public'              => false,
			'show_ui'             => true,
			'capability_type'     => 'product',
			'map_meta_cap'        => true,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_in_menu'        => false,
			'hierarchical'        => false,
			'show_in_nav_menus'   => false,
			'rewrite'             => false,
			'query_var'           => true,
			'supports'            => array( 'title' ),
			'has_archive'         => false,
		) ) );
	}

	public static function xlwcfg_xl_init() {
		XL_Common::include_xl_core();
	}

	public static function check_query_params() {

		$force_debug = filter_input( INPUT_GET, 'xlwcfg_force_debug' );

		if ( $force_debug === 'yes' ) {
			self::$is_force_debug = true;
		}
	}

	public static function toolbar_link_to_xlplugins( $wp_admin_bar ) {

		if ( is_admin() ) {
			return;
		}
		if ( ! is_user_logged_in() || ! current_user_can( 'administrator' ) ) {
			return;
		}

		$upload_dir = wp_upload_dir();

		$base_url = $upload_dir['baseurl'] . '/' . 'bonanza-woocommerce-free-gifts-lite';

		$args = array(
			'id'    => 'xlwcfg_admin_page_node',
			'title' => 'XL Free Gifts',
			'href'  => admin_url( 'admin.php?page=wc-settings&tab=' . XLWCFG_Common::get_wc_settings_tab_slug() ),
			'meta'  => array( 'class' => 'xlwcfg_admin_page_node' ),
		);
		$wp_admin_bar->add_node( $args );
	}

	public static function filter_variable_products_from_search( $products ) {
		global $wpdb;
		if ( is_array( $products ) && count( $products ) > 0 ) {
			// phpcs:disable
			$product_keys = array_keys( $products );
			$query        = "SELECT DISTINCT `post_parent` FROM `$wpdb->posts` WHERE `ID` IN (" . implode( ',', $product_keys ) . ") AND `post_type` = 'product_variation' ORDER BY `ID` DESC";
			$result       = $wpdb->get_results( $query, ARRAY_A );
			$mod_products = $products;
			if ( is_array( $result ) && count( $result ) > 0 ) {
				$result_ids = array_map( function ( $val ) use ( $mod_products ) {
					return $val['post_parent'];
				}, $result );

				if ( is_array( $result_ids ) && count( $result_ids ) > 0 ) {
					foreach ( $result_ids as $id ) {
						if ( isset( $mod_products[ $id ] ) ) {
							unset( $mod_products[ $id ] );
						}
					}
				}

				return ( is_array( $mod_products ) ) ? $mod_products : array();
			}
			// phpcs:enable
		}

		return $products;
	}

	public static function get_free_gift_cpt_slug() {
		return 'xlwcfg_free_gift';
	}

	public static function get_wc_settings_tab_slug() {
		return 'xl-free-gifts';
	}

	public static function get_post_table_data( $trigger = 'all', $filters = array() ) {
		if ( $trigger == 'all' ) {
			$args = array(
				'post_type'        => self::get_free_gift_cpt_slug(),
				'post_status'      => array( 'publish', XLWCFG_SHORT_SLUG . 'disabled' ),
				'nopaging'         => true,
				'suppress_filters' => false,   //WPML Compatibility
				'orderby'          => 'menu_order',
				'order'            => 'ASC',
			);
		} else {
			$meta_q      = array();
			$post_status = '';

			if ( $trigger == 'deactivated' ) {
				$post_status = XLWCFG_SHORT_SLUG . 'disabled';
			}

			$args = array(
				'post_type'        => self::get_free_gift_cpt_slug(),
				'post_status'      => array( 'publish', XLWCFG_SHORT_SLUG . 'disabled' ),
				'nopaging'         => true,
				'suppress_filters' => false,   //WPML Compatibility
				'orderby'          => 'menu_order',
				'order'            => 'ASC',
			);

			if ( $post_status != '' ) {
				$args['post_status'] = $post_status;
			} else {
				$args['post_status'] = 'publish';
			}

			if ( is_array( $meta_q ) && count( $meta_q ) > 0 ) {
				$args['meta_query'] = $meta_q;
			}
		}

		$args = wp_parse_args( $filters, $args );

		$q = new WP_Query( $args );

		$found_posts = array();
		if ( $q->have_posts() ) {

			while ( $q->have_posts() ) {
				$q->the_post();
				global $post;

				/** Saving post data as cache & transient */
				self::set_post_data( get_the_ID(), $post );

				$status      = $post->post_status;
				$row_actions = array();

				$deactivation_url = wp_nonce_url( add_query_arg( 'page', 'wc-settings', add_query_arg( 'tab', self::get_wc_settings_tab_slug(), add_query_arg( 'action', 'xlwcfg-post-deactivate', add_query_arg( 'postid', get_the_ID(), add_query_arg( 'trigger', $trigger ) ), network_admin_url( 'admin.php' ) ) ) ), 'xlwcfg-post-deactivate' );

				if ( $status == XLWCFG_SHORT_SLUG . 'disabled' ) {

					$activation_url = wp_nonce_url( add_query_arg( 'page', 'wc-settings', add_query_arg( 'tab', self::get_wc_settings_tab_slug(), add_query_arg( 'action', 'xlwcfg-post-activate', add_query_arg( 'postid', get_the_ID(), add_query_arg( 'trigger', $trigger ) ), network_admin_url( 'admin.php' ) ) ) ), 'xlwcfg-post-activate' );

					$row_actions[] = array(
						'action' => 'activate',
						'text'   => __( 'Activate', 'bonanza-woocommerce-free-gifts-lite' ),
						'link'   => $activation_url,
						'attrs'  => '',
					);
				} else {
					$row_actions[] = array(
						'action' => 'edit',
						'text'   => __( 'Edit', 'bonanza-woocommerce-free-gifts-lite' ),
						'link'   => get_edit_post_link( get_the_ID() ),
						'attrs'  => '',
					);

					$row_actions[] = array(
						'action' => 'deactivate',
						'text'   => __( 'Deactivate', 'bonanza-woocommerce-free-gifts-lite' ),
						'link'   => $deactivation_url,
						'attrs'  => '',
					);
				}
				$row_actions[] = array(
					'action' => 'xlwcfg_duplicate',
					'text'   => __( 'Duplicate', 'bonanza-woocommerce-free-gifts-lite' ),
					'link'   => wp_nonce_url( add_query_arg( 'page', 'wc-settings', add_query_arg( 'tab', self::get_wc_settings_tab_slug(), add_query_arg( 'action', 'xlwcfg-duplicate', add_query_arg( 'postid', get_the_ID(), add_query_arg( 'trigger', $trigger ) ), network_admin_url( 'admin.php' ) ) ) ), 'xlwcfg-duplicate' ),
					'attrs'  => '',
				);
				$row_actions[] = array(
					'action' => 'delete',
					'text'   => __( 'Delete Permanently', 'bonanza-woocommerce-free-gifts-lite' ),
					'link'   => get_delete_post_link( get_the_ID(), '', true ),
					'attrs'  => '',
				);

				array_push( $found_posts, array(
					'id'          => get_the_ID(),
					'post_obj'    => $post,
					'status'      => $status,
					'row_actions' => $row_actions,
					'menu_order'  => $post->menu_order,
				) );
			}
		}

		return $found_posts;
	}


	/**
	 * Getting list of status in hierarchical order
	 * @return array
	 */
	public static function get_campaign_statuses() {
		return array(
			'active'      => array(
				'name'     => __( 'Active', 'bonanza-woocommerce-free-gifts-lite' ),
				'slug'     => 'active',
				'position' => 5,
			),
			'deactivated' => array(
				'name'     => __( 'Deactivated', 'bonanza-woocommerce-free-gifts-lite' ),
				'slug'     => 'deactivated',
				'position' => 9,
			),
		);
	}

	public static function xlwcfg_valid_admin_pages( $area = 'all' ) {
		$screen = get_current_screen();

		if ( ! is_object( $screen ) ) {
			return false;
		}

		if ( 'all' == $area && ( ( $screen->base == 'woocommerce_page_wc-settings' && isset( $_GET['tab'] ) && $_GET['tab'] == self::get_wc_settings_tab_slug() ) || ( $screen->base == 'post' && $screen->post_type == XLWCFG_Common::get_free_gift_cpt_slug() ) ) ) {
			return true;
		}

		if ( 'single' == $area && ( $screen->base == 'post' && $screen->post_type == XLWCFG_Common::get_free_gift_cpt_slug() ) ) {
			return true;
		}

		return apply_filters( 'xlwcfg_valid_admin_pages', false );
	}

	public static function get_post_meta_data( $item_id, $force = false ) {
		global $wpdb;

		$xl_cache_obj     = XL_Cache::get_instance();
		$xl_transient_obj = XL_Transient::get_instance();

		$cache_key = 'xlwcfg_gift_meta_' . $item_id;

		/** When force enabled */
		if ( true === $force ) {
			$post_meta                       = get_post_meta( $item_id );
			$post_meta                       = self::parsed_query_results( $post_meta );
			$get_product_xlwcfg_meta_default = self::parse_default_args( $post_meta );
			$parseObj                        = wp_parse_args( $post_meta, $get_product_xlwcfg_meta_default );
			$xl_transient_obj->set_transient( $cache_key, $parseObj, DAY_IN_SECONDS, 'free-gift' );
			$xl_cache_obj->set_cache( $cache_key, $parseObj, 'free-gift' );
		} else {
			/**
			 * Setting xl cache and transient for Free gift meta
			 */
			$cache_data = $xl_cache_obj->get_cache( $cache_key, 'free-gift' );
			if ( false !== $cache_data ) {
				$parseObj = $cache_data;
			} else {
				$transient_data = $xl_transient_obj->get_transient( $cache_key, 'free-gift' );

				if ( false !== $transient_data ) {
					$parseObj = $transient_data;
				} else {
					$post_meta                       = get_post_meta( $item_id );
					$post_meta                       = self::parsed_query_results( $post_meta );
					$get_product_xlwcfg_meta_default = self::parse_default_args( $post_meta );
					$parseObj                        = wp_parse_args( $post_meta, $get_product_xlwcfg_meta_default );
					$xl_transient_obj->set_transient( $cache_key, $parseObj, DAY_IN_SECONDS, 'free-gift' );
				}
				$xl_cache_obj->set_cache( $cache_key, $parseObj, 'free-gift' );
			}
		}

		$fields = array();
		if ( $parseObj && is_array( $parseObj ) && count( $parseObj ) > 0 ) {
			foreach ( $parseObj as $key => $val ) {
				$newKey = $key;
				if ( strpos( $key, '_xlwcfg_' ) !== false ) {
					$newKey = str_replace( '_xlwcfg_', '', $key );
				}
				$fields[ $newKey ] = $val;
			}
		}

		return $fields;
	}

	public static function get_post_data( $item_id, $force = false ) {
		$xl_cache_obj     = XL_Cache::get_instance();
		$xl_transient_obj = XL_Transient::get_instance();

		$cache_key = 'xlwcfg_gift_post_data_' . $item_id;

		/** When force enabled */
		if ( true === $force ) {
			$post_data = get_post( $item_id );
			self::set_post_data( $item_id, $post_data );
		} else {
			/**
			 * Setting xl cache and transient for Gift data
			 */
			$cache_data = $xl_cache_obj->get_cache( $cache_key, 'free-gift' );
			if ( false !== $cache_data ) {
				$post_data = $cache_data;
			} else {
				$transient_data = $xl_transient_obj->get_transient( $cache_key, 'free-gift' );

				if ( false !== $transient_data ) {
					$post_data = $transient_data;
				} else {
					$post_data = get_post( $item_id );
					$xl_transient_obj->set_transient( $cache_key, $post_data, DAY_IN_SECONDS, 'free-gift' );
				}
				self::set_post_data( $item_id, $post_data );
			}
		}

		return $post_data;
	}

	/**
	 * Save post data in cache & transient
	 *
	 * @param string $item_id
	 * @param string $post_data
	 */
	public static function set_post_data( $item_id = '', $post_data = '' ) {
		$xl_cache_obj     = XL_Cache::get_instance();
		$xl_transient_obj = XL_Transient::get_instance();

		if ( empty( $item_id ) || empty( $post_data ) ) {
			return;
		}

		$cache_key = 'xlwcfg_gift_post_data_' . $item_id;

		$xl_transient_obj->set_transient( $cache_key, $post_data, DAY_IN_SECONDS, 'free-gift' );
		$xl_cache_obj->set_cache( $cache_key, $post_data, 'free-gift' );
	}

	public static function parsed_query_results( $results ) {
		$parsed_results = array();

		if ( is_array( $results ) && count( $results ) > 0 ) {
			foreach ( $results as $key => $result ) {
				$parsed_results[ $key ] = maybe_unserialize( $result['0'] );
			}
		}

		return $parsed_results;
	}

	public static function parse_default_args( $data ) {
		$field_option_data = self::get_default_settings();
		foreach ( $field_option_data as $slug => $value ) {
			$data[ $slug ] = $value;
		}

		return $data;
	}

	public static function get_default_settings() {
		self::$default = apply_filters( 'xlwcfg_gift_settings_default', array(
			'_xlwcfg_schedule'                => 'forever',
			'_xlwcfg_gift_qty_buy'            => '1',
			'_xlwcfg_gift_qty_get'            => '1',
			'_xlwcfg_repeat'                  => 'yes',
			'_xlwcfg_offer_mode'              => 'free',
			'_xlwcfg_enable_single_pro'       => 'no',
			'_xlwcfg_single_pro_position'     => '5',
			'_xlwcfg_single_pro_bg_color'     => '#444444',
			'_xlwcfg_single_pro_text_color'   => '#ffffff',
			'_xlwcfg_single_pro_fs'           => '16',
			'_xlwcfg_single_pro_padding_t'    => '10',
			'_xlwcfg_single_pro_padding_l'    => '10',
			'_xlwcfg_single_pro_border_style' => 'none',
			'_xlwcfg_single_pro_border_width' => '1',
			'_xlwcfg_single_pro_border_color' => '#dddddd',
		) );

		return self::$default;
	}

	public static function pr( $arr ) {
		echo '<pre>';
		print_r( $arr );
		echo '</pre>';
	}

	public static function get_filter_args() {
		$args = array();

		$args = apply_filters( 'xlwcfg_default_filter_args_campaigns_admin', $args );
		if ( null !== filter_input( INPUT_GET, 'xlwcfg_sort' ) ) {

			$orderby = filter_input( INPUT_GET, 'xlwcfg_sort' );
			switch ( $orderby ) {
				case 'date':
					$args['order'] = 'DESC';
					break;
				default:
					$args['order'] = 'ASC';

			}
			$args['orderby'] = filter_input( INPUT_GET, 'xlwcfg_sort' );
		}

		return $args;
	}

	/**
	 * Get all Free Gifts posts
	 *
	 * @param bool $force
	 * @param string $count
	 *
	 * @return bool|mixed|WP_Query
	 */
	public static function get_gift_posts( $force = false, $count = '-1' ) {
		$xl_cache_obj     = XL_Cache::get_instance();
		$xl_transient_obj = XL_Transient::get_instance();

		/** $force = true */
		if ( true === $force ) {
			$cache_key = 'xlwcfg_gift_posts';
			$args      = array(
				'post_type'        => self::get_free_gift_cpt_slug(),
				'post_status'      => array( 'publish' ),
				'nopaging'         => true,
				'suppress_filters' => false,   //WPML Compatibility
				'orderby'          => 'menu_order',
				'order'            => 'ASC',
				'showposts'        => $count,
			);

			$q = new WP_Query( $args );

			$gifts_post_query = $q;
			$xl_transient_obj->set_transient( $cache_key, $gifts_post_query, DAY_IN_SECONDS, 'free-gift' );
			$xl_cache_obj->set_cache( $cache_key, $gifts_post_query, 'free-gift' );

			return $gifts_post_query;
		}

		$cache_key  = 'xlwcfg_gift_posts';
		$cache_data = $xl_cache_obj->get_cache( $cache_key, 'free-gift' );
		if ( false !== $cache_data ) {
			$gifts_post_query = $cache_data;
		} else {
			$transient_data = $xl_transient_obj->get_transient( $cache_key, 'free-gift' );

			if ( false !== $transient_data ) {
				$gifts_post_query = $transient_data;
			} else {

				$args = array(
					'post_type'        => self::get_free_gift_cpt_slug(),
					'post_status'      => array( 'publish' ),
					'nopaging'         => true,
					'suppress_filters' => false,   //WPML Compatibility
					'orderby'          => 'menu_order',
					'order'            => 'ASC',
					'showposts'        => '-1',
				);

				$q = new WP_Query( $args );

				$gifts_post_query = $q;
				$xl_transient_obj->set_transient( $cache_key, $gifts_post_query, DAY_IN_SECONDS, 'free-gift' );
			}
			$xl_cache_obj->set_cache( $cache_key, $gifts_post_query, 'free-gift' );
		}

		return $gifts_post_query;
	}

	/**
	 * Get gift schedule status
	 *
	 * @param $item_id
	 *
	 * @return array
	 */
	public static function gift_schedule_status( $item_id ) {
		$output = '';

		$data = self::get_post_meta_data( $item_id );

		$todayDate = time();

		$start_date_timestamp = $todayDate - ( DAY_IN_SECONDS * 10 );
		$end_date_timestamp   = $todayDate + ( DAY_IN_SECONDS * 10 );

		$timings = array(
			'todayDate'            => (int) $todayDate,
			'start_date_timestamp' => (int) $start_date_timestamp,
			'end_date_timestamp'   => (int) $end_date_timestamp,
		);

		$slug_timing = '';

		/** Checking is post inactive */
		$item_data = self::get_post_data( $item_id );
		$status    = $item_data->post_status;

		if ( XLWCFG_SHORT_SLUG . 'disabled' == $status ) {
			$output      = __( 'Deactivated', 'bonanza-woocommerce-free-gifts-lite' );
			$slug_timing = 'inactive';
		} elseif ( $timings['start_date_timestamp'] > 0 && $timings['end_date_timestamp'] > 0 ) {
			if ( $timings['todayDate'] >= $timings['start_date_timestamp'] && $timings['todayDate'] < $timings['end_date_timestamp'] ) {
				$output      = __( 'Running', 'bonanza-woocommerce-free-gifts-lite' );
				$slug_timing = 'running';
			} elseif ( $timings['todayDate'] > $timings['end_date_timestamp'] ) {
				$output      = __( 'Finished', 'bonanza-woocommerce-free-gifts-lite' );
				$slug_timing = 'finished';
			} elseif ( $timings['start_date_timestamp'] > $timings['todayDate'] ) {
				$output      = __( 'Scheduled', 'bonanza-woocommerce-free-gifts-lite' );
				$slug_timing = 'schedule';
			}
		}

		$return = array(
			'html'   => $output,
			'slug'   => $slug_timing,
			'status' => ( $slug_timing == 'running' ) ? true : false,
		);

		return $return;
	}

	public static function check_gift_get_product_status( $item_id ) {
		$status = false;

		$data = self::get_post_meta_data( $item_id );

		/** If no data set */
		if ( ! is_array( $data ) ) {
			return $status;
		}

		if ( isset( $data['get_products'] ) && is_array( $data['get_products'] ) && count( $data['get_products'] ) > 0 ) {
			$status = true;
		}

		return $status;
	}

	/**
	 * Validate Gift get products stock status
	 *
	 * @param int $item_id gift id
	 *
	 * @return bool
	 */
	public static function gift_get_products_valid( $item_id ) {

		$data = self::get_post_meta_data( $item_id );

		/** If no data set */
		if ( ! is_array( $data ) ) {
			return false;
		}

		/** No get products set */
		if ( ! isset( $data['get_products'] ) || ! is_array( $data['get_products'] ) || count( $data['get_products'] ) == 0 ) {
			return false;
		}

		$status = false;
		foreach ( $data['get_products'] as $id ) {
			$product_status = get_post_meta( $id, '_stock_status', true );
			if ( in_array( $product_status, array( 'instock', 'onbackorder' ) ) ) {
				$status = true;
				break;
			}
		}

		return $status;
	}

	public static function debug( $string, $param2 = '' ) {
		if ( class_exists( 'PC' ) && method_exists( 'PC', 'debug' ) && ( true == apply_filters( 'xlwcfg_pc_debug', false ) ) ) {
			pc::debug( $string, $param2 );
		}
	}

	public static function get_product_valid_gifts( $product_id ) {
		/** Get all gifts */
		$all_gifts = XLWCFG_Common::get_gift_posts();

		if ( false === $all_gifts->have_posts() || ! isset( $all_gifts->posts ) || ! is_array( $all_gifts->posts ) ) {
			return;
		}
		$product_gifts = array();
		foreach ( $all_gifts->posts as $gift ) {
			do_action( 'xlwcfg_before_matching_rules_indexing' );
			XLWCFG_Core()->rules->env_product = $product_id;

			$result = XLWCFG_Core()->rules->match_groups( $gift->ID );
			XLWCFG_Common::debug( $result, 'product id ' . $product_id . ' - gift id ' . $gift->ID . ' status' );
			if ( true === $result ) {
				$product_gifts[] = $gift->ID;
			}
			XLWCFG_Core()->rules->env_product = '';
			do_action( 'xlwcfg_after_matching_rules_indexing' );
		}

		return $product_gifts;
	}

	/**
	 * Validate if Gift buy products in stock
	 *
	 * @param int $item_id gift id
	 *
	 * @return bool
	 */
	public static function gift_buy_products_in_stock( $item_id ) {

		$data = self::get_post_meta_data( $item_id );
		global $product;

		/** If no data set */
		if ( ! is_array( $data ) || ! isset( $data['variations'] ) ) {
			return true;
		}

		/** Product not available */
		if ( ! $product instanceof WC_Product || ! array_search( $product->get_id(), $data['variations'] ) ) {
			return true;
		}

		$variation = array_keys( $data['variations'], $product->get_id() );
		if ( is_array( $variation ) && count( $variation ) > 0 ) {
			$status = false;
			foreach ( $variation as $id ) {
				$product_status = get_post_meta( $id, '_stock_status', true );
				if ( in_array( $product_status, array( 'instock', 'onbackorder' ) ) ) {
					$status = true;
					break;
				}
			}

			return $status;
		}

		return true;
	}

	/**
	 * Function to get timezone string by checking WordPress timezone settings
	 * @return mixed|string|void
	 */
	public static function wc_timezone_string() {

		// if site timezone string exists, return it
		if ( $timezone = get_option( 'timezone_string' ) ) {
			return $timezone;
		}

		// get UTC offset, if it isn't set then return UTC
		if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) ) {
			return 'UTC';
		}

		// get timezone using offset manual
		return self::get_timezone_by_offset( $utc_offset );
	}

	/**
	 * Function to get timezone string based on specified offset
	 *
	 * @param $offset
	 *
	 * @return string
	 *
	 */
	public static function get_timezone_by_offset( $offset ) {
		switch ( $offset ) {
			case '-12':
				return 'GMT-12';
				break;
			case '-11.5':
				return 'Pacific/Niue'; // 30 mins wrong
				break;
			case '-11':
				return 'Pacific/Niue';
				break;
			case '-10.5':
				return 'Pacific/Honolulu'; // 30 mins wrong
				break;
			case '-10':
				return 'Pacific/Tahiti';
				break;
			case '-9.5':
				return 'Pacific/Marquesas';
				break;
			case '-9':
				return 'Pacific/Gambier';
				break;
			case '-8.5':
				return 'Pacific/Pitcairn'; // 30 mins wrong
				break;
			case '-8':
				return 'Pacific/Pitcairn';
				break;
			case '-7.5':
				return 'America/Hermosillo'; // 30 mins wrong
				break;
			case '-7':
				return 'America/Hermosillo';
				break;
			case '-6.5':
				return 'America/Belize'; // 30 mins wrong
				break;
			case '-6':
				return 'America/Belize';
				break;
			case '-5.5':
				return 'America/Belize'; // 30 mins wrong
				break;
			case '-5':
				return 'America/Panama';
				break;
			case '-4.5':
				return 'America/Lower_Princes'; // 30 mins wrong
				break;
			case '-4':
				return 'America/Curacao';
				break;
			case '-3.5':
				return 'America/Paramaribo'; // 30 mins wrong
				break;
			case '-3':
				return 'America/Recife';
				break;
			case '-2.5':
				return 'America/St_Johns';
				break;
			case '-2':
				return 'America/Noronha';
				break;
			case '-1.5':
				return 'Atlantic/Cape_Verde'; // 30 mins wrong
				break;
			case '-1':
				return 'Atlantic/Cape_Verde';
				break;
			case '+1':
				return 'Africa/Luanda';
				break;
			case '+1.5':
				return 'Africa/Mbabane'; // 30 mins wrong
				break;
			case '+2':
				return 'Africa/Harare';
				break;
			case '+2.5':
				return 'Indian/Comoro'; // 30 mins wrong
				break;
			case '+3':
				return 'Asia/Baghdad';
				break;
			case '+3.5':
				return 'Indian/Mauritius'; // 30 mins wrong
				break;
			case '+4':
				return 'Indian/Mauritius';
				break;
			case '+4.5':
				return 'Asia/Kabul';
				break;
			case '+5':
				return 'Indian/Maldives';
				break;
			case '+5.5':
				return 'Asia/Kolkata';
				break;
			case '+5.75':
				return 'Asia/Kathmandu';
				break;
			case '+6':
				return 'Asia/Urumqi';
				break;
			case '+6.5':
				return 'Asia/Yangon';
				break;
			case '+7':
				return 'Antarctica/Davis';
				break;
			case '+7.5':
				return 'Asia/Jakarta'; // 30 mins wrong
				break;
			case '+8':
				return 'Asia/Manila';
				break;
			case '+8.5':
				return 'Asia/Pyongyang';
				break;
			case '+8.75':
				return 'Australia/Eucla';
				break;
			case '+9':
				return 'Asia/Tokyo';
				break;
			case '+9.5':
				return 'Australia/Darwin';
				break;
			case '+10':
				return 'Australia/Brisbane';
				break;
			case '+10.5':
				return 'Australia/Lord_Howe';
				break;
			case '+11':
				return 'Antarctica/Casey';
				break;
			case '+11.5':
				return 'Pacific/Auckland'; // 30 mins wrong
				break;
			case '+12':
				return 'Pacific/Wallis';
				break;
			case '+12.75':
				return 'Pacific/Chatham';
				break;
			case '+13':
				return 'Pacific/Fakaofo';
				break;
			case '+13.75':
				return 'Pacific/Chatham'; // 1 hr wrong
				break;
			case '+14':
				return 'Pacific/Kiritimati';
				break;
			default:
				return 'UTC';
				break;
		}
	}


}
