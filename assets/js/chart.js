/**
 * Qaiyo Easy Charts — front-end behaviour.
 *
 * Charts render fully in HTML/CSS. This script only adds what needs a browser:
 * lazy mounting near the viewport, the entrance animation (optionally replayed),
 * counting values up, thinning out colliding axis ticks, dismissing tooltips
 * with Escape, the PNG / SVG export and reporting anonymous chart views. Without it, charts
 * are shown static (lazy charts through their <noscript> copy).
 */
( function () {
	'use strict';

	var SELECTOR = '.qec-chart';
	var LAZY_MARGIN = '300px 0px';

	document.documentElement.classList.add( 'qec-js' );

	function reducedMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function supportsObserver() {
		return 'IntersectionObserver' in window;
	}

	/* Timing ---------------------------------------------------------------- */

	function cssTime( chart, name, fallback ) {
		var raw = window.getComputedStyle( chart ).getPropertyValue( name ).trim();
		var value = parseFloat( raw );
		if ( isNaN( value ) ) {
			return fallback;
		}
		return /ms$/.test( raw ) ? value : ( /s$/.test( raw ) ? value * 1000 : value );
	}

	/* Axis ticks ------------------------------------------------------------- */

	// Hide intermediate ticks that would overlap a neighbour; first and last stay.
	function fitTicks( chart ) {
		var ticks = Array.prototype.slice.call( chart.querySelectorAll( '.qec-axis .qec-tick' ) );
		if ( ticks.length < 3 ) {
			return;
		}
		ticks.forEach( function ( tick ) {
			tick.classList.remove( 'qec-tick--hidden' );
		} );

		var gap = 8;
		var overlaps = function ( a, b ) {
			var ra = a.getBoundingClientRect();
			var rb = b.getBoundingClientRect();
			return ra.left < rb.right + gap && rb.left < ra.right + gap && ra.top < rb.bottom && rb.top < ra.bottom;
		};

		var last = ticks[ ticks.length - 1 ];
		var kept = ticks[ 0 ];
		for ( var i = 1; i < ticks.length - 1; i++ ) {
			if ( overlaps( ticks[ i ], kept ) || overlaps( ticks[ i ], last ) ) {
				ticks[ i ].classList.add( 'qec-tick--hidden' );
			} else {
				kept = ticks[ i ];
			}
		}
	}

	var resizeObserver = 'ResizeObserver' in window ? new ResizeObserver( function ( entries ) {
		entries.forEach( function ( entry ) {
			fitTicks( entry.target );
		} );
	} ) : null;

	/* Count-up -------------------------------------------------------------- */

	var NUMBER = /\d(?:[\d   .,]*\d)?/g;

	// Write `value` into the digit positions of `token`, keeping its separators
	// ("1 000", "3,5"), so the counting text always looks like the final one.
	function maskFormat( token, value ) {
		var fraction = token.match( /[.,](\d{1,2})$/ );
		var digits = String( value );
		var minimum = fraction ? fraction[ 1 ].length + 1 : 1;
		while ( digits.length < minimum ) {
			digits = '0' + digits;
		}

		var out = '';
		var d = digits.length - 1;
		for ( var i = token.length - 1; i >= 0 && d >= 0; i-- ) {
			out = ( /\d/.test( token[ i ] ) ? digits[ d-- ] : token[ i ] ) + out;
		}
		return out;
	}

	function countUp( chart ) {
		var duration = cssTime( chart, '--qec-duration', 1100 );
		var stagger = cssTime( chart, '--qec-stagger', 140 );

		chart.querySelectorAll( '.qec-count' ).forEach( function ( el, index ) {
			var original = el.getAttribute( 'data-qec-text' );
			if ( null === original ) {
				original = el.textContent;
				el.setAttribute( 'data-qec-text', original );
			}

			var tokens = original.match( NUMBER );
			if ( ! tokens ) {
				return;
			}
			var targets = tokens.map( function ( token ) {
				return parseInt( token.replace( /\D/g, '' ), 10 );
			} );

			var mark = el.closest( '.qec-mark' );
			var position = mark ? parseInt( mark.style.getPropertyValue( '--qec-i' ) || index, 10 ) : 0;
			var delay = position * stagger + duration * 0.6;
			var length = duration * 0.8;
			var start = null;

			var render = function ( progress ) {
				var n = 0;
				el.textContent = original.replace( NUMBER, function ( token ) {
					var target = targets[ n++ ];
					return maskFormat( token, Math.round( target * progress ) );
				} );
			};

			var step = function ( now ) {
				if ( null === start ) {
					start = now + delay;
				}
				var t = Math.min( 1, Math.max( 0, ( now - start ) / length ) );
				render( 1 - Math.pow( 1 - t, 3 ) );
				if ( t < 1 ) {
					el.qecFrame = window.requestAnimationFrame( step );
				} else {
					el.textContent = original;
				}
			};

			window.cancelAnimationFrame( el.qecFrame );
			render( 0 );
			el.qecFrame = window.requestAnimationFrame( step );
		} );
	}

	function resetCount( chart ) {
		chart.querySelectorAll( '.qec-count[data-qec-text]' ).forEach( function ( el ) {
			window.cancelAnimationFrame( el.qecFrame );
			el.textContent = el.getAttribute( 'data-qec-text' );
		} );
	}

	/* Animation ------------------------------------------------------------- */

	function show( chart ) {
		chart.classList.add( 'qec-in-view' );
		if ( chart.hasAttribute( 'data-qec-count' ) ) {
			countUp( chart );
		}
	}

	function hide( chart ) {
		resetCount( chart );
		chart.classList.remove( 'qec-in-view' );
	}

	var animationObserver = null;

	function getAnimationObserver() {
		if ( ! animationObserver && supportsObserver() ) {
			animationObserver = new IntersectionObserver(
				function ( entries ) {
					entries.forEach( function ( entry ) {
						var chart = entry.target;
						var replay = chart.hasAttribute( 'data-qec-replay' );
						if ( entry.isIntersecting ) {
							show( chart );
							if ( ! replay ) {
								animationObserver.unobserve( chart );
							}
						} else if ( replay && chart.classList.contains( 'qec-in-view' ) ) {
							hide( chart );
						}
					} );
				},
				{ threshold: 0.2 }
			);
		}
		return animationObserver;
	}

	function activate( chart ) {
		fitTicks( chart );
		trackView( chart );
		if ( resizeObserver ) {
			resizeObserver.observe( chart );
		}

		var observer = getAnimationObserver();
		if ( ! chart.hasAttribute( 'data-qec-animate' ) || ! observer || reducedMotion() ) {
			return;
		}

		chart.classList.add( 'qec-armed' );
		// Commit the start state before the reveal transition can begin.
		void chart.offsetWidth;
		observer.observe( chart );
	}

	/* Tooltips ---------------------------------------------------------------
	 * Shown by CSS on hover and keyboard focus. Escape hides the open one
	 * (WCAG 1.4.13, dismissible) until the pointer or focus leaves the mark. */

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' !== event.key ) {
			return;
		}
		document.querySelectorAll( '.qec-tooltips .qec-mark:hover, .qec-tooltips .qec-mark:focus-visible' ).forEach( function ( mark ) {
			mark.classList.add( 'qec-tip-off' );
		} );
	} );

	function restoreTip( event ) {
		var mark = event.target && event.target.closest ? event.target.closest( '.qec-mark.qec-tip-off' ) : null;
		if ( mark && ! mark.contains( event.relatedTarget ) ) {
			mark.classList.remove( 'qec-tip-off' );
		}
	}

	// Keep an opening tip inside the chart and the window (WCAG 1.4.10 / 1.4.13).
	function placeTip( event ) {
		var mark = event.target && event.target.closest ? event.target.closest( '.qec-tooltips .qec-mark' ) : null;
		var tip = mark ? mark.querySelector( ':scope > .qec-tip' ) : null;
		if ( ! tip ) {
			return;
		}
		tip.style.setProperty( '--qec-tip-x', '0px' );
		window.requestAnimationFrame( function () {
			var chart = mark.closest( SELECTOR ).getBoundingClientRect();
			var box = tip.getBoundingClientRect();
			var left = Math.max( chart.left, 0 );
			var right = Math.min( chart.right, document.documentElement.clientWidth );
			var shift = box.right > right ? right - box.right : ( box.left < left ? left - box.left : 0 );
			tip.style.setProperty( '--qec-tip-x', Math.round( shift ) + 'px' );
		} );
	}

	document.addEventListener( 'mouseover', placeTip );
	document.addEventListener( 'focusin', placeTip );
	document.addEventListener( 'mouseout', restoreTip );
	document.addEventListener( 'focusout', restoreTip );

	/* Export -----------------------------------------------------------------
	 * The chart is HTML/CSS, so it is exported as an SVG that wraps a copy of it
	 * (<foreignObject>) together with the chart stylesheet, the fonts it uses
	 * (embedded, so the file looks the same anywhere) and the typography and
	 * colours that builders set from outside; the PNG is drawn from that SVG at
	 * twice the size. Charts are exported in their final, fully drawn state. */

	var SVG_NS = 'http://www.w3.org/2000/svg';
	var COPIED = [ 'font-family', 'font-size', 'font-weight', 'font-style', 'line-height', 'letter-spacing', 'text-transform', 'color' ];

	// Typography and colours, which page builders may set on any part of the chart.
	function copyTypography( source, target ) {
		var computed = window.getComputedStyle( source );
		COPIED.forEach( function ( name ) {
			target.style.setProperty( name, computed.getPropertyValue( name ) );
		} );
		for ( var c = 0; c < source.children.length && c < target.children.length; c++ ) {
			copyTypography( source.children[ c ], target.children[ c ] );
		}
	}

	// Custom properties (colours, sizes) set on the chart root by builders or themes.
	function copyVariables( source, target ) {
		var computed = window.getComputedStyle( source );
		for ( var i = 0; i < computed.length; i++ ) {
			if ( 0 === computed[ i ].indexOf( '--qec-' ) ) {
				target.style.setProperty( computed[ i ], computed.getPropertyValue( computed[ i ] ) );
			}
		}
	}

	function rulesOf( sheet ) {
		try {
			return Array.prototype.slice.call( sheet.cssRules || [] );
		} catch ( error ) {
			return []; // Cross-origin stylesheet.
		}
	}

	function chartCss() {
		var css = '';
		Array.prototype.forEach.call( document.styleSheets, function ( sheet ) {
			if ( sheet.href && /qaiyo-easy-charts\/assets\/css\/chart\.css/.test( sheet.href ) ) {
				css += rulesOf( sheet ).map( function ( rule ) {
					return rule.cssText;
				} ).join( '\n' );
			}
		} );
		return css;
	}

	function toDataUrl( url ) {
		return window.fetch( url, { mode: 'cors' } )
			.then( function ( response ) {
				return response.ok ? response.blob() : Promise.reject();
			} )
			.then( function ( blob ) {
				return new Promise( function ( resolve ) {
					var reader = new FileReader();
					reader.onloadend = function () {
						resolve( reader.result );
					};
					reader.readAsDataURL( blob );
				} );
			} );
	}

	// @font-face rules of the families the chart uses, with the files inlined.
	function fontCss( chart ) {
		var families = {};
		[ chart ].concat( Array.prototype.slice.call( chart.querySelectorAll( '*' ) ) ).forEach( function ( node ) {
			window.getComputedStyle( node ).fontFamily.split( ',' ).forEach( function ( family ) {
				families[ family.trim().replace( /^["']|["']$/g, '' ).toLowerCase() ] = true;
			} );
		} );

		var jobs = [];
		Array.prototype.forEach.call( document.styleSheets, function ( sheet ) {
			rulesOf( sheet ).forEach( function ( rule ) {
				if ( ! window.CSSFontFaceRule || ! ( rule instanceof window.CSSFontFaceRule ) ) {
					return;
				}
				var family = rule.style.getPropertyValue( 'font-family' ).trim().replace( /^["']|["']$/g, '' ).toLowerCase();
				var src = rule.style.getPropertyValue( 'src' );
				var match = src.match( /url\(\s*["']?([^"')]+)["']?\s*\)/ );
				if ( ! families[ family ] || ! match ) {
					return;
				}
				var base = rule.parentStyleSheet && rule.parentStyleSheet.href ? rule.parentStyleSheet.href : document.baseURI;
				jobs.push(
					toDataUrl( new URL( match[ 1 ], base ).href )
						.then( function ( data ) {
							return rule.cssText.replace( /src\s*:[^;]+;/, 'src: url("' + data + '");' );
						} )
						.catch( function () {
							return '';
						} )
				);
			} );
		} );

		return Promise.all( jobs ).then( function ( rules ) {
			return rules.join( '\n' );
		} );
	}

	function fileName( chart ) {
		var title = chart.querySelector( '.qec-title' );
		var name = ( title ? title.textContent : chart.getAttribute( 'aria-label' ) ) || '';
		return name.toLowerCase().replace( /[^\p{L}\p{N}]+/gu, '-' ).replace( /^-+|-+$/g, '' ) || 'chart';
	}

	function toSvg( chart ) {
		mount( chart );
		var armed = chart.classList.contains( 'qec-armed' );
		// Final state at once: no start state, no running transition, final numbers.
		chart.classList.add( 'qec-exporting' );
		chart.classList.remove( 'qec-armed' );
		resetCount( chart );

		var rect = chart.getBoundingClientRect();
		var width = Math.ceil( rect.width );
		var height = Math.ceil( rect.height );
		var copy = chart.cloneNode( true );
		copyTypography( chart, copy );
		copyVariables( chart, copy );

		chart.classList.remove( 'qec-exporting' );
		if ( armed ) {
			chart.classList.add( 'qec-armed' );
		}

		// Parts that only make sense on the page.
		copy.querySelectorAll( '.qec-data-table, .qec-export, .qec-tip, .qec-sr-only, template, noscript' ).forEach( function ( node ) {
			node.remove();
		} );
		copy.classList.remove( 'qec-armed', 'qec-exporting' );
		copy.style.setProperty( 'width', width + 'px' );
		copy.style.setProperty( 'margin', '0' );

		return fontCss( chart ).then( function ( fonts ) {
			var wrapper = document.createElement( 'div' );
			wrapper.setAttribute( 'xmlns', 'http://www.w3.org/1999/xhtml' );
			var style = document.createElement( 'style' );
			style.textContent = fonts + '\n' + chartCss();
			wrapper.appendChild( style );
			wrapper.appendChild( copy );

			var svg = document.createElementNS( SVG_NS, 'svg' );
			svg.setAttribute( 'xmlns', SVG_NS );
			svg.setAttribute( 'width', width );
			svg.setAttribute( 'height', height );
			svg.setAttribute( 'viewBox', '0 0 ' + width + ' ' + height );
			var object = document.createElementNS( SVG_NS, 'foreignObject' );
			object.setAttribute( 'width', '100%' );
			object.setAttribute( 'height', '100%' );
			object.appendChild( wrapper );
			svg.appendChild( object );

			return {
				markup: new XMLSerializer().serializeToString( svg ),
				width: width,
				height: height,
			};
		} );
	}

	function download( name, blob ) {
		var url = URL.createObjectURL( blob );
		var link = document.createElement( 'a' );
		link.href = url;
		link.download = name;
		document.body.appendChild( link );
		link.click();
		link.remove();
		window.setTimeout( function () {
			URL.revokeObjectURL( url );
		}, 1000 );
	}

	function background( chart ) {
		for ( var node = chart; node && 1 === node.nodeType; node = node.parentElement ) {
			var color = window.getComputedStyle( node ).backgroundColor;
			if ( color && ! /^(transparent|rgba\(.*,\s*0\))$/.test( color ) ) {
				return color;
			}
		}
		return '#ffffff';
	}

	function exportChart( chart, format ) {
		var name = fileName( chart );

		return toSvg( chart ).then( function ( svg ) {
			var svgBlob = new Blob( [ svg.markup ], { type: 'image/svg+xml;charset=utf-8' } );
			if ( 'svg' === format ) {
				download( name + '.svg', svgBlob );
				return;
			}

			return new Promise( function ( resolve, reject ) {
				var image = new Image();
				image.onload = function () {
					var scale = 2;
					var canvas = document.createElement( 'canvas' );
					canvas.width = svg.width * scale;
					canvas.height = svg.height * scale;
					var context = canvas.getContext( '2d' );
					context.fillStyle = background( chart );
					context.fillRect( 0, 0, canvas.width, canvas.height );
					context.scale( scale, scale );
					context.drawImage( image, 0, 0 );
					try {
						canvas.toBlob( function ( png ) {
							download( name + '.png', png );
							resolve();
						}, 'image/png' );
					} catch ( error ) {
						// Browsers that taint canvases drawn from SVG: fall back to the SVG file.
						download( name + '.svg', svgBlob );
						reject( error );
					}
				};
				image.onerror = reject;
				image.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent( svg.markup );
			} );
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target && event.target.closest ? event.target.closest( '[data-qec-export]' ) : null;
		var chart = button ? button.closest( SELECTOR ) : null;
		if ( chart ) {
			exportChart( chart, button.getAttribute( 'data-qec-export' ) ).catch( function () {} );
		}
	} );

	/* Views ------------------------------------------------------------------
	 * A saved chart reports one anonymous view when at least half of it has been
	 * on screen, once per browser session (sessionStorage, no cookies). */

	var viewObserver = null;

	function alreadySeen( id ) {
		try {
			if ( window.sessionStorage.getItem( 'qec-view-' + id ) ) {
				return true;
			}
			window.sessionStorage.setItem( 'qec-view-' + id, '1' );
		} catch ( error ) {
			// Storage blocked: the server-side throttle still prevents double counting.
		}
		return false;
	}

	function reportView( id ) {
		var endpoint = window.qaiyoEcViews && window.qaiyoEcViews.url;
		if ( ! endpoint || alreadySeen( id ) ) {
			return;
		}
		var body = new window.FormData();
		body.append( 'id', id );
		if ( navigator.sendBeacon && navigator.sendBeacon( endpoint, body ) ) {
			return;
		}
		if ( window.fetch ) {
			window.fetch( endpoint, { method: 'POST', body: body, keepalive: true, credentials: 'omit' } ).catch( function () {} );
		}
	}

	function trackView( chart ) {
		var id = chart.getAttribute( 'data-qec-view' );
		if ( ! id || ! supportsObserver() ) {
			return;
		}
		if ( ! viewObserver ) {
			viewObserver = new IntersectionObserver(
				function ( entries ) {
					entries.forEach( function ( entry ) {
						if ( entry.isIntersecting ) {
							viewObserver.unobserve( entry.target );
							reportView( entry.target.getAttribute( 'data-qec-view' ) );
						}
					} );
				},
				{ threshold: 0.5 }
			);
		}
		viewObserver.observe( chart );
	}

	/* Lazy loading ---------------------------------------------------------- */

	function mount( chart ) {
		var template = chart.querySelector( ':scope > template.qec-lazy-template' );
		if ( ! template ) {
			return;
		}
		var placeholder = chart.querySelector( ':scope > .qec-lazy-placeholder' );
		var fragment = template.content.cloneNode( true );

		if ( placeholder ) {
			placeholder.replaceWith( fragment );
		} else {
			template.before( fragment );
		}
		template.remove();
		chart.querySelectorAll( ':scope > noscript' ).forEach( function ( node ) {
			node.remove();
		} );
	}

	var lazyObserver = null;

	function getLazyObserver() {
		if ( ! lazyObserver && supportsObserver() ) {
			lazyObserver = new IntersectionObserver(
				function ( entries ) {
					entries.forEach( function ( entry ) {
						if ( entry.isIntersecting ) {
							lazyObserver.unobserve( entry.target );
							mount( entry.target );
							activate( entry.target );
						}
					} );
				},
				{ rootMargin: LAZY_MARGIN }
			);
		}
		return lazyObserver;
	}

	/* Setup ----------------------------------------------------------------- */

	function setup( chart ) {
		if ( chart.qecReady ) {
			return;
		}
		chart.qecReady = true;

		if ( chart.hasAttribute( 'data-qec-lazy' ) ) {
			var observer = getLazyObserver();
			if ( observer ) {
				observer.observe( chart );
				return;
			}
			mount( chart );
		}
		activate( chart );
	}

	function init( context ) {
		context = context || document;

		if ( context.matches && context.matches( SELECTOR ) ) {
			setup( context );
		}
		if ( context.querySelectorAll ) {
			Array.prototype.forEach.call( context.querySelectorAll( SELECTOR ), setup );
		}
	}

	// Public: called by Bricks after rendering the element in the builder, and
	// usable by any code that inserts charts dynamically.
	window.qaiyoEasyChartsInit = function () {
		init( document );
	};

	// Public: export a chart element as 'png' or 'svg' (the chart builder uses it).
	window.qaiyoEasyChartsExport = exportChart;

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			init( document );
		} );
	} else {
		init( document );
	}

	// Elementor: (re)initialise widgets rendered in the editor or loaded dynamically.
	function bindElementor() {
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/qaiyo-easy-chart.default', function ( $scope ) {
			init( $scope && $scope[ 0 ] ? $scope[ 0 ] : document );
		} );
	}

	if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
		bindElementor();
	} else if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', bindElementor );
	}
} )();
