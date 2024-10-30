<?php
namespace AsanaPlugins\WhatsApp\Validator;

defined( 'ABSPATH' ) || exit;

if ( class_exists( '\AsanaPlugins\WhatsAppPro\Validator\ProductValidatorPro' ) ) {
	class ProductValidator extends \AsanaPlugins\WhatsAppPro\Validator\ProductValidatorPro {}
} else {
	class ProductValidator extends BaseProductValidator {}
}
