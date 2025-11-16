import { registerBlockType, registerBlockVariation } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import { PanelBody, TextControl } from '@wordpress/components';
import { InspectorControls, InnerBlocks, useBlockProps } from '@wordpress/block-editor';

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

// Register parent block: AP Group by Taxonomy (with InnerBlocks)
registerBlockType('ap/group-by-tax', {
  title: __('AP Group by Taxonomy', 'ap-query-loop'),
  description: __('Group current Query posts by a taxonomy. Use InnerBlocks to compose your layout per term.', 'ap-query-loop'),
  icon: 'filter',
  category: 'theme',
  attributes: {
    taxonomy: { type: 'string', default: 'aplb_library_pdate' },
  },
  edit: ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps();
    return (
      <>
        <InspectorControls>
          <PanelBody title={ __('Grouping', 'ap-query-loop') } initialOpen={ true }>
            <TextControl
              label={ __('Taxonomy slug', 'ap-query-loop') }
              help={ __('Enter the taxonomy to group by.', 'ap-query-loop') }
              value={ attributes.taxonomy || '' }
              onChange={ (value) => setAttributes({ taxonomy: value }) }
            />
          </PanelBody>
        </InspectorControls>
        <div { ...blockProps }>
          <div style={{ padding: '1rem', border: '2px dashed #ccc', background: '#f9f9f9' }}>
            <p style={{ margin: 0, fontWeight: 600 }}>{ __('AP Group by Taxonomy', 'ap-query-loop') }</p>
            <p style={{ margin: '0.5rem 0 1rem', fontSize: '0.85rem', color: '#666' }}>
              { __('Add blocks below (e.g., Heading, AP Query Loop Gallery) to compose the layout for each term group.', 'ap-query-loop') }
            </p>
            <InnerBlocks
              template={[
                ['core/heading', { level: 3, placeholder: __('Term Name (use Term Info block)', 'ap-query-loop') }],
                ['ap/query-loop-gallery']
              ]}
            />
          </div>
        </div>
      </>
    );
  },
  save: () => <InnerBlocks.Content />
});

// Register Term Info block (displays current term name)
registerBlockType('ap/term-info', {
  title: __('Term Info', 'ap-query-loop'),
  description: __('Display the current taxonomy term name. Use inside AP Group by Taxonomy.', 'ap-query-loop'),
  icon: 'tag',
  category: 'theme',
  edit: () => (
    <div { ...useBlockProps() }>
      <p style={{ margin: 0, padding: '0.5rem', background: '#f0f0f0', border: '1px solid #ddd', borderRadius: '2px' }}>
        <strong>{ __('Term Name', 'ap-query-loop') }</strong> { __('(will display current term)', 'ap-query-loop') }
      </p>
    </div>
  ),
  save: () => null
});
