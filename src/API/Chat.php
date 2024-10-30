<?php

namespace AsanaPlugins\WhatsApp\API;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WhatsApp;

class Chat extends BaseController {

	protected $rest_base = 'chat';

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'query' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'query' => array(
							'description' => __( 'Chat query', 'asnp-easy-whatsapp' ),
							'type'        => 'string',
						),
					),
				),
			)
		);
	}

	public function query( $request ) {
		if ( defined( 'ASNP_CHAT_DEBUG' ) && ASNP_CHAT_DEBUG ) {
			$response = [
				'Hi',
				'How are you?',
				'I\'m ChatGPT developed by OpenAI',
				'How can I help you?',
				'Lorem ipsum dolor sit amet, facer graece tamquam eu pro, cu animal lucilius pro, dicta appareat te vis. Oratio splendide mel no. Usu persecuti suscipiantur comprehensam ut, per ei graeco disputationi. Sit id feugiat mediocrem gubergren, his vitae graeco feugait cu, altera accusamus scripserit in qui. Id fastidii periculis nam, cum solet tempor eu. Legere ceteros sit et, vitae doming meliore in usu, pro denique scriptorem id. Vix in nullam indoctum, eu fabulas oporteat vix.<br>
				Vim mundi dissentias no, mea postulant incorrupte ex. Vel tation officiis adipisci an, vis at luptatum consulatu. Minimum noluisse id vis. Eos quando munere dignissim et. Et velit saperet vim, pri eu illum nobis theophrastus. His no impedit mnesarchum, per ei modo populo electram, audiam nostrud et his. Id sea omnes aeterno.<br>
				Has fugit omnium voluptatibus eu. Ad vel oratio eruditi. Cu commodo recusabo principes vim, denique honestatis has id. At cum scribentur signiferumque.<br>
				Ut idque audire comprehensam mea, ne mel suavitate gubergren. Populo omnesque est at, consul feugait eos ut, ius no graecis admodum. Usu ex deserunt dignissim dissentias, sed quodsi deterruisset at. Cu dico soleat quaeque ius. Affert electram posidonium mel cu, at accusam singulis nec, ea per novum patrioque omittantur. Mei mollis dignissim ut, illud labores fastidii ad vel.<br>
				Habemus ocurreret philosophia sed an. Vim ad elitr tritani molestiae. Ad etiam indoctum cotidieque pri. Eam ex lobortis disputationi, ea mea porro iisque.'
			];

			$rand = mt_rand( 0, count( $response ) - 1 );

			return new \WP_REST_Response( array(
				'response' => $response[ $rand ],
			) );
		}

		try {
			$query = ! empty( $request['query'] ) ? wp_kses_post( $request['query'] ) : '';
			if ( empty( $query ) ) {
				throw new \Exception( __( 'Empty query.', 'asnp-easy-whatsapp' ) );
			}

			$api_key = WhatsApp\get_plugin()->settings->get_setting( 'openaiApiKey', '' );
			if ( empty( $api_key ) ) {
				throw new \Exception( __( 'OpenAI API Key is required.', 'asnp-easy-whatsapp' ) );
			}

			$messages = [];
			if ( ! empty( $request['context'] ) ) {
				foreach ( $request['context'] as $value ) {
					if (
						! empty( $value['from'] ) &&
						in_array( strtolower( $value['from'] ), array( 'customer', 'agent' ) ) &&
						! empty( $value['message'] )
					) {
						$messages[] = [
							'role'    => 'agent' === $value['from'] ? 'assistant' : 'user',
							'content' => wp_kses_post( $value['message'] ),
						];
					}
				}
			}

			$messages[] = [
				'role'    => 'user',
				'content' => $query,
			];

			$messages = apply_filters( 'asnp_ewhatsapp_chat_query_prompt', $messages, $request );

			$max_tokens = (int) WhatsApp\get_plugin()->settings->get_setting( 'aiMaxTokens', 100 );
			$max_tokens = ! $max_tokens || 0 > $max_tokens ? 100 : $max_tokens;

			$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
				'headers' => [
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				],
				'body' => json_encode( [
					'model'             => 'gpt-3.5-turbo',
					'messages'          => $messages,
					'temperature'       => 0.0,
					'max_tokens'        => $max_tokens,
					'frequency_penalty' => 0.0,
					'presence_penalty'  => 0.0,
					'top_p'             => 1,
				] ),
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
				$response = preg_replace( '/^\n+/', '', wp_kses_post( $response['choices'][0]['text'] ) );
			} elseif ( ! empty( $response['choices'][0]['message']['content'] ) ) {
				$response = preg_replace( '/^\n+/', '', wp_kses_post( $response['choices'][0]['message']['content'] ) );
			} elseif ( isset( $response['error']['message'] ) ) {
				$response = preg_replace( '/^\n+/', '', wp_kses_post( $response['error']['message'] ) );
			} else {
				throw new \Exception( __( 'Sorry, something went wrong please try again.', 'asnp-easy-whatsapp' ) );
			}

			return new \WP_REST_Response( array(
				'response' => $response,
			) );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'asnp_ewhatsapp_rest_chat_error', $e->getMessage(), array( 'status' => 400 ) );
		}
	}

}
