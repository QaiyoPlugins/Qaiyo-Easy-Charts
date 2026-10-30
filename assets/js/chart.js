/**
 * Qaiyo Easy Charts: entrance animations.
 *
 * Charts render fully in HTML/CSS; this script only plays the reveal animation
 * when a chart scrolls into view and thins out colliding axis ticks. Without
 * it, charts are shown static.
 */
( function () {
	'use strict';

	var SELECTOR = '.qec-chart';
	var observer = null;

	function prefersReducedMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function reveal( chart ) {
		chart.classList.add( 'qec-in-view' );
	}

	function getObserver() {
		if ( observer || ! ( 'IntersectionObserver' in window ) ) {
			return observer;
		}

		observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						reveal( entry.target );
						observer.unobserve( entry.target );
					}
				} );
			},
			{ threshold: 0.2, rootMargin: '0px 0px -5% 0px' }
		);

		return observer;
	}

	/*
	 * Hide intermediate axis ticks that would overlap their neighbours (e.g. "15"
	 * colliding with "20 days" in a narrow column). First and last ticks stay.
	 */
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

	function setup( chart ) {
		if ( chart.qecReady ) {
			return;
		}
		chart.qecReady = true;

		fitTicks( chart );
		if ( resizeObserver ) {
			resizeObserver.observe( chart );
		}

		var io = getObserver();
		if ( ! chart.hasAttribute( 'data-qec-animate' ) || ! io || prefersReducedMotion() ) {
			return;
		}

		chart.classList.add( 'qec-armed' );
		// Commit the collapsed state before the reveal transition can start.
		void chart.offsetWidth;
		io.observe( chart );
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

	// Public: Called by Bricks after rendering the element in the builder, and usable by any code that inserts charts dynamically.
	window.qaiyoEasyChartsInit = function () {
		init( document );
	};

	if ( document.readyState === 'loading' ) {
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
