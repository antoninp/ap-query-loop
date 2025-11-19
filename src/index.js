import { registerBlockType, registerBlockVariation } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import { PanelBody, TextControl, SelectControl, Spinner, ToggleControl } from '@wordpress/components';
import { 
  InspectorControls, 
  BlockControls,
  AlignmentControl,
  InnerBlocks, 
  useBlockProps,
  __experimentalUseBorderProps as useBorderProps,
  __experimentalUseColorProps as useColorProps,
  __experimentalGetSpacingClassesAndStyles as useSpacingProps
} from '@wordpress/block-editor';
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
      inherit: true
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
  description: __('Group current Query posts by a taxonomy or meta field. Use InnerBlocks to compose your layout per term/value.', 'apql-gallery'),
  icon: 'filter',
  category: 'theme',
  attributes: {
    groupBy: { type: 'string', default: 'taxonomy' },
    taxonomy: { type: 'string', default: '' },
    metaKey: { type: 'string', default: '' },
    metaType: { type: 'string', default: 'string' },
    dateFormat: { type: 'string', default: 'F j, Y' },
    termOrderBy: { type: 'string', default: 'name' },
    termOrder: { type: 'string', default: 'desc' },
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
            <SelectControl
              label={ __('Group By', 'apql-gallery') }
              value={ attributes.groupBy || 'taxonomy' }
              options={ [
                { label: __('Taxonomy', 'apql-gallery'), value: 'taxonomy' },
                { label: __('Meta Field', 'apql-gallery'), value: 'meta' },
              ] }
              onChange={ ( value ) => setAttributes( { groupBy: value } ) }
            />
            
            { attributes.groupBy === 'taxonomy' && (
              <>
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
              </>
            ) }
            
            { attributes.groupBy === 'meta' && (
              <>
                <TextControl
                  label={ __('Meta Key', 'apql-gallery') }
                  help={ __('The post meta key to group by (e.g., aplb_published_date)', 'apql-gallery') }
                  value={ attributes.metaKey || '' }
                  onChange={ (value) => setAttributes({ metaKey: value }) }
                />
                <SelectControl
                  label={ __('Meta Type', 'apql-gallery') }
                  value={ attributes.metaType || 'string' }
                  options={ [
                    { label: __('String', 'apql-gallery'), value: 'string' },
                    { label: __('Date', 'apql-gallery'), value: 'date' },
                    { label: __('Number', 'apql-gallery'), value: 'number' },
                  ] }
                  onChange={ ( value ) => setAttributes( { metaType: value } ) }
                />
                { attributes.metaType === 'date' && (
                  <TextControl
                    label={ __('Date Format', 'apql-gallery') }
                    help={ __('PHP date format (e.g., F j, Y for "November 19, 2025")', 'apql-gallery') }
                    value={ attributes.dateFormat || 'F j, Y' }
                    onChange={ (value) => setAttributes({ dateFormat: value }) }
                  />
                ) }
              </>
            ) }
            
            <SelectControl
              label={ __('Order By', 'apql-gallery') }
              value={ attributes.termOrderBy || 'name' }
              options={ [
                { label: __('Value (A → Z or date)', 'apql-gallery'), value: 'name' },
                { label: __('Slug (A → Z)', 'apql-gallery'), value: 'slug' },
                { label: __('ID (numeric)', 'apql-gallery'), value: 'id' },
                { label: __('Post Count', 'apql-gallery'), value: 'count' },
              ] }
              onChange={ ( value ) => setAttributes( { termOrderBy: value } ) }
            />
            <SelectControl
              label={ __('Order Direction', 'apql-gallery') }
              value={ attributes.termOrder || 'desc' }
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
              🔗 { __('APQL Filter', 'apql-gallery') } 
              { attributes.groupBy === 'taxonomy' ? ` (${attributes.taxonomy})` : ` (${attributes.metaKey})` }
            </p>
            <p style={{ margin: '0.5rem 0 1rem', fontSize: '0.85rem', color: '#666' }}>
              { __('Add blocks below (e.g., APQL Term Name, APQL Gallery) to compose the layout for each group.', 'apql-gallery') }
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
    textAlign: { type: 'string' },
    prefix: { type: 'string', default: '' },
    suffix: { type: 'string', default: '' },
    isLink: { type: 'boolean', default: false }
  },
  edit: ({ attributes, setAttributes }) => {
    const { textAlign, prefix = '', suffix = '', isLink = false } = attributes;
    
    const blockProps = useBlockProps({
      className: textAlign ? `has-text-align-${textAlign}` : undefined,
    });
    
    return (
      <>
        <BlockControls group="block">
          <AlignmentControl
            value={ textAlign }
            onChange={ (newAlign) => setAttributes({ textAlign: newAlign }) }
          />
        </BlockControls>
        <InspectorControls>
          <PanelBody title={ __('Settings', 'apql-gallery') } initialOpen={ true }>
            <ToggleControl
              label={ __('Make term a link', 'apql-gallery') }
              checked={ isLink }
              onChange={ () => setAttributes({ isLink: !isLink }) }
              help={ __('Links to the term archive page.', 'apql-gallery') }
            />
            <TextControl
              label={ __('Prefix', 'apql-gallery') }
              help={ __('Text or space to prepend to term.', 'apql-gallery') }
              value={ prefix }
              onChange={ (value) => setAttributes({ prefix: value }) }
            />
            <TextControl
              label={ __('Suffix', 'apql-gallery') }
              help={ __('Text or space to append to term.', 'apql-gallery') }
              value={ suffix }
              onChange={ (value) => setAttributes({ suffix: value }) }
            />
          </PanelBody>
        </InspectorControls>
        <div { ...blockProps }>
          <span style={{ padding: '0.25rem 0.5rem', background: '#e0f2fe', border: '1px solid #0ea5e9', borderRadius: '4px', display: 'inline-block' }}>
            <strong style={{ color: '#0369a1' }}>
              📌 { __('Term Name', 'apql-gallery') }
            </strong>
            {(prefix || suffix || isLink) && (
              <span style={{ marginLeft: '0.5rem', fontSize: '0.85rem', color: '#666' }}>
                {prefix && <span>[{prefix}]</span>}
                {isLink && <span>🔗</span>}
                {suffix && <span>[{suffix}]</span>}
              </span>
            )}
          </span>
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
                        order: currentOrder === 'asc' ? 'desc' : 'desc'
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
