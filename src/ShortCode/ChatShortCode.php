<?php

namespace AsanaPlugins\WhatsApp\ShortCode;

defined( 'ABSPATH' ) || exit;

use AsanaPlugins\WhatsApp;
use AsanaPlugins\WhatsApp\Models\ItemsModel;
use AsanaPlugins\WhatsApp\Models\WhatsAppModel;

class ChatShortCode {

	public static function output( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'asnp_chat' );
		if ( 0 >= absint( $atts['id'] ) ) {
			return '';
		}

		$item = WhatsApp\get_plugin()->container()->get( WhatsAppModel::class )->get_item( absint( $atts['id'] ) );
		if ( ! $item || empty( $item->accounts ) || empty( $item->status ) || 1 != $item->status ) {
			return '';
		}

		$active = apply_filters( 'asnp_ewhatsapp_whatsapp_active', true, $item );
		if ( ! $active ) {
			return '';
		}

		$item->accounts = ItemsModel::get_accounts( [ 'id' => array_filter( array_map( 'absint', $item->accounts ) ) ] );
		if ( empty( $item->accounts ) ) {
			return '';
		}

		WhatsApp\get_plugin()->container()->get( WhatsApp\Assets::class )->add_whatsapp( $item );

		if ( isset( $item->type ) && 'sticky' === $item->type ) {
			return '';
		}

		return '<div id="asnp-easy-whatsapp-' . esc_attr( $item->id ) . '"></div>';
	}

}
