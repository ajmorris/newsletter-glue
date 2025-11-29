<?php
/**
 * Kit Settings.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

?>

<div class="ngl-metabox-flex">

	<div class="ngl-metabox-flex">
		<div class="ngl-metabox-header">
			<label for="ngl_audience"><?php echo esc_html( $api->get_audience_label() ); ?></label>
			<?php $api->input_verification_info(); ?>
		</div>
		<div class="ngl-field">
			<?php
				$audience  = newsletterglue_get_option( 'audience', $app );
				$audiences = $api->get_audiences();
				if ( ! $audience ) {
					$audience = array_keys( $audiences );
					$audience = $audience[0];
				}
				newsletterglue_select_field( array(
					'id' 			=> 'ngl_audience',
					'legacy'		=> true,
					'helper'		=> __( 'Which form receives your email.', 'newsletter-glue' ),
					'class'			=> 'ngl-ajax',
					'options'		=> $audiences,
					'default'		=> $audience,
				) );
			?>
		</div>
	</div>

	<div class="ngl-metabox-flex ngl-metabox-segment">
		<div class="ngl-metabox-header">
			<label for="ngl_segment"><?php echo esc_html( $api->get_segment_label() ); ?></label>
			<?php $api->input_verification_info(); ?>
			<?php echo $api->show_loading(); ?>
		</div>
		<div class="ngl-field">
			<?php

				$segment = newsletterglue_get_option( 'segment', $app );

				if ( ! $segment ) {
					$segment = '_everyone';
				}

				newsletterglue_select_field( array(
					'id' 			=> 'ngl_segment',
					'legacy'		=> true,
					'helper'		=> sprintf( __( 'A specific group of subscribers. %s', 'newsletter-glue' ), '<a href="' . esc_url( $api->get_create_tag_url() ) . '" target="_blank">' . sprintf( __( 'Create %s', 'newsletter-glue' ), strtolower( $api->get_segment_label() ) ) . ' <i class="arrow right icon"></i></a>' ),
					'options'		=> $api->get_segments( $audience ),
					'default'		=> $segment,
					'class'			=> 'ngl-ajax',
				) );

			?>
		</div>
	</div>

</div>

