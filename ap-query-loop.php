<?php
/**
 * Plugin Name:       AP Query Loop Gallery
 * Description:       Display a list of posts from a selected post type as a gallery using featured images (Meow Gallery compatible).
 * Version:           0.1.1
 * Author:            Antonin P.
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
} );/**
 * Server-side render callback for the block.
 *
 * @param array $attributes Block attributes.
 * @param string $content   Block inner content (unused).
 * @param WP_Block $block   Full block instance (unused).
 * @return string HTML
 */
function ap_query_loop_render_block( $attributes, $content = '', $block = null ) {
	$post_type        = isset( $attributes['postType'] ) ? sanitize_key( $attributes['postType'] ) : 'post';
	$per_page         = isset( $attributes['perPage'] ) ? max( 1, intval( $attributes['perPage'] ) ) : 12;
	$order            = isset( $attributes['order'] ) ? ( strtoupper( $attributes['order'] ) === 'ASC' ? 'ASC' : 'DESC' ) : 'DESC';
	$orderby          = isset( $attributes['orderBy'] ) ? sanitize_key( $attributes['orderBy'] ) : 'date';
	$enable_pagination= ! empty( $attributes['enablePagination'] );

	// Determine paged for pagination if enabled
	$paged = 1;
	if ( $enable_pagination ) {
		$paged = max( 1, intval( get_query_var( 'paged' ) ?: get_query_var( 'page' ) ) );
	}

	$query_args = [
		'post_type'           => $post_type,
		'posts_per_page'      => $per_page,
		'orderby'             => $orderby,
		'order'               => $order,
		'ignore_sticky_posts' => true,
		'paged'               => $paged,
		'no_found_rows'       => ! $enable_pagination,
	];

	$q = new WP_Query( $query_args );

	if ( ! $q->have_posts() ) {
		return '<div class="ap-query-loop-empty">' . esc_html__( 'No posts found.', 'ap-query-loop' ) . '</div>';
	}

	$image_ids = [];
	while ( $q->have_posts() ) {
		$q->the_post();
		$thumb_id = get_post_thumbnail_id( get_the_ID() );
		if ( $thumb_id ) {
			$image_ids[] = (int) $thumb_id;
		}
	}
	wp_reset_postdata();

	if ( empty( $image_ids ) ) {
		return '<div class="ap-query-loop-empty">' . esc_html__( 'No posts found.', 'ap-query-loop' ) . '</div>';
	}

	$ids_csv = implode( ',', array_map( 'intval', $image_ids ) );

	// Prefer Meow Gallery shortcode if available. We detect by checking shortcode list.
	global $shortcode_tags;
	$has_meow = is_array( $shortcode_tags ) && ( isset( $shortcode_tags['meow-gallery'] ) || isset( $shortcode_tags['meow_gallery'] ) );

	$html = '';
	if ( $has_meow ) {
		// Try meow-gallery first, then meow_gallery.
		if ( isset( $shortcode_tags['meow-gallery'] ) ) {
			$html = do_shortcode( '[meow-gallery ids="' . esc_attr( $ids_csv ) . '"]' );
		} elseif ( isset( $shortcode_tags['meow_gallery'] ) ) {
			$html = do_shortcode( '[meow_gallery ids="' . esc_attr( $ids_csv ) . '"]' );
		}
	}

	// Fallback to standard gallery if Meow not present (or shortcode returned empty)
	if ( empty( $html ) ) {
		// Standard gallery shortcode usually accepts attachment IDs as ids
		$html = gallery_shortcode( [ 'ids' => $ids_csv, 'size' => 'large' ] );

		if ( empty( $html ) ) {
			// Final graceful fallback: simple grid of images
			$html = '<div class="ap-query-loop-basic-gallery">';
			foreach ( $image_ids as $id ) {
				$img = wp_get_attachment_image( $id, 'large', false, [ 'loading' => 'lazy' ] );
				if ( $img ) {
					$html .= '<div class="ap-query-loop-item">' . $img . '</div>';
				}
			}
			$html .= '</div>';
		}
	}

	// Append pagination if enabled
	if ( $enable_pagination ) {
		$big = 999999999; // need an unlikely integer
		$pagination = paginate_links( [
			'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format'    => '?paged=%#%',
			'current'   => max( 1, $paged ),
			'total'     => (int) $q->max_num_pages,
			'type'      => 'list',
		] );
		if ( $pagination ) {
			$html .= '<nav class="navigation pagination ap-query-loop-pagination" role="navigation">' . $pagination . '</nav>';
		}
	}

	// Wrap for block context styling
	return '<div class="wp-block-ap-query-loop-gallery ap-query-loop-gallery">' . $html . '</div>';
}

