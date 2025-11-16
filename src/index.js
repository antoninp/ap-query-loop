import { registerBlockType, registerBlockVariation } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';

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

// Register parent block: AP Group by Taxonomy (server-rendered)
registerBlockType('ap/group-by-tax', {
  title: __('AP Group by Taxonomy', 'ap-query-loop'),
  description: __('Group current Query posts by a taxonomy and render a gallery per term.', 'ap-query-loop'),
  icon: 'filter',
  category: 'theme',
  attributes: {
    taxonomy: { type: 'string', default: 'aplb_library_pdate' },
    showHeading: { type: 'boolean', default: true },
  },
  edit: ({ attributes, setAttributes }) => (
    <>
      <InspectorControls>
        <PanelBody title={ __('Grouping', 'ap-query-loop') } initialOpen={ true }>
          <TextControl
            label={ __('Taxonomy slug', 'ap-query-loop') }
            help={ __('Enter the taxonomy to group by (e.g. aplb_pdate).', 'ap-query-loop') }
            value={ attributes.taxonomy || '' }
            onChange={ (value) => setAttributes({ taxonomy: value }) }
          />
          <ToggleControl
            label={ __('Show heading per term', 'ap-query-loop') }
            checked={ !!attributes.showHeading }
            onChange={ (value) => setAttributes({ showHeading: !!value }) }
          />
        </PanelBody>
      </InspectorControls>
      <ServerSideRender
        block="ap/group-by-tax"
        attributes={ attributes }
      />
    </>
  ),
  save: () => null
});
