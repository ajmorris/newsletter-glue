<?php
/**
 * wp_mail() Metabox.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// $app, $api, $settings, $defaults, $post are expected from caller.

// Fallbacks.
$audiences      = isset( $defaults->audiences ) ? $defaults->audiences : array();
$selected_roles = array();

if ( isset( $settings->audience ) ) {
	$selected_roles = (array) $settings->audience;
} else {
	$saved = newsletterglue_get_option( 'audiences', $app );
	if ( is_array( $saved ) ) {
		$selected_roles = $saved;
	}
}

if ( empty( $selected_roles ) && ! empty( $audiences ) ) {
	$keys           = array_keys( $audiences );
	$selected_roles = array( $keys[0] );
}

$saved_excluded_roles = array();
if ( isset( $settings->excluded_roles ) ) {
	$saved_excluded_roles = (array) $settings->excluded_roles;
} else {
	$global_excluded = newsletterglue_get_option( 'excluded_roles', $app );
	if ( is_array( $global_excluded ) ) {
		$saved_excluded_roles = $global_excluded;
	}
}

$default_excluded = array();
if ( method_exists( $api, 'get_default_excluded_roles' ) ) {
	$default_excluded = $api->get_default_excluded_roles();
}

if ( empty( $saved_excluded_roles ) ) {
	$saved_excluded_roles = $default_excluded;
}

// Build role options from global WordPress roles.
if ( function_exists( 'wp_roles' ) ) {
	$wp_roles = wp_roles();
	$roles    = isset( $wp_roles->roles ) ? $wp_roles->roles : array();
	$role_options = array();

	if ( ! empty( $roles ) ) {
		foreach ( $roles as $role_key => $role_data ) {
			$label                    = isset( $role_data['name'] ) ? $role_data['name'] : $role_key;
			$role_options[ $role_key ] = $label;
		}
	}
} else {
	$role_options = array();
}

?>

<div class="ngl-metabox-flex">

	<div class="ngl-metabox-flex">
		<div class="ngl-metabox-header">
			<label for="ngl_audience"><?php esc_html_e( 'Audience roles', 'newsletter-glue' ); ?></label>
		</div>
		<div class="ngl-field">
			<?php
				newsletterglue_select_field(
					array(
						'id'       => 'ngl_audience',
						'legacy'   => true,
						'multiple' => true,
						'helper'   => __( 'WordPress roles that should receive this newsletter.', 'newsletter-glue' ),
						'class'    => 'is-required',
						'options'  => $role_options,
						'default'  => $selected_roles,
					)
				);
			?>
		</div>
	</div>

	<div class="ngl-metabox-flex ngl-metabox-segment">
		<div class="ngl-metabox-header">
			<label for="ngl_excluded_roles"><?php esc_html_e( 'Excluded roles', 'newsletter-glue' ); ?></label>
			<?php echo $api->show_loading(); ?>
		</div>
		<div class="ngl-field">
			<?php
				newsletterglue_select_field(
					array(
						'id'       => 'ngl_excluded_roles',
						'legacy'   => true,
						'multiple' => true,
						'helper'   => __( 'Users with these roles will be skipped (e.g. unsubscribed or bounced).', 'newsletter-glue' ),
						'options'  => $role_options,
						'default'  => $saved_excluded_roles,
					)
				);
			?>
		</div>
	</div>

</div>


