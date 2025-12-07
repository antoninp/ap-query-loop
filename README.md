# APQL Gallery

A WordPress Gutenberg block plugin that extends Query Loop with advanced filtering and gallery display capabilities. Group posts by taxonomy terms or by meta values and display them as galleries with full Meow Gallery integration.

## Description

This plugin provides a suite of blocks that extend WordPress's core Query Loop functionality:

- **APQL Gallery**: Render queried posts as an image gallery using featured images
- **APQL Filter**: Group Query Loop posts by taxonomy or meta and render custom layouts per group
- **APQL Term Name**: Display the current taxonomy term name with customizable styling and linking

Each block integrates seamlessly with the WordPress block editor, providing server-side rendering for accurate previews and full integration with Meow Gallery when installed.

## Features

- **APQL Gallery Block**: Context-aware gallery rendering with full Meow Gallery options control
- **Gallery Options**: Customize layout (tiles, masonry, justified, square, cascade), columns, gutter, row height, animations, captions, link behavior, custom CSS, and alignment
- **APQL Filter Block**: Group posts by taxonomy, post meta, or WordPress date fields (`post_date`, `post_modified`)
- **APQL Term Name Block**: Display term names with HTML tag selection (H2/H3/H4), prefix/suffix, optional linking, and full styling controls including writing mode
- **Server-Side Rendering**: Preview actual gallery output directly in the editor
- **Meow Gallery Integration**: Automatically uses Meow Gallery shortcode when available with full control over all gallery options
- **Graceful Fallback Cascade**: Meow Gallery → modern core gallery HTML → legacy `[gallery]` shortcode
- **Standalone or Filtered**: APQL Gallery works independently or within APQL Filter with automatic passthrough mode
- **Block Variation**: One-click Query Loop variation includes filter + term name + gallery + no-results + pagination
- **Advanced Sorting**: Order posts by date, title, author, modified date, menu order, random, comment count, or ID
- **Group Ordering**: Sort taxonomy terms by name, slug, ID, post count, or date extracted from name; when grouping by meta, sort by meta value (string) or by count; date groups sort chronologically
- **Performance**: Efficient query; filters to posts with featured images only
- **Archive Compatible**: Works with inherited archive queries (e.g., taxonomy archives) without creating custom queries

## Requirements

- WordPress 6.5 or higher
- PHP 7.4 or higher
- Node.js and npm (for development only)
- Meow Gallery (optional, but recommended for enhanced gallery features)

## Release Install

Download ready-to-install ZIPs from the GitHub Releases page:

- https://github.com/antoninp/ap-query-loop/releases

In WordPress, go to Plugins > Add New > Upload Plugin and upload `ap-query-loop.zip`.

## Manual Install

1. Upload the `ap-query-loop` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. (Optional) Install and activate Meow Gallery for enhanced gallery features

## Usage

### Quick Start with Block Variation

1. Insert a **Query Loop** block and select the "Query: APQL Filter + Gallery" variation.
2. Configure the Query Loop (post type, filters, pagination) using core controls.
3. Configure the APQL Filter taxonomy in the block sidebar.
4. Customize term display and gallery settings as needed.
5. Preview updates immediately via server render.
6. Publish and view the filtered galleries on the frontend.

### Manual Setup

1. Insert a **Query Loop** block.
2. Add an **APQL Filter** block inside the Query Loop.
3. Choose your taxonomy from the dropdown (or enter a custom slug).
4. Configure term ordering (by name, slug, ID, post count, or date).
5. Add **APQL Term Name** and **APQL Gallery** blocks inside the filter.
6. Optionally add **Query No Results** and **Query Pagination** blocks.

### APQL Filter Block

The APQL Filter block groups Query Loop posts by a taxonomy, a meta key, or WordPress date fields and renders your chosen layout for each group:

- **Grouping Mode**: `taxonomy` (default), `meta`, or `date`
- **Taxonomy Selection** (taxonomy mode): Choose from registered taxonomies via dropdown or enter a custom slug
- **Meta Selection** (meta mode): Provide `metaKey`; optionally set `metaType` (`string` or `date`) and `dateFormat` (default `F j, Y`)
- **Date Selection** (date mode): Choose `post_date` (published date) or `post_modified` (last modified date); posts are automatically grouped by date with chronological ordering
- **Ordering**: Sort terms (taxonomy mode) by name, slug, ID, post count, or date extracted from name; sort meta groups by value or count; date groups sort chronologically; choose ascending/descending (default descending)
- **Context Provision**: Passes context to child blocks so they render per-group content:
  - `apql/currentTerm`: Group descriptor (term object for taxonomy; name/value for meta; date string for date mode)
  - `apql/filterTax`: Taxonomy slug in taxonomy mode; meta key in meta mode; date field name in date mode (`post_date` or `post_modified`)
  - `apql/filterTerm`: Term slug in taxonomy mode; meta value in meta mode; formatted date value in date mode

### APQL Term Name Block

Display the current taxonomy term name with extensive customization:

- **HTML Tag**: Choose semantic heading level (H2, H3, or H4) with H2 as default
- **Text Alignment**: Left, center, right alignment controls
- **Prefix/Suffix**: Add custom text before or after the term name
- **Linking**: Optionally link to term archive page
- **Typography**: Full font family, size, weight, style, transform, decoration, letter spacing, and writing mode (orientation) controls
- **Colors**: Text, background, and link color with gradient support
- **Spacing**: Margin and padding controls

### APQL Gallery Block

Context-aware gallery rendering with full Meow Gallery options control:

- Automatically displays featured images from Query Loop posts
- Works standalone inside Query Loop or within APQL Filter with automatic passthrough mode
- Respects parent filter context when inside APQL Filter (taxonomy-or-meta-or-date aware)
- **Gallery Options** (when Meow Gallery is active):
  - **Layout**: Tiles, Masonry, Justified, Square Grid, or Cascade
  - **Columns**: 0-6 columns (0 = auto, not available for Cascade layout)
  - **Gutter**: Spacing between images (0-50px)
  - **Row Height**: For justified layout (configurable in pixels)
  - **Image Size**: Thumbnail, Medium, Large, or Full Size
  - **Animation**: None, Zoom In, Zoom Out, Fade In, Fade Out, or Colorize
  - **Captions**: Attachment Title, Attachment Caption, or Image Description
  - **Image Link**: Link to Attachment Page, Media File, or None
  - **Custom CSS Class**: Add custom classes for styling
  - **Alignment**: Left, Center, Right, Wide, or Full Width
- Falls back gracefully when Meow Gallery is not available (core gallery HTML or legacy `[gallery]` shortcode)
- Uses the inherited Query Loop (no custom queries) and therefore works seamlessly on archive templates

### Post Ordering

When using the APQL variation, additional post ordering controls are available:

- **Order By**: Date, title, author, modified date, menu order, random, comment count, or post ID
- **Order Direction**: Ascending or descending

Notes:
- The filter block saves its children (InnerBlocks) and renders dynamically on the server to inject context per term
- The main query is not altered beyond temporarily setting `queried_object` for core taxonomy blocks to resolve labels

## Development

### Setup

```bash
# Install dependencies
npm install

# Build for production
npm run build

# Start development watch mode
npm start
```

### Project Structure

```
ap-query-loop/
├── ap-query-loop.php       # Main plugin file
├── blocks/                 # Block definitions
│   ├── gallery/
│   │   └── block.json      # APQL Gallery metadata
│   ├── filter/
│   │   └── block.json      # APQL Filter metadata (with InnerBlocks support)
│   └── term-name/
│       └── block.json      # APQL Term Name metadata
├── includes/               # PHP helpers
│   └── render-callbacks.php # Server-side render functions for all blocks
├── src/                    # Build source
│   ├── index.js           # Block registration, editor UI, and Query order controls
│   └── style.scss         # Frontend styles
├── build/                  # Compiled assets (generated)
├── scripts/                # Build automation
│   └── update-version.js  # Release script
├── version.json            # Version configuration for releases
```

### Build Commands

- `npm run build` - Production build (minified)
- `npm start` - Development mode with watch
- `npm run lint:js` - JavaScript linting
- `npm run format` - Code formatting

## How It Works

### APQL Gallery

1. **Context Consumption**: Declares `usesContext` for `query`, `queryId`, `apql/filterTax`, and `apql/filterTerm`; must reside under `core/query` (can be used with or without `apql/filter`).
2. **Single Query Pass**: Server render reuses the global `$wp_query` already executed by parent Query Loop block.
3. **Featured Image Filter**: Collects only posts with a valid featured image; respects filter context when inside APQL Filter. If `apql/filterTax` is a registered taxonomy, filter by term; if it's a WordPress date field (`post_date` or `post_modified`), filter by date; otherwise treat it as a meta key and filter by exact meta value equality.
4. **Gallery Options**: When Meow Gallery is active, passes configured attributes (layout, columns, gutter, rowHeight, size, animation, captions, link, customClass, align) directly to the Meow Gallery shortcode for full control.
5. **Rendering Cascade**:
   - Meow Gallery shortcode with configured options if available
   - Modern gallery HTML (`wp-block-gallery` + nested `wp-block-image`)
   - Legacy `[gallery ids="..."]` shortcode as tertiary fallback
6. **Pagination**: Delegated entirely to sibling core pagination blocks.

### APQL Filter

1. **Grouping Modes**: `taxonomy` mode groups by terms; `meta` mode groups by meta values from the current page's posts; `date` mode groups by WordPress date fields (`post_date` or `post_modified`).
2. **Ordering**: For taxonomy, sort by name/slug/id/count/date_name; for meta, sort by value (string) or by count; for date, sort chronologically.
3. **Date Presentation**: In meta mode when `metaType` is `date`, values are formatted using `dateFormat` for headings while preserving raw value for filtering. In date mode, posts are automatically grouped by date (YYYY-MM-DD format) from the selected date field.
4. **Context Injection**: For each group, sets context and renders child InnerBlocks with group-specific data.
5. **InnerBlocks Serialization**: Saves child blocks to post content for server-side rendering with injected context.

### APQL Term Name

1. **Context Consumption**: Reads `apql/currentTerm` and `apql/filterTax` from parent APQL Filter.
2. **Display Options**: Renders term name with optional prefix/suffix and linking to term archive.
3. **Styling Support**: Full WordPress block supports for typography, colors, and spacing.
4. **Server-Side Render**: Dynamically generates output based on context and block attributes.

### Query Order Controls

1. **Variation Namespace**: Adds custom order controls to Query blocks with `namespace: 'apql-gallery'`.
2. **Extended Options**: Provides orderBy options beyond core defaults (comment count, post ID, etc.).
3. **Filter Hook**: Uses `editor.BlockEdit` filter to inject controls into Query Loop sidebar.

## Filters & Hooks

### Available Hooks

- **`editor.BlockEdit`** (JavaScript filter): Extended to add custom order controls to Query Loop blocks with APQL namespace

### Potential Future Hooks

- Filter for gallery shortcode selection or forcing legacy fallback
- Filter for term ordering customization
- Filter for date extraction patterns in term names
- Action hooks for custom rendering phases (before/after gallery HTML)

## Changelog

### 0.2.6 - WordPress 6.9 compatibility and feature enhancements
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

### 0.2.5 - Release script fix
- Fixed readme.txt changelog not updated by release script

### 0.2.4 - Code cleanup
- Removed unnecessary WordPress function stubs added for local development lint errors
- Retained essential helper functions for block rendering (ap_qg_block_to_parsed, ap_render_blocks_with_context)

### 0.2.3 - Meta grouping and gallery meta-aware filtering
- APQL Filter: added groupBy=meta mode with metaKey, metaType (string|date) and dateFormat options
- APQL Gallery: respects taxonomy-or-meta context; filters by exact meta value when grouping by meta
- Improved archive compatibility by reusing inherited Query Loop (no custom queries)
- Documentation updated to reflect meta grouping and taxonomy-or-meta aware behavior

### 0.2.2 - Release scripts fix
- Fixed README.txt not committed after release script execution

### 0.2.1 - Release scripts update
- Added auto push option in release script
- Fixed scripts included in released zip
- Added standard WP readme.txt
- Added index.php to plugin folder to prevent directory listing
- Updated README with new release instructions

### 0.2.0 - Major feature release: Taxonomy filtering and term display blocks
- Added APQL Filter block to group Query Loop posts by taxonomy terms
- Added APQL Term Name block with full styling controls (typography, colors, spacing)
- Added term ordering options (name, slug, ID, post count, date from name)
- Added post ordering controls to Query Loop variation (inspired by Advanced Query Loop)
- Added prefix/suffix options and optional linking for term names
- Added dropdown taxonomy selector with custom slug input fallback
- Updated Query Loop variation to include filter + term name + gallery structure
- Restructured plugin architecture with separate block directories
- Renamed blocks: AP Group by Tax → APQL Filter, AP Query Loop Gallery → APQL Gallery, Term Info → APQL Term Name
- Set default descending order for filtered galleries and terms
- Fixed InnerBlocks support to properly serialize child blocks

### 0.1.5 - Fix query
- Fixed issue with query parameters not being applied correctly to the gallery.

### 0.1.4 - Release scripts
- Added building scripts to automate release process

### 0.1.3 - Context-only refactor
- Removed all block attributes & inspector controls (context-only design).

### 0.1.2 - Context & Fallback Update
- Added `usesContext` and `parent` to restrict block under `core/query`.
- Implemented context-aware query building (`build_query_vars_from_query_block`).
- Added fallback cascade: Meow → core gallery HTML → legacy shortcode.
- Registered `core/query` variation (gallery + no-results + pagination).
- Updated README and PLAN; refined pagination behavior inside Query Loop.

### 0.1.1 - Build Update
- Add version sync system.
- Fix release packaging.

### 0.1.0 - Initial Release
- Basic block functionality.
- Post type selection.
- Query controls (perPage, order, orderBy).
- Server-side rendering with Meow Gallery integration.
- Pagination support.
- Initial fallback rendering.

## License

## Release Workflow

Automated versioning & changelog:

1. Make code changes; commit normally.
2. Edit `version.json` with new `version`, `summary`, and `changes` array (optionally add `date`).
3. Run `npm run release` to update versions and prepend the changelog entry in `README.md`. To create and push the tag automatically, use: `npm run release -- --tag --push`.
4. If you didn't use `--push`, push manually:
  - Push commit: `git push`
  - Push tag: `git push --follow-tags` (or `git push origin vX.Y.Z`)
5. GitHub Actions (release workflow) builds package from the pushed tag.

Flags:

- `--tag`: Create annotated git tag `vX.Y.Z` after commit.
- `--build`: Run `npm run build` before committing/tagging.
- `--auto-summary`: Generate summary from Conventional Commits since last tag.
- `--auto-changes`: Populate `changes` from Conventional Commits subjects since last tag.
- `--dry-run`: Perform all steps without writing files, commits, or tags.
- `--push`: Push commit and tag after creation (uses `git push --follow-tags`; sets upstream if missing; falls back to pushing the tag directly if needed).
- `--remote <name>`: Remote to use with `--push` (defaults to `origin`).

Alternative tools: consider `standard-version`, `changesets`, or `release-please` if you later adopt Conventional Commits or want automated semver inference. Current custom script keeps WordPress-specific headers synchronized.

GPL-2.0-or-later

## Testing Checklist

Use the following manual scenarios to verify functionality:

### Basic Gallery Functionality

1. **Variation Insertion**: Insert the "Query: APQL Filter + Gallery" variation; confirm filter + term name + gallery + no-results + pagination blocks appear.
2. **Meow Active**: With Meow Gallery enabled, gallery renders via Meow shortcode (inspect HTML for Meow classes or scripts).
3. **Meow Inactive**: Deactivate Meow; gallery falls back to modern core gallery HTML (`wp-block-gallery` wrapper).
4. **Legacy Fallback**: Temporarily force failure to confirm `[gallery]` shortcode fallback (optional edge test).
5. **Featured Image Filtering**: Include posts without featured images; ensure they are excluded from rendered gallery.

### Filtering & Grouping

6. **Taxonomy Selection**: Test dropdown taxonomy selection and custom slug input in APQL Filter.
7. **Term Grouping**: Verify posts are correctly grouped by selected taxonomy terms.
8. **Term Ordering**: Test all term ordering options (name, slug, ID, count, date_name) in both directions.
9. **Date Name Sorting**: Use terms with date-like names ("2024", "January 2024", "2024-01") and verify chronological sorting.
10. **Empty Terms**: Ensure terms with no posts in the current query are not displayed.

### Term Name Display

11. **Term Name Rendering**: Verify term names display correctly within APQL Filter.
12. **Prefix/Suffix**: Add prefix and suffix text; confirm they appear before/after term name.
13. **Term Linking**: Enable "Make term a link" and verify links to term archive pages.
14. **Styling Controls**: Test text alignment, typography, colors, and spacing controls.

### Query Ordering

15. **Post Order Controls**: In variation Query Loop, test all orderBy options (date, title, author, modified, menu_order, rand, comment_count, id).
16. **Order Direction**: Toggle between ascending and descending order.

### General

17. **Pagination**: Add many posts with featured images; verify pagination works with filtered galleries.
18. **No Results**: Select a taxonomy with no terms or post type with no posts; confirm "No posts found." message.
19. **Block Theme vs Classic Theme**: Test in both to confirm styling adaptability.
20. **Internationalization**: Switch site language; confirm all strings are translatable.

## Support

For issues, feature requests, or contributions, please open an issue on the GitHub repository.

## Credits

Developed by Antonin Puleo
