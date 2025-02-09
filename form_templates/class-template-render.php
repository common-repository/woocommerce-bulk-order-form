<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WooCommerce_Bulk_Order_Form_Template_UI' ) ):

class WooCommerce_Bulk_Order_Form_Template_UI {

	/**
	 * @var array
	 */
	public $atts;

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var array
	 */
	public $attr;

	public function __construct( array $atts, string $type ) {
		$this->atts = $atts;
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function render() {
		$this->attr              = $this->add_extras( $this->atts );
		$this->attr['formid']    = wp_rand() . time();
		$this->atts['formid']    = $this->attr['formid'];
		$this->attr['engine']    = $this;
		$this->attr['reqfields'] = $this->get_hidden_fields();

		$render = '';
		$render .= $this->get_header();
		$render .= $this->get_table_header();
		$render .= $this->get_content();
		$render .= $this->get_table_footer();
		$render .= $this->get_footer();
		return $render;
	}

	/**
	 * @param array $atts
	 *
	 * @return array
	 */
	public function add_extras( array $atts ) {
		$atts['is_standard']  = false;
		$atts['is_variation'] = true;

		if ( 'standard' === $atts['template'] ) {
			$atts['is_standard']  = true;
			$atts['is_variation'] = false;
		}

		return $atts;
	}

	public function get_hidden_fields(): string {
		$exclude = array( 'price_label', 'product_label', 'quantity_label', 'variation_label', 'add_rows' );
		$output  = array();

		foreach ( $this->atts as $key => $val ) {
			if ( in_array( $key, $exclude ) ) {
				continue;
			}
			if ( is_array( $val ) ) {
				$val = implode( ',', $val );
			}
			$output[] = '<input id="' . $key . '" type="hidden" name="wcbulkorder[settings][' . $key . ']" value="' . $val . '"  />';
		}

		$output = implode( ' ', $output );

		$formid = $this->atts['formid'];
		$output = '<div class="form_hidden_fileds" id="form_hidden_fileds_' . $formid . '"> ' . $output . '</div>';
		$output = $output . '<input type="hidden" name="action" value="wcbulkorder_product_buy_now" id="form_action"  />';
		return $output;
	}

	/**
	 * @return false|string
	 */
	public function get_header() {
		ob_start();
		wc_bof_get_template( 'form-header.php', $this->attr );
		$output = ob_get_clean();
		return $output;
	}

	/**
	 * @return false|string
	 */
	public function get_table_header() {
		ob_start();
		wc_bof_get_template( 'table-header.php', $this->attr );
		$output = ob_get_clean();
		return $output;
	}

	/**
	 * @return string
	 */
	public function get_content() {
		$output                  = '';
		$count                   = 1;
		$max                     = $this->attr['rows'];
		$atts                    = $this->attr;
		$atts['currency_symbol'] = get_woocommerce_currency_symbol();
		while ( $count <= $max ) {
			$atts['count'] = $count;
			ob_start();
			wc_bof_get_template( 'form-content.php', $atts );
			$output .= ob_get_clean();
			$count++;
		}

		$atts['count'] = 'removeHidden';
		ob_start();
		wc_bof_get_template( 'form-content.php', $atts );
		$output .= ob_get_clean();
		return $output;
	}

	/**
	 * @return false|string
	 */
	public function get_table_footer() {
		ob_start();
		wc_bof_get_template( 'table-footer.php', $this->attr );
		$output = ob_get_clean();
		return $output;
	}

	/**
	 * @return false|string
	 */
	public function get_footer() {
		ob_start();
		wc_bof_get_template( 'form-footer.php', $this->attr );
		$output = ob_get_clean();
		return $output;
	}

	/**
	 * @param string $count
	 * @param bool $echo
	 *
	 * @return string|null
	 */
	public function get_product_column( string $count, bool $echo = true ) {
		/*$return = '<i class="bulkorder_spinner"></i>
		<input type="hidden" name="wcbulkorder[wcbof_products]['.$count.'][product_id]" value="" class="product_id" />
		<input type="text" name="wcbulkorder[wcbof_products]['.$count.'][product_name]" value="" class="product_name" />';*/

		if ( apply_filters( 'wc_bulk_order_form_show_search_placeholder', 1 === absint( $count ) ) ) {
			$placeholder = apply_filters( 'wc_bulk_order_form_search_placeholder', esc_attr__( 'Search for a product&hellip;', 'woocommerce-bulk-order-form' ) );
		} else {
			$placeholder = '';
		}

		$select_style = $count === 'removeHidden' ? 'display:none;' : '';

		$return = '<i class="bulkorder_spinner"></i><input type="hidden" name="wcbulkorder[wcbof_products][' . esc_attr( $count ) . '][product_id]" value="" class="product_id" />
		<select name="wcbulkorder[wcbof_products][' . esc_attr( $count ) . '][product_name]"  class="product_name product_name_search_field" placeholder="' . esc_attr( $placeholder ) . '" style="' . esc_attr( $select_style ) . '"></select>';

		// set default extra filter args
		$withImg = true;
		$name_only = false;
		$return = apply_filters( 'wc_bulk_order_form_product_column', $return, $count, $withImg, $name_only, null );

		if ( $echo ) {
			echo wp_kses( $return, WC_BOF_ALLOWED_HTML );
			return null;
		}
		return $return;
	}

	/**
	 * @param string $count
	 * @param bool $echo
	 *
	 * @return bool|null
	 */
	public function get_variation_column( string $count, bool $echo = true ) {
		$return = '<div class="attributes" id="attribute_' . esc_attr( $count ) . '"  data-count="' . esc_attr( $count ) . '">
	<input data-count="' . esc_attr( $count ) . '" type="hidden" name="wcbulkorder[wcbof_products][' . esc_attr( $count ) . '][variation_name]" value="" class="variation_name" />
</div>';

		$return = apply_filters( 'wc_bulk_order_form_variation_column', $return, $count, null );

		if ( $echo ) {
			echo wp_kses( $return, WC_BOF_ALLOWED_HTML );
			return null;
		}
		return true;
	}

	/**
	 * @param string $count
	 * @param string $currency_symbol
	 * @param bool $echo
	 *
	 * @return string|null
	 */
	public function get_quantity_column( string $count, string $currency_symbol, bool $echo = true ) {
		$return = '<input type="number" data-count="' . esc_attr( $count ) . '" data-currency="' . esc_attr( $currency_symbol ) . '" 
			min="0" max="9999" step="1" name="wcbulkorder[wcbof_products][' . esc_attr( $count ) . '][product_qty]" value="" class="product_qty" />';

		$return = apply_filters( 'wc_bulk_order_form_quantity_column', $return, $count, $currency_symbol, null );

		if ( $echo ) {
			echo wp_kses( $return, WC_BOF_ALLOWED_HTML );
			return null;
		}
		return $return;
	}

	/**
	 * @param string $count
	 * @param bool $echo
	 *
	 * @return string
	 */
	public function get_price_column( string $count, bool $echo = true ) {
		$return = '<span class="amount"></span>';

		$return = apply_filters( 'wc_bulk_order_form_variation_column', $return, $count, null );

		if ( $echo ) {
			echo wp_kses( $return, WC_BOF_ALLOWED_HTML );
		}
		return $return;
	}

} // end class WooCommerce_Bulk_Order_Form_Template_UI

endif; // end class_exists()
