<?php
/**
 * Gutenberg.
 */

if ( ! class_exists( 'NGL_Abstract_Block', false ) ) {
	include_once NGL_PLUGIN_DIR . 'includes/abstract-block.php';
}

class NGL_Block_Metadata extends NGL_Abstract_Block {

	public $id = 'newsletterglue_block_metadata';
	public $is_pro = false;

	/**
	 * Construct.
	 */
	public function __construct() {

		$this->asset_id = str_replace( '_', '-', $this->id );

	}

	/**
	 * Block icon.
	 */
	public function get_icon_svg() {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 31.5 31.5" class="ngl-block-svg-icon">
			<g transform="translate(-115 -126)">
				<path d="M30.984,12.8l.5-2.812A.844.844,0,0,0,30.656,9H25.4l1.028-5.758a.844.844,0,0,0-.831-.992H22.737a.844.844,0,0,0-.831.7L20.825,9H13.89l1.028-5.758a.844.844,0,0,0-.831-.992H11.23a.844.844,0,0,0-.831.7L9.318,9H3.757a.844.844,0,0,0-.831.7l-.5,2.813a.844.844,0,0,0,.831.992h5.26l-1.607,9H1.346a.844.844,0,0,0-.831.7l-.5,2.813A.844.844,0,0,0,.844,27H6.1L5.076,32.758a.844.844,0,0,0,.831.992H8.763a.844.844,0,0,0,.831-.7L10.675,27H17.61l-1.028,5.758a.844.844,0,0,0,.831.992H20.27a.844.844,0,0,0,.831-.7L22.182,27h5.561a.844.844,0,0,0,.831-.7l.5-2.813a.844.844,0,0,0-.831-.992h-5.26l1.607-9h5.561a.844.844,0,0,0,.831-.7Zm-12.57,9.7H11.479l1.607-9h6.935Z" transform="translate(115 123.75)"/>
			</g>
		</svg>';
	}

	/**
	 * Block label.
	 */
	public function get_label() {
		return __( 'Newsletter meta data', 'newsletter-glue' );
	}

	/**
	 * Block label.
	 */
	public function get_description() {
		return __( 'Add standard meta data to each post.', 'newsletter-glue' );
	}

	/**
	 * Get defaults.
	 */
	public function get_defaults() {
		return array(
			'show_author'		=> true,
			'show_date'			=> false,
			'show_location'		=> false,
			'show_readonline'	=> false,
			'alignment'			=> 'left',
			'text_color'		=> '#666666',
			'date_format'		=> 'l, j M Y',
		);
	}

	/**
	 * Save settings.
	 */
	public function save_settings() {

		$defaults = get_option( $this->id );

		if ( ! $defaults ) {
			$defaults = $this->get_defaults();
		}

		// Save checkbox values
		$defaults['show_author'] = isset( $_POST['show_author'] ) && $_POST['show_author'] === 'yes';
		$defaults['show_date'] = isset( $_POST['show_date'] ) && $_POST['show_date'] === 'yes';
		$defaults['show_location'] = isset( $_POST['show_location'] ) && $_POST['show_location'] === 'yes';
		$defaults['show_readonline'] = isset( $_POST['show_readonline'] ) && $_POST['show_readonline'] === 'yes';

		// Save alignment
		if ( isset( $_POST['alignment'] ) ) {
			$alignment = sanitize_text_field( $_POST['alignment'] );
			if ( in_array( $alignment, array( 'left', 'center', 'right' ), true ) ) {
				$defaults['alignment'] = $alignment;
			}
		}

		// Save text color
		if ( isset( $_POST['text_color'] ) ) {
			$defaults['text_color'] = sanitize_text_field( $_POST['text_color'] );
		}

		// Save date format
		if ( isset( $_POST['date_format'] ) ) {
			$defaults['date_format'] = sanitize_text_field( $_POST['date_format'] );
		}

		update_option( $this->id, $defaults );

		return $defaults;

	}

}

return new NGL_Block_Metadata;