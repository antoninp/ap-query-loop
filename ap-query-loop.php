<?php
/**
 * Plugin Name:       AP Query Loop Gallery
 * Description:       Display a list of posts from a selected post type as a gallery using featured images (Meow Gallery compatible).
 * Version:           0.2.0
 * Author:            Antonin Puleo
 * Text Domain:       ap-query-loop
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
if ( ! function_exists( 'add_action' ) ) { function add_action( $hook, $callback ){ if ( is_callable( $callback ) ) { $callback(); } } }
if ( ! function_exists( 'plugin_basename' ) ) { function plugin_basename( $file ){ return basename( (string) $file ); } }
if ( ! function_exists( 'load_plugin_textdomain' ) ) { function load_plugin_textdomain( $domain, $deprecated = false, $rel_path = '' ) { return true; } }
if ( ! function_exists( 'plugins_url' ) ) { function plugins_url( $path = '', $plugin = '' ) { return (string) $path; } }
if ( ! function_exists( 'wp_register_script' ) ) { function wp_register_script( $handle, $src, $deps = [], $ver = false, $in_footer = false ) { return true; } }
if ( ! function_exists( 'wp_register_style' ) ) { function wp_register_style( $handle, $src, $deps = [], $ver = false, $media = 'all' ) { return true; } }
if ( ! function_exists( 'register_block_type' ) ) { function register_block_type( $path_or_name, $args = [] ) { return true; } }
if ( ! class_exists( 'WP_Query' ) ) { class WP_Query { public $max_num_pages = 1; public $posts = []; public function __construct( $args = [] ) {} public function have_posts() { return false; } public function the_post() {} } }
if ( ! class_exists( 'WP_Block' ) ) { class WP_Block { public $context = []; } }

// Load text domain (if languages/ present later)
add_action( 'init', function() {
	load_plugin_textdomain( 'ap-query-loop', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
} );

/**
 * Register block from block.json and hook up render callback.
 */
add_action( 'init', function() {
    $dir = __DIR__;

    // Register editor script with proper WP dependencies (ensures ServerSideRender is available in editor)
    $editor_handle = 'ap-query-loop-editor';
    $editor_file   = $dir . '/build/index.js';
    if ( file_exists( $editor_file ) ) {
        wp_register_script(
            $editor_handle,
            plugins_url( 'build/index.js', __FILE__ ),
            [ 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ],
            filemtime( $editor_file ),
            true
        );
    }

    // Register style (frontend)
    $style_handle = 'ap-query-loop-style';
    $style_file   = $dir . '/build/style-index.css';
    if ( file_exists( $style_file ) ) {
        wp_register_style(
            $style_handle,
            plugins_url( 'build/style-index.css', __FILE__ ),
            [],
            filemtime( $style_file )
        );
    }

	// Register block using metadata but override assets and render callback
    register_block_type( $dir, [
        'editor_script'   => $editor_handle,
        'style'           => $style_handle,
        'render_callback' => 'ap_query_loop_render_block',
    ] );

	// Register the Group-by-Taxonomy block in subfolder (if present)
	$group_dir = $dir . '/group-by-tax';
	if ( is_dir( $group_dir ) ) {
		register_block_type( $group_dir, [
			'editor_script'   => $editor_handle,
			'style'           => $style_handle,
			'render_callback' => 'ap_group_by_tax_render_block',
		] );
	}
} );

/**
 * Server-side render callback for the block.
 *
 * @param array $attributes Block attributes.
 * @param string $content   Block inner content (unused).
 * @param WP_Block $block   Full block instance.
 * @return string HTML
 */
function ap_query_loop_render_block( $attributes, $content = '', $block = null ) {
	// Must be inside a Query Loop block providing 'query' context.
	$use_context = ( $block instanceof WP_Block ) && ! empty( $block->context['query'] );
	if ( ! $use_context ) {
		return '<div class="wp-block-ap-query-loop-gallery ap-query-loop-gallery"><em>' . esc_html__( 'Place this block inside a Query Loop block.', 'ap-query-loop' ) . '</em></div>';
	}

	// We DO NOT create a new query. We rely entirely on the query already
	// executed by the parent core/query block. At this point in rendering,
	// the global $wp_query should represent that query (inherited or custom).
	global $wp_query;
	if ( ! ( $wp_query instanceof WP_Query ) || empty( $wp_query->posts ) ) {
		return '<div class="ap-query-loop-empty">' . esc_html__( 'No posts found.', 'ap-query-loop' ) . '</div>';
	}

	// Optional filtering context from parent group block
	$filter_tax  = ( $block instanceof WP_Block && ! empty( $block->context['ap/groupTax'] ) ) ? (string) $block->context['ap/groupTax'] : '';
	$filter_term = ( $block instanceof WP_Block && isset( $block->context['ap/groupTerm'] ) ) ? (string) $block->context['ap/groupTerm'] : '';

	// Render a single gallery, optionally filtered by provided group context (no auto-group fallback)
		// Flat list behavior (original): collect all featured images from current page posts
		$image_ids = [];
		foreach ( $wp_query->posts as $post ) {
			$post_id  = isset( $post->ID ) ? (int) $post->ID : 0;
			if ( ! $post_id ) { continue; }
			if ( $filter_tax && $filter_term ) {
				$terms = get_the_terms( $post_id, $filter_tax );
				if ( is_wp_error( $terms ) || empty( $terms ) ) { continue; }
				$slugs = array_map( function( $t ){ return is_object( $t ) && isset( $t->slug ) ? (string) $t->slug : ''; }, $terms );
				if ( ! in_array( $filter_term, $slugs, true ) ) { continue; }
			}
			$thumb_id = get_post_thumbnail_id( $post_id );
			if ( $thumb_id ) { $image_ids[] = (int) $thumb_id; }
		}
		if ( empty( $image_ids ) ) {
			return '<div class="ap-query-loop-empty">' . esc_html__( 'No posts found.', 'ap-query-loop' ) . '</div>';
		}

		$ids_csv = implode( ',', array_map( 'intval', $image_ids ) );

		// Prefer Meow Gallery shortcode if available.
		global $shortcode_tags;
		$has_meow = is_array( $shortcode_tags ) && ( isset( $shortcode_tags['meow-gallery'] ) || isset( $shortcode_tags['meow_gallery'] ) );

		$html = '';
		if ( $has_meow ) {
			if ( isset( $shortcode_tags['meow-gallery'] ) ) {
				$html = do_shortcode( '[meow-gallery ids="' . esc_attr( $ids_csv ) . '"]' );
			} elseif ( isset( $shortcode_tags['meow_gallery'] ) ) {
				$html = do_shortcode( '[meow_gallery ids="' . esc_attr( $ids_csv ) . '"]' );
			}
		}

		// Fallback chain if Meow not present (or shortcode returned empty).
		if ( empty( $html ) ) {
			$gallery  = '<figure class="wp-block-gallery has-nested-images is-cropped">';
			foreach ( $image_ids as $id ) {
				$img = wp_get_attachment_image( $id, 'large', false, [ 'loading' => 'lazy', 'class' => 'wp-image-' . intval( $id ) ] );
				if ( $img ) {
					$gallery .= '<figure class="wp-block-image">' . $img . '</figure>';
				}
			}
			$gallery .= '</figure>';
			$html = $gallery;

			if ( empty( $html ) ) {
				$shortcode_html = gallery_shortcode( [ 'ids' => $ids_csv, 'size' => 'large', 'link' => 'none' ] );
				if ( ! empty( $shortcode_html ) ) {
					$html = $shortcode_html;
				}
			}
		}

		return '<div class="wp-block-ap-query-loop-gallery ap-query-loop-gallery">' . $html . '</div>';
}

/**
 * Group-by-Taxonomy parent block render: iterates terms on current page and renders a heading + scoped child gallery.
 */
function ap_group_by_tax_render_block( $attributes, $content = '', $block = null ) {
	$taxonomy     = isset( $attributes['taxonomy'] ) && is_string( $attributes['taxonomy'] ) ? $attributes['taxonomy'] : 'aplb_library_pdate';
	$show_heading = ! isset( $attributes['showHeading'] ) || (bool) $attributes['showHeading'];

	$use_context = ( $block instanceof WP_Block ) && ! empty( $block->context['query'] );
	if ( ! $use_context ) {
		return '<div class="ap-group-by-tax"><em>' . esc_html__( 'Place this block inside a Query Loop block.', 'ap-query-loop' ) . '</em></div>';
	}

	global $wp_query;
	if ( ! ( $wp_query instanceof WP_Query ) || empty( $wp_query->posts ) ) {
		return '<div class="ap-group-by-tax ap-group-by-tax--empty">' . esc_html__( 'No posts found.', 'ap-query-loop' ) . '</div>';
	}

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return '<div class="ap-group-by-tax ap-group-by-tax--empty">' . esc_html__( 'Taxonomy not found.', 'ap-query-loop' ) . '</div>';
	}

	$groups = [];
	foreach ( $wp_query->posts as $post ) {
		$post_id = isset( $post->ID ) ? (int) $post->ID : 0;
		if ( ! $post_id ) { continue; }
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( is_wp_error( $terms ) || empty( $terms ) ) { continue; }
		foreach ( $terms as $t ) {
			$key = is_object( $t ) && isset( $t->slug ) ? (string) $t->slug : ( is_object( $t ) && isset( $t->term_id ) ? (string) $t->term_id : '' );
			if ( '' === $key ) { continue; }
			if ( ! isset( $groups[ $key ] ) ) {
				$groups[ $key ] = [ 'term' => $t, 'count' => 0 ];
			}
			$groups[ $key ]['count']++;
		}
	}

	if ( empty( $groups ) ) {
		return '<div class="ap-group-by-tax ap-group-by-tax--empty">' . esc_html__( 'No matching terms for current posts.', 'ap-query-loop' ) . '</div>';
	}

	uasort( $groups, function( $a, $b ) {
		$an = is_object( $a['term'] ) ? $a['term']->name : '';
		$bn = is_object( $b['term'] ) ? $b['term']->name : '';
		return strcmp( (string) $an, (string) $bn );
	} );

	$out = '<div class="ap-group-by-tax" data-taxonomy="' . esc_attr( $taxonomy ) . '">';

	foreach ( $groups as $key => $data ) {
		$term = $data['term'];
		$slug = isset( $term->slug ) ? (string) $term->slug : (string) $key;
		$name = isset( $term->name ) ? (string) $term->name : (string) $slug;

		$out .= '<section class="ap-group-by-tax__group ap-group-by-tax__group--term-' . esc_attr( $slug ) . '">';
		if ( $show_heading ) {
			$out .= '<h3 class="ap-group-by-tax__heading">' . esc_html( $name ) . '</h3>';
		}

		// Render child AP gallery scoped to this term by instantiating a child block with injected context
		$parsed_child = [
			'blockName'    => 'ap/query-loop-gallery',
			'attrs'        => [],
			'innerBlocks'  => [],
			'innerHTML'    => '',
			'innerContent' => [],
		];
		$child_context = is_array( $block->context ) ? $block->context : [];
		$child_context['ap/groupTax']  = $taxonomy;
		$child_context['ap/groupTerm'] = $slug;
		$child_block = new WP_Block( $parsed_child, $child_context );
		$out .= $child_block->render();

		$out .= '</section>';
	}

	$out .= '</div>';
	return $out;
}

