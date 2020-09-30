<div class="wpsp-dashboard-body">
    <div class="wpsp_loader">
        <img src="<?php echo esc_url(WPSP_ASSETS_URI . 'images/wpscp-logo.gif'); ?>" alt="Loader">
    </div>
    <?php
    //include topbar page
    include WPSP_VIEW_DIR_PATH . 'topbar.php';
    //include license page
    include WPSP_VIEW_DIR_PATH . 'license.php';
    // main option pages
    include WPSP_VIEW_DIR_PATH . 'options.php';
    // social integations
    include WPSP_VIEW_DIR_PATH . 'integrations-settings.php';
    // social templates
    include WPSP_VIEW_DIR_PATH . 'social-settings.php';
    // pro setting will be show here
    do_action('wpscp_pro_options_settings');
    //manage schedule template
    do_action('wpsp_manage_schedule');
    ?>
</div>