<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AISCPH_Image
 *
 * Handles stock image fetching for generated posts.
 * Supports: Pexels, Unsplash, Shutterstock
 *
 * Returns a direct image URL which is passed in the webhook payload.
 * The client plugin downloads the image and sets it as the featured image.
 */
class AISCPH_Image {

	public static function get_image_url( $image_prompt, $preferences ) {
		$want_stock    = ! empty( $preferences['enable_stock_images'] ) && $preferences['enable_stock_images'] === '1';
		$want_ai_thumb = ! empty( $preferences['enable_thumbnails'] )   && $preferences['enable_thumbnails']   === '1';

		if ( ! $want_stock && ! $want_ai_thumb ) {
			return null;
		}

		return self::fetch_stock_image( $image_prompt );
	}

	// ---------------------------------------------------------------
	// Route to selected service
	// ---------------------------------------------------------------

	private static function fetch_stock_image( $query ) {
		$service = AISCPH_Settings::get( 'stock_image_service', 'pexels' );

		switch ( $service ) {
			case 'unsplash':
				return self::fetch_unsplash( $query );
			case 'shutterstock':
				return self::fetch_shutterstock( $query );
			default:
				return self::fetch_pexels( $query );
		}
	}

	// ---------------------------------------------------------------
	// Pexels
	// ---------------------------------------------------------------

	private static function fetch_pexels( $query ) {
		$api_key = AISCPH_Settings::get_pexels_key();
		if ( empty( $api_key ) ) {
			AISCPH_Generator::log( 'Pexels API key not configured.' );
			return null;
		}

		$response = wp_remote_get( add_query_arg( array(
			'query'       => urlencode( $query ),
			'per_page'    => 1,
			'orientation' => 'landscape',
		), 'https://api.pexels.com/v1/search' ), array(
			'timeout' => 15,
			'headers' => array( 'Authorization' => $api_key ),
		) );

		if ( is_wp_error( $response ) ) {
			AISCPH_Generator::log( 'Pexels error: ' . $response->get_error_message() );
			return null;
		}

		$body  = json_decode( wp_remote_retrieve_body( $response ), true );
		$photo = $body['photos'][0] ?? null;

		if ( empty( $photo ) ) {
			AISCPH_Generator::log( 'Pexels: no results for: ' . $query );
			return null;
		}

		$url = $photo['src']['large2x'] ?? $photo['src']['large'] ?? $photo['src']['original'] ?? null;
		AISCPH_Generator::log( 'Pexels image fetched: ' . $url );
		return $url;
	}

	// ---------------------------------------------------------------
	// Unsplash
	// ---------------------------------------------------------------

	private static function fetch_unsplash( $query ) {
		$api_key = AISCPH_Settings::get_unsplash_key();
		if ( empty( $api_key ) ) {
			AISCPH_Generator::log( 'Unsplash API key not configured.' );
			return null;
		}

		$response = wp_remote_get( add_query_arg( array(
			'query'       => urlencode( $query ),
			'per_page'    => 1,
			'orientation' => 'landscape',
			'client_id'   => $api_key,
		), 'https://api.unsplash.com/search/photos' ), array(
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			AISCPH_Generator::log( 'Unsplash error: ' . $response->get_error_message() );
			return null;
		}

		$body   = json_decode( wp_remote_retrieve_body( $response ), true );
		$result = $body['results'][0] ?? null;

		if ( empty( $result ) ) {
			AISCPH_Generator::log( 'Unsplash: no results for: ' . $query );
			return null;
		}

		$url = $result['urls']['regular'] ?? $result['urls']['full'] ?? null;
		AISCPH_Generator::log( 'Unsplash image fetched: ' . $url );
		return $url;
	}

	// ---------------------------------------------------------------
	// Shutterstock
	// ---------------------------------------------------------------

	private static function fetch_shutterstock( $query ) {
		$api_key    = AISCPH_Settings::get_shutterstock_key();
		$api_secret = AISCPH_Settings::get_shutterstock_secret();

		if ( empty( $api_key ) || empty( $api_secret ) ) {
			AISCPH_Generator::log( 'Shutterstock API key/secret not configured.' );
			return null;
		}

		// Shutterstock uses HTTP Basic Auth with key:secret
		$response = wp_remote_get( add_query_arg( array(
			'query'        => urlencode( $query ),
			'per_page'     => 1,
			'orientation'  => 'horizontal',
			'image_type'   => 'photo',
			'safe'         => 'true',
		), 'https://api.shutterstock.com/v2/images/search' ), array(
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ),
				'Accept'        => 'application/json',
			),
		) );

		if ( is_wp_error( $response ) ) {
			AISCPH_Generator::log( 'Shutterstock error: ' . $response->get_error_message() );
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 || empty( $body['data'][0] ) ) {
			AISCPH_Generator::log( 'Shutterstock: no results for: ' . $query . ' (HTTP ' . $code . ')' );
			return null;
		}

		$image = $body['data'][0];

		// Prefer large preview, fall back to smaller ones
		$url = $image['assets']['huge_thumb']['url']
			?? $image['assets']['large_thumb']['url']
			?? $image['assets']['preview']['url']
			?? null;

		AISCPH_Generator::log( 'Shutterstock image fetched: ' . $url );
		return $url;
	}
}
