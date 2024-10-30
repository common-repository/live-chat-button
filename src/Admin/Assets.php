<?php

namespace AsanaPlugins\WhatsApp\Admin;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WhatsApp;

class Assets {

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ), 15 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'load_block_editor_assets' ), 15 );
	}

	public function load_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( 'toplevel_page_asnp-whatsapp' === $screen_id ) {
			WhatsApp\register_polyfills();

			wp_enqueue_style(
				'asnp-whatsapp-admin',
				apply_filters( 'asnp_ewhatsapp_whatsapp_admin_style', $this->get_url( 'admin/style', 'css' ) )
			);
			wp_enqueue_script(
				'asnp-whatsapp-admin',
				apply_filters( 'asnp_ewhatsapp_whatsapp_admin_script', $this->get_url( 'admin/admin/index', 'js' ) ),
				array(
					'react-dom',
					'wp-hooks',
					'wp-i18n',
					'wp-api-fetch',
				),
				ASNP_EWHATSAPP_VERSION,
				true
			);

			wp_localize_script(
				'asnp-whatsapp-admin',
				'whatsappData',
				array(
					'pluginUrl'        => ASNP_EWHATSAPP_PLUGIN_URL,
					'timezone'         => WhatsApp\get_timezone_string(),
					'contentPostTypes' => WhatsApp\get_content_post_types(),
					'contentLayouts'   => WhatsApp\get_content_layouts(),
					'imageLayouts'     => WhatsApp\get_image_layouts(),
					'showReview'       => WhatsApp\maybe_show_review(),
				)
			);

			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations(
					'asnp-whatsapp-admin',
					'asnp-easy-whatsapp',
					apply_filters( 'asnp_ewhatsapp_whatsapp_admin_script_translations', ASNP_EWHATSAPP_ABSPATH . 'languages' )
				);
			}
		} elseif ( in_array( $screen_id, apply_filters( 'asnp_ewhatsapp_ai_content_writer_legacy_editor_pages', array( 'product' ) ) ) ) {
			WhatsApp\register_polyfills();

			wp_enqueue_style(
				'asnp-ai-content-writer-legacy-editor',
				apply_filters( 'asnp_ewhatsapp_ai_content_writer_legacy_editor_style', $this->get_url( 'legacy/style', 'css' ) )
			);
			wp_enqueue_script(
				'asnp-ai-content-writer-legacy-editor',
				apply_filters( 'asnp_ewhatsapp_ai_content_writer_legacy_editor_script', $this->get_url( 'admin/legacy/index', 'js' ) ),
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
					'asnp-ai-content-writer-legacy-editor',
					'asnp-easy-whatsapp',
					apply_filters( 'asnp_ewhatsapp_ai_content_writer_legacy_editor_script_translations', ASNP_EWHATSAPP_ABSPATH . 'languages' )
				);
			}
		} elseif ( 'dashboard' === $screen_id ) {
			$this->show_review();
		}
	}

	public function load_block_editor_assets() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_enqueue_style(
			'asnp-aicontent',
			apply_filters( 'asnp_ewhatsapp_aicontent_style', $this->get_url( 'aicontent/style', 'css' ) ),
			array( 'wp-edit-blocks' ),
			ASNP_EWHATSAPP_VERSION
		);

		wp_enqueue_script(
			'asnp-aicontent',
			apply_filters( 'asnp_ewhatsapp_aicontent_script', $this->get_url( 'admin/aicontent/index', 'js' ) ),
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

		wp_enqueue_style(
			'asnp-aimenu',
			apply_filters( 'asnp_ewhatsapp_aimenu_style', $this->get_url( 'aimenu/style', 'css' ) ),
			array( 'wp-edit-blocks' ),
			ASNP_EWHATSAPP_VERSION
		);

		wp_enqueue_script(
			'asnp-aimenu',
			apply_filters( 'asnp_ewhatsapp_aimenu_script', $this->get_url( 'admin/aimenu/index', 'js' ) ),
			array(
				'wp-block-editor',
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-components',
				'wp-api-fetch',
				'wp-data',
				'wp-rich-text',
				'wp-hooks',
			),
			ASNP_EWHATSAPP_VERSION,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				'asnp-aicontent',
				'asnp-easy-whatsapp',
				apply_filters( 'asnp_ewhatsapp_aicontent_script_translations', ASNP_EWHATSAPP_ABSPATH . 'languages' )
			);
			wp_set_script_translations(
				'asnp-aimenu',
				'asnp-easy-whatsapp',
				apply_filters( 'asnp_ewhatsapp_aimenu_script_translations', ASNP_EWHATSAPP_ABSPATH . 'languages' )
			);
		}
	}

	protected function show_review() {
		if ( ! WhatsApp\maybe_show_review() ) {
			return;
		}

		WhatsApp\register_polyfills();
		wp_enqueue_style(
			'asnp-easy-whatsapp-review',
			$this->get_url( 'review/style', 'css' )
		);
		wp_enqueue_script(
			'asnp-easy-whatsapp-review',
			$this->get_url( 'admin/review/index', 'js' ),
			array(
				'react-dom',
				'wp-i18n',
				'wp-api-fetch',
			),
			ASNP_EWHATSAPP_VERSION,
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'asnp-easy-whatsapp-review', 'asnp-easy-whatsapp', ASNP_EWHATSAPP_ABSPATH . 'languages' );
		}
	}

	public function get_url( $file, $ext ) {
		return plugins_url( $this->get_path( $ext ) . $file . '.' . $ext, ASNP_EWHATSAPP_PLUGIN_FILE );
	}

	protected function get_path( $ext ) {
		return 'css' === $ext ? 'assets/css/' : 'assets/js/';
	}
}
