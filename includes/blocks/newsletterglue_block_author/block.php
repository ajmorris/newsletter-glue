<?php
/**
 * Newsletter Author Block.
 */

if ( ! class_exists( 'NGL_Abstract_Block', false ) ) {
	include_once NGL_PLUGIN_DIR . 'includes/abstract-block.php';
}

class NGL_Block_Author extends NGL_Abstract_Block {

	public $id = 'newsletterglue_block_author';

	public $is_pro = false;

	/**
	 * Construct.
	 */
	public function __construct() {

		$this->asset_id = str_replace( '_', '-', $this->id );

		if ( $this->use_block() === 'yes' ) {
			add_action( 'init', array( $this, 'register_block' ), 10 );
		}

	}

	/**
	 * Block icon.
	 */
	public function get_icon_svg() {
		return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 42.301 42.301" class="ngl-block-svg-icon">
			<path xmlns="http://www.w3.org/2000/svg"  d="M21.15.563A21.15,21.15,0,1,0,42.3,21.713,21.147,21.147,0,0,0,21.15.563Zm0,8.187a7.5,7.5,0,1,1-7.5,7.5A7.505,7.505,0,0,1,21.15,8.75Zm0,29.338A16.343,16.343,0,0,1,8.656,32.271a9.509,9.509,0,0,1,8.4-5.1,2.087,2.087,0,0,1,.606.094,11.292,11.292,0,0,0,3.488.588,11.249,11.249,0,0,0,3.488-.588,2.087,2.087,0,0,1,.606-.094,9.509,9.509,0,0,1,8.4,5.1A16.343,16.343,0,0,1,21.15,38.087Z" transform="translate(0 -0.563)"/>
		</svg>';
	}

	/**
	 * Block label.
	 */
	public function get_label() {
		return __( 'Newsletter Author', 'newsletter-glue' );
	}

	/**
	 * Block description.
	 */
	public function get_description() {
		return __( 'Display the post author\'s name, avatar, and bio in your newsletter.', 'newsletter-glue' );
	}

	/**
	 * Get defaults.
	 */
	public function get_defaults() {
		return array(
			'showAvatar'	=> true,
			'showName'		=> true,
			'showBio'		=> false,
			'showMoreLink'	=> false,
			'maxBioChars'	=> 140,
			'avatarSize'	=> 48,
			'alignment'		=> 'left',
		);
	}

	/**
	 * Register the block.
	 */
	public function register_block() {

		$defaults = get_option( $this->id );

		if ( ! $defaults ) {
			$defaults = $this->get_defaults();
		}

		$js_dir = NGL_PLUGIN_URL . 'includes/blocks/' . $this->id . '/js/';
		$suffix = '';

		wp_register_script( $this->asset_id, $js_dir . 'block' . $suffix . '.js', array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data' ), time() );
		wp_localize_script( $this->asset_id, $this->id, $defaults );

		register_block_type( 'newsletterglue/author', array(
			'attributes'		=> array(
				'showAvatar'	=> array(
					'type'		=> 'boolean',
					'default'	=> true,
				),
				'showName'		=> array(
					'type'		=> 'boolean',
					'default'	=> true,
				),
				'showBio'		=> array(
					'type'		=> 'boolean',
					'default'	=> false,
				),
				'showMoreLink'	=> array(
					'type'		=> 'boolean',
					'default'	=> false,
				),
				'maxBioChars'	=> array(
					'type'		=> 'number',
					'default'	=> 140,
				),
				'avatarSize'	=> array(
					'type'		=> 'number',
					'default'	=> 48,
				),
				'alignment'		=> array(
					'type'		=> 'string',
					'default'	=> 'left',
				),
			),
			'editor_script' 	=> $this->asset_id,
			'render_callback'	=> array( $this, 'render_block' ),
		) );

	}

	/**
	 * Render the block.
	 */
	public function render_block( $attributes, $content ) {

		// Get post ID from context.
		global $post;
		$post_id = isset( $post->ID ) ? $post->ID : 0;

		// Fallback: try to get post ID from query.
		if ( ! $post_id && isset( $GLOBALS['ng_post'] ) && isset( $GLOBALS['ng_post']->ID ) ) {
			$post_id = $GLOBALS['ng_post']->ID;
		}

		if ( ! $post_id ) {
			return '';
		}

		// Get author ID.
		$author_id = get_post_field( 'post_author', $post_id );
		if ( ! $author_id ) {
			// Fallback to "Editorial Team" if no author.
			$author_id = 0;
		}

		// Get user data.
		$author = null;
		if ( $author_id ) {
			$author = get_userdata( $author_id );
		}

		// Use fallback if author doesn't exist.
		if ( ! $author ) {
			$author_name = __( 'Editorial Team', 'newsletter-glue' );
			$author_bio = '';
			$author_url = '';
			$has_author = false;
		} else {
			$has_author = true;
		}

		// Merge attributes with defaults.
		$defaults = $this->get_defaults();
		$attributes = wp_parse_args( $attributes, $defaults );

		// Extract attributes.
		$show_avatar		= (bool) $attributes['showAvatar'];
		$show_name		= (bool) $attributes['showName'];
		$show_bio		= (bool) $attributes['showBio'];
		$show_more_link	= (bool) $attributes['showMoreLink'];
		$max_bio_chars	= absint( $attributes['maxBioChars'] );
		$avatar_size	= absint( $attributes['avatarSize'] );
		$alignment		= sanitize_text_field( $attributes['alignment'] );

		// Validate alignment.
		if ( ! in_array( $alignment, array( 'left', 'center' ), true ) ) {
			$alignment = 'left';
		}

		// Get author data.
		if ( $has_author ) {
			$author_name	= $author->display_name;
			$author_bio		= get_the_author_meta( 'description', $author_id );
			$author_url		= get_author_posts_url( $author_id );
		}

		// Truncate bio if needed.
		if ( $show_bio && ! empty( $author_bio ) && $max_bio_chars > 0 ) {
			if ( mb_strlen( $author_bio ) > $max_bio_chars ) {
				$author_bio = mb_substr( $author_bio, 0, $max_bio_chars );
				$author_bio = trim( $author_bio );
				// Add ellipsis if truncated.
				$last_char = mb_substr( $author_bio, -1 );
				if ( ! in_array( $last_char, array( '.', '!', '?' ), true ) ) {
					$author_bio .= '...';
				}
			}
		}

		// Build email-safe HTML.
		$html = '';

		// Start table wrapper for email compatibility.
		$table_align = ( $alignment === 'center' ) ? 'center' : 'left';
		$html .= '<table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom: 16px; margin-top: 16px;">';
		$html .= '<tr>';
		$html .= '<td style="text-align: ' . esc_attr( $table_align ) . '; vertical-align: top;">';

		// Avatar.
		if ( $show_avatar ) {
			if ( $has_author ) {
				$avatar = get_avatar( $author_id, $avatar_size, '', $author_name, array( 'class' => 'ngl-author-avatar' ) );
			} else {
				// Use default avatar for fallback.
				$avatar = get_avatar( 0, $avatar_size, '', $author_name, array( 'class' => 'ngl-author-avatar' ) );
			}
			if ( $avatar ) {
				$html .= '<div style="margin-bottom: 12px;">';
				$html .= $avatar;
				$html .= '</div>';
			}
		}

		// Name.
		if ( $show_name ) {
			$html .= '<div style="margin-bottom: 8px;">';
			$html .= '<strong style="font-size: 16px; line-height: 1.4; color: #333;">' . esc_html( $author_name ) . '</strong>';
			$html .= '</div>';
		}

		// Bio.
		if ( $show_bio && ! empty( $author_bio ) ) {
			$html .= '<div style="margin-bottom: 12px; font-size: 14px; line-height: 1.6; color: #666;">';
			$html .= wp_kses_post( $author_bio );
			$html .= '</div>';
		}

		// More link.
		if ( $show_more_link && $has_author && $author_url ) {
			$html .= '<div style="margin-top: 8px;">';
			$html .= '<a href="' . esc_url( $author_url ) . '" target="_blank" rel="noopener noreferrer" style="color: #0088a0; text-decoration: none; font-size: 14px;">';
			$html .= esc_html__( 'More from this author →', 'newsletter-glue' );
			$html .= '</a>';
			$html .= '</div>';
		}

		// Close table.
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';

		return $html;

	}

}

return new NGL_Block_Author;