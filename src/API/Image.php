<?php

namespace AsanaPlugins\WhatsApp\API;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WhatsApp;
use AsanaPlugins\WhatsApp\Helpers\AI;
use AsanaPlugins\WhatsApp\Models\ImageLayoutModel;

class Image extends BaseController {

	protected $rest_base = 'image';

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_image' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(
						'model' => array(
							'description' => __( 'Image model', 'asnp-easy-whatsapp' ),
							'type'        => 'string',
						),
						'prompt' => array(
							'description' => __( 'Image prompt', 'asnp-easy-whatsapp' ),
							'type'        => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/save',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_image' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(
						'url' => array(
							'description' => __( 'Image url', 'asnp-easy-whatsapp' ),
							'type'        => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/layout',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_layout' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(
						'name' => array(
							'description' => __( 'Layout name', 'asnp-easy-whatsapp' ),
							'type'        => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/layout/(?P<id>[\d]+)',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'asnp-easy-whatsapp' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_layout' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_layout' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
			)
		);
	}

	public function get_image( $request ) {
		try {
			$api_key = WhatsApp\get_plugin()->settings->get_setting( 'openaiApiKey', '' );
			if ( empty( $api_key ) ) {
				throw new \Exception( __( 'OpenAI API Key is required.', 'asnp-easy-whatsapp' ) );
			}

			$prompt = ! empty( $request['prompt'] ) ? wp_kses_post( $request['prompt'] ) : '';
			if ( empty( $prompt ) ) {
				throw new \Exception( __( 'Prompt is required.', 'asnp-easy-whatsapp' ) );
			}

			$model = ! empty( $request['model'] ) ? sanitize_text_field( $request['model'] ) : '';
			if ( empty( $model ) ) {
				throw new \Exception( __( 'Model is required.', 'asnp-easy-whatsapp' ) );
			}

			if ( ! AI\is_valid_image_model( $model ) ) {
				throw new \Exception( __( 'Invalid model.', 'asnp-easy-whatsapp' ) );
			}

			$number_of_images = ! empty( $request['number_of_images'] ) ? absint( $request['number_of_images'] ) : 1;
			$number_of_images = 1 > $number_of_images ? 1 : $number_of_images;

			$size = ! empty( $request['size'] ) ? sanitize_text_field( $request['size'] ) : '1024x1024';

			$prompt = apply_filters( 'asnp_ewhatsapp_image_prompt', $prompt, $request );

			$data = [
				'prompt' => $prompt,
				'n'      => $number_of_images,
				'size'   => $size,
			];

			$data = apply_filters( 'asnp_ewhatsapp_content_data', $data, $request );

			$response = wp_remote_post(
				'https://api.openai.com/v1/images/generations',
				[
					'headers' => [
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $api_key,
					],
					'body' => json_encode( $data ),
					'timeout' => 120,
				]
			);

			if ( is_wp_error( $response ) ) {
				throw new \Exception( $response->get_error_message() );
			}

			if ( ! empty( $response['body'] ) ) {
				$response = json_decode( $response['body'], true );
			}

			if ( ! empty( $response['error']['message'] ) ) {
				throw new \Exception( preg_replace( '/^\n+/', '', esc_html( $response['error']['message'] ) ) );
			}

			if ( ! empty( $response['data'] ) ) {
				return rest_ensure_response( [
					'response' => $response['data'],
				] );
			}

			throw new \Exception( __( 'Error occurred in getting images.', 'asnp-easy-whatsapp' ) );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_image_error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

	public function save_image( $request ) {
		try {
			$url = ! empty( $request['url'] ) ? sanitize_url( $request['url'] ) : '';
			if ( empty( $url ) ) {
				throw new \Exception( __( 'Image url is required.', 'asnp-easy-whatsapp' ) );
			}

			$args = [
				'url'         => $url,
				'title'       => ! empty( $request['title'] ) ? sanitize_text_field( $request['title'] ) : '',
				'filename'    => ! empty( $request['filename'] ) ? sanitize_file_name( $request['filename'] ) : '',
				'caption'     => ! empty( $request['caption'] ) ? sanitize_text_field( $request['caption'] ) : '',
				'alt'         => ! empty( $request['alt'] ) ? sanitize_text_field( $request['alt'] ) : '',
				'description' => ! empty( $request['description'] ) ? sanitize_text_field( $request['description'] ) : '',
			];

			$id = WhatsApp\save_image_from_url( $args );
			if ( ! empty( $id ) ) {
				return rest_ensure_response( [
					'id' => $id,
				] );
			}

			throw new \Exception( __( 'Error occurred in saving image.', 'asnp-easy-whatsapp' ) );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_save_image_error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

	public function save_layout( $request ) {
		try {
			$data = [
				'name'        => ! empty( $request['name'] ) ? sanitize_text_field( $request['name'] ) : __( 'New Layout', 'asnp-easy-whatsapp' ),
				'description' => ! empty( $request['description'] ) ? wp_kses_post( $request['description'] ) : '',
			];

			if ( ! empty( $request['id'] ) && 0 < (int) $request['id'] ) {
				$data['id'] = absint( $request['id'] );
			}

			$model = WhatsApp\get_plugin()->container()->get( ImageLayoutModel::class );

			$options = $this->get_options( $request );
			if ( ! empty( $options ) ) {
				$data['options'] = maybe_serialize( $options );
			}

			$id = $model->add( $data );
			if ( ! $id || 0 >= $id ) {
				throw new \Exception( __( 'Error occurred in saving layout.', 'asnp-easy-whatsapp' ) );
			}

			$item = $model->get_item( $id );
			if ( ! $item ) {
				throw new \Exception( __( 'Error occurred in getting layout.', 'asnp-easy-whatsapp' ) );
			}

			return rest_ensure_response( [ 'item' => $item ] );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_image_save_layout_error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

	public function get_layout( $request ) {
		try {
			$id = isset( $request['id'] ) ? (int) $request['id'] : 0;
			if ( 0 >= $id ) {
				throw new \Exception( __( 'Invalid layout ID.', 'asnp-easy-whatsapp' ) );
			}

			$model = WhatsApp\get_plugin()->container()->get( ImageLayoutModel::class );
			$item  = $model->get_item( $id );
			if ( ! $item ) {
				throw new \Exception( __( 'Layout doesn\'t exist.', 'asnp-easy-whatsapp' ) );
			}

			return rest_ensure_response( [ 'item' => $item ] );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_image_get_layout_error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

	public function delete_layout( $request ) {
		try {
			$id = isset( $request['id'] ) ? (int) $request['id'] : 0;
			if ( 0 >= $id ) {
				throw new \Exception( __( 'Invalid layout ID.', 'asnp-easy-whatsapp' ) );
			}

			$model = WhatsApp\get_plugin()->container()->get( ImageLayoutModel::class );
			$item  = $model->get_item( $id );
			if ( ! $item ) {
				throw new \Exception( __( 'Layout doesn\'t exist.', 'asnp-easy-whatsapp' ) );
			}

			$delete = $model->delete( $id );
			if ( ! $delete ) {
				throw new \Exception( __( 'Cannot delete the layout.', 'asnp-easy-whatsapp' ) );
			}

			return rest_ensure_response( [ 'id' => $id ] );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_image_delete_layout_error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

	protected function get_options( $request ) {
		$options = [];

		foreach ( $request->get_params() as $key => $value ) {
			switch ( $key ) {
				case 'creator':
				case 'photographyStyle':
				case 'cameraMode':
				case 'cameraEffect':
				case 'resolution':
				case 'size':
					$options[ $key ] = sanitize_text_field( $value );
					break;

				case 'promptImage':
					$options[ $key ] = wp_kses_post( $value );
					break;

				case 'numberOfImages':
					$options[ $key ] = absint( $value );
					break;
			}
		}

		return apply_filters(
			'asnp_ewhatsapp_api_image_layout_' . __FUNCTION__,
			$options,
			$request
		);
	}

	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'publish_posts' ) ) {
			return new \WP_Error( 'asnp_easy_whatsapp_rest_cannot_create', __( 'Sorry, you don\'t have permission to do this request.', 'asnp-easy-whatsapp' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

}
