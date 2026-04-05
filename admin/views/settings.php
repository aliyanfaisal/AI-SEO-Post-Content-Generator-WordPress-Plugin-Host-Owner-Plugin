<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="aiscph-wrap">

	<?php include __DIR__ . '/partials/header.php'; ?>

	<div class="aiscph-page-content">
		<div id="aiscph-notice" class="aiscph-notice" style="display:none;"></div>

		<form id="aiscph-settings-form">

			<!-- AI API Keys -->
			<div class="aiscph-card">
				<div class="aiscph-card-header">
					<h2><?php _e( 'AI API Keys', 'aiscp-host' ); ?></h2>
					<p><?php _e( 'Configure your AI provider credentials. Keys are encrypted before storage.', 'aiscp-host' ); ?></p>
				</div>
				<div class="aiscph-card-body">

					<div class="aiscph-field">
						<label for="claude_api_key"><?php _e( 'Anthropic (Claude) API Key', 'aiscp-host' ); ?></label>
						<div class="aiscph-key-input-wrap">
							<input
								type="password"
								id="claude_api_key"
								name="claude_api_key"
								value="<?php echo esc_attr( AISCPH_Crypto::mask( AISCPH_Settings::get_claude_key() ) ); ?>"
								placeholder="sk-ant-..."
								autocomplete="new-password"
							/>
							<button type="button" class="aiscph-toggle-key-visibility" data-target="claude_api_key">
								<span class="eye-show">👁</span>
							</button>
						</div>
						<span class="aiscph-field-hint">
							<?php _e( 'Get your key from', 'aiscp-host' ); ?>
							<a href="https://console.anthropic.com/keys" target="_blank">console.anthropic.com</a>.
						</span>
					</div>

					<div class="aiscph-field-row" style="grid-template-columns:1fr 1fr 1fr;">
						<div class="aiscph-field">
							<label for="default_model"><?php _e( 'Default AI Model', 'aiscp-host' ); ?></label>
							<select id="default_model" name="default_model">
								<option value="claude" <?php selected( AISCPH_Settings::get( 'default_model', 'claude' ), 'claude' ); ?>><?php _e( 'Claude (Anthropic)', 'aiscp-host' ); ?></option>
								<option value="openai" <?php selected( AISCPH_Settings::get( 'default_model', 'claude' ), 'openai' ); ?> disabled><?php _e( 'GPT-4 (OpenAI) — Coming Soon', 'aiscp-host' ); ?></option>
							</select>
						</div>
						<div class="aiscph-field">
							<label for="max_tokens"><?php _e( 'Max Tokens per Post', 'aiscp-host' ); ?></label>
							<input type="number" id="max_tokens" name="max_tokens" min="500" max="8000" step="100"
								value="<?php echo esc_attr( AISCPH_Settings::get( 'max_tokens', '4000' ) ); ?>">
							<span class="aiscph-field-hint"><?php _e( 'Recommended: 4000–8000.', 'aiscp-host' ); ?></span>
						</div>
						<div class="aiscph-field">
							<label for="max_content_words"><?php _e( 'Max Content Words', 'aiscp-host' ); ?></label>
							<input type="number" id="max_content_words" name="max_content_words" min="100" max="5000" step="50"
								value="<?php echo esc_attr( AISCPH_Settings::get( 'max_content_words', '800' ) ); ?>">
							<span class="aiscph-field-hint"><?php _e( 'Max words for the generated article body. Recommended: 600–1200.', 'aiscp-host' ); ?></span>
						</div>
					</div>

				</div>
			</div>

			<!-- Image Services -->
			<div class="aiscph-card">
				<div class="aiscph-card-header">
					<h2><?php _e( 'Image Services', 'aiscp-host' ); ?></h2>
					<p><?php _e( 'Configure APIs for stock images and AI-generated thumbnails.', 'aiscp-host' ); ?></p>
				</div>
				<div class="aiscph-card-body">

					<div class="aiscph-field">
						<label for="stock_image_service"><?php _e( 'Stock Image Service', 'aiscp-host' ); ?></label>
						<select id="stock_image_service" name="stock_image_service" style="max-width:300px;">
							<option value="pexels" <?php selected( AISCPH_Settings::get( 'stock_image_service', 'pexels' ), 'pexels' ); ?>><?php _e( 'Pexels', 'aiscp-host' ); ?></option>
							<option value="unsplash" <?php selected( AISCPH_Settings::get( 'stock_image_service', 'pexels' ), 'unsplash' ); ?>><?php _e( 'Unsplash', 'aiscp-host' ); ?></option>
								<option value="shutterstock" <?php selected( AISCPH_Settings::get( 'stock_image_service', 'pexels' ), 'shutterstock' ); ?>><?php _e( 'Shutterstock', 'aiscp-host' ); ?></option>
						</select>
						<span class="aiscph-field-hint"><?php _e( 'Used for both Stock Images and Auto Thumbnails.', 'aiscp-host' ); ?></span>
					</div>

					<div class="aiscph-field-row">
						<div class="aiscph-field">
							<label for="pexels_api_key"><?php _e( 'Pexels API Key', 'aiscp-host' ); ?></label>
							<div class="aiscph-key-input-wrap">
								<input type="password" id="pexels_api_key" name="pexels_api_key"
									value="<?php echo esc_attr( AISCPH_Crypto::mask( AISCPH_Settings::get( 'pexels_api_key', '' ) ) ); ?>"
									placeholder="<?php esc_attr_e( 'Your Pexels API key', 'aiscp-host' ); ?>" autocomplete="new-password">
								<button type="button" class="aiscph-toggle-key-visibility" data-target="pexels_api_key"><span class="eye-show">👁</span></button>
							</div>
							<span class="aiscph-field-hint"><a href="https://www.pexels.com/api/" target="_blank">pexels.com/api</a></span>
						</div>
						<div class="aiscph-field">
							<label for="unsplash_api_key"><?php _e( 'Unsplash Access Key', 'aiscp-host' ); ?></label>
							<div class="aiscph-key-input-wrap">
								<input type="password" id="unsplash_api_key" name="unsplash_api_key"
									value="<?php echo esc_attr( AISCPH_Crypto::mask( AISCPH_Settings::get( 'unsplash_api_key', '' ) ) ); ?>"
									placeholder="<?php esc_attr_e( 'Your Unsplash Access Key', 'aiscp-host' ); ?>" autocomplete="new-password">
								<button type="button" class="aiscph-toggle-key-visibility" data-target="unsplash_api_key"><span class="eye-show">👁</span></button>
							</div>
							<span class="aiscph-field-hint"><a href="https://unsplash.com/developers" target="_blank">unsplash.com/developers</a></span>
						</div>
					</div>



				</div>
			</div>

			<!-- Section Prompts -->
			<div class="aiscph-card">
				<div class="aiscph-card-header">
					<h2><?php _e( 'Post Section Prompts', 'aiscp-host' ); ?></h2>
					<p><?php _e( 'Define named sections and their dedicated prompts. Claude will generate each section in order, strictly following each prompt. Cached for token efficiency.', 'aiscp-host' ); ?></p>
				</div>
				<div class="aiscph-card-body">

					<div id="aiscph-sections-repeater">
						<?php
						$sections = AISCPH_Settings::get_sections();
						if ( empty( $sections ) ) {
							$sections = array( array( 'name' => '', 'prompt' => '' ) );
						}
						foreach ( $sections as $i => $section ) :
						?>
						<div class="aiscph-section-row" data-index="<?php echo $i; ?>">
							<div class="aiscph-section-row-header">
								<span class="aiscph-section-row-num"><?php echo $i + 1; ?></span>
								<input type="text"
									name="sections[<?php echo $i; ?>][name]"
									class="aiscph-section-name"
									placeholder="<?php esc_attr_e( 'e.g. Introduction, Main Content, FAQ, Summary', 'aiscp-host' ); ?>"
									value="<?php echo esc_attr( $section['name'] ); ?>">
								<button type="button" class="aiscph-remove-section" title="Remove section">&times;</button>
							</div>
							<textarea
								name="sections[<?php echo $i; ?>][prompt]"
								class="aiscph-section-prompt"
								rows="4"
								placeholder="<?php esc_attr_e( 'Write the specific prompt for this section. Claude will follow this exactly when generating this part of the post.', 'aiscp-host' ); ?>"><?php echo esc_textarea( $section['prompt'] ); ?></textarea>
						</div>
						<?php endforeach; ?>
					</div>

					<button type="button" id="aiscph-add-section" class="aiscph-btn aiscph-btn-ghost" style="margin-top:12px;">
						+ <?php _e( 'Add Section', 'aiscp-host' ); ?>
					</button>

				</div>
			</div>

			<!-- Global Prompt -->
			<div class="aiscph-card">
				<div class="aiscph-card-header">
					<h2><?php _e( 'Global Prompt', 'aiscp-host' ); ?></h2>
					<p><?php _e( 'These instructions are appended to every generation request across all client sites.', 'aiscp-host' ); ?></p>
				</div>
				<div class="aiscph-card-body">
					<div class="aiscph-field-row">
						<div class="aiscph-field">
							<label for="post_generation_instructions"><?php _e( 'Global Post Generation Instructions', 'aiscp-host' ); ?>
								<?php if ( ! empty( AISCPH_Settings::get( 'post_generation_instructions' ) ) ) : ?>
									<span class="aiscph-mode-badge aiscph-mode-badge--project"><?php _e( '✓ Cached', 'aiscp-host' ); ?></span>
								<?php endif; ?>
							</label>
							<textarea id="post_generation_instructions" name="post_generation_instructions" rows="10"
								placeholder="<?php esc_attr_e( 'Define how Claude should write content for your clients. Include tone guidelines, writing rules, content structure requirements, SEO standards, and any domain-specific instructions the AI must follow on every post generation.', 'aiscp-host' ); ?>"
							><?php echo esc_textarea( AISCPH_Settings::get( 'post_generation_instructions', '' ) ); ?></textarea>
							 
						</div>
						<div class="aiscph-field">
							<label for="global_prompt"><?php _e( 'Global Prompt', 'aiscp-host' ); ?></label>
							<textarea id="global_prompt" name="global_prompt" rows="10"
								placeholder="<?php esc_attr_e( 'Write a global prompt that will be added with every generation', 'aiscp-host' ); ?>"
							><?php echo esc_textarea( AISCPH_Settings::get( 'global_prompt', '' ) ); ?></textarea>
							 
						</div>
					</div>
				</div>
			</div>

			<!-- Logging -->
			<div class="aiscph-card">
				<div class="aiscph-card-header">
					<h2><?php _e( 'Logging', 'aiscp-host' ); ?></h2>
					<p><?php _e( 'Control whether post generation activity is recorded.', 'aiscp-host' ); ?></p>
				</div>
				<div class="aiscph-card-body">
					<div class="aiscph-toggle-row">
						<div class="aiscph-toggle-info">
							<strong><?php _e( 'Enable Generation Log', 'aiscp-host' ); ?></strong>
							<span><?php _e( 'Record each generation request and result. Viewable in the Generation Log page.', 'aiscp-host' ); ?></span>
						</div>
						<div class="aiscph-toggle">
							<input type="checkbox" id="generation_log" name="generation_log" value="1"
								<?php checked( AISCPH_Settings::get( 'generation_log', '1' ), '1' ); ?>>
							<span class="aiscph-toggle-slider"></span>
						</div>
					</div>
				</div>
			</div>

			<!-- API Endpoints -->
			<div class="aiscph-card aiscph-endpoints-card">
				<div class="aiscph-card-header">
					<h2><?php _e( 'REST API Endpoints', 'aiscp-host' ); ?></h2>
					<p><?php _e( 'These are the endpoints your client plugins connect to.', 'aiscp-host' ); ?></p>
				</div>
				<div class="aiscph-card-body">
					<div class="aiscph-endpoint-row">
						<span class="endpoint-method post">POST</span>
						<code><?php echo esc_html( home_url( '/wp-json/aiscp/v1/license/validate' ) ); ?></code>
					</div>
					<div class="aiscph-endpoint-row">
						<span class="endpoint-method post">POST</span>
						<code><?php echo esc_html( home_url( '/wp-json/aiscp/v1/generate/post' ) ); ?></code>
					</div>
				</div>
			</div>

			<div class="aiscph-form-footer">
				<button type="submit" id="aiscph-save-btn" class="aiscph-btn aiscph-btn-primary">
					<span class="btn-text"><?php _e( 'Save Settings', 'aiscp-host' ); ?></span>
					<span class="btn-spinner" style="display:none;">⏳</span>
				</button>
			</div>

		</form>
	</div>
</div>