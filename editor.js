(function () {
	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor ? wp.blockEditor.InspectorControls : wp.editor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var RangeControl = wp.components.RangeControl;
	var ToggleControl = wp.components.ToggleControl;
	var ServerSideRender = wp.serverSideRender || wp.serverSideRender; // WP provides wp.serverSideRender global

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
		edit: function (props) {
			var attrs = props.attributes;

			return el(
				wp.element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __('Query', 'ap-query-loop'), initialOpen: true },
						el(TextControl, {
							label: __('Post type', 'ap-query-loop'),
							help: __('Public post type slug (e.g., post, page, your_cpt)', 'ap-query-loop'),
							value: attrs.postType || 'post',
							onChange: function (val) { props.setAttributes({ postType: val || 'post' }); }
						}),
						el(RangeControl, {
							label: __('Items per page (editor may limit)', 'ap-query-loop'),
							min: 1,
							max: 24,
							value: attrs.perPage || 12,
							onChange: function (val) { props.setAttributes({ perPage: val }); }
						}),
						el(TextControl, {
							label: __('Order by', 'ap-query-loop'),
							help: __('e.g., date, title, menu_order, rand', 'ap-query-loop'),
							value: attrs.orderBy || 'date',
							onChange: function (val) { props.setAttributes({ orderBy: val || 'date' }); }
						}),
						el(TextControl, {
							label: __('Order', 'ap-query-loop'),
							help: __('ASC or DESC', 'ap-query-loop'),
							value: attrs.order || 'DESC',
							onChange: function (val) { props.setAttributes({ order: (val || 'DESC').toUpperCase() }); }
						}),
						el(ToggleControl, {
							label: __('Enable pagination', 'ap-query-loop'),
							checked: !!attrs.enablePagination,
							onChange: function (val) { props.setAttributes({ enablePagination: !!val }); }
						})
					)
				),
				el(ServerSideRender, {
					block: 'ap/query-loop-gallery',
					attributes: props.attributes
				})
			);
		},
		save: function () { return null; }
	});
})();
