<?php

namespace AsanaPlugins\WhatsApp\Admin;

defined( 'ABSPATH' ) || exit;

class Menu {


	protected $menus = array();

	public function init() {
		add_action( 'admin_menu', array( $this, 'menus' ) );
	}

	/**
	 * Getting all of admin-face menus of plugin.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_menus() {
		return $this->menus;
	}

	public function menus() {
		$this->menus['whatsapp'] = add_menu_page(
			__( 'AI Content & Chat', 'asnp-easy-whatsapp' ),
			__( 'AI Content & Chat', 'asnp-easy-whatsapp' ),
			apply_filters( 'asnp_ewhatsapp_whatsapp_menu_capability', 'manage_options' ),
			'asnp-whatsapp',
			array( $this, 'create_menu' ),
			ASNP_EWHATSAPP_PLUGIN_URL . 'assets/images/menu-icon.svg'
		);
	}

	public function create_menu() {
		?>
		<div id="asnp-whatsapp-wrapper" class="asnp-whatsapp-wrapper">
			<div id="asnp-whatsapp">
			</div>
		</div>
		<?php
	}

}
