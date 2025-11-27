<?php
/**
 * Gutenberg.
 */

if ( ! class_exists( 'NGL_Abstract_Block', false ) ) {
	include_once NGL_PLUGIN_DIR . 'includes/abstract-block.php';
}

class NGL_Block_Callout extends NGL_Abstract_Block {

	public $id = 'newsletterglue_block_callout';
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
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="ngl-block-svg-icon">
			<path d="M21 15V18H24V20H21V23H19V20H16V18H19V15H21M14 18H3V6H19V13H21V6C21 4.89 20.11 4 19 4H3C1.9 4 1 4.89 1 6V18C1 19.11 1.9 20 3 20H14V18Z"/>
		</svg>';
	}

	/**
	 * Block label.
	 */
	public function get_label() {
		return __( 'Container', 'newsletter-glue' );
	}

	/**
	 * Block label.
	 */
	public function get_description() {
		return __( 'Customise the background and border of this container block to help its content stand out.', 'newsletter-glue' );
	}

	/**
	 * Get defaults.
	 */
	public function get_defaults() {
		return array(
			'bg_color'		=> '#f9f9f9',
			'font_color'	=> '',
			'cta_padding'	=> 0,
			'cta_padding2'	=> 0,
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

		// Save background color
		if ( isset( $_POST['bg_color'] ) ) {
			$defaults['bg_color'] = sanitize_text_field( $_POST['bg_color'] );
		}

		// Save font color
		if ( isset( $_POST['font_color'] ) ) {
			$defaults['font_color'] = sanitize_text_field( $_POST['font_color'] );
		}

		// Save padding top/bottom
		if ( isset( $_POST['cta_padding'] ) ) {
			$defaults['cta_padding'] = absint( $_POST['cta_padding'] );
			if ( $defaults['cta_padding'] > 100 ) {
				$defaults['cta_padding'] = 100;
			}
		}

		// Save padding left/right
		if ( isset( $_POST['cta_padding2'] ) ) {
			$defaults['cta_padding2'] = absint( $_POST['cta_padding2'] );
			if ( $defaults['cta_padding2'] > 100 ) {
				$defaults['cta_padding2'] = 100;
			}
		}

		update_option( $this->id, $defaults );

		return $defaults;

	}

}

return new NGL_Block_Callout;