<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AISCPH_Generator {

	/**
	 * Main entry point — generate a post and return structured data to client.
	 *
	 * @param array $preferences  Preferences sent from the client plugin.
	 * @return array              Standard response array.
	 */
	public static function generate( $preferences ) {
		$model = $preferences['ai_model'] ?? AISCPH_Settings::get( 'default_model', 'claude' );

		if ( $model === 'claude' ) {
			$result = AISCPH_Claude::generate_post( $preferences );
		} else {
			return array(
				'success' => false,
				'message' => 'OpenAI module is not yet implemented.',
				'data'    => array(),
			);
		}

		if ( is_wp_error( $result ) ) {
			self::log( 'Generation failed: ' . $result->get_error_message(), $preferences['domain'] ?? '' );
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
				'data'    => array(),
			);
		}

		self::log(
			sprintf( 'Post generated: "%s" for domain %s', $result['title'] ?? 'unknown', $preferences['domain'] ?? '' ),
			$preferences['domain'] ?? ''
		);

		// Fetch images if stock images or thumbnails enabled
		$want_stock    = ! empty( $preferences['enable_stock_images'] ) && $preferences['enable_stock_images'] === '1';
		$want_ai_thumb = ! empty( $preferences['enable_thumbnails'] )   && $preferences['enable_thumbnails']   === '1';

		if ( $want_stock || $want_ai_thumb ) {
			$image_queries = $result['image_queries'] ?? array();

			// Fallback: if Claude returned no image queries use post title
			if ( empty( $image_queries ) ) {
				$image_queries = array( 1 => $result['title'] ?? 'blog post header' );
			}

			$fetched_urls = array();
			foreach ( $image_queries as $index => $query ) {
				$url = AISCPH_Image::get_image_url( $query, $preferences );
				if ( $url ) {
					$fetched_urls[ $index ] = $url;
					self::log( 'Image ' . $index . ' fetched: ' . $query . ' => ' . $url, $preferences['domain'] ?? '' );
				} else {
					self::log( "Image {$index} fetch failed for query: {$query}", $preferences['domain'] ?? '' );
				}
			}

			// image_1 is always the featured image
			if ( ! empty( $fetched_urls[1] ) ) {
				$result['thumbnail_url'] = $fetched_urls[1];
			}

			// Pass all fetched URLs indexed so client can replace {{IMAGE_N}} placeholders
			$result['image_urls'] = $fetched_urls;
		}

		// Clean up internal field not needed by client
		unset( $result['image_queries'] );

		return array(
			'success' => true,
			'message' => 'Post generated successfully.',
			'data'    => $result,
		);
	}

	/**
	 * Log a generation event if logging is enabled.
	 */
	public static function log( $message, $domain = '' ) {
		if ( AISCPH_Settings::get( 'generation_log', '1' ) !== '1' ) return;

		$log   = get_option( 'aiscph_generation_log_entries', array() );
		$log[] = array(
			'time'    => current_time( 'mysql' ),
			'domain'  => $domain,
			'message' => $message,
		);
		// Keep last 100 entries
		if ( count( $log ) > 100 ) {
			$log = array_slice( $log, -100 );
		}
		update_option( 'aiscph_generation_log_entries', $log );
	}

	public static function get_log() {
		return array_reverse( get_option( 'aiscph_generation_log_entries', array() ) );
	}
}
