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

	<div class="ngl-popup-field-header"><?php _e( 'Display options', 'newsletter-glue' ); ?></div>

	<div class="ngl-popup-field">
		<label for="show_author-<?php echo esc_attr( $this->id ); ?>">
			<input type="checkbox" id="show_author-<?php echo esc_attr( $this->id ); ?>" name="show_author" value="yes" <?php if ( isset( $defaults['show_author'] ) && $defaults['show_author'] ) echo 'checked'; ?> >
			<span class="ngl-block-use-switch"></span>
			<span class="ngl-block-use-label"><?php _e( 'Show author', 'newsletter-glue' ); ?></span>
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="show_date-<?php echo esc_attr( $this->id ); ?>">
			<input type="checkbox" id="show_date-<?php echo esc_attr( $this->id ); ?>" name="show_date" value="yes" <?php if ( isset( $defaults['show_date'] ) && $defaults['show_date'] ) echo 'checked'; ?> >
			<span class="ngl-block-use-switch"></span>
			<span class="ngl-block-use-label"><?php _e( 'Show date', 'newsletter-glue' ); ?></span>
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="show_location-<?php echo esc_attr( $this->id ); ?>">
			<input type="checkbox" id="show_location-<?php echo esc_attr( $this->id ); ?>" name="show_location" value="yes" <?php if ( isset( $defaults['show_location'] ) && $defaults['show_location'] ) echo 'checked'; ?> >
			<span class="ngl-block-use-switch"></span>
			<span class="ngl-block-use-label"><?php _e( 'Show location', 'newsletter-glue' ); ?></span>
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="show_readonline-<?php echo esc_attr( $this->id ); ?>">
			<input type="checkbox" id="show_readonline-<?php echo esc_attr( $this->id ); ?>" name="show_readonline" value="yes" <?php if ( isset( $defaults['show_readonline'] ) && $defaults['show_readonline'] ) echo 'checked'; ?> >
			<span class="ngl-block-use-switch"></span>
			<span class="ngl-block-use-label"><?php _e( 'Show read online link', 'newsletter-glue' ); ?></span>
		</label>
	</div>

	<div class="ngl-popup-field-header"><?php _e( 'Styling options', 'newsletter-glue' ); ?></div>

	<div class="ngl-popup-field">
		<label for="alignment-<?php echo esc_attr( $this->id ); ?>">
			<span class="ngl-block-use-label"><?php _e( 'Alignment', 'newsletter-glue' ); ?></span>
			<select id="alignment-<?php echo esc_attr( $this->id ); ?>" name="alignment">
				<option value="left" <?php selected( isset( $defaults['alignment'] ) ? $defaults['alignment'] : 'left', 'left' ); ?>><?php _e( 'Left', 'newsletter-glue' ); ?></option>
				<option value="center" <?php selected( isset( $defaults['alignment'] ) ? $defaults['alignment'] : 'left', 'center' ); ?>><?php _e( 'Center', 'newsletter-glue' ); ?></option>
				<option value="right" <?php selected( isset( $defaults['alignment'] ) ? $defaults['alignment'] : 'left', 'right' ); ?>><?php _e( 'Right', 'newsletter-glue' ); ?></option>
			</select>
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="text_color-<?php echo esc_attr( $this->id ); ?>">
			<span class="ngl-block-use-label"><?php _e( 'Text color', 'newsletter-glue' ); ?></span>
			<input type="text" id="text_color-<?php echo esc_attr( $this->id ); ?>" name="text_color" value="<?php echo isset( $defaults['text_color'] ) ? esc_attr( $defaults['text_color'] ) : '#666666'; ?>" placeholder="#666666">
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="date_format-<?php echo esc_attr( $this->id ); ?>">
			<span class="ngl-block-use-label"><?php _e( 'Date format', 'newsletter-glue' ); ?></span>
			<input type="text" id="date_format-<?php echo esc_attr( $this->id ); ?>" name="date_format" value="<?php echo isset( $defaults['date_format'] ) ? esc_attr( $defaults['date_format'] ) : 'l, j M Y'; ?>" placeholder="l, j M Y">
			<small style="display: block; margin-top: 5px; color: #666;"><?php _e( 'PHP date format (e.g., l, j M Y for "Monday, 1 Jan 2021")', 'newsletter-glue' ); ?></small>
		</label>
	</div>

</form>

