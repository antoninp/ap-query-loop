# APQL Gallery

A WordPress Gutenberg block plugin that extends Query Loop with advanced filtering and gallery display capabilities. Group posts by taxonomy terms and display them as galleries with full Meow Gallery integration.

## Description

This plugin provides a suite of blocks that extend WordPress's core Query Loop functionality:

- **APQL Gallery**: Render queried posts as an image gallery using featured images
- **APQL Filter**: Group Query Loop posts by taxonomy and render custom layouts per term
- **APQL Term Name**: Display the current taxonomy term name with customizable styling and linking

Each block integrates seamlessly with the WordPress block editor, providing server-side rendering for accurate previews and full integration with Meow Gallery when installed.

## Features

- **APQL Gallery Block**: Context-aware gallery rendering with no additional settings UI
- **APQL Filter Block**: Group posts by any taxonomy with customizable term ordering
- **APQL Term Name Block**: Display term names with prefix/suffix support, optional linking, and full styling controls
- **Server-Side Rendering**: Preview actual gallery output directly in the editor
- **Meow Gallery Integration**: Automatically uses Meow Gallery shortcode when available
- **Graceful Fallback Cascade**: Meow Gallery → modern core gallery HTML → legacy `[gallery]` shortcode
- **Block Variation**: One-click Query Loop variation includes filter + term name + gallery + no-results + pagination
- **Advanced Sorting**: Order posts by date, title, author, modified date, menu order, random, comment count, or ID
- **Term Ordering**: Sort taxonomy terms by name, slug, ID, post count, or date extracted from name
- **Performance**: Efficient query; filters to posts with featured images only

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

The APQL Filter block groups Query Loop posts by a taxonomy and renders your chosen layout for each term:

- **Taxonomy Selection**: Choose from registered taxonomies via dropdown or enter a custom slug
- **Term Ordering**: Sort terms by name, slug, ID, post count, or date extracted from name
- **Order Direction**: Ascending or descending (defaults to descending)
- **Context Provision**: Passes context to child blocks:
  - `apql/currentTerm`: Current term object (slug, name, id)
  - `apql/filterTax`: Taxonomy slug
  - `apql/filterTerm`: Term slug

### APQL Term Name Block

Display the current taxonomy term name with extensive customization:

- **Text Alignment**: Left, center, right alignment controls
- **Prefix/Suffix**: Add custom text before or after the term name
- **Linking**: Optionally link to term archive page
- **Typography**: Full font family, size, weight, style, transform, decoration, letter spacing controls
- **Colors**: Text, background, and link color with gradient support
- **Spacing**: Margin and padding controls

### APQL Gallery Block

Context-aware gallery rendering with no additional settings:

- Automatically displays featured images from Query Loop posts
- Respects parent filter context when inside APQL Filter
- Falls back gracefully when Meow Gallery is not available

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

1. **Context Consumption**: Declares `usesContext` for `query`, `queryId`, `apql/filterTax`, and `apql/filterTerm`; must reside under `core/query` or `apql/filter`.
2. **Single Query Pass**: Server render reuses the global `$wp_query` already executed by parent Query Loop block.
3. **Featured Image Filter**: Collects only posts with a valid featured image; respects filter context when inside APQL Filter.
4. **Rendering Cascade**:
   - Meow Gallery shortcode if available
   - Modern gallery HTML (`wp-block-gallery` + nested `wp-block-image`)
   - Legacy `[gallery ids="..."]` shortcode as tertiary fallback
5. **Pagination**: Delegated entirely to sibling core pagination blocks.

### APQL Filter

1. **Taxonomy Grouping**: Queries all terms from the selected taxonomy that have posts in the current Query Loop.
2. **Term Ordering**: Sorts terms based on `termOrderBy` (name/slug/id/count/date_name) and `termOrder` (asc/desc).
3. **Date Extraction**: When using `date_name` ordering, extracts dates from term names (supports formats like "YYYY", "YYYY-MM", "Month YYYY").
4. **Context Injection**: For each term, temporarily sets context and renders child InnerBlocks with term-specific data.
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
3. Run `npm run release` to update versions and prepend the changelog entry in `README.md`.
4. Push commits and tag: `git push && git push --tags`.
5. GitHub Actions (release workflow) builds package from tag.

Flags:

- `--tag`: Create annotated git tag `vX.Y.Z` after commit.
- `--build`: Run `npm run build` before committing/tagging.
- `--stable`: Also update `readme.txt` "Stable tag:" if present (for wp.org).
- `--auto-summary`: Generate summary from Conventional Commits since last tag.
- `--auto-changes`: Populate `changes` from Conventional Commits subjects since last tag.
- `--dry-run`: Perform all steps without writing files, commits, or tags.

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
