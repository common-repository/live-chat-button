<?php

namespace AsanaPlugins\WhatsApp\Models;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WhatsApp;
use AsanaPlugins\WhatsApp\Models\AccountModel;

class ItemsModel {

	public static function search_products( array $args = array() ) {
		if ( empty( $args['search'] ) ) {
			throw new \Exception( 'Search term is required to search products.' );
		}

		$data_store = \WC_Data_Store::load( 'product' );

		if ( version_compare( WC_VERSION, '3.5.0', '>=' ) ) {
			$products = $data_store->search_products( wc_clean( wp_unslash( $args['search'] ) ), '', true, true, 50 );
		} else {
			$products = $data_store->search_products( wc_clean( wp_unslash( $args['search'] ) ), '', true, true );
		}

		return ! empty( $products ) ? self::prepare_product_items( $products, ! empty( $args['type'] ) ? $args['type'] : array() ) : array();
	}

	public static function get_products( array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'status'   => array( 'private', 'publish' ),
				'type'     => array( 'simple', 'variation' ),
				'limit'    => -1,
				'orderby'  => array(
					'menu_order' => 'ASC',
					'ID'         => 'DESC',
				),
				'paginate' => false,
			)
		);

		$products = wc_get_products( $args );
		return ! empty( $products ) ? self::prepare_product_items( $products, $args['type'] ) : array();
	}

	public static function get_accounts( array $args = array() ) {
		$args['output'] = empty( $args['id'] ) ? OBJECT : OBJECT_K;
		$model          = WhatsApp\get_plugin()->container()->get( AccountModel::class );
		$items          = $model->get_items( $args );
		if ( empty( $items ) ) {
			return [];
		}

		if ( empty( $args['id'] ) ) {
			foreach ( $items as &$item ) {
				$item->value = (int) $item->id;
				$item->label = sanitize_text_field( $item->name );
			}
			return $items;
		}

		// Keep ordering based on given IDs.
		$accounts = [];
		foreach ( $args['id'] as $id ) {
			if ( isset( $items[ $id ] ) ) {
				$items[ $id ]->value = (int) $items[ $id ]->id;
				$items[ $id ]->label = sanitize_text_field( $items[ $id ]->name );
				$accounts[]          = $items[ $id ];
			}
		}
		return $accounts;
	}

	protected static function prepare_product_items( array $products, $allowed_types = array( 'simple', 'variation' ) ) {
		if ( empty( $products ) ) {
			return array();
		}

		$products_select = array();
		foreach ( $products as $product ) {
			if ( is_numeric( $product ) ) {
				$product = wc_get_product( $product );
			}

			if ( ! WhatsApp\wc_products_array_filter_readable( $product ) ) {
				continue;
			}

			if ( ! empty( $allowed_types ) && ! in_array( $product->get_type(), $allowed_types ) ) {
				continue;
			}

			if ( $product->get_sku() ) {
				$identifier = $product->get_sku();
			} else {
				$identifier = '#' . $product->get_id();
			}

			if ( 'variation' === $product->get_type() ) {
				$formatted_variation_list = wc_get_formatted_variation( $product, true );
				$text                     = sprintf( '%2$s (%1$s)', $identifier, $product->get_title() ) . ' ' . $formatted_variation_list;
			} else {
				$text = sprintf( '%2$s (%1$s)', $identifier, $product->get_title() );
			}

			$products_select[] = (object) array(
				'value' => $product->get_id(),
				'label' => $text,
			);
		}

		return $products_select;
	}

}
