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

		// Fetch image if needed
		$want_stock    = ! empty( $preferences['enable_stock_images'] ) && $preferences['enable_stock_images'] === '1';
		$want_ai_thumb = ! empty( $preferences['enable_thumbnails'] )   && $preferences['enable_thumbnails']   === '1';

		if ( $want_stock || $want_ai_thumb ) {
			$image_prompt = ! empty( $result['image_prompt'] ) ? $result['image_prompt'] : $result['title'];
			$image_url    = AISCPH_Image::get_image_url( $image_prompt, $preferences );
			if ( $image_url ) {
				$result['thumbnail_url'] = $image_url;
				self::log( 'Image URL added to payload: ' . $image_url, $preferences['domain'] ?? '' );
			} else {
				self::log( 'Image fetch failed or no API key set.', $preferences['domain'] ?? '' );
			}
		}

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
