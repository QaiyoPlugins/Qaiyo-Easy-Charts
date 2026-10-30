/**
 * Qaiyo Easy Charts — front-end behaviour.
 *
 * Charts render fully in HTML/CSS. This script only adds what needs a browser:
 * lazy mounting near the viewport, the entrance animation (optionally replayed),
 * counting values up, and thinning out colliding axis ticks. Without it, charts
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

		chart.querySelectorAll( '.qec-value' ).forEach( function ( el, index ) {
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

			var row = el.closest( '.qec-row' );
			var position = row ? parseInt( row.style.getPropertyValue( '--qec-i' ) || index, 10 ) : index;
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
		chart.querySelectorAll( '.qec-value[data-qec-text]' ).forEach( function ( el ) {
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
