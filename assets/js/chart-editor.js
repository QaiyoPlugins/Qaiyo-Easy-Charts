/**
 * Qaiyo Easy Charts — chart edit screen: chart type, rows, spreadsheet paste,
 * colour pickers, style copy, live preview and export.
 */
( function ( $ ) {
	'use strict';

	var config = window.qaiyoEcEditor || {};
	var i18n = config.i18n || {};
	var builder = document.querySelector( '.qec-builder' );

	if ( ! builder ) {
		return;
	}

	var rows = document.getElementById( 'qec-rows' );
	var template = document.getElementById( 'qec-row-template' );
	var preview = document.getElementById( 'qec-preview' );
	var counter = rows ? rows.children.length : 0;
	var timer = null;
	var request = null;

	function speak( message ) {
		if ( message && window.wp && wp.a11y && wp.a11y.speak ) {
			wp.a11y.speak( message );
		}
	}

	/* Colour pickers ------------------------------------------------------ */

	function initColors( context ) {
		$( context ).find( 'input.qec-color' ).each( function () {
			if ( $( this ).data( 'qecColor' ) ) {
				return;
			}
			$( this ).data( 'qecColor', true ).wpColorPicker( {
				change: schedulePreview,
				clear: schedulePreview
			} );
		} );
	}

	/* Rows ---------------------------------------------------------------- */

	function createRow() {
		var html = template.innerHTML.replace( /__i__/g, 'n' + ( counter++ ) );
		var tbody = document.createElement( 'tbody' );
		tbody.innerHTML = html.trim();
		var row = tbody.firstElementChild;
		rows.appendChild( row );
		initColors( row );
		return row;
	}

	function addRow() {
		var row = createRow();
		var first = row.querySelector( 'input' );
		if ( first ) {
			first.focus();
		}
		speak( i18n.rowAdded );
		schedulePreview();
	}

	function onRowClick( event ) {
		var button = event.target.closest( 'button' );
		if ( ! button || ! rows.contains( button ) ) {
			return;
		}
		var row = button.closest( 'tr' );

		if ( button.classList.contains( 'qec-remove' ) ) {
			var next = row.nextElementSibling || row.previousElementSibling;
			row.remove();
			speak( i18n.rowRemoved );
			if ( next ) {
				var focusTarget = next.querySelector( '.qec-remove' );
				if ( focusTarget ) {
					focusTarget.focus();
				}
			} else {
				builder.querySelector( '.qec-add-row' ).focus();
			}
			schedulePreview();
			return;
		}

		var direction = button.getAttribute( 'data-qec-move' );
		if ( 'up' === direction && row.previousElementSibling ) {
			rows.insertBefore( row, row.previousElementSibling );
		} else if ( 'down' === direction && row.nextElementSibling ) {
			rows.insertBefore( row.nextElementSibling, row );
		} else {
			return;
		}
		button.focus();
		speak( i18n.rowMoved );
		schedulePreview();
	}

	/* Preview ------------------------------------------------------------- */

	function schedulePreview() {
		// The colour picker updates its field after its change event.
		window.setTimeout( checkContrast, 0 );
		window.clearTimeout( timer );
		timer = window.setTimeout( refreshPreview, 350 );
	}

	function refreshPreview( play ) {
		var data = new window.FormData();
		if ( true === play ) {
			data.append( 'play', '1' );
		}
		data.append( 'action', config.action );
		data.append( 'nonce', config.nonce );
		data.append( 'post_id', config.postId );

		var title = document.getElementById( 'title' );
		if ( title ) {
			data.append( 'post_title', title.value );
		}

		builder.querySelectorAll( '[name^="' + config.field + '["]' ).forEach( function ( field ) {
			if ( field.closest( 'template' ) ) {
				return;
			}
			if ( ( 'checkbox' === field.type || 'radio' === field.type ) && ! field.checked ) {
				return;
			}
			data.append( field.name, field.value );
		} );

		if ( request ) {
			request.abort();
		}
		request = new window.AbortController();

		preview.setAttribute( 'aria-busy', 'true' );
		window.fetch( config.ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin', signal: request.signal } )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( json ) {
				if ( json && json.success && json.data ) {
					preview.innerHTML = json.data.html;
					if ( window.qaiyoEasyChartsInit ) {
						window.qaiyoEasyChartsInit();
					}
				} else {
					speak( i18n.previewFail );
				}
			} )
			.catch( function ( error ) {
				if ( ! error || 'AbortError' !== error.name ) {
					speak( i18n.previewFail );
				}
			} )
			.finally( function () {
				preview.removeAttribute( 'aria-busy' );
			} );
	}

	/* Play animation ------------------------------------------------------ */

	function onPlay( event ) {
		if ( ! event.target.closest( '.qec-play' ) ) {
			return;
		}
		var toggle = builder.querySelector( 'input[name="' + config.field + '[animate]"]' );
		if ( toggle && ! toggle.checked ) {
			speak( i18n.noAnimation );
			return;
		}
		window.clearTimeout( timer );
		refreshPreview( true );
	}

	/* Device toggle ------------------------------------------------------- */

	function onDevice( event ) {
		var button = event.target.closest( '[data-qec-device]' );
		if ( ! button ) {
			return;
		}
		builder.querySelectorAll( '[data-qec-device]' ).forEach( function ( other ) {
			other.setAttribute( 'aria-pressed', other === button ? 'true' : 'false' );
		} );
		preview.setAttribute( 'data-device', button.getAttribute( 'data-qec-device' ) );
	}

	/* Copy shortcode ------------------------------------------------------ */

	function onCopy( event ) {
		var button = event.target.closest( '[data-qec-copy]' );
		if ( ! button ) {
			return;
		}
		var input = document.querySelector( button.getAttribute( 'data-qec-copy' ) );
		if ( ! input ) {
			return;
		}
		input.select();
		var done = function () {
			speak( i18n.copied );
		};
		if ( navigator.clipboard && window.isSecureContext ) {
			navigator.clipboard.writeText( input.value ).then( done );
		} else {
			document.execCommand( 'copy' );
			done();
		}
	}

	/* Chart type --------------------------------------------------------- */

	function fieldName( key ) {
		return config.field + '[' + key + ']';
	}

	// Column headings, field labels and visible settings follow the chart type.
	function applyType( announce ) {
		var checked = builder.querySelector( '.qec-type-radio:checked' );
		var type = checked ? checked.value : 'bars';
		var labels = ( config.types || {} )[ type ];
		builder.setAttribute( 'data-qec-type', type );

		if ( labels ) {
			var help = document.getElementById( 'qec-type-help' );
			if ( help ) {
				help.textContent = labels.help;
			}
			[ [ 'value', labels.value ], [ 'second', labels.second ] ].forEach( function ( pair ) {
				builder.querySelectorAll( 'th.qec-col-' + pair[ 0 ] ).forEach( function ( cell ) {
					cell.textContent = pair[ 1 ];
				} );
				var cells = Array.prototype.slice.call( builder.querySelectorAll( 'td.qec-col-' + pair[ 0 ] ) );
				if ( template ) {
					cells = cells.concat( Array.prototype.slice.call( template.content.querySelectorAll( 'td.qec-col-' + pair[ 0 ] ) ) );
				}
				cells.forEach( function ( cell ) {
					cell.setAttribute( 'data-label', pair[ 1 ] );
					var input = cell.querySelector( 'input' );
					if ( input ) {
						input.setAttribute( 'aria-label', pair[ 1 ] );
					}
				} );
			} );
		}

		builder.querySelectorAll( '[data-qec-types]' ).forEach( function ( group ) {
			group.hidden = -1 === group.getAttribute( 'data-qec-types' ).split( ' ' ).indexOf( type );
		} );

		if ( announce ) {
			speak( i18n.typeChanged );
		}
	}

	/* Spreadsheet / CSV paste ----------------------------------------------- */

	function parseNumber( text ) {
		var clean = String( text || '' ).replace( /[\s\u00a0\u202f]/g, '' );
		if ( -1 !== clean.indexOf( ',' ) && -1 === clean.indexOf( '.' ) ) {
			clean = clean.replace( ',', '.' );
		} else {
			clean = clean.replace( /,/g, '' );
		}
		return '' !== clean && isFinite( clean ) ? clean : '';
	}

	function splitLine( line, delimiter ) {
		var cells = [];
		var cell = '';
		var quoted = false;
		for ( var i = 0; i < line.length; i++ ) {
			var ch = line[ i ];
			if ( '"' === ch ) {
				if ( quoted && '"' === line[ i + 1 ] ) {
					cell += '"';
					i++;
				} else {
					quoted = ! quoted;
				}
			} else if ( ch === delimiter && ! quoted ) {
				cells.push( cell.trim() );
				cell = '';
			} else {
				cell += ch;
			}
		}
		cells.push( cell.trim() );
		return cells;
	}

	function parseTable( text ) {
		var lines = text.split( /\r\n|\n|\r/ ).filter( function ( line ) {
			return '' !== line.trim();
		} );
		var delimiter = -1 !== text.indexOf( '\t' ) ? '\t' : ( -1 !== text.indexOf( ';' ) ? ';' : ',' );
		var table = lines.map( function ( line ) {
			return splitLine( line, delimiter );
		} );
		// A header row has no number in its value column.
		if ( table.length && '' === parseNumber( table[ 0 ][ 1 ] ) ) {
			table.shift();
		}
		return table;
	}

	function onImport() {
		var area = document.getElementById( 'qec-import-text' );
		var table = parseTable( area ? area.value : '' );
		if ( ! table.length ) {
			speak( i18n.importEmpty );
			return;
		}

		while ( rows.firstElementChild ) {
			rows.firstElementChild.remove();
		}
		table.forEach( function ( cells ) {
			var row = createRow();
			var values = { label: cells[ 0 ] || '', value: parseNumber( cells[ 1 ] ), max: parseNumber( cells[ 2 ] ), text: cells[ 3 ] || '' };
			Object.keys( values ).forEach( function ( key ) {
				var input = row.querySelector( '[name$="[' + key + ']"]' );
				if ( input ) {
					input.value = values[ key ];
				}
			} );
		} );
		area.value = '';
		speak( ( i18n.imported || '%d' ).replace( '%d', table.length ) );
		schedulePreview();
	}

	/* Style copy ------------------------------------------------------------ */

	function fillStyleSources() {
		var select = document.getElementById( 'qec-style-source' );
		if ( ! select ) {
			return;
		}
		var styles = config.styles || [];
		if ( ! styles.length ) {
			select.closest( '.qec-style-copy' ).hidden = true;
			return;
		}
		styles.forEach( function ( chart ) {
			var option = document.createElement( 'option' );
			option.value = String( chart.id );
			option.textContent = chart.title;
			select.appendChild( option );
		} );
	}

	function onStyleCopy() {
		var select = document.getElementById( 'qec-style-source' );
		var source = ( config.styles || [] ).filter( function ( chart ) {
			return select && String( chart.id ) === select.value;
		} )[ 0 ];
		if ( ! source ) {
			return;
		}

		Object.keys( source.style ).forEach( function ( key ) {
			var value = source.style[ key ];
			var field = builder.querySelector( '[name="' + fieldName( key ) + '"]' );
			if ( ! field ) {
				return;
			}
			if ( 'checkbox' === field.type ) {
				field.checked = !! value;
			} else if ( field.classList.contains( 'qec-color' ) ) {
				var color = value || field.getAttribute( 'data-default-color' ) || '';
				if ( color ) {
					$( field ).wpColorPicker( 'color', color );
				} else {
					field.value = '';
				}
			} else {
				field.value = ( 0 === value && ( 'duration' === key || 'stagger' === key ) ) ? '' : value;
			}
		} );
		speak( i18n.styleCopied );
		schedulePreview();
	}

	/* Contrast check -------------------------------------------------------
	 * Text colours are checked against white as the user types (WCAG 1.4.3). */

	function luminance( hex ) {
		var match = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.exec( String( hex || '' ).trim() );
		if ( ! match ) {
			return null;
		}
		var value = match[ 1 ].length === 3 ? match[ 1 ].replace( /(.)/g, '$1$1' ) : match[ 1 ];
		var channels = [ 0, 2, 4 ].map( function ( i ) {
			var c = parseInt( value.substr( i, 2 ), 16 ) / 255;
			return c <= 0.03928 ? c / 12.92 : Math.pow( ( c + 0.055 ) / 1.055, 2.4 );
		} );
		return 0.2126 * channels[ 0 ] + 0.7152 * channels[ 1 ] + 0.0722 * channels[ 2 ];
	}

	function contrastRatio( a, b ) {
		return ( Math.max( a, b ) + 0.05 ) / ( Math.min( a, b ) + 0.05 );
	}

	function fieldColor( key ) {
		var field = builder.querySelector( '[name="' + fieldName( key ) + '"]' );
		return field ? ( field.value || field.getAttribute( 'data-default-color' ) || '' ) : '';
	}

	// Checks three text colours against a background and writes the result into a status box.
	function reportContrast( boxId, keys, background, message, ok ) {
		var box = document.getElementById( boxId );
		if ( ! box ) {
			return;
		}
		var problems = [];
		keys.forEach( function ( key ) {
			var lum = luminance( fieldColor( key ) );
			if ( null === lum ) {
				return;
			}
			var ratio = contrastRatio( lum, background );
			if ( ratio < 4.5 ) {
				var field = builder.querySelector( '[name="' + fieldName( key ) + '"]' );
				var label = field ? builder.querySelector( 'label[for="' + field.id + '"]' ) : null;
				problems.push( ( message || '' ).replace( '%1$s', label ? label.textContent.trim() : key ).replace( '%2$s', ratio.toFixed( 1 ) ) );
			}
		} );
		var text = problems.length ? problems.join( ' ' ) : ok;
		if ( box.textContent !== text ) {
			box.textContent = text;
		}
		box.classList.toggle( 'is-warning', problems.length > 0 );
	}

	function checkContrast() {
		var lightBg = luminance( fieldColor( 'bg_color' ) );
		reportContrast( 'qec-contrast', [ 'label_color', 'value_color', 'axis_color' ], null === lightBg ? 1 : lightBg, i18n.lowContrast, i18n.contrastOk );

		var darkBg = luminance( fieldColor( 'dark_bg_color' ) );
		reportContrast( 'qec-contrast-dark', [ 'dark_label_color', 'dark_value_color', 'dark_axis_color' ], null === darkBg ? luminance( '#0f0c24' ) : darkBg, i18n.lowContrastDark, i18n.contrastDarkOk );
	}

	/* Colour scheme ------------------------------------------------------------ */

	function previewScheme( scheme ) {
		builder.querySelectorAll( '[data-qec-preview-scheme]' ).forEach( function ( button ) {
			button.setAttribute( 'aria-pressed', button.getAttribute( 'data-qec-preview-scheme' ) === scheme ? 'true' : 'false' );
		} );
		preview.classList.toggle( 'qec-force-dark', 'dark' === scheme );
		preview.classList.toggle( 'qec-force-light', 'dark' !== scheme );
		preview.closest( '.qec-preview-stage' ).classList.toggle( 'is-dark', 'dark' === scheme );
	}

	function applyScheme() {
		var select = document.getElementById( 'qec-scheme' );
		var scheme = select ? select.value : 'light';
		builder.querySelectorAll( '[data-qec-schemes]' ).forEach( function ( group ) {
			group.hidden = -1 === group.getAttribute( 'data-qec-schemes' ).split( ' ' ).indexOf( scheme );
		} );
		previewScheme( 'dark' === scheme ? 'dark' : ( 'light' === scheme ? 'light' : ( preview.classList.contains( 'qec-force-dark' ) ? 'dark' : 'light' ) ) );
	}

	function onPreviewScheme( event ) {
		var button = event.target.closest( '[data-qec-preview-scheme]' );
		if ( button ) {
			previewScheme( button.getAttribute( 'data-qec-preview-scheme' ) );
		}
	}

	/* Export ------------------------------------------------------------------ */

	function onExport( event ) {
		var button = event.target.closest( '[data-qec-admin-export]' );
		var chart = preview.querySelector( '.qec-chart' );
		if ( button && chart && window.qaiyoEasyChartsExport ) {
			window.qaiyoEasyChartsExport( chart, button.getAttribute( 'data-qec-admin-export' ) ).catch( function () {} );
		}
	}

	/* Wire up ------------------------------------------------------------- */

	initColors( builder );
	builder.querySelector( '.qec-add-row' ).addEventListener( 'click', addRow );
	rows.addEventListener( 'click', onRowClick );
	builder.addEventListener( 'input', schedulePreview );
	builder.addEventListener( 'change', schedulePreview );
	builder.addEventListener( 'click', onDevice );
	builder.addEventListener( 'click', onPlay );
	builder.addEventListener( 'click', onExport );
	builder.querySelector( '.qec-import-apply' ).addEventListener( 'click', onImport );
	builder.querySelector( '.qec-style-apply' ).addEventListener( 'click', onStyleCopy );
	builder.querySelector( '.qec-type-picker' ).addEventListener( 'change', function () {
		applyType( true );
	} );
	fillStyleSources();
	applyType( false );
	applyScheme();
	checkContrast();
	builder.addEventListener( 'click', onPreviewScheme );
	document.getElementById( 'qec-scheme' ).addEventListener( 'change', applyScheme );
	document.addEventListener( 'click', onCopy );
	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest( '.qec-reset-views' );
		if ( link && ! window.confirm( i18n.resetViews ) ) {
			event.preventDefault();
		}
	} );

	var titleField = document.getElementById( 'title' );
	if ( titleField ) {
		titleField.addEventListener( 'input', schedulePreview );
	}
} )( window.jQuery );
