<?php
/**
 * Blocks UI.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

?>

<form action="" class="ngl-popup-settings" data-block="<?php echo esc_attr( $this->id ); ?>">

	<a href="#" class="ngl-popup-close"><span class="dashicons dashicons-no-alt"></span></a>

	<div class="ngl-popup-header">
		<?php echo $this->get_label(); ?>
		<span><?php _e( 'Customise how this block shows up in the post editor.', 'newsletter-glue' ); ?></span>
	</div>

	<div class="ngl-popup-field-header"><?php _e( 'Styling options', 'newsletter-glue' ); ?></div>

	<div class="ngl-popup-field">
		<label for="bg_color-<?php echo esc_attr( $this->id ); ?>">
			<span class="ngl-block-use-label"><?php _e( 'Background color', 'newsletter-glue' ); ?></span>
			<input type="text" id="bg_color-<?php echo esc_attr( $this->id ); ?>" name="bg_color" value="<?php echo isset( $defaults['bg_color'] ) ? esc_attr( $defaults['bg_color'] ) : '#f9f9f9'; ?>" placeholder="#f9f9f9">
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="font_color-<?php echo esc_attr( $this->id ); ?>">
			<span class="ngl-block-use-label"><?php _e( 'Font color', 'newsletter-glue' ); ?></span>
			<input type="text" id="font_color-<?php echo esc_attr( $this->id ); ?>" name="font_color" value="<?php echo isset( $defaults['font_color'] ) ? esc_attr( $defaults['font_color'] ) : ''; ?>" placeholder="#333333">
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="cta_padding-<?php echo esc_attr( $this->id ); ?>">
			<span class="ngl-block-use-label"><?php _e( 'Padding top/bottom (pixels)', 'newsletter-glue' ); ?></span>
			<input type="number" id="cta_padding-<?php echo esc_attr( $this->id ); ?>" name="cta_padding" value="<?php echo isset( $defaults['cta_padding'] ) ? esc_attr( $defaults['cta_padding'] ) : 0; ?>" min="0" max="100" step="1">
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="cta_padding2-<?php echo esc_attr( $this->id ); ?>">
			<span class="ngl-block-use-label"><?php _e( 'Padding left/right (pixels)', 'newsletter-glue' ); ?></span>
			<input type="number" id="cta_padding2-<?php echo esc_attr( $this->id ); ?>" name="cta_padding2" value="<?php echo isset( $defaults['cta_padding2'] ) ? esc_attr( $defaults['cta_padding2'] ) : 0; ?>" min="0" max="100" step="1">
		</label>
	</div>

</form>

