<?php
/**
 * Onboarding Modal for wp_mail().
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
		$label                    = isset( $role_data['name'] ) ? $role_data['name'] : $role_key;
		$role_options[ $role_key ] = $label;
	}
}

$saved_audiences      = newsletterglue_get_option( 'audiences', $app );
$saved_excluded_roles = newsletterglue_get_option( 'excluded_roles', $app );

if ( ! is_array( $saved_audiences ) ) {
	$saved_audiences = array();
}

if ( ! is_array( $saved_excluded_roles ) ) {
	$saved_excluded_roles = array();
}

$default_excluded = array();
if ( isset( $api ) && is_object( $api ) && method_exists( $api, 'get_default_excluded_roles' ) ) {
	$default_excluded = $api->get_default_excluded_roles();
}

?>

<div class="ngl-boarding alt ngl-mb-wpmail is-hidden" data-screen="4">

	<div class="ngl-boarding-logo">
		<div class="ngl-logo"><img src="<?php echo esc_url( NGL_PLUGIN_URL . '/assets/images/top-bar-logo.svg' ); ?>" alt="" /></div>
	</div>

	<div class="ngl-boarding-step"><?php esc_html_e( 'Step 2 of 3', 'newsletter-glue' ); ?></div>

	<h3 style="max-width:100%;">
		<?php esc_html_e( 'Now, let’s select your default audience.', 'newsletter-glue' ); ?>
		<span><?php esc_html_e( 'You can always change this later on in the settings.', 'newsletter-glue' ); ?></span>
	</h3>

	<div class="ngl-settings ngl-metabox-flex">
		<div class="ngl-metabox-header">
			<?php esc_html_e( 'Audience roles', 'newsletter-glue' ); ?>
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

	<div class="ngl-settings ngl-metabox-flex ngl-metabox-segment">
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
						'helper'   => __( 'Users with these roles will never receive newsletters.', 'newsletter-glue' ),
						'options'  => $role_options,
						'default'  => $excluded_default,
						'class'    => 'ngl-ajax',
					)
				);
			?>
		</div>
	</div>

	<div class="ngl-boarding-next disabled">
		<span class="material-icons">arrow_forward</span>
		<span class="ngl-boarding-next-text"><?php esc_html_e( 'next', 'newsletter-glue' ); ?></span>
	</div>
	<div class="ngl-boarding-prev">
		<span class="material-icons">arrow_back</span>
		<span class="ngl-boarding-prev-text"><?php esc_html_e( 'prev', 'newsletter-glue' ); ?></span>
	</div>

</div>

<?php $api->load_last_onboarding_screen(); ?>


