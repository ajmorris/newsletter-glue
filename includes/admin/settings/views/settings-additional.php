<?php
/**
 * Settings UI.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

?>

<div class="ui large header">

	<?php esc_html_e( 'Custom post types', 'newsletter-glue' ); ?>

	<div class="sub header"><?php echo __( 'Newsletter Glue will be enabled for the custom post types you select here.', 'newsletter-glue' ); ?></div>

</div>

<div class="ngl-metabox">

	<div class="ngl-metabox-flex">
	<div class="ngl-metabox-flex">
		<div class="ngl-metabox-header">
			<label for="ngl_post_types"><?php esc_html_e( 'Custom post types', 'newsletter-glue' ); ?></label>
			<?php $api->input_verification_info(); ?>
		</div>
		<div class="ngl-field">
			<?php
				$saved = get_option( 'newsletterglue_post_types' );
				newsletterglue_select_field( array(
					'id' 			=> 'ngl_post_types',
					'legacy'		=> true,
					'class'			=> 'ngl-ajax ngl-long-dropdown',
					'options'		=> newsletterglue_get_post_types(),
					'default'		=> $saved ? explode( ',', $saved ) : '',
					'multiple'		=> true,
					'placeholder'	=> __( 'Select post types...', 'newsletter-glue' ),
				) );
			?>
		</div>
	</div>
	</div>

</div>

<div class="ui large header" style="margin-top: 2em;">

	<?php esc_html_e( 'Editor Settings', 'newsletter-glue' ); ?>

	<div class="sub header"><?php echo wp_kses_post( __( 'Configure how newsletter settings appear in the block editor.', 'newsletter-glue' ) ); ?></div>

</div>

<div class="ngl-metabox">

	<div class="ngl-metabox-flex">
		<div class="ngl-metabox-flex">
			<div class="ngl-metabox-header">
				<label for="ng_editor_settings_location"><?php esc_html_e( 'Settings location', 'newsletter-glue' ); ?></label>
			</div>
			<div class="ngl-field">
				<?php
					$settings_location = get_option( 'newsletterglue_editor_settings_location', 'metabox' );
					$location_options = array(
						'metabox' => __( 'Meta Box (below editor)', 'newsletter-glue' ),
						'panel'   => __( 'Sidebar Panel (right side)', 'newsletter-glue' ),
					);
					newsletterglue_select_field( array(
						'id' 			=> 'ng_editor_settings_location',
						'legacy'		=> true,
						'helper'		=> __( 'Choose where newsletter settings should appear in the block editor.', 'newsletter-glue' ),
						'options'		=> $location_options,
						'default'		=> $settings_location,
						'class'			=> 'ngl-ajax',
					) );
				?>
			</div>
		</div>
	</div>

</div>