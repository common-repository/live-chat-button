<?php

namespace AsanaPlugins\WhatsApp;

defined( 'ABSPATH' ) || exit;

class Assets {

	protected $whatsapps = [];

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ), 15 );
		add_action( 'wp_footer', array( $this, 'localize_scripts' ), 15 );
	}

	public function add_whatsapp( $whatsapp, $replace_sticky = true ) {
		if ( ! $whatsapp ) {
			return;
		}

		if ( empty( $this->whatsapps ) ) {
			$this->whatsapps = [ $whatsapp ];
			return;
		}

		$sticky = -1;
		for ( $i = 0; $i < count( $this->whatsapps ); $i++ ) {
			if ( $whatsapp->id == $this->whatsapps[ $i ]->id ) {
				return;
			}
			if (
				$replace_sticky &&
				isset( $whatsapp->type ) &&
				'sticky' === $whatsapp->type &&
				isset( $this->whatsapps[ $i ]->type ) &&
				'sticky' === $this->whatsapps[ $i ]->type
			) {
				$sticky = $i;
			}
		}

		if ( -1 < $sticky ) {
			$this->whatsapps[ $sticky ] = $whatsapp;
		} else {
			$this->whatsapps[] = $whatsapp;
		}
	}

	public function load_scripts() {
		global $post;
		$this->whatsapps = get_page_active_whatsapps();
		if (
			! empty( $this->whatsapps ) ||
			( ! empty( $post->post_content ) && false !== strpos( $post->post_content, '[asnp_chat' ) ) ||
			( ! empty( $post->post_content ) && false !== strpos( $post->post_content, 'livechatbutton/whatsapp' ) )
		) {
			register_polyfills();

			wp_enqueue_style(
				'asnp-easy-whatsapp',
				apply_filters( 'asnp_ewhatsapp_whatsapp_style', $this->get_url( 'whatsapp/style', 'css' ) )
			);
			wp_enqueue_script(
				'asnp-easy-whatsapp',
				apply_filters( 'asnp_ewhatsapp_whatsapp_script', $this->get_url( 'whatsapp/index', 'js' ) ),
				array(
					'react-dom',
					'wp-hooks',
					'wp-i18n',
					'wp-api-fetch',
				),
				ASNP_EWHATSAPP_VERSION,
				true
			);

			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations(
					'asnp-easy-whatsapp',
					'asnp-easy-whatsapp',
					apply_filters( 'asnp_ewhatsapp_whatsapp_script_translations', ASNP_EWHATSAPP_ABSPATH . 'languages' )
				);
			}
		}
	}

	public function localize_scripts() {
		if ( empty( $this->whatsapps ) ) {
			return;
		}

		$id = get_the_ID();
		$settings = get_plugin()->settings;
		wp_localize_script(
			'asnp-easy-whatsapp',
			'easyWhatsappData',
			array(
				'whatsapps' => apply_filters( 'asnp_ewhatsapp_data_whatsapps', $this->whatsapps ),
				'pluginUrl' => ASNP_EWHATSAPP_PLUGIN_URL,
				'postId'    => ! empty( $id ) ? $id : 0,
				'settings'  => [
					'timezone'            => $settings->get_setting( 'timezone', get_timezone_string() ),
					'cssSelector'         => in_array( $settings->get_setting( 'woocommerceBtnPosition', 'after_add_to_cart_button' ), [ 'before_css_selector', 'after_css_selector' ] ) ? $settings->get_setting( 'woocommerceCssSelector', '' ) : '',
					'cssSelectorPosition' => 'before_css_selector' === $settings->get_setting( 'woocommerceBtnPosition', 'after_add_to_cart_button' ) ? 'before' : 'after',
					'googleAnalytics'     => $settings->get_setting( 'googleAnalytics', 0 ),
					'facebookPixel'       => $settings->get_setting( 'facebookPixel', 0 ),
					'openNewTab'          => $settings->get_setting( 'openNewTab', 'true' ),
					'urlDesktop'          => $settings->get_setting( 'urlDesktop', 'API' ),
					'urlMobile'           => $settings->get_setting( 'urlMobile', 'API' ),
					'poweredBy'           => $settings->get_setting( 'poweredBy', 'true' ),
				],
			)
		);
	}

	public function get_url( $file, $ext ) {
		return plugins_url( $this->get_path( $ext ) . $file . '.' . $ext, ASNP_EWHATSAPP_PLUGIN_FILE );
	}

	protected function get_path( $ext ) {
		return 'css' === $ext ? 'assets/css/' : 'assets/js/';
	}

}
