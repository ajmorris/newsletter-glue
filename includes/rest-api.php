<?php
/**
 * REST API support for Newsletter Glue.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register meta fields for REST API access.
 */
function newsletterglue_register_rest_meta_fields() {

	// Get post types that support newsletter sending.
	$saved_types = get_option( 'newsletterglue_post_types' );
	$post_types = array( 'newsletterglue', 'ngl_pattern' );

	if ( ! empty( $saved_types ) ) {
		$custom_types = explode( ',', $saved_types );
		$post_types = array_merge( $post_types, $custom_types );
	} else {
		$core_types = apply_filters( 'newsletterglue_supported_core_types', array() );
		$post_types = array_merge( $post_types, $core_types );
	}

	// Register _newsletterglue meta field for REST API.
	foreach ( $post_types as $post_type ) {
		register_post_meta( $post_type, '_newsletterglue', array(
			'show_in_rest' => array(
				'schema' => array(
					'type'       => 'object',
					'properties' => array(),
					'additionalProperties' => true,
				),
			),
			'single'       => true,
			'type'         => 'object',
			'auth_callback' => function() {
				return current_user_can( 'manage_newsletterglue' );
			},
		) );

		// Register future send meta.
		register_post_meta( $post_type, '_ngl_future_send', array(
			'show_in_rest' => true,
			'single'       => true,
			'type'         => 'string',
			'auth_callback' => function() {
				return current_user_can( 'manage_newsletterglue' );
			},
		) );
	}
}
add_action( 'init', 'newsletterglue_register_rest_meta_fields' );

/**
 * Get newsletter form defaults via REST API.
 */
function newsletterglue_rest_get_defaults( $request ) {
	
	if ( ! current_user_can( 'manage_newsletterglue' ) ) {
		return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have permission to access this endpoint.', 'newsletter-glue' ), array( 'status' => 401 ) );
	}

	$post_id = $request->get_param( 'post_id' );
	
	if ( ! $post_id ) {
		return new WP_Error( 'rest_invalid_param', esc_html__( 'Invalid post ID.', 'newsletter-glue' ), array( 'status' => 400 ) );
	}

	$post = get_post( $post_id );
	if ( ! $post ) {
		return new WP_Error( 'rest_invalid_param', esc_html__( 'Invalid post.', 'newsletter-glue' ), array( 'status' => 400 ) );
	}

	$app = newsletterglue_default_connection();
	
	if ( ! $app ) {
		return array(
			'connected' => false,
			'app' => null,
		);
	}

	include_once newsletterglue_get_path( $app ) . '/init.php';

	$class = 'NGL_' . ucfirst( $app );
	$api = new $class();
	
	// Connect the API first.
	if ( method_exists( $api, 'connect' ) ) {
		$api->connect();
	}
	
	$defaults = newsletterglue_get_form_defaults( $post, $api );
	$settings = newsletterglue_get_data( $post_id );

	// Get audiences - they might be in the defaults object or we need to fetch them.
	$audiences = array();
	if ( isset( $defaults->audiences ) && is_array( $defaults->audiences ) ) {
		$audiences = $defaults->audiences;
	} else if ( method_exists( $api, '_get_lists_compat' ) ) {
		$audiences = $api->_get_lists_compat();
	} else if ( method_exists( $api, 'get_audiences' ) ) {
		$audiences = $api->get_audiences();
	}

	// Get the currently selected audience.
	$current_audience = '';
	if ( isset( $settings->audience ) ) {
		$current_audience = $settings->audience;
	} else if ( isset( $defaults->audience ) ) {
		$current_audience = $defaults->audience;
	}

	return array(
		'connected' => true,
		'app' => $app,
		'appName' => newsletterglue_get_name( $app ),
		'defaults' => $defaults,
		'settings' => $settings,
		'audiences' => $audiences,
		'current_audience' => $current_audience,
		'segments' => array(),
	);
}

/**
 * Get segments for an audience via REST API.
 */
function newsletterglue_rest_get_segments( $request ) {
	
	if ( ! current_user_can( 'manage_newsletterglue' ) ) {
		return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have permission to access this endpoint.', 'newsletter-glue' ), array( 'status' => 401 ) );
	}

	$audience = $request->get_param( 'audience' );
	
	if ( ! $audience ) {
		return array();
	}

	$app = newsletterglue_default_connection();
	
	if ( ! $app ) {
		return array();
	}

	include_once newsletterglue_get_path( $app ) . '/init.php';

	$class = 'NGL_' . ucfirst( $app );
	$api = new $class();
	
	// Connect the API first.
	if ( method_exists( $api, 'connect' ) ) {
		$api->connect();
	}
	
	if ( method_exists( $api, 'get_segments' ) ) {
		return $api->get_segments( $audience );
	}

	return array();
}

/**
 * Get audience name via REST API.
 */
function newsletterglue_rest_get_audience_name( $request ) {
	
	if ( ! current_user_can( 'manage_newsletterglue' ) ) {
		return new WP_Error( 'rest_forbidden', esc_html__( 'You do not have permission to access this endpoint.', 'newsletter-glue' ), array( 'status' => 401 ) );
	}

	$audience_id = $request->get_param( 'audience_id' );
	
	if ( ! $audience_id ) {
		return array( 'name' => '' );
	}

	$app = newsletterglue_default_connection();
	
	if ( ! $app ) {
		return array( 'name' => '' );
	}

	include_once newsletterglue_get_path( $app ) . '/init.php';

	$class = 'NGL_' . ucfirst( $app );
	$api = new $class();
	
	// Connect the API first.
	if ( method_exists( $api, 'connect' ) ) {
		$api->connect();
	}
	
	// Try to get lists using _get_lists_compat or other methods.
	$lists = array();
	if ( method_exists( $api, '_get_lists_compat' ) ) {
		$lists = $api->_get_lists_compat();
	} else if ( method_exists( $api, 'get_audiences' ) ) {
		$lists = $api->get_audiences();
	}
	
	$name = isset( $lists[ $audience_id ] ) ? $lists[ $audience_id ] : $audience_id;
	
	return array( 
		'name' => $name,
		'id' => $audience_id
	);
}

/**
 * Register REST routes.
 */
function newsletterglue_register_rest_routes() {
	register_rest_route( 'newsletterglue/v1', '/defaults/(?P<post_id>\d+)', array(
		'methods'  => 'GET',
		'callback' => 'newsletterglue_rest_get_defaults',
		'permission_callback' => function() {
			return current_user_can( 'manage_newsletterglue' );
		},
	) );

	register_rest_route( 'newsletterglue/v1', '/segments', array(
		'methods'  => 'GET',
		'callback' => 'newsletterglue_rest_get_segments',
		'permission_callback' => function() {
			return current_user_can( 'manage_newsletterglue' );
		},
	) );

	register_rest_route( 'newsletterglue/v1', '/audience-name', array(
		'methods'  => 'GET',
		'callback' => 'newsletterglue_rest_get_audience_name',
		'permission_callback' => function() {
			return current_user_can( 'manage_newsletterglue' );
		},
	) );
}
add_action( 'rest_api_init', 'newsletterglue_register_rest_routes' );

/**
 * Handle newsletter sending after REST API meta update.
 * This ensures we catch panel mode saves that happen via REST API.
 */
function newsletterglue_rest_after_insert_post( $post, $request, $creating ) {
	
	// Only handle updates, not creates (creates are drafts).
	if ( $creating ) {
		return;
	}
	
	// Get settings location.
	$settings_location = get_option( 'newsletterglue_editor_settings_location', 'metabox' );
	
	// Only process if using panel mode.
	if ( $settings_location !== 'panel' ) {
		return;
	}
	
	// Use the unified function to process newsletter sending.
	newsletterglue_process_newsletter_send( $post->ID, $post, 'panel' );
	
}
add_action( 'rest_after_insert_post', 'newsletterglue_rest_after_insert_post', 10, 3 );
add_action( 'rest_after_insert_page', 'newsletterglue_rest_after_insert_post', 10, 3 );

