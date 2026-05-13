/**
 * Vedika Horoscope Block — Editor component.
 */
( function ( wp ) {
    const { createElement: el } = wp.element;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl } = wp.components;
    const { __ } = wp.i18n;

    const SIGNS = [
        { label: 'Aries',       value: 'aries' },
        { label: 'Taurus',      value: 'taurus' },
        { label: 'Gemini',      value: 'gemini' },
        { label: 'Cancer',      value: 'cancer' },
        { label: 'Leo',         value: 'leo' },
        { label: 'Virgo',       value: 'virgo' },
        { label: 'Libra',       value: 'libra' },
        { label: 'Scorpio',     value: 'scorpio' },
        { label: 'Sagittarius', value: 'sagittarius' },
        { label: 'Capricorn',   value: 'capricorn' },
        { label: 'Aquarius',    value: 'aquarius' },
        { label: 'Pisces',      value: 'pisces' },
    ];

    const SIGN_SYMBOLS = {
        aries: '♈', taurus: '♉', gemini: '♊', cancer: '♋',
        leo: '♌', virgo: '♍', libra: '♎', scorpio: '♏',
        sagittarius: '♐', capricorn: '♑', aquarius: '♒', pisces: '♓',
    };

    const PERIODS = [
        { label: __( 'Daily', 'vedika-astrology' ),   value: 'daily' },
        { label: __( 'Weekly', 'vedika-astrology' ),   value: 'weekly' },
        { label: __( 'Monthly', 'vedika-astrology' ),  value: 'monthly' },
    ];

    wp.blocks.registerBlockType( 'vedika/horoscope', {
        edit: function ( props ) {
            var attrs = props.attributes;
            var blockProps = useBlockProps( {
                className: 'vedika-horoscope vedika-card vedika-block-preview',
            } );
            var signLabel = SIGNS.find( function ( s ) { return s.value === attrs.sign; } );
            var symbol = SIGN_SYMBOLS[ attrs.sign ] || '♈';

            return el(
                'div',
                null,
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        { title: __( 'Horoscope Settings', 'vedika-astrology' ), initialOpen: true },
                        el( SelectControl, {
                            label: __( 'Zodiac Sign', 'vedika-astrology' ),
                            value: attrs.sign,
                            options: SIGNS,
                            onChange: function ( val ) { props.setAttributes( { sign: val } ); },
                        } ),
                        el( SelectControl, {
                            label: __( 'Period', 'vedika-astrology' ),
                            value: attrs.period,
                            options: PERIODS,
                            onChange: function ( val ) { props.setAttributes( { period: val } ); },
                        } )
                    )
                ),
                el(
                    'div',
                    blockProps,
                    el(
                        'div',
                        { className: 'vedika-horoscope-header' },
                        el( 'span', { className: 'vedika-sign-symbol' }, symbol ),
                        el( 'h3', { className: 'vedika-sign-name' }, signLabel ? signLabel.label : 'Aries' ),
                        el( 'span', { className: 'vedika-date' }, new Date().toLocaleDateString() )
                    ),
                    el(
                        'div',
                        { className: 'vedika-horoscope-body' },
                        el(
                            'p',
                            { className: 'vedika-prediction', style: { fontStyle: 'italic', opacity: 0.7 } },
                            __( 'Horoscope content will appear here on the frontend.', 'vedika-astrology' )
                        )
                    ),
                    el(
                        'div',
                        { className: 'vedika-attribution' },
                        __( 'Powered by Vedika AI', 'vedika-astrology' )
                    )
                )
            );
        },
    } );
} )( window.wp );
