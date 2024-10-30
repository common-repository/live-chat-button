<?php

namespace AsanaPlugins\WhatsApp\Helpers\AI;

function is_valid_model( $model ) {
	if ( empty( $model ) ) {
		throw new \Exception( __( 'Model is required.', 'asnp-easy-whatsapp' ) );
	}

	$models = [
		'gpt-4',
		'gpt-4-32k',
		'gpt-3.5-turbo',
		'text-davinci-003',
		'text-curie-001',
		'text-babbage-001',
		'text-ada-001',
		'text-embedding-ada-002',
	];

	return in_array( $model, $models );
}

function is_valid_image_model( $model ) {
	if ( empty( $model ) ) {
		throw new \Exception( __( 'Model is required.', 'asnp-easy-whatsapp' ) );
	}

	return in_array( $model, [ 'dall-e', 'dalle-2' ] );
}

function check_max_tokens( $model, $max_tokens ) {
	if ( empty( $model ) ) {
		throw new \Exception( __( 'Model is required.', 'asnp-easy-whatsapp' ) );
	}

	if ( empty( $max_tokens ) ) {
		throw new \Exception( __( 'Max tokens are required.', 'asnp-easy-whatsapp' ) );
	}

	if ( 0 > (int) $max_tokens ) {
		throw new \Exception( __( 'Max tokens must be a positive integer.', 'asnp-easy-whatsapp' ) );
	}

	if ( 2048 >= (int) $max_tokens ) {
		return true;
	}

	$model_max_tokens = [
		'gpt-4' => 8192,
		'gpt-4-32k' => 32768,
		'gpt-3.5-turbo' => 4096,
		'text-davinci-003' => 2048,
		'text-curie-001' => 2048,
		'text-babbage-001' => 2048,
		'text-ada-001' => 2048,
	];

	if ( ! isset( $model_max_tokens[ $model ] ) ) {
		throw new \Exception( __( 'Invalid model.', 'asnp-easy-whatsapp' ) );
	}

	if ( (int) $max_tokens > (int) $model_max_tokens[ $model ] ) {
		throw new \Exception( sprintf( __( 'Max tokens must be less or equal to %d for the %s model.', 'asnp-easy-whatsapp' ), absint( $model_max_tokens[ $model ] ), sanitize_text_field( $model ) ) );
	}

	return true;
}

function get_valid_temprature( $temprature ) {
	$temprature = (float) $temprature;
	$temprature = 0 > $temprature ? 0 : $temprature;
	$temprature = 1 < $temprature ? 1 : $temprature;
	return round( $temprature, 2 );
}
