<?php
/**
 * Settings General.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

?>

<div class="ngl-metabox-flex">

	<div class="ngl-metabox-flex">
		<div class="ngl-metabox-header">
			<label for="ngl_from_name"><?php esc_html_e( 'From name', 'newsletter-glue' ); ?></label>
			<?php $api->input_verification_info(); ?>
		</div>
		<div class="ngl-field">
			<?php
				newsletterglue_text_field( array(
					'id' 			=> 'ngl_from_name',
					'helper'		=> __( 'Your subscribers will see this name in their inboxes.', 'newsletter-glue' ),
					'value'			=> newsletterglue_get_option( 'from_name', $app ),
					'class'			=> 'ngl-ajax',
				) );
			?>
		</div>
	</div>

	<div class="ngl-metabox-flex">
		<div class="ngl-metabox-header">
			<label for="ngl_from_email"><?php esc_html_e( 'From email', 'newsletter-glue' ); ?></label>
			<?php $api->email_verification_info(); ?>
		</div>
		<div class="ngl-field">
			<?php
				$verify = ! $api->has_email_verify() ? 'no-support-verify' : '';
				newsletterglue_text_field( array(
					'id' 			=> 'ngl_from_email',
					'helper'		=> __( 'Subscribers will see and reply to this email address.', 'newsletter-glue' ),
					'value'			=> newsletterglue_get_option( 'from_email', $app ),
					'class'			=> 'ngl-ajax ' . $verify,
				) );
			?>
			<?php if ( ! $api->has_email_verify() ) { ?>
			<div class="ngl-helper">
				<?php echo sprintf( __( 'Only use verified email addresses. %s', 'newsletter-glue' ), '<a href="' . $api->get_email_verify_help() . '" target="_blank">' . __( 'Learn more', 'newsletter-glue' ) . ' <i class="arrow right icon"></i></a>' ); ?>
			</div>
			<?php } ?>
		</div>
	</div>

</div>

<div class="ui large header" style="margin-top: 2em;">

	<?php esc_html_e( 'Read on Site Link', 'newsletter-glue' ); ?>

	<div class="sub header"><?php echo wp_kses_post( __( 'Automatically append a link back to the original post at the end of newsletter emails.', 'newsletter-glue' ) ); ?></div>

</div>

<div class="ngl-metabox">

	<div class="ngl-metabox-flex">
		<div class="ngl-metabox-flex">
			<div class="ngl-metabox-header">
				<label for="ng_include_read_link_global"><?php esc_html_e( 'Include "Read on site" link', 'newsletter-glue' ); ?></label>
			</div>
			<div class="ngl-field">
				<?php
					$include_read_link = get_option( 'newsletterglue_include_read_link_global', 'yes' );
				?>
				<div class="ngl-field-master">
					<input type="checkbox" name="ng_include_read_link_global" id="ng_include_read_link_global" value="1" class="ngl-ajax" <?php checked( $include_read_link, 'yes' ); ?> />
					<label for="ng_include_read_link_global"><?php esc_html_e( 'Include "Read on site" link at end of newsletter', 'newsletter-glue' ); ?></label>
				</div>
				<div class="ngl-helper"><?php esc_html_e( 'When enabled, a link to the original post will be appended to the end of newsletter emails.', 'newsletter-glue' ); ?></div>
			</div>
		</div>
	</div>

	<div class="ngl-metabox-flex">
		<div class="ngl-metabox-flex">
			<div class="ngl-metabox-header">
				<label for="ng_read_link_default_label"><?php esc_html_e( 'Default link label', 'newsletter-glue' ); ?></label>
			</div>
			<div class="ngl-field">
				<?php
					$default_label = get_option( 'newsletterglue_read_link_default_label', __( 'Read this on the web →', 'newsletter-glue' ) );
					newsletterglue_text_field( array(
						'id' 			=> 'ng_read_link_default_label',
						'helper'		=> __( 'The default text for the "Read on site" link. Can be overridden per post.', 'newsletter-glue' ),
						'value'			=> $default_label,
						'class'			=> 'ngl-ajax',
					) );
				?>
			</div>
		</div>
	</div>

</div>

<?php do_action( 'newsletterglue_edit_more_settings', $app, $api->get_settings(), true ); ?>