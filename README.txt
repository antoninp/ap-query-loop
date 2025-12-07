=== APQL Gallery ===
Contributors: Antonin Puleo
Tags: query loop, gallery, taxonomy, block, gutenberg, meow gallery
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.2.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced Query Loop blocks: group posts by taxonomy or meta and render galleries. Integrates with Meow Gallery with graceful fallbacks.

== Description ==

APQL Gallery extends the WordPress Query Loop with a set of blocks to display queried posts as image galleries and to group them by taxonomy terms or by post meta values.

It provides three blocks that work together inside `core/query`:

- APQL Gallery: Render queried posts as a gallery of featured images (Meow Gallery integration when available).
- APQL Filter: Group Query Loop posts by a taxonomy or by a meta key and render custom layouts per group.
- APQL Term Name: Display the current term name with optional link, prefix/suffix, and full styling controls.

Features:

- Context-only design; uses `core/query` settings (post type, filters, pagination).
- Server-side rendering for accurate editor previews.
- Meow Gallery integration with full control over gallery options (layout, columns, gutter, animations, captions, etc.).
- Graceful fallback to core gallery HTML and legacy `[gallery]` shortcode when Meow Gallery is not available.
- One-click Query Loop variation that composes Filter + Term Name + Gallery + No Results + Pagination.
- Advanced ordering: post order (date, title, author, modified, menu order, random, comment count, ID) and group order (for taxonomy: name, slug, ID, count, date from name; for meta: value or count; for dates: chronological).
- Flexible grouping: by taxonomy, by post meta, or by WordPress date fields (post_date, post_modified).
- Archive compatible: works with inherited archive queries without creating custom queries.

Requirements:

- WordPress 6.5 or higher
- PHP 7.4 or higher
- Meow Gallery optional (enhanced gallery rendering)

== Installation ==

1. Upload the `ap-query-loop` folder to `/wp-content/plugins/` (or install the ZIP from the Releases page).
2. Activate the plugin from the Plugins screen.
3. (Optional) Install and activate Meow Gallery for enhanced gallery features.

== Usage ==

Quick start (recommended):

1. Insert a `Query Loop` block.
2. Choose the variation “Query: APQL Filter + Gallery”.
3. In APQL Filter settings, select a taxonomy (or enter a custom slug).
4. Adjust post and term order as needed.
5. Add pagination or other Query Loop blocks as desired.

Manual setup:

1. Insert `Query Loop`.
2. Add `APQL Filter` inside.
3. Inside APQL Filter, add `APQL Term Name` and `APQL Gallery` (default template provided).
4. Configure APQL Filter (taxonomy, term ordering) and Query Loop (post type, filters, pagination).

Notes:

- APQL Gallery must reside under `core/query` (or under `apql/filter` which itself must be inside `core/query`).
- APQL Filter passes context to children: current term object and selected taxonomy.
- Pagination is handled by core pagination blocks.

== Blocks ==

1. `apql/gallery` — APQL Gallery
   - Renders featured images from the current Query Loop posts; respects APQL Filter context if present.
   - Full Meow Gallery options control: layout (tiles, masonry, justified, square, cascade), columns, gutter, row height, image size, animations, captions, link behavior, custom CSS class, and alignment.
   - Meow Gallery shortcode when available; otherwise modern core gallery HTML or legacy `[gallery]` fallback.
   - Works standalone or inside APQL Filter with automatic passthrough mode.
   - Taxonomy-or-meta-or-date aware: when inside APQL Filter, filters by term (taxonomy mode), by exact meta value equality (meta mode), or by date (date mode).

2. `apql/filter` — APQL Filter
   - Groups current Query Loop posts by a chosen taxonomy, by a meta key, or by WordPress date fields.
   - Provides InnerBlocks to compose your per-group layout (e.g., Term Name + Gallery).
   - Taxonomy mode ordering: name, slug, ID, post count, or date extracted from term name.
   - Meta mode options: `metaKey`, optional `metaType` (`string` or `date`) and `dateFormat` for display.
   - Date mode: Filter by standard WordPress date fields (`post_date` or `post_modified`) with automatic date grouping.

3. `apql/term-name` — APQL Term Name
   - Displays current term name with optional link to term archive.
   - HTML tag selection: Choose between H2 (default), H3, or H4 for semantic heading structure.
   - Supports prefix/suffix, text alignment, typography (including writing mode/orientation), colors, and spacing controls.

== Frequently Asked Questions ==

= Does it work without Meow Gallery? =
Yes. The block falls back to core gallery HTML and as a tertiary fallback to the legacy `[gallery]` shortcode.

= Does APQL Gallery work outside APQL Filter? =
Yes! APQL Gallery can work standalone inside Query Loop or within APQL Filter. When used outside APQL Filter, it displays all posts from the query. When inside APQL Filter, it automatically filters by the current group (term, meta value, or date).

= How is pagination handled? =
Pagination is handled by core pagination blocks; APQL Gallery doesn’t output pagination markup itself.

= Can I group by post meta or date fields? =
Yes. APQL Filter supports three grouping modes:
- **Taxonomy**: Group by any taxonomy (category, tag, custom taxonomies).
- **Meta**: Group by custom field values. If the value is a date string (e.g., `YYYY-MM-DD`), set type to "date" to control the display format.
- **Date**: Group by standard WordPress date fields (`post_date` for published date or `post_modified` for last modified date).

== Changelog ==

= 0.2.6 - WordPress 6.9 compatibility and feature enhancements =
- Updated WordPress compatibility to 6.9
- Added attribute to control Meow Gallery options in APQL Gallery block
- Added HTML tag option and orientation setting for Term Name block
- Added filtering by standard WordPress date (post_date and post_modified)
- Fixed: Term link ('Make term a link') not working properly
- Fixed: APQL Gallery not usable outside of APQL Filter - added passthrough mode
- Removed unnecessary APQL preview in editor
- Removed redundant 'no post found' message (handled by WP No Results block)
- Updated documentation: Added detailed gallery options (layout, columns, gutter, animations, captions, etc.)
- Updated documentation: Documented HTML tag selection for Term Name block
- Updated documentation: Documented date field filtering mode in APQL Filter
- Updated documentation: Clarified standalone usage capability for APQL Gallery

= 0.2.5 - Release script fix =
- Fixed readme.txt changelog not updated by release script

= 0.2.4 - Code cleanup =
- Removed unnecessary WordPress function stubs added for local development lint errors
- Updated ensured essential helper rendering helpers remain (ap_qg_block_to_parsed, ap_render_blocks_with_context)

= 0.2.3 - Meta grouping and gallery meta-aware filtering =
- Added APQL Filter groupBy=meta mode with metaKey, metaType (string|date) and dateFormat options
- Updated APQL Gallery now respects taxonomy-or-meta context and filters by exact meta value when grouping by meta
- Updated archive compatibility by reusing inherited Query Loop (no custom queries)
- Updated documentation to reflect meta grouping and taxonomy-or-meta aware behavior

= 0.2.2 - Release scripts fix =
- Fixed README.txt not committed after release script execution

= 0.2.1 - Release scripts update =
- Added auto push option in release script
- Fixed scripts included in released zip
- Added standard WP readme.txt
- Added index.php to plugin folder to prevent directory listing
- Updated README with new release instructions

= 0.2.0 - Group by Tax & UX =
- Added APQL Filter block to group Query Loop posts by taxonomy terms.
- Added APQL Term Name block with full styling controls and optional linking.
- Added term ordering options (name, slug, ID, post count, date from name).
- Added post ordering controls to Query Loop variation.
- Updated variation to compose Filter + Term Name + Gallery + No Results + Pagination.
- Restructured plugin and improved editor UX.

= 0.1.5 - Fix query =
- Fixed issue with query parameters not being applied correctly to the gallery.

= 0.1.4 - Release scripts =
- Added building scripts to automate release process.

= 0.1.3 - Context-only refactor =
- Removed all block attributes & inspector controls (context-only design).

= 0.1.2 - Context & Fallback Update =
- Added `usesContext` and `parent` to restrict block under `core/query`.
- Implemented context-aware query building and fallback cascade.
- Registered `core/query` variation (gallery + no-results + pagination).

= 0.1.1 - Build Update =
- Added version sync system and fixed release packaging.

= 0.1.0 - Initial Release =
- Basic gallery block with server-side rendering and Meow Gallery integration.
- Query controls and pagination support.

== Upgrade Notice ==

= 0.2.0 =
Major feature release introducing APQL Filter and APQL Term Name blocks. Review variation and ordering options after update.
