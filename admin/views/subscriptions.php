<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="aiscph-wrap">

	<?php include __DIR__ . '/partials/header.php'; ?>

	<div class="aiscph-page-content">

		<div class="aiscph-card">
			<div class="aiscph-card-header">
				<h2><?php _e( 'Active Subscriptions', 'aiscp-host' ); ?></h2>
				<p><?php _e( 'Domains with active subscriptions connected to this host.', 'aiscp-host' ); ?></p>
			</div>
			<div class="aiscph-card-body">
				<div class="aiscph-empty-state">
					<div class="empty-state-icon">📋</div>
					<h3><?php _e( 'No subscriptions yet', 'aiscp-host' ); ?></h3>
					<p><?php _e( 'Active subscriptions will appear here once the subscription system is connected.', 'aiscp-host' ); ?></p>
				</div>
			</div>
		</div>

	</div>
</div>
