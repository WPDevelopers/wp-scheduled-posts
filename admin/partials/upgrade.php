<?php 
	if( class_exists('WpScp_Pro') ) {
		$link = 'https://wpdeveloper.net/account';
		$button_title = 'Manage License';
		$p_title = 'WP Scheduled Posts Pro';
	}else {
		$link = 'http://wpdeveloper.net/in/wp-scheduled-posts';
		$button_title = 'UPGRADE TO PRO';
		$p_title = 'WP Scheduled Posts';
	}
?>
<div class="wpsp_pro_features_upgrade">
	<h1 class="wpsp_promo_title"><?php esc_html_e('WP Scheduled Posts Pro', 'wp-scheduled-posts'); ?></h1>
	<img src="<?php echo plugins_url(); ?>/wp-scheduled-posts/admin/assets/images/wpsp.png" alt="">
	<h2><?php echo $p_title; ?></h2>
	<a href="<?php echo $link; ?>"><?php echo $button_title; ?></a>
</div>