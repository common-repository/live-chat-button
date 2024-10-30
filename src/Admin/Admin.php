<?php

namespace AsanaPlugins\WhatsApp\Admin;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WhatsApp\Registry\Container;
use AsanaPlugins\WhatsApp;

class Admin {

	protected $container;

	public function __construct( Container $container ) {
		$this->container = $container;
	}

	public function init() {
		$this->register_dependencies();

		$this->container->get( Assets::class )->init();
		$this->container->get( Menu::class )->init();

		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta_links' ), 10, 2 );
	}

	protected function register_dependencies() {
		$this->container->register(
			Menu::class,
			function ( Container $container ) {
				return new Menu();
			}
		);
		$this->container->register(
			Assets::class,
			function ( Container $container ) {
				return new Assets();
			}
		);
	}

	/**
	 * Plugin row meta links
	 * This function adds additional links below the plugin in admin plugins page.
	 *
	 * @since  1.0.0
	 * @param  array  $links    The array having default links for the plugin.
	 * @param  string $file     The name of the plugin file.
	 * @return array  $links    Plugin default links and specific links.
	 */
	public function plugin_row_meta_links( $links, $file ) {
		if ( false === strpos( $file, 'easy-whatsapp.php' ) ) {
			return $links;
		}

		if ( WhatsApp\is_pro_active() ) {
			return $links;
		}

		$links = array_merge(
			$links,
			[ '<a href="https://www.asanaplugins.com/product/whatsapp-chat-wordpress?utm_source=whatsapp-chat-wordpress&utm_campaign=go-pro&utm_medium=link" target="_blank" onMouseOver="this.style.color=\'#55ce5a\'" onMouseOut="this.style.color=\'#39b54a\'" style="color: #39b54a; font-weight: bold;">' . esc_html__( 'Go Pro', 'asnp-easy-whatsapp' ) . '</a>' ]
		);

		return $links;
	}
}
