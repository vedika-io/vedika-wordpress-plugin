/**
 * Vedika Tarot Block — Editor component.
 */
( function ( wp ) {
    const { createElement: el } = wp.element;
    const { useBlockProps } = wp.blockEditor;
    const { __ } = wp.i18n;

    wp.blocks.registerBlockType( 'vedika/tarot', {
        edit: function () {
            var blockProps = useBlockProps( {
                className: 'vedika-tarot vedika-card vedika-block-preview',
            } );

            return el(
                'div',
                blockProps,
                el(
                    'div',
                    { className: 'vedika-tarot-header' },
                    el( 'h3', { className: 'vedika-card-name' }, __( 'Card of the Day', 'vedika-astrology' ) ),
                    el( 'span', { className: 'vedika-arcana' }, __( 'Major Arcana', 'vedika-astrology' ) )
                ),
                el(
                    'div',
                    { className: 'vedika-tarot-body' },
                    el(
                        'div',
                        { className: 'vedika-tarot-orientation' },
                        el( 'span', { className: 'vedika-orientation-badge vedika-upright' }, __( 'Upright', 'vedika-astrology' ) )
                    ),
                    el(
                        'p',
                        { className: 'vedika-tarot-meaning', style: { fontStyle: 'italic', opacity: 0.7 } },
                        __( 'Tarot reading will appear here on the frontend.', 'vedika-astrology' )
                    ),
                    el(
                        'div',
                        { className: 'vedika-tarot-keywords' },
                        el( 'span', { className: 'vedika-keyword' }, __( 'New Beginnings', 'vedika-astrology' ) ),
                        el( 'span', { className: 'vedika-keyword' }, __( 'Innocence', 'vedika-astrology' ) ),
                        el( 'span', { className: 'vedika-keyword' }, __( 'Spontaneity', 'vedika-astrology' ) )
                    )
                ),
                el(
                    'div',
                    { className: 'vedika-attribution' },
                    __( 'Powered by Vedika AI', 'vedika-astrology' )
                )
            );
        },
    } );
} )( window.wp );
