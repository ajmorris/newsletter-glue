( function( element, components, data ) {

	const el = element.createElement;
	const { Fragment } = element;
	const { useState, useEffect } = element;
	const { useSelect } = data;
	
	// Safely get i18n - wait for it to be available.
	let __;
	if ( typeof wp !== 'undefined' && wp.i18n && wp.i18n.__ ) {
		__ = wp.i18n.__;
	} else {
		__ = function( text, domain ) {
			return text;
		};
	}

	/**
	 * Get PluginPrePublishPanel - wait for it to be available.
	 */
	function getPluginPrePublishPanel() {
		if ( typeof wp !== 'undefined' && wp.editor && wp.editor.PluginPrePublishPanel ) {
			return wp.editor.PluginPrePublishPanel;
		}
		if ( typeof wp !== 'undefined' && wp.editPost && wp.editPost.PluginPrePublishPanel ) {
			return wp.editPost.PluginPrePublishPanel;
		}
		return null;
	}

	/**
	 * Pre-publish panel component that shows newsletter details.
	 */
	function NewsletterConfirmPanel() {
		const [ newsletterData, setNewsletterData ] = useState( {} );
		const [ sendChecked, setSendChecked ] = useState( false );
		const [ PluginPrePublishPanel, setPluginPrePublishPanel ] = useState( null );

		// Wait for PluginPrePublishPanel to be available.
		useEffect( () => {
			const checkForPanel = () => {
				const Panel = getPluginPrePublishPanel();
				if ( Panel ) {
					setPluginPrePublishPanel( () => Panel );
					return true;
				}
				return false;
			};

			// Check immediately.
			if ( ! checkForPanel() ) {
				// If not available, check periodically.
				const interval = setInterval( () => {
					if ( checkForPanel() ) {
						clearInterval( interval );
					}
				}, 100 );

				return () => clearInterval( interval );
			}
		}, [] );

		// Check if "Send as newsletter" is checked.
		useEffect( () => {
			const checkSendStatus = () => {
				if ( typeof jQuery !== 'undefined' ) {
					const checked = jQuery( '#ngl_send_newsletter' ).is( ':checked' ) || jQuery( '#ngl_send_newsletter2' ).is( ':checked' );
					setSendChecked( checked );
					
					if ( checked ) {
						// Get newsletter data.
						const data = {
							subject: '',
							audience: '',
							audienceName: '',
							segment: '',
							app: '',
							appName: '',
						};

						// Try to get from localized script.
						if ( typeof newsletterglueConfirm !== 'undefined' ) {
							data.subject = newsletterglueConfirm.subject || '';
							data.audience = newsletterglueConfirm.audience || '';
							data.audienceName = newsletterglueConfirm.audienceName || '';
							data.segment = newsletterglueConfirm.segment || '';
							data.app = newsletterglueConfirm.app || '';
							data.appName = newsletterglueConfirm.appName || '';
						}

						// Fallback: get from metabox fields.
						if ( typeof jQuery !== 'undefined' ) {
							if ( ! data.subject ) {
								const subjectField = jQuery( '#ngl_subject' );
								if ( subjectField.length ) {
									data.subject = subjectField.val() || '';
								}
							}

							if ( ! data.audience ) {
								const audienceField = jQuery( '#ngl_audience' );
								if ( audienceField.length ) {
									data.audience = audienceField.val() || '';
									const selectedOption = audienceField.find( 'option:selected' );
									if ( selectedOption.length ) {
										data.audienceName = selectedOption.text() || data.audience;
									}
								}
							}

							if ( ! data.segment ) {
								const segmentField = jQuery( '#ngl_segment' );
								if ( segmentField.length ) {
									const segmentValue = segmentField.val();
									if ( segmentValue && segmentValue !== '_everyone' ) {
										const selectedOption = segmentField.find( 'option:selected' );
										data.segment = selectedOption.length ? selectedOption.text() : segmentValue;
									}
								}
							}
						}

						// Fallback to post title if no subject.
						if ( ! data.subject ) {
							try {
								const title = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'title' );
								data.subject = title || '';
							} catch ( e ) {
								// Silent fallback.
							}
						}

						setNewsletterData( data );
					}
				}
			};

			// Check immediately.
			checkSendStatus();

			// Check on interval to catch changes.
			const interval = setInterval( checkSendStatus, 500 );

			// Also listen for changes to the checkbox.
			if ( typeof jQuery !== 'undefined' ) {
				jQuery( document ).on( 'change', '#ngl_send_newsletter, #ngl_send_newsletter2', checkSendStatus );
			}

			return () => {
				clearInterval( interval );
				if ( typeof jQuery !== 'undefined' ) {
					jQuery( document ).off( 'change', '#ngl_send_newsletter, #ngl_send_newsletter2', checkSendStatus );
				}
			};
		}, [] );

		// Only show panel if confirmation is enabled and send newsletter is checked.
		const confirmationEnabled = typeof newsletterglueConfirm !== 'undefined' && (
			newsletterglueConfirm.confirmationEnabled === true ||
			newsletterglueConfirm.confirmationEnabled === 'yes' ||
			newsletterglueConfirm.confirmationEnabled === '1' ||
			newsletterglueConfirm.confirmationEnabled === 1
		);

		if ( ! confirmationEnabled || ! sendChecked || ! PluginPrePublishPanel ) {
			return null;
		}

		const subject = newsletterData.subject || '';
		const audienceName = newsletterData.audienceName || newsletterData.audience || __( 'Unknown', 'newsletter-glue' );
		const segment = newsletterData.segment && newsletterData.segment !== '_everyone' ? newsletterData.segment : '';
		const appName = newsletterData.appName || '';

		return el( PluginPrePublishPanel,
			{
				className: 'ngl-confirm-send-panel',
				title: __( 'Newsletter Details', 'newsletter-glue' ),
				initialOpen: true,
			},
			el( 'div', { style: { marginBottom: '16px' } },
				el( 'p', { style: { marginBottom: '12px', fontWeight: '600' } },
					__( 'This post will be sent as a newsletter when published.', 'newsletter-glue' )
				),
				el( 'div', { style: { marginBottom: '12px' } },
					el( 'strong', { style: { display: 'inline-block', minWidth: '80px' } }, __( 'Subject: ', 'newsletter-glue' ) ),
					el( 'span', {}, subject || __( '(not set)', 'newsletter-glue' ) )
				),
				el( 'div', { style: { marginBottom: '12px' } },
					el( 'strong', { style: { display: 'inline-block', minWidth: '80px' } }, __( 'Audience: ', 'newsletter-glue' ) ),
					el( 'span', {}, audienceName )
				),
				segment && el( 'div', { style: { marginBottom: '12px' } },
					el( 'strong', { style: { display: 'inline-block', minWidth: '80px' } }, __( 'Segment: ', 'newsletter-glue' ) ),
					el( 'span', {}, segment )
				),
				appName && el( 'div', { style: { marginBottom: '12px' } },
					el( 'strong', { style: { display: 'inline-block', minWidth: '80px' } }, __( 'Service: ', 'newsletter-glue' ) ),
					el( 'span', {}, appName )
				),
				el( 'p', { style: { marginTop: '16px', fontSize: '13px', color: '#757575' } },
					__( 'Double-check these details before publishing. The newsletter will be sent immediately upon publishing.', 'newsletter-glue' )
				)
			)
		);
	}

	/**
	 * Plugin component that renders pre-publish panel.
	 */
	function ConfirmSendPlugin() {
		return el( Fragment, {},
			el( NewsletterConfirmPanel )
		);
	}

	// Register plugin - wait for wp.plugins to be available.
	function registerConfirmSendPlugin() {
		if ( typeof wp === 'undefined' ) {
			setTimeout( registerConfirmSendPlugin, 100 );
			return;
		}
		
		if ( ! wp.plugins ) {
			setTimeout( registerConfirmSendPlugin, 100 );
			return;
		}
		
		if ( typeof wp.plugins.registerPlugin !== 'function' ) {
			setTimeout( registerConfirmSendPlugin, 100 );
			return;
		}
		
		try {
			wp.plugins.registerPlugin( 'newsletterglue-confirm-send', {
				render: ConfirmSendPlugin,
				icon: '',
			} );
		} catch ( error ) {
			// Silent fail if plugin registration fails.
		}
	}
	
	// Start registration process - wait a bit to ensure everything is loaded.
	setTimeout( registerConfirmSendPlugin, 200 );

} )(
	window.wp.element,
	window.wp.components,
	window.wp.data
);
