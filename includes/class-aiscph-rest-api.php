<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AISCPH_Rest_API {

	const NAMESPACE = 'aiscp/v1';

	public function init() {
		add_action( 'rest_api_init',       array( $this, 'register_routes' ) );
		add_action( 'aiscph_process_job',  array( $this, 'process_job' ) );
	}

	public function register_routes() {

		// License validation
		register_rest_route( self::NAMESPACE, '/license/validate', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_license_validate' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'domain' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
			),
		) );

		// Async post generation — accepts job, returns job_id immediately
		register_rest_route( self::NAMESPACE, '/generate/post', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'handle_generate_post' ),
			'permission_callback' => array( $this, 'verify_request' ),
		) );
	}

	public function verify_request( WP_REST_Request $request ) {
		$domain = sanitize_text_field( $request->get_param( 'domain' ) ?? '' );
		if ( empty( $domain ) ) return false;
		$license = AISCPH_License_API::validate( $domain );
		return ! empty( $license['success'] );
	}

	// POST /license/validate
	public function handle_license_validate( WP_REST_Request $request ) {
		$domain = $request->get_param( 'domain' );
		$result = AISCPH_License_API::validate( $domain );
		return new WP_REST_Response( $result, $result['success'] ? 200 : 403 );
	}

	/**
	 * POST /generate/post
	 *
	 * Immediately queues a background job and returns job_id.
	 * Claude is called via WP-Cron so no timeout risk.
	 * Result is delivered to the client's webhook_url.
	 */
	public function handle_generate_post( WP_REST_Request $request ) {
		$preferences = $request->get_json_params() ?: $request->get_body_params();

		if ( empty( $preferences ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'message' => 'No preferences received.',
			), 400 );
		}

		if ( empty( $preferences['webhook_url'] ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'message' => 'webhook_url is required.',
			), 400 );
		}

		// Create a unique job ID
		$job_id = 'aiscph_' . wp_generate_uuid4();

		// Store job data for the cron to pick up
		set_transient( $job_id, array(
			'preferences' => $preferences,
			'webhook_url' => esc_url_raw( $preferences['webhook_url'] ),
			'job_id'      => $job_id,
			'queued_at'   => current_time( 'mysql' ),
		), HOUR_IN_SECONDS );

		// Schedule processing in 5 seconds via WP-Cron
		wp_schedule_single_event( time() + 5, 'aiscph_process_job', array( $job_id ) );

		AISCPH_Generator::log( "Job {$job_id} queued for domain: " . ( $preferences['domain'] ?? '' ), $preferences['domain'] ?? '' );

		// Return immediately — client is not kept waiting
		return new WP_REST_Response( array(
			'success' => true,
			'message' => 'Job queued. Result will be sent to your webhook.',
			'job_id'  => $job_id,
		), 202 );
	}

	/**
	 * WP-Cron handler — runs Claude and delivers result to client webhook.
	 */
	public function process_job( $job_id ) {
		$job = get_transient( $job_id );

		if ( ! $job ) {
			AISCPH_Generator::log( "Job {$job_id} not found or expired." );
			return;
		}

		$preferences = $job['preferences'];
		$webhook_url = $job['webhook_url'];

		AISCPH_Generator::log( "Processing job {$job_id}...", $preferences['domain'] ?? '' );

		// Run generation (this is now in cron — no HTTP timeout)
		$result = AISCPH_Generator::generate( $preferences );

		// Deliver result to client webhook
		$payload = array_merge( $result, array( 'job_id' => $job_id ) );

		// Store webhook payload for debugging
		update_option( 'aiscph_debug_webhook_payload', array(
			'time'        => current_time( 'mysql' ),
			'job_id'      => $job_id,
			'webhook_url' => $webhook_url,
			'payload'     => $payload,
		) );

		$response = wp_remote_post( $webhook_url, array(
			'timeout'  => 15,
			'headers'  => array( 'Content-Type' => 'application/json' ),
			'body'     => wp_json_encode( $payload ),
		) );

		if ( is_wp_error( $response ) ) {
			AISCPH_Generator::log( "Webhook delivery failed for job {$job_id}: " . $response->get_error_message(), $preferences['domain'] ?? '' );
			update_option( 'aiscph_debug_webhook_payload', array_merge(
				get_option( 'aiscph_debug_webhook_payload', array() ),
				array( 'delivery_error' => $response->get_error_message() )
			) );
		} else {
			$webhook_response_body = wp_remote_retrieve_body( $response );
			AISCPH_Generator::log( "Job {$job_id} complete. Webhook delivered to {$webhook_url}.", $preferences['domain'] ?? '' );
			// Store webhook response for debugging
			update_option( 'aiscph_debug_webhook_response', array(
				'time'          => current_time( 'mysql' ),
				'job_id'        => $job_id,
				'response_code' => wp_remote_retrieve_response_code( $response ),
				'response_body' => $webhook_response_body,
			) );
		}

		// Clean up transient
		delete_transient( $job_id );
	}
}
