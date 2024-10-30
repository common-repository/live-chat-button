<?php

namespace AsanaPlugins\WhatsApp\WooCommerce;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WhatsApp;

abstract class BaseWooCommerceHooks {

	public static function init() {
		add_action( 'woocommerce_init', array( static::class, 'position_hooks' ) );
	}

	public static function display_whatsapp() {
		global $product;
		if ( ! $product ) {
			return;
		}

		$whatsapp = WhatsApp\get_woocommerce_product_active_whatsapp( $product );
		if ( ! $whatsapp || empty( $whatsapp->id ) ) {
			return;
		}

		echo '<div id="asnp-easy-whatsapp-' . esc_attr( $whatsapp->id ) . '"></div>';
	}

	public static function position_hooks() {
		$add_to_cart_priority = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart' );
		$add_to_cart_priority ?
			add_action( 'woocommerce_single_product_summary', array( static::class, 'display_whatsapp' ), $add_to_cart_priority + 1 ) :
			add_action( 'woocommerce_single_product_summary', array( static::class, 'display_whatsapp' ), 31 );
	}
}
