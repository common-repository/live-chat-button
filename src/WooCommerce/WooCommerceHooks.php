<?php

namespace AsanaPlugins\WhatsApp\WooCommerce;

defined( 'ABSPATH' ) || exit;

if ( class_exists( '\AsanaPlugins\WhatsAppPro\WooCommerce\WooCommerceHooksPro' ) ) {
	class WooCommerceHooks extends \AsanaPlugins\WhatsAppPro\WooCommerce\WooCommerceHooksPro {}
} else {
	class WooCommerceHooks extends BaseWooCommerceHooks {}
}
