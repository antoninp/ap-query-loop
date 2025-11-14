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
1. Post Type Selection
   - UI control (dropdown) to choose any public post type.
2. Query Behavior
   - Retain core Query Loop features: pagination (if enabled), ordering, filters (taxonomy, author, date) as supported by core components.
   - Respect `perPage`.
3. Rendering
   - Frontend: Output a Meow Gallery of featured images for posts in the query (Meow defaults, no custom gallery controls).
   - Editor: Preview a grid of featured images approximating the gallery (no custom settings).
4. Featured Image Handling
   - Include only posts that have a featured image (skip those without).
5. Fallbacks
   - No posts: show a translatable “No posts found.” message.
   - Fallback cascade when Meow Gallery not usable: (1) Modern core Gallery block HTML (nested `wp-block-image` figures) (2) Legacy `[gallery ids="..."]` shortcode as tertiary compatibility layer for classic themes.
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

## Block Attributes (initial)
- `postType: string`
- `queryProps: object` (similar shape to core Query Loop; e.g., `perPage`, `order`, `orderBy`, optional `taxQuery`)
- `enablePagination: boolean`
- Reserved (not implemented now): `imageSize`, `includePlaceholders`.

## Editor UX Outline
- Sidebar Panel: Post Type selector.
- Reuse core Query Loop inspector panels where practical (or provide minimal equivalents).
- Preview: Uses actual server-side rendering of the Meow Gallery shortcode when Meow Gallery is active, via the `<ServerSideRender />` component (falls back to REST-powered thumbnail grid if shortcode unavailable or Meow inactive).
- Loading: Spinner when query or post type changes or server render in flight.

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

## Implementation Plan
1. Boilerplate
   - Add plugin header in `ap-query-loop.php` (if not present).
   - Load text domain early (`init`).
   - Register dynamic block on `init`.
2. Block Metadata
   - Update/confirm `block.json` for `ap/query-loop-gallery`: name, category, icon, attributes (`postType`, `queryProps`, `enablePagination`, optional `sizeSlug`, optional `forceLegacy`), `usesContext` (`query`, `queryId`), `editorScript`, `renderCallback`.
3. Build Setup
   - Ensure `package.json` uses `@wordpress/scripts`.
   - Create/extend `src/index.js` (registration & variation), `src/edit.js` (inspector + preview), `src/style.scss` & optional `src/editor.scss`.
4. Editor Components
   - Post type selector: fetch `/wp/v2/types` filtering public types.
   - Minimal query controls: `perPage`, `order`, `orderBy` (extendable later).
   - Preview: `<ServerSideRender>`; implement debounce; cap preview `perPage` at 12.
5. Server Render (Dynamic Child Block)
   - Derive query args via `build_query_vars_from_query_block()` using context and page key.
   - Collect post IDs with featured images; build attachment ID array.
   - Fallback cascade: Meow → core gallery HTML → `[gallery ids="..."]` shortcode.
   - Escape/sanitize output; provide i18n fallback message when empty.
6. Pagination
   - Maintain compatibility with core pagination blocks; rely on `queryId` page param naming; do not output custom pagination if core pagination siblings exist.
7. Enqueues & Assets
   - Conditionally enqueue front-end styles only if block present (e.g., detect via `has_block`).
   - Editor assets enqueued via `block.json` handles.
8. I18n & Security
   - Sanitize attributes defensively (types, arrays, ints).
   - Use escaping for URLs, HTML, attributes; avoid raw image HTML unless generated via `wp_get_attachment_image()`.
9. Documentation
   - Update `README.md` with fallback cascade details and Meow detection methods.
   - Note soft dependency and degradation behavior.
10. Block Variation
   - Provide a `core/query` variation that inserts `[ap/query-loop-gallery, core/query-no-results, core/query-pagination]` pre-composed layout.
11. Manual Acceptance Review
   - Scenarios: Meow active/inactive; classic vs block theme; empty query; pagination; large perPage limit; no PHP notices with `WP_DEBUG`.
12. Future (Deferred)
   - Transient caching; extended query filters; gallery layout controls; placeholders.

## Acceptance Criteria
- Block is discoverable in inserter (category chosen) and variation available for quick insertion.
- Selecting a post type updates editor preview with featured images (limited preview count when large).
- Frontend renders Meow gallery when active; otherwise modern core gallery HTML; otherwise legacy shortcode markup if forced/fallback.
- Pagination integration intact with core pagination blocks (page changes update gallery results).
- No PHP notices/warnings under `WP_DEBUG`.
- All user-facing strings translatable; attributes sanitized; output escaped.
- Empty or no-image results show translatable fallback message.
- Fallback cascade functions correctly across classic and block themes.

## Assumptions
- Meow Gallery either provides a shortcode or auto-enhances standard gallery markup.
- Latest WordPress provides stable core Query Loop controls we can reuse or extend.

## Future Enhancements (Not in initial scope)
- Gallery layout/style controls (columns, aspect ratio, link behavior).
- Placeholder images for posts lacking featured images (attribute `includePlaceholders`).
- Transient caching layer keyed by query hash.
- Additional query filters (taxonomy UI, author filter panel).
- Force legacy shortcode attribute for edge theme compatibility.
