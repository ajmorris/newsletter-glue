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
		<label for="icon_size">
			<span class="ngl-block-use-label"><?php _e( 'Icon size (pixels)', 'newsletter-glue' ); ?></span>
			<input type="number" id="icon_size" name="icon_size" value="<?php echo isset( $defaults['icon_size'] ) ? esc_attr( $defaults['icon_size'] ) : 18; ?>" min="12" max="48" step="1">
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="icon_color">
			<span class="ngl-block-use-label"><?php _e( 'Icon color', 'newsletter-glue' ); ?></span>
			<select id="icon_color" name="icon_color">
				<option value="grey" <?php selected( isset( $defaults['icon_color'] ) ? $defaults['icon_color'] : 'grey', 'grey' ); ?>><?php _e( 'Grey', 'newsletter-glue' ); ?></option>
				<option value="white" <?php selected( isset( $defaults['icon_color'] ) ? $defaults['icon_color'] : 'grey', 'white' ); ?>><?php _e( 'White', 'newsletter-glue' ); ?></option>
				<option value="black" <?php selected( isset( $defaults['icon_color'] ) ? $defaults['icon_color'] : 'grey', 'black' ); ?>><?php _e( 'Black', 'newsletter-glue' ); ?></option>
			</select>
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="icon_shape">
			<span class="ngl-block-use-label"><?php _e( 'Icon shape', 'newsletter-glue' ); ?></span>
			<select id="icon_shape" name="icon_shape">
				<option value="default" <?php selected( isset( $defaults['icon_shape'] ) ? $defaults['icon_shape'] : 'default', 'default' ); ?>><?php _e( 'Default', 'newsletter-glue' ); ?></option>
				<option value="rounded" <?php selected( isset( $defaults['icon_shape'] ) ? $defaults['icon_shape'] : 'default', 'rounded' ); ?>><?php _e( 'Rounded', 'newsletter-glue' ); ?></option>
				<option value="circle" <?php selected( isset( $defaults['icon_shape'] ) ? $defaults['icon_shape'] : 'default', 'circle' ); ?>><?php _e( 'Circle', 'newsletter-glue' ); ?></option>
			</select>
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="alignment">
			<span class="ngl-block-use-label"><?php _e( 'Alignment', 'newsletter-glue' ); ?></span>
			<select id="alignment" name="alignment">
				<option value="left" <?php selected( isset( $defaults['alignment'] ) ? $defaults['alignment'] : 'left', 'left' ); ?>><?php _e( 'Left', 'newsletter-glue' ); ?></option>
				<option value="center" <?php selected( isset( $defaults['alignment'] ) ? $defaults['alignment'] : 'left', 'center' ); ?>><?php _e( 'Center', 'newsletter-glue' ); ?></option>
				<option value="right" <?php selected( isset( $defaults['alignment'] ) ? $defaults['alignment'] : 'left', 'right' ); ?>><?php _e( 'Right', 'newsletter-glue' ); ?></option>
			</select>
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="add_description">
			<input type="checkbox" id="add_description" name="add_description" value="yes" <?php if ( isset( $defaults['add_description'] ) && $defaults['add_description'] ) echo 'checked'; ?> >
			<span class="ngl-block-use-switch"></span>
			<span class="ngl-block-use-label"><?php _e( 'Add description text', 'newsletter-glue' ); ?></span>
		</label>
	</div>

</form>

