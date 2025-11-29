<?php
/**
 * Kit API Client.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Kit API Class.
 */
class NGL_Kit_API {

	private $api_key;
	private $api_url = 'https://api.kit.com/v4/';

	const TIMEOUT = 30;

	/**
	 * Create a new instance.
	 *
	 * @param string $api_key Your Kit API key
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Make API request.
	 *
	 * @param string $endpoint API endpoint
	 * @param string $method HTTP method
	 * @param array  $body Request body
	 * @return array|WP_Error Response data or error
	 */
	private function request( $endpoint, $method = 'GET', $body = array() ) {

		$url = $this->api_url . ltrim( $endpoint, '/' );

		// Kit API v4 authentication - uses X-Kit-Api-Key header
		// Based on Kit API documentation: https://developers.kit.com/api-reference/broadcasts/create-a-broadcast
		$args = array(
			'method'  => $method,
			'timeout' => self::TIMEOUT,
			'headers' => array(
				'X-Kit-Api-Key' => $this->api_key,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
		);

		if ( ! empty( $body ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ) ) ) {
			$args['body'] = json_encode( $body );
		}

		// Debug: Log request details
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'NGL_Kit_API Request: ' . $method . ' ' . $url );
			error_log( 'NGL_Kit_API Headers: ' . print_r( $args['headers'], true ) );
			if ( ! empty( $body ) ) {
				error_log( 'NGL_Kit_API Body: ' . print_r( $body, true ) );
			}
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'NGL_Kit_API WP_Error: ' . $error_message );
			}
			return array(
				'status'  => 'error',
				'message' => $error_message,
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		// Debug: Log response details
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'NGL_Kit_API Response Code: ' . $response_code );
			error_log( 'NGL_Kit_API Response Body: ' . $response_body );
			error_log( 'NGL_Kit_API Decoded Data: ' . print_r( $data, true ) );
		}

		if ( $response_code >= 200 && $response_code < 300 ) {
			return $data;
		}

		// Extract error message from response
		$error_message = __( 'API request failed', 'newsletter-glue' );
		if ( isset( $data['errors'] ) && is_array( $data['errors'] ) && ! empty( $data['errors'] ) ) {
			$error_message = is_array( $data['errors'][0] ) ? json_encode( $data['errors'][0] ) : $data['errors'][0];
		} elseif ( isset( $data['message'] ) ) {
			$error_message = $data['message'];
		} elseif ( isset( $data['error'] ) ) {
			$error_message = is_array( $data['error'] ) ? json_encode( $data['error'] ) : $data['error'];
		}

		return array(
			'status'  => $response_code,
			'message' => $error_message,
			'data'    => $data,
		);
	}

	/**
	 * Get account information.
	 *
	 * @return array Account data
	 */
	public function get_account() {
		// Kit API v4 - validate API key by making a simple request
		// We'll use /forms endpoint as it's lightweight and validates the key
		// If that doesn't work, we can try other endpoints
		$result = $this->request( 'forms' );
		
		// If forms endpoint works, the API key is valid
		// We don't need the actual account data, just validation
		return $result;
	}

	/**
	 * Get forms (audiences).
	 *
	 * @return array Forms data
	 */
	public function get_forms() {
		return $this->request( 'forms' );
	}

	/**
	 * Get tags (segments).
	 *
	 * @return array Tags data
	 */
	public function get_tags() {
		return $this->request( 'tags' );
	}

	/**
	 * Create a broadcast.
	 *
	 * @param array $data Broadcast data
	 * @return array Broadcast response
	 */
	public function create_broadcast( $data ) {
		$broadcast_data = array(
			'email_address' => isset( $data['email_address'] ) ? $data['email_address'] : '',
			'name'          => isset( $data['name'] ) ? $data['name'] : '',
			'subject'       => isset( $data['subject'] ) ? $data['subject'] : '',
			'content'       => isset( $data['content'] ) ? $data['content'] : '',
		);

		// Add form_id if specified (for audience targeting).
		if ( isset( $data['form_id'] ) && ! empty( $data['form_id'] ) ) {
			$broadcast_data['form_id'] = intval( $data['form_id'] );
		}

		// Add tag_id if specified (for segment targeting).
		if ( isset( $data['tag_id'] ) && ! empty( $data['tag_id'] ) ) {
			$broadcast_data['tag_id'] = intval( $data['tag_id'] );
		}

		return $this->request( 'broadcasts', 'POST', $broadcast_data );
	}

	/**
	 * Send a broadcast.
	 *
	 * @param int $broadcast_id Broadcast ID
	 * @return array Send response
	 */
	public function send_broadcast( $broadcast_id ) {
		return $this->request( 'broadcasts/' . intval( $broadcast_id ) . '/send', 'POST' );
	}

	/**
	 * Send test email.
	 *
	 * @param int    $broadcast_id Broadcast ID
	 * @param string $email Test email address
	 * @return array Test send response
	 */
	public function send_test( $broadcast_id, $email ) {
		return $this->request( 'broadcasts/' . intval( $broadcast_id ) . '/test', 'POST', array(
			'email' => $email,
		) );
	}

	/**
	 * Delete a broadcast.
	 *
	 * @param int $broadcast_id Broadcast ID
	 * @return array Delete response
	 */
	public function delete_broadcast( $broadcast_id ) {
		return $this->request( 'broadcasts/' . intval( $broadcast_id ), 'DELETE' );
	}

	/**
	 * Get a broadcast.
	 *
	 * @param int $broadcast_id Broadcast ID
	 * @return array Broadcast data
	 */
	public function get_broadcast( $broadcast_id ) {
		return $this->request( 'broadcasts/' . intval( $broadcast_id ) );
	}

}

