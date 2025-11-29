<?php
/**
 * wp_mail() integration.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'NGL_Abstract_Integration', false ) ) {
	include_once NGL_PLUGIN_DIR . 'includes/abstract-integration.php';
}

/**
 * Main Class.
 */
class NGL_Wp_Mail extends NGL_Abstract_Integration {

	/**
	 * App slug used internally and for folder name.
	 *
	 * @var string
	 */
	public $app = 'wp-mail';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// For consistency with other integrations, allow HTML content filters.
		add_filter( 'newsltterglue_wp-mail_html_content', array( $this, 'html_content' ), 10, 2 );
	}

	/**
	 * Get connect settings.
	 *
	 * wp_mail() does not need API keys, but we keep a minimal stub so the
	 * connect UI can render something if needed in the future.
	 *
	 * @param array $integrations Existing integrations.
	 */
	public function get_connect_settings( $integrations = array() ) {
		// Intentionally left mostly empty – wp_mail() has no remote API keys.
		// We could surface informational text via a helper/filter if desired.
	}

	/**
	 * Test emails are always sent via WordPress.
	 *
	 * @return bool
	 */
	public function test_email_by_wordpress() {
		return true;
	}

	/**
	 * Get available audiences.
	 *
	 * For wp_mail(), audiences are represented by WordPress roles.
	 * Returns array( 'role_key' => 'Role Label' ).
	 *
	 * @return array
	 */
	public function get_audiences() {
		if ( ! function_exists( 'wp_roles' ) ) {
			return array();
		}

		$wp_roles = wp_roles();

		if ( ! isset( $wp_roles->roles ) || empty( $wp_roles->roles ) ) {
			return array();
		}

		$roles        = $wp_roles->roles;
		$audiences    = array();
		$excluded_set = $this->get_default_excluded_roles();

		foreach ( $roles as $role_key => $role_data ) {
			// Skip roles that are clearly not mailing audiences.
			if ( in_array( $role_key, array( 'administrator', 'bh_ngl_unsubscribed', 'bounced_email' ), true ) ) {
				continue;
			}

			// Skip roles that are in the default excluded set.
			if ( in_array( $role_key, $excluded_set, true ) ) {
				continue;
			}

			$audiences[ $role_key ] = isset( $role_data['name'] ) ? $role_data['name'] : $role_key;
		}

		return $audiences;
	}

	/**
	 * Get default list ID (first available role key).
	 *
	 * @return string
	 */
	public function get_default_list_id() {
		$audiences = $this->get_audiences();

		if ( ! empty( $audiences ) ) {
			$keys = array_keys( $audiences );
			return $keys[0];
		}

		return '';
	}

	/**
	 * Get segments for a given audience.
	 *
	 * For wp_mail(), we keep this simple and only expose an Everyone option.
	 *
	 * @param string $audience_id Audience / role.
	 * @return array
	 */
	public function get_segments( $audience_id = '' ) {
		return array(
			'_everyone' => __( 'Everyone', 'newsletter-glue' ),
		);
	}

	/**
	 * Get lists compat (legacy helper).
	 *
	 * @return array|null
	 */
	public function _get_lists_compat() {
		return $this->get_audiences();
	}

	/**
	 * Get default excluded roles.
	 *
	 * This honours roles commonly used by other plugins to mark users as
	 * unsubscribed or bounced, such as:
	 * - bh_ngl_unsubscribed (bh-wp-ngl-wp-mail)
	 * - bounced_email (bh-wp-aws-ses-bounce-handler)
	 *
	 * @return array
	 */
	public function get_default_excluded_roles() {
		$defaults = array(
			'bh_ngl_unsubscribed',
			'bounced_email',
		);

		/**
		 * Filter default excluded roles for the wp_mail integration.
		 *
		 * @param array $defaults Default excluded roles.
		 */
		return apply_filters( 'newsletterglue_wp_mail_default_excluded_roles', $defaults );
	}

	/**
	 * Get settings (global per-app settings).
	 *
	 * @return stdClass
	 */
	public function get_settings() {
		$settings = new stdClass();

		$settings->audiences      = newsletterglue_get_option( 'audiences', $this->app );
		$settings->excluded_roles = newsletterglue_get_option( 'excluded_roles', $this->app );

		return $settings;
	}

	/**
	 * Get form defaults used by the editor metabox.
	 *
	 * @return array
	 */
	public function get_form_defaults() {
		$defaults            = array();
		$defaults['audiences'] = $this->get_audiences();

		return $defaults;
	}

	/**
	 * Replace universal tags with integration-specific tags.
	 *
	 * For wp_mail(), we mostly rely on the base convert_tags implementation.
	 *
	 * @param string $html    HTML content.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public function html_content( $html, $post_id ) {
		$html = $this->convert_tags( $html, $post_id );
		return $html;
	}

	/**
	 * Send newsletter using wp_mail() and WordPress roles as audiences.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Newsletter data.
	 * @param bool  $test    Whether this is a test send.
	 * @return array|null
	 */
	public function send_newsletter( $post_id = 0, $data = array(), $test = false ) {
		// Use a transient to prevent concurrent sends.
		$lock_key = 'ngl_send_in_progress_' . $post_id;
		if ( get_transient( $lock_key ) ) {
			return null;
		}

		set_transient( $lock_key, true, 5 * MINUTE_IN_SECONDS );

		$post = get_post( $post_id );

		// If no data was provided. Get it from the post.
		if ( empty( $data ) ) {
			$data = get_post_meta( $post_id, '_newsletterglue', true );
		}

		$subject     = isset( $data['subject'] ) ? urldecode( $data['subject'] ) : urldecode( $post->post_title );
		$from_name   = isset( $data['from_name'] ) ? $data['from_name'] : newsletterglue_get_default_from_name();
		$from_email  = isset( $data['from_email'] ) ? $data['from_email'] : $this->get_current_user_email();
		$audiences   = isset( $data['audience'] ) ? (array) $data['audience'] : (array) $this->get_default_list_id();
		$excluded    = isset( $data['excluded_roles'] ) ? (array) $data['excluded_roles'] : $this->get_default_excluded_roles();
		$schedule    = isset( $data['schedule'] ) ? $data['schedule'] : 'immediately';

		// Empty content check for test emails.
		if ( $test && isset( $post->post_status ) && 'auto-draft' === $post->post_status ) {
			delete_transient( $lock_key );
			$response['fail'] = $this->nothing_to_send();
			return $response;
		}

		// Validate test email address.
		if ( $test ) {
			$test_email = isset( $data['test_email'] ) ? $data['test_email'] : '';
			if ( $this->is_invalid_email( $test_email ) ) {
				delete_transient( $lock_key );
				return $this->is_invalid_email( $test_email );
			}
		}

		// Generate email content.
		$html_content = newsletterglue_generate_content( $post, $subject, $this->app );

		// For tests, send only one email.
		if ( $test ) {
			add_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );

			$headers = array();
			if ( $from_name && $from_email ) {
				$headers[] = 'From: ' . sprintf( '%s <%s>', $from_name, $from_email );
			}

			$success = wp_mail( $data['test_email'], sprintf( __( '[Test] %s', 'newsletter-glue' ), $subject ), $html_content, $headers );

			remove_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );

			delete_transient( $lock_key );

			if ( $success ) {
				return array(
					'success' => $this->get_test_success_msg(),
				);
			}

			return array(
				'fail' => __( 'Test email could not be sent.', 'newsletter-glue' ),
			);
		}

		// Only support immediate sends for now – no draft scheduling with wp_mail().
		if ( 'immediately' !== $schedule ) {
			delete_transient( $lock_key );

			$result = array(
				'status'  => 400,
				'type'    => 'error',
				'message' => __( 'Scheduling is not supported for wp_mail() integration.', 'newsletter-glue' ),
			);

			newsletterglue_add_campaign_data( $post_id, $subject, $result );

			return $result;
		}

		// Build recipient list from WordPress users / roles.
		$recipients = $this->get_recipients_from_roles( $audiences, $excluded );

		if ( empty( $recipients ) ) {
			delete_transient( $lock_key );

			$result = array(
				'status'  => 400,
				'type'    => 'error',
				'message' => __( 'No recipients found for the selected roles.', 'newsletter-glue' ),
			);

			newsletterglue_add_campaign_data( $post_id, $subject, $result );

			return $result;
		}

		add_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );

		$sent   = 0;
		$failed = 0;

		$headers = array();
		if ( $from_name && $from_email ) {
			$headers[] = 'From: ' . sprintf( '%s <%s>', $from_name, $from_email );
		}

		// Send to each recipient – keep this simple and rely on wp_mail() + any
		// queueing/plugins like WP Offload SES.
		foreach ( $recipients as $email ) {
			$success = wp_mail( $email, $subject, $html_content, $headers );

			if ( $success ) {
				$sent++;
			} else {
				$failed++;
			}
		}

		remove_filter( 'wp_mail_content_type', array( $this, 'wp_mail_content_type' ) );

		delete_transient( $lock_key );

		// Normalise response and log.
		$result = $this->prepare_message(
			array(
				'sent'   => $sent,
				'failed' => $failed,
			)
		);

		newsletterglue_add_campaign_data( $post_id, $subject, $result );

		return $result;
	}

	/**
	 * Build a recipient list from selected roles and excluded roles.
	 *
	 * @param array $included_roles Included role keys.
	 * @param array $excluded_roles Excluded role keys.
	 * @return array List of email addresses.
	 */
	protected function get_recipients_from_roles( $included_roles = array(), $excluded_roles = array() ) {
		$included_roles = array_filter( array_map( 'sanitize_key', (array) $included_roles ) );
		$excluded_roles = array_filter( array_map( 'sanitize_key', (array) $excluded_roles ) );

		if ( empty( $included_roles ) ) {
			return array();
		}

		$paged      = 1;
		$per_page   = 200;
		$recipients = array();

		do {
			$query = new WP_User_Query(
				array(
					'number'  => $per_page,
					'paged'   => $paged,
					'role__in' => $included_roles,
					'fields'  => array( 'ID', 'user_email', 'roles' ),
				)
			);

			$users = $query->get_results();

			if ( empty( $users ) ) {
				break;
			}

			foreach ( $users as $user ) {
				if ( empty( $user->user_email ) ) {
					continue;
				}

				// Exclude users with any excluded role.
				if ( ! empty( $excluded_roles ) && is_array( $user->roles ) && array_intersect( $excluded_roles, $user->roles ) ) {
					continue;
				}

				$recipients[ $user->user_email ] = $user->user_email;
			}

			$paged++;

			// Hard upper bound to avoid over-long sends on very large sites.
			if ( count( $recipients ) > 5000 ) {
				break;
			}
		} while ( true );

		return array_values( $recipients );
	}

	/**
	 * Prepare result for plugin.
	 *
	 * @param array $result Raw result with sent/failed counts.
	 * @return array
	 */
	public function prepare_message( $result ) {
		$sent   = isset( $result['sent'] ) ? (int) $result['sent'] : 0;
		$failed = isset( $result['failed'] ) ? (int) $result['failed'] : 0;

		if ( $sent > 0 && 0 === $failed ) {
			return array(
				'status'  => 200,
				'type'    => 'success',
				'message' => __( 'Sent', 'newsletter-glue' ),
				'extra'   => array(
					'sent'   => $sent,
					'failed' => $failed,
				),
			);
		}

		if ( $sent > 0 && $failed > 0 ) {
			return array(
				'status'  => 200,
				'type'    => 'neutral',
				'message' => sprintf(
					/* translators: 1: sent count, 2: failed count */
					__( 'Partially sent: %1$d sent, %2$d failed.', 'newsletter-glue' ),
					$sent,
					$failed
				),
				'extra'   => array(
					'sent'   => $sent,
					'failed' => $failed,
				),
			);
		}

		return array(
			'status'  => 500,
			'type'    => 'error',
			'message' => __( 'Email send failed.', 'newsletter-glue' ),
			'extra'   => array(
				'sent'   => $sent,
				'failed' => $failed,
			),
		);
	}
}


