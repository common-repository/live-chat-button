<?php

namespace AsanaPlugins\WhatsApp\API;

use AsanaPlugins\WhatsApp\Models\AccountModel;
use function AsanaPlugins\WhatsApp\get_plugin;

defined( 'ABSPATH' ) || exit;

class Account extends BaseController {

	protected $rest_base = 'account';

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
		$model = get_plugin()->container()->get( AccountModel::class );
		return new \WP_REST_Response(
			array(
				'items' => $model->get_items(),
			)
		);
	}

	public function get_item( $request ) {
		$id = isset( $request['id'] ) ? (int) $request['id'] : 0;
		if ( 0 >= $id ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_whatsapp_exists', __( 'Invalid item ID.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		$model = get_plugin()->container()->get( AccountModel::class );
		$item  = $model->get_item( $id );
		if ( ! $item ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_whatsapp_exists', __( 'Invalid item ID.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		return new \WP_REST_Response(
			array(
				'item' => $item,
			)
		);
	}

	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_whatsapp_exists', __( 'Cannot create existing account.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		try {
			$item = $this->save_item( $request );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest', $e->getMessage(), array( 'status' => 400 ) );
		}

		do_action( 'asnp_ewhatsapp_account_created', $item, $request );

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

		$model = get_plugin()->container()->get( AccountModel::class );
		$item  = $model->get_item( $id );
		if ( ! $item ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_whatsapp_exists', __( 'Invalid item ID.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		try {
			$item = $this->save_item( $request );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest', $e->getMessage(), array( 'status' => 400 ) );
		}

		do_action( 'asnp_ewhatsapp_account_updated', $item, $request );

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

		$model = get_plugin()->container()->get( AccountModel::class );
		$item  = $model->get_item( $id );
		if ( ! $item ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_whatsapp_exists', __( 'Invalid item ID.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		$delete = $model->delete( $id );
		if ( ! $delete ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_cannot_delete_item', __( 'Cannot delete item.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		$this->delete_account_files( $id );

		do_action( 'asnp_ewhatsapp_account_deleted', $id, $request );

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

		$model   = get_plugin()->container()->get( AccountModel::class );
		$item_id = $model->duplicate( $id );
		if ( ! $item_id ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_cannot_duplicate_item', __( 'Cannot duplicate item.', 'asnp-easy-whatsapp' ), array( 'status' => 400 ) );
		}

		do_action( 'asnp_ewhatsapp_account_duplicated', $item_id, $id, $request );

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

		$model   = get_plugin()->container()->get( AccountModel::class );
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
	 * Save a single auto add products.
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

		$data['type'] = ! empty( $request['type'] ) ? sanitize_text_field( $request['type'] ) : 'whatsapp';

		if ( ! empty( $request['id'] ) && 0 < (int) $request['id'] ) {
			$data['id'] = (int) $request['id'];
		} else {
			$data['name'] = ! empty( $data['name'] ) ? $data['name'] : __( 'Account', 'asnp-easy-whatsapp' );
		}

		$model = get_plugin()->container()->get( AccountModel::class );

		$upload = $this->avatar_upload();
		if ( ! empty( $upload['url'] ) ) {
			$data['avatar'] = $upload['url'];
		}

		foreach ( $request->get_params() as $key => $value ) {
			switch ( $key ) {
				case 'caption':
				case 'customCaption':
				case 'accountNumber':
				case 'timezone':
					$data[ $key ] = sanitize_text_field( $value );
					break;

				case 'textMessage':
					$data[ $key ] = wp_kses_post( $value );
					break;

				case 'alwaysOnline':
				case 'useTimezone':
					$data[ $key ] = absint( $value );
					break;

				case 'availability':
					if ( ! empty( $value ) ) {
						$value                = json_decode( $value, true );
						$data['availability'] = maybe_serialize( wp_kses_post_deep( $value ) );
					} else {
						$data[ $key ] = '';
					}
					break;

				case 'avatar':
					if ( ! isset( $data['avatar'] ) && empty( $requery['avatarFile'] ) ) {
						$data[ $key ] = esc_url_raw( $value );
					}
					break;
			}
		}

		$id = $model->add( $data );
		if ( ! $id || 0 >= $id ) {
			throw new \Exception( __( 'Error occurred in saving item.', 'asnp-easy-whatsapp' ) );
		}

		if ( ! empty( $upload['url'] ) && empty( $data['id'] ) ) {
			// move file.
			$new_file = str_replace( '/accounts/', "/accounts/$id/", $upload['file'] );
			if ( file_exists( $upload['file'] ) ) {
				try {
					if ( wp_mkdir_p( dirname( $new_file ) ) && copy( $upload['file'], $new_file ) && file_exists( $new_file ) ) {
						unlink( $upload['file'] );
						$model->update( $id, array( 'avatar' => str_replace( '/accounts/', "/accounts/$id/", $upload['url'] ) ) );
					}
				} catch ( \Exception $e ) {
				}
			}
		}

		$item = $model->get_item( $id );

		do_action( 'asnp_ewhatsapp_account_saved', $item, $request );

		return $item;
	}

	protected function avatar_upload() {
		if ( empty( $_FILES['easyWhatsappAccountAvatarFile'] ) ) {
			return false;
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$upload = wp_handle_upload(
			$_FILES['easyWhatsappAccountAvatarFile'],
			[
				'test_form' => false,
				'mimes'     => [
					'jpg|jpeg|jpe' => 'image/jpeg',
					'gif'          => 'image/gif',
					'png'          => 'image/png',
				],
			]
		);
		if ( isset( $upload['error'] ) ) {
			throw new \Exception( $upload['error'] );
		}

		return $upload;
	}

	protected function delete_account_files( $id ) {
		if ( empty( $id ) || 0 >= (int) $id ) {
			return false;
		}

		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['subdir'] ) ) {
			$path = str_replace( $uploads['subdir'], "/easy_whatsapp_uploads/accounts/$id", $uploads['path'] );
		} else {
			$path = $uploads['path'] . "/easy_whatsapp_uploads/accounts/$id";
		}

		if ( is_dir( $path ) ) {
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			WP_Filesystem();
			global $wp_filesystem;
			return $wp_filesystem->delete( $path, true );
		}

		return false;
	}

}
