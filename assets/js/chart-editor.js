/**
 * Qaiyo Easy Charts — chart edit screen: bar rows, colour pickers, live preview.
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

	function addRow() {
		var html = template.innerHTML.replace( /__i__/g, 'n' + ( counter++ ) );
		var tbody = document.createElement( 'tbody' );
		tbody.innerHTML = html.trim();
		var row = tbody.firstElementChild;
		rows.appendChild( row );
		initColors( row );
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

	/* Wire up ------------------------------------------------------------- */

	initColors( builder );
	builder.querySelector( '.qec-add-row' ).addEventListener( 'click', addRow );
	rows.addEventListener( 'click', onRowClick );
	builder.addEventListener( 'input', schedulePreview );
	builder.addEventListener( 'change', schedulePreview );
	builder.addEventListener( 'click', onDevice );
	builder.addEventListener( 'click', onPlay );
	document.addEventListener( 'click', onCopy );

	var titleField = document.getElementById( 'title' );
	if ( titleField ) {
		titleField.addEventListener( 'input', schedulePreview );
	}
} )( window.jQuery );
