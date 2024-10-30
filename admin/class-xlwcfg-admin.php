<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class XLWCFG_Admin
 * @package Bonanza-Lite
 * @author XlPlugins
 */
class XLWCFG_Admin {

	protected static $instance = null;
	protected static $default;
	public $admin_dir;

	public function __construct() {
		$this->admin_dir = XLWCFG_PLUGIN_DIR . 'admin/';
		$this->admin_url = plugin_dir_url( XLWCFG_PLUGIN_FILE ) . 'admin/';

		$this->includes();
		$this->hooks();
	}

	/**
	 * Include files
	 */
	public function includes() {
		/**
		 * Loading dependencies
		 */
		include_once $this->admin_dir . 'includes/cmb2/init.php';
		include_once $this->admin_dir . 'includes/cmb2-addons/conditional/cmb2-conditionals.php';

		include_once $this->admin_dir . 'includes/class-xlwcfg-admin-meta-boxes.php';

		include_once $this->admin_dir . 'includes/xlwcfg-admin-cmb2-support.php';
	}

	public function hooks() {
		/** Adding meta boxes */
		add_filter( 'cmb2_init', array( $this, 'xlwcfg_metabox_fields' ), 11 );
		add_action( 'add_meta_boxes', array( $this, 'xlwcfg_rules_metabox_fields' ) );

		add_filter( 'cmb2_init', array( $this, 'xlwcfg_add_cmb2_post_select' ) );

		add_action( 'cmb2_render_xlwcfg_post_select', array( $this, 'xlwcfg_post_select' ), 10, 5 );

		/** Remove plugin update transient */
		add_action( 'admin_init', array( $this, 'xlwcfg_remove_plugin_update_transient' ), 10 );

		/** Update Gift cpt metabox order */
		add_action( 'admin_head', array( $this, 'update_gift_metabox_order' ), 10 );

		/** Duplicate a Gift */
		add_action( 'admin_init', array( $this, 'maybe_duplicate_post' ) );

		/** Loading js and css */
		add_action( 'admin_enqueue_scripts', array( $this, 'xlwcfg_enqueue_admin_assets' ), 20 );

		/** Allowing conditionals to work on custom page */
		add_filter( 'xl_cmb2_add_conditional_script_page', array( 'XLWCFG_Admin_CMB2_Support', 'xlwcfg_push_support_form_cmb_conditionals' ) );

		/** Handle tabs ordering */
		add_filter( 'xlwcfg_cmb2_modify_field_tabs', array( $this, 'xlwcfg_admin_reorder_tabs' ), 99 );

		/**  Adds HTML field to cmb2 config */
		add_filter( 'cmb2_render_xlwcfg_html_content_field', array( $this, 'xlwcfg_html_content_fields' ), 10, 5 );

		/** Keeping meta box open */
		add_filter( 'postbox_classes_product_xlwcfg_product_option_tabs', array( $this, 'xlwcfg_metabox_always_open' ) );

		/** Pushing Deactivation For XL Core */
		add_filter( 'plugin_action_links_' . XLWCFG_PLUGIN_BASENAME, array( $this, 'xlwcfg_plugin_actions' ) );
		add_filter( 'plugin_row_meta', array( $this, 'xlwcfg_plugin_row_actions' ), 10, 2 );

		/** Adding New Tab in WooCommerce Settings API */
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'modify_woocommerce_settings' ), 99 );

		/** Adding Customer HTML On setting page for WooCommerce */
		add_action( 'woocommerce_settings_' . XLWCFG_Common::get_wc_settings_tab_slug(), array( $this, 'xlwcfg_woocommerce_options_page' ) );

		/** Adding `Return To` Notice Out Post Pages */
		add_action( 'edit_form_top', array( $this, 'xlwcfg_edit_form_top' ) );

		/** Adding optgroup to trigger selects */
		add_filter( 'cmb2_select_attributes', array( 'XLWCFG_Admin_CMB2_Support', 'cmb_opt_groups' ), 10, 4 );

		/** Modifying Post update messages */
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );

		/** Hooks to check if activation and deactivation request for post.*/
		add_action( 'admin_init', array( $this, 'maybe_activate_post' ) );
		add_action( 'admin_init', array( $this, 'maybe_deactivate_post' ) );
		add_action( 'admin_init', array( $this, 'maybe_duplicate_post' ) );

		/** CMB2 after save metadata */
		add_action( 'cmb2_save_post_fields_xlwcfg_buy_box_settings', array( $this, 'after_cmb2_form_data_saved' ), 1000 );

		add_action( 'admin_footer', array( $this, 'xlwcfg_footer_css' ), 20 );

		add_action( 'delete_post', array( $this, 'clear_transients_on_delete' ), 10 );
		add_action( 'post_updated', array( $this, 'restrict_to_publish_when_post_is_disabled' ), 10, 3 );

		/*Admin Notices*/
		add_filter( 'admin_notices', array( $this, 'maybe_show_advanced_update_notification' ), 999 );
	}

	/**
	 * Return an instance of this class.
	 * @return    object    A single instance of this class.
	 * @since     1.0.0
	 */
	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Sorter function to sort array by internal key called priority
	 *
	 * @param type $a
	 * @param type $b
	 *
	 * @return int
	 */
	public static function _sort_by_priority( $a, $b ) {
		if ( $a['position'] == $b['position'] ) {
			return 0;
		}

		return ( $a['position'] < $b['position'] ) ? - 1 : 1;
	}

	public function xlwcfg_metabox_fields() {
		XLWCFG_Admin_Meta_Boxes::prepare_default_config();
		XLWCFG_Admin_Meta_Boxes::metabox_offer_fields();
		XLWCFG_Admin_Meta_Boxes::metabox_get_block_fields();
		XLWCFG_Admin_Meta_Boxes::metabox_schedule_fields();
		XLWCFG_Admin_Meta_Boxes::metabox_conditions_fields();
	}

	public function xlwcfg_rules_metabox_fields() {
		add_meta_box( 'xlwcfg_rule_buy_block_settings', __( 'Customer Buys', 'bonanza-woocommerce-free-gifts-lite' ), array(
			$this,
			'xlwcfg_rules_buy_metabox_callback'
		), XLWCFG_Common::get_free_gift_cpt_slug() );

		add_meta_box( 'xlwcfg_rule_basic_block_settings', __( 'Additional Rules', 'bonanza-woocommerce-free-gifts-lite' ), array(
			$this,
			'xlwcfg_rules_basic_metabox_callback'
		), XLWCFG_Common::get_free_gift_cpt_slug() );
	}

	public function xlwcfg_rules_buy_metabox_callback() {
		echo XLWCFG_Core()->rules->render_product_rules();
	}

	public function xlwcfg_rules_basic_metabox_callback() {
		echo XLWCFG_Core()->rules->render_basic_rules();
	}

	/**
	 * Render options for woocommerce custom option page
	 */
	public function xlwcfg_woocommerce_options_page() {
		if ( 'blank' === get_option( 'xlp_is_opted', 'blank' ) ) {
			include_once( $this->admin_dir . 'views/optin-temp.php' );

			return;
		} elseif ( filter_input( INPUT_GET, 'section' ) === 'settings' ) {
			?>
            <div class="notice">
                <p><?php _e( 'Back to <a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . XLWCFG_Common::get_wc_settings_tab_slug() . '' ) . '">' . XLWCFG_FULL_NAME . '</a> listing.', 'bonanza-woocommerce-free-gifts-lite' ); ?></p>
            </div>
            <div class="wrap xlwcfg_global_option">
                <h1 class="wp-heading-inline"><?php echo __( 'Settings', 'bonanza-woocommerce-free-gifts-lite' ); ?></h1>
                <div id="poststuff">
                    <div class="inside">
                        <div class="xlwcfg_options_page_col2_wrap">
                            <div class="xlwcfg_options_page_left_wrap">
                                <div class="postbox">
                                    <div class="inside">
                                        <div class="xlwcfg_options_common xlwcfg_options_settings">
                                            <div class="xlwcfg_h20"></div>
											<?php cmb2_metabox_form( 'xlwcfg_global_settings', 'xlwcfg_global_options' ); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="xlwcfg_options_page_right_wrap">
								<?php do_action( 'xlwcfg_options_page_right_content' ); ?>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
			<?php
		} else {
			require_once( $this->admin_dir . 'includes/class-xlwcfg-post-table.php' );

			$listing_sections = array(
				'null'        => null,
				'all'         => 'all',
				'active'      => 'active',
				'deactivated' => 'deactivated',
			);

			$sections = apply_filters( 'xlwcfg_section_pages', array() );
			?>

            <style>body {
                    position: relative;
                    height: auto
                }</style>
            <div class="wrap cmb2-options-page xlwcfg_global_option">
				<?php
				$addon_found = false;
				if ( is_array( $sections ) && count( $sections ) > 0 ) {
					foreach ( $sections as $key => $pages ) {
						if ( filter_input( INPUT_GET, 'section' ) == $key && filter_input( INPUT_GET, 'tab' ) == XLWCFG_Common::get_wc_settings_tab_slug() ) {
							$addon_found = true;
							do_action( 'xlwcfg_add_on_setting-' . $pages );
							break;
						}
					}
				}

				if ( ! $addon_found && in_array( filter_input( INPUT_GET, 'section' ), $listing_sections ) ) {
					$this->admin_page();
				}
				?>
            </div>
			<?php
		}
	}

	public function admin_page() {
		?>
        <h1 class="wp-heading-inline">Free Gifts</h1>
		<?php
		$tabs = array(
			array(
				'title' => __( 'Add New Gift', 'bonanza-woocommerce-free-gifts-lite' ),
				'link'  => admin_url( 'post-new.php?post_type=' . XLWCFG_Common::get_free_gift_cpt_slug() ),
			),
		);
		$tabs = array_merge( $tabs, apply_filters( 'xlwcfg_gifts_listing_buttons', array() ) );
		if ( is_array( $tabs ) && count( $tabs ) > 0 ) {
			foreach ( $tabs as $key => $val ) {
				?>
                <a href="<?php echo $val['link']; ?>" class="page-title-action <?php echo isset( $val['class'] ) ? $val['class'] : ''; ?>"><?php echo $val['title']; ?></a>
				<?php
			}
		}
		?>
        <br/>
        <br/>
		<?php XLWCFG_Admin_CMB2_Support::render_trigger_nav(); ?>
        <div id="poststuff">
            <div class="inside">
                <div class="inside">
                    <div class="xlwcfg_options_page_col2_wrap">
                        <div class="xlwcfg_options_page_left_wrap">
							<?php
							do_action( 'xlwcfg_before_post_table' );
							add_filter( 'xlwcfg_default_filter_args_campaigns_admin', array( $this, 'default_orderby_date' ) );
							$table = new XLWCFG_Post_Table();

							$table->data = XLWCFG_Common::get_post_table_data( XLWCFG_Admin_CMB2_Support::get_current_trigger(), XLWCFG_Common::get_filter_args() );
							$table->prepare_items();
							$table->display();
							do_action( 'xlwcfg_after_post_table' );
							?>
                        </div>
                        <div class="xlwcfg_options_page_right_wrap">
							<?php do_action( 'xlwcfg_options_page_right_content' ); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}

	/**
	 * Hooked over `admin_enqueue_scripts`
	 * Enqueue scripts and css to wp-admin
	 */
	public function xlwcfg_enqueue_admin_assets() {
		$screen = get_current_screen();
		if ( XLWCFG_Common::xlwcfg_valid_admin_pages() ) {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'jquery' );

			wp_enqueue_style( 'xl-confirm-css', $this->admin_url . 'assets/css/jquery-confirm.min.css', XLWCFG_VER );
			wp_enqueue_style( 'xlwcfg-admin-css', $this->admin_url . 'assets/css/xlwcfg-admin.css', false, XLWCFG_VER );
			wp_enqueue_style( 'xl-chosen-css', $this->admin_url . 'assets/css/chosen' . $suffix . '.css', false, XLWCFG_VER );
			wp_register_script( 'xl-chosen', $this->admin_url . 'assets/js/chosen/chosen.jquery.min.js', array( 'jquery' ), XLWCFG_VER );
			wp_register_script( 'xl-ajax-chosen', $this->admin_url . 'assets/js/chosen/ajax-chosen.jquery.min.js', array( 'jquery', 'xl-chosen' ), XLWCFG_VER );
			wp_enqueue_script( 'xl-ajax-chosen' );
			wp_enqueue_script( 'xl-confirm-js', $this->admin_url . 'assets/js/jquery-confirm.min.js', XLWCFG_VER );
			wp_enqueue_script( 'xlwcfg_admin-js', $this->admin_url . 'assets/js/xlwcfg-admin.js', array( 'jquery', 'cmb2-scripts', 'xlwcfg-cmb2-conditionals' ), XLWCFG_VER, true );

			$data = array(
				'ajax_nonce'            => wp_create_nonce( 'xlwcfgaction-admin' ),
				'plugin_url'            => plugin_dir_url( XLWCFG_PLUGIN_FILE ),
				'ajax_url'              => admin_url( 'admin-ajax.php' ),
				'ajax_chosen'           => wp_create_nonce( 'json-search' ),
				'search_products_nonce' => wp_create_nonce( 'search-products' ),
				'text_or'               => __( 'or', 'bonanza-woocommerce-free-gifts-lite' ),
				'text_apply_when'       => __( 'Apply this Campaign when these conditions are matched', 'bonanza-woocommerce-free-gifts-lite' ),
				'remove_text'           => __( 'Remove', 'bonanza-woocommerce-free-gifts-lite' ),
				'admin_url'             => admin_url( 'post.php' ),
			);
			wp_localize_script( 'xlwcfg_admin-js', 'XLWCFGParams', $data );
			$go_pro_link = add_query_arg( array(
				'utm_source'   => 'bonanza-lite',
				'utm_medium'   => 'modals-click',
				'utm_campaign' => 'optin-modals',
				'utm_term'     => 'go-pro-{current_slug}',
			), 'https://xlplugins.com/woocommerce-free-gifts-bonanza/' );
			wp_localize_script( 'xlwcfg_admin-js', 'buy_pro_helper', array(
				'buy_now_link'        => $go_pro_link,
				'call_to_action_text' => __( 'Upgrade To PRO', 'bonanza-woocommerce-free-gifts-lite' ) . ' &nbsp;<i class="dashicons dashicons-arrow-right-alt"></i>',
				'popups'              => array(
					'get_mode_same'          => array(
						'title'   => __( "Same As Buy Product is a PRO Feature", 'bonanza-woocommerce-free-gifts-lite' ),
						'content' => '<div class="hcontent h_noImg h_align_center">' . __( "This feature allows you to set Free products which are same as Buy products.<br> It saves you time in configuration. <br><span class='h_align_center'>Want it on your store?</span>", 'bonanza-woocommerce-free-gifts-lite' ) . '</div>',
					),
					'schedule_fixed'         => array(
						'title'   => __( "Fixed Schedule is a PRO Feature", 'bonanza-woocommerce-free-gifts-lite' ),
						'content' => '<div class="himage"><img src="' . plugin_dir_url( XLWCFG_PLUGIN_FILE ) . 'admin/assets/img/modules/schedule-fixed.jpg" /></div><div class="hcontent">' . __( "You can schedule your Free Gift Campaigns by specifying a Start and End Date. This induces scarcity that offer is valid for limited time. <br> Want it on your store?", 'bonanza-woocommerce-free-gifts-lite' ) . '</div>',
					),
					'product_rules_modal'    => array(
						'title'   => __( "Product Category(s), Product Tag(s) Rules are a PRO Feature", 'bonanza-woocommerce-free-gifts-lite' ),
						'content' => '<div class="hcontent h_noImg h_list">' . __( "<ul><li><i class='dashicons dashicons-yes'></i>Create Campaigns based on Product Category or Tags.</li><li><i class='dashicons dashicons-yes'></i>Run campaigns such as Buy X from Category A and Get Y.</li></ul>
<span class='h_align_center'>Want it on your store?</span>", 'bonanza-woocommerce-free-gifts-lite' ) . '</div>',
					),
					'additional_rules_modal' => array(
						'title'   => __( "Cart Payment Gateway, Cart Coupon, Custom Order Count Rules are a PRO Feature", 'bonanza-woocommerce-free-gifts-lite' ),
						'content' => '<div class="hcontent h_noImg h_list">' . __( "<ul><li><i class='dashicons dashicons-yes'></i>Motivate buyers to use a particular gateway</li><li>
 <i class='dashicons dashicons-yes'></i>Give away a free item based on Specific coupon code </li><li><i class='dashicons dashicons-yes'></i>Surprise your repeat buyers by giving free item based on order count</li></ul>", 'bonanza-woocommerce-free-gifts-lite' ) . '</div>',
					),
				)
			) );

		}
	}

	/**
	 * Hooked over `admin_enqueue_scripts`
	 * Force remove Plugin update transient
	 */
	public function xlwcfg_remove_plugin_update_transient() {
		if ( isset( $_GET['remove_update_transient'] ) && $_GET['remove_update_transient'] == '1' ) {
			delete_option( '_site_transient_update_plugins' );
		}
	}

	/**
	 * Update Gift cpt metabox order
	 */
	public function update_gift_metabox_order() {
		if ( true === XLWCFG_Common::xlwcfg_valid_admin_pages( 'single' ) ) {
			$current_user = wp_get_current_user();
			if ( $current_user instanceof WP_User ) {
				$user_session_order = get_option( '_xlwcfg_metabox_updated_' . $current_user->ID, '' );
				if ( empty( $user_session_order ) ) {
					$key  = 'meta-box-order_' . XLWCFG_Common::get_free_gift_cpt_slug();
					$meta = get_user_meta( $current_user->ID, $key, true );

					$meta['normal'] = 'xlwcfg_buy_box_settings,xlwcfg_rule_buy_block_settings,xlwcfg_get_block_settings,xlwcfg_rule_basic_block_settings,xlwcfg_schedule_box_settings,xlwcfg_conditions_block_settings,slugdiv';
					update_user_meta( $current_user->ID, $key, $meta );

					update_option( '_xlwcfg_metabox_updated_' . $current_user->ID, 'yes', true );
				}
			}
		}
	}

	/**
	 * Duplicate a post (i.e. gift)
	 */
	public function maybe_duplicate_post() {
		global $wpdb;
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'xlwcfg-duplicate' ) {

			if ( wp_verify_nonce( $_GET['_wpnonce'], 'xlwcfg-duplicate' ) ) {

				$original_id = filter_input( INPUT_GET, 'postid' );
				$section     = filter_input( INPUT_GET, 'trigger' );
				if ( $original_id ) {

					// Get the post as an array
					$duplicate = get_post( $original_id, 'ARRAY_A' );

					$settings = $defaults = array(
						'status'                => XLWCFG_SHORT_SLUG . 'disabled',
						'type'                  => 'same',
						'timestamp'             => 'current',
						'title'                 => __( 'Copy', 'post-duplicator' ),
						'slug'                  => 'copy',
						'time_offset'           => false,
						'time_offset_days'      => 0,
						'time_offset_hours'     => 0,
						'time_offset_minutes'   => 0,
						'time_offset_seconds'   => 0,
						'time_offset_direction' => 'newer',
					);

					// Modify some of the elements
					$appended                = ( $settings['title'] != '' ) ? ' ' . $settings['title'] : '';
					$duplicate['post_title'] = $duplicate['post_title'] . ' ' . $appended;
					$duplicate['post_name']  = sanitize_title( $duplicate['post_name'] . '-' . $settings['slug'] );

					// Set the status
					if ( $settings['status'] != 'same' ) {
						$duplicate['post_status'] = $settings['status'];
					}

					// Set the type
					if ( $settings['type'] != 'same' ) {
						$duplicate['post_type'] = $settings['type'];
					}

					// Set the post date
					$timestamp     = ( $settings['timestamp'] == 'duplicate' ) ? strtotime( $duplicate['post_date'] ) : current_time( 'timestamp', 0 );
					$timestamp_gmt = ( $settings['timestamp'] == 'duplicate' ) ? strtotime( $duplicate['post_date_gmt'] ) : current_time( 'timestamp', 1 );

					if ( $settings['time_offset'] ) {
						$offset = intval( $settings['time_offset_seconds'] + $settings['time_offset_minutes'] * 60 + $settings['time_offset_hours'] * 3600 + $settings['time_offset_days'] * 86400 );
						if ( $settings['time_offset_direction'] == 'newer' ) {
							$timestamp     = intval( $timestamp + $offset );
							$timestamp_gmt = intval( $timestamp_gmt + $offset );
						} else {
							$timestamp     = intval( $timestamp - $offset );
							$timestamp_gmt = intval( $timestamp_gmt - $offset );
						}
					}
					$duplicate['post_date']         = date( 'Y-m-d H:i:s', $timestamp );
					$duplicate['post_date_gmt']     = date( 'Y-m-d H:i:s', $timestamp_gmt );
					$duplicate['post_modified']     = date( 'Y-m-d H:i:s', current_time( 'timestamp', 0 ) );
					$duplicate['post_modified_gmt'] = date( 'Y-m-d H:i:s', current_time( 'timestamp', 1 ) );

					// Remove some of the keys
					unset( $duplicate['ID'] );
					unset( $duplicate['guid'] );
					unset( $duplicate['comment_count'] );

					// Insert the post into the database
					$duplicate_id = wp_insert_post( $duplicate );

					// Duplicate all the taxonomies/terms
					$taxonomies = get_object_taxonomies( $duplicate['post_type'] );
					foreach ( $taxonomies as $taxonomy ) {
						$terms = wp_get_post_terms( $original_id, $taxonomy, array( 'fields' => 'names' ) );
						wp_set_object_terms( $duplicate_id, $terms, $taxonomy );
					}

					// Duplicate all the custom fields
					$custom_fields = get_post_custom( $original_id );

					foreach ( $custom_fields as $key => $value ) {
						if ( strpos( $key, 'xlwcfg_' ) === false ) {
							continue;
						}
						if ( is_array( $value ) && count( $value ) > 0 ) {
							foreach ( $value as $v ) {
								add_post_meta( $duplicate_id, $key, maybe_unserialize( $v ) );
							}
						}
					}

					do_action( 'xlwcfg_post_duplicated', $original_id, $duplicate_id, $settings );

					wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=' . XLWCFG_Common::get_wc_settings_tab_slug() . '&section=' . $section ) );
				}
			} else {
				die( __( 'Unable to Duplicate', 'bonanza-woocommerce-free-gifts-lite' ) );
			}
		}
	}

	/**
	 * Hooked over `xlwcfg_cmb2_modify_field_tabs`
	 * Sorts Tabs for settings
	 *
	 * @param $tabs Array of tabs
	 *
	 * @return mixed Sorted array
	 */
	public function xlwcfg_admin_reorder_tabs( $tabs ) {
		usort( $tabs, array( $this, '_sort_by_priority' ) );

		return $tabs;
	}

	/**
	 * Hooked over `postbox_classes_product_xlwcfg_product_option_tabs`
	 * Always open for meta boxes
	 * removing closed class
	 *
	 * @param $classes classes
	 *
	 * @return mixed array of classes
	 */
	public function xlwcfg_metabox_always_open( $classes ) {
		if ( ( $key = array_search( 'closed', $classes ) ) !== false ) {
			unset( $classes[ $key ] );
		}

		return $classes;
	}

	/**
	 * Hooked over 'plugin_action_links_{PLUGIN_BASENAME}' WordPress hook to add deactivate popup support
	 *
	 * @param array $links array of existing links
	 *
	 * @return array modified array
	 */
	public function xlwcfg_plugin_actions( $links ) {
		$go_pro_link         = add_query_arg( array(
			'utm_source'   => 'bonanza-lite',
			'utm_medium'   => 'text-click',
			'utm_campaign' => 'plugin-actions',
			'utm_term'     => 'go-pro',
		), 'https://xlplugins.com/woocommerce-free-gifts-bonanza/' );
		$links['settings']   = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . XLWCFG_Common::get_wc_settings_tab_slug() ) . '" class="edit">Settings</a>';
		$links['deactivate'] .= '<i class="xl-slug" data-slug="' . XLWCFG_PLUGIN_BASENAME . '"></i>';
		$links['go_pro']     = '<a style="font-weight: 700; color:#39b54a" href="' . $go_pro_link . '" class="go_pro_a">' . __( "Go Pro", 'bonanza-woocommerce-free-gifts-lite' ) . '</a>';

		return $links;
	}

	public function xlwcfg_plugin_row_actions( $links, $file ) {
		if ( $file == XLWCFG_PLUGIN_BASENAME ) {
			$links[] = '<a href="' . add_query_arg( array(
					'utm_source'   => 'bonanza-lite',
					'utm_campaign' => 'plugin-action-link',
					'utm_medium'   => 'text-click',
					'utm_term'     => 'Docs',
				), 'https://xlplugins.com/documentation/bonanza-woocommerce-free-gifts/' ) . '">' . esc_html__( 'Docs', 'bonanza-woocommerce-free-gifts-lite' ) . '</a>';
			$links[] = '<a href="' . add_query_arg( array(
					'utm_source'   => 'bonanza-lite',
					'utm_campaign' => 'plugin-action-link',
					'utm_medium'   => 'text-click',
					'utm_term'     => 'support',
				), 'https://xlplugins.com/support/' ) . '">' . esc_html__( 'Support', 'bonanza-woocommerce-free-gifts-lite' ) . '</a>';
		}

		return $links;
	}

	/**
	 * Hooked to `woocommerce_settings_tabs_array`
	 * Adding new tab in woocommerce settings
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function modify_woocommerce_settings( $settings ) {
		$settings[ XLWCFG_Common::get_wc_settings_tab_slug() ] = __( 'Bonanza Lite: XLPlugins', 'bonanza-woocommerce-free-gifts-lite' );

		return $settings;
	}

	/** Functions For Rules Functionality Starts */
	public function xlwcfg_edit_form_top() {
		global $post;
		if ( XLWCFG_Common::get_free_gift_cpt_slug() != $post->post_type ) {
			return;
		}
		?>
        <div class="notice">
            <p><?php _e( 'Back to <a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . XLWCFG_Common::get_wc_settings_tab_slug() . '' ) . '">' . XLWCFG_FULL_NAME . '</a> settings.', 'bonanza-woocommerce-free-gifts-lite' ); ?></p>
        </div>
		<?php
	}

	public function post_updated_messages( $messages ) {
		global $post, $post_ID;

		$messages[ XLWCFG_Common::get_free_gift_cpt_slug() ] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => sprintf( __( 'Free Gift updated.', 'bonanza-woocommerce-free-gifts-lite' ), admin_url( 'admin.php?page=wc-settings&tab=' . XLWCFG_Common::get_wc_settings_tab_slug() . '' ) ),
			2  => __( 'Custom field updated.', 'bonanza-woocommerce-free-gifts-lite' ),
			3  => __( 'Custom field deleted.', 'bonanza-woocommerce-free-gifts-lite' ),
			4  => sprintf( __( 'Free Gift updated. ', 'bonanza-woocommerce-free-gifts-lite' ), admin_url( 'admin.php?page=wc-settings&tab=' . XLWCFG_Common::get_wc_settings_tab_slug() . '' ) ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Free Gift restored to revision from %s', 'bonanza-woocommerce-free-gifts-lite' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( __( 'Free Gift updated. ', 'bonanza-woocommerce-free-gifts-lite' ), admin_url( 'admin.php?page=wc-settings&tab=' . XLWCFG_Common::get_wc_settings_tab_slug() . '' ) ),
			7  => sprintf( __( 'Trigger saved. ', 'bonanza-woocommerce-free-gifts-lite' ), admin_url( 'admin.php?page=wc-settings&tab=' . XLWCFG_Common::get_wc_settings_tab_slug() . '' ) ),
			8  => sprintf( __( 'Free Gift updated. ', 'bonanza-woocommerce-free-gifts-lite' ), admin_url( 'admin.php?page=wc-settings&tab=' . XLWCFG_Common::get_wc_settings_tab_slug() . '' ) ),
			9  => sprintf( __( 'Free Gift scheduled for: <strong>%1$s</strong>.', 'bonanza-woocommerce-free-gifts-lite' ), date_i18n( 'M j, Y @ G:i', strtotime( $post->post_date ) ) ),
			10 => __( 'Free Gift draft updated.', 'bonanza-woocommerce-free-gifts-lite' ),
			11 => sprintf( __( 'Free Gift updated. ', 'bonanza-woocommerce-free-gifts-lite' ), admin_url( 'admin.php?page=wc-settings&tab=' . XLWCFG_Common::get_wc_settings_tab_slug() . '' ) ),
		);

		return $messages;
	}

	public function maybe_activate_post() {
		if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'xlwcfg-post-activate' || ( isset( $_GET['xlwcfg_action'] ) && $_GET['xlwcfg_action'] == 'xlwcfg-post-activate' ) ) ) {
			if ( wp_verify_nonce( $_GET['_wpnonce'], 'xlwcfg-post-activate' ) ) {

				$postID  = filter_input( INPUT_GET, 'postid' );
				$section = filter_input( INPUT_GET, 'trigger' );
				if ( $postID ) {
					wp_update_post( array(
						'ID'          => $postID,
						'post_status' => 'publish',
					) );
					if ( isset( $_GET['xlwcfg_action'] ) ) {
						wp_redirect( admin_url( 'post.php?post=' . $_GET['postid'] . '&action=edit' ) );
					} else {
						wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=' . XLWCFG_Common::get_wc_settings_tab_slug() . '&section=' . $section ) );
					}
				}
			} else {
				die( __( 'Unable to Activate', 'bonanza-woocommerce-free-gifts-lite' ) );
			}
		}
	}

	public function maybe_deactivate_post() {
		if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'xlwcfg-post-deactivate' || ( isset( $_GET['xlwcfg_action'] ) && $_GET['xlwcfg_action'] == 'xlwcfg-post-deactivate' ) ) ) {

			if ( wp_verify_nonce( $_GET['_wpnonce'], 'xlwcfg-post-deactivate' ) ) {

				$postID  = filter_input( INPUT_GET, 'postid' );
				$section = filter_input( INPUT_GET, 'trigger' );
				if ( $postID ) {

					wp_update_post( array(
						'ID'          => $postID,
						'post_status' => XLWCFG_SHORT_SLUG . 'disabled',
					) );
					if ( isset( $_GET['xlwcfg_action'] ) ) {
						wp_redirect( admin_url( 'post.php?post=' . $_GET['postid'] . '&action=edit' ) );
					} else {
						wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=' . XLWCFG_Common::get_wc_settings_tab_slug() . '&section=' . $section ) );
					}
				}
			} else {
				die( __( 'Unable to Deactivate', 'bonanza-woocommerce-free-gifts-lite' ) );
			}
		}
	}

	public function xlwcfg_footer_css() {
		if ( XLWCFG_Common::xlwcfg_valid_admin_pages() ) {
			?>
            <style>
                .wrap.woocommerce p.submit {
                    display: none;
                }

                #XLWCFG_MB_ajaxContent ol {
                    font-weight: bold;
                }
            </style>
			<?php
		}
	}

	public function xlwcfg_add_cmb2_post_select() {
		include_once $this->admin_dir . 'includes/cmb2-addons/post-select/class-cmb2-xlwcfg-post-select.php';
	}

	/**
	 * Hooked over `cmb2_render_xlwcfg_post_select`
	 * Render Html for `xlwcfg_xlwcfg_post_select` Field
	 *
	 * @param $field CMB@ Field object
	 * @param $escaped_value Value
	 * @param $object_id object ID
	 * @param $object_type Object Type
	 * @param $field_type_object Field Type Object
	 */
	public function xlwcfg_post_select( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
		$field_obj = new CMB2_XLWCFG_Post_Select( $field_type_object );
		echo $field_obj->render();
	}

	/**
	 * @hooked over `cmb2 after field save`
	 *
	 * @param $post_id
	 */
	public function after_cmb2_form_data_saved( $post_id ) {
		if ( class_exists( 'XL_Transient' ) ) {
			/** Running twice so unhook */
			remove_action( 'cmb2_save_post_fields_xlwcfg_buy_box_settings', array( $this, 'after_cmb2_form_data_saved' ), 1000 );

			$xl_transient_obj = XL_Transient::get_instance();

			$key = 'xlwcfg_gift_meta_' . $post_id;
			$xl_transient_obj->delete_transient( $key, 'free-gift' );

			$key = 'xlwcfg_gift_posts';
			$xl_transient_obj->delete_transient( $key, 'free-gift' );

			/** Saving buy products in meta if variation found */
			if ( isset( $_REQUEST['xlwcfg_rule'] ) && ! empty( $_REQUEST['xlwcfg_rule'] ) ) {
				$rules_data     = maybe_unserialize( $_REQUEST['xlwcfg_rule'] );
				$variation_data = array();

				if ( isset( $rules_data['product'] ) && is_array( $rules_data['product'] ) && count( $rules_data['product'] ) > 0 ) {
					foreach ( $rules_data['product'] as $rule ) {
						if ( is_array( $rule ) && count( $rule ) > 0 ) {
							foreach ( $rule as $single_rule ) {
								if ( 'product_item' !== $single_rule['rule_type'] ) {
									continue;
								}
								if ( is_array( $single_rule['condition']['products'] ) && count( $single_rule['condition']['products'] ) > 0 ) {
									foreach ( $single_rule['condition']['products'] as $product_id ) {
										$product_id = absint( $product_id );
										$operator   = $single_rule['operator'];

										if ( 'in' === $operator ) {
											$product_data = wc_get_product( $product_id );
											if ( $product_data instanceof WC_Product_Variation ) {
												/** This is a variation */
												$parent_id                     = $product_data->get_parent_id();
												$variation_data[ $product_id ] = $parent_id;
											}
										}
									}
								}

							}
						}

					}
				}
				if ( count( $variation_data ) > 0 ) {
					update_post_meta( $post_id, '_xlwcfg_variations', $variation_data );
				} else {
					delete_post_meta( $post_id, '_xlwcfg_variations' );
				}
			}
		}
	}

	/**
	 * @hooked over `delete_post`
	 *
	 * @param $post_id
	 */
	public function clear_transients_on_delete( $post_id ) {

		$get_post_type = get_post_type( $post_id );

		if ( XLWCFG_Common::get_free_gift_cpt_slug() === $get_post_type ) {
			if ( class_exists( 'XL_Transient' ) ) {
				$xl_transient_obj = XL_Transient::get_instance();
				$xl_transient_obj->delete_all_transients( 'free-gift' );
			}
		}
	}

	public function restrict_to_publish_when_post_is_disabled( $post_ID, $post_after, $post_before ) {
		remove_action( 'post_updated', array( $this, 'restrict_to_publish_when_post_is_disabled' ), 10 );
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'wc-settings' && isset( $_GET['tab'] ) && $_GET['tab'] == XLWCFG_Common::get_wc_settings_tab_slug() ) {
		} else {
			if ( $post_before->post_status == XLWCFG_SHORT_SLUG . 'disabled' && ! isset( $_GET['xlwcfg_action'] ) ) {
				$post_after->post_status = XLWCFG_SHORT_SLUG . 'disabled';
				$temp                    = json_encode( $post_after );
				$post_after              = json_decode( $temp, true );
				wp_update_post( $post_after );
			}
		}
	}

	public function default_orderby_date( $args ) {
		$args['order']   = 'DESC';
		$args['orderby'] = 'date';

		return $args;
	}

	/**
	 * Hooked over `cmb2_render_xlwcfg_html_content_field`
	 * Render Html for `xlwcfg_html_content` Field
	 *
	 * @param $field CMB@ Field object
	 * @param $escaped_value Value
	 * @param $object_id object ID
	 * @param $object_type Object Type
	 * @param $field_type_object Field Type Object
	 */
	public function xlwcfg_html_content_fields( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
		$conditional_value = ( isset( $field->args['attributes']['data-conditional-value'] ) ? 'data-conditional-value="' . esc_attr( $field->args['attributes']['data-conditional-value'] ) . '"' : '' );
		$conditional_id    = ( isset( $field->args['attributes']['data-conditional-id'] ) ? ' data-conditional-id="' . esc_attr( $field->args['attributes']['data-conditional-id'] ) . '"' : '' );

		$xlwcfg_conditional_value = ( isset( $field->args['attributes']['data-xlwcfg-conditional-value'] ) ? 'data-xlwcfg-conditional-value="' . esc_attr( $field->args['attributes']['data-xlwcfg-conditional-value'] ) . '"' : '' );
		$xlwcfg_conditional_id    = ( isset( $field->args['attributes']['data-xlwcfg-conditional-id'] ) ? ' data-xlwcfg-conditional-id="' . esc_attr( $field->args['attributes']['data-xlwcfg-conditional-id'] ) . '"' : '' );

		$switch = '<div ' . $conditional_value . $conditional_id . $xlwcfg_conditional_value . $xlwcfg_conditional_id . ' class="cmb2-xlwcfg_html" id="' . $field->args['id'] . '">';
		if ( isset( $field->args['content_cb'] ) ) {
			$switch .= call_user_func( $field->args['content_cb'] );
		} elseif ( isset( $field->args['content'] ) ) {
			$switch .= ( $field->args['content'] );
		}

		$switch .= '</div>';
		echo $switch;
	}

	/**
	 * Check the screen and check if plugins update available to show notification to the admin to update the plugin
	 */
	public function maybe_show_advanced_update_notification() {

		$screen = get_current_screen();

		if ( is_object( $screen ) && ( 'plugins.php' == $screen->parent_file || 'index.php' == $screen->parent_file || XLWCFG_Common::get_wc_settings_tab_slug() == filter_input( INPUT_GET, 'tab' ) ) ) {
			$plugins = get_site_transient( 'update_plugins' );
			if ( isset( $plugins->response ) && is_array( $plugins->response ) ) {
				$plugins = array_keys( $plugins->response );
				if ( is_array( $plugins ) && count( $plugins ) > 0 && in_array( XLWCFG_PLUGIN_BASENAME, $plugins ) ) {
					?>
                    <div class="notice notice-warning is-dismissible">
                        <p>
							<?php
							_e( sprintf( 'Attention: There is an update available of <strong>%s</strong> plugin. &nbsp;<a href="%s" class="">Go to updates</a>', XLWCFG_FULL_NAME, admin_url( 'plugins.php?s=bonanza&plugin_status=all' ) ), 'bonanza-woocommerce-free-gifts-lite' );
							?>
                        </p>
                    </div>
					<?php

				}
			}
		}

	}

}

new XLWCFG_Admin();
