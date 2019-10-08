<?php 
	$lic_content_active = '';
	if( isset($_GET['wpsptab']) ) {
		$tab = $_GET['wpsptab'];

		if($tab == 'license') {
			$lic_content_active = 'wpsp_nav_tab_content_active';
		}
	}
?>
<div class="wpsp_nav_tab_content <?php echo $lic_content_active; ?>" id="wpsp-wpsp_lic">

	<div class="wpsp-admin-sidebar">
		
		<div class="wpsp-sidebar-block wpsp-license-block">
			<?php include WPSCP_ADMIN_DIR_PATH . 'partials/upgrade.php'; ?>
			<?php
			    do_action( 'wpsp_licensing' );
			?>
		</div>
	</div><!--admin sidebar end-->
</div>