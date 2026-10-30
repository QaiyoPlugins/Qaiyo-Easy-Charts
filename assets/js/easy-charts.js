/**
 * Qaiyo Easy Charts: entrance animations.
 *
 * Charts render fully in HTML/CSS; this script only plays the reveal animation
 * when a chart scrolls into view. Without it, charts are shown static.
 */
( function () {
	'use strict';

	var SELECTOR = '.qec-chart[data-qec-animate]';
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

	function setup( chart ) {
		if ( chart.qecReady ) {
			return;
		}
		chart.qecReady = true;

		var io = getObserver();
		if ( ! io || prefersReducedMotion() ) {
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

	// Public: Bricks calls this after rendering the element in the builder.
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
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/qaiyo-range-bars.default', function ( $scope ) {
			init( $scope && $scope[ 0 ] ? $scope[ 0 ] : document );
		} );
	}

	if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
		bindElementor();
	} else if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', bindElementor );
	}
} )();
