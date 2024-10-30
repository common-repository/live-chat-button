<?php

namespace AsanaPlugins\WhatsApp\Compatibility;

defined( 'ABSPATH' ) || exit;

class YoastSeo {

	public static function init() {
		add_action( 'asnp_ewhatsapp_post_created_successfully', array( __CLASS__, 'add_meta_description' ), 10, 2 );
	}

	public static function add_meta_description( $id, $request ) {
		if ( 0 >= (int) $id || empty( $request ) ) {
			return;
		}

		if ( empty( $request['meta_description'] ) ) {
			return;
		}

		$metadesc = wp_kses_post( $request['meta_description'] );
		if ( empty( $metadesc ) ) {
			return;
		}

		update_post_meta( $id, '_yoast_wpseo_metadesc', $metadesc );
	}

}
