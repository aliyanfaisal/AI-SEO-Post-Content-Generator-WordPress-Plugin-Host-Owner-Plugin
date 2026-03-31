<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AISCPH_Settings {

	public static function get( $key, $default = '' ) {
		return get_option( 'aiscph_' . $key, $default );
	}

	public static function get_claude_key() {
		return AISCPH_Crypto::decrypt( get_option( 'aiscph_claude_api_key', '' ) );
	}

	public static function get_openai_key() {
		return AISCPH_Crypto::decrypt( get_option( 'aiscph_openai_api_key', '' ) );
	}

	public static function get_pexels_key() {
		return AISCPH_Crypto::decrypt( get_option( 'aiscph_pexels_api_key', '' ) );
	}

	public static function get_unsplash_key() {
		return AISCPH_Crypto::decrypt( get_option( 'aiscph_unsplash_api_key', '' ) );
	}

	public static function get_shutterstock_key() {
		return AISCPH_Crypto::decrypt( get_option( 'aiscph_shutterstock_api_key', '' ) );
	}

	public static function get_shutterstock_secret() {
		return AISCPH_Crypto::decrypt( get_option( 'aiscph_shutterstock_api_secret', '' ) );
	}

	public static function save( $data ) {
		// Encrypt API keys if provided and not masked
		$encrypted_keys = array( 'claude_api_key', 'openai_api_key', 'pexels_api_key', 'unsplash_api_key', 'shutterstock_api_key', 'shutterstock_api_secret' );
		foreach ( $encrypted_keys as $key ) {
			if ( ! empty( $data[ $key ] ) && strpos( $data[ $key ], '•' ) === false ) {
				update_option( 'aiscph_' . $key, AISCPH_Crypto::encrypt( sanitize_text_field( $data[ $key ] ) ) );
			}
		}

		// Plain settings
		$plain_fields = array( 'global_prompt', 'post_generation_instructions', 'max_tokens', 'max_content_words', 'default_model', 'stock_image_service' ); // note: image API keys handled via encrypted_keys above
		foreach ( $plain_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				update_option( 'aiscph_' . $field, sanitize_textarea_field( $data[ $field ] ) );
			}
		}

		// Checkbox
		update_option( 'aiscph_generation_log', isset( $data['generation_log'] ) ? '1' : '0' );
	}
}
