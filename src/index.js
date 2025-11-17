import { registerBlockType, registerBlockVariation } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import { PanelBody, TextControl, SelectControl, Spinner, ToggleControl } from '@wordpress/components';
import { InspectorControls, InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';

// Import styles so they get built
import './style.scss';

registerBlockType('apql/gallery', {
  title: __('APQL Gallery', 'apql-gallery'),
  description: __('Displays the current Query Loop posts as a gallery of featured images.', 'ap-query-loop'),
  icon: 'images-alt2',
  category: 'theme',
  edit: () => (
    <ServerSideRender
      block="apql/gallery"
      attributes={{}}
    />
  ),
  save: () => null
});

// Provide a convenient Query variation that composes our gallery with no-results and pagination
registerBlockVariation('core/query', {
  name: 'apql-filter-gallery',
  title: __('Query: APQL Filter + Gallery', 'apql-gallery'),
  description: __('Query with APQL Filter, APQL Term Name, APQL Gallery, No Results, and Pagination.', 'apql-gallery'),
  icon: 'images-alt2',
  scope: [ 'inserter' ],
  attributes: {
    namespace: 'apql-gallery',
    query: {
      perPage: 10,
      pages: 0,
      offset: 0,
      postType: 'post',
      order: 'desc',
      orderBy: 'date',
      author: '',
      search: '',
      exclude: [],
      sticky: '',
      inherit: false
    }
  },
  innerBlocks: [
    [ 'apql/filter', { taxonomy: '' }, [
        [ 'apql/term-name', { tagName: 'h3' } ],
        [ 'apql/gallery' ]
      ]],
    [ 'core/query-no-results' ],
    [ 'core/query-pagination' ]
  ]
});

// Register parent block: APQL Filter (with InnerBlocks)
registerBlockType('apql/filter', {
  title: __('APQL Filter', 'apql-gallery'),
  description: __('Group current Query posts by a taxonomy. Use InnerBlocks to compose your layout per term.', 'apql-gallery'),
  icon: 'filter',
  category: 'theme',
  attributes: {
    taxonomy: { type: 'string', default: '' },
    termOrderBy: { type: 'string', default: 'name' },
    termOrder: { type: 'string', default: 'asc' },
  },
  edit: ({ attributes, setAttributes, clientId }) => {
    const blockProps = useBlockProps();
    // Load all registered taxonomies to populate a dropdown
    const taxonomies = useSelect( ( select ) => {
      const core = select( 'core' );
      if ( core && core.getTaxonomies ) {
        return core.getTaxonomies( { per_page: -1, context: 'view' } );
      }
      return null;
    }, [] );
    const taxonomyOptions = Array.isArray( taxonomies )
      ? [
          { label: __('Select a taxonomy', 'apql-gallery'), value: '' },
          ...taxonomies.map( ( t ) => ({
            label: t?.name || t?.slug,
            value: t?.slug,
          }) ),
        ]
      : null;
    const TEMPLATE = [
      ['apql/term-name', { tagName: 'h3' }],
      ['apql/gallery']
    ];
    
    return (
      <>
        <InspectorControls>
          <PanelBody title={ __('Grouping', 'apql-gallery') } initialOpen={ true }>
            { taxonomyOptions ? (
              <SelectControl
                label={ __('Taxonomy', 'apql-gallery') }
                value={ attributes.taxonomy || '' }
                options={ taxonomyOptions }
                onChange={ ( value ) => setAttributes( { taxonomy: value } ) }
              />
            ) : (
              <div style={{ display: 'flex', alignItems: 'center', gap: '0.5rem' }}>
                <Spinner />
                <span>{ __('Loading taxonomies…', 'apql-gallery') }</span>
              </div>
            ) }
            <TextControl
              label={ __('Custom taxonomy (slug)', 'apql-gallery') }
              help={ __('Optional: override or type a custom taxonomy slug.', 'apql-gallery') }
              value={ attributes.taxonomy || '' }
              onChange={ (value) => setAttributes({ taxonomy: value }) }
            />
            <SelectControl
              label={ __('Term Order By', 'apql-gallery') }
              value={ attributes.termOrderBy || 'name' }
              options={ [
                { label: __('Name (A → Z)', 'apql-gallery'), value: 'name' },
                { label: __('Slug (A → Z)', 'apql-gallery'), value: 'slug' },
                { label: __('ID (numeric)', 'apql-gallery'), value: 'id' },
                { label: __('Post Count', 'apql-gallery'), value: 'count' },
                { label: __('Date from Name', 'apql-gallery'), value: 'date_name' },
              ] }
              onChange={ ( value ) => setAttributes( { termOrderBy: value } ) }
            />
            <SelectControl
              label={ __('Term Order Direction', 'apql-gallery') }
              value={ attributes.termOrder || 'asc' }
              options={ [
                { label: __('Ascending', 'apql-gallery'), value: 'asc' },
                { label: __('Descending', 'apql-gallery'), value: 'desc' },
              ] }
              onChange={ ( value ) => setAttributes( { termOrder: value } ) }
            />
          </PanelBody>
        </InspectorControls>
        <div { ...blockProps }>
          <div style={{ padding: '1rem', border: '2px dashed #8b5cf6', background: '#faf5ff' }}>
            <p style={{ margin: 0, fontWeight: 600, color: '#7c3aed' }}>
              🔗 { __('APQL Filter', 'apql-gallery') } ({ attributes.taxonomy })
            </p>
            <p style={{ margin: '0.5rem 0 1rem', fontSize: '0.85rem', color: '#666' }}>
              { __('Add blocks below (e.g., APQL Term Name, APQL Gallery) to compose the layout for each term group.', 'apql-gallery') }
            </p>
            <InnerBlocks
              template={ TEMPLATE }
              templateLock={ false }
            />
          </div>
        </div>
      </>
    );
  },
  // Important: serialize inner blocks so we can access parsed_block['innerBlocks'] server-side
  save: () => <InnerBlocks.Content />
});

// Register APQL Term Name block (displays current term name)
registerBlockType('apql/term-name', {
  title: __('APQL Term Name', 'apql-gallery'),
  description: __('Display the current taxonomy term name. Use inside APQL Filter.', 'apql-gallery'),
  icon: 'tag',
  category: 'theme',
  attributes: {
    tagName: { type: 'string', default: 'h3' }
  },
  edit: ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps();
    return (
      <>
        <InspectorControls>
          <PanelBody title={ __('Settings', 'apql-gallery') } initialOpen={ true }>
            <TextControl
              label={ __('HTML Tag', 'apql-gallery') }
              help={ __('e.g., h2, h3, p, div', 'apql-gallery') }
              value={ attributes.tagName || 'h3' }
              onChange={ (value) => setAttributes({ tagName: value }) }
            />
          </PanelBody>
        </InspectorControls>
        <div { ...blockProps }>
          <div style={{ margin: 0, padding: '0.75rem', background: '#e0f2fe', border: '1px solid #0ea5e9', borderRadius: '4px' }}>
            <strong style={{ color: '#0369a1' }}>
              📌 { __('Term Name', 'apql-gallery') }
            </strong>
            <span style={{ marginLeft: '0.5rem', fontSize: '0.85rem', color: '#666' }}>
              ({ __('renders as', 'apql-gallery') } &lt;{ attributes.tagName || 'h3' }&gt;)
            </span>
          </div>
        </div>
      </>
    );
  },
  save: () => null
});

// Add custom order controls to Query blocks with our variation
addFilter(
  'editor.BlockEdit',
  'apql/query-order-controls',
  (BlockEdit) => {
    return (props) => {
      const { name, attributes, setAttributes } = props;
      
      // Only add controls to core/query blocks
      if (name !== 'core/query') {
        return <BlockEdit {...props} />;
      }
      
      // Only show controls if the query has our namespace
      const hasAPQLNamespace = attributes?.namespace === 'apql-gallery';
      
      const orderByOptions = [
        { label: __('Date', 'apql-gallery'), value: 'date' },
        { label: __('Title', 'apql-gallery'), value: 'title' },
        { label: __('Author', 'apql-gallery'), value: 'author' },
        { label: __('Modified Date', 'apql-gallery'), value: 'modified' },
        { label: __('Menu Order', 'apql-gallery'), value: 'menu_order' },
        { label: __('Random', 'apql-gallery'), value: 'rand' },
        { label: __('Comment Count', 'apql-gallery'), value: 'comment_count' },
        { label: __('Post ID', 'apql-gallery'), value: 'id' },
      ];
      
      return (
        <>
          <BlockEdit {...props} />
          {hasAPQLNamespace && (
            <InspectorControls>
              <PanelBody title={__('Post Order', 'apql-gallery')} initialOpen={true}>
                <SelectControl
                  label={__('Order By', 'apql-gallery')}
                  value={attributes?.query?.orderBy || 'date'}
                  options={orderByOptions}
                  onChange={(value) => {
                    setAttributes({
                      query: {
                        ...attributes.query,
                        orderBy: value
                      }
                    });
                  }}
                />
                <ToggleControl
                  label={__('Ascending Order', 'apql-gallery')}
                  checked={attributes?.query?.order === 'asc'}
                  onChange={() => {
                    const currentOrder = attributes?.query?.order || 'desc';
                    setAttributes({
                      query: {
                        ...attributes.query,
                        order: currentOrder === 'asc' ? 'desc' : 'asc'
                      }
                    });
                  }}
                />
              </PanelBody>
            </InspectorControls>
          )}
        </>
      );
    };
  }
);
