<?php
/**
 * Logs settings view.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Get all posts that have newsletter data (both newsletterglue post type and regular posts)
$saved_types = get_option( 'newsletterglue_post_types' );
$post_types = array( 'newsletterglue' );

if ( ! empty( $saved_types ) ) {
	$custom_types = explode( ',', $saved_types );
	$post_types = array_merge( $post_types, $custom_types );
} else {
	$core_types = apply_filters( 'newsletterglue_supported_core_types', array() );
	$post_types = array_merge( $post_types, $core_types );
}

// Get all posts that have newsletter meta data
$newsletters = get_posts( array(
	'post_type'      => $post_types,
	'posts_per_page' => -1,
	'post_status'    => 'any',
	'orderby'        => 'date',
	'order'          => 'DESC',
	'meta_query'     => array(
		'relation' => 'OR',
		array(
			'key'     => '_newsletterglue',
			'compare' => 'EXISTS',
		),
		array(
			'key'     => '_ngl_results',
			'compare' => 'EXISTS',
		),
	),
) );

?>

<div class="ngl-metabox ngl-metabox-flex">
	<div class="ngl-metabox-header">
		<h3><?php _e( 'Newsletter Send Logs', 'newsletter-glue' ); ?></h3>
		<p class="description"><?php _e( 'View the send status and any errors for all newsletters.', 'newsletter-glue' ); ?></p>
	</div>
</div>

<?php if ( empty( $newsletters ) ) : ?>

	<div class="ngl-metabox ngl-metabox-flex">
		<div class="ngl-field">
			<p><?php _e( 'No newsletters found.', 'newsletter-glue' ); ?></p>
		</div>
	</div>

<?php else : ?>

	<div class="ngl-metabox ngl-metabox-flex">
		<div class="ngl-field" style="width: 100%;">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 30%;"><?php _e( 'Newsletter', 'newsletter-glue' ); ?></th>
						<th style="width: 15%;"><?php _e( 'Status', 'newsletter-glue' ); ?></th>
						<th style="width: 20%;"><?php _e( 'Last Send Attempt', 'newsletter-glue' ); ?></th>
						<th style="width: 35%;"><?php _e( 'Message / Error', 'newsletter-glue' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $newsletters as $newsletter ) : 
						$results = get_post_meta( $newsletter->ID, '_ngl_results', true );
						$last_result = get_post_meta( $newsletter->ID, '_ngl_last_result', true );
						$newsletter_data = get_post_meta( $newsletter->ID, '_newsletterglue', true );
						$is_sent = isset( $newsletter_data['sent'] ) && $newsletter_data['sent'];
					?>
						<tr>
							<td>
								<strong>
									<a href="<?php echo esc_url( get_edit_post_link( $newsletter->ID ) ); ?>">
										<?php echo esc_html( $newsletter->post_title ? $newsletter->post_title : __( '(No title)', 'newsletter-glue' ) ); ?>
									</a>
								</strong>
								<br>
								<small style="color: #666;">
									<?php 
									echo esc_html( get_the_date( 'Y/m/d g:i a', $newsletter->ID ) );
									if ( $newsletter->post_status !== 'publish' ) {
										echo ' <span style="color: #d63638;">(' . esc_html( ucfirst( $newsletter->post_status ) ) . ')</span>';
									}
									?>
								</small>
							</td>
							<td>
								<?php
								if ( $last_result && isset( $last_result['type'] ) ) {
									$type = $last_result['type'];
									$status_class = '';
									$status_text = '';
									
									if ( $type === 'success' ) {
										$status_class = 'ngl-success';
										$status_text = __( 'Sent', 'newsletter-glue' );
									} elseif ( $type === 'error' ) {
										$status_class = 'ngl-error';
										$status_text = __( 'Error', 'newsletter-glue' );
									} elseif ( $type === 'neutral' ) {
										$status_class = 'ngl-neutral';
										$status_text = __( 'Draft', 'newsletter-glue' );
									} elseif ( $type === 'schedule' ) {
										$status_class = 'ngl-schedule';
										$status_text = __( 'Scheduled', 'newsletter-glue' );
									} else {
										$status_class = 'ngl-neutral';
										$status_text = __( 'Unknown', 'newsletter-glue' );
									}
									
									echo '<span class="ngl-state ' . esc_attr( $status_class ) . '">' . esc_html( $status_text ) . '</span>';
								} elseif ( $is_sent ) {
									echo '<span class="ngl-state ngl-success">' . esc_html__( 'Sent', 'newsletter-glue' ) . '</span>';
								} else {
									echo '<span class="ngl-state ngl-neutral">' . esc_html__( 'Not Sent', 'newsletter-glue' ) . '</span>';
								}
								?>
							</td>
							<td>
								<?php
								if ( $results && is_array( $results ) && ! empty( $results ) ) {
									// Get the most recent result
									krsort( $results );
									$most_recent = reset( $results );
									$most_recent_time = key( $results );
									
									if ( $most_recent_time ) {
										echo esc_html( get_date_from_gmt( date( 'Y-m-d H:i:s', $most_recent_time ), 'Y/m/d g:i a' ) );
									} else {
										echo '—';
									}
								} else {
									echo '—';
								}
								?>
							</td>
							<td>
								<?php
								if ( $last_result && isset( $last_result['message'] ) ) {
									$message = $last_result['message'];
									$type = isset( $last_result['type'] ) ? $last_result['type'] : '';
									
									// Display the message
									echo '<div style="margin-bottom: 8px;">';
									echo wp_kses_post( $message );
									echo '</div>';
									
									// Display error details if available
									if ( $type === 'error' && isset( $last_result['error_details'] ) ) {
										$error_details = $last_result['error_details'];
										echo '<details style="margin-top: 8px;">';
										echo '<summary style="cursor: pointer; color: #2271b1; text-decoration: underline;">' . esc_html__( 'View error details', 'newsletter-glue' ) . '</summary>';
										echo '<pre style="background: #f0f0f1; padding: 10px; margin-top: 8px; overflow-x: auto; font-size: 12px; max-height: 200px; overflow-y: auto;">';
										echo esc_html( print_r( $error_details, true ) );
										echo '</pre>';
										echo '</details>';
									}
									
									// Display help link if available
									if ( isset( $last_result['help'] ) && ! empty( $last_result['help'] ) ) {
										echo '<div style="margin-top: 8px;">';
										echo '<a href="' . esc_url( $last_result['help'] ) . '" target="_blank" style="color: #2271b1;">';
										echo esc_html__( 'Get help', 'newsletter-glue' ) . ' <span class="dashicons dashicons-external" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span>';
										echo '</a>';
										echo '</div>';
									}
								} else {
									echo '<span style="color: #666;">—</span>';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

<?php endif; ?>

<style>
	.ngl-state {
		display: inline-block;
		padding: 4px 8px;
		border-radius: 3px;
		font-size: 12px;
		font-weight: 600;
	}
	.ngl-state.ngl-success {
		background: #00a32a;
		color: #fff;
	}
	.ngl-state.ngl-error {
		background: #d63638;
		color: #fff;
	}
	.ngl-state.ngl-neutral {
		background: #646970;
		color: #fff;
	}
	.ngl-state.ngl-schedule {
		background: #f0b849;
		color: #fff;
	}
	.wp-list-table details summary {
		user-select: none;
	}
	.wp-list-table details summary:hover {
		color: #135e96;
	}
</style>


