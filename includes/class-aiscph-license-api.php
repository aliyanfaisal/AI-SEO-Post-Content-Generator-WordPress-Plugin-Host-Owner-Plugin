<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AISCPH_License_API {

	/**
	 * Validate a domain against active subscriptions.
	 * Phase 1: always returns active = true.
	 * Phase 2: will query WooCommerce subscriptions.
	 */
	public static function validate( $domain ) {
		if ( empty( $domain ) ) {
			return array(
				'success' => false,
				'message' => 'Domain is required.',
				'data'    => array(),
			);
		}

		// Phase 1 — always active
		return array(
			'success' => true,
			'message' => 'License is active.',
			'data'    => array(
				'domain'      => $domain,
				'plan'        => 'Professional',
				'posts_limit' => 50,
				'expires'     => date( 'Y-m-d', strtotime( '+30 days' ) ),
				'status'      => 'active',
			),
		);

		/*
		 * Phase 2 implementation (uncomment when WooCommerce subscriptions are set up):
		 *
		 * $subscriptions = wcs_get_subscriptions( array(
		 *     'subscription_status' => 'active',
		 *     'meta_key'            => '_aiscp_domain',
		 *     'meta_value'          => sanitize_text_field( $domain ),
		 * ) );
		 *
		 * if ( empty( $subscriptions ) ) {
		 *     return array( 'success' => false, 'message' => 'No active subscription for this domain.' );
		 * }
		 * ...
		 */
	}
}
