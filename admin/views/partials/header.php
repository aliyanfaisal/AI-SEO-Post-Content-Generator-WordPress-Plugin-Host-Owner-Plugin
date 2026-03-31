<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="aiscph-header">
	<div class="aiscph-header-brand">
		<div class="aiscph-header-titles">
			<h1><?php _e( 'AI SEO Host', 'aiscp-host' ); ?></h1>
			<span class="aiscph-version">v<?php echo AISCPH_VERSION; ?> &nbsp;·&nbsp; by Aliyan Faisal</span>
		</div>
	</div>
	<div class="aiscph-header-nav">
		<a href="<?php echo admin_url( 'admin.php?page=aiscph-dashboard' ); ?>" class="aiscph-nav-item <?php echo $current_page === 'aiscph-dashboard' ? 'active' : ''; ?>">
			<?php _e( 'API Settings', 'aiscp-host' ); ?>
		</a>
		<a href="<?php echo admin_url( 'admin.php?page=aiscph-subscriptions' ); ?>" class="aiscph-nav-item <?php echo $current_page === 'aiscph-subscriptions' ? 'active' : ''; ?>">
			<?php _e( 'Subscriptions', 'aiscp-host' ); ?>
		</a>
		<a href="<?php echo admin_url( 'admin.php?page=aiscph-log' ); ?>" class="aiscph-nav-item <?php echo $current_page === 'aiscph-log' ? 'active' : ''; ?>">
			<?php _e( 'Generation Log', 'aiscp-host' ); ?>
		</a>
	</div>
</div>
