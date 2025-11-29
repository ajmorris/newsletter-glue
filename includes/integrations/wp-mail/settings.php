<?php
/**
 * wp_mail() Settings.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// $app and $api are provided by the caller (see other integrations).

if ( ! function_exists( 'wp_roles' ) ) {
	return;
}

$wp_roles = wp_roles();
$roles    = isset( $wp_roles->roles ) ? $wp_roles->roles : array();

$role_options = array();

if ( ! empty( $roles ) ) {
	foreach ( $roles as $role_key => $role_data ) {
		$label                  = isset( $role_data['name'] ) ? $role_data['name'] : $role_key;
		$role_options[ $role_key ] = $label;
	}
}

// Global defaults.
$saved_audiences      = newsletterglue_get_option( 'audiences', $app );
$saved_excluded_roles = newsletterglue_get_option( 'excluded_roles', $app );

if ( ! is_array( $saved_audiences ) ) {
	$saved_audiences = array();
}

if ( ! is_array( $saved_excluded_roles ) ) {
	$saved_excluded_roles = array();
}

// Default excluded roles from the integration class.
$default_excluded = array();
if ( isset( $api ) && is_object( $api ) && method_exists( $api, 'get_default_excluded_roles' ) ) {
	$default_excluded = $api->get_default_excluded_roles();
}

?>

<div class="ngl-metabox-flex">

	<div class="ngl-metabox-flex">
		<div class="ngl-metabox-header">
			<label for="ngl_audiences"><?php esc_html_e( 'Audience roles', 'newsletter-glue' ); ?></label>
			<?php $api->input_verification_info(); ?>
		</div>
		<div class="ngl-field">
			<?php
				newsletterglue_select_field(
					array(
						'id'       => 'ngl_audiences',
						'legacy'   => true,
						'multiple' => true,
						'helper'   => __( 'Select WordPress roles that should receive your newsletters by default.', 'newsletter-glue' ),
						'class'    => 'ngl-ajax',
						'options'  => $role_options,
						'default'  => $saved_audiences,
					)
				);
			?>
		</div>
	</div>

	<div class="ngl-metabox-flex ngl-metabox-segment">
		<div class="ngl-metabox-header">
			<label for="ngl_excluded_roles"><?php esc_html_e( 'Excluded roles', 'newsletter-glue' ); ?></label>
			<?php $api->input_verification_info(); ?>
			<?php echo $api->show_loading(); ?>
		</div>
		<div class="ngl-field">
			<?php
				$excluded_default = ! empty( $saved_excluded_roles ) ? $saved_excluded_roles : $default_excluded;

				newsletterglue_select_field(
					array(
						'id'       => 'ngl_excluded_roles',
						'legacy'   => true,
						'multiple' => true,
						'helper'   => __( 'Users with these roles will never receive newsletters (e.g. unsubscribed or bounced).', 'newsletter-glue' ),
						'options'  => $role_options,
						'default'  => $excluded_default,
						'class'    => 'ngl-ajax',
					)
				);
			?>
		</div>
	</div>

</div>


