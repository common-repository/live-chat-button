<?php
/**
 * @wordpress-plugin
 * Plugin Name: Live Chat Button
 * Plugin URI: https://www.asanaplugins.com/product/whatsapp-chat-wordpress/?utm_source=whatsapp-chat-wordpress&utm_campaign=live-chat-button&utm_medium=link
 * Description: WhatsApp Chat for WordPress and WooCommerce
 * Tags: ChatGPT, whatsapp, chat, chatbot, AI, woocommerce whatsapp, click to chat, openai, whatsapp business, whats app, wame, wp social chat, join chat, wp whatsapp
 * Version: 5.1.1
 * Author: Asana Plugins
 * Author URI: http://www.asanaplugins.com/
 * License: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: asnp-easy-whatsapp
 * Domain Path: /languages
 * WC requires at least: 3.0
 * WC tested up to: 8.7.0
 *
 * Copyright 2023 Asana Plugins (http://www.asanaplugins.com/)
 */

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WhatsApp\Plugin;

// Plugin version.
if ( ! defined( 'ASNP_EWHATSAPP_VERSION' ) ) {
	define( 'ASNP_EWHATSAPP_VERSION', '5.1.1' );
}

/**
 * Autoload packages.
 *
 * We want to fail gracefully if `composer install` has not been executed yet, so we are checking for the autoloader.
 * If the autoloader is not present, let's log the failure and display a nice admin notice.
 */
$autoloader = __DIR__ . '/vendor/autoload.php';
if ( is_readable( $autoloader ) ) {
	require $autoloader;
} else {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log(  // phpcs:ignore
			sprintf(
				/* translators: 1: composer command. 2: plugin directory */
				esc_html__( 'Your installation of the Live Chat Button plugin is incomplete. Please run %1$s within the %2$s directory.', 'asnp-easy-whatsapp' ),
				'`composer install`',
				'`' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '`'
			)
		);
	}
	/**
	 * Outputs an admin notice if composer install has not been ran.
	 */
	add_action(
		'admin_notices',
		function() {
			?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						/* translators: 1: composer command. 2: plugin directory */
						esc_html__( 'Your installation of the Live Chat Button plugin is incomplete. Please run %1$s within the %2$s directory.', 'asnp-easy-whatsapp' ),
						'<code>composer install</code>',
						'<code>' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '</code>'
					);
					?>
				</p>
			</div>
			<?php
		}
	);
	return;
}

/**
 * The main function for that returns Plugin
 *
 * The main function responsible for returning the one true Plugin
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $plugin = ASNP_EWHATSAPP(); ?>
 *
 * @since  1.0.0
 * @return object|Plugin The one true Plugin Instance.
 */
function ASNP_EWHATSAPP() {
	return Plugin::instance();
}
ASNP_EWHATSAPP()->init();
