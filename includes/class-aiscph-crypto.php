<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AISCPH_Crypto {

	private static function get_key() {
		// Derive a site-specific key from WP salts
		return substr( hash( 'sha256', wp_salt( 'auth' ) . SECURE_AUTH_KEY ), 0, 32 );
	}

	private static function get_iv_length() {
		return openssl_cipher_iv_length( 'aes-256-cbc' );
	}

	public static function encrypt( $plain_text ) {
		if ( empty( $plain_text ) ) return '';
		$iv         = openssl_random_pseudo_bytes( self::get_iv_length() );
		$encrypted  = openssl_encrypt( $plain_text, 'aes-256-cbc', self::get_key(), 0, $iv );
		return base64_encode( $iv . '::' . $encrypted );
	}

	public static function decrypt( $cipher_text ) {
		if ( empty( $cipher_text ) ) return '';
		$decoded = base64_decode( $cipher_text );
		if ( strpos( $decoded, '::' ) === false ) return '';
		list( $iv, $encrypted ) = explode( '::', $decoded, 2 );
		return openssl_decrypt( $encrypted, 'aes-256-cbc', self::get_key(), 0, $iv );
	}

	/**
	 * Mask a key for display — shows last 6 chars only.
	 */
	public static function mask( $plain_text ) {
		if ( empty( $plain_text ) ) return '';
		$len = strlen( $plain_text );
		return str_repeat( '•', max( $len - 6, 6 ) ) . substr( $plain_text, -6 );
	}
}
