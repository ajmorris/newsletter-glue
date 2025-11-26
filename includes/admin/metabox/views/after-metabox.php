<?php
/**
 * Newsletter Metabox.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

global $post_type;

?>

	<?php $api->show_settings( $settings, $defaults, $post ); ?>

	</div>

</div>

<?php if ( $post_type != 'ngl_pattern' ) : ?>
<div class="ngl-metabox ngl-metabox-flex alt3">
	<div class="ngl-metabox-flex">
		<div class="ngl-metabox-header">
			<label><?php esc_html_e( 'Read on Site Link', 'newsletter-glue' ); ?></label>
		</div>
		<div class="ngl-field">
			<?php
				$include_read_link = isset( $settings->include_read_link ) ? $settings->include_read_link : '';
				$read_link_label = isset( $settings->read_link_custom_label ) ? $settings->read_link_custom_label : '';
				$global_include = get_option( 'newsletterglue_include_read_link_global', 'yes' );
			?>
			<div class="ngl-field-master">
				<input type="checkbox" name="ngl_include_read_link" id="ngl_include_read_link" value="1" <?php checked( $include_read_link, '1' ); ?> />
				<label for="ngl_include_read_link"><?php esc_html_e( 'Include "Read on site" link in this newsletter', 'newsletter-glue' ); ?></label>
			</div>
			<div class="ngl-helper">
				<?php
					if ( $global_include === 'yes' ) {
						esc_html_e( 'When checked, the link will be included. When unchecked, it will follow the global setting (currently enabled).', 'newsletter-glue' );
					} else {
						esc_html_e( 'When checked, the link will be included for this post. When unchecked, it will follow the global setting (currently disabled).', 'newsletter-glue' );
					}
				?>
			</div>
		</div>
	</div>

	<div class="ngl-metabox-flex">
		<div class="ngl-metabox-header">
			<label for="ngl_read_link_custom_label"><?php esc_html_e( 'Custom link label', 'newsletter-glue' ); ?></label>
		</div>
		<div class="ngl-field">
			<?php
				$default_label = get_option( 'newsletterglue_read_link_default_label', __( 'Read this on the web →', 'newsletter-glue' ) );
				newsletterglue_text_field( array(
					'id' 			=> 'ngl_read_link_custom_label',
					'placeholder'	=> $default_label,
					'helper'		=> sprintf( __( 'Leave empty to use the global default: "%s"', 'newsletter-glue' ), esc_html( $default_label ) ),
					'value'			=> $read_link_label,
				) );
			?>
		</div>
	</div>
</div>

<div class="ngl-metabox ngl-metabox-flex alt3 ngl-sending-box <?php if ( ! $hide ) echo 'is-hidden'; ?>">

	<div class="ngl-metabox-flex ngl-metabox-flex-toggle">

		<div class="ngl-field ngl-field-master">
			<input type="hidden" name="ngl_double_confirm" id="ngl_double_confirm" value="no" />
			<input type="checkbox" name="ngl_send_newsletter" id="ngl_send_newsletter" value="1" />
			<label for="ngl_send_newsletter"><?php _e( 'Send as newsletter', 'newsletter-glue' ); ?> <span class="ngl-field-master-help"><?php _e( '(when post is published/updated)', 'newsletter-glue' ); ?></span></label>
		</div>

	</div>

	<div class="ngl-metabox-flex">
		<div class="ngl-not-ready is-hidden">
			<div class="ngl-metabox-msg is-error"><?php _e( 'Almost ready. Just fill in the blank red boxes.' ,'newsletter-glue' ); ?></div>
		</div>
	</div>

</div>
<?php endif; ?>