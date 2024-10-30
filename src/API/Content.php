<?php

namespace AsanaPlugins\WhatsApp\API;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WhatsApp;
use AsanaPlugins\WhatsApp\Helpers\AI;
use AsanaPlugins\WhatsApp\Models\AIContentLayoutModel;

class Content extends BaseController {

	protected $rest_base = 'content';

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_content' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(
						'model' => array(
							'description' => __( 'Content model', 'asnp-easy-whatsapp' ),
							'type'        => 'string',
						),
						'prompt' => array(
							'description' => __( 'Content prompt', 'asnp-easy-whatsapp' ),
							'type'        => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/create',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_post' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(
						'content' => array(
							'description' => __( 'Content model', 'asnp-easy-whatsapp' ),
							'type'        => 'string',
						),
						'excerpt' => array(
							'description' => __( 'Content prompt', 'asnp-easy-whatsapp' ),
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

	public function get_content( $request ) {
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

			if ( ! AI\is_valid_model( $model ) ) {
				throw new \Exception( __( 'Invalid model.', 'asnp-easy-whatsapp' ) );
			}

			$max_tokens = ! empty( $request['max_tokens'] ) ? absint( $request['max_tokens'] ) : 512;
			if ( empty( $max_tokens ) || 0 > $max_tokens ) {
				throw new \Exception( __( 'Invalid max token.', 'asnp-easy-whatsapp' ) );
			}
			AI\check_max_tokens( $model, $max_tokens );

			$temperature = ! empty( $request['temperature'] ) ? (float) $request['temperature'] : 0.0;
			$temperature = AI\get_valid_temprature( $temperature );

			$prompt = apply_filters( 'asnp_ewhatsapp_content_prompt', $prompt, $request );

			$data = [
				'model'             => $model,
				'prompt'            => $prompt,
				'temperature'       => $temperature,
				'max_tokens'        => $max_tokens,
				'frequency_penalty' => 0.0,
				'presence_penalty'  => 0.0,
				'top_p'             => 1,
			];

			$url = 'https://api.openai.com/v1/completions';
			if ( in_array( $model, [ 'gpt-4', 'gpt-4-32k', 'gpt-3.5-turbo' ] ) ) {
				$url = 'https://api.openai.com/v1/chat/completions';
				$data['messages'] = [ [ 'role' => 'user', 'content' => $prompt ] ];
				unset( $data['prompt'] );
			}

			$data = apply_filters( 'asnp_ewhatsapp_content_data', $data, $request );

			$response = wp_remote_post( $url, [
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				],
				'body' => json_encode( $data ),
				'timeout' => 120,
			] );

			if ( is_wp_error( $response ) ) {
				throw new \Exception( $response->get_error_message() );
			}

			if ( ! empty( $response['body'] ) ) {
				$response = json_decode( $response['body'], true );
			}

			if ( ! empty( $response['error']['message'] ) ) {
				throw new \Exception( preg_replace( '/^\n+/', '', esc_html( $response['error']['message'] ) ) );
			}

			if ( ! empty( $response['choices'][0]['text'] ) ) {
				return rest_ensure_response( [
					'response' => wp_kses_post( $response['choices'][0]['text'] ),
				] );
			} elseif ( ! empty( $response['choices'][0]['message']['content'] ) ) {
				return rest_ensure_response( [
					'response' => wp_kses_post( $response['choices'][0]['message']['content'] ),
				] );
			}

			throw new \Exception( __( 'Error occurred in getting content.', 'asnp-easy-whatsapp' ) );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_content_error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

	public function create_post( $request ) {
		try {
			$title = ! empty( $request['title'] ) ? sanitize_text_field( $request['title'] ) : '';
			if ( empty( $title ) ) {
				throw new \Exception( __( 'Title is required to create a post.', 'asnp-easy-whatsapp' ) );
			}

			$content = ! empty( $request['content'] ) ? wp_kses_post( $request['content'] ) : '';
			if ( empty( $content ) ) {
				throw new \Exception( __( 'Content is required to create a post.', 'asnp-easy-whatsapp' ) );
			}
			$content = WhatsApp\markdown_to_html( $content );

			$excerpt = ! empty( $request['excerpt'] ) ? wp_kses_post( $request['excerpt'] ) : '';

			$tags = [];
			if ( ! empty( $request['tags'] ) ) {
				$tags = array_map( 'sanitize_text_field', explode( ',', $request['tags'] ) );
			}

			$id = wp_insert_post( [
				'post_title'   => $title,
				'post_content' => $content,
				'post_excerpt' => $excerpt,
				'post_type'    => ! empty( $request['type'] ) ? sanitize_text_field( $request['type'] ) : 'post',
				'tags_input'   => $tags,
			] );

			if ( 0 < $id ) {
				do_action( 'asnp_ewhatsapp_post_created_successfully', $id, $request );
				return rest_ensure_response( [ 'id' => $id, 'link' => get_edit_post_link( $id, 'edit' ) ] );
			}

			throw new \Exception( __( 'Error occurred in creating post.', 'asnp-easy-whatsapp' ) );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_content_create_post_error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

	public function save_layout( $request ) {
		try {
			$data = [
				'name' => ! empty( $request['name'] ) ? sanitize_text_field( $request['name'] ) : __( 'New Layout', 'asnp-easy-whatsapp' ),
			];

			if ( ! empty( $request['id'] ) && 0 < (int) $request['id'] ) {
				$data['id'] = absint( $request['id'] );
			}

			foreach ( $request->get_params() as $key => $value ) {
				switch ( $key ) {
					case 'topic':
					case 'title':
					case 'typeContent':
					case 'contentlang':
					case 'contentStyle':
					case 'contentTone':
					case 'modelContent':
						$data[ $key ] = sanitize_text_field( $value );
						break;

					case 'content':
					case 'excerpt':
					case 'metaDescription':
					case 'headings':
					case 'tags':
					case 'keywords':
					case 'excludeKeywords':
					case 'topics':
					case 'promptTitle':
					case 'promptHeadings':
					case 'promptContent':
					case 'promptExcerpt':
					case 'promptMetaDescription':
					case 'promptTags':
						$data[ $key ] = wp_kses_post( $value );
						break;

					case 'numberHeadings':
					case 'numberContent':
					case 'maxTokenContent':
					case 'useTopics':
						$data[ $key ] = absint( $value );
						break;

					case 'temperature':
						$data[ $key ] = AI\get_valid_temprature( $value );
						break;
				}
			}

			$model = WhatsApp\get_plugin()->container()->get( AIContentLayoutModel::class );

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
			return new \WP_Error( 'asnp_ewhatsapp_rest_content_save_layout_error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

	public function get_layout( $request ) {
		try {
			$id = isset( $request['id'] ) ? (int) $request['id'] : 0;
			if ( 0 >= $id ) {
				throw new \Exception( __( 'Invalid layout ID.', 'asnp-easy-whatsapp' ) );
			}

			$model = WhatsApp\get_plugin()->container()->get( AIContentLayoutModel::class );
			$item  = $model->get_item( $id );
			if ( ! $item ) {
				throw new \Exception( __( 'Layout doesn\'t exist.', 'asnp-easy-whatsapp' ) );
			}

			return rest_ensure_response( [ 'item' => $item ] );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_content_get_layout_error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

	public function delete_layout( $request ) {
		try {
			$id = isset( $request['id'] ) ? (int) $request['id'] : 0;
			if ( 0 >= $id ) {
				throw new \Exception( __( 'Invalid layout ID.', 'asnp-easy-whatsapp' ) );
			}

			$model = WhatsApp\get_plugin()->container()->get( AIContentLayoutModel::class );
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
			return new \WP_Error( 'asnp_ewhatsapp_rest_content_delete_layout_error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

	public function create_item_permissions_check( $request ) {
		$type = 'post';
		if ( ! empty( $request['type'] ) ) {
			$type = sanitize_text_field( $request['type'] );
		} elseif ( ! empty( $request['typeContent'] ) ) {
			$type = sanitize_text_field( $request['typeContent'] );
		}

		if ( 'page' === $type ) {
			if ( ! current_user_can( 'publish_pages' ) ) {
				return new \WP_Error( 'asnp_easy_whatsapp_rest_cannot_create', __( 'Sorry, you don\'t have permission to do this request.', 'asnp-easy-whatsapp' ), array( 'status' => rest_authorization_required_code() ) );
			}
			return true;
		}

		if ( 'post' === $type ) {
			if ( ! current_user_can( 'publish_posts' ) ) {
				return new \WP_Error( 'asnp_easy_whatsapp_rest_cannot_create', __( 'Sorry, you don\'t have permission to do this request.', 'asnp-easy-whatsapp' ), array( 'status' => rest_authorization_required_code() ) );
			}
			return true;
		}

		if ( in_array( $type, [ 'attachment', 'revision', 'nav_menu_item' ] ) ) {
			return new \WP_Error( 'asnp_easy_whatsapp_rest_cannot_create', __( 'Sorry, you don\'t have permission to do this request.', 'asnp-easy-whatsapp' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$post_type_object = get_post_type_object( $type );
		if (
			! $post_type_object ||
			! current_user_can( $post_type_object->cap->publish_posts )
		) {
			return new \WP_Error( 'asnp_easy_whatsapp_rest_cannot_create', __( 'Sorry, you don\'t have permission to do this request.', 'asnp-easy-whatsapp' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

}
