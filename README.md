# AP Query Loop Gallery

A WordPress Gutenberg block that displays posts from a selected post type as a gallery using featured images. Integrates with Meow Gallery when available.

## Description

This block extends the functionality of WordPress's core Query Loop by rendering queried posts as an image gallery. Each post's featured image is displayed in the gallery, with full integration with Meow Gallery Pro when installed.

## Features

- **Post Type Selection**: Choose any public post type via dropdown
- **Query Controls**: Configure items per page, ordering, and pagination
- **Server-Side Rendering**: Preview actual gallery output in the editor
- **Meow Gallery Integration**: Automatically uses Meow Gallery shortcode when available
- **Graceful Fallbacks**: Works without Meow Gallery (uses standard WordPress gallery)
- **Performance**: Efficient queries, only includes posts with featured images

## Requirements

- WordPress 6.5 or higher
- PHP 7.4 or higher
- Node.js and npm (for development only)
- Meow Gallery Pro (optional, but recommended for enhanced gallery features)

## Installation

1. Upload the `ap-query-loop` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. (Optional) Install and activate Meow Gallery Pro for enhanced gallery features

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

1. **Editor**: Uses `ServerSideRender` to display actual gallery output while editing
2. **Query**: Builds WP_Query from block attributes
3. **Filter**: Only includes posts with featured images
4. **Render**: 
   - If Meow Gallery active: outputs Meow Gallery shortcode
   - Fallback: uses standard WordPress gallery shortcode
   - Final fallback: simple responsive grid
5. **Pagination**: Optional pagination links when enabled

## Filters & Hooks

Currently no custom filters or hooks exposed. Future versions may add:
- Filter for gallery shortcode selection
- Filter for query args modification
- Action hooks for custom rendering

## Changelog

### 0.1.0 - Initial Release
- Basic block functionality
- Post type selection
- Query controls (perPage, order, orderBy)
- Server-side rendering with Meow Gallery integration
- Pagination support
- Fallback rendering

## License

GPL-2.0-or-later

## Support

For issues, feature requests, or contributions, please open an issue on the GitHub repository.

## Credits

Developed by Antonin Puleo

## Download

Download ready-to-install ZIPs from the GitHub Releases page:

- https://github.com/antoninp/ap-query-loop/releases

In WordPress, go to Plugins > Add New > Upload Plugin and upload `ap-query-loop.zip`.
