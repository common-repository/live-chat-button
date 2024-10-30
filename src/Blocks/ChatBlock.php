<?php

namespace AsanaPlugins\WhatsApp\Blocks;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WhatsApp;
use AsanaPlugins\WhatsApp\ShortCode\ChatShortCode;

class ChatBlock {

	protected $assets;

	public function __construct( $assets ) {
		$this->assets = $assets;
		$this->register_block_type();
	}

	public function render( $attributes = [], $content = '', $block = null ) {
		if ( empty( $attributes['id'] ) ) {
			return;
		}

		return ChatShortCode::output( $attributes );
	}

	protected function register_block_type() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_style(
			'asnp-whatsapp-block',
			apply_filters( 'asnp_ewhatsapp_whatsapp_block_style', $this->assets->get_url( 'blocks/whatsapp/style', 'css' ) ),
			array( 'wp-edit-blocks' ),
			ASNP_EWHATSAPP_VERSION
		);

		wp_register_script(
			'asnp-whatsapp-block',
			apply_filters( 'asnp_ewhatsapp_whatsapp_block_script', $this->assets->get_url( 'blocks/whatsapp/index', 'js' ) ),
			array(
				'wp-block-editor',
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-components',
				'wp-api-fetch',
				'wp-hooks',
			),
			ASNP_EWHATSAPP_VERSION,
			true
		);

		$settings = WhatsApp\get_plugin()->settings;
		wp_localize_script(
			'asnp-whatsapp-block',
			'easyWhatsappData',
			[
				'pluginUrl' => ASNP_EWHATSAPP_PLUGIN_URL,
				'settings'  => [
					'timezone'            => $settings->get_setting( 'timezone', WhatsApp\get_timezone_string() ),
					'cssSelector'         => in_array( $settings->get_setting( 'woocommerceBtnPosition', 'after_add_to_cart_button' ), [ 'before_css_selector', 'after_css_selector' ] ) ? $settings->get_setting( 'woocommerceCssSelector', '' ) : '',
					'cssSelectorPosition' => 'before_css_selector' === $settings->get_setting( 'woocommerceBtnPosition', 'after_add_to_cart_button' ) ? 'before' : 'after',
					'googleAnalytics'     => $settings->get_setting( 'googleAnalytics', 0 ),
					'facebookPixel'       => $settings->get_setting( 'facebookPixel', 0 ),
					'openNewTab'          => $settings->get_setting( 'openNewTab', 'true' ),
					'urlDesktop'          => $settings->get_setting( 'urlDesktop', 'API' ),
					'urlMobile'           => $settings->get_setting( 'urlMobile', 'API' ),
					'poweredBy'           => $settings->get_setting( 'poweredBy', 'true' ),
				],
			]
		);

		register_block_type(
			ASNP_EWHATSAPP_ABSPATH . 'assets/js/blocks/whatsapp/block.json',
			[
				'editor_script'   => 'asnp-whatsapp-block',
				'editor_style'    => 'asnp-whatsapp-block',
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

}
