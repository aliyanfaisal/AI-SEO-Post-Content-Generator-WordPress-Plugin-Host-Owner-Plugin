<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AISCPH_Claude
 *
 * Handles all Claude API communication.
 *
 * System prompt structure (with prompt caching):
 *
 *  Block 1 — CACHED: Global Post Generation Instructions (client's txt file)
 *            Marked with cache_control → Claude caches for 1hr, costs 10% after first call
 *
 *  Block 2 — NOT CACHED: Dynamic part (language, tone, token budget, format rules)
 *            Changes per request so cannot be cached
 */
class AISCPH_Claude {

	const API_URL = 'https://api.anthropic.com/v1/messages';
	const MODEL   = 'claude-opus-4-5';

	public static function generate_post( $preferences ) {
		$api_key = AISCPH_Settings::get_claude_key();

		if ( empty( $api_key ) ) {
			return new WP_Error( 'no_api_key', 'Claude API key is not configured.' );
		}

		$max_tokens        = (int) AISCPH_Settings::get( 'max_tokens', 4000 );
		$max_content_words = (int) AISCPH_Settings::get( 'max_content_words', 800 );
		$instructions      = trim( AISCPH_Settings::get( 'post_generation_instructions', '' ) );

		// Build system prompt blocks
		$system_blocks = self::build_system_blocks( $preferences, $max_tokens, $max_content_words, $instructions );

		// Store full payload for debugging
		update_option( 'aiscph_debug_last_payload', array(
			'time'             => current_time( 'mysql' ),
			'reference_mode'   => ! empty( $preferences['reference_mode'] ),
			'system_blocks'    => $system_blocks,
			'user_prompt'      => self::build_prompt( $preferences ),
			'max_tokens'       => $max_tokens,
			'max_words'        => $max_content_words,
			'domain'           => $preferences['domain'] ?? '',
		) );

		$response = wp_remote_post( self::API_URL, array(
			'timeout' => 600,
			'headers' => array(
				'x-api-key'         => $api_key,
				'anthropic-version' => '2023-06-01',
				'content-type'      => 'application/json',
				'anthropic-beta'    => 'prompt-caching-2024-07-31',
			),
			'body' => wp_json_encode( array(
				'model'      => self::MODEL,
				'max_tokens' => $max_tokens,
				'system'     => $system_blocks,
				'messages'   => array(
					array( 'role' => 'user', 'content' => self::build_prompt( $preferences ) ),
				),
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			update_option( 'aiscph_debug_claude_raw', array(
				'time'   => current_time( 'mysql' ),
				'status' => 'wp_error',
				'error'  => $response->get_error_message(),
				'raw'    => '',
			) );
			return $response;
		}

		$code     = wp_remote_retrieve_response_code( $response );
		$body     = json_decode( wp_remote_retrieve_body( $response ), true );
		$raw_text = $body['content'][0]['text'] ?? '';

		if ( $code !== 200 || empty( $raw_text ) ) {
			$error_msg = $body['error']['message'] ?? "Claude API returned HTTP {$code}";
			update_option( 'aiscph_debug_claude_raw', array(
				'time'   => current_time( 'mysql' ),
				'status' => 'api_error',
				'code'   => $code,
				'error'  => $error_msg,
				'raw'    => wp_remote_retrieve_body( $response ),
			) );
			return new WP_Error( 'claude_error', $error_msg );
		}

		// Store raw response + cache stats for debugging
		update_option( 'aiscph_debug_claude_raw', array(
			'time'             => current_time( 'mysql' ),
			'status'           => 'received',
			'raw'              => $raw_text,
			'stop_reason'      => $body['stop_reason'] ?? 'unknown',
			'cache_read'       => $body['usage']['cache_read_input_tokens'] ?? 0,
			'cache_created'    => $body['usage']['cache_creation_input_tokens'] ?? 0,
			'instructions_set' => ! empty( $instructions ),
		) );

		$parsed = self::parse_response( $raw_text );

		if ( is_wp_error( $parsed ) ) {
			update_option( 'aiscph_debug_claude_raw', array(
				'time'        => current_time( 'mysql' ),
				'status'      => 'parse_error',
				'error'       => $parsed->get_error_message(),
				'raw'         => $raw_text,
				'stop_reason' => $body['stop_reason'] ?? 'unknown',
			) );
		} else {
			update_option( 'aiscph_debug_claude_raw', array(
				'time'          => current_time( 'mysql' ),
				'status'        => 'parsed_ok',
				'title'         => $parsed['title'] ?? '',
				'raw'           => $raw_text,
				'stop_reason'   => $body['stop_reason'] ?? 'unknown',
				'cache_read'    => $body['usage']['cache_read_input_tokens'] ?? 0,
				'cache_created' => $body['usage']['cache_creation_input_tokens'] ?? 0,
			) );
		}

		return $parsed;
	}

	// ---------------------------------------------------------------
	// System prompt — structured as cached + dynamic blocks
	// ---------------------------------------------------------------

	private static function build_system_blocks( $prefs, $max_tokens, $max_content_words, $instructions ) {
		$blocks = array();

		// Block 1 — CACHED: Global Post Generation Instructions
		// Only included if the admin has filled in the instructions field
		if ( ! empty( $instructions ) ) {
			$blocks[] = array(
				'type'          => 'text',
				'text'          => $instructions,
				'cache_control' => array( 'type' => 'ephemeral' ),
			);
		}

		// Skip section prompts entirely for reference mode
		$is_reference_mode = ! empty( $prefs['reference_mode'] );

		// Block 2 — CACHED: Section prompts (skipped in reference mode)
		$sections = $is_reference_mode ? array() : AISCPH_Settings::get_sections();
		if ( ! empty( $sections ) ) {
			$sections_text  = "=== MANDATORY POST STRUCTURE ===\n";
			$sections_text .= "You MUST generate the post in exactly these sections, in this exact order. ";
			$sections_text .= "Follow each section prompt strictly and completely. Do not skip, merge, or reorder any section. ";
			$sections_text .= "This structure takes priority over all other formatting instructions.\n\n";
			foreach ( $sections as $i => $section ) {
				$num     = $i + 1;
				$name    = strtoupper( $section['name'] );
				$sprompt = $section['prompt'];
				$sections_text .= "SECTION {$num} — {$name}:\n{$sprompt}\n\n";
			}
			$blocks[] = array(
				'type'          => 'text',
				'text'          => $sections_text,
				'cache_control' => array( 'type' => 'ephemeral' ),
			);
		}

		// Block 3 — NOT CACHED: Dynamic system prompt
		// Changes per request (language, global_prompt, token budget, format rules)
		$blocks[] = array(
			'type' => 'text',
			'text' => self::build_dynamic_prompt( $prefs, $max_tokens, $max_content_words, empty( $instructions ) ),
		);

		return $blocks;
	}

	/**
	 * Dynamic system prompt block — always sent uncached.
	 *
	 * @param bool $include_base  If true (no instructions set), include the base SEO writer intro.
	 */
	private static function build_dynamic_prompt( $prefs, $max_tokens, $max_content_words, $include_base = true ) {
		$global_prompt  = trim( AISCPH_Settings::get( 'global_prompt', '' ) );
		$language       = $prefs['content_language'] ?? 'he';
		$lang_label     = $language === 'he' ? 'Hebrew (עברית)' : ( $language === 'ar' ? 'Arabic (عربي)' : 'English' );
		$content_budget = max( 500, $max_tokens - 300 );

		$system = '';
		$is_reference = ! empty( $prefs['reference_mode'] );

		if ( $is_reference ) {
			// Reference mode: different persona focused on reading and rewriting
			$system .= "You are an expert content writer and editor. I will provide you with reference post URLs and a prompt. Your task is to read the content from those URLs and create a brand new, original, high-quality post based on them. Do not copy — rewrite, improve, and expand the content.\n";
		} elseif ( $include_base ) {
			$system .= "You are an expert SEO content writer. Write high-quality, human-sounding blog posts optimized for search engines.\n";
		}

		$system .= "Primary language: {$lang_label}\n";

		if ( $language === 'he' ) {
			$system .= "Write in natural Hebrew with storytelling, local slang, and varied sentence structure.\n";
		}

		// Global prompt (short admin notes, always dynamic)
		if ( ! empty( $global_prompt ) ) {
			$system .= "\n{$global_prompt}\n";
		}

		$system .= "\nTOKEN BUDGET: {$max_tokens} tokens total. Budget ~{$content_budget} for CONTENT. Always close every delimiter.\n";
		$system .= "CONTENT LENGTH: Approximately {$max_content_words} words inside [CONTENT].\n\n";
		$system .= self::get_format_rules();

		return $system;
	}

	/**
	 * Shared response format rules.
	 */
	private static function get_format_rules() {
		$rules  = "RESPONSE FORMAT — output ONLY these delimiters in this exact order, nothing else before or after:\n\n";

		$rules .= "[TITLE]The post title[/TITLE]\n";
		$rules .= "[EXCERPT]1-2 sentence post summary[/EXCERPT]\n";
		$rules .= "[CATEGORIES]Category One,Category Two[/CATEGORIES]\n";
		$rules .= "[TAGS]tag1,tag2,tag3,tag4,tag5[/TAGS]\n";
		$rules .= "[SEO_TITLE]SEO title max 60 chars[/SEO_TITLE]\n";
		$rules .= "[SEO_DESC]SEO meta description max 160 chars[/SEO_DESC]\n";
		$rules .= "[FOCUS_KEYWORD]primary SEO keyword phrase[/FOCUS_KEYWORD]\n";
		$rules .= "[OG_TITLE]Open Graph title max 60 chars[/OG_TITLE]\n";
		$rules .= "[OG_DESC]Open Graph description max 160 chars[/OG_DESC]\n";
		$rules .= "[TWITTER_TITLE]Twitter card title max 70 chars[/TWITTER_TITLE]\n";
		$rules .= "[TWITTER_DESC]Twitter card description max 200 chars[/TWITTER_DESC]\n";
		$rules .= "[SCHEMA_TYPE]Article or BlogPosting or HowTo or FAQPage[/SCHEMA_TYPE]\n";

		$rules .= "\n[IMAGES]\n";
		$rules .= "image_1: concise English search query for the FEATURED image (5-8 words)\n";
		$rules .= "image_2: concise English search query for first INLINE image (5-8 words)\n";
		$rules .= "image_3: concise English search query for second INLINE image (5-8 words)\n";
		$rules .= "[/IMAGES]\n";

		$rules .= "\n[CONTENT]\n";
		$rules .= "<h2>First Section Heading</h2>\n";
		$rules .= "<p>Paragraph text...</p>\n";
		$rules .= "{{IMAGE_2}}\n";
		$rules .= "<h2>Second Section Heading</h2>\n";
		$rules .= "<p>More paragraph text...</p>\n";
		$rules .= "{{IMAGE_3}}\n";
		$rules .= "<p>Closing paragraph.</p>\n";
		$rules .= "[/CONTENT]\n\n";

		$rules .= "RULES:\n";
		$rules .= "- Output ONLY the delimited sections above. No preamble, no commentary.\n";
		$rules .= "- CONTENT must use valid HTML: <h2>, <h3>, <p>, <ul>, <li>, <strong>, <em>.\n";
		$rules .= "- CATEGORIES and TAGS: comma-separated on a single line.\n";
		$rules .= "- All image queries in [IMAGES] must be in English regardless of content language.\n";
		$rules .= "- image_1 is the featured image — do NOT place {{IMAGE_1}} anywhere in CONTENT.\n";
		$rules .= "- Place {{IMAGE_2}}, {{IMAGE_3}} etc. between paragraphs in CONTENT where relevant, never inside a <p> tag.\n";
		$rules .= "- {{IMAGE_N}} index must match the corresponding image_N entry in [IMAGES].\n";
		$rules .= "- SCHEMA_TYPE: choose the single most appropriate schema type.\n";
		$rules .= "- CRITICAL: Always close [/CONTENT]. If running low on tokens, finish the sentence and close the tag.\n";
		return $rules;
	}

	/**
	 * Parse the [IMAGES] block into an indexed array of search queries.
	 * Returns: [ 1 => 'query for featured image', 2 => 'query for inline image', ... ]
	 */
	private static function parse_images_block( $text ) {
		$raw = self::extract( $text, 'IMAGES' );
		if ( empty( $raw ) ) return array();

		$queries = array();
		foreach ( explode( "\n", $raw ) as $line ) {
			$line = trim( $line );
			if ( preg_match( '/^image_(\d+)\s*:\s*(.+)$/i', $line, $m ) ) {
				$queries[ (int) $m[1] ] = trim( $m[2] );
			}
		}
		return $queries;
	}

	// ---------------------------------------------------------------
	// User prompt (the actual generation request)
	// ---------------------------------------------------------------

	private static function build_prompt( $prefs ) {
		$keywords          = trim( $prefs['target_keywords'] ?? '' );
		$negative_keywords = trim( $prefs['negative_keywords'] ?? '' );
		$style             = $prefs['writing_style'] ?? 'professional';
		$tone              = $prefs['tone'] ?? 'informative';
		$tone_examples     = trim( $prefs['tone_examples'] ?? '' );
		$internal_links    = trim( $prefs['internal_links'] ?? '' );

		$kw_list  = array_filter( array_map( 'trim', explode( "\n", $keywords ) ) );
		$focus_kw = ! empty( $kw_list ) ? $kw_list[ array_rand( $kw_list ) ] : 'general topic';

		$prompt = '';

		// Reference posts — injected at the very top with highest priority
		$use_ref     = ! empty( $prefs['use_reference_posts'] ) && $prefs['use_reference_posts'] === '1';
		$ref_prompt  = trim( $prefs['reference_posts_prompt'] ?? '' );
		$ref_urls    = $prefs['reference_urls'] ?? array();

		if ( $use_ref && ! empty( $ref_prompt ) && ! empty( $ref_urls ) ) {
			$prompt .= "####very very very important####\n";
			$prompt .= $ref_prompt . "\n\n";
			$prompt .= "Reference URLs to use as source material:\n";
			foreach ( $ref_urls as $url ) {
				$prompt .= "- " . esc_url( $url ) . "\n";
			}
			$prompt .= "####end of very important instruction####\n\n";
		}

		$prompt .= "Write a comprehensive SEO blog post about: \"{$focus_kw}\"\n";
		$prompt .= "Writing style: {$style}\n";
		$prompt .= "Tone: {$tone}\n";

		if ( ! empty( $negative_keywords ) ) {
			$prompt .= "Avoid these keywords and topics: {$negative_keywords}\n";
		}

		$restrictions = trim( $prefs['content_restrictions'] ?? '' );
		if ( ! empty( $restrictions ) ) {
			$prompt .= "\nCONTENT RESTRICTIONS — strictly follow these rules for every sentence written:\n{$restrictions}\n";
		}
		if ( ! empty( $tone_examples ) ) {
			$prompt .= "Match this voice:\n{$tone_examples}\n";
		}
		if ( ! empty( $internal_links ) ) {
			$prompt .= "Naturally link to these URLs where relevant:\n{$internal_links}\n";
		}

		$sitemap_url = trim( $prefs['sitemap_url'] ?? '' );
		if ( ! empty( $sitemap_url ) ) {
			$sitemap_links = self::fetch_sitemap_urls( $sitemap_url, 20 );
			if ( ! empty( $sitemap_links ) ) {
				$prompt .= "Also naturally link to relevant pages from this sitemap:\n";
				$prompt .= implode( "\n", $sitemap_links ) . "\n";
			}
		}

		if ( ! empty( $prefs['fact_checking'] ) && $prefs['fact_checking'] === '1' ) {
			$prompt .= "Ensure all facts are accurate.\n";
		}

		return $prompt;
	}

	// ---------------------------------------------------------------
	// Response parsing
	// ---------------------------------------------------------------

	private static function parse_response( $text ) {
		$text = trim( $text );

		$fields = array(
			'title'         => self::extract( $text, 'TITLE' ),
			'excerpt'       => self::extract( $text, 'EXCERPT' ),
			'content'       => self::extract_content( $text ),
			'image_queries' => self::parse_images_block( $text ),
			'focus_keyword' => self::extract( $text, 'FOCUS_KEYWORD' ),
			'og_title'      => self::extract( $text, 'OG_TITLE' ),
			'og_description'=> self::extract( $text, 'OG_DESC' ),
			'tw_title'      => self::extract( $text, 'TWITTER_TITLE' ),
			'tw_description'=> self::extract( $text, 'TWITTER_DESC' ),
			'schema_type'   => self::extract( $text, 'SCHEMA_TYPE' ),
			'seo_title'     => self::extract( $text, 'SEO_TITLE' ),
			'seo_desc'      => self::extract( $text, 'SEO_DESC' ),
		);

		$cats_raw = self::extract( $text, 'CATEGORIES' );
		$tags_raw = self::extract( $text, 'TAGS' );

		$fields['categories'] = ! empty( $cats_raw )
			? array_map( 'trim', explode( ',', $cats_raw ) )
			: array();

		$fields['tags'] = ! empty( $tags_raw )
			? array_map( 'trim', explode( ',', $tags_raw ) )
			: array();

		$seo_title = $fields['seo_title'] ?: $fields['title'];
		$seo_desc  = $fields['seo_desc']  ?: $fields['excerpt'];

		$fields['seo_meta'] = array(
			'title'               => $seo_title,
			'description'         => $seo_desc,
			'focus_keyword'       => $fields['focus_keyword'],
			'og_title'            => $fields['og_title']       ?: $seo_title,
			'og_description'      => $fields['og_description'] ?: $seo_desc,
			'twitter_title'       => $fields['tw_title']       ?: $seo_title,
			'twitter_description' => $fields['tw_description'] ?: $seo_desc,
			'schema_type'         => $fields['schema_type']    ?: 'Article',
		);

		// image_1 is the featured image — expose as image_prompt for generator
		$fields['image_prompt'] = $fields['image_queries'][1] ?? $fields['title'];

		unset( $fields['seo_title'], $fields['seo_desc'], $fields['focus_keyword'],
			   $fields['og_title'], $fields['og_description'], $fields['tw_title'],
			   $fields['tw_description'], $fields['schema_type'] );

		if ( empty( $fields['title'] ) || empty( $fields['content'] ) ) {
			return new WP_Error( 'parse_error', 'Could not parse AI response. Please try again.' );
		}

		return $fields;
	}

	private static function extract( $text, $tag ) {
		if ( preg_match( '/\[' . $tag . '\](.*?)\[\/' . $tag . '\]/su', $text, $m ) ) {
			return trim( $m[1] );
		}
		if ( preg_match( '/\[' . $tag . '\](.+?)(?:\n|$)/su', $text, $m ) ) {
			return trim( $m[1] );
		}
		return '';
	}

	private static function extract_content( $text ) {
		if ( preg_match( '/\[CONTENT\](.*?)\[\/CONTENT\]/su', $text, $m ) ) {
			return trim( $m[1] );
		}
		if ( preg_match( '/\[CONTENT\](.*)/su', $text, $m ) ) {
			return trim( $m[1] );
		}
		return '';
	}

	private static function fetch_sitemap_urls( $sitemap_url, $limit = 20 ) {
		$response = wp_remote_get( esc_url_raw( $sitemap_url ), array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );

		if ( strpos( $body, '<sitemapindex' ) !== false ) {
			preg_match_all( '/<loc>(.*?)<\/loc>/s', $body, $matches );
			$child = $matches[1][0] ?? '';
			if ( ! empty( $child ) ) {
				return self::fetch_sitemap_urls( trim( $child ), $limit );
			}
			return array();
		}

		preg_match_all( '/<loc>(.*?)<\/loc>/s', $body, $matches );
		$urls = array_map( 'trim', $matches[1] ?? array() );
		$urls = array_filter( $urls );
		return array_slice( array_values( $urls ), 0, $limit );
	}
}
