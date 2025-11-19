<?php
/**
 * Plugin Name:       APQL Gallery
 * Description:       Advanced Query Loop blocks: Filter posts by taxonomy terms and display as galleries with context-aware rendering.
 * Version:           0.2.3
 * Author:            Antonin Puleo
 * Text Domain:       apql-gallery
 * Requires at least: 6.5
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) { 
	// Prevent direct access; if WordPress core isn't loaded, provide minimal stubs to avoid fatal errors in static analysis.
	// EXIT to avoid executing plugin logic outside WP. Return instead of exit if running in a code scanning context.
	// We intentionally do NOT run the rest of this file when ABSPATH is missing.
	return; 
}

// Minimal defensive stubs for static analysis environments (will be ignored in real WP runtime because functions exist).
if ( ! function_exists( 'sanitize_key' ) ) { function sanitize_key( $key ){ return preg_replace( '/[^a-z0-9_]/', '', strtolower( (string) $key ) ); } }
if ( ! function_exists( 'esc_html__' ) ) { function esc_html__( $text, $domain = null ){ return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); } }
if ( ! function_exists( 'esc_html' ) ) { function esc_html( $text ){ return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); } }
if ( ! function_exists( 'esc_attr' ) ) { function esc_attr( $text ){ return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); } }
if ( ! function_exists( 'esc_url' ) ) { function esc_url( $url ){ return filter_var( $url, FILTER_SANITIZE_URL ); } }
if ( ! function_exists( 'get_query_var' ) ) { function get_query_var( $var ){ return 1; } }
if ( ! function_exists( 'do_shortcode' ) ) { function do_shortcode( $s ){ return ''; } }
if ( ! function_exists( 'gallery_shortcode' ) ) { function gallery_shortcode( $atts ){ return ''; } }
if ( ! function_exists( 'get_pagenum_link' ) ) { function get_pagenum_link( $n ){ return '#'; } }
if ( ! function_exists( 'paginate_links' ) ) { function paginate_links( $args ){ return ''; } }
if ( ! function_exists( 'get_post_thumbnail_id' ) ) { function get_post_thumbnail_id( $post_id ){ return 0; } }
if ( ! function_exists( 'get_the_ID' ) ) { function get_the_ID(){ return 0; } }
if ( ! function_exists( 'wp_reset_postdata' ) ) { function wp_reset_postdata(){} }
if ( ! function_exists( 'wp_get_attachment_image' ) ) { function wp_get_attachment_image( $id, $size = 'large', $icon = false, $attr = [] ){ return ''; } }
if ( ! function_exists( 'taxonomy_exists' ) ) { function taxonomy_exists( $tax ){ return false; } }
if ( ! function_exists( 'get_the_terms' ) ) { function get_the_terms( $post_id, $tax ){ return []; } }
if ( ! function_exists( 'is_wp_error' ) ) { function is_wp_error( $thing ){ return false; } }
if ( ! function_exists( 'get_term_link' ) ) { function get_term_link( $term, $taxonomy = '' ){ return '#'; } }
if ( ! function_exists( 'get_block_wrapper_attributes' ) ) { function get_block_wrapper_attributes( $extra_attributes = [] ){ return ''; } }
if ( ! function_exists( 'add_action' ) ) { function add_action( $hook, $callback ){ if ( is_callable( $callback ) ) { $callback(); } } }
if ( ! function_exists( 'plugin_basename' ) ) { function plugin_basename( $file ){ return basename( (string) $file ); } }
if ( ! function_exists( 'load_plugin_textdomain' ) ) { function load_plugin_textdomain( $domain, $deprecated = false, $rel_path = '' ) { return true; } }
if ( ! function_exists( 'plugins_url' ) ) { function plugins_url( $path = '', $plugin = '' ) { return (string) $path; } }
if ( ! function_exists( 'wp_register_script' ) ) { function wp_register_script( $handle, $src, $deps = [], $ver = false, $in_footer = false ) { return true; } }
if ( ! function_exists( 'wp_register_style' ) ) { function wp_register_style( $handle, $src, $deps = [], $ver = false, $media = 'all' ) { return true; } }
if ( ! function_exists( 'register_block_type' ) ) { function register_block_type( $path_or_name, $args = [] ) { return true; } }
if ( ! class_exists( 'WP_Query' ) ) { class WP_Query { public $max_num_pages = 1; public $posts = []; public $queried_object = null; public $is_tax = false; public $query_vars = []; public function __construct( $args = [] ) {} public function have_posts() { return false; } public function the_post() {} } }
if ( ! class_exists( 'WP_Block' ) ) {
	class WP_Block {
		public $context = [];
		public $name = '';
		public $attributes = [];
		public $inner_blocks = [];
		public $innerBlocks = [];
		public $innerHTML = '';
		public $inner_html = '';
		public $inner_content = [];

		public function __construct( $parsed_block = [], $context = [] ){
			$this->name = is_array( $parsed_block ) && isset( $parsed_block['blockName'] ) ? (string) $parsed_block['blockName'] : '';
			$this->attributes = is_array( $parsed_block ) && isset( $parsed_block['attrs'] ) && is_array( $parsed_block['attrs'] ) ? $parsed_block['attrs'] : [];
			$this->inner_blocks = is_array( $parsed_block ) && isset( $parsed_block['innerBlocks'] ) && is_array( $parsed_block['innerBlocks'] ) ? $parsed_block['innerBlocks'] : [];
			$this->innerHTML = is_array( $parsed_block ) && isset( $parsed_block['innerHTML'] ) ? $parsed_block['innerHTML'] : '';
			$this->inner_content = is_array( $parsed_block ) && isset( $parsed_block['innerContent'] ) && is_array( $parsed_block['innerContent'] ) ? $parsed_block['innerContent'] : [];
			$this->context = is_array( $context ) ? $context : [];
		}
		public function render(){ return ''; }
	}
}

// Helper: convert a WP_Block instance (or nested) into a parsed block array structure expected by WP_Block constructor
if ( ! function_exists( 'ap_qg_block_to_parsed' ) ) {
	function ap_qg_block_to_parsed( $blk ) {
		if ( is_array( $blk ) ) {
			// Assume already parsed
			return $blk;
		}
		if ( $blk instanceof WP_Block ) {
			$inner_parsed = [];
			if ( is_object( $blk ) && property_exists( $blk, 'innerBlocks' ) && is_array( $blk->innerBlocks ) ) {
				foreach ( $blk->innerBlocks as $ib ) {
					$inner_parsed[] = ap_qg_block_to_parsed( $ib );
				}
			} elseif ( is_object( $blk ) && property_exists( $blk, 'inner_blocks' ) && is_array( $blk->inner_blocks ) ) {
				foreach ( $blk->inner_blocks as $ib ) {
					$inner_parsed[] = ap_qg_block_to_parsed( $ib );
				}
			}
			$attrs = [];
			if ( is_object( $blk ) && property_exists( $blk, 'attributes' ) && is_array( $blk->attributes ) ) {
				$attrs = $blk->attributes;
			}
			$name = ( is_object( $blk ) && property_exists( $blk, 'name' ) ) ? (string) $blk->name : '';

			// Capture saved HTML and inner content so static (save) blocks render
			$innerHTML = '';
			if ( is_object( $blk ) && ( property_exists( $blk, 'innerHTML' ) || property_exists( $blk, 'inner_html' ) ) ) {
				if ( property_exists( $blk, 'innerHTML' ) ) {
					$innerHTML = (string) $blk->innerHTML;
				} elseif ( property_exists( $blk, 'inner_html' ) ) {
					$innerHTML = (string) $blk->inner_html;
				}
			}
			if ( is_object( $blk ) && property_exists( $blk, 'inner_content' ) && is_array( $blk->inner_content ) ) {
				$innerHTML = implode( '', $blk->inner_content );
			}
			$innerContent = [];
			if ( is_object( $blk ) && property_exists( $blk, 'inner_content' ) && is_array( $blk->inner_content ) ) {
				$innerContent = $blk->inner_content;
			}
			return [
				'blockName'    => $name,
				'attrs'        => $attrs,
				'innerBlocks'  => $inner_parsed,
				'innerHTML'    => $innerHTML,
				'innerContent' => $innerContent,
			];
		}
		return [];
	}
}

// Helper: render parsed blocks with proper context (recursively handles innerBlocks)
if ( ! function_exists( 'ap_render_blocks_with_context' ) ) {
	function ap_render_blocks_with_context( $parsed_blocks, $context ) {
		$output = '';
		
		if ( ! is_array( $parsed_blocks ) ) {
			return $output;
		}
		
		foreach ( $parsed_blocks as $parsed_block ) {
			if ( ! is_array( $parsed_block ) ) {
				continue;
			}
			
			// Handle plain HTML content (no blockName)
			if ( empty( $parsed_block['blockName'] ) ) {
				$output .= isset( $parsed_block['innerHTML'] ) ? $parsed_block['innerHTML'] : '';
				continue;
			}
			
			// Create WP_Block with the provided context
			$block_instance = new WP_Block( $parsed_block, $context );
			$output .= $block_instance->render();
		}
		
		return $output;
	}
}

// Load text domain
add_action( 'init', function() {
	load_plugin_textdomain( 'apql-gallery', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

// Hook into query_loop_block_query_vars to apply custom order settings
add_filter( 'query_loop_block_query_vars', function( $query, $block ) {
	// Check if this is our query variation by looking at the block context
	$block_query = isset( $block->context['query'] ) ? $block->context['query'] : array();
	
	// Only modify queries with our namespace
	if ( isset( $block_query['namespace'] ) && 'apql-gallery' === $block_query['namespace'] ) {
		// Apply order from block context (WordPress uses 'order')
		if ( isset( $block_query['order'] ) && ! empty( $block_query['order'] ) ) {
			$query['order'] = sanitize_key( $block_query['order'] );
		}
		// Apply orderBy from block context (WordPress uses 'orderby' in query vars)
		if ( isset( $block_query['orderBy'] ) && ! empty( $block_query['orderBy'] ) ) {
			$query['orderby'] = sanitize_key( $block_query['orderBy'] );
		}
	}
	
	return $query;
}, 10, 2 );

// Include render callbacks
require_once __DIR__ . '/includes/render-callbacks.php';

/**
 * Register blocks from block.json metadata.
 */
add_action( 'init', function() {
	$plugin_dir = __DIR__;

	// Register editor script with proper WP dependencies
	$editor_handle = 'apql-gallery-editor';
	$editor_file   = $plugin_dir . '/build/index.js';
	if ( file_exists( $editor_file ) ) {
		wp_register_script(
			$editor_handle,
			plugins_url( 'build/index.js', __FILE__ ),
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ),
			filemtime( $editor_file ),
			true
		);
	}

	// Register frontend style
	$style_handle = 'apql-gallery-style';
	$style_file   = $plugin_dir . '/build/style-index.css';
	if ( file_exists( $style_file ) ) {
		wp_register_style(
			$style_handle,
			plugins_url( 'build/style-index.css', __FILE__ ),
			array(),
			filemtime( $style_file )
		);
	}

	// Register Gallery block
	$gallery_dir = $plugin_dir . '/blocks/gallery';
	if ( is_dir( $gallery_dir ) ) {
		register_block_type(
			$gallery_dir,
			array(
				'editor_script'   => $editor_handle,
				'style'           => $style_handle,
				'render_callback' => 'apql_gallery_render_block',
			)
		);
	}

	// Register Filter block
	$filter_dir = $plugin_dir . '/blocks/filter';
	if ( is_dir( $filter_dir ) ) {
		register_block_type(
			$filter_dir,
			array(
				'editor_script'   => $editor_handle,
				'style'           => $style_handle,
				'render_callback' => 'apql_filter_render_block',
			)
		);
	}

	// Register Term Name block
	$term_name_dir = $plugin_dir . '/blocks/term-name';
	if ( is_dir( $term_name_dir ) ) {
		register_block_type(
			$term_name_dir,
			array(
				'editor_script'   => $editor_handle,
				'style'           => $style_handle,
				'render_callback' => 'apql_term_name_render_block',
			)
		);
	}
} );
