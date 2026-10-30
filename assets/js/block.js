/**
 * Qaiyo Easy Charts — Gutenberg block editor UI (server-side rendered preview).
 * Attributes come from block.json via the server-side registration.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelColorSettings = wp.blockEditor.PanelColorSettings;
	var components = wp.components;
	var ServerSideRender = wp.serverSideRender;

	var data = window.qaiyoEcBlock || { charts: [], i18n: {}, manageUrl: '', defaults: {} };
	var t = data.i18n || {};
	var defaults = data.defaults || {};

	// Shows the chart's default colour when the attribute is empty, so every
	// block displays (and can change) its own colours.
	function colorValue( value, key ) {
		return value || defaults[ key ] || undefined;
	}

	function text( key ) {
		return t[ key ] || key;
	}

	function BarsPanel( props ) {
		var items = props.items || [];

		function update( index, key, value ) {
			var next = items.map( function ( item, i ) {
				if ( i !== index ) {
					return item;
				}
				var copy = Object.assign( {}, item );
				copy[ key ] = value;
				return copy;
			} );
			props.onChange( next );
		}

		function move( index, offset ) {
			var target = index + offset;
			if ( target < 0 || target >= items.length ) {
				return;
			}
			var next = items.slice();
			var moved = next.splice( index, 1 )[ 0 ];
			next.splice( target, 0, moved );
			props.onChange( next );
		}

		function remove( index ) {
			props.onChange( items.filter( function ( item, i ) {
				return i !== index;
			} ) );
		}

		function add() {
			props.onChange( items.concat( [ { label: '', value: '', max: '', text: '' } ] ) );
		}

		return el(
			components.PanelBody,
			{ title: text( 'bars' ), initialOpen: true },
			items.map( function ( item, index ) {
				return el(
					'div',
					{ key: index, className: 'qec-block-bar', style: { borderBottom: '1px solid #e2e8f0', paddingBottom: '12px', marginBottom: '12px' } },
					el( components.TextControl, {
						label: text( 'bar' ) + ' ' + ( index + 1 ) + ' – ' + text( 'label' ),
						value: item.label || '',
						onChange: function ( v ) { update( index, 'label', v ); }
					} ),
					el( components.Flex, { align: 'flex-start' },
						el( components.FlexBlock, null, el( components.TextControl, {
							label: text( 'value' ),
							type: 'number',
							value: item.value || '',
							onChange: function ( v ) { update( index, 'value', v ); }
						} ) ),
						el( components.FlexBlock, null, el( components.TextControl, {
							label: text( 'rangeEnd' ),
							type: 'number',
							value: item.max || '',
							onChange: function ( v ) { update( index, 'max', v ); }
						} ) )
					),
					el( components.TextControl, {
						label: text( 'valueText' ),
						value: item.text || '',
						onChange: function ( v ) { update( index, 'text', v ); }
					} ),
					el( components.ButtonGroup, null,
						el( components.Button, { icon: 'arrow-up-alt2', label: text( 'moveUp' ), disabled: 0 === index, onClick: function () { move( index, -1 ); } } ),
						el( components.Button, { icon: 'arrow-down-alt2', label: text( 'moveDown' ), disabled: index === items.length - 1, onClick: function () { move( index, 1 ); } } ),
						el( components.Button, { icon: 'trash', label: text( 'removeBar' ), isDestructive: true, onClick: function () { remove( index ); } } )
					)
				);
			} ),
			el( components.Button, { variant: 'primary', icon: 'plus-alt2', onClick: add }, text( 'addBar' ) )
		);
	}

	function Edit( props ) {
		var a = props.attributes;
		var set = props.setAttributes;
		var inline = 'saved' !== a.source;
		var blockProps = useBlockProps();

		var chartOptions = [ { value: 0, label: text( 'selectChart' ) } ].concat( data.charts || [] );

		var panels = [
			el(
				components.PanelBody,
				{ key: 'source', title: text( 'source' ), initialOpen: true },
				el( components.SelectControl, {
					label: text( 'source' ),
					value: a.source,
					options: [
						{ value: 'inline', label: text( 'inline' ) },
						{ value: 'saved', label: text( 'saved' ) }
					],
					onChange: function ( v ) { set( { source: v } ); }
				} ),
				! inline && el( components.SelectControl, {
					label: text( 'chart' ),
					value: a.chartId,
					options: chartOptions,
					onChange: function ( v ) { set( { chartId: parseInt( v, 10 ) || 0 } ); }
				} ),
				! inline && data.manageUrl && el( components.ExternalLink, { href: data.manageUrl }, text( 'manage' ) )
			)
		];

		if ( inline ) {
			panels.push(
				el( BarsPanel, { key: 'bars', items: a.items, onChange: function ( v ) { set( { items: v } ); } } ),
				el(
					components.PanelBody,
					{ key: 'layout', title: text( 'layoutPanel' ), initialOpen: false },
					el( components.SelectControl, {
						label: text( 'orientation' ),
						value: a.orientation,
						options: [
							{ value: '', label: text( 'default' ) },
							{ value: 'horizontal', label: text( 'horizontal' ) },
							{ value: 'vertical', label: text( 'vertical' ) },
							{ value: 'auto', label: text( 'auto' ) }
						],
						onChange: function ( v ) { set( { orientation: v } ); }
					} ),
					el( components.SelectControl, {
						label: text( 'labels' ),
						value: a.layout,
						options: [
							{ value: 'auto', label: text( 'layoutAuto' ) },
							{ value: 'inline', label: text( 'layoutInline' ) },
							{ value: 'stacked', label: text( 'layoutStacked' ) }
						],
						onChange: function ( v ) { set( { layout: v } ); }
					} ),
					el( components.TextControl, { label: text( 'title' ), value: a.title, onChange: function ( v ) { set( { title: v } ); } } ),
					el( components.TextControl, { label: text( 'description' ), value: a.description, onChange: function ( v ) { set( { description: v } ); } } ),
					el( components.ToggleControl, { label: text( 'showCaption' ), checked: a.showCaption, onChange: function ( v ) { set( { showCaption: v } ); } } )
				),
				el(
					components.PanelBody,
					{ key: 'scale', title: text( 'scalePanel' ), initialOpen: false },
					el( components.TextControl, { label: text( 'unit' ), value: a.unit, onChange: function ( v ) { set( { unit: v } ); } } ),
					el( components.SelectControl, {
						label: text( 'unitPosition' ),
						value: a.unitPosition,
						options: [
							{ value: 'last', label: text( 'unitLast' ) },
							{ value: 'all', label: text( 'unitAll' ) },
							{ value: 'none', label: text( 'unitNone' ) }
						],
						onChange: function ( v ) { set( { unitPosition: v } ); }
					} ),
					el( components.TextControl, { label: text( 'maximum' ), help: text( 'maximumHelp' ), type: 'number', value: a.scaleMax, onChange: function ( v ) { set( { scaleMax: v } ); } } ),
					el( components.TextControl, { label: text( 'step' ), type: 'number', value: a.step, onChange: function ( v ) { set( { step: v } ); } } ),
					el( components.ToggleControl, { label: text( 'showAxis' ), checked: a.showAxis, onChange: function ( v ) { set( { showAxis: v } ); } } ),
					el( components.ToggleControl, { label: text( 'showValues' ), checked: a.showValues, onChange: function ( v ) { set( { showValues: v } ); } } )
				)
			);
		}

		panels.push(
			el( PanelColorSettings, {
				key: 'colors',
				title: text( 'colors' ),
				initialOpen: false,
				colorSettings: [
					{ label: text( 'barColor' ), value: colorValue( a.barColor, 'bar_color' ), onChange: function ( v ) { set( { barColor: v || '' } ); } },
					{ label: text( 'rangeColor' ), value: colorValue( a.rangeColor, 'range_color' ), onChange: function ( v ) { set( { rangeColor: v || '' } ); } },
					{ label: text( 'trackColor' ), value: colorValue( a.trackColor, 'track_color' ), onChange: function ( v ) { set( { trackColor: v || '' } ); } },
					{ label: text( 'labelColor' ), value: colorValue( a.labelColor, 'label_color' ), onChange: function ( v ) { set( { labelColor: v || '' } ); } },
					{ label: text( 'valueColor' ), value: colorValue( a.valueColor, 'value_color' ), onChange: function ( v ) { set( { valueColor: v || '' } ); } },
					{ label: text( 'axisColor' ), value: colorValue( a.axisColor, 'axis_color' ), onChange: function ( v ) { set( { axisColor: v || '' } ); } }
				]
			} ),
			el(
				components.PanelBody,
				{ key: 'size', title: text( 'size' ), initialOpen: false },
				el( components.TextControl, { label: text( 'barSize' ), placeholder: '28px', value: a.barSize, onChange: function ( v ) { set( { barSize: v } ); } } ),
				el( components.TextControl, { label: text( 'chartHeight' ), placeholder: '280px', value: a.chartHeight, onChange: function ( v ) { set( { chartHeight: v } ); } } ),
				el( components.TextControl, { label: text( 'radius' ), placeholder: '0px', value: a.radius, onChange: function ( v ) { set( { radius: v } ); } } )
			)
		);

		if ( inline ) {
			panels.push(
				el(
					components.PanelBody,
					{ key: 'animation', title: text( 'animation' ), initialOpen: false },
					el( components.ToggleControl, { label: text( 'animate' ), checked: a.animate, onChange: function ( v ) { set( { animate: v } ); } } ),
					a.animate && el( components.SelectControl, {
						label: text( 'effect' ),
						value: a.animation,
						options: [
							{ value: 'grow', label: text( 'grow' ) },
							{ value: 'fade', label: text( 'fade' ) },
							{ value: 'rise', label: text( 'rise' ) }
						],
						onChange: function ( v ) { set( { animation: v } ); }
					} ),
					a.animate && el( components.SelectControl, {
						label: text( 'easing' ),
						value: a.easing,
						options: [
							{ value: 'smooth', label: text( 'smooth' ) },
							{ value: 'linear', label: text( 'linear' ) },
							{ value: 'spring', label: text( 'spring' ) }
						],
						onChange: function ( v ) { set( { easing: v } ); }
					} ),
					a.animate && el( components.TextControl, { label: text( 'duration' ), type: 'number', placeholder: '1100', value: a.duration, onChange: function ( v ) { set( { duration: v } ); } } ),
					a.animate && el( components.TextControl, { label: text( 'stagger' ), type: 'number', placeholder: '140', value: a.stagger, onChange: function ( v ) { set( { stagger: v } ); } } ),
					a.animate && el( components.ToggleControl, { label: text( 'countUp' ), checked: a.countUp, onChange: function ( v ) { set( { countUp: v } ); } } ),
					a.animate && el( components.ToggleControl, { label: text( 'replay' ), checked: a.replay, onChange: function ( v ) { set( { replay: v } ); } } )
				),
				el(
					components.PanelBody,
					{ key: 'behaviour', title: text( 'behaviour' ), initialOpen: false },
					el( components.ToggleControl, { label: text( 'lazy' ), checked: a.lazy, onChange: function ( v ) { set( { lazy: v } ); } } ),
					el( components.ToggleControl, { label: text( 'hoverFocus' ), checked: a.hoverFocus, onChange: function ( v ) { set( { hoverFocus: v } ); } } )
				)
			);
		}

		var preview = ( ! inline && ! a.chartId )
			? el( components.Placeholder, { icon: 'chart-bar', label: 'Qaiyo Easy Chart', instructions: text( 'noChart' ) } )
			: el( ServerSideRender, { block: 'qaiyo-easy-charts/chart', attributes: a } );

		return el(
			Fragment,
			null,
			el( InspectorControls, null, panels ),
			el( 'div', blockProps, preview )
		);
	}

	wp.blocks.registerBlockType( 'qaiyo-easy-charts/chart', {
		edit: Edit,
		save: function () {
			return null;
		}
	} );
} )( window.wp );
