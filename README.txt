=== APQL Gallery ===
Contributors: Antonin Puleo
Tags: query loop, gallery, taxonomy, block, gutenberg, meow gallery
Requires at least: 6.5
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced Query Loop blocks: group posts by taxonomy and render galleries. Integrates with Meow Gallery with graceful fallbacks.

== Description ==

APQL Gallery extends the WordPress Query Loop with a set of blocks to display queried posts as image galleries and to group them by taxonomy terms.

It provides three blocks that work together inside `core/query`:

- APQL Gallery: Render queried posts as a gallery of featured images (Meow Gallery integration when available).
- APQL Filter: Group Query Loop posts by a taxonomy and render custom layouts per term.
- APQL Term Name: Display the current term name with optional link, prefix/suffix, and full styling controls.

Features:

- Context-only design; uses `core/query` settings (post type, filters, pagination).
- Server-side rendering for accurate editor previews.
- Meow Gallery integration with graceful fallback to core gallery HTML and legacy `[gallery]` shortcode.
- One-click Query Loop variation that composes Filter + Term Name + Gallery + No Results + Pagination.
- Advanced ordering: post order (date, title, author, modified, menu order, random, comment count, ID) and term order (name, slug, ID, count, date from name).

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
   - Meow Gallery shortcode when available; otherwise modern core gallery HTML or legacy `[gallery]` fallback.

2. `apql/filter` — APQL Filter
   - Groups current Query Loop posts by a chosen taxonomy.
   - Provides InnerBlocks to compose your per-term layout (e.g., Term Name + Gallery).
   - Term ordering: name, slug, ID, post count, or date extracted from term name.

3. `apql/term-name` — APQL Term Name
   - Displays current term name with optional link to term archive.
   - Supports prefix/suffix, text alignment, typography, colors, and spacing controls.

== Frequently Asked Questions ==

= Does it work without Meow Gallery? =
Yes. The block falls back to core gallery HTML and as a tertiary fallback to the legacy `[gallery]` shortcode.

= Does APQL Gallery work outside Query Loop? =
It’s designed to consume context from `core/query` and should be used inside a Query Loop for correct results.

= How is pagination handled? =
Pagination is handled by core pagination blocks; APQL Gallery doesn’t output pagination markup itself.

== Changelog ==

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
