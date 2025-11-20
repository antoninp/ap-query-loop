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
	exit; // Exit if accessed directly
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
