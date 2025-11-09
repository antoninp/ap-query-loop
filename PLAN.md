# ap-query-loop — Specification and Plan

Last updated: 2025-11-09

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
   - Meow Gallery inactive: degrade to a simple image list/grid without Meow enhancements.
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
- Register as a dynamic block using `render_callback`.
- Server render builds the post query, filters to posts with featured images, and outputs:
   - If Meow Gallery active: render the actual Meow Gallery shortcode (e.g., `[gallery posts="1,2,3"]`).
   - If shortcode not available but Meow styles auto-enhance standard gallery markup: output standard gallery markup with post IDs.
   - If Meow not active: basic semantic HTML list/grid of linked images with a neutral class (e.g., `ap-query-loop-basic-gallery`).
- Editor preview: Prefer `<ServerSideRender block="ap/query-loop-gallery" attributes={...} />` to display the same markup as frontend for parity. If performance becomes an issue (large queries or repeated renders), fallback to a lightweight REST-powered thumbnail grid (feature-detected by a flag/attribute or auto fallback when request exceeds a threshold duration).
- Performance considerations: Debounce attribute changes before triggering `<ServerSideRender>`; limit perPage for editor preview to a sensible maximum (e.g., 12) while still honoring frontend settings.

## Edge Cases
- No posts in query: show i18n “No posts found.”
- No featured images among matching posts: also show the same fallback message.
- Large result sets: respect `perPage`; if pagination enabled, output pagination links via `paginate_links()`.
- Private post types: exclude from selector.

## Implementation Plan
1. Boilerplate
   - Add plugin header in `ap-query-loop.php`.
   - Load text domain.
   - Register block on `init`.
2. Block Metadata
   - Create `block.json` with name, category, icon, attributes, editorScript, render callback.
3. Build Setup
   - Add `package.json` using `@wordpress/scripts`.
   - Create `src/index.js`, `src/edit.js`, `src/style.scss` (or CSS), `src/editor.scss`.
4. Editor Components
   - Post type selector component: fetch `/wp/v2/types` and list public types.
   - Query controls: reuse core components where feasible (or minimal subset for `perPage`, `order`, `orderBy`).
   - Preview component: fetch posts via `/wp/v2/{postType}?_embed&per_page=...` and render thumbnails.
5. Server Render
   - `render_callback`: use `WP_Query` with args derived from attributes; collect featured image IDs; choose Meow vs fallback markup.
6. Pagination (optional/if enabled)
   - Respect `paged` query var; output `paginate_links()`.
7. Enqueues & Assets
   - Conditionally enqueue editor and frontend assets only when the block is used.
8. I18n & Security
   - Ensure all strings are translatable; sanitize attributes; escape output.
9. Documentation
   - Update `README.md` with installation, dependency note (Meow Gallery optional), usage.
10. Manual Acceptance Review
   - Different post types, empty queries, Meow active/inactive, pagination toggled, no PHP notices/warnings.

## Acceptance Criteria
- Block is discoverable in the inserter under a sensible category.
- Selecting a post type updates the editor preview with featured images from that type.
- Frontend renders a Meow-enhanced gallery when Meow is active; otherwise a clean fallback list/grid.
- No PHP notices/warnings with `WP_DEBUG` enabled.
- All strings are translatable; attributes sanitized; output escaped.
- Pagination works when enabled and does not break gallery rendering.
- Empty results are handled with a clear, translatable message.

## Assumptions
- Meow Gallery either provides a shortcode or auto-enhances standard gallery markup.
- Latest WordPress provides stable core Query Loop controls we can reuse or extend.

## Future Enhancements (Not in initial scope)
- Gallery layout and style controls surfaced in block inspector.
- Placeholder images for posts without thumbnails.
- Caching layer for heavy queries.
