<?php

namespace AsanaPlugins\WhatsApp;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WhatsApp\Compatibility\YoastSeo;

class Compatibility {

	public static function init() {
		if ( defined( 'WPSEO_VERSION' ) ) {
			YoastSeo::init();
		}
	}

}
