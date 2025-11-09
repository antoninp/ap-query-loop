import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, RangeControl, ToggleControl, SelectControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useState, useEffect } from '@wordpress/element';

// Import styles so they get built
import './style.scss';

registerBlockType('ap/query-loop-gallery', {
	title: __('AP Query Loop Gallery', 'ap-query-loop'),
	description: __('Display posts as a gallery using featured images. Uses server-side rendering.', 'ap-query-loop'),
	icon: 'images-alt2',
	category: 'theme',
	attributes: {
		postType: { type: 'string', default: 'post' },
		perPage: { type: 'number', default: 12 },
		order: { type: 'string', default: 'DESC' },
		orderBy: { type: 'string', default: 'date' },
		enablePagination: { type: 'boolean', default: false }
	},
	edit: ( props ) => {
		const { attributes, setAttributes } = props;
		const { postType, perPage, order, orderBy, enablePagination } = attributes;

		// Discover public post types via REST for better UX
		const [ postTypes, setPostTypes ] = useState( [ { label: __('Post', 'ap-query-loop'), value: 'post' } ] );
		const [ loadingTypes, setLoadingTypes ] = useState( true );

		useEffect( () => {
			let mounted = true;
			fetch( wp.url.addQueryArgs( '/wp/v2/types', { context: 'edit' } ) )
				.then( r => r.json() )
				.then( data => {
					if ( ! mounted || ! data ) return;
					const choices = Object.keys( data )
						.filter( key => data[ key ]?.viewable )
						.map( key => ( { label: data[ key ].name || key, value: key } ) );
					if ( choices.length ) setPostTypes( choices );
				} )
				.catch( () => {} )
				.finally( () => mounted && setLoadingTypes( false ) );
			return () => { mounted = false; };
		}, [] );

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __('Query', 'ap-query-loop') } initialOpen={ true }>
						<SelectControl
							label={ __('Post type', 'ap-query-loop') }
							help={ loadingTypes ? __('Loading post types…', 'ap-query-loop') : __('Select a public post type', 'ap-query-loop') }
							value={ postType }
							options={ postTypes }
							onChange={ ( val ) => setAttributes( { postType: val || 'post' } ) }
						/>
						<RangeControl
							label={ __('Items per page (editor capped)', 'ap-query-loop') }
							min={ 1 }
							max={ 24 }
							value={ perPage }
							onChange={ ( val ) => setAttributes( { perPage: val } ) }
						/>
						<TextControl
							label={ __('Order by', 'ap-query-loop') }
							help={ __('date, title, menu_order, rand', 'ap-query-loop') }
							value={ orderBy }
							onChange={ ( val ) => setAttributes( { orderBy: val || 'date' } ) }
						/>
						<TextControl
							label={ __('Order', 'ap-query-loop') }
							help={ __('ASC or DESC', 'ap-query-loop') }
							value={ order }
							onChange={ ( val ) => setAttributes( { order: ( val || 'DESC' ).toUpperCase() } ) }
						/>
						<ToggleControl
							label={ __('Enable pagination', 'ap-query-loop') }
							checked={ !! enablePagination }
							onChange={ ( val ) => setAttributes( { enablePagination: !! val } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<ServerSideRender
					block="ap/query-loop-gallery"
					attributes={ attributes }
				/>
			</>
		);
	},
	save: () => null
});
