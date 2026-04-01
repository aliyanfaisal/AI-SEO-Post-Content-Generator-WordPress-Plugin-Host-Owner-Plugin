<?php
/**
 * Plugin Name: AI SEO Content — Host Plugin
 * Plugin URI:  https://aliyanfaisal.com
 * Description: Host plugin for AI SEO Content Generator. Handles licensing, Claude AI generation, and post delivery to client sites.
 * Version:     1.0.3
 * Author:      Aliyan Faisal
 * Author URI:  https://aliyanfaisal.com
 * Text Domain: aiscp-host
 * License:     GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'AISCPH_VERSION',    '1.0.3' );
define( 'AISCPH_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'AISCPH_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'AISCPH_PLUGIN_FILE', __FILE__ );

require_once AISCPH_PLUGIN_DIR . 'includes/class-aiscph-crypto.php';
require_once AISCPH_PLUGIN_DIR . 'includes/class-aiscph-settings.php';
require_once AISCPH_PLUGIN_DIR . 'includes/class-aiscph-license-api.php';
require_once AISCPH_PLUGIN_DIR . 'includes/class-aiscph-claude.php';
require_once AISCPH_PLUGIN_DIR . 'includes/class-aiscph-generator.php';
require_once AISCPH_PLUGIN_DIR . 'includes/class-aiscph-image.php';
require_once AISCPH_PLUGIN_DIR . 'includes/class-aiscph-rest-api.php';
require_once AISCPH_PLUGIN_DIR . 'includes/class-aiscph-admin.php';

register_activation_hook( __FILE__, 'aiscph_activate' );
register_deactivation_hook( __FILE__, 'aiscph_deactivate' );

function aiscph_activate() {
	$defaults = array(
		'claude_api_key'       => '',
		'openai_api_key'       => '',
		'generation_log'       => '1',
		'global_prompt'        => '',
		'max_tokens'           => '4000',
		'max_content_words'    => '800',
		'max_content_words'    => '800',
		'default_model'                => 'claude',
		'post_generation_instructions' => '',
		'pexels_api_key'       => '',
		'unsplash_api_key'     => '',
		'stock_image_service'       => 'pexels',
		'shutterstock_api_key'      => '',
		'shutterstock_api_secret'   => '',
	);
	foreach ( $defaults as $key => $value ) {
		if ( false === get_option( 'aiscph_' . $key ) ) {
			update_option( 'aiscph_' . $key, $value );
		}
	}
}

function aiscph_deactivate() {}

function aiscph_init() {
	$rest  = new AISCPH_Rest_API();
	$rest->init();
	$admin = new AISCPH_Admin();
	$admin->init();
}
add_action( 'plugins_loaded', 'aiscph_init' );
