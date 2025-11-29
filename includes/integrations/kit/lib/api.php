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

		$args = array(
			'method'  => $method,
			'timeout' => self::TIMEOUT,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
		);

		if ( ! empty( $body ) && in_array( $method, array( 'POST', 'PUT', 'PATCH' ) ) ) {
			$args['body'] = json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'status'  => 'error',
				'message' => $response->get_error_message(),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		if ( $response_code >= 200 && $response_code < 300 ) {
			return $data;
		}

		return array(
			'status'  => $response_code,
			'message' => isset( $data['message'] ) ? $data['message'] : __( 'API request failed', 'newsletter-glue' ),
			'data'    => $data,
		);
	}

	/**
	 * Get account information.
	 *
	 * @return array Account data
	 */
	public function get_account() {
		return $this->request( 'account' );
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

