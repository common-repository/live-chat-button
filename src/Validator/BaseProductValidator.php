<?php
namespace AsanaPlugins\WhatsApp\Validator;

defined( 'ABSPATH' ) || exit;

abstract class BaseProductValidator {

	public static function valid_product( $whatsapp, $product ) {
		if ( ! $whatsapp || empty( $whatsapp->woocommerceItems ) ) {
			return false;
		}

		$match_mode = isset( $whatsapp->woocommmerceItemsConditions ) && in_array( $whatsapp->woocommmerceItemsConditions, [ 'any', 'all' ], true ) ? $whatsapp->woocommmerceItemsConditions : 'any';
		foreach ( $whatsapp->woocommerceItems as $item ) {
			if ( 'any' === $match_mode && static::is_valid( $item, $product ) ) {
				return true;
			} elseif ( 'all' === $match_mode && ! static::is_valid( $item, $product ) ) {
				return false;
			}
		}

		return 'all' === $match_mode;
	}

	public static function is_valid( $item, $product ) {
		if ( empty( $item ) || ! $product ) {
			return false;
		}

		if ( ! isset( $item['type'] ) ) {
			return false;
		}

		$is_valid = false;
		if ( is_callable( [ static::class, 'is_valid_' . $item['type'] ] ) ) {
			$is_valid = static::{'is_valid_' . $item['type']}( $item, $product );
		}

		return apply_filters(
			'asnp_ewhatsapp_product_validator_is_valid_' . $item['type'],
			$is_valid,
			$item,
			$product
		);
	}

	public static function is_valid_products( $item, $product ) {
		if ( empty( $item ) || ! $product ) {
			return false;
		}

		if ( empty( $item['items'] ) ) {
			return false;
		}

		$product = is_numeric( $product ) ? $product : $product->get_id();
		if ( 0 >= $product ) {
			return false;
		}

		return in_array( $product, static::get_items( $item['items'] ) );
	}

	protected static function get_items( $items ) {
		if ( empty( $items ) ) {
			return [];
		}

		return array_filter( array_map( 'absint', explode( ',', $items ) ) );
	}

}
