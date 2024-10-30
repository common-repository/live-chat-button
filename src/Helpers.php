<?php

namespace AsanaPlugins\WhatsApp;

use AsanaPlugins\WhatsApp\Models\WhatsAppModel;
use AsanaPlugins\WhatsApp\Models\AIContentLayoutModel;
use AsanaPlugins\WhatsApp\Models\ImageLayoutModel;
use AsanaPlugins\WhatsApp\Models\ItemsModel;
use AsanaPlugins\WhatsApp\Validator\ProductValidator;
use Parsedown;

function get_plugin() {
	return Plugin::instance();
}

function get_page_active_whatsapps() {
	$items = get_plugin()->container()->get( WhatsAppModel::class )->get_items( array( 'status' => 1 ) );
	if ( empty( $items ) ) {
		return [];
	}

	$woocommerce_enabled = string_to_bool( get_plugin()->settings->get_setting( 'woocommerceEnabled', true ) );
	$sticky              = false;
	$active              = [];
	foreach ( $items as $item ) {
		if ( ! isset( $item->pagesType ) || empty( $item->accounts ) ) {
			continue;
		}

		$maybe_active = false;
		if ( $woocommerce_enabled && is_woocommerce_product_whatsapp( $item ) ) {
			$maybe_active = true;
		} elseif ( is_page_whatsapp( $item ) ) {
			$maybe_active = true;
		}

		$maybe_active = apply_filters( 'asnp_ewhatsapp_whatsapp_active', $maybe_active, $item );
		if ( ! $maybe_active ) {
			continue;
		}

		if ( isset( $item->type ) && 'sticky' === $item->type ) {
			// Only one sticky whatsapp should be added to the page.
			if ( ! $sticky ) {
				$item->accounts = ItemsModel::get_accounts( [ 'id' => array_filter( array_map( 'absint', $item->accounts ) ) ] );
				if ( ! empty( $item->accounts ) ) {
					$active[] = $item;
					$sticky   = true;
				}
			}
		} else {
			$item->accounts = ItemsModel::get_accounts( [ 'id' => array_filter( array_map( 'absint', $item->accounts ) ) ] );
			if ( ! empty( $item->accounts ) ) {
				$active[] = $item;
			}
		}
	}
	return $active;
}

function get_woocommerce_product_active_whatsapp( $product ) {
	$items = get_plugin()->container()->get( WhatsAppModel::class )->get_items( array( 'status' => 1 ) );
	if ( empty( $items ) ) {
		return false;
	}

	foreach ( $items as $item ) {
		if ( ! isset( $item->pagesType ) ) {
			continue;
		}

		if ( ! isset( $item->type ) || 'sticky' === $item->type ) {
			continue;
		}

		if ( is_woocommerce_product_whatsapp( $item, $product ) ) {
			return $item;
		}
	}
	return false;
}

function is_woocommerce_product_whatsapp( $item, $product = null ) {
	if (
		! $item ||
		! isset( $item->pagesType ) ||
		! in_array( $item->pagesType, [ 'allPages', 'allWoocommerceProducts', 'woocommerceProducts' ], true )
	) {
		return false;
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}

	// Check is it the WooCommerce product page, when product is not given.
	if ( ! $product ) {
		global $post;

		if ( ! $post ||
			! is_product() ||
			( ! empty( $post->post_content ) && false !== strpos( $post->post_content, '[product_page' ) )
		) {
			return false;
		}
	}

	$product_id = $product ?
		( is_numeric( $product ) ? $product : $product->get_id() )
		: get_the_ID();
	if ( 0 >= $product_id ) {
		return false;
	}

	if ( 'allWoocommerceProducts' === $item->pagesType || 'allPages' === $item->pagesType ) {
		return true;
	}

	return ProductValidator::valid_product( $item, $product_id );
}

function is_page_whatsapp( $item ) {
	if ( ! $item || ! isset( $item->pagesType ) ) {
		return false;
	}

	if ( 'allPages' === $item->pagesType ) {
		return true;
	}

	if ( 'specificPages' === $item->pagesType ) {
		if ( empty( $item->pagesUrl ) ) {
			return false;
		}
		return is_in_urls( get_current_page_link(), $item->pagesUrl );
	}

	if ( 'excludedPages' === $item->pagesType ) {
		if ( empty( $item->excludedPagesUrl ) ) {
			return true;
		}
		return ! is_in_urls( get_current_page_link(), $item->excludedPagesUrl );
	}

	return false;
}

function is_in_urls( $url, $urls ) {
	if ( empty( $urls ) ) {
		return false;
	}

	$urls = array_filter( array_map( __NAMESPACE__ . '\get_url', $urls ) );
	if ( empty( $urls ) ) {
		return false;
	}

	$url = preg_replace( '/((^https?:\/\/)?(www\.)?)|(\/$)/', '', trim( $url, '/' ) ) . '/';
	foreach ( $urls as $value ) {
		if ( false !== strpos( $url, $value ) ) {
			return true;
		}
	}

	return false;
}

function get_current_page_link() {
	return esc_url( ( is_ssl() ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" );
}

function get_url( $url ) {
	$url = trim( $url );
	if ( empty( $url ) ) {
		return '';
	}

	$site_url = site_url();
	$parsed   = parse_url( $url );
	if ( empty( $parsed['host'] ) && ! empty( $site_url ) ) {
		$url = $site_url . '/' . ltrim( $url, '/' );
	} elseif ( ! empty( $site_url ) && $parsed['host'] !== $_SERVER['HTTP_HOST'] ) {
		$url = str_replace( $parsed['host'], $site_url, $url );
	}

	return preg_replace( '/((^https?:\/\/)?(www\.)?)|(\/$)/', '', esc_url( trim( $url, '/' ) ) ) . '/';
}

/**
 * Callback for array filter to get products the user can view only.
 *
 * @since  1.0.0
 *
 * @param  \WC_Product $product WC_Product object.
 *
 * @return bool
 */
function wc_products_array_filter_readable( $product ) {
	if ( function_exists( '\wc_products_array_filter_readable' ) ) {
		return \wc_products_array_filter_readable( $product );
	}

	return $product && is_a( $product, 'WC_Product' ) && current_user_can( 'read_product', $product->get_id() );
}

/**
 * Converts a string (e.g. 'yes' or 'no') to a bool.
 *
 * @since 1.0.0
 *
 * @param string|bool $string String to convert. If a bool is passed it will be returned as-is.
 *
 * @return bool
 */
function string_to_bool( $string ) {
	return is_bool( $string ) ? $string : ( 'yes' === strtolower( $string ) || 1 === $string || 'true' === strtolower( $string ) || '1' === $string );
}

/**
 * Retrieves the timezone of the site as a string.
 *
 * @since 1.0.0
 *
 * @return string timezone name.
 */
function get_timezone_string() {
	$tzstring = get_option( 'timezone_string' );

	// Remove old Etc mappings. Fallback to gmt_offset.
	if ( false !== strpos( $tzstring, 'Etc/GMT' ) ) {
		$tzstring = '';
	} elseif ( false !== strpos( $tzstring, 'UTC' ) ) {
		$tzstring = 'UTC';
	}

	return ! empty( $tzstring ) ? $tzstring : 'UTC';
}

function markdown_to_html( $content ) {
	if ( empty( $content ) ) {
		return $content;
	}

	$parser = new Parsedown();
	return $parser->text( $content );
}

function get_content_post_types() {
	$post_types = get_post_types( array( 'public' => true ), 'objects' );
	$types      = [];

	foreach ( $post_types as $type ) {
		if ( in_array( $type->name, [ 'attachment', 'revision', 'nav_menu_item' ] ) ) {
			continue;
		}

		$types[] = [
			'key'   => sanitize_text_field( $type->name ),
			'value' => sanitize_text_field( $type->labels->name ),
		];
	}

	return $types;
}

function get_content_layouts() {
	$model   = get_plugin()->container()->get( AIContentLayoutModel::class );
	$layouts = $model->get_items( [ 'number' => -1 ] );
	$layouts = array_map( function ( $layout ) {
		return [
			'key'   => absint( $layout->id ),
			'value' => sanitize_text_field( $layout->name ),
		];
	}, $layouts );

	return $layouts;
}

function get_image_layouts() {
	$model   = get_plugin()->container()->get( ImageLayoutModel::class );
	$layouts = $model->get_items( [ 'number' => -1 ] );
	$layouts = array_map( function ( $layout ) {
		return [
			'key'   => absint( $layout->id ),
			'value' => sanitize_text_field( $layout->name ),
		];
	}, $layouts );

	return $layouts;
}

function register_polyfills() {
	global $wp_version;

	$handles = array(
		'react'        => array( '17.0.2', array() ),
		'react-dom'    => array( '17.0.2', array( 'react' ) ),
		'wp-i18n'      => array( '6.0', array() ),
		'wp-hooks'     => array( '6.0', array() ),
		'wp-api-fetch' => array( '6.0', array() ),
	);
	foreach ( $handles as $handle => $value ) {
		if ( ! version_compare( $wp_version, '5.9', '>=' ) && in_array( $handle, array( 'react', 'react-dom' ) ) ) {
			wp_deregister_script( $handle );
		}

		if ( ! wp_script_is( $handle, 'registered' ) ) {
			wp_register_script(
				$handle,
				plugins_url( 'assets/js/vendor/' . $handle . '.js', ASNP_EWHATSAPP_PLUGIN_FILE ),
				$value[1],
				$value[0],
				true
			);
		}
	}
}

function get_review() {
	return get_option( 'asnp_easy_whatsapp_review', array() );
}

function set_review( $review ) {
	return update_option( 'asnp_easy_whatsapp_review', $review );
}

function maybe_show_review() {
	$review = get_review();
	if ( isset( $review['dismissed'] ) ) {
		return false;
	}

	if ( ! used_plugin() ) {
		return false;
	}

	$schedule = strtotime( '+7 days' );
	if ( empty( $review['schedule'] ) ) {
		$review['schedule'] = $schedule;
		set_review( $review );
	} else {
		$schedule = (int) $review['schedule'];
	}

	if ( empty( $schedule ) || time() < $schedule ) {
		return false;
	}

	return true;
}

function used_plugin() {
	$items = get_plugin()->container()->get( WhatsAppModel::class )->get_items( array( 'status' => 1 ) );
	if ( ! empty( $items ) ) {
		return true;
	}

	$layouts = get_plugin()->container()->get( AIContentLayoutModel::class )->get_items( [ 'number' => -1 ] );
	if ( ! empty( $layouts ) ) {
		return true;
	}

	$layouts = get_plugin()->container()->get( ImageLayoutModel::class )->get_items( [ 'number' => -1 ] );
	if ( ! empty( $layouts ) ) {
		return true;
	}

	return false;
}

function save_image_from_url( array $args ) {
	if ( empty( $args['url'] ) ) {
		throw new \Exception( __( 'Image url is required to save the image.', 'asnp-easy-whatsapp' ) );
	}

	$args['url'] = sanitize_url( $args['url'] );

	if ( ! function_exists( 'download_url' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/file.php' );
	}

	if ( ! function_exists( 'media_handle_sideload' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/media.php' );
	}

	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		include_once( ABSPATH . 'wp-admin/includes/image.php' );
	}

	$download = download_url( $args['url'] );
	if ( is_wp_error( $download ) ) {
		throw new \Exception( $download->get_error_message() );
	}

	$filename = ! empty( $args['filename'] ) ? sanitize_file_name( $args['filename'] ) : '';
	if ( ! empty( $filename ) ) {
		$filename_parts = pathinfo( $filename );
		if ( empty( $filename_parts['extension'] ) ) {
			$url_parts = pathinfo( parse_url( $args['url'], PHP_URL_PATH ) );
        	$filename  = $filename_parts['filename'] . '.' . $url_parts['extension'];
		}
	} else {
		$filename = sanitize_file_name( basename( parse_url( $args['url'], PHP_URL_PATH ) ) );
	}

	$upload_dir      = wp_upload_dir();
    $unique_filename = wp_unique_filename( $upload_dir['path'], $filename );
	$filename_parts  = pathinfo( $unique_filename );
	if ( empty( $filename_parts['extension'] ) || ! in_array( strtolower( $filename_parts['extension'] ), array( 'jpg', 'jpeg', 'png', 'gif' ) ) ) {
		throw new \Exception( __( 'Invalid image extension.', 'asnp-easy-whatsapp' ) );
	}

	$title       = ! empty( $args['title'] ) ? sanitize_file_name( $args['title'] ) : sanitize_file_name( $filename );
	$description = ! empty( $args['description'] ) ? sanitize_text_field( $args['description'] ) : '';
	$caption     = ! empty( $args['caption'] ) ? sanitize_text_field( $args['caption'] ) : '';

	$attach_id = media_handle_sideload(
		[
			'name'     => $unique_filename,
			'tmp_name' => $download
		],
		0,
		null,
		[
			'post_title'   => $title,
			'post_content' => $description,
			'post_excerpt' => $caption,
		]
	);
	if ( is_wp_error( $attach_id ) ) {
		throw new \Exception( $attach_id->get_error_message() );
	}

	if ( ! empty( $args['alt'] ) ) {
		update_post_meta( $attach_id, '_wp_attachment_image_alt', sanitize_text_field( $args['alt'] ) );
	}

	$path = get_attached_file( $attach_id );
	if ( empty( $path ) ) {
		throw new \Exception( __( 'Error occurred on getting image file.', 'asnp-easy-whatsapp' ) );
	}

	$attach_data = wp_generate_attachment_metadata( $attach_id, $path );
	wp_update_attachment_metadata( $attach_id, $attach_data );

    return $attach_id;
}

function is_pro_active() {
	return defined( 'ASNP_EWHATSAPP_PRO_VERSION' );
}
