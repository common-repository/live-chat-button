<?php

namespace AsanaPlugins\WhatsApp\API;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WhatsApp\Models\ItemsModel;
use AsanaPlugins\WhatsApp\Models\WhatsAppModel;
use function AsanaPlugins\WhatsApp\get_plugin;

class Items extends BaseController {

	protected $rest_base = 'items';

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Search items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function search_items( $request ) {
		if ( empty( $request['search'] ) ) {
			return new \WP_Error( 'asnp_ewhatsapp_search_term_required', __( 'Search term is required.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		if ( empty( $request['type'] ) ) {
			return new \WP_Error( 'asnp_ewhatsapp_type_required', __( 'Type is required.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		$search = sanitize_text_field( wp_unslash( $request['search'] ) );
		if ( empty( $search ) ) {
			return new \WP_Error( 'asnp_ewhatsapp_search_term_required', __( 'Search term is required.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		$items = [];
		if ( 'products' === $request['type'] ) {
			try {
				$items = ItemsModel::search_products(
					array(
						'search' => $search,
						'type'   => array(
							'simple',
							'variation',
						),
					)
				);
			} catch ( \Exception $e ) {
				return new \WP_Error( 'asnp_ewhatsapp_error_in_searching_items', $e->getMessage(), array( 'status' => 400 ) );
			}
		} elseif ( 'accounts' === $request['type'] ) {
			$items = ItemsModel::get_accounts( [ 'name' => $search ] );
		} elseif ( 'whatsapp' === $request['type'] ) {
			$model = get_plugin()->container()->get( WhatsAppModel::class );
			$whatsapps = $model->get_items( [ 'name' => $search ] );
			if ( ! empty( $whatsapps ) ) {
				foreach ( $whatsapps as &$whatsapp ) {
					if ( ! empty( $whatsapp->accounts ) ) {
						$items[] = [
							'value' => absint( $whatsapp->id ),
							'label' => sanitize_text_field( $whatsapp->name ),
						];
					}
				}
			}
		} else {
			$items = apply_filters( 'asnp_ewhatsapp_items_api_' . __FUNCTION__, $items, $search, $request );
		}

		return new \WP_REST_Response(
			array(
				'items' => $items,
			)
		);
	}

	/**
	 * Get items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		if ( empty( $request['items'] ) ) {
			return new \WP_Error( 'asnp_ewhatsapp_items_required', __( 'Items is required.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		if ( empty( $request['type'] ) ) {
			return new \WP_Error( 'asnp_ewhatsapp_type_required', __( 'Type is required.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		$items = $request['items'];
		if ( ! is_array( $items ) ) {
			$items = explode( ',', $items );
		}

		if ( 'products' === $request['type'] ) {
			try {
				$items = ItemsModel::get_products(
					array(
						'type'    => array( 'simple', 'variation' ),
						'include' => array_filter( array_map( 'absint', $items ) ),
					)
				);
			} catch ( \Exception $e ) {
				return new \WP_Error( 'asnp_ewhatsapp_error_in_getting_items', $e->getMessage(), array( 'status' => 400 ) );
			}
		} elseif ( 'accounts' === $request['type'] ) {
			$items = ItemsModel::get_accounts( [ 'id' => array_filter( array_map( 'absint', $items ) ) ] );
		}  elseif ( 'whatsapp' === $request['type'] ) {
			$model = get_plugin()->container()->get( WhatsAppModel::class );
			$items = $model->get_items( [ 'id' => array_filter( array_map( 'absint', $items ) ) ] );
			$items = array_map( function ( $item ) {
				return [
					'value' => absint( $item->id ),
					'label' => sanitize_text_field( $item->name ),
				];
			}, $items );
		} else {
			$items = apply_filters( 'asnp_ewhatsapp_items_api_' . __FUNCTION__, [], $items, $request );
		}

		return new \WP_REST_Response(
			array(
				'items' => $items,
			)
		);
	}

}
