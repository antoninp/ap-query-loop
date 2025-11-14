# AP Query Loop Gallery

A WordPress Gutenberg block that displays posts from a selected post type as a gallery using featured images. Integrates with Meow Gallery when available.

## Description

This block extends the functionality of WordPress's core Query Loop by rendering queried posts as an image gallery. Each post's featured image is displayed in the gallery, with full integration with Meow Gallery when installed.

## Features

- **Post Type Selection**: Choose any public post type via dropdown.
- **Query Controls**: Configure items per page, ordering, and pagination.
- **Query Context Integration**: Consumes `core/query` context for consistent pagination & filtering when nested.
- **Server-Side Rendering**: Preview actual gallery output in the editor.
- **Meow Gallery Integration**: Automatically uses Meow Gallery shortcode when available.
- **Graceful Fallback Cascade**: Meow Gallery → modern core gallery HTML → legacy `[gallery]` shortcode.
- **Block Variation**: One-click Query Loop variation includes gallery + no-results + pagination.
- **Performance**: Efficient queries, only includes posts with featured images.

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

1. Add the "AP Query Loop Gallery" block to any post or page
2. In the block settings sidebar:
   - Select a post type from the dropdown
   - Adjust items per page (1-24)
   - Configure ordering (date, title, menu_order, rand)
   - Set order direction (ASC or DESC)
   - Enable pagination if needed
3. The block preview will show the actual gallery output
4. Publish and view on the frontend

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

1. **Editor**: Uses `ServerSideRender` to display actual gallery output while editing.
2. **Query**: Derives query vars from `core/query` context when placed inside a Query Loop; falls back to local attributes if used standalone.
3. **Filter**: Only includes posts with featured images.
4. **Render Fallback Cascade**:
   - Meow Gallery active: render Meow Gallery shortcode for the featured image IDs.
   - Else: output modern core Gallery equivalent HTML (`wp-block-gallery` with nested `wp-block-image` figures).
   - Else (edge legacy fallback): output `[gallery ids="..."]` shortcode.
5. **Pagination**: When inside `core/query`, pagination is handled by sibling core pagination blocks; standalone usage can enable internal pagination.
6. **Variation**: Ships a `core/query` variation inserting the gallery block plus no-results and pagination blocks for rapid composition.

## Filters & Hooks

Currently no custom filters or hooks exposed. Future versions may add:
- Filter for gallery shortcode selection or forcing legacy fallback.
- Filter for query args modification (before WP_Query runs).
- Action hooks for custom rendering phases (before/after gallery HTML).

## Changelog

### 0.1.1 - Context & Fallback Update
- Added `usesContext` and `parent` to restrict block under `core/query`.
- Implemented context-aware query building (`build_query_vars_from_query_block`).
- Added fallback cascade: Meow → core gallery HTML → legacy shortcode.
- Registered `core/query` variation (gallery + no-results + pagination).
- Updated README and PLAN; refined pagination behavior inside Query Loop.

### 0.1.0 - Initial Release
- Basic block functionality.
- Post type selection.
- Query controls (perPage, order, orderBy).
- Server-side rendering with Meow Gallery integration.
- Pagination support.
- Initial fallback rendering.

## License

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
