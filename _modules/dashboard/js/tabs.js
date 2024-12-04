/**
 * Tabbed UI
 */

var active_tab 			= '',
	active_child_tab 	= '';	

jQuery( document ).ready( function( $ ) {

	/**
	* Top level tabbed interface. If defined in the view:
	* - tabs are set to display, as JS is enabled
	* - the selected tab's panel is displayed, with all others hidden
	* - clicking a tab will switch which panel is displayed
	*/
	if ( $( '.nav-tab-wrapper.needs-js' ).length > 0 ) {
		// Determine the CSS class that's used to denote panels
		var nav_tab_wrapper_panel 	= $( '.nav-tab-wrapper.needs-js' ).data( 'panel' ),
			nav_tab_wrapper 		= $( '.nav-tab-wrapper.needs-js' ),
			nav_tab_form			= $( '.nav-tab-wrapper.needs-js' ).data( 'form' ),
			nav_tab_form_action 	= $( 'form' + nav_tab_form ).attr( 'action' );
		
		// Show tabbed bar
		$( nav_tab_wrapper ).fadeIn( 'fast', function() {
			$( this ).removeClass( 'needs-js' );
		} );
		
		// Hide all panels
		$( 'div.' + nav_tab_wrapper_panel ).hide();

		// Get the active tab, so we know which panel to display
		active_tab = window.location.hash;

		if ( active_tab.length == 0 ) {
			// Get active tab from the tabbed menu
			active_tab = $( 'a.nav-tab-active', $( nav_tab_wrapper ) ).attr( 'href' );
		} else {
			// Get active tab from the window location hash
			$( 'a.nav-tab-active', $( nav_tab_wrapper ) ).removeClass( 'nav-tab-active' );
			$( 'a[href="' + active_tab + '"]', $( nav_tab_wrapper ) ).addClass( 'nav-tab-active' );
		}

		// If we don't have an active tab at this point, we don't have any tabs, so bail
		if ( typeof active_tab == 'undefined' ) {
			return;
		}

		// Show the active tab's panel now, both by ID and class
		$( active_tab + '-panel' ).show(); // ID
		$( active_tab.replace( '#', '.' ) + '-panel' ).show(); // Class

		// Update the Documentation tab, if it exists
		if ( typeof $( 'a.nav-tab-active', $( nav_tab_wrapper ) ).data( 'documentation' ) != 'undefined' ) {
			$( 'a.nav-tab.documentation' ).attr( 'href', $( 'a.nav-tab-active', $( nav_tab_wrapper ) ).data( 'documentation' ) );
		}

		// Change active panel on tab click
		$( nav_tab_wrapper ).on( 'click', 'a', function( e ) {

			// Don't do anything if this is an external URL
			if( location.hostname === this.hostname || ! this.hostname.length ) {
				// Local
				e.preventDefault();
			} else {
				// External
				return true;
			}

			// Deactivate all tabs, hide all panels
			$( 'a', $( nav_tab_wrapper ) ).removeClass( 'nav-tab-active' );
			$( 'div.' + nav_tab_wrapper_panel ).hide();
			
			// Set clicked tab to active
			$( this ).addClass( 'nav-tab-active' );
			active_tab = $( this ).attr( 'href' );

			// Update the Documentation tab, if it exists
			if ( typeof $( this ).data( 'documentation' ) != 'undefined' ) {
				$( 'a.nav-tab.documentation' ).attr( 'href', $( this ).data( 'documentation' ) );
			}

			// Show the active tab's panel now
			$( active_tab + '-panel' ).each( function() {
				$( this ).show();
			} );
			$( active_tab.replace( '#', '.' ) + '-panel' ).each( function() {
				$( this ).show();
			} );

			// Update the URL hash
			if ( history.pushState ) {
    			history.pushState( null, null, $( this ).attr( 'href' ) );
			} else {
    			location.hash = $( this ).attr( 'href' );
			}

			// Update the URL hash on the form
			if ( nav_tab_form_action !== undefined ) {
				$( nav_tab_form ).attr( 'action', nav_tab_form_action + active_tab );
			}
		} );
	}

} );