<?php
/**
 * Gutenberg.
 */

if ( ! class_exists( 'NGL_Abstract_Block', false ) ) {
	include_once NGL_PLUGIN_DIR . 'includes/abstract-block.php';
}

class NGL_Block_Share extends NGL_Abstract_Block {

	public $id = 'newsletterglue_block_share';
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
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 111.001 111.001" class="ngl-block-svg-icon">
					<path class="a" d="M-14-283v-99.9A11.115,11.115,0,0,1-2.9-394H85.9A11.115,11.115,0,0,1,97-382.9v66.6a11.113,11.113,0,0,1-11.1,11.1H8.2l-22.194,22.194Zm41.742-92.5a14.333,14.333,0,0,0-9.425,3.443,15.85,15.85,0,0,0-5.568,11.422A16.113,16.113,0,0,0,17.2-348.693l21.745,22.454a3.524,3.524,0,0,0,2.546,1.086,3.524,3.524,0,0,0,2.546-1.086l21.745-22.454a16.11,16.11,0,0,0,4.466-11.942,15.84,15.84,0,0,0-5.565-11.421,14.337,14.337,0,0,0-9.423-3.443,16.213,16.213,0,0,0-11.549,4.971L41.5-368.246l-2.211-2.281A16.192,16.192,0,0,0,27.742-375.5Z" transform="translate(14 394)"/>
				</svg>';
	}

	/**
	 * Block label.
	 */
	public function get_label() {
		return __( 'Social sharing', 'newsletter-glue' );
	}

	/**
	 * Block label.
	 */
	public function get_description() {
		return __( 'Add social sharing links to your newsletter.', 'newsletter-glue' );
	}

	/**
	 * Get defaults.
	 */
	public function get_defaults() {
		return array(
			'icon_size'		=> 18,
			'icon_shape'	=> 'default',
			'icon_color'	=> 'grey',
			'alignment'		=> 'left',
			'add_description' => false,
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

		// Save icon size
		if ( isset( $_POST['icon_size'] ) ) {
			$defaults['icon_size'] = absint( $_POST['icon_size'] );
			// Validate range
			if ( $defaults['icon_size'] < 12 ) {
				$defaults['icon_size'] = 12;
			} elseif ( $defaults['icon_size'] > 48 ) {
				$defaults['icon_size'] = 48;
			}
		}

		// Save icon color
		if ( isset( $_POST['icon_color'] ) ) {
			$icon_color = sanitize_text_field( $_POST['icon_color'] );
			if ( in_array( $icon_color, array( 'grey', 'white', 'black' ), true ) ) {
				$defaults['icon_color'] = $icon_color;
			}
		}

		// Save icon shape
		if ( isset( $_POST['icon_shape'] ) ) {
			$icon_shape = sanitize_text_field( $_POST['icon_shape'] );
			if ( in_array( $icon_shape, array( 'default', 'rounded', 'circle' ), true ) ) {
				$defaults['icon_shape'] = $icon_shape;
			}
		}

		// Save alignment
		if ( isset( $_POST['alignment'] ) ) {
			$alignment = sanitize_text_field( $_POST['alignment'] );
			if ( in_array( $alignment, array( 'left', 'center', 'right' ), true ) ) {
				$defaults['alignment'] = $alignment;
			}
		}

		// Save add_description checkbox
		$defaults['add_description'] = isset( $_POST['add_description'] ) && $_POST['add_description'] === 'yes';

		update_option( $this->id, $defaults );

		return $defaults;

	}

}

return new NGL_Block_Share;