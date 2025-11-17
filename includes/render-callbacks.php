<?php
/**
 * Server-side render callbacks for APQL blocks.
 *
 * @package APQL_Gallery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-side render callback for the APQL Gallery block.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block inner content (unused).
 * @param WP_Block $block      Full block instance.
 * @return string HTML output.
 */
function apql_gallery_render_block( $attributes, $content = '', $block = null ) {
	// Must be inside a Query Loop block providing 'query' context.
	$use_context = ( $block instanceof WP_Block ) && ! empty( $block->context['query'] );
	if ( ! $use_context ) {
		return '<div class="wp-block-apql-gallery apql-gallery"><em>' . esc_html__( 'Place this block inside a Query Loop block.', 'apql-gallery' ) . '</em></div>';
	}

	// We DO NOT create a new query. We rely entirely on the query already
	// executed by the parent core/query block. At this point in rendering,
	// the global $wp_query should represent that query (inherited or custom).
	global $wp_query;
	if ( ! ( $wp_query instanceof WP_Query ) || empty( $wp_query->posts ) ) {
		return '<div class="apql-empty">' . esc_html__( 'No posts found.', 'apql-gallery' ) . '</div>';
	}

	// Optional filtering context from parent group block
	$filter_tax  = ( $block instanceof WP_Block && ! empty( $block->context['apql/filterTax'] ) ) ? (string) $block->context['apql/filterTax'] : '';
	$filter_term = ( $block instanceof WP_Block && isset( $block->context['apql/filterTerm'] ) ) ? (string) $block->context['apql/filterTerm'] : '';

	// Collect all featured images from current page posts
	$image_ids = [];
	foreach ( $wp_query->posts as $post ) {
		if ( ! is_object( $post ) ) {
			continue;
		}
		$post_id = isset( $post->ID ) ? (int) $post->ID : 0;
		if ( ! $post_id ) {
			continue;
		}
		if ( $filter_tax && $filter_term ) {
			$terms = get_the_terms( $post_id, $filter_tax );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				continue;
			}
			$slugs = array_map(
				function ( $t ) {
					return is_object( $t ) && isset( $t->slug ) ? (string) $t->slug : '';
				},
				$terms
			);
			if ( ! in_array( $filter_term, $slugs, true ) ) {
				continue;
			}
		}
		$thumb_id = get_post_thumbnail_id( $post_id );
		if ( $thumb_id ) {
			$image_ids[] = (int) $thumb_id;
		}
	}

	if ( empty( $image_ids ) ) {
		return '<div class="apql-empty">' . esc_html__( 'No posts found.', 'apql-gallery' ) . '</div>';
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
		$gallery = '<figure class="wp-block-gallery has-nested-images is-cropped">';
		foreach ( $image_ids as $id ) {
			$img = wp_get_attachment_image(
				$id,
				'large',
				false,
				array(
					'loading' => 'lazy',
					'class'   => 'wp-image-' . intval( $id ),
				)
			);
			if ( $img ) {
				$gallery .= '<figure class="wp-block-image">' . $img . '</figure>';
			}
		}
		$gallery .= '</figure>';
		$html     = $gallery;

		if ( empty( $html ) ) {
			$shortcode_html = gallery_shortcode(
				array(
					'ids'  => $ids_csv,
					'size' => 'large',
					'link' => 'none',
				)
			);
			if ( ! empty( $shortcode_html ) ) {
				$html = $shortcode_html;
			}
		}
	}

	return '<div class="wp-block-apql-gallery apql-gallery">' . $html . '</div>';
}

/**
 * APQL Filter block render: iterates terms on current page and renders inner blocks per term with proper context.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block inner content (unused).
 * @param WP_Block $block      Full block instance.
 * @return string HTML output.
 */
function apql_filter_render_block( $attributes, $content = '', $block = null ) {
	$taxonomy = isset( $attributes['taxonomy'] ) && is_string( $attributes['taxonomy'] ) ? $attributes['taxonomy'] : '';

	$use_context = ( $block instanceof WP_Block ) && ! empty( $block->context['query'] );
	if ( ! $use_context ) {
		return '<div class="apql-filter"><em>' . esc_html__( 'Place this block inside a Query Loop block.', 'apql-gallery' ) . '</em></div>';
	}

	// If no taxonomy is selected, prompt the user (editor-friendly message)
	if ( '' === $taxonomy ) {
		return '<div class="apql-filter apql-filter--empty"><em>' . esc_html__( 'Select a taxonomy in the block settings.', 'apql-gallery' ) . '</em></div>';
	}

	global $wp_query;
	if ( ! ( $wp_query instanceof WP_Query ) || empty( $wp_query->posts ) ) {
		return '<div class="apql-filter apql-filter--empty">' . esc_html__( 'No posts found.', 'apql-gallery' ) . '</div>';
	}

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return '<div class="apql-filter apql-filter--empty">' . esc_html__( 'Taxonomy not found.', 'apql-gallery' ) . '</div>';
	}

	// Collect all terms from current page posts
	$groups = array();
	foreach ( $wp_query->posts as $post ) {
		if ( ! is_object( $post ) ) {
			continue;
		}
		$post_id = isset( $post->ID ) ? (int) $post->ID : 0;
		if ( ! $post_id ) {
			continue;
		}
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			continue;
		}
		foreach ( $terms as $t ) {
			$key = is_object( $t ) && isset( $t->slug ) ? (string) $t->slug : ( is_object( $t ) && isset( $t->term_id ) ? (string) $t->term_id : '' );
			if ( '' === $key ) {
				continue;
			}
			if ( ! isset( $groups[ $key ] ) ) {
				$groups[ $key ] = array(
					'term'  => $t,
					'count' => 0,
				);
			}
			++$groups[ $key ]['count'];
		}
	}

	if ( empty( $groups ) ) {
		return '<div class="apql-filter apql-filter--empty">' . esc_html__( 'No matching terms for current posts.', 'apql-gallery' ) . '</div>';
	}

	// Sort groups based on attributes
	$order_by = isset( $attributes['termOrderBy'] ) ? (string) $attributes['termOrderBy'] : 'name';
	$order    = isset( $attributes['termOrder'] ) ? strtolower( (string) $attributes['termOrder'] ) : 'asc';
	$order    = in_array( $order, array( 'asc', 'desc' ), true ) ? $order : 'asc';

	$value_for = function( $entry ) use ( $order_by ) {
		$term  = isset( $entry['term'] ) ? $entry['term'] : null;
		$count = isset( $entry['count'] ) ? (int) $entry['count'] : 0;
		if ( ! is_object( $term ) ) {
			return null;
		}
		switch ( $order_by ) {
			case 'slug':
				return isset( $term->slug ) ? (string) $term->slug : '';
			case 'id':
				return isset( $term->term_id ) ? (int) $term->term_id : 0;
			case 'count':
				return $count;
			case 'date_name':
				// Attempt to parse a date from the term name (e.g., "November 14, 2025").
				$name = isset( $term->name ) ? (string) $term->name : '';
				$ts   = $name ? strtotime( $name ) : false;
				// Fallback: if parsing fails, return 0 to push to one side consistently.
				return false !== $ts ? (int) $ts : 0;
			case 'name':
			default:
				return isset( $term->name ) ? (string) $term->name : '';
		}
	};

	uasort(
		$groups,
		function ( $a, $b ) use ( $value_for, $order, $order_by ) {
			$va = $value_for( $a );
			$vb = $value_for( $b );

			// Normalize types for compare
			if ( is_int( $va ) && is_int( $vb ) ) {
				$cmp = $va <=> $vb;
			} else {
				$cmp = strcmp( (string) $va, (string) $vb );
			}

			// For dates/numbers, a natural descending is often desired; we honor explicit order.
			return 'desc' === $order ? -$cmp : $cmp;
		}
	);

	// Get inner blocks structure - WordPress stores them in parsed_block
	$inner_blocks_list = array();

	// First try parsed_block (this is where WordPress stores the structure)
	if ( is_object( $block ) && isset( $block->parsed_block ) && is_array( $block->parsed_block ) ) {
		if ( isset( $block->parsed_block['innerBlocks'] ) && is_array( $block->parsed_block['innerBlocks'] ) ) {
			$inner_blocks_list = $block->parsed_block['innerBlocks'];
		}
	}

	// Fallback: try direct properties (for compatibility)
	if ( empty( $inner_blocks_list ) ) {
		if ( is_object( $block ) && property_exists( $block, 'innerBlocks' ) && is_array( $block->innerBlocks ) ) {
			$inner_blocks_list = $block->innerBlocks;
		} elseif ( is_object( $block ) && property_exists( $block, 'inner_blocks' ) && is_array( $block->inner_blocks ) ) {
			$inner_blocks_list = $block->inner_blocks;
		}
	}

	// Render a section for each term with context provided
	$out = '<div class="apql-filter" data-taxonomy="' . esc_attr( $taxonomy ) . '">';

	foreach ( $groups as $key => $data ) {
		$term    = $data['term'];
		$slug    = isset( $term->slug ) ? (string) $term->slug : (string) $key;
		$name    = isset( $term->name ) ? (string) $term->name : (string) $slug;
		$term_id = isset( $term->term_id ) ? (int) $term->term_id : 0;

		$term_obj = array(
			'slug' => $slug,
			'name' => $name,
			'id'   => $term_id,
		);

		$out .= '<section class="apql-filter__group apql-filter__group--term-' . esc_attr( $slug ) . '">';

		if ( ! empty( $inner_blocks_list ) ) {
			// Save current global query state
			$prev_queried_object = isset( $wp_query->queried_object ) ? $wp_query->queried_object : null;
			$prev_is_tax         = isset( $wp_query->is_tax ) ? $wp_query->is_tax : false;
			$prev_query_vars     = isset( $wp_query->query_vars ) && is_array( $wp_query->query_vars ) ? $wp_query->query_vars : array();

			// Temporarily set queried object to current term so core/post-terms and other core blocks work
			if ( $term_id && function_exists( 'get_term' ) ) {
				$full_term = get_term( $term_id, $taxonomy );
				if ( ! is_wp_error( $full_term ) && is_object( $full_term ) ) {
					$wp_query->queried_object = $full_term;
					$wp_query->is_tax         = true;
					if ( is_array( $wp_query->query_vars ) ) {
						$wp_query->query_vars['taxonomy'] = $taxonomy;
						$wp_query->query_vars['term']     = $slug;
					}
				}
			}

			// Render each inner block with updated context
			foreach ( $inner_blocks_list as $inner_block ) {
				// Build child context with term information
				$child_context                      = is_array( $block->context ) ? $block->context : array();
				$child_context['apql/filterTax']    = $taxonomy;
				$child_context['apql/filterTerm']   = $slug;
				$child_context['apql/currentTerm']  = $term_obj;

				// If inner_block is already a WP_Block instance, convert to parsed
				// If it's an array (from parsed_block), use it directly
				if ( $inner_block instanceof WP_Block ) {
					$parsed_child = ap_qg_block_to_parsed( $inner_block );
				} elseif ( is_array( $inner_block ) ) {
					$parsed_child = $inner_block;
				} else {
					continue; // Skip invalid blocks
				}

				// Create new WP_Block with updated context
				$child_block = new WP_Block( $parsed_child, $child_context );
				$out        .= $child_block->render();
			}

			// Restore previous query state
			$wp_query->queried_object = $prev_queried_object;
			$wp_query->is_tax         = $prev_is_tax;
			$wp_query->query_vars     = $prev_query_vars;
		} else {
			// Fallback: show placeholder if no inner blocks
			$out .= '<div class="apql-filter__empty-placeholder">';
			$out .= '<h3>' . esc_html( $name ) . '</h3>';
			$out .= '<p><em>' . esc_html__( 'Add blocks inside "APQL Filter" to compose your layout (e.g., APQL Term Name, APQL Gallery, etc.).', 'apql-gallery' ) . '</em></p>';
			$out .= '</div>';
		}

		$out .= '</section>';
	}

	$out .= '</div>';
	return $out;
}

/**
 * APQL Term Name block render: displays current term name from context.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block inner content (unused).
 * @param WP_Block $block      Full block instance.
 * @return string HTML output.
 */
function apql_term_name_render_block( $attributes, $content = '', $block = null ) {
	$tag_name     = isset( $attributes['tagName'] ) && is_string( $attributes['tagName'] ) ? $attributes['tagName'] : 'h3';
	$allowed_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span' );
	if ( ! in_array( $tag_name, $allowed_tags, true ) ) {
		$tag_name = 'h3';
	}

	$term = ( $block instanceof WP_Block && ! empty( $block->context['apql/currentTerm'] ) ) ? $block->context['apql/currentTerm'] : null;
	if ( ! $term || ! is_array( $term ) || empty( $term['name'] ) ) {
		return '<' . $tag_name . ' class="apql-term-name"><em>' . esc_html__( 'Term name will appear here', 'apql-gallery' ) . '</em></' . $tag_name . '>';
	}

	$name = (string) $term['name'];
	return '<' . $tag_name . ' class="apql-term-name">' . esc_html( $name ) . '</' . $tag_name . '>';
}
