import { registerBlockType, registerBlockVariation } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

// Import styles so they get built
import './style.scss';

registerBlockType('ap/query-loop-gallery', {
  title: __('AP Query Loop Gallery', 'ap-query-loop'),
  description: __('Displays the current Query Loop posts as a gallery of featured images.', 'ap-query-loop'),
  icon: 'images-alt2',
  category: 'theme',
  edit: () => (
    <ServerSideRender
      block="ap/query-loop-gallery"
      attributes={{}}
    />
  ),
  save: () => null
});

// Provide a convenient Query variation that composes our gallery with no-results and pagination
registerBlockVariation('core/query', {
	name: 'ap-query-gallery-variation',
	title: __('Query: Gallery (AP)', 'ap-query-loop'),
	description: __('Render the current query as a gallery of featured images, with no-results and pagination blocks.', 'ap-query-loop'),
	icon: 'images-alt2',
	scope: [ 'inserter' ],
	innerBlocks: [
		[ 'ap/query-loop-gallery' ],
		[ 'core/query-no-results' ],
		[ 'core/query-pagination' ]
	]
});
