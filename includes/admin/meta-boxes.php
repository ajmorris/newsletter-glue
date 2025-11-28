<?php
/**
 * Metaboxes.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add meta box support.
 */
function newsletterglue_add_meta_box() {

	// Check if the user prefers panel over metabox.
	$settings_location = get_option( 'newsletterglue_editor_settings_location', 'metabox' );
	
	// Only add metabox if the setting is set to 'metabox'.
	if ( $settings_location !== 'metabox' ) {
		return;
	}

	$unsupported = array();
	$saved_types = get_option( 'newsletterglue_post_types' );

	if ( ! empty( $saved_types ) ) {
		$post_types = explode( ',', $saved_types );
	} else {
		$post_types = apply_filters( 'newsletterglue_supported_core_types', array() );
	}

	$post_types = array_merge( $post_types, array( 'newsletterglue', 'ngl_pattern' ) );

	if ( is_array( $post_types ) ) {
		foreach( $post_types as $post_type ) {
			if ( ! in_array( $post_type, apply_filters( 'newsletterglue_unsupported_post_types', $unsupported ) ) ) {
				add_meta_box( 'newsletter_glue_metabox', __( 'Newsletter Glue: Send as newsletter', 'newsletter-glue' ), 'newsletterglue_meta_box', $post_type, 'normal', 'high' );
			}
		}
	}

}
add_action( 'add_meta_boxes', 'newsletterglue_add_meta_box', 1 );

/**
 * Save meta box.
 */
function newsletterglue_save_meta_box( $post_id, $post ) {

	// Get settings location.
	$settings_location = get_option( 'newsletterglue_editor_settings_location', 'metabox' );
	
	// Only process meta box mode here (panel mode is handled by REST API hook).
	if ( $settings_location === 'metabox' ) {
		newsletterglue_process_newsletter_send( $post_id, $post, 'metabox' );
	}
	
}
add_action( 'save_post', 'newsletterglue_save_meta_box', 20, 2 );

/**
 * Shows the metabox content.
 */
function newsletterglue_meta_box() {
	global $post, $the_lists, $post_type;

	$settings   = newsletterglue_get_data( $post->ID );

	if ( ! $app = newsletterglue_default_connection() ) {

		$app 		= 'mailchimp';

		include_once newsletterglue_get_path( $app ) . '/init.php';

		$class 		= 'NGL_' . ucfirst( $app );
		$api   		= new $class();
		$defaults 	= newsletterglue_get_form_defaults( $post, $api );

		include( 'metabox/views/connect.php' );

	} else {

		include_once newsletterglue_get_path( $app ) . '/init.php';

		$class 		= 'NGL_' . ucfirst( $app );
		$api   		= new $class();
		$defaults 	= newsletterglue_get_form_defaults( $post, $api );

		$hide = false;

		if ( ! isset( $settings->sent ) ) {
			$hide = true;
		}

		if ( get_post_meta( $post->ID, '_ngl_future_send', true ) ) {
			$hide = false;
		}

		include( 'metabox/views/before-metabox.php' );

		if ( $post_type != 'ngl_pattern' ) {
			include newsletterglue_get_path( $app ) . '/metabox.php';
		}

		include( 'metabox/views/after-metabox.php' );

		wp_nonce_field( 'newsletterglue_save_data', 'newsletterglue_meta_nonce' );

	}

}