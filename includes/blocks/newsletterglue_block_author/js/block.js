( function( blocks, editor, element, components ) {

	const el = element.createElement;
	const { registerBlockType } = blocks;
	const { InspectorControls, BlockControls } = editor;
	const { Fragment } = element;
		const { ToggleControl, RangeControl, PanelBody, BaseControl, SelectControl } = components;
	const { useSelect } = wp.data || editor.data || {};

	const icon = el( 'svg', { width: 24, height: 24, viewBox: '0 0 42.301 42.301' },
		el( 'path', {
			fill: '#0088A0',
			d: 'M21.15.563A21.15,21.15,0,1,0,42.3,21.713,21.147,21.147,0,0,0,21.15.563Zm0,8.187a7.5,7.5,0,1,1-7.5,7.5A7.505,7.505,0,0,1,21.15,8.75Zm0,29.338A16.343,16.343,0,0,1,8.656,32.271a9.509,9.509,0,0,1,8.4-5.1,2.087,2.087,0,0,1,.606.094,11.292,11.292,0,0,0,3.488.588,11.249,11.249,0,0,0,3.488-.588,2.087,2.087,0,0,1,.606-.094,9.509,9.509,0,0,1,8.4,5.1A16.343,16.343,0,0,1,21.15,38.087Z',
			transform: 'translate(0 -0.563)'
		} )
	);

	registerBlockType( 'newsletterglue/author', {
		title: 'Newsletter Author',
		description: 'Display the post author\'s name, avatar, and bio in your newsletter.',
		icon: icon,
		category: 'newsletterglue-blocks',
		keywords: [ 'newsletter', 'glue', 'author', 'byline', 'writer' ],
		supports: {
			multiple: true,
			reusable: true,
		},
		attributes: {
			showAvatar: {
				type: 'boolean',
				default: true,
			},
			showName: {
				type: 'boolean',
				default: true,
			},
			showBio: {
				type: 'boolean',
				default: false,
			},
			showMoreLink: {
				type: 'boolean',
				default: false,
			},
			maxBioChars: {
				type: 'number',
				default: 140,
			},
			avatarSize: {
				type: 'number',
				default: 48,
			},
			alignment: {
				type: 'string',
				default: 'left',
			},
		},
		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const {
				showAvatar,
				showName,
				showBio,
				showMoreLink,
				maxBioChars,
				avatarSize,
				alignment,
			} = attributes;

			// Get author data from post.
			let authorId = null;
			let authorName = 'Editorial Team';
			let authorBio = '';
			let authorAvatar = '';

			if ( typeof useSelect === 'function' ) {
				authorId = useSelect( ( select ) => {
					if ( select( 'core/editor' ) ) {
						return select( 'core/editor' ).getEditedPostAttribute( 'author' );
					}
					return null;
				}, [] );

				const author = useSelect( ( select ) => {
					if ( ! authorId || ! select( 'core' ) ) {
						return null;
					}
					try {
						return select( 'core' ).getEntityRecord( 'root', 'user', authorId );
					} catch ( e ) {
						return null;
					}
				}, [ authorId ] );

				if ( author ) {
					authorName = author.name || author.slug || 'Editorial Team';
					authorBio = author.description || '';
					authorAvatar = author.avatar_urls && author.avatar_urls[ 96 ] ? author.avatar_urls[ 96 ] : '';
				}
			}

			// Build preview HTML.
			const previewStyle = {
				border: '1px dashed #ccc',
				padding: '16px',
				margin: '8px 0',
				textAlign: alignment,
				backgroundColor: '#f9f9f9',
			};

			return el( Fragment, {},
				el( InspectorControls, {},
					el( PanelBody, { title: 'Author Settings', initialOpen: true },
						el( BaseControl, {},
							el( ToggleControl,
								{
									label: 'Show avatar',
									checked: showAvatar,
									onChange: ( value ) => {
										setAttributes( { showAvatar: value } );
									},
								}
							)
						),
						el( BaseControl, {},
							el( ToggleControl,
								{
									label: 'Show author name',
									checked: showName,
									onChange: ( value ) => {
										setAttributes( { showName: value } );
									},
								}
							)
						),
						el( BaseControl, {},
							el( ToggleControl,
								{
									label: 'Show author bio',
									checked: showBio,
									onChange: ( value ) => {
										setAttributes( { showBio: value } );
									},
								}
							)
						),
						el( BaseControl, {},
							el( ToggleControl,
								{
									label: 'Show "More from this author" link',
									checked: showMoreLink,
									onChange: ( value ) => {
										setAttributes( { showMoreLink: value } );
									},
								}
							)
						),
						showAvatar && el( BaseControl, { label: 'Avatar size (pixels)' },
							el( RangeControl,
								{
									value: avatarSize,
									onChange: ( value ) => {
										setAttributes( { avatarSize: value } );
									},
									min: 32,
									max: 128,
									step: 8,
								}
							)
						),
						showBio && el( BaseControl, { label: 'Max bio characters' },
							el( RangeControl,
								{
									value: maxBioChars,
									onChange: ( value ) => {
										setAttributes( { maxBioChars: value } );
									},
									min: 50,
									max: 500,
									step: 10,
								}
							)
						),
						el( BaseControl, { label: 'Alignment' },
							el( SelectControl,
								{
									value: alignment,
									options: [
										{ label: 'Left', value: 'left' },
										{ label: 'Center', value: 'center' },
									],
									onChange: ( value ) => {
										setAttributes( { alignment: value } );
									},
								}
							)
						)
					)
				),
				el( 'div', { className: props.className, style: previewStyle },
					showAvatar && authorAvatar && el( 'div', { style: { marginBottom: '12px' } },
						el( 'img', {
							src: authorAvatar,
							alt: authorName,
							width: avatarSize,
							height: avatarSize,
							style: {
								borderRadius: '50%',
								display: 'block',
								margin: alignment === 'center' ? '0 auto' : '0',
							}
						} )
					),
					showName && el( 'div', { style: { marginBottom: '8px', fontWeight: 'bold', fontSize: '16px' } },
						authorName
					),
					showBio && authorBio && el( 'div', {
						style: {
							marginBottom: '12px',
							fontSize: '14px',
							lineHeight: '1.6',
							color: '#666',
						}
					},
						authorBio.length > maxBioChars
							? authorBio.substring( 0, maxBioChars ) + '...'
							: authorBio
					),
					showMoreLink && el( 'div', { style: { marginTop: '8px' } },
						el( 'a', {
							href: '#',
							style: {
								color: '#0088a0',
								textDecoration: 'none',
								fontSize: '14px',
							}
						}, 'More from this author →' )
					)
				)
			);
		},
		save: function() {
			// Dynamic block - no save function needed.
			return null;
		},
	} );

} ) (
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.element,
	window.wp.components
);

