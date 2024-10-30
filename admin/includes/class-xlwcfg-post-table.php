<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class XLWCFG_Post_Table extends WP_List_Table {

	public $per_page = 40;
	public $data;
	public $meta_data;
	public $batch_data = [];
	public $check_options;

	/**
	 * Constructor.
	 * @since  1.0.0
	 */
	public function __construct( $args = array() ) {
		global $status, $page;
		parent::__construct( array(
			'singular' => 'free-gift', //singular name of the listed records
			'plural'   => 'free-gifts', //plural name of the listed records
			'ajax'     => false        //does this table support ajax?
		) );
		$status     = 'all';
		$page       = $this->get_pagenum();
		$this->data = array();
		// Make sure this file is loaded, so we have access to plugins_api(), etc.
		require_once( ABSPATH . '/wp-admin/includes/plugin-install.php' );
		parent::__construct( $args );
	}

	/**
	 * Text to display if no items are present.
	 * @since  1.0.0
	 * @return  void
	 */
	public function no_items() {
		echo wpautop( __( 'No gift available', 'bonanza-woocommerce-free-gifts-lite' ) );
	}

	/**
	 * The content of each column.
	 *
	 * @param  array $item The current item in the list.
	 * @param  string $column_name The key of the current column.
	 *
	 * @since  1.0.0
	 * @return string              Output for the current column.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'check-column':
				return '&nbsp;';
			case 'status':
				return $item[ $column_name ];
				break;
		}
	}

	public function get_item_data( $item_id ) {
		global $wpdb;
		$data = array();

		if ( isset( $this->meta_data[ $item_id ] ) ) {
			$data = $this->meta_data[ $item_id ];
		} else {
			$this->meta_data[ $item_id ] = XLWCFG_Common::get_post_meta_data( $item_id );
			$data                        = $this->meta_data[ $item_id ];
		}

		return $data;
	}

	/**
	 * Content for the "product_name" column.
	 *
	 * @param  array $item The current item.
	 *
	 * @since  1.0.0
	 * @return string       The content of this column.
	 */
	public function column_status( $item ) {
		$output = "";
		if ( $item['status'] == XLWCFG_SHORT_SLUG . 'disabled' ) {
			$output = __( 'Deactivated', 'bonanza-woocommerce-free-gifts-lite' );
		} else {
			$output = __( 'Activated', 'bonanza-woocommerce-free-gifts-lite' );
		}

		return wpautop( $output );
	}


	public function column_name( $item ) {
		$edit_link     = get_edit_post_link( $item['id'] );
		$column_string = '<strong>';
		if ( $item['status'] == "trash" ) {
			$column_string .= '' . _draft_or_post_title( $item['id'] ) . '' . _post_states( get_post( $item['id'] ) ) . ' (#' . $item['id'] . ')</strong>';
		} else {
			$column_string .= '<a href="' . $edit_link . '" class="row-title">' . _draft_or_post_title( $item['id'] ) . ' (#' . $item['id'] . ')</a>' . _post_states( get_post( $item['id'] ) ) . '</strong>';
		}
		$column_string .= '<div class=\'row-actions\'>';
		$count         = count( $item['row_actions'] );
		foreach ( $item['row_actions'] as $k => $action ) {
			$column_string .= '<span class="' . $action['action'] . '"><a href="' . $action['link'] . '" ' . $action['attrs'] . '>' . $action['text'] . '</a>';
			if ( $k < $count - 1 ) {
				$column_string .= " | ";
			}
			$column_string .= "</span>";
		}

		return wpautop( $column_string );
	}

	public function column_offer( $item ) {
		$item_meta     = XLWCFG_Common::get_post_meta_data( $item['id'] );
		$repeat        = ucfirst( $item_meta['repeat'] );
		$column_string = "Buy {$item_meta['gift_qty_buy']} & Get {$item_meta['gift_qty_get']}<br/>Repeat: {$repeat}";

		return wpautop( $column_string );
	}

	public function column_get_prod( $item ) {
		$item_meta = XLWCFG_Common::get_post_meta_data( $item['id'] );
		if ( ! isset( $item_meta['get_products'] ) || ! is_array( $item_meta['get_products'] ) || count( $item_meta['get_products'] ) == 0 ) {
			return __( 'No product assigned', 'bonanza-woocommerce-free-gifts-lite' );
		}

		$get_products  = $item_meta['get_products'];
		$column_string = array();

		foreach ( $get_products as $pro ) {
			$column_string[] = '- ' . get_the_title( $pro );
		}

		if ( count( $column_string ) > 0 ) {
			return implode( "<br/>", $column_string );
		}

		return '';
	}

	public function column_scheduled( $item ) {
		$item_meta = XLWCFG_Common::get_post_meta_data( $item['id'] );
		$string    = 'Forever';

		return wpautop( $string );
	}

	public function column_display( $item ) {
		$item_meta = XLWCFG_Common::get_post_meta_data( $item['id'] );

		$string = '';
		$string .= ( isset( $item_meta['enable_single_pro'] ) && ! empty( $item_meta['enable_single_pro'] ) ) ? 'Single Product: ' . ucfirst( $item_meta['enable_single_pro'] ) : '';

		return wpautop( $string );
	}


	/**
	 * Retrieve an array of possible bulk actions.
	 * @since  1.0.0
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array();

		return $actions;
	}

	/**
	 * Prepare an array of items to be listed.
	 * @since  1.0.0
	 * @return array Prepared items.
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$total_items = count( $this->data );

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $total_items //WE have to determine how many items to show on a page
		) );
		$this->items = $this->data;
	}

	/**
	 * Retrieve an array of columns for the list table.
	 * @since  1.0.0
	 * @return array Key => Value pairs.
	 */
	public function get_columns() {
		$columns = array(
			'name'      => __( 'Title', 'bonanza-woocommerce-free-gifts-lite' ),
			'scheduled' => __( 'Schedule', 'bonanza-woocommerce-free-gifts-lite' ),
			'offer'     => __( 'Offer', 'bonanza-woocommerce-free-gifts-lite' ),
			'get_prod'  => __( 'Get Products', 'bonanza-woocommerce-free-gifts-lite' ),
			'display'   => __( 'Display', 'bonanza-woocommerce-free-gifts-lite' ),
			'status'    => __( 'Status', 'bonanza-woocommerce-free-gifts-lite' ),

		);

		return $columns;
	}

	/**
	 * Retrieve an array of sortable columns.
	 * @since  1.0.0
	 * @return array
	 */
	public function get_sortable_columns() {
//        return array("Running","Finished","Schedule","Deactivated");
		return array(
			'running'     => array( 'Running', true ),
			'finished'    => array( 'Finished', true ),
			'schedule'    => array( 'Schedule', true ),
			'deactivated' => array( 'Deactivated', true ),
		);
	}

	public function get_table_classes() {
		$get_default_classes = parent::get_table_classes();
		array_push( $get_default_classes, 'xlwcfg-instance-table' );

		return $get_default_classes;
	}

	public function single_row( $item ) {
		$tr_class = 'xlwcfg_trigger_active';
		if ( $item['status'] == XLWCFG_SHORT_SLUG . 'disabled' ) {
			$tr_class = 'xlwcfg_trigger_deactive';
		}
		echo '<tr class="' . $tr_class . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	public function get_cp_batch_data( $cp_id ) {
		$output = array(
			'date'   => '',
			'action' => '',
		);
		if ( isset( $this->batch_data[ $cp_id ] ) ) {
			$output = $this->batch_data[ $cp_id ];
		} else {
			$output                     = get_option( 'xlwcfg-process-action-' . $cp_id, array(
				'date'         => '',
				'action'       => '',
				'current_step' => 1,
			) );
			$this->batch_data[ $cp_id ] = $output;
		}

		return $output;
	}

	public function column_check_column( $item ) {

		return ' <input id="cb-select-' . $item['id'] . '" class="xlwccfg_batch_columns" type="checkbox" name="cp_id[]" value="' . $item['id'] . '" style="margin-left:8px;">';
	}

	/**
	 * Print column headers, accounting for hidden and sortable columns.
	 *
	 * @since 3.1.0
	 * @access public
	 *
	 * @staticvar int $cb_counter
	 *
	 * @param bool $with_id Whether to set the id attribute or not
	 */
	public function print_column_headersss( $with_id = true ) {
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();


		$sortable['status'] = array( 'status', 0 );
		$current_url        = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url        = remove_query_arg( 'paged', $current_url );

		if ( isset( $_GET['orderby'] ) ) {
			$current_orderby = $_GET['orderby'];
		} else {
			$current_orderby = '';
		}

		if ( isset( $_GET['order'] ) && 'desc' === $_GET['order'] ) {
			$current_order = 'desc';
		} else {
			$current_order = 'asc';
		}

		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;
			$columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __( 'Select All' ) . '</label>' . '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
			$cb_counter ++;
		}

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			if ( in_array( $column_key, $hidden ) ) {
				$class[] = 'hidden';
			}

			if ( 'cb' === $column_key ) {
				$class[] = 'check-column';
			} elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) ) {
				$class[] = 'num';
			}

			if ( $column_key === $primary ) {
				$class[] = 'column-primary';
			}

			if ( isset( $sortable[ $column_key ] ) ) {
				list( $orderby, $desc_first ) = $sortable[ $column_key ];

				if ( $current_orderby === $orderby ) {
					$order   = 'asc' === $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					$order   = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}

				$column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
			}

			$tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
			$scope = ( 'th' === $tag ) ? 'scope="col"' : '';
			$id    = $with_id ? "id='$column_key'" : '';

			if ( ! empty( $class ) ) {
				$class = "class='" . join( ' ', $class ) . "'";
			}

			echo "<$tag $scope $id $class>$column_display_name</$tag>";
		}
	}

}
