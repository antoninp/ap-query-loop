# APQL Gallery

A WordPress Gutenberg block that displays posts from a selected post type as a gallery using featured images. Integrates with Meow Gallery when available.

## Description

This block extends the functionality of WordPress's core Query Loop by rendering queried posts as an image gallery. Each post's featured image is displayed in the gallery, with full integration with Meow Gallery when installed.

## Features

- **Context-Only**: No settings UI; relies entirely on parent `core/query` block configuration.
- **Server-Side Rendering**: Preview actual gallery output directly in the editor.
- **Meow Gallery Integration**: Automatically uses Meow Gallery shortcode when available.
- **Graceful Fallback Cascade**: Meow Gallery → modern core gallery HTML → legacy `[gallery]` shortcode.
- **Block Variation**: One-click Query Loop variation includes gallery + no-results + pagination.
- **Performance**: Efficient query; filters to posts with featured images only.

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

1. Insert a **Query Loop** block (or use the provided variation "Query: Gallery (AP)").
2. Configure the Query Loop (post type, filters, ordering, pagination) using core controls.
3. Ensure the inner blocks include `APQL Gallery` (variation does this automatically).
4. Preview updates immediately via server render.
5. Publish and view the gallery on the frontend.

### APQL Filter (parent block)

This plugin also provides a parent block that groups the current Query Loop posts by a taxonomy and renders your chosen layout for every term.

- Insert `APQL Filter` inside a `core/query` block.
- Choose the taxonomy in the block sidebar (dropdown or enter a custom slug).
- Add blocks inside it (recommended: `APQL Term Name` then `APQL Gallery`).
- On the frontend, the block renders a section per term and passes context to children:
   - `apql/currentTerm`: the current term object (slug, name, id)
   - `apql/filterTax`: the taxonomy slug
   - `apql/filterTerm`: the term slug

Notes:
- The block saves its children (InnerBlocks) and renders dynamically on the server to inject context per term.
- If you created instances before this change, remove and re-insert the block so its inner blocks are serialized.
- The main query is not altered beyond temporarily setting `queried_object` for core taxonomy blocks to resolve labels.

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
├── src/
│   ├── index.js      # Block editor JavaScript (React)
│   └── style.scss    # Frontend styles
├── build/            # Compiled assets (generated)
├── ap-query-loop.php # Main plugin file
├── block.json        # Block metadata
└── PLAN.md           # Project specification
```

### Build Commands

- `npm run build` - Production build (minified)
- `npm start` - Development mode with watch
- `npm run lint:js` - JavaScript linting
- `npm run format` - Code formatting

## How It Works

1. **Context Consumption**: The block declares `usesContext` for `query` and `queryId` and must reside under `core/query`.
2. **Single Query Pass**: Server render reuses the query vars derived by WordPress core via `build_query_vars_from_query_block()`.
3. **Featured Image Filter**: Collects only posts with a valid featured image; empty set triggers fallback message.
4. **Rendering Cascade**:
   - Meow Gallery shortcode if available.
   - Modern gallery HTML (`wp-block-gallery` + nested `wp-block-image`).
   - Legacy `[gallery ids="..."]` shortcode as tertiary fallback.
5. **Pagination**: Delegated entirely to sibling core pagination blocks; this block does not generate pagination markup itself.
6. **Variation**: Provided variation pre-composes gallery + no-results + pagination for quick insertion.

## Filters & Hooks

Currently no custom filters or hooks exposed. Future versions may add:
- Filter for gallery shortcode selection or forcing legacy fallback.
- Filter for query args modification (before WP_Query runs).
- Action hooks for custom rendering phases (before/after gallery HTML).

## Changelog

### 0.2.0 - Group by Tax + UX
- Added `APQL Filter` parent block with server-side term grouping and context.
- Added `APQL Term Name` block to display the current term name.
- Improved editor UX: taxonomy dropdown and clearer guidance.

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

1. **Variation Insertion**: Insert the provided Query Loop variation; confirm gallery + no-results + pagination blocks appear.
2. **Meow Active**: With Meow Gallery enabled, gallery renders via Meow shortcode (inspect HTML for Meow classes or scripts).
3. **Meow Inactive**: Deactivate Meow; gallery falls back to modern core gallery HTML (`wp-block-gallery` wrapper).
4. **Legacy Fallback**: Temporarily force failure (e.g., simulate missing `wp_get_attachment_image`) to confirm `[gallery]` shortcode fallback (optional edge test).
5. **Pagination**: Add many posts with featured images; verify pagination links update gallery contents when using core pagination blocks.
6. **No Results**: Select a post type with no posts or filter orderBy to yield empty set; confirm "No posts found." message.
7. **Featured Image Filtering**: Include a post without a featured image; ensure it is excluded from rendered gallery.
8. **Block Theme vs Classic Theme**: Test in both to confirm styling adaptability.
9. **Standalone Usage**: Insert block outside Query Loop (if allowed manually) to verify internal pagination (legacy support) still works.
10. **Internationalization**: Switch site language; confirm fallback message translated (after providing translations in `.pot`).

## Support

For issues, feature requests, or contributions, please open an issue on the GitHub repository.

## Credits

Developed by Antonin Puleo
