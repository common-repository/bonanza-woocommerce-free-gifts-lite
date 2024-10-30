<?php
defined( 'ABSPATH' ) || exit;

/**
 * Class XLWCFG_XL_Support
 * @package Bonanza-Lite
 * @author XlPlugins
 */
class XLWCFG_XL_Support {

	public static $_instance = null;
	public $full_name = XLWCFG_FULL_NAME;
	public $expected_url;
	protected $encoded_basename = '';

	public function __construct() {
		$this->expected_url     = admin_url( 'admin.php?page=xlplugins' );
		$this->encoded_basename = sha1( XLWCFG_PLUGIN_BASENAME );

		/** XL core hooks */
		add_filter( "xl_optin_notif_show", array( $this, 'xlwcfg_xl_show_optin_pages' ), 10, 1 );

		add_action( 'admin_init', array( $this, 'xlwcfg_xl_expected_slug' ), 9.1 );
		add_action( 'maybe_push_optin_notice_state_action', array( $this, 'xlwcfg_try_push_notification_for_optin' ), 10 );

		add_action( 'admin_init', array( $this, 'modify_api_args_if_xlwcfg_dashboard' ), 20 );
		add_filter( 'extra_plugin_headers', array( $this, 'extra_woocommerce_headers' ) );

		add_filter( 'add_menu_classes', array( $this, 'modify_menu_classes' ) );
		add_action( 'admin_init', array( $this, 'xlwcfg_xl_parse_request_and_process' ), 15 );

		add_action( 'xl_deactivate_request', array( $this, 'maybe_process_deactivation' ) );

		add_filter( 'xl_dashboard_tabs', array( $this, 'xlwcfg_modify_tabs' ), 999, 1 );

		add_action( 'xlwcfg_options_page_right_content', array( $this, 'xlwcfg_options_page_right_content' ), 10 );

		add_action( 'admin_menu', array( $this, 'add_menus' ), 80.1 );
		add_action( 'admin_menu', array( $this, 'add_xlwcfg_menu' ), 85.2 );

		add_filter( 'xl_uninstall_reasons', array( $this, 'modify_uninstall_reason' ) );

		add_filter( 'xl_uninstall_reason_threshold_' . XLWCFG_PLUGIN_BASENAME, function () {
			return 10;
		} );

		add_filter( 'xl_global_tracking_data', array( $this, 'xl_add_administration_emails' ) );

		// tools
		add_action( 'admin_init', array( $this, 'download_tools_settings' ), 2 );
		add_action( 'xl_tools_after_content', array( $this, "export_tools_after_content" ) );
	}

	/**
	 * @return null|XLWCFG_XL_Support
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	public function xlwcfg_xl_show_optin_pages( $is_show ) {
		return true;
	}

	public function xlwcfg_xl_expected_slug() {
		if ( isset( $_GET['page'] ) && ( $_GET['page'] == "xlplugins" || $_GET['page'] == "xlplugins-support" || $_GET['page'] == "xlplugins-addons" ) ) {
			XL_dashboard::set_expected_slug( XLWCFG_SHORT_SLUG );
		}
		XL_dashboard::set_expected_url( $this->expected_url );
	}

	public function xlwcfg_metabox_always_open( $classes ) {
		if ( ( $key = array_search( 'closed', $classes ) ) !== false ) {
			unset( $classes[ $key ] );
		}

		return $classes;
	}

	public function modify_api_args_if_xlwcfg_dashboard() {
		if ( XL_dashboard::get_expected_slug() == XLWCFG_SHORT_SLUG ) {
			add_filter( 'xl_api_call_agrs', array( $this, 'modify_api_args_for_gravityxl' ) );
			XL_dashboard::register_dashboard( array( 'parent' => array( 'woocommerce' => "WooCommerce Add-ons" ), 'name' => XLWCFG_SHORT_SLUG ) );
		}
	}

	public function xlplugins_page() {
		if ( ! isset( $_GET['tab'] ) ) {
			$licenses = apply_filters( 'xl_plugins_license_needed', array() );
			if ( empty( $licenses ) ) {
				XL_dashboard::$selected = "support";
			} else {
				XL_dashboard::$selected = "licenses";
			}
		}
		XL_dashboard::load_page();
	}

	public function xlplugins_support_page() {
		if ( ! isset( $_GET['tab'] ) ) {
			XL_dashboard::$selected = "support";
		}
		XL_dashboard::load_page();
	}

	public function xlplugins_plugins_page() {
		XL_dashboard::$selected = "plugins";
		XL_dashboard::load_page();
	}

	public function modify_api_args_for_gravityxl( $args ) {
		if ( isset( $args['edd_action'] ) && $args['edd_action'] == "get_xl_plugins" ) {
			$args['attrs']['tax_query'] = array(
				array(
					'taxonomy' => 'xl_edd_tax_parent',
					'field'    => 'slug',
					'terms'    => 'woocommerce',
					'operator' => 'IN'
				)
			);
		}
		$args['purchase'] = XLWCFG_PURCHASE;

		return $args;
	}

	public function xlwcfg_try_push_notification_for_optin() {
		if ( ! XL_admin_notifications::has_notification( 'xl_optin_notice' ) ) {
			XL_admin_notifications::add_notification( array(
				'xl_optin_notice' => array(
					'type'    => 'info',
					'content' => sprintf( '
                        <p>We\'re always building new features into <a target="_blank" href=\'%s\'>Bonanza</a>, Play a part in shaping the future of Bonanza and in turn benefit from new conversion-boosting updates.</p>
                        <p>Simply by allowing us to learn about your plugin usage. No sensitive information will be passed on to us. It\'s all safe & secure to say YES.</p>
                        <p><a href=\'%s\' class=\'button button-primary\'>Yes, I want to help</a> <a href=\'%s\' class=\'button button-secondary\'>No, I don\'t want to help</a> <a style="float: right;" target="_blank" href=\'%s\'>Know More</a></p> ', esc_url( "https://xlplugins.com/woocommerce-free-gifts-bonanza/?utm_source=bonanza-lite&utm_campaign=optin&utm_medium=text&utm_term=yes-ready-to-help" ), esc_url( wp_nonce_url( add_query_arg( array(
						'xl-optin-choice' => 'yes',
						'ref'             => filter_input( INPUT_GET, 'page' )
					) ), 'xl_optin_nonce', '_xl_optin_nonce' ) ), esc_url( wp_nonce_url( add_query_arg( 'xl-optin-choice', 'no' ), 'xl_optin_nonce', '_xl_optin_nonce' ) ), esc_url( "https://xlplugins.com/data-collection-policy/?utm_source=bonanza-lite&utm_campaign=optin&utm_medium=text&utm_term=data-collection-policy" ) )
				)
			) );
		}
	}

	/**
	 * Adding XL Header to tell wordpress to read one extra params while reading plugin's header info. <br/>
	 * Hooked over `extra_plugin_headers`
	 * @since 1.0.0
	 *
	 * @param array $headers already registered arrays
	 *
	 * @return type
	 */
	public function extra_woocommerce_headers( $headers ) {
		array_push( $headers, 'XL' );

		return $headers;
	}

	public function modify_menu_classes( $menu ) {
		return $menu;
	}

	public function xlwcfg_xl_parse_request_and_process() {
		$instance_support = XL_Support::get_instance();

		if ( XLWCFG_SHORT_SLUG == XL_dashboard::get_expected_slug() && isset( $_POST['xl_submit_support'] ) ) {

			if ( filter_input( INPUT_POST, 'choose_addon' ) == "" || filter_input( INPUT_POST, 'comments' ) == "" ) {
				$instance_support->validation = false;
				XL_admin_notifications::add_notification( array(
					'support_request_failure' => array(
						'type'           => 'error',
						'is_dismissable' => true,
						'content'        => __( '<p> Unable to submit your request.All fields are required. Please ensure that all the fields are filled out.</p>', 'bonanza-woocommerce-free-gifts-lite' ),
					)
				) );
			} else {
				$instance_support->xl_maybe_push_support_request( $_POST );
			}
		}
	}

	/**
	 * Validate is it is for email product deactivation
	 *
	 * @param type $posted_data
	 */
	public function maybe_process_deactivation( $posted_data ) {
		if ( isset( $posted_data['filepath'] ) && $posted_data['filepath'] == $this->encoded_basename ) {
			wp_safe_redirect( 'admin.php?page=' . $posted_data['page'] . "&tab=" . $posted_data['tab'] );
		}
	}

	public function xlwcfg_modify_tabs( $tabs ) {
		if ( XLWCFG_SHORT_SLUG == XL_dashboard::get_expected_slug() ) {
			return array();
		}

		return $tabs;
	}

	/**
	 * Adding WooCommerce sub-menu for global options
	 */
	public function add_menus() {
		if ( ! XL_dashboard::$is_core_menu ) {
			add_menu_page( __( 'XLPlugins', 'bonanza-woocommerce-free-gifts-lite' ), __( 'XLPlugins', 'bonanza-woocommerce-free-gifts-lite' ), 'manage_woocommerce', 'xlplugins', array(
				$this,
				'xlplugins_page'
			), '', '59.5' );
			XL_dashboard::$is_core_menu = true;
		}
	}

	public function add_xlwcfg_menu() {
		add_submenu_page( 'xlplugins', XLWCFG_FULL_NAME, 'Bonanza Lite', 'manage_woocommerce', 'admin.php?page=wc-settings&tab=' . XLWCFG_Common::get_wc_settings_tab_slug() . '', false );
	}

	public function xlwcfg_options_page_right_content() {
		$go_pro_link         = add_query_arg( array(
			'utm_source'   => 'bonanza-lite',
			'utm_medium'   => 'sidebar',
			'utm_campaign' => 'plugin-resource',
			'utm_term'     => 'buy_now',
		), 'https://xlplugins.com/woocommerce-free-gifts-bonanza/' );
		$demo_link           = add_query_arg( array(
			'utm_source'   => 'bonanza-lite',
			'utm_medium'   => 'sidebar',
			'utm_campaign' => 'plugin-resource',
			'utm_term'     => 'demo',
		), 'http://demo.xlplugins.com/bonanza/' );
		$support_link        = add_query_arg( array(
			'utm_source'   => 'bonanza-lite',
			'utm_medium'   => 'sidebar',
			'utm_campaign' => 'plugin-resource',
			'utm_term'     => 'support',
		), 'https://xlplugins.com/support/' );
		$documentation_link  = add_query_arg( array(
			'utm_source'   => 'bonanza-lite',
			'utm_medium'   => 'sidebar',
			'utm_campaign' => 'plugin-resource',
			'utm_term'     => 'documentation',
		), 'https://xlplugins.com/documentation/bonanza-woocommerce-free-gifts/' );

		$other_products = array();
		if ( ! class_exists( 'WCCT_Core' ) ) {
			$finale_link              = add_query_arg( array(
				'utm_source'   => 'bonanza-lite',
				'utm_medium'   => 'sidebar',
				'utm_campaign' => 'other-products',
				'utm_term'     => 'finale',
			), 'https://xlplugins.com/finale-woocommerce-sales-countdown-timer-discount-plugin/' );
			$other_products['finale'] = array(
				'image' => 'finale.png',
				'link'  => $finale_link,
				'head'  => 'Finale WooCommerce Sales Free Gift',
				'desc'  => 'Run Urgency Marketing Campaigns On Your Store And Move Buyers to Make A Purchase',
			);
		}
		if ( ! defined( 'WCST_SLUG' ) ) {
			$sales_trigger_link              = add_query_arg( array(
				'utm_source'   => 'bonanza-lite',
				'utm_medium'   => 'sidebar',
				'utm_campaign' => 'other-products',
				'utm_term'     => 'sales-trigger',
			), 'https://xlplugins.com/woocommerce-sales-triggers/' );
			$other_products['sales_trigger'] = array(
				'image' => 'sales-trigger.png',
				'link'  => $sales_trigger_link,
				'head'  => 'XL WooCommerce Sales Triggers',
				'desc'  => 'Use 7 Built-in Sales Triggers to Optimise Single Product Pages For More Conversions',
			);
		}
		if ( ! class_exists( 'XLWCTY_Core' ) ) {
			$nextmove_link              = add_query_arg( array(
				'utm_source'   => 'bonanza-lite',
				'utm_medium'   => 'sidebar',
				'utm_campaign' => 'other-products',
				'utm_term'     => 'nextmove',
			), 'https://xlplugins.com/woocommerce-thank-you-page-nextmove/' );
			$other_products['nextmove'] = array(
				'image' => 'nextmove.png',
				'link'  => $nextmove_link,
				'head'  => 'NextMove WooCommerce Thank You Pages',
				'desc'  => 'Get More Repeat Orders With 17 Plug n Play Components',
			);
		}
		if ( is_array( $other_products ) && count( $other_products ) > 0 ) {
			?>
            <h3>Checkout Our Other Plugins:</h3>
			<?php
			foreach ( $other_products as $product_short_name => $product_data ) {
				?>
                <div class="postbox xlwcfg_side_content xlwcfg_xlplugins xlwcfg_xlplugins_<?php echo $product_short_name ?>">
                    <a href="<?php echo $product_data['link'] ?>" target="_blank"></a>
                    <img src="<?php echo XLWCFG_PLUGIN_URL . 'admin/assets/img/' . $product_data['image']; ?>"/>
                    <div class="xlwcfg_plugin_head"><?php echo $product_data['head'] ?></div>
                    <div class="xlwcfg_plugin_desc"><?php echo $product_data['desc'] ?></div>
                </div>
				<?php
			}
		}

		$img_url = XLWCFG_PLUGIN_URL . 'admin/assets/img/support.jpg';
		?>
        <div class="postbox xlwcfg_side_content">
            <div class="inside">
                <h3>Resources</h3>
                <ul>
                    <li><a href="<?php echo $go_pro_link ?>" target="_blank">Get PRO</a></li>
                    <li><a href="<?php echo $support_link ?>" target="_blank">Support</a></li>
                    <li><a href="<?php echo $documentation_link ?>" target="_blank">Documentation</a></li>
                </ul>
            </div>
        </div>
		<?php
	}

	public function modify_uninstall_reason( $reasons ) {
		$reasons_our = $reasons;

		$reason_other = array(
			'id'                => 7,
			'text'              => __( "Other", 'bonanza-woocommerce-free-gifts-lite' ),
			'input_type'        => 'textfield',
			'input_placeholder' => __( "Other", 'bonanza-woocommerce-free-gifts-lite' ),
		);


		$support_ticket_link = admin_url( 'admin.php?page=xlplugins' );

		$xl_contact_link = add_query_arg( array(
			'utm_source' => 'bonanza-lite',
			'utm_medium' => 'deactivation-modal',
			'utm_term'   => 'contact',
		), 'https://xlplugins.com/contact/' );

		$reasons_our[ XLWCFG_PLUGIN_BASENAME ] = array(
			array(
				'id'                => 42,
				'text'              => __( 'Free Gifts are not adding to cart', 'bonanza-woocommerce-free-gifts-lite' ),
				'input_type'        => '',
				'input_placeholder' => '',
			),
			array(
				'id'                => 43,
				'text'              => __( 'Gifts were not restricted as per rules', 'bonanza-woocommerce-free-gifts-lite' ),
				'input_type'        => '',
				'input_placeholder' => '',
				'html'              => __( 'Raise a <a href="' . $support_ticket_link . '">Support ticket</a> with the screenshot of rules settings and will help you resolve this.', 'bonanza-woocommerce-free-gifts-lite' ),
			),
			array(
				'id'                => 44,
				'text'              => __( 'Bonanza Activation caused PHP Errors or blank white screen', 'bonanza-woocommerce-free-gifts-lite' ),
				'input_type'        => '',
				'input_placeholder' => '',
				'html'              => __( 'Ensure you have the latest version of WooCommerce & Bonanza. There could be a possibility of conflict with other plugins. Raise a <a href="' . $support_ticket_link . '">Support ticket</a> and will help you resolve this.', 'bonanza-woocommerce-free-gifts-lite' ),
			),
			array(
				'id'                => 41,
				'text'              => __( 'Troubleshooting conflicts with other plugins', 'bonanza-woocommerce-free-gifts-lite' ),
				'input_type'        => '',
				'input_placeholder' => '',
				'html'              => __( 'Hope you could resolve conflicts soon.', 'bonanza-woocommerce-free-gifts-lite' ),
			),
			array(
				'id'                => 35,
				'text'              => __( 'Doing Testing', 'bonanza-woocommerce-free-gifts-lite' ),
				'input_type'        => '',
				'input_placeholder' => '',
				'html'              => __( 'Hope to see you using it again.', 'bonanza-woocommerce-free-gifts-lite' ),
			),
			array(
				'id'                => 1,
				'text'              => __( 'I no longer need the plugin', 'bonanza-woocommerce-free-gifts-lite' ),
				'input_type'        => '',
				'input_placeholder' => '',
				'html'              => __( 'Sorry to know that! How can we better your experience? We may be able to fix what we are aware of. Please <a href="' . $xl_contact_link . '" target="_blank">let us know</a>.', 'bonanza-woocommerce-free-gifts-lite' ),
			),
		);

		array_push( $reasons_our[ XLWCFG_PLUGIN_BASENAME ], $reason_other );

		return $reasons_our;

	}

	public function xl_add_administration_emails( $data ) {

		if ( isset( $data['admins'] ) ) {
			return $data;
		}
		$users = get_users( array( 'role' => 'administrator', 'fields' => array( 'user_email', 'user_nicename' ) ) );

		$data['admins'] = $users;

		return $data;


	}

	public function export_tools_after_content( $model ) {
		$system_info = XL_Support::get_instance()->prepare_system_information_report();
		?>
        <div class="xl_core_tools" style="width:80%;background: #fff;">
            <h2><?php echo __( 'Bonanza Lite' ); ?></h2>
            <form method="post">
                <div class="xl_core_tools_inner" style="min-height: 300px;">
                    <textarea name="xl_tools_system_info" readonly style="width:100%;height: 280px;"><?php echo $system_info ?></textarea>
                </div>
                <div style="clear: both;"></div>
                <div class="xl_core_tools_button" style="margin-bottom: 10px;">
                    <a class="button button-primary button-large xl_core_tools_btn" data-plugin="bonanza-lite" href="<?php echo add_query_arg( array(
						"content"  => XLWCFG_Common::get_free_gift_cpt_slug(),
						"download" => "true"
					), admin_url( "export.php" ) ) ?>"><?php echo __( "Export Bonanza Gifts", 'bonanza-woocommerce-free-gifts-lite' ) ?></a>
                    <button type="submit" class="button button-primary button-large xl_core_tools_btn" name="xl_tools_export_setting" value="bonanza-lite"><?php echo __( "Export Settings", 'bonanza-woocommerce-free-gifts-lite' ) ?></button>
                </div>
                <br>
            </form>
        </div>
		<?php
	}

	public function download_tools_settings() {
		if ( isset( $_POST["xl_tools_export_setting"] ) && $_POST["xl_tools_export_setting"] == "bonanza-lite" && isset( $_POST["xl_tools_system_info"] ) && $_POST["xl_tools_system_info"] != '' ) {
			$system_info = XL_Support::get_instance()->prepare_system_information_report( true );
			header( 'Content-Description: File Transfer' );
			header( 'Content-Disposition: attachment; filename=bonanza-lite.json' );
			echo( json_encode( $system_info ) );
			exit;
		}
	}

	public function get_shipping_method() {
		global $wpdb;
		$output     = array();
		$freeMethod = $wpdb->get_results( "select * from {$wpdb->prefix}woocommerce_shipping_zone_methods where method_id='free_shipping'", ARRAY_A );
		if ( is_array( $freeMethod ) && count( $freeMethod ) > 0 ) {
			foreach ( $freeMethod as $method ) {
				$free_shipping = get_option( "woocommerce_free_shipping_{$method["method_order"]}_settings", array() );
				if ( is_array( $free_shipping ) && count( $free_shipping ) > 0 ) {
					$output[] = $free_shipping;
				}
			}
		}

		return $output;

	}

	public function get_free_shipping_coupon() {
		global $wpdb;
		$free_coupon = $wpdb->get_results( "select p.id,p.post_title from {$wpdb->prefix}postmeta as m join {$wpdb->prefix}posts as p on m.post_id=p.id where m.meta_key='free_shipping' and m.meta_value='yes' and p.post_type='shop_coupon' and p.post_status='publish' order by p.post_date desc limit 10 ", ARRAY_A );
		if ( is_array( $free_coupon ) && count( $free_coupon ) > 0 ) {
			foreach ( $free_coupon as $key => $value ) {
				$date_expires                        = get_post_meta( $value['id'], "date_expires", true );
				$expiry_date                         = get_post_meta( $value['id'], "expiry_date", true );
				$free_coupon[ $key ]["date_expires"] = $date_expires;
				$free_coupon[ $key ]["expiry_date"]  = $expiry_date;
				$post_title                          = $free_coupon[ $key ]["post_title"];
				unset( $free_coupon[ $key ]["post_title"] );
				$free_coupon[ $key ]["coupon_code"] = $post_title;
			}
		}

		return $free_coupon;
	}

}

if ( class_exists( "XLWCFG_XL_Support" ) ) {
	XLWCFG_Core::register( "xl_support", "XLWCFG_XL_Support" );
}
