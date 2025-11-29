( function( blocks, editor, element, components, data, hooks, apiFetch ) {

const { createElement: el, Fragment, useState, useEffect } = element;
const { registerPlugin } = wp.plugins;
const { PluginDocumentSettingPanel } = wp.editPost;
const { useSelect, useDispatch } = data;
const { 
	TextControl, 
	SelectControl, 
	ToggleControl, 
	PanelBody, 
	PanelRow,
	BaseControl,
	Button,
	Spinner,
	Notice
} = components;

/**
 * Newsletter Glue Panel Component
 */
function NewsletterGluePanel() {

	// Get current post ID and meta.
	const postId = useSelect( ( select ) => {
		return select( 'core/editor' ).getCurrentPostId();
	}, [] );

	const postType = useSelect( ( select ) => {
		return select( 'core/editor' ).getCurrentPostType();
	}, [] );

	const postStatus = useSelect( ( select ) => {
		return select( 'core/editor' ).getEditedPostAttribute( 'status' );
	}, [] );

	const meta = useSelect( ( select ) => {
		return select( 'core/editor' ).getEditedPostAttribute( 'meta' );
	}, [] );

	const { editPost } = useDispatch( 'core/editor' );

	// State management.
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isConnected, setIsConnected ] = useState( false );
	const [ app, setApp ] = useState( null );
	const [ defaults, setDefaults ] = useState( {} );
	const [ audiences, setAudiences ] = useState( {} );
	const [ segments, setSegments ] = useState( {} );
	const [ selectedAudience, setSelectedAudience ] = useState( '' );
	const [ isLoadingSegments, setIsLoadingSegments ] = useState( false );
	const [ isSent, setIsSent ] = useState( false );
	const [ isScheduled, setIsScheduled ] = useState( false );
	const [ testEmail, setTestEmail ] = useState( '' );
	const [ isSendingTest, setIsSendingTest ] = useState( false );
	const [ testResult, setTestResult ] = useState( null );
	const [ testEmailByWordPress, setTestEmailByWordPress ] = useState( false );
	const [ appName, setAppName ] = useState( '' );
	const [ subjectError, setSubjectError ] = useState( false );
	const [ isResetting, setIsResetting ] = useState( false );
	const [ audienceLabel, setAudienceLabel ] = useState( 'Audience' );
	const [ segmentLabel, setSegmentLabel ] = useState( 'Segment / tag' );
	const [ createTagUrl, setCreateTagUrl ] = useState( '' );

	// Get newsletter data from meta.
	const newsletterData = meta._newsletterglue || {};
	const futureData = meta._ngl_future_send || '';

	const postLink = useSelect( ( select ) => {
		const post = select( 'core/editor' ).getCurrentPost();
		if ( post && post.link ) {
			return post.link;
		}
		return null;
	}, [] );

	// Load defaults and check connection.
	useEffect( () => {
		if ( postId ) {
			setIsLoading( true );
			apiFetch( { 
				path: `/newsletterglue/v1/defaults/${postId}` 
			} )
			.then( ( response ) => {
				setIsConnected( response.connected );
				setApp( response.app );
				setAppName( response.appName || '' );
				setDefaults( response.defaults || {} );
				setAudiences( response.audiences || {} );
				setTestEmailByWordPress( response.testEmailByWordPress || false );
				setAudienceLabel( response.audienceLabel || 'Audience' );
				setSegmentLabel( response.segmentLabel || 'Segment / tag' );
				setCreateTagUrl( response.createTagUrl || '' );
				
				// Set initial audience value - check multiple sources.
				let initialAudience = '';
				let initialAudienceName = '';
				
				if ( newsletterData.audience ) {
					initialAudience = newsletterData.audience;
					initialAudienceName = newsletterData.audienceName || response.audiences[ newsletterData.audience ] || newsletterData.audience;
				} else if ( response.current_audience ) {
					initialAudience = response.current_audience;
					initialAudienceName = response.audiences[ response.current_audience ] || response.current_audience;
				} else if ( response.defaults && response.defaults.audience ) {
					initialAudience = response.defaults.audience;
					initialAudienceName = response.audiences[ response.defaults.audience ] || response.defaults.audience;
				} else if ( response.audiences && Object.keys( response.audiences ).length > 0 ) {
					// Default to first audience if nothing is set.
					initialAudience = Object.keys( response.audiences )[0];
					initialAudienceName = response.audiences[ initialAudience ];
				}
				
				setSelectedAudience( initialAudience );
				
				// If audience is set but no audience name is stored, update it.
				if ( initialAudience && ! newsletterData.audienceName ) {
					const updatedData = { 
						...newsletterData, 
						audience: initialAudience,
						audienceName: initialAudienceName,
						app: response.app,
						appName: response.appName || ''
					};
					editPost( { meta: { _newsletterglue: updatedData } } );
				}

				// Check if already sent.
				if ( newsletterData.sent ) {
					setIsSent( true );
				}

				// Check if scheduled.
				if ( futureData === 'yes' ) {
					setIsScheduled( true );
				}

				// Set initial test email from defaults or stored value.
				if ( response.defaults && response.defaults.test_email ) {
					setTestEmail( response.defaults.test_email );
				} else if ( newsletterData.test_email ) {
					setTestEmail( newsletterData.test_email );
				}

				setIsLoading( false );
			} )
			.catch( ( error ) => {
				console.error( 'Error loading Newsletter Glue defaults:', error );
				setIsLoading( false );
			} );
		}
	}, [ postId ] );

	// Load segments when audience changes.
	useEffect( () => {
		if ( selectedAudience && selectedAudience !== '' ) {
			setIsLoadingSegments( true );
			apiFetch( { 
				path: `/newsletterglue/v1/segments?audience=${selectedAudience}` 
			} )
			.then( ( response ) => {
				setSegments( response || {} );
				setIsLoadingSegments( false );
			} )
			.catch( ( error ) => {
				console.error( 'Error loading segments:', error );
				setIsLoadingSegments( false );
			} );
		}
	}, [ selectedAudience ] );

	// Validate subject field.
	const validateSubject = ( subjectValue, sendNewsletter ) => {
		const sendChecked = sendNewsletter === '1' || sendNewsletter === 1 || 
			( sendNewsletter === undefined && ( newsletterData.send_newsletter === '1' || newsletterData.send_newsletter === 1 ) );
		const isEmpty = ! subjectValue || subjectValue.trim() === '';
		
		// Show error if send is checked and subject is empty.
		setSubjectError( sendChecked && isEmpty );
	};
	
	// Validate subject when send_newsletter or subject changes.
	useEffect( () => {
		const subject = newsletterData.subject || defaults.subject || '';
		const sendChecked = newsletterData.send_newsletter === '1' || newsletterData.send_newsletter === 1;
		validateSubject( subject, sendChecked ? '1' : '0' );
	}, [ newsletterData.send_newsletter, newsletterData.subject ] );

	// Helper function to update newsletter meta.
	const updateNewsletterMeta = ( key, value ) => {
		const updatedData = { 
			...newsletterData, 
			[key]: value,
			app: app // Always store the app name.
		};
		editPost( { meta: { _newsletterglue: updatedData } } );
		
		// Validate subject if it's being updated and send_newsletter is checked.
		if ( key === 'subject' ) {
			validateSubject( value, updatedData.send_newsletter );
		}
	};

	// Handle audience change.
	const handleAudienceChange = ( value ) => {
		setSelectedAudience( value );
		
		// Get the audience name from the audiences object.
		const audienceName = audiences[ value ] || value;
		
		// Update both the audience ID and name.
		const updatedData = { 
			...newsletterData, 
			audience: value,
			audienceName: audienceName,
			segment: '', // Reset segment when audience changes.
			segmentName: '', // Reset segment name.
			app: app,
			appName: app ? newsletterglue_meta.appName || '' : ''
		};
		editPost( { meta: { _newsletterglue: updatedData } } );
	};

	// Handle segment change.
	const handleSegmentChange = ( value ) => {
		// Get the segment name from the segments object.
		const segmentName = value !== '_everyone' ? ( segments[ value ] || value ) : '';
		
		// Update both the segment ID and name.
		const updatedData = { 
			...newsletterData, 
			segment: value,
			segmentName: segmentName,
			app: app
		};
		editPost( { meta: { _newsletterglue: updatedData } } );
	};

	// Handle test email send.
	const handleSendTestEmail = () => {
		if ( ! testEmail || isSendingTest ) {
			return;
		}

		setIsSendingTest( true );
		setTestResult( null );

		// First, ensure all current newsletter data is saved to meta.
		const currentData = { 
			...newsletterData,
			test_email: testEmail,
			app: app || defaults.app
		};
		editPost( { meta: { _newsletterglue: currentData } } );

		// Use the unified REST API endpoint for sending test emails.
		apiFetch( {
			path: '/newsletterglue/v1/test-email',
			method: 'POST',
			data: {
				post_id: postId,
				test_email: testEmail,
			},
		} )
		.then( ( response ) => {
			setIsSendingTest( false );
			
			if ( response && response.success ) {
				setTestResult( { type: 'success', message: response.success } );
				// Store test email for next time.
				updateNewsletterMeta( 'test_email', testEmail );
			} else {
				const errorMessage = ( response && response.fail ) 
					? response.fail 
					: ( response && response.message )
						? response.message
						: ( newsletterglue_meta.unknown_error || 'Could not send test email. Please try again.' );
				setTestResult( { type: 'error', message: errorMessage } );
			}
		} )
		.catch( ( error ) => {
			setIsSendingTest( false );
			const errorMessage = error.message || ( newsletterglue_meta.unknown_error || 'Could not send test email. Please try again.' );
			setTestResult( { type: 'error', message: errorMessage } );
		} );
	};

	// Get preview email URL.
	const getPreviewEmailUrl = () => {
		if ( ! postId ) {
			return '#';
		}
		// Use post link if available, otherwise construct preview URL.
		let previewUrl = postLink;
		if ( ! previewUrl ) {
			const siteUrl = window.location.origin;
			previewUrl = siteUrl + '/?p=' + postId + '&preview=true';
		}
		
		// Add preview_email parameter.
		const separator = previewUrl.indexOf( '?' ) > -1 ? '&' : '?';
		return previewUrl + separator + 'preview_email=' + postId;
	};

	// Handle reset newsletter (send another).
	const handleResetNewsletter = () => {
		if ( ! postId || isResetting ) {
			return;
		}

		setIsResetting( true );

		// Use REST API endpoint for consistency with panel architecture.
		apiFetch( {
			path: '/newsletterglue/v1/reset-newsletter',
			method: 'POST',
			data: {
				post_id: postId,
			},
		} )
		.then( () => {
			// Reset the sent state locally.
			setIsSent( false );
			setIsScheduled( false );
			
			// Update meta to remove sent flag and reset send_newsletter.
			const updatedData = { 
				...newsletterData,
				sent: undefined, // Remove sent flag
				send_newsletter: '0', // Reset send toggle
			};
			editPost( { meta: { _newsletterglue: updatedData } } );
			
			// Reload defaults to refresh the panel.
			setIsLoading( true );
			apiFetch( { 
				path: `/newsletterglue/v1/defaults/${postId}` 
			} )
			.then( ( response ) => {
				setDefaults( response.defaults || {} );
				setIsLoading( false );
				setIsResetting( false );
			} )
			.catch( ( error ) => {
				console.error( 'Error reloading defaults:', error );
				setIsLoading( false );
				setIsResetting( false );
			} );
		} )
		.catch( ( error ) => {
			console.error( 'Error resetting newsletter:', error );
			setIsResetting( false );
		} );
	};

	// Don't show for patterns.
	if ( postType === 'ngl_pattern' ) {
		return null;
	}

	if ( isLoading ) {
		return el(
			PluginDocumentSettingPanel,
			{
				name: 'newsletterglue-panel',
				title: 'Newsletter Glue',
				className: 'newsletterglue-panel',
			},
			el( 'div', { style: { padding: '16px', textAlign: 'center' } },
				el( Spinner )
			)
		);
	}

	if ( ! isConnected ) {
		return el(
			PluginDocumentSettingPanel,
			{
				name: 'newsletterglue-panel',
				title: 'Newsletter Glue',
				className: 'newsletterglue-panel',
			},
			el( Notice, {
				status: 'warning',
				isDismissible: false,
			}, 
				'Please connect your email service provider in ',
				el( 'a', { 
					href: newsletterglue_meta.settings_url,
					target: '_blank'
				}, 'Newsletter Glue Settings' )
			)
		);
	}

	// Show sent/scheduled status.
	if ( isSent || isScheduled ) {
		return el(
			PluginDocumentSettingPanel,
			{
				name: 'newsletterglue-panel',
				title: 'Newsletter Glue',
				className: 'newsletterglue-panel',
			},
			el( Notice, {
				status: 'success',
				isDismissible: false,
			}, 
				isScheduled ? 'Newsletter scheduled to send when published.' : 'Newsletter has been sent.'
			),
			el( PanelRow, {},
				el( Button, {
					isSecondary: true,
					isBusy: isResetting,
					onClick: handleResetNewsletter,
					disabled: isResetting,
					style: { marginTop: '12px' },
				}, isResetting ? 'Resetting...' : 'Send another newsletter' )
			),
			el( 'p', { style: { fontSize: '13px', color: '#757575', marginTop: '12px' } },
				'Click the button above to send another newsletter with different settings.'
			)
		);
	}

	// Prepare audience options.
	const audienceOptions = Object.keys( audiences ).map( ( key ) => ({
		label: audiences[ key ],
		value: key
	}) );

	// Prepare segment options.
	const segmentOptions = Object.keys( segments ).map( ( key ) => ({
		label: segments[ key ],
		value: key
	}) );

	// Add "Everyone" option to segments.
	if ( segmentOptions.length > 0 ) {
		segmentOptions.unshift( { label: 'Everyone', value: '_everyone' } );
	}

	return el(
		PluginDocumentSettingPanel,
		{
			name: 'newsletterglue-panel',
			title: 'Newsletter Glue',
			className: 'newsletterglue-panel',
		},
		
		// Subject line error notice.
		subjectError && el( Notice, {
			status: 'error',
			isDismissible: false,
		}, 'Subject is required when sending as newsletter.' ),
		
		// Subject line.
		el( TextControl, {
			label: 'Subject',
			value: newsletterData.subject || defaults.subject || '',
			onChange: ( value ) => updateNewsletterMeta( 'subject', value ),
			help: subjectError ? 'This field is required.' : 'Short, catchy subject lines get more opens.',
			className: subjectError ? 'has-error' : '',
		} ),

		// Preview text.
		el( TextControl, {
			label: 'Preview text',
			value: newsletterData.preview_text || defaults.preview_text || '',
			onChange: ( value ) => updateNewsletterMeta( 'preview_text', value ),
			help: 'Snippet of text that appears after your subject in subscribers\' inboxes.',
		} ),

		// Audience selector.
		audienceOptions.length > 0 && el( SelectControl, {
			label: audienceLabel,
			value: newsletterData.audience || selectedAudience || '',
			options: audienceOptions,
			onChange: handleAudienceChange,
			help: 'Who receives your email.',
		} ),

		// Segment selector.
		selectedAudience && el( BaseControl, {
			label: segmentLabel,
			help: isLoadingSegments ? 'Loading segments...' : ( createTagUrl ? el( Fragment, {}, 
				'A specific group of subscribers. ',
				el( 'a', { href: createTagUrl, target: '_blank', rel: 'noopener noreferrer' }, 'Create ' + segmentLabel.toLowerCase() )
			) : 'A specific group of subscribers.' ),
		},
			isLoadingSegments ? el( Spinner ) : (
				segmentOptions.length > 0 ? el( SelectControl, {
					value: newsletterData.segment || '_everyone',
					options: segmentOptions,
					onChange: handleSegmentChange,
				} ) : el( 'p', { style: { fontSize: '13px', color: '#757575' } }, 'No segments available for this audience.' )
			)
		),

		// From name.
		el( TextControl, {
			label: 'From name',
			value: newsletterData.from_name || defaults.from_name || '',
			onChange: ( value ) => updateNewsletterMeta( 'from_name', value ),
			help: 'Your subscribers will see this name in their inboxes.',
		} ),

		// From email.
		el( TextControl, {
			label: 'From email',
			value: newsletterData.from_email || defaults.from_email || '',
			onChange: ( value ) => updateNewsletterMeta( 'from_email', value ),
			help: 'Subscribers will see and reply to this email address.',
		} ),

		// Read on site link.
		el( PanelRow, {},
			el( ToggleControl, {
				label: 'Include "Read on site" link',
				checked: newsletterData.include_read_link === '1' || newsletterData.include_read_link === 1,
				onChange: ( value ) => updateNewsletterMeta( 'include_read_link', value ? '1' : '0' ),
				help: 'Include a link to the original post at the end of the newsletter.',
			} )
		),

		// Custom link label (only show if include_read_link is checked).
		( newsletterData.include_read_link === '1' || newsletterData.include_read_link === 1 ) && el( TextControl, {
			label: 'Custom link label',
			value: newsletterData.read_link_custom_label || '',
			onChange: ( value ) => updateNewsletterMeta( 'read_link_custom_label', value ),
			help: 'Leave empty to use the global default.',
		} ),

		// Test email section.
		el( 'hr', { style: { margin: '16px 0' } } ),
		
		el( BaseControl, {
			label: 'Send test email',
			help: 'Send a test email to preview how your newsletter will look.',
		},
			el( 'div', { style: { display: 'flex', gap: '8px', alignItems: 'flex-start' } },
				el( 'div', { style: { flex: '1' } },
					el( TextControl, {
						value: testEmail,
						onChange: ( value ) => setTestEmail( value ),
						placeholder: 'Enter email address',
						type: 'email',
					} )
				),
				el( 'div', {},
					el( Button, {
						isPrimary: true,
						isBusy: isSendingTest,
						onClick: handleSendTestEmail,
						disabled: ! testEmail || isSendingTest,
					}, isSendingTest ? 'Sending...' : 'Send' )
				)
			),
			testResult && testResult.type === 'success' && el( Notice, {
				status: 'success',
				isDismissible: true,
				onRemove: () => setTestResult( null ),
			}, testResult.message || 'Email sent' ),
			testResult && testResult.type === 'error' && el( Notice, {
				status: 'error',
				isDismissible: true,
				onRemove: () => setTestResult( null ),
			}, testResult.message || 'Could not send test email. Please try again.' ),
			testEmailByWordPress && el( Notice, {
				status: 'info',
				isDismissible: false,
			}, el( Fragment, {},
				'This test email is sent by WordPress. Formatting and deliverability might differ slightly from email campaigns sent by ',
				el( 'strong', {}, appName || 'your email service provider' ),
				'.'
			) )
		),

		// Preview email link.
		el( PanelRow, {},
			el( 'a', {
				href: getPreviewEmailUrl(),
				target: '_blank',
				style: { fontSize: '13px', textDecoration: 'none' },
			}, 'Preview email in browser', el( 'span', { style: { color: '#757575', marginLeft: '4px' } }, '(opens in new tab)' ) )
		),

		// Send as newsletter toggle.
		el( 'hr', { style: { margin: '16px 0' } } ),
		
		el( PanelRow, {},
			el( ToggleControl, {
				label: 'Send as newsletter',
				checked: newsletterData.send_newsletter === '1' || newsletterData.send_newsletter === 1,
				onChange: ( value ) => {
					updateNewsletterMeta( 'send_newsletter', value ? '1' : '0' );
					// Validate subject when toggling send_newsletter.
					const subject = newsletterData.subject || defaults.subject || '';
					validateSubject( subject, value ? '1' : '0' );
				},
				help: 'Send this post as a newsletter when published/updated.',
			} )
		),

		// Info message.
		el( Notice, {
			status: 'info',
			isDismissible: false,
		}, 
			'Make sure to fill in all required fields before publishing. The newsletter will be sent when you publish or update the post.'
		)
	);
}

// Register the panel plugin.
registerPlugin( 'newsletterglue-panel-plugin', {
	render: NewsletterGluePanel,
	icon: null,
} );

} ) (
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.element,
	window.wp.components,
	window.wp.data,
	window.wp.hooks,
	window.wp.apiFetch
);

