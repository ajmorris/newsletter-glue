<?php
/**
 * Kit.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'NGL_Abstract_Integration', false ) ) {
	include_once NGL_PLUGIN_DIR . 'includes/abstract-integration.php';
}

/**
 * Main Class.
 */
class NGL_Kit extends NGL_Abstract_Integration {

	public $app		= 'kit';
	public $api_key = null;
	public $api 	= null;

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Include needed files.
		include_once 'lib/api.php';

		$this->get_api_key();

		add_filter( 'newsltterglue_kit_html_content', array( $this, 'html_content' ), 10, 2 );
	}

	/**
	 * Get API Key.
	 */
	public function get_api_key() {
		$integrations = get_option( 'newsletterglue_integrations' );
		$integration  = isset( $integrations[ $this->app ] ) ? $integrations[ $this->app] : '';
		$this->api_key = isset( $integration[ 'api_key' ] ) ? $integration[ 'api_key' ] : '';
	}

	/**
	 * Add Integration.
	 */
	public function add_integration() {

		// Get API key from input.
		$api_key = isset( $_POST['ngl_kit_key'] ) ? sanitize_text_field( $_POST['ngl_kit_key'] ) : '';

		// Test mode. no key provided.
		if ( ! $api_key ) {
			$integrations 	= get_option( 'newsletterglue_integrations' );
			$options		= isset( $integrations[ $this->app ] ) ? $integrations[ $this->app ] : '';
			if ( isset( $options[ 'api_key'] ) ) {
				$api_key = $options[ 'api_key' ];
			}
		}

		// Clean the API key - only extract if it has prefix text, otherwise use as-is
		$clean_api_key = trim( $api_key );
		if ( ! empty( $clean_api_key ) ) {
			// Only extract if there's text before the key (like "Kit API Key - kit_...")
			// If it already starts with "kit_", use it as-is
			if ( strpos( $clean_api_key, 'kit_' ) !== 0 ) {
				// Has prefix, extract just the key part
				if ( preg_match( '/kit_[a-zA-Z0-9]+/', $clean_api_key, $matches ) ) {
					$clean_api_key = $matches[0];
				}
			}
			// Trim whitespace
			$clean_api_key = trim( $clean_api_key );
		}

		$this->api = new NGL_Kit_API( $clean_api_key );

		// Debug: Log API key (masked for security)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$masked_key = ! empty( $clean_api_key ) ? substr( $clean_api_key, 0, 8 ) . '...' . substr( $clean_api_key, -4 ) : 'empty';
			error_log( 'NGL_Kit add_integration: Original API Key: ' . $api_key );
			error_log( 'NGL_Kit add_integration: Cleaned API Key (masked): ' . $masked_key );
		}

		// Use /accounts endpoint to validate (Kit API v4)
		$account = $this->api->get_account();

		// Debug: Log account response
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'NGL_Kit add_integration: Account response: ' . print_r( $account, true ) );
		}

		// Check for error responses first
		if ( isset( $account['status'] ) && ( $account['status'] == 'error' || ( is_numeric( $account['status'] ) && $account['status'] >= 400 ) ) ) {
			// Extract error message - check for errors array first, then message
			$error_message = 'Unknown error';
			if ( isset( $account['data']['errors'] ) && is_array( $account['data']['errors'] ) && ! empty( $account['data']['errors'] ) ) {
				$error_message = is_array( $account['data']['errors'][0] ) ? json_encode( $account['data']['errors'][0] ) : $account['data']['errors'][0];
			} elseif ( isset( $account['message'] ) ) {
				$error_message = $account['message'];
			} elseif ( isset( $account['data']['message'] ) ) {
				$error_message = $account['data']['message'];
			}
			
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'NGL_Kit add_integration: Error detected - Status: ' . $account['status'] . ', Message: ' . $error_message );
				error_log( 'NGL_Kit add_integration: Full error response: ' . print_r( $account, true ) );
			}
			$this->remove_integration();
			$result = array( 
				'response' => 'invalid',
				'message'  => $error_message,
			);
			delete_option( 'newsletterglue_kit' );
			return $result;
		}

		// Check for valid API key - Kit API v4 validation
		// We're using /forms endpoint to validate - if it returns forms data or empty array, key is valid
		// If it returns an error status, key is invalid
		$valid_account = false;
		$validation_details = array();

		// Check for error responses (invalid key)
		if ( isset( $account['status'] ) && ( $account['status'] == 'error' || ( is_numeric( $account['status'] ) && $account['status'] >= 400 ) ) ) {
			$validation_details[] = 'Error status detected: ' . $account['status'];
			$valid_account = false;
		} elseif ( ! empty( $account ) ) {
			// Success - check if we got forms data (means API key is valid)
			if ( isset( $account['forms'] ) || ( is_array( $account ) && ! isset( $account['status'] ) && ! isset( $account['error'] ) ) ) {
				$valid_account = true;
				$validation_details[] = 'API key validated successfully via /forms endpoint';
			} else {
				$validation_details[] = 'Unexpected response structure';
				$validation_details[] = 'Available keys: ' . implode( ', ', array_keys( $account ) );
			}
		} else {
			$validation_details[] = 'Empty response - treating as valid (empty forms list)';
			$valid_account = true; // Empty response might mean no forms, but API key is valid
		}

		// Debug: Log validation details
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'NGL_Kit add_integration: Validation details: ' . implode( ' | ', $validation_details ) );
			error_log( 'NGL_Kit add_integration: Valid account: ' . ( $valid_account ? 'YES' : 'NO' ) );
		}

		if ( ! $valid_account ) {

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'NGL_Kit add_integration: Connection failed - invalid account structure' );
				error_log( 'NGL_Kit add_integration: Full account response for debugging: ' . print_r( $account, true ) );
			}

			$this->remove_integration();

			$result = array( 
				'response' => 'invalid',
				'message' => __( 'Invalid API key or account structure. Please check your API key and try again.', 'newsletter-glue' ),
				'debug'   => defined( 'WP_DEBUG' ) && WP_DEBUG ? $validation_details : array(),
			);

			delete_option( 'newsletterglue_kit' );

		} else {

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'NGL_Kit add_integration: Connection successful' );
			}

			// Save the cleaned API key, not the original
			$this->save_integration( $clean_api_key, $account );

			$result = array( 'response' => 'successful' );

			update_option( 'newsletterglue_kit', $account );

		}

		return $result;
	}

	/**
	 * Save Integration.
	 */
	public function save_integration( $api_key = '', $account = '' ) {

		delete_option( 'newsletterglue_integrations' );

		$integrations = get_option( 'newsletterglue_integrations' );

		$integrations[ $this->app ] = array();
		$integrations[ $this->app ][ 'api_key' ] = $api_key;

		update_option( 'newsletterglue_integrations', $integrations );

		// Add default options.
		$globals = get_option( 'newsletterglue_options' );
		$options = ! empty( $globals ) && isset( $globals[ $this->app ] ) ? $globals[ $this->app ] : '';

		if ( ! $options ) {

			$globals[ $this->app ] = array(
				'from_name' 	=> newsletterglue_get_default_from_name(),
				'from_email'	=> isset( $account[ 'account' ][ 'email' ] ) ? $account[ 'account' ][ 'email' ] : '',
			);

			update_option( 'newsletterglue_options', $globals );

		}
	}

	/**
	 * Connect.
	 */
	public function connect() {

		$this->api = new NGL_Kit_API( $this->api_key );

	}

	/**
	 * Get form defaults.
	 */
	public function get_form_defaults() {

		$this->api = new NGL_Kit_API( $this->api_key );

		$defaults[ 'audiences' ] = $this->get_audiences();

		return $defaults;
	}

	/**
	 * Get default list ID.
	 */
	public function get_default_list_id() {
		$audiences = array();

		$this->api = new NGL_Kit_API( $this->api_key );

		$audiences = $this->get_audiences();

		if ( ! empty( $audiences ) ) {
			return array_keys( $audiences )[0];
		}

		return '';
	}

	/**
	 * Get audiences (Forms).
	 */
	public function get_audiences() {
		$audiences = array();

		if ( ! $this->api_key ) {
			return $audiences;
		}

		$this->api = new NGL_Kit_API( $this->api_key );

		$forms = $this->api->get_forms();

		if ( ! empty( $forms ) && isset( $forms[ 'forms' ] ) ) {
			foreach( $forms[ 'forms' ] as $form ) {
				$audiences[ $form[ 'id' ] ] = $form[ 'name' ];
			}
		}

		return $audiences;
	}

	/**
	 * Get segments (Tags).
	 */
	public function get_segments( $audience_id = '' ) {

		$segments = array( '_everyone' => __( 'Everyone', 'newsletter-glue' ) );

		if ( ! $this->api_key ) {
			return $segments;
		}

		$this->api = new NGL_Kit_API( $this->api_key );

		$tags = $this->api->get_tags();

		if ( ! empty( $tags ) && isset( $tags[ 'tags' ] ) ) {
			foreach( $tags[ 'tags' ] as $tag ) {
				$segments[ $tag[ 'id' ] ] = $tag[ 'name' ];
			}
		}

		return $segments;

	}

	/**
	 * Send newsletter.
	 */
	public function send_newsletter( $post_id = 0, $data = array(), $test = false ) {

		// Use a transient to prevent concurrent sends (clears automatically after 5 minutes).
		$lock_key = 'ngl_send_in_progress_' . $post_id;
		if ( get_transient( $lock_key ) ) {
			return;
		}

		// Set lock for 5 minutes (should be more than enough for any send operation).
		set_transient( $lock_key, true, 5 * MINUTE_IN_SECONDS );

		$post = get_post( $post_id );

		// If no data was provided. Get it from the post.
		if ( empty( $data ) ) {
			$data = get_post_meta( $post_id, '_newsletterglue', true );
		}

		$subject 		= isset( $data['subject'] ) ? urldecode( $data['subject'] ) : urldecode( $post->post_title );
		$from_name		= isset( $data['from_name'] ) ? $data['from_name'] : newsletterglue_get_default_from_name();
		$from_email		= isset( $data['from_email'] ) ? $data['from_email'] : $this->get_current_user_email();
		$audience		= isset( $data['audience'] ) ? $data['audience'] : $this->get_default_list_id();
		$segment		= isset( $data['segment'] ) && $data['segment'] && ( $data['segment'] != '_everyone' ) ? $data['segment'] : '';
		$schedule  	 	= isset( $data['schedule'] ) ? $data['schedule'] : 'immediately';

		if ( $test ) {
			if ( $this->is_invalid_email( $data[ 'test_email' ] ) ) {
				delete_transient( $lock_key );
				return $this->is_invalid_email( $data[ 'test_email' ] );
			}
		}

		// API request.
		$this->api = new NGL_Kit_API( $this->api_key );

		// Empty content.
		if ( $test && isset( $post->post_status ) && $post->post_status === 'auto-draft' ) {

			delete_transient( $lock_key );
			$response['fail'] = $this->nothing_to_send();

			return $response;
		}

		// Generate email HTML content.
		$html_content = newsletterglue_generate_content( $post, $subject, $this->app );

		// Create broadcast data for Kit API v4.
		$broadcast_data = array(
			'subject'      => $subject,
			'content'      => $html_content,
			'description'  => sprintf( __( 'Newsletter: %s', 'newsletter-glue' ), $subject ),
			'public'       => false,
		);

		// Add preview_text if available (optional field)
		$preview_text = get_post_meta( $post_id, '_newsletterglue_preview_text', true );
		if ( ! empty( $preview_text ) ) {
			$broadcast_data['preview_text'] = $preview_text;
		}

		// Set send_at based on test vs actual send.
		if ( $test ) {
			// For test emails, keep as draft (null send_at)
			$broadcast_data['send_at'] = null;
		} elseif ( $schedule === 'immediately' ) {
			// Send immediately - set to current time
			$broadcast_data['send_at'] = gmdate( 'c' );
		} else {
			// Scheduled send - keep as draft for now (scheduling will be handled separately)
			$broadcast_data['send_at'] = null;
		}

		// Note: email_address is not sent to Kit API - Kit uses account default
		// The from_email is stored in options for wp_mail fallback only
		
		// Add tag (segment) if specified - this is what Kit uses for targeting
		// Forms are for audience selection but targeting is done via tags
		if ( $segment ) {
			$broadcast_data['tag_id'] = $segment;
		}

		// Debug: Log broadcast data
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'NGL_Kit send_newsletter: Broadcast data (test=' . ( $test ? 'true' : 'false' ) . '): ' . print_r( $broadcast_data, true ) );
		}

		$broadcast = $this->api->create_broadcast( $broadcast_data );

		if ( ! $broadcast || ! isset( $broadcast[ 'broadcast' ] ) || ! isset( $broadcast[ 'broadcast' ][ 'id' ] ) ) {

			delete_transient( $lock_key );

			$error_result = array(
				'status'  => 500,
				'type'    => 'error',
				'message' => __( 'Failed to create broadcast', 'newsletter-glue' ),
			);

			if ( isset( $broadcast[ 'message' ] ) ) {
				$error_result[ 'message' ] = esc_html( $broadcast[ 'message' ] );
			}

			newsletterglue_add_campaign_data( $post_id, $subject, $error_result );

			return $error_result;

		}

		$broadcast_id = $broadcast[ 'broadcast' ][ 'id' ];

		// Handle test email.
		if ( $test ) {

			// Debug: Log test email attempt
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'NGL_Kit send_newsletter: Sending test email to: ' . $data['test_email'] . ' (broadcast_id: ' . $broadcast_id . ')' );
			}

			// Try to send test email via Kit API test endpoint
			$test_result = $this->api->send_test( $broadcast_id, $data['test_email'] );

			// Debug: Log test result
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'NGL_Kit send_newsletter: Test email result: ' . print_r( $test_result, true ) );
			}

			// Check if test endpoint exists and worked
			$test_failed = false;
			if ( isset( $test_result[ 'error' ] ) || 
			     ( isset( $test_result[ 'status' ] ) && is_numeric( $test_result[ 'status' ] ) && $test_result[ 'status' ] >= 400 ) ||
			     ( isset( $test_result[ 'errors' ] ) && ! empty( $test_result[ 'errors' ] ) ) ) {
				$test_failed = true;
			}

			// If test endpoint doesn't exist (404) or failed, try alternative: create a temporary broadcast with test email as subscriber
			if ( $test_failed || ( isset( $test_result[ 'status' ] ) && $test_result[ 'status' ] == 404 ) ) {
				
				// Alternative approach: Use wp_mail as fallback (like Sendy, GetResponse, etc.)
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'NGL_Kit send_newsletter: Test endpoint failed or not available, using wp_mail fallback' );
				}

				// Use WordPress wp_mail to send test email with configured from_name and from_email
				add_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );
				add_filter( 'wp_mail_from', array( $this, 'wp_mail_from' ) );
				add_filter( 'wp_mail_from_name', array( $this, 'wp_mail_from_name' ) );
				
				$test_subject = sprintf( __( '[Test] %s', 'newsletter-glue' ), $subject );
				$test_body = $html_content;
				
				$wp_mail_result = wp_mail( $data['test_email'], $test_subject, $test_body );
				
				remove_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );
				remove_filter( 'wp_mail_from', array( $this, 'wp_mail_from' ) );
				remove_filter( 'wp_mail_from_name', array( $this, 'wp_mail_from_name' ) );

				// Delete test broadcast.
				$this->api->delete_broadcast( $broadcast_id );

				delete_transient( $lock_key );

				if ( ! $wp_mail_result ) {
					return array( 'fail' => __( 'Failed to send test email via WordPress mail', 'newsletter-glue' ) );
				}

				return array( 'success' => $this->get_test_success_msg() );
			}

			// Test endpoint worked - delete broadcast and return success
			$this->api->delete_broadcast( $broadcast_id );

			delete_transient( $lock_key );

			return array( 'success' => $this->get_test_success_msg() );

		}

		// Handle actual send.
		if ( $schedule === 'immediately' ) {

			$send_result = $this->api->send_broadcast( $broadcast_id );

		} else {

			// Schedule for later - Kit doesn't support scheduling, so save as draft.
			$send_result = array( 'status' => 'draft' );

		}

		// Process response and log.
		$result = $this->prepare_message( $send_result );

		if ( ! $test ) {
			newsletterglue_add_campaign_data( $post_id, $subject, $result, $broadcast_id );
		}

		delete_transient( $lock_key );

		return $result;

	}

	/**
	 * Prepare result for plugin.
	 */
	public function prepare_message( $result ) {
		$output = array();

		if ( isset( $result['status'] ) ) {

			if ( $result['status'] == 'draft' ) {
				$output['status']		= 200;
				$output['type']		= 'neutral';
				$output['message']    = __( 'Saved as draft', 'newsletter-glue' );
			} elseif ( $result['status'] == 200 || $result['status'] == 'success' ) {
				$output['status']  = 200;
				$output['type']    = 'success';
				$output['message'] = __( 'Sent', 'newsletter-glue' );
			} else {
				$output['status']  = isset( $result['status'] ) ? $result['status'] : 500;
				$output['type']    = 'error';
				$output['message'] = isset( $result['message'] ) ? esc_html( $result['message'] ) : __( 'Unknown error', 'newsletter-glue' );
			}

		} else {

			if ( isset( $result[ 'broadcast' ] ) && isset( $result[ 'broadcast' ][ 'id' ] ) ) {
				$output['status'] 	= 200;
				$output['type']  	= 'success';
				$output['message'] 	= __( 'Sent', 'newsletter-glue' );
			} else {
				$output['status']  = 200;
				$output['type']    = 'success';
				$output['message'] = __( 'Sent', 'newsletter-glue' );
			}

		}

		return $output;
	}

	/**
	 * Get connect settings.
	 */
	public function get_connect_settings( $integrations = array() ) {

		$app = $this->app;

		newsletterglue_text_field( array(
			'id' 			=> "ngl_{$app}_key",
			'placeholder' 	=> esc_html__( 'Enter API Key', 'newsletter-glue' ),
			'value'			=> isset( $integrations[ $app ]['api_key'] ) ? $integrations[ $app ]['api_key'] : '',
			'helper'		=> '<a href="https://app.kit.com/account_settings/advanced" target="_blank">' . __( 'Get API key', 'newsletter-glue' ) . ' <i class="arrow right icon"></i></a>',
		) );

	}

	/**
	 * Replace universal tags with esp tags.
	 */
	public function html_content( $html, $post_id ) {

		$html = $this->convert_tags( $html, $post_id );

		return $html;
	}

	/**
	 * Code supported tags for this ESP.
	 */
	public function get_tag( $tag, $post_id = 0, $fallback = null ) {

		switch ( $tag ) {
			case 'unsubscribe_link' :
				return '{{ unsubscribe_url }}';
			break;
			case 'first_name' :
				return '{{ subscriber.first_name }}';
			break;
			case 'last_name' :
				return '{{ subscriber.last_name }}';
			break;
			case 'email' :
				return '{{ subscriber.email_address }}';
			break;
			case 'update_preferences' :
				return '{{ subscriber.account_url }}';
			break;
			default :
				return apply_filters( "newsletterglue_{$this->app}_custom_tag", '', $tag, $post_id );
			break;
		}

		return false;
	}

	/**
	 * Get lists compat.
	 */
	public function _get_lists_compat() {
		$this->api = new NGL_Kit_API( $this->api_key );
		return $this->get_audiences();
	}

	/**
	 * Get audience label (Form for Kit).
	 */
	public function get_audience_label() {
		return __( 'Form', 'newsletter-glue' );
	}

	/**
	 * Get segment label (Tag for Kit).
	 */
	public function get_segment_label() {
		return __( 'Tag', 'newsletter-glue' );
	}

	/**
	 * Get create tag link URL.
	 */
	public function get_create_tag_url() {
		return 'https://app.kit.com/subscribers';
	}

	/**
	 * Set email content type to HTML for wp_mail.
	 */
	public function wp_mail_content_type() {
		return 'text/html';
	}

	/**
	 * Set from email address for wp_mail.
	 */
	public function wp_mail_from( $from_email ) {
		$options = get_option( 'newsletterglue_options' );
		$kit_options = isset( $options[ $this->app ] ) ? $options[ $this->app ] : array();
		$configured_email = isset( $kit_options['from_email'] ) ? $kit_options['from_email'] : '';
		
		if ( ! empty( $configured_email ) && is_email( $configured_email ) ) {
			return $configured_email;
		}
		
		return $from_email;
	}

	/**
	 * Set from name for wp_mail.
	 */
	public function wp_mail_from_name( $from_name ) {
		$options = get_option( 'newsletterglue_options' );
		$kit_options = isset( $options[ $this->app ] ) ? $options[ $this->app ] : array();
		$configured_name = isset( $kit_options['from_name'] ) ? $kit_options['from_name'] : '';
		
		if ( ! empty( $configured_name ) ) {
			return $configured_name;
		}
		
		return $from_name;
	}

}

