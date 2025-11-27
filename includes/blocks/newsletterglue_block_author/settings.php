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
		<label for="showAvatar">
			<input type="checkbox" id="showAvatar" name="showAvatar" value="yes" <?php if ( isset( $defaults['showAvatar'] ) && $defaults['showAvatar'] ) echo 'checked'; ?> >
			<span class="ngl-block-use-switch"></span>
			<span class="ngl-block-use-label"><?php _e( 'Show avatar', 'newsletter-glue' ); ?></span>
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="showName">
			<input type="checkbox" id="showName" name="showName" value="yes" <?php if ( isset( $defaults['showName'] ) && $defaults['showName'] ) echo 'checked'; ?> >
			<span class="ngl-block-use-switch"></span>
			<span class="ngl-block-use-label"><?php _e( 'Show author name', 'newsletter-glue' ); ?></span>
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="showBio">
			<input type="checkbox" id="showBio" name="showBio" value="yes" <?php if ( isset( $defaults['showBio'] ) && $defaults['showBio'] ) echo 'checked'; ?> >
			<span class="ngl-block-use-switch"></span>
			<span class="ngl-block-use-label"><?php _e( 'Show author bio', 'newsletter-glue' ); ?></span>
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="showMoreLink">
			<input type="checkbox" id="showMoreLink" name="showMoreLink" value="yes" <?php if ( isset( $defaults['showMoreLink'] ) && $defaults['showMoreLink'] ) echo 'checked'; ?> >
			<span class="ngl-block-use-switch"></span>
			<span class="ngl-block-use-label"><?php _e( 'Show "More from this author" link', 'newsletter-glue' ); ?></span>
		</label>
	</div>

	<div class="ngl-popup-field-header"><?php _e( 'Styling options', 'newsletter-glue' ); ?></div>

	<div class="ngl-popup-field">
		<label for="avatarSize">
			<span class="ngl-block-use-label"><?php _e( 'Avatar size (pixels)', 'newsletter-glue' ); ?></span>
			<input type="number" id="avatarSize" name="avatarSize" value="<?php echo isset( $defaults['avatarSize'] ) ? esc_attr( $defaults['avatarSize'] ) : 48; ?>" min="24" max="200" step="1">
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="maxBioChars">
			<span class="ngl-block-use-label"><?php _e( 'Max bio characters', 'newsletter-glue' ); ?></span>
			<input type="number" id="maxBioChars" name="maxBioChars" value="<?php echo isset( $defaults['maxBioChars'] ) ? esc_attr( $defaults['maxBioChars'] ) : 140; ?>" min="0" max="500" step="1">
		</label>
	</div>

	<div class="ngl-popup-field">
		<label for="alignment">
			<span class="ngl-block-use-label"><?php _e( 'Alignment', 'newsletter-glue' ); ?></span>
			<select id="alignment" name="alignment">
				<option value="left" <?php selected( isset( $defaults['alignment'] ) ? $defaults['alignment'] : 'left', 'left' ); ?>><?php _e( 'Left', 'newsletter-glue' ); ?></option>
				<option value="center" <?php selected( isset( $defaults['alignment'] ) ? $defaults['alignment'] : 'left', 'center' ); ?>><?php _e( 'Center', 'newsletter-glue' ); ?></option>
			</select>
		</label>
	</div>

</form>

