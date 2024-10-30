<?php

namespace AsanaPlugins\WhatsApp\API;

use AsanaPlugins\WhatsApp\Models\WhatsAppModel;
use AsanaPlugins\WhatsApp\Models\ItemsModel;
use function AsanaPlugins\WhatsApp\get_plugin;

defined( 'ABSPATH' ) || exit;

class WhatsApp extends BaseController {

	protected $rest_base = 'whatsapp';

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'asnp-easy-whatsapp' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/duplicate' . '/(?P<id>[\d]+)',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'asnp-easy-whatsapp' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'duplicate_item' ),
					'permission_callback' => array( $this, 'duplicate_item_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/reorder',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'reorder_items' ),
					'permission_callback' => array( $this, 'reorder_items_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Get a collection of posts.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$model = get_plugin()->container()->get( WhatsAppModel::class );
		$items = $model->get_items();
		if ( ! empty( $items ) ) {
			foreach ( $items as &$item ) {
				if ( ! empty( $item->accounts ) ) {
					$item->accounts = ItemsModel::get_accounts( [ 'id' => array_filter( array_map( 'absint', $item->accounts ) ) ] );
				}
			}
		}

		return new \WP_REST_Response(
			array(
				'items' => $items,
			)
		);
	}

	public function get_item( $request ) {
		$id = isset( $request['id'] ) ? (int) $request['id'] : 0;
		if ( 0 >= $id ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_whatsapp_exists', __( 'Invalid item ID.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		$model = get_plugin()->container()->get( WhatsAppModel::class );
		$item  = $model->get_item( $id );
		if ( ! $item ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_whatsapp_exists', __( 'Invalid item ID.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		if ( ! empty( $item->accounts ) ) {
			$item->accounts = ItemsModel::get_accounts( [ 'id' => array_filter( array_map( 'absint', $item->accounts ) ) ] );
		}

		return new \WP_REST_Response(
			array(
				'item' => $item,
			)
		);
	}

	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_whatsapp_exists', __( 'Cannot create an existing whatsapp.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		try {
			$item = $this->save_item( $request );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest', $e->getMessage(), array( 'status' => 400 ) );
		}

		do_action( 'asnp_ewhatsapp_whatsapp_created', $item, $request );

		return new \WP_REST_Response(
			array(
				'item' => $item,
			)
		);
	}

	public function update_item( $request ) {
		$id = isset( $request['id'] ) ? (int) $request['id'] : 0;
		if ( ! $id || 0 >= $id ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_invalid_id', __( 'ID is invalid.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		$model = get_plugin()->container()->get( WhatsAppModel::class );
		$item  = $model->get_item( $id );
		if ( ! $item ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_whatsapp_exists', __( 'Invalid item ID.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		try {
			$item = $this->save_item( $request );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest', $e->getMessage(), array( 'status' => 400 ) );
		}

		do_action( 'asnp_ewhatsapp_whatsapp_updated', $item, $request );

		return new \WP_REST_Response(
			array(
				'item' => $item,
			)
		);
	}

	public function delete_item( $request ) {
		$id = isset( $request['id'] ) ? (int) $request['id'] : 0;
		if ( ! $id || 0 >= $id ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_invalid_id', __( 'ID is invalid.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		$model = get_plugin()->container()->get( WhatsAppModel::class );
		$item  = $model->get_item( $id );
		if ( ! $item ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_whatsapp_exists', __( 'Invalid item ID.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		$delete = $model->delete( $id );
		if ( ! $delete ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_cannot_delete_item', __( 'Cannot delete item.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		do_action( 'asnp_ewhatsapp_whatsapp_deleted', $id, $request );

		return new \WP_REST_Response(
			array(
				'success' => 1,
				'id'      => $id,
			)
		);
	}

	public function duplicate_item( $request ) {
		$id = isset( $request['id'] ) ? (int) $request['id'] : 0;
		if ( ! $id || 0 >= $id ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_invalid_id', __( 'ID is invalid.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		$model   = get_plugin()->container()->get( WhatsAppModel::class );
		$item_id = $model->duplicate( $id );
		if ( ! $item_id ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_cannot_duplicate_item', __( 'Cannot duplicate item.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		do_action( 'asnp_ewhatsapp_whatsapp_duplicated', $item_id, $id, $request );

		return new \WP_REST_Response(
			array(
				'id' => $item_id,
			)
		);
	}

	public function reorder_items( $request ) {
		$items = ! empty( $request['items'] ) ? map_deep( $request['items'], 'intval' ) : array();
		if ( empty( $items ) ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_invalid_items', __( 'Items are invalid.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		$model   = get_plugin()->container()->get( WhatsAppModel::class );
		$reorder = $model->update_ordering( $items );
		if ( ! $reorder ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_cannot_reorder_items', __( 'Cannot reorder items.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		return new \WP_REST_Response(
			array(
				'success' => 1,
				'message' => __( 'Reordered successfully.', 'asnp-easy-whatsapp' ),
			)
		);
	}

	/**
	 * Save a single whatsapp.
	 *
	 * @return int Saved item ID.
	 *
	 * @throws \Exception
	 */
	protected function save_item( $request ) {
		$data = [];
		if ( isset( $request['name'] ) ) {
			$data['name'] = sanitize_text_field( $request['name'] );
		}

		if ( isset( $request['status'] ) ) {
			$data['status'] = (int) $request['status'];
		}

		if ( ! empty( $request['type'] ) ) {
			$data['type'] = sanitize_text_field( $request['type'] );
		}

		if ( ! empty( $request['id'] ) && 0 < (int) $request['id'] ) {
			$data['id'] = (int) $request['id'];
		} else {
			$data['name']   = ! empty( $data['name'] ) ? $data['name'] : __( 'WhatsApp', 'asnp-easy-whatsapp' );
			$data['status'] = isset( $data['status'] ) ? $data['status'] : 1;
		}

		$model = get_plugin()->container()->get( WhatsAppModel::class );

		$options = $this->get_options( $request );
		if ( ! empty( $options ) ) {
			$data['options'] = maybe_serialize( $options );
		}

		$id = $model->add( $data );
		if ( ! $id || 0 >= $id ) {
			throw new \Exception( __( 'Error occurred in saving item.', 'asnp-easy-whatsapp' ) );
		}

		$item = $model->get_item( $id );
		if ( ! empty( $item->accounts ) ) {
			$item->accounts = ItemsModel::get_accounts( [ 'id' => array_filter( array_map( 'absint', $item->accounts ) ) ] );
		}

		do_action( 'asnp_ewhatsapp_whatsapp_saved', $item, $request );

		return $item;
	}

	protected function get_options( $request ) {
		$id = isset( $request['id'] ) ? (int) $request['id'] : 0;

		$options = array();

		$defaults = array(
			'accounts'                => array(),
			'bubbleText'              => '',
			'chatHeaderName'          => 'John Doe',
			'buttonText'              => __( 'Start Chat', 'asnp-easy-whatsapp' ),
			'chatHeaderSelectPicture' => '',
			'chatHeaderReplyTimeText' => __( 'Typically replies in minutes', 'asnp-easy-whatsapp' ),
			'position'                => 'right',
			'welcomeMessage'          => __( 'Hi there', 'asnp-easy-whatsapp' ),
			'woocommerceItems'        => array(),
			'pagesUrl'                => array(),
			'excludedPagesUrl'        => array(),
		);

		foreach ( $request->get_params() as $key => $value ) {
			// Excluded fields.
			if ( in_array( $key, array( '_locale', 'id', 'name', 'status', 'type' ) ) ) {
				continue;
			}

			switch ( $key ) {
				case 'accounts':
					if ( empty( $value ) ) {
						throw new \Exception( __( 'Please add an account to the whatsapp.', 'asnp-easy-whatsapp' ) );
					}
					if ( isset( $value ) && is_array( $value ) ) {
						$options[ $key ] = array_filter( array_map( 'absint', wp_list_pluck( $value, 'value' ) ) );
					} elseif ( ! $id && isset( $defaults[ $key ] ) ) {
						$options[ $key ] = $defaults[ $key ];
					}
					break;

				case 'bubbleText':
				case 'chatHeaderName':
				case 'buttonText':
				case 'chatHeaderSelectPicture':
				case 'chatHeaderReplyTimeText':
				case 'position':
					if ( isset( $value ) ) {
						$options[ $key ] = sanitize_text_field( $value );
					} elseif ( ! $id && isset( $defaults[ $key ] ) ) {
						$options[ $key ] = $defaults[ $key ];
					}
					break;

				case 'welcomeMessage':
					if ( isset( $value ) ) {
						$options[ $key ] = wp_kses_post( $value );
					} elseif ( ! $id && isset( $defaults[ $key ] ) ) {
						$options[ $key ] = $defaults[ $key ];
					}
					break;

				case 'woocommerceItems':
					if ( isset( $value ) && is_array( $value ) ) {
						$temp_values = array();
						foreach ( $value as $v ) {
							$temp_value = array(
								'type'  => sanitize_text_field( $v['type'] ),
								'items' => '',
							);
							if ( ! empty( $v['items'] ) ) {
								if ( is_string( $v['items'] ) ) {
									$temp_value['items'] = implode( ',', array_filter( array_map( 'absint', explode( ',', $v['items'] ) ) ) );
								} elseif ( is_array( $v['items'] ) ) {
									$temp_value['items'] = implode( ',', array_filter( array_map( 'absint', wp_list_pluck( $v['items'], 'value' ) ) ) );
								}
							}
							$temp_values[] = $temp_value;
						}
						$options[ $key ] = $temp_values;
					} elseif ( ! $id && isset( $defaults[ $key ] ) ) {
						$options[ $key ] = $defaults[ $key ];
					}
					break;

				case 'pagesUrl':
				case 'excludedPagesUrl':
					if ( ! empty( $value ) ) {
						$options[ $key ] = array_filter( array_map( 'sanitize_text_field', $value ) );
					} elseif ( isset( $defaults[ $key ] ) ) {
						$options[ $key ] = $defaults[ $key ];
					}
					break;

				default:
					if ( isset( $value ) ) {
						$options[ sanitize_text_field( $key ) ] = wp_kses_post_deep( $value );
					}
					break;
			}
		}

		if ( ! $id ) {
			foreach ( $defaults as $key => $value ) {
				if ( ! isset( $options[ $key ] ) ) {
					$options[ $key ] = $value;
				}
			}
		}

		return apply_filters(
			'asnp_ewhatsapp_api_whatsapp_' . __FUNCTION__,
			$options,
			$request
		);
	}

}
