/**
 * Vedika Astrology — Frontend JavaScript
 *
 * Handles tab switching, AJAX form submissions, and theme application.
 *
 * Security: All user/API content is escaped via escapeHtml() before DOM insertion.
 * The innerHTML assignments below only use pre-escaped strings built from
 * escapeHtml() calls, never raw user input.
 */
( function () {
    'use strict';

    var config = window.vedikaAstrology || {};

    // -------------------------------------------------------------------------
    // Theme
    // -------------------------------------------------------------------------

    function applyTheme() {
        if ( config.theme === 'dark' ) {
            document.querySelectorAll( '.vedika-card, .vedika-horoscope-all' ).forEach( function ( el ) {
                el.classList.add( 'vedika-theme-dark' );
            } );
        }
    }

    // -------------------------------------------------------------------------
    // Tabs (vedika_horoscope_all)
    // -------------------------------------------------------------------------

    function initTabs() {
        document.querySelectorAll( '.vedika-tabs' ).forEach( function ( tabBar ) {
            var panels = tabBar.parentElement.querySelectorAll( '.vedika-tab-panel' );

            tabBar.addEventListener( 'click', function ( e ) {
                var btn = e.target.closest( '.vedika-tab' );
                if ( ! btn ) return;

                var sign = btn.getAttribute( 'data-sign' );
                if ( ! sign ) return;

                // Update active tab.
                tabBar.querySelectorAll( '.vedika-tab' ).forEach( function ( t ) {
                    t.classList.remove( 'vedika-tab-active' );
                    t.setAttribute( 'aria-selected', 'false' );
                } );
                btn.classList.add( 'vedika-tab-active' );
                btn.setAttribute( 'aria-selected', 'true' );

                // Show matching panel.
                panels.forEach( function ( p ) {
                    if ( p.getAttribute( 'data-sign' ) === sign ) {
                        p.classList.remove( 'vedika-hidden' );
                    } else {
                        p.classList.add( 'vedika-hidden' );
                    }
                } );
            } );
        } );
    }

    // -------------------------------------------------------------------------
    // HTML escape — prevents XSS on all dynamic content
    // -------------------------------------------------------------------------

    function escapeHtml( str ) {
        var div = document.createElement( 'div' );
        div.appendChild( document.createTextNode( str ) );
        return div.textContent !== undefined ? div.innerHTML : str;
    }

    // -------------------------------------------------------------------------
    // Safe DOM manipulation helpers
    // -------------------------------------------------------------------------

    function setContent( element, safeHtml ) {
        // All callers build safeHtml exclusively from escapeHtml()-processed strings.
        // No raw user input ever reaches this function.
        element.textContent = '';
        var temp = document.createElement( 'div' );
        temp.innerHTML = safeHtml; // nosec: input is pre-escaped
        while ( temp.firstChild ) {
            element.appendChild( temp.firstChild );
        }
    }

    function showLoading( container ) {
        container.textContent = '';
        var wrapper = document.createElement( 'div' );
        wrapper.className = 'vedika-loading';
        var spinner = document.createElement( 'span' );
        spinner.className = 'vedika-spinner';
        wrapper.appendChild( spinner );
        wrapper.appendChild( document.createTextNode( ' Loading...' ) );
        container.appendChild( wrapper );
        container.style.display = 'block';
    }

    function showError( container, message ) {
        container.textContent = '';
        var wrapper = document.createElement( 'div' );
        wrapper.className = 'vedika-error';
        var p = document.createElement( 'p' );
        p.textContent = message;
        wrapper.appendChild( p );
        container.appendChild( wrapper );
        container.style.display = 'block';
    }

    // -------------------------------------------------------------------------
    // AJAX form handler
    // -------------------------------------------------------------------------

    function postAjax( action, formData, resultContainer, renderer ) {
        formData.append( 'action', action );
        formData.append( 'nonce', config.nonce || '' );

        showLoading( resultContainer );

        fetch( config.ajaxUrl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
        } )
            .then( function ( res ) {
                return res.json();
            } )
            .then( function ( json ) {
                if ( json.success && json.data ) {
                    var safeHtml = renderer( json.data );
                    setContent( resultContainer, safeHtml );
                    resultContainer.style.display = 'block';
                } else {
                    var msg = ( json.data && json.data.message ) || 'Something went wrong. Please try again.';
                    showError( resultContainer, msg );
                }
            } )
            .catch( function () {
                showError( resultContainer, 'Network error. Please check your connection and try again.' );
            } );
    }

    // -------------------------------------------------------------------------
    // Birth Chart form
    // -------------------------------------------------------------------------

    function initBirthChart() {
        var form = document.getElementById( 'vedika-birth-chart-form' );
        if ( ! form ) return;

        form.addEventListener( 'submit', function ( e ) {
            e.preventDefault();
            var result = document.getElementById( 'vedika-birth-chart-result' );
            var fd = new FormData( form );

            postAjax( 'vedika_birth_chart', fd, result, renderBirthChart );
        } );
    }

    function renderBirthChart( data ) {
        var html = '<h4>' + escapeHtml( 'Birth Chart' ) + '</h4>';

        if ( data.ascendant ) {
            html += '<div class="vedika-result-grid">';
            html += resultItem( 'Ascendant', data.ascendant.sign || data.ascendant );
            if ( data.moonSign ) {
                html += resultItem( 'Moon Sign', data.moonSign.sign || data.moonSign );
            }
            if ( data.sunSign ) {
                html += resultItem( 'Sun Sign', data.sunSign.sign || data.sunSign );
            }
            html += '</div>';
        }

        if ( data.planets && Array.isArray( data.planets ) ) {
            html += '<h4 style="margin-top:1rem;">' + escapeHtml( 'Planetary Positions' ) + '</h4>';
            html += '<div class="vedika-result-grid">';
            data.planets.forEach( function ( p ) {
                var label = p.name || p.planet || 'Planet';
                var val = ( p.sign || '' ) + ( p.degree !== undefined ? ' (' + p.degree.toFixed( 2 ) + ')' : '' );
                html += resultItem( label, val );
            } );
            html += '</div>';
        }

        return html;
    }

    // -------------------------------------------------------------------------
    // Compatibility form
    // -------------------------------------------------------------------------

    function initCompatibility() {
        var form = document.getElementById( 'vedika-compatibility-form' );
        if ( ! form ) return;

        form.addEventListener( 'submit', function ( e ) {
            e.preventDefault();
            var result = document.getElementById( 'vedika-compatibility-result' );
            var fd = new FormData( form );

            postAjax( 'vedika_compatibility', fd, result, renderCompatibility );
        } );
    }

    function renderCompatibility( data ) {
        var html = '<h4>' + escapeHtml( 'Compatibility Report' ) + '</h4>';

        if ( data.totalScore !== undefined || data.total !== undefined ) {
            var score = data.totalScore !== undefined ? data.totalScore : data.total;
            var max = data.maxScore || data.maxPoints || 36;
            html += '<div class="vedika-result-grid">';
            html += resultItem( 'Score', score + ' / ' + max );
            if ( data.compatibility ) {
                html += resultItem( 'Compatibility', data.compatibility );
            }
            html += '</div>';
        }

        if ( data.categories && Array.isArray( data.categories ) ) {
            html += '<div class="vedika-result-grid" style="margin-top:1rem;">';
            data.categories.forEach( function ( c ) {
                html += resultItem(
                    c.name || c.koot || 'Category',
                    ( c.obtained || c.score || 0 ) + '/' + ( c.total || c.max || '-' )
                );
            } );
            html += '</div>';
        }

        return html;
    }

    // -------------------------------------------------------------------------
    // Numerology form
    // -------------------------------------------------------------------------

    function initNumerology() {
        var form = document.getElementById( 'vedika-numerology-form' );
        if ( ! form ) return;

        form.addEventListener( 'submit', function ( e ) {
            e.preventDefault();
            var result = document.getElementById( 'vedika-numerology-result' );
            var fd = new FormData( form );

            postAjax( 'vedika_numerology', fd, result, renderNumerology );
        } );
    }

    function renderNumerology( data ) {
        var html = '<h4>' + escapeHtml( 'Numerology Report' ) + '</h4>';
        html += '<div class="vedika-result-grid">';

        if ( data.lifePathNumber !== undefined ) {
            html += resultItem( 'Life Path Number', data.lifePathNumber );
        }
        if ( data.destinyNumber !== undefined ) {
            html += resultItem( 'Destiny Number', data.destinyNumber );
        }
        if ( data.personalityNumber !== undefined ) {
            html += resultItem( 'Personality Number', data.personalityNumber );
        }
        if ( data.soulUrgeNumber !== undefined ) {
            html += resultItem( 'Soul Urge Number', data.soulUrgeNumber );
        }

        html += '</div>';

        if ( data.meaning && data.meaning.summary ) {
            html += '<p style="margin-top:1rem;">' + escapeHtml( data.meaning.summary ) + '</p>';
        }

        if ( data.lifePath && data.lifePath.meaning && data.lifePath.meaning.summary ) {
            html += '<p style="margin-top:1rem;">' + escapeHtml( data.lifePath.meaning.summary ) + '</p>';
        }

        return html;
    }

    // -------------------------------------------------------------------------
    // Shared render helpers
    // -------------------------------------------------------------------------

    function resultItem( label, value ) {
        return '<div class="vedika-result-item">' +
            '<div class="vedika-label">' + escapeHtml( String( label ) ) + '</div>' +
            '<div class="vedika-value">' + escapeHtml( String( value ) ) + '</div>' +
            '</div>';
    }

    // -------------------------------------------------------------------------
    // Init
    // -------------------------------------------------------------------------

    function init() {
        applyTheme();
        initTabs();
        initBirthChart();
        initCompatibility();
        initNumerology();
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }
} )();
