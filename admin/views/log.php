<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="aiscph-wrap">

	<?php include __DIR__ . '/partials/header.php'; ?>

	<div class="aiscph-page-content">

		<?php if ( $log_enabled !== '1' ) : ?>
			<div class="aiscph-card">
				<div class="aiscph-card-body">
					<div class="aiscph-empty-state">
						<div class="empty-state-icon">🔕</div>
						<h3><?php _e( 'Generation Log is Disabled', 'aiscp-host' ); ?></h3>
						<p><?php _e( 'Enable the Generation Log in API Settings to start recording post generation activity.', 'aiscp-host' ); ?></p>
						<a href="<?php echo admin_url( 'admin.php?page=aiscph-dashboard' ); ?>" class="aiscph-btn aiscph-btn-primary">
							<?php _e( 'Go to Settings →', 'aiscp-host' ); ?>
						</a>
					</div>
				</div>
			</div>

		<?php elseif ( empty( $log_entries ) ) : ?>
			<div class="aiscph-card">
				<div class="aiscph-card-body">
					<div class="aiscph-empty-state">
						<div class="empty-state-icon">📭</div>
						<h3><?php _e( 'No log entries yet', 'aiscp-host' ); ?></h3>
						<p><?php _e( 'Post generation activity will appear here once client sites start sending requests.', 'aiscp-host' ); ?></p>
					</div>
				</div>
			</div>

		<?php else : ?>
			<div class="aiscph-card">
				<div class="aiscph-card-header aiscph-card-header--flex">
					<div>
						<h2><?php _e( 'Generation Log', 'aiscp-host' ); ?></h2>
						<p><?php printf( __( '%d entries recorded', 'aiscp-host' ), count( $log_entries ) ); ?></p>
					</div>
					<button type="button" id="aiscph-clear-log-btn" class="aiscph-btn aiscph-btn-danger-outline">
						<?php _e( 'Clear Log', 'aiscp-host' ); ?>
					</button>
				</div>
				<div class="aiscph-card-body aiscph-log-card-body">
					<table class="aiscph-log-table">
						<thead>
							<tr>
								<th><?php _e( 'Time', 'aiscp-host' ); ?></th>
								<th><?php _e( 'Domain', 'aiscp-host' ); ?></th>
								<th><?php _e( 'Message', 'aiscp-host' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $log_entries as $entry ) : ?>
							<tr>
								<td class="log-time"><?php echo esc_html( $entry['time'] ); ?></td>
								<td class="log-domain">
									<?php if ( ! empty( $entry['domain'] ) ) : ?>
										<a href="<?php echo esc_url( $entry['domain'] ); ?>" target="_blank">
											<?php echo esc_html( str_replace( array( 'https://', 'http://' ), '', $entry['domain'] ) ); ?>
										</a>
									<?php else : ?>—<?php endif; ?>
								</td>
								<td class="log-message"><?php echo esc_html( $entry['message'] ); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		<?php endif; ?>

	</div>
</div>
