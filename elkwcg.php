<?php
/**
 * @wordpress-plugin
 * Plugin Name:				ELK WooCommerce Goodies
 * Plugin URI:				https://github.com/kokiddp/elkwcg
 * Description:				This plugin adds some useful functions to WooCommerce
 * Version:					1.0.0
 * Requires at least:		4.6
 * Tested up to:			5.7.2
 * Requires PHP:			7.1
 * WC requires at least:	3.0.2
 * WC tested up to:			5.3.0
 * Author:					ELK-Lab
 * Author URI:				https://www.elk-lab.com
 * License:					GPL-2.0+
 * License URI:				http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:				elkwcg
 * Domain Path:				/languages
 */

if ( !defined( 'ABSPATH' ) || !defined( 'WPINC' ) ) {
    die;
}

add_action( 'init', 'elkwcg_load_textdomain' );  
function elkwcg_load_textdomain() {
	load_plugin_textdomain( 'elkwcg', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}

add_action( 'admin_init', 'elkwcg_require_woocommerce' );
function elkwcg_require_woocommerce() {
    if ( is_admin() && current_user_can( 'activate_plugins' ) ) {
    	if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	        add_action( 'admin_notices', 'elkwcg_no_woocommerce_notice' );
	        deactivate_plugins( plugin_basename( __FILE__ ) ); 

	        if ( isset( $_GET['activate'] ) ) {
	            unset( $_GET['activate'] );
	        }
	    }
    }
}

function elkwcg_no_woocommerce_notice(){
    ?><div class="error"><p><?= __( 'WooCommerce is required in order to use ELK WooCommerce Goodies', 'elkwcg' ) ?></p></div><?php
}

add_filter( 'woocommerce_get_sections_products', 'elkwcg_add_settings_section' );
function elkwcg_add_settings_section( $sections ) {	
	$sections['elkwcg'] = __( 'ELK WooCommerce Goodies', 'elkwcg' );
	return $sections;	
}

add_filter( 'woocommerce_get_settings_products', 'elkwcg_settings_section', 10, 2 );
function elkwcg_settings_section( $settings, $current_section ) {
	if ( $current_section == 'elkwcg' ) {
		$elkwcg_settings = array();

		$elkwcg_settings[] = array(
			'name' => __( 'ELK WooCommerce Goodies Settings', 'elkwcg' ),
			'type' => 'title',
			'desc' => __( 'The following options are used to configure ELK WooCommerce Goodies', 'elkwcg' ),
			'id' => 'elkwcg'
		);

		$elkwcg_settings[] = array(
			'name'     => __( 'Minimum order', 'elkwcg' ),
			'desc_tip' => __( 'Insert the minimum cart value to enable order. Leave blank to disable', 'elkwcg' ),
			'id'       => 'elkwcg_min_value',
			'type'     => 'number'
		);

		$elkwcg_settings[] = array(
			'name'     => __( 'Free shipping available at', 'elkwcg' ),
			'desc_tip' => __( 'Insert the cart value that triggers free shipping to enable a message about it\'s availability . Leave blank to disable', 'elkwcg' ),
			'id'       => 'elkwcg_free_shipping_value',
			'type'     => 'number'
		);

		$elkwcg_settings[] = array(
			'name'     => __( 'Minimum items', 'elkwcg' ),
			'desc_tip' => __( 'Insert the minimum number of items per order. Leave blank to disable', 'elkwcg' ),
			'id'       => 'elkwcg_min_items',
			'type'     => 'number'
		);

		$elkwcg_settings[] = array(
			'name'     => __( 'Multiple of', 'elkwcg' ),
			'desc_tip' => __( 'Insert the number the items must be multiple of. Leave blank to disable', 'elkwcg' ),
			'id'       => 'elkwcg_multiple',
			'type'     => 'number'
		);
		
		$elkwcg_settings[] = array(
			'type' => 'sectionend',
			'id' => 'elkwcg'
		);
		return $elkwcg_settings;
	}
	else {
		return $settings;
	}
}

add_action( 'woocommerce_check_cart_items', 'elkwcg_minimum_order_amount' ); 
function elkwcg_minimum_order_amount() {
	if( is_cart() || is_checkout() ) {
		$minimum = intval( get_option( 'elkwcg_min_items' ) != null ? get_option( 'elkwcg_min_items' ) : 1 );
		$multiple = intval( get_option( 'elkwcg_multiple' ) != null ? get_option( 'elkwcg_multiple' ) : 1 );
		$cart_count = intval( WC()->cart->get_cart_contents_count() );

		if ( $cart_count < $minimum ) {
			wc_add_notice( 
				sprintf(
					__( 'In your cart there are %1$s items — you must have at least %2$s items in your cart to place your order. <a href="%3$s">Back to Store</a>' , 'elkwcg' ), 
					$cart_count, 
					$minimum,
					get_permalink( wc_get_page_id( 'shop' ) )
				),
				'error' 
			);
		}
		if ( ( $cart_count % $multiple ) > 0 ) {
			wc_add_notice( 
				sprintf(
					__( 'In your cart there are %1$s items — you must have a multiple of %2$s items in your cart to place your order. <a href="%3$s">Back to Store</a>' , 'elkwcg' ), 
					$cart_count, 
					$minimum,
					get_permalink( wc_get_page_id( 'shop' ) )
				),
				'error' 
			);
		}
	}
}

add_action( 'woocommerce_check_cart_items', 'elkwcg_minimum_order_value' ); 
function elkwcg_minimum_order_value() {
	if( is_cart() || is_checkout() ) {
		$minimum = floatval( get_option( 'elkwcg_min_value' ) != null ? get_option( 'elkwcg_min_value' ) : 1 );
		$free_shipping = floatval( get_option( 'elkwcg_free_shipping_value' ) != null ? get_option( 'elkwcg_free_shipping_value' ) : 1 );
		$cart_total = floatval( WC()->cart->total - WC()->cart->get_shipping_total() );

		if ( $cart_total < $minimum ) {
			wc_add_notice( 
				sprintf(
					__( 'Your cart total is %1$s — the minimum amount to place an order is %2$s. <a href="%3$s">Back to Store</a>' , 'elkwcg' ), 
					wc_price( $cart_total ),
					wc_price( $minimum ),
					get_permalink( wc_get_page_id( 'shop' ) )
				),
				'error' 
			);
		}
		if ( $cart_total < $free_shipping ) {
			wc_add_notice( 
				sprintf(
					__( 'Your cart total is %1$s — if you place an order of at least %2$s you will be entitled to get free shipping. <a href="%3$s">Back to Store?</a>' , 'elkwcg' ), 
					wc_price( $cart_total ),
					wc_price( $free_shipping ),
					get_permalink( wc_get_page_id( 'shop' ) )
				),
				'notice' 
			);
		}
	}
}