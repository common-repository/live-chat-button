<?php

namespace AsanaPlugins\WhatsApp\Blocks;

defined( 'ABSPATH' ) || exit;

class AIContentBlock {

	protected $assets;

	public function __construct( $assets ) {
		$this->assets = $assets;
		$this->register_block_type();
	}

	protected function register_block_type() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_style(
			'asnp-aicontent-block',
			apply_filters( 'asnp_ewhatsapp_aicontent_block_style', $this->assets->get_url( 'blocks/aicontent/style', 'css' ) ),
			array( 'wp-edit-blocks' ),
			ASNP_EWHATSAPP_VERSION
		);

		wp_register_script(
			'asnp-aicontent-block',
			apply_filters( 'asnp_ewhatsapp_aicontent_block_script', $this->assets->get_url( 'blocks/aicontent/index', 'js' ) ),
			array(
				'wp-block-editor',
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-components',
				'wp-api-fetch',
				'wp-data',
				'wp-hooks',
			),
			ASNP_EWHATSAPP_VERSION,
			true
		);

		register_block_type(
			ASNP_EWHATSAPP_ABSPATH . 'assets/js/blocks/aicontent/block.json',
			[
				'editor_script'   => 'asnp-aicontent-block',
				'editor_style'    => 'asnp-aicontent-block',
			]
		);
	}

}
