# ap-query-loop — Specification and Plan

Last updated: 2025-11-14

## Purpose
Create a custom Gutenberg block that behaves like the core “Query Loop” block but renders the queried posts as a Meow Gallery using each post’s featured image. No custom gallery settings UI; use Meow Gallery defaults.

- Target: Latest WordPress
- Backward compatibility: Not critical
- Coding: Follow WordPress best practices (block.json, i18n, sanitization/escaping, conditional enqueues, coding standards)

## Block
- Name: `ap/query-loop-gallery`
- Category: Theme (or custom category like “AP Blocks”)
- Type: Dynamic (server-rendered) block with editor preview

## Functional Requirements
1. Context-Only Operation
   - Block exposes no inspector UI or attributes; must reside under `core/query`.
2. Query Behavior
   - Reuse all core Query Loop features (pagination, ordering, taxonomy filters) via context—no duplication.
3. Rendering
   - Frontend & editor: Server-side render of gallery based solely on current query context.
4. Featured Image Handling
   - Include only posts with featured images; skip others.
5. Fallbacks
   - No posts or no images: translatable “No posts found.” message.
   - Cascade: Meow Gallery shortcode → modern core gallery HTML → legacy shortcode.
6. Internationalization
   - Wrap all user-facing strings in translation functions; load text domain.
7. Security & Data Integrity
   - Sanitize saved attributes; escape output (`esc_url`, `esc_html`, `wp_kses_post`).
8. Performance
   - Avoid N+1 queries; fetch posts and thumbnails efficiently.
   - Optional: lightweight per-request cache of results per block instance (defer if unnecessary).
9. Dependencies
   - Soft dependency on Meow Gallery; detect and degrade gracefully when not active.

## Explicitly Out of Scope (for now)
- Gallery settings controls (columns/layout/spacing).
- Advanced image size manipulation UI.
- Exhaustive testing of gallery style variants.

## Block Attributes
None (context-only). Potential future optional: `forceLegacy`, `includePlaceholders` (deferred).

## Editor UX Outline
- No inspector controls; relies entirely on parent Query Loop configuration.
- Preview: `<ServerSideRender />` returns gallery HTML for current query context.
- Spinner automatically shown by component on request.

## Rendering Strategy
- Architecture: Use the core `Query Loop` (`core/query`) block and provide a single child dynamic block `ap/query-loop-gallery` that consumes its query context (`query`, `queryId`). This avoids re‑implementing pagination, no‑results, and inner block ecosystem.
- Register `ap/query-loop-gallery` as a dynamic block using `render_callback`.
- Server render: derive `$page` and query args via `build_query_vars_from_query_block()`, filter posts to those with featured images, collect attachment IDs.
- Output cascade:
   1. If Meow Gallery active (plugin detected via class/constant/shortcode): render Meow’s gallery (preferred shortcode or API) using the collected post featured image IDs.
   2. Else render modern core Gallery block equivalent HTML: `<figure class="wp-block-gallery has-nested-images is-cropped">` containing nested `<figure class="wp-block-image">` wrappers generated via `wp_get_attachment_image()` for each featured image ID.
   3. Else (should rarely occur unless block markup fails or forced) emit legacy `[gallery ids="1,2,3" link="none"]` shortcode for broad theme compatibility.
- No posts or no images after filtering: output translatable fallback message.
- Editor preview: Prefer `<ServerSideRender block="ap/query-loop-gallery" attributes={...} />` to mirror front-end markup. Optional future optimization: attribute‑change debounce and temporary REST thumbnail grid when latency threshold exceeded.
- Pagination: Use the same `query-<queryId>-page` param key logic as core to remain compatible with `core/query-pagination-*` siblings.
- Performance considerations: Limit preview `perPage` to a sensible maximum (e.g., 12) while honoring full value on front end. Consider transient cache keyed by (query hash + page + block attributes) in a future enhancement.

## Edge Cases
- No posts in query: show i18n “No posts found.”
- No featured images among matching posts: also show the same fallback message.
- Large result sets: respect `perPage`; if pagination enabled, output pagination links via `paginate_links()`.
- Private post types: exclude from selector.

## Implementation Plan (Revised Context-Only)
1. Boilerplate: Maintain plugin header, text domain load, block registration with render callback.
2. Block Metadata: Confirm `usesContext` & `parent`; remove attributes.
3. Editor Script: Minimal registration + variation; no inspector.
4. Server Render: Use `build_query_vars_from_query_block()`; filter posts by featured image; perform fallback cascade.
5. Pagination: Handled by core siblings; do not emit pagination markup.
6. Assets: Conditional frontend styles; editor script via metadata.
7. Security/I18n: Escape output; translate messages.
8. Documentation: Update README & changelog to reflect context-only approach.
9. Variation: Pre-composed layout (gallery + no-results + pagination) remains.
10. Acceptance Review: Test Meow active/inactive, empty results, pagination via core, classic vs block theme, translation.
11. Future: Optional attributes (`forceLegacy`, `includePlaceholders`), caching, extended filters.

## Acceptance Criteria
- Block discoverable only under Query Loop (or via variation) with no custom controls.
- Gallery reflects current Query Loop configuration automatically (post type, filters, ordering, pagination).
- Fallback cascade works (Meow → core gallery HTML → legacy shortcode) across themes.
- No PHP notices/warnings under `WP_DEBUG`.
- Empty or no-image results trigger translatable message.
- Pagination links from core pagination blocks update gallery images accordingly.
- Works in both block and classic themes (styles degrade gracefully).

## Assumptions
- Meow Gallery either provides a shortcode or auto-enhances standard gallery markup.
- Latest WordPress provides stable core Query Loop controls we can reuse or extend.

## Future Enhancements (Not in initial scope)
- Gallery layout/style controls (columns, aspect ratio, link behavior).
- Placeholder images for posts lacking featured images (attribute `includePlaceholders`).
- Transient caching layer keyed by query hash.
- Additional query filters (taxonomy UI, author filter panel).
- Force legacy shortcode attribute for edge theme compatibility.
