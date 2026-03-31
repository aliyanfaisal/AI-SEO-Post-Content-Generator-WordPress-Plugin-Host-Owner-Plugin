<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AISCPH_Admin {

	public function init() {
		add_action( 'admin_menu',             array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_aiscph_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_aiscph_clear_log',     array( $this, 'ajax_clear_log' ) );
	}

	public function register_menus() {
		add_menu_page(
			__( 'AI SEO Host', 'aiscp-host' ),
			__( 'AI SEO Host', 'aiscp-host' ),
			'manage_options',
			'aiscph-dashboard',
			array( $this, 'render_settings' ),
			'dashicons-admin-network',
			25
		);

		add_submenu_page(
			'aiscph-dashboard',
			__( 'API Settings', 'aiscp-host' ),
			__( 'API Settings', 'aiscp-host' ),
			'manage_options',
			'aiscph-dashboard',
			array( $this, 'render_settings' )
		);

		add_submenu_page(
			'aiscph-dashboard',
			__( 'Subscriptions', 'aiscp-host' ),
			__( 'Subscriptions', 'aiscp-host' ),
			'manage_options',
			'aiscph-subscriptions',
			array( $this, 'render_subscriptions' )
		);

		add_submenu_page(
			'aiscph-dashboard',
			__( 'Generation Log', 'aiscp-host' ),
			__( 'Generation Log', 'aiscp-host' ),
			'manage_options',
			'aiscph-log',
			array( $this, 'render_log' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'aiscph' ) === false ) return;

		wp_enqueue_style(
			'aiscph-admin',
			AISCPH_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			AISCPH_VERSION
		);

		wp_enqueue_script(
			'aiscph-admin',
			AISCPH_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			AISCPH_VERSION,
			true
		);

		wp_localize_script( 'aiscph-admin', 'AISCPH', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'aiscph_nonce' ),
			'strings'  => array(
				'saving' => __( 'Saving...', 'aiscp-host' ),
				'saved'  => __( 'Settings saved!', 'aiscp-host' ),
				'error'  => __( 'An error occurred.', 'aiscp-host' ),
			),
		) );
	}

	public function render_settings() {
		$current_page = 'aiscph-dashboard';
		include AISCPH_PLUGIN_DIR . 'admin/views/settings.php';
	}

	public function render_subscriptions() {
		$current_page = 'aiscph-subscriptions';
		include AISCPH_PLUGIN_DIR . 'admin/views/subscriptions.php';
	}

	public function render_log() {
		$current_page = 'aiscph-log';
		$log_enabled  = AISCPH_Settings::get( 'generation_log', '1' );
		$log_entries  = AISCPH_Generator::get_log();
		include AISCPH_PLUGIN_DIR . 'admin/views/log.php';
	}

	public function ajax_save_settings() {
		check_ajax_referer( 'aiscph_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );

		AISCPH_Settings::save( $_POST );
		wp_send_json_success( array( 'message' => __( 'Settings saved successfully!', 'aiscp-host' ) ) );
	}

	public function ajax_clear_log() {
		check_ajax_referer( 'aiscph_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( -1 );

		delete_option( 'aiscph_generation_log_entries' );
		delete_option( 'aiscph_debug_claude_raw' );
		delete_option( 'aiscph_debug_webhook_payload' );
		delete_option( 'aiscph_debug_webhook_response' );

		wp_send_json_success( array( 'message' => __( 'Log cleared.', 'aiscp-host' ) ) );
	}
}
