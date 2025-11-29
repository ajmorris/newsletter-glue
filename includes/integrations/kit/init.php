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

		$this->api = new NGL_Kit_API( $api_key );

		$account = $this->api->get_account();

		$valid_account = ! empty( $account ) && isset( $account[ 'account' ] ) && isset( $account[ 'account' ][ 'id' ] ) ? true : false;

		if ( ! $valid_account ) {

			$this->remove_integration();

			$result = array( 'response' => 'invalid' );

			delete_option( 'newsletterglue_kit' );

		} else {

			$this->save_integration( $api_key, $account );

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

		// Create broadcast.
		$broadcast_data = array(
			'email_address' => $from_email,
			'name'          => $from_name,
			'subject'       => $subject,
			'content'       => $html_content,
		);

		// Add form (audience) if specified.
		if ( $audience ) {
			$broadcast_data['form_id'] = $audience;
		}

		// Add tag (segment) if specified.
		if ( $segment ) {
			$broadcast_data['tag_id'] = $segment;
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

			$test_result = $this->api->send_test( $broadcast_id, $data['test_email'] );

			// Delete test broadcast.
			$this->api->delete_broadcast( $broadcast_id );

			delete_transient( $lock_key );

			if ( isset( $test_result[ 'error' ] ) || ( isset( $test_result[ 'status' ] ) && $test_result[ 'status' ] != 200 ) ) {
				$error_msg = isset( $test_result[ 'message' ] ) ? $test_result[ 'message' ] : __( 'Failed to send test email', 'newsletter-glue' );
				return array( 'fail' => $error_msg );
			}

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

}

