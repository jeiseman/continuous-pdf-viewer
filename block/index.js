( function( wp ) {
    var el = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var CheckboxControl = wp.components.CheckboxControl;
    var MediaUpload = wp.blockEditor.MediaUpload;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var Button = wp.components.Button;

    registerBlockType( 'cpv/pdf-viewer', {

        edit: function( props ) {
            var atts = props.attributes;

            // --- SMART WRAPPERS (Defensive Programming for Defaults) ---

            // 1. Smart Text Input: Enforces default if value is undefined
            var SmartText = function( label, attrName, fallback ) {
                var currentVal = atts[attrName] !== undefined ? atts[attrName] : fallback;
                return el( TextControl, {
                    label: label,
                    value: currentVal,
                    onChange: function( v ) { var newAtts = {}; newAtts[attrName] = v; props.setAttributes( newAtts ); }
                });
            };

            // 2. Smart Yes/No Select: Enforces default if value is undefined
            var SmartYesNo = function( label, attrName, fallback ) {
                var currentVal = atts[attrName] !== undefined ? atts[attrName] : fallback;
                return el( SelectControl, {
                    label: label,
                    value: currentVal,
                    options: [ { label: 'Yes', value: 'yes' }, { label: 'No', value: 'no' } ],
                    onChange: function( v ) { var newAtts = {}; newAtts[attrName] = v; props.setAttributes( newAtts ); }
                });
            };

            // 3. Smart Select: For dropdowns with multiple options
            var SmartSelect = function( label, attrName, options, fallback ) {
                var currentVal = atts[attrName] !== undefined ? atts[attrName] : fallback;
                return el( SelectControl, {
                    label: label,
                    value: currentVal,
                    options: options,
                    onChange: function( v ) { var newAtts = {}; newAtts[attrName] = v; props.setAttributes( newAtts ); }
                });
            };

            // 4. Responsive Checkbox Row: Defaults to 'yes' unless explicitly told 'no'
            var ResponsiveToggleRow = function( label, baseAttr ) {
                return el( 'div', { style: { marginBottom: '15px', paddingBottom: '15px', borderBottom: '1px solid #e0e0e0' } },
                    el( 'div', { style: { fontWeight: '500', marginBottom: '8px' } }, label ),
                    el( 'div', { style: { display: 'flex', gap: '15px', flexWrap: 'wrap' } },
                        el( CheckboxControl, {
                            label: 'Desktop',
                            checked: atts[baseAttr + 'Desktop'] !== 'no',
                            onChange: function( val ) { var newAtts = {}; newAtts[baseAttr + 'Desktop'] = val ? 'yes' : 'no'; props.setAttributes( newAtts ); }
                        }),
                        el( CheckboxControl, {
                            label: 'Tablet',
                            checked: atts[baseAttr + 'Tablet'] !== 'no',
                            onChange: function( val ) { var newAtts = {}; newAtts[baseAttr + 'Tablet'] = val ? 'yes' : 'no'; props.setAttributes( newAtts ); }
                        }),
                        el( CheckboxControl, {
                            label: 'Mobile',
                            checked: atts[baseAttr + 'Mobile'] !== 'no',
                            onChange: function( val ) { var newAtts = {}; newAtts[baseAttr + 'Mobile'] = val ? 'yes' : 'no'; props.setAttributes( newAtts ); }
                        })
                    )
                );
            };

            // WP Media Uploader Handler
            var onSelectPDF = function( media ) { props.setAttributes( { url: media.url } ); };

            return [
                // --- 1. THE SIDEBAR CONTROLS (InspectorControls) ---
                el( InspectorControls, { key: 'inspector' },

                    el( PanelBody, { title: 'General Configuration', initialOpen: true },
                        SmartText('PDF File URL', 'url', ''),
                        SmartText('Start Page', 'startPage', '1'),
                        SmartYesNo('Open Sidebar on Load', 'sidebarOpen', 'no'),
                        SmartYesNo('Enable Keyboard Nav', 'keyboard', 'yes')
                    ),

                    el( PanelBody, { title: 'Dimensions & Layout', initialOpen: false },
                        SmartText('Desktop Height', 'height', '720px'),
                        SmartText('Tablet Height', 'tabletHeight', '600px'),
                        SmartText('Mobile Height', 'mobileHeight', '480px'),
                        SmartText('Width', 'width', '100%'),
                        SmartText('Max Width', 'maxWidth', '100%'),
                        SmartText('Border Radius', 'borderRadius', '12'),
                        SmartYesNo('Viewer Drop Shadow', 'viewerShadow', 'yes')
                    ),

                    el( PanelBody, { title: 'Colors & Typography', initialOpen: false },
                        SmartText('Accent Color (Hex)', 'accent', '#4f7df3'),
                        SmartText('Background Color', 'bgColor', ''),
                        SmartText('Surface Color', 'surfaceColor', ''),
                        SmartText('Text Color', 'textColor', ''),
                        SmartText('Font Family', 'font', 'DM Sans'),
                        SmartText('Font Display', 'fontDisplay', 'Instrument Serif')
                    ),

                    el( PanelBody, { title: 'Toolbar Features', initialOpen: false },
                        ResponsiveToggleRow('Download Button', 'download'),
                        ResponsiveToggleRow('Print Button', 'print'),
                        ResponsiveToggleRow('Fullscreen Toggle', 'fullscreen'),
                        ResponsiveToggleRow('Search Input', 'search'),
                        ResponsiveToggleRow('Thumbnails Sidebar', 'thumbnails'),
                        ResponsiveToggleRow('Zoom Controls', 'zoom'),
                        ResponsiveToggleRow('Theme Toggle (Dark/Light)', 'themeToggle'),
                        ResponsiveToggleRow('Page Navigation', 'pageNav'),
                        ResponsiveToggleRow('Fit Width Button', 'fitWidthBtn'),
                        ResponsiveToggleRow('Fit Height Button', 'fitHeightBtn'),
                        ResponsiveToggleRow('Status Bar (Footer)', 'statusBar')
                    ),

                    el( PanelBody, { title: 'Cover Settings', initialOpen: false },
                        SmartYesNo('Enable Cover Mode', 'coverMode', 'no'),
                        SmartYesNo('Show Cover Overlay', 'coverOverlay', 'no'),
                        SmartYesNo('Show Cover Button', 'coverButton', 'yes'),
                        SmartText('Cover Height', 'coverHeight', '720px'),
                        SmartText('Cover Hint Text', 'coverHint', 'Click to open the document viewer'),

                        SmartText('Overlay Text', 'coverOverlayText', 'Click to Open'),
                        SmartText('Overlay BG Color', 'coverOverlayBg', 'rgba(0,0,0,0.45)'),
                        SmartText('Overlay Text Color', 'coverOverlayColor', '#ffffff'),
                        SmartText('Overlay Padding', 'coverOverlayPadding', '18px 24px'),
                        SmartText('Overlay Radius', 'coverOverlayRadius', '0px'),
                        SmartText('Overlay Font Size', 'coverOverlayFontSize', '18px'),
                        SmartText('Overlay Font Weight', 'coverOverlayFontWeight', '700'),

                        SmartText('Button Text', 'coverButtonText', 'Open PDF'),
                        SmartSelect('Button Align', 'coverButtonAlign', [{label: 'Left', value: 'left'}, {label: 'Center', value: 'center'}, {label: 'Right', value: 'right'}], 'left'),
                        SmartText('Button Padding', 'coverButtonPadding', '12px 18px'),
                        SmartText('Button Radius', 'coverButtonRadius', '10px')
                    ),

                    el( PanelBody, { title: 'Zoom Configuration', initialOpen: false },
                        SmartSelect('Default Zoom', 'defaultZoom', [
                            { label: 'Fit Width', value: 'fit_width' },
                            { label: 'Fit Height', value: 'fit_height' },
                            { label: '100%', value: '1' },
                            { label: '150%', value: '1.5' },
                            { label: 'Use Custom Number', value: 'number' }
                        ], 'fit_width'),
                        SmartText('Min Zoom', 'minZoom', '0.3'),
                        SmartText('Max Zoom', 'maxZoom', '5'),
                        SmartText('Zoom Step', 'zoomStep', '0.2')
                    ),

                    el( PanelBody, { title: 'System Text Overrides', initialOpen: false },
                        SmartText('Loading Text', 'loadingText', 'Loading document...'),
                        SmartText('Error Text', 'errorText', 'Could not load the document. Please check the file URL.')
                    )
                ),

                // --- 2. THE EDITOR PREVIEW BLOCK ---
                el( 'div', useBlockProps({
                    key: 'preview',
                    className: props.className,
                    style: {
                        padding: '40px 20px', background: atts.bgColor || '#f4f5f7',
                        border: '1px dashed #ccc', borderRadius: '8px', textAlign: 'center', fontFamily: 'sans-serif'
                    }
                }),
                    el('h4', { style: { margin: '0 0 15px 0', color: atts.textColor || '#333' } }, 'Continuous PDF Viewer'),

                    el( MediaUpload, {
                        onSelect: onSelectPDF, allowedTypes: ['application/pdf'], value: atts.url,
                        render: function( obj ) {
                            return el( Button, {
                                className: atts.url ? 'components-button is-secondary' : 'components-button is-primary',
                                onClick: obj.open
                            }, atts.url ? 'Change PDF File' : 'Select PDF File' );
                        }
                    } ),

                    el('p', { style: { marginTop: '15px', fontSize: '13px', color: '#666', wordBreak: 'break-all' } },
                        atts.url ? 'Current File: ' + atts.url.split('/').pop() : 'No PDF selected.'
                    )
                )
            ];
        },

        save: function() { return null; }
    } );
} )( window.wp );
