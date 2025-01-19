<?php

namespace WPSP\Social;

use DirkGroenen\Pinterest\Pinterest;
use myPHPNotes\LinkedIn;
use WPSP\Helper;

class InstantShare
{
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'instant_share_metabox'));
        add_action('save_post', array($this, 'instant_share_metabox_data_save'), 100, 2);
        // ajax request for fetch selected profile
        add_action('wp_ajax_wpscp_instant_share_fetch_profile', array($this, 'instant_share_fetch_profile'));
        add_action('wp_ajax_wpscp_instant_social_single_profile_share', array($this, 'instant_social_single_profile_share'));
        add_action('wpsp_instant_social_single_profile_share', array($this, 'instant_social_single_profile_share'));
    }

    public function instant_share_metabox()
    {
        $allow_post_types = \WPSP\Helper::get_all_allowed_post_type();
        $allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
        if( Helper::is_enable_classic_editor() ) {
            add_meta_box('WpScp_instantshare_meta_box', __('Social Share Settings', 'wp-scheduled-posts'), array($this, 'instant_share_metabox_markup'), $allow_post_types, 'side', 'low');
        }
    }
    public function instant_share_metabox_markup()
    {
        wp_nonce_field(basename(__FILE__), 'wpscp_pro_instant_social_share_nonce');
        // status=
        $twitterIntegation = \WPSP\Helper::get_settings('twitter_profile_status');
        $facebookIntegation = \WPSP\Helper::get_settings('facebook_profile_status');
        $linkedinIntegation = \WPSP\Helper::get_settings('linkedin_profile_status');
        $pinterestIntegation = \WPSP\Helper::get_settings('pinterest_profile_status');
        $instagramIntegation = \WPSP\Helper::get_settings('instagram_profile_status');
        $mediumIntegation = \WPSP\Helper::get_settings('medium_profile_status');
        $threadsIntegation = \WPSP\Helper::get_settings('threads_profile_status');
        // profile
        $facebookProfile = \WPSP\Helper::get_settings('facebook_profile_list');
        $twitterProfile = \WPSP\Helper::get_settings('twitter_profile_list');
        $linkedinProfile = \WPSP\Helper::get_settings('linkedin_profile_list');
        $pinterestProfile = \WPSP\Helper::get_settings('pinterest_profile_list');
        $instagramProfile = \WPSP\Helper::get_settings('instagram_profile_list');
        $mediumProfile = \WPSP\Helper::get_settings('medium_profile_list');
        $threadsProfile = \WPSP\Helper::get_settings('threads_profile_list');
        // already checked 'Helper::is_enable_classic_editor()'
    ?>
        <div class="wpscppro-instantshare">
            <!-- skip share -->
            <div>
                <label>
                    <input type="hidden" name="postid" id="wpscppropostid" value="<?php print get_the_ID(); ?>">
                    <input type="checkbox" id="wpscpprodontshare" name="wpscppro-dont-share-socialmedia" <?php checked('on', get_post_meta(get_the_ID(), '_wpscppro_dont_share_socialmedia', true), true); ?> /> <?php esc_html_e('Disable Social Share', 'wp-scheduled-posts') ?>
                </label>
            </div>
            <?php
            $metaInlineCss = "";
            if (get_post_meta(get_the_ID(), '_wpscppro_dont_share_socialmedia', true) == 'on') {
                $metaInlineCss = 'style="display: none;"';
            }
            ?>
            <div id="socialmedia" class="social-media" <?php print $metaInlineCss; ?>>
                <div class="wpscppro-custom-social-share-image">
                    <?php
                    $socialshareimage = get_post_meta(get_the_id(), '_wpscppro_custom_social_share_image', true);
                    ?>
                    <span class='upload'>
                        <input type='hidden' id='wpscppro_custom_social_share_image' class='regular-text text-upload' name='wpscppro_custom_social_share_image' value='<?php print $socialshareimage; ?>' />
                        <?php
                        if ($socialshareimage != "") :
                            $imageUrl = wp_get_attachment_image_src($socialshareimage, 'full');
                            if( !empty( $imageUrl[0] ) ) {
                                ?>
                                    <div>
                                        <img id="wpscpprouploadimagepreviewold" src="<?php print esc_url($imageUrl[0]); ?>" alt="image">
                                    </div>
                                <?php
                            }
                         endif; ?>
                        <div id="wpscpprouploadimagepreview"></div>
                        <input type='button' id="wpscppro_btn_meta_image_upload" class='button button-primary' value='Upload Social Share Banner' />
                        <input type="button" id="wpscppro_btn_remove_meta_image_upload" class="button button-danger" value="Remove Banner" <?php print($socialshareimage == "" ? 'style="display:none;"' : ''); ?>>
                    </span>
                </div>
                <?php if( $facebookIntegation == 'on' && $twitterIntegation == 'on' && $linkedinIntegation == 'on' && $pinterestIntegation == 'on' && $instagramIntegation == 'on' && $mediumIntegation == 'on' && $threadsIntegation == 'on' ) : ?>
                    <h4 class="meta-heading"><?php esc_html_e('Choose Social Share Platform', 'wp-scheduled-posts'); ?></h4>
                <?php endif ?>
                <ul>
                    <?php
                    if ($facebookIntegation == 'on' && is_array($facebookProfile) && count($facebookProfile) > 0) :
                        $facebookShareCount = get_post_meta(get_the_ID(), '__wpscppro_social_share_facebook');
                        // $isFacebook = get_post_meta(get_the_ID(), '_wpsp_is_facebook_share', true) ? get_post_meta(get_the_ID(), '_wpsp_is_facebook_share', true) : true;
                        $isFacebook = get_post_meta(get_the_ID(), '_wpsp_is_facebook_share', true);
                    ?>
                        <li class="facebook">
                            <label style="margin-bottom: 10px;">
                                <input type="checkbox" id="wpscpprofacebookis" name="_wpsp_is_facebook_share" <?php (!empty($isFacebook) ? checked('on', $isFacebook, true) : checked('', $isFacebook, true)  ); ?> /> <?php esc_html_e('Facebook', 'wp-scheduled-posts'); ?>
                                <?php
                                if (is_array($facebookShareCount) && count($facebookShareCount) > 0) :
                                ?>
                                    <span class="sharecount"><?php print count($facebookShareCount); ?></span>
                                <?php endif; ?>
                                <span class="ajaxrequest"></span>
                            </label>
                            <div class="errorlog"></div>
                        </li>
                    <?php
                    endif;
                    if ($twitterIntegation == 'on' && is_array($twitterProfile) && count($twitterProfile) > 0) :
                        $twitterShareCount = get_post_meta(get_the_ID(), '__wpscppro_social_share_twitter', true);
                        $isTwitter = get_post_meta(get_the_ID(), '_wpsp_is_twitter_share', true);
                    ?>
                        <li class="twitter">
                            <label style="margin-bottom: 10px;">
                                <input type="checkbox" id="wpscpprotwitteris" name="_wpsp_is_twitter_share" <?php (!empty($isTwitter) ? checked('on', $isTwitter, true) : checked('', $isTwitter, true)  ); ?> /> <?php esc_html_e('Twitter', 'wp-scheduled-posts'); ?>
                                <?php
                                if (is_array($twitterShareCount) && count($twitterShareCount) > 0) :
                                ?>
                                    <span class="sharecount"><?php print count($twitterShareCount); ?></span>
                                <?php
                                endif;
                                ?>
                                <span class="ajaxrequest"></span>
                            </label>
                            <div class="errorlog"></div>
                        </li>
                    <?php
                    endif;
                    if ($linkedinIntegation == 'on' && is_array($linkedinProfile) && count($linkedinProfile) > 0) :
                        $linkedinShareCount = get_post_meta(get_the_ID(), '__wpscppro_social_share_linkedin', true);
                        $isLinkedin = get_post_meta(get_the_ID(), '_wpsp_is_linkedin_share', true);
                    ?>
                        <li class="linkedin">
                            <label style="margin-bottom: 10px;">
                                <input type="checkbox" id="wpscpprolinkedinis" name="_wpsp_is_linkedin_share" <?php (!empty($isLinkedin) ? checked('on', $isLinkedin, true) : checked('', $isLinkedin, true)  ); ?> /> <?php esc_html_e('Linkedin', 'wp-scheduled-posts'); ?>
                                <?php
                                if (is_array($linkedinShareCount) && count($linkedinShareCount) > 0) :
                                ?>
                                    <span class="sharecount"><?php print count($linkedinShareCount); ?></span>
                                <?php
                                endif;
                                ?>
                                <span class="ajaxrequest"></span>
                            </label>
                            <div class="errorlog"></div>
                        </li>
                    <?php
                    endif;

                    if ($pinterestIntegation == 'on' && is_array($pinterestProfile) && count($pinterestProfile) > 0) :
                        $pinterestShareCount = get_post_meta(get_the_ID(), '__wpscppro_social_share_pinterest', true);
                        $pinterestCustomBoardName = get_post_meta(get_the_ID(), '_wpscppro_pinterest_board_name', true);
                        $pinterestCustomSectionName = get_post_meta(get_the_ID(), '_wpscppro_pinterest_section_name', true);
                        $pinterestBoardType = get_post_meta(get_the_ID(), '_wpscppro_pinterestboardtype', true);
                        $pinterestDefaultBoard = ($pinterestBoardType == "" ? 'default' : $pinterestBoardType);
                        $isPinterest = get_post_meta(get_the_ID(), '_wpsp_is_pinterest_share', true);
                    ?>
                        <li class="pinterest">
                            <label style="margin-bottom: 10px;">
                                <input type="checkbox" id="wpscppropinterestis" name="_wpsp_is_pinterest_share" <?php (!empty($isPinterest) ? checked('on', $isPinterest, true) : checked('', $isPinterest, true)  ); ?> /> <?php esc_html_e('Pinterest', 'wp-scheduled-posts'); ?>
                                <?php
                                if (is_array($pinterestShareCount) && count($pinterestShareCount) > 0) :
                                ?>
                                    <span class="sharecount"><?php print count($pinterestShareCount); ?></span>
                                <?php
                                endif;
                                ?>
                                <span class="ajaxrequest"></span>
                            </label>
                            <div class="boardname">
                                <label><input type="radio" name="pinterestboardtype" value="default" <?php checked($pinterestDefaultBoard, 'default', true); ?>><?php esc_html_e('Default Board', 'wp-scheduled-posts'); ?></label>
                                <label><input type="radio" name="pinterestboardtype" value="custom" <?php checked($pinterestDefaultBoard, 'custom', true); ?>><?php esc_html_e('Custom Board', 'wp-scheduled-posts'); ?> </label>

                                <div id="wpscppropinterestboardname" <?php print(($pinterestDefaultBoard == "default") ? 'style="display: none;"' : ''); ?>>
                                <?php
                                foreach ($pinterestProfile as $key => $profile) {
                                    if(!empty($profile->boards) && is_array($profile->boards)){
                                        $index = md5($profile->access_token);
                                        $selected_board = isset($pinterestCustomBoardName[$index]) ? $pinterestCustomBoardName[$index] : (isset($profile->default_board_name->value) ? $profile->default_board_name->value : '');
                                        $selected_section = isset($pinterestCustomSectionName[$index]) ? $pinterestCustomSectionName[$index] : (isset($profile->defaultSection->value) ? $profile->defaultSection->value : '');
                                        echo "<p>";
                                        echo "<label><b>Profile Name:</b> {$profile->name}</label>";
                                        echo "<label style='margin-top: 5px'>Boards</label>";
                                        echo "<select class='pinterest-board pinterest-select' name='wpscppro-pinterest-board-name[{$index}]'>";
                                        // echo "<option value='default'>Default ({$profile->default_board_name})</option>";
                                        foreach ($profile->boards as $board_key => $board) {
                                            $_selected = $selected_board === $board->id ? "selected='selected'" : '';
                                            echo "<option value='{$board->id}' $_selected>{$board->name}</option>";
                                        }
                                        echo "</select>";
                                        echo "<label style='margin-top: 5px'>Sections</label>";
                                        echo "<select class='pinterest-section pinterest-select' data-index='$key' name='wpscppro-pinterest-section-name[$index]' data-value='$selected_section'>";
                                        echo "</select>";
                                        echo "</p>";
                                    }
                                }
                                ?>
                                </div>
                            </div>
                            <div class="errorlog"></div>
                        </li>
                    <?php
                    endif;
                    if ($instagramIntegation == 'on' && is_array($instagramProfile) && count($instagramProfile) > 0) :
                        $instagramShareCount = get_post_meta(get_the_ID(), '__wpscppro_social_share_instagram');
                        // $isInstagram = get_post_meta(get_the_ID(), '_wpsp_is_instagram_share', true) ? get_post_meta(get_the_ID(), '_wpsp_is_instagram_share', true) : true;
                        $isInstagram = get_post_meta(get_the_ID(), '_wpsp_is_instagram_share', true);
                    ?>
                        <li class="instagram">
                            <label style="margin-bottom: 10px;">
                                <input type="checkbox" id="wpscpproinstagramis" name="_wpsp_is_instagram_share" <?php (!empty($isInstagram) ? checked('on', $isInstagram, true) : checked('', $isInstagram, true)  ); ?> /> <?php esc_html_e('Instagram', 'wp-scheduled-posts'); ?>
                                <?php
                                if (is_array($instagramShareCount) && count($instagramShareCount) > 0) :
                                ?>
                                    <span class="sharecount"><?php print count($instagramShareCount); ?></span>
                                <?php endif; ?>
                                <span class="ajaxrequest"></span>
                            </label>
                            <div class="errorlog"></div>
                        </li>
                    <?php
                    endif;
                    ?>
                    <?php
                    if ($mediumIntegation == 'on' && is_array($mediumProfile) && count($mediumProfile) > 0) :
                        $mediumShareCount = get_post_meta(get_the_ID(), '__wpscppro_social_share_medium');
                        // $isInstagram = get_post_meta(get_the_ID(), '_wpsp_is_instagram_share', true) ? get_post_meta(get_the_ID(), '_wpsp_is_instagram_share', true) : true;
                        $isMedium = get_post_meta(get_the_ID(), '_wpsp_is_medium_share', true);
                    ?>
                        <li class="medium">
                            <label style="margin-bottom: 10px;">
                                <input type="checkbox" id="wpscppromediumis" name="_wpsp_is_medium_share" <?php (!empty($isMedium) ? checked('on', $isMedium, true) : checked('', $isMedium, true)  ); ?> /> <?php esc_html_e('Medium', 'wp-scheduled-posts'); ?>
                                <?php
                                if (is_array($mediumShareCount) && count($mediumShareCount) > 0) :
                                ?>
                                    <span class="sharecount"><?php print count($mediumShareCount); ?></span>
                                <?php endif; ?>
                                <span class="ajaxrequest"></span>
                            </label>
                            <div class="errorlog"></div>
                        </li>
                    <?php
                    endif;
                    ?>
                    <?php
                    if ($threadsIntegation == 'on' && is_array($threadsProfile) && count($threadsProfile) > 0) :
                        $threadsShareCount = get_post_meta(get_the_ID(), '__wpscppro_social_share_threads');
                        $isThreads = get_post_meta(get_the_ID(), '_wpsp_is_threads_share', true);
                    ?>
                        <li class="threads">
                            <label style="margin-bottom: 10px;">
                                <input type="checkbox" id="wpscpprothreadsis" name="_wpsp_is_threads_share" <?php (!empty($isThreads) ? checked('on', $isThreads, true) : checked('', $isThreads, true)  ); ?> /> <?php esc_html_e('Threads', 'wp-scheduled-posts'); ?>
                                <?php
                                if (is_array($threadsShareCount) && count($threadsShareCount) > 0) :
                                ?>
                                    <span class="sharecount"><?php print count($threadsShareCount); ?></span>
                                <?php endif; ?>
                                <span class="ajaxrequest"></span>
                            </label>
                            <div class="errorlog"></div>
                        </li>
                    <?php
                    endif;
                    ?>
                    <?php if( $facebookIntegation != 'on' && $twitterIntegation != 'on' && $linkedinIntegation != 'on' && $pinterestIntegation != 'on' && $instagramIntegation != 'on' && $mediumIntegation != 'on' && $threadsIntegation != 'on' ) : ?>
                        <?php echo sprintf( __( 'You may forget to add or enable social media from <a href="%s">SchedulePress settings</a>. ', 'wp-scheduled-posts' ), admin_url('admin.php?page=schedulepress&tab=social-profile') ) ?>
                    <?php endif ?>
                </ul>
                <button id="wpscpproinstantsharenow" <?php echo ( $facebookIntegation != 'on' && $twitterIntegation != 'on' && $linkedinIntegation != 'on' && $pinterestIntegation != 'on' && $instagramIntegation != 'on' && $mediumIntegation != 'on' && $threadsIntegation != 'on' ) ? 'disabled' : '' ?> class="button button-primary button-large"><?php esc_html_e('Share Now', 'wp-scheduled-posts'); ?></button>
                <div class="wpscppro-ajax-status"></div>
            </div>
        </div>
    <?php

    }
    public function instant_share_metabox_data_save($post_id, $post)
    {
        if( Helper::is_enable_classic_editor() ) {
            if ( !did_action('wpsp_schedule_published') && (!isset($_POST['wpscp_pro_instant_social_share_nonce']) || !wp_verify_nonce($_POST['wpscp_pro_instant_social_share_nonce'], basename(__FILE__)))) {
                return;
            }
        }
        //don't do anything for autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        //check if user has permission to edit posts otherwise don't do anything
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        // save post meta
        if (isset($_POST['wpscppro_custom_social_share_image'])) {
            update_post_meta($post_id, '_wpscppro_custom_social_share_image', sanitize_text_field($_POST['wpscppro_custom_social_share_image']));
        }
        if( Helper::is_enable_classic_editor() ) {
            update_post_meta($post_id, '_wpscppro_dont_share_socialmedia', sanitize_text_field((isset($_POST['wpscppro-dont-share-socialmedia']) ? $_POST['wpscppro-dont-share-socialmedia'] : 'off')));
        }
        // facebook
        if ( Helper::is_enable_classic_editor()) {
            update_post_meta($post_id, '_wpsp_is_facebook_share', sanitize_text_field((isset($_POST['_wpsp_is_facebook_share']) ? $_POST['_wpsp_is_facebook_share'] : 'off')));
        }
        
        // twitter
        if ( Helper::is_enable_classic_editor() ) {
            update_post_meta($post_id, '_wpsp_is_twitter_share', sanitize_text_field((isset($_POST['_wpsp_is_twitter_share']) ? $_POST['_wpsp_is_twitter_share'] : 'off')));
        }
        
        // linkedin
        if ( Helper::is_enable_classic_editor() ) {
            update_post_meta($post_id, '_wpsp_is_linkedin_share', sanitize_text_field( (isset($_POST['_wpsp_is_linkedin_share']) ? $_POST['_wpsp_is_linkedin_share'] : 'off')) );
        }
        // pinterest
        if ( Helper::is_enable_classic_editor() ) {
            update_post_meta($post_id, '_wpsp_is_pinterest_share', sanitize_text_field((isset($_POST['_wpsp_is_pinterest_share']) ? $_POST['_wpsp_is_pinterest_share'] : 'off')));
        }
        

        // pinterest meta checkbox
        if (isset($_POST['pinterestboardtype'])) {
            update_post_meta($post_id, '_wpscppro_pinterestboardtype', sanitize_text_field($_POST['pinterestboardtype']));
        }
        // pinterest meta board name save
        if (isset($_POST['wpscppro-pinterest-board-name']) && is_array($_POST['wpscppro-pinterest-board-name'])) {
            $board_names = array_filter($_POST['wpscppro-pinterest-board-name'], 'sanitize_text_field');
            update_post_meta($post_id, '_wpscppro_pinterest_board_name', $board_names);
        }
        if (isset($_POST['wpscppro-pinterest-section-name']) && is_array($_POST['wpscppro-pinterest-section-name'])) {
            $section_names = array_filter($_POST['wpscppro-pinterest-section-name'], 'sanitize_text_field');
            update_post_meta($post_id, '_wpscppro_pinterest_section_name', $section_names);
        }
        update_post_meta( $post_id, '_facebook_share_type', 'default' );
        update_post_meta( $post_id, '_twitter_share_type', 'default' );
        update_post_meta( $post_id, '_linkedin_share_type', 'default' );
        update_post_meta( $post_id, '_pinterest_share_type', 'default' );
        update_post_meta( $post_id, '_instagram_share_type', 'default' );
        update_post_meta( $post_id, '_medium_share_type', 'default' );
        update_post_meta( $post_id, '_medium_share_type', 'default' );
        $facebookProfile  = \WPSP\Helper::get_settings('facebook_profile_list');
        $twitterProfile   = \WPSP\Helper::get_settings('twitter_profile_list');
        $linkedinProfile  = \WPSP\Helper::get_settings('linkedin_profile_list');
        $pinterestProfile = \WPSP\Helper::get_settings('pinterest_profile_list');
        $instagramProfile = \WPSP\Helper::get_settings('instagram_profile_list');
        $mediumProfile = \WPSP\Helper::get_settings('medium_profile_list');
        $selectedSocialProfiles = [];
        $facebookProfile  = is_array( $facebookProfile ) ? $facebookProfile : [];
        $twitterProfile   = is_array( $twitterProfile ) ? $twitterProfile : [];
        $linkedinProfile  = is_array( $linkedinProfile ) ? $linkedinProfile : [];
        $pinterestProfile = is_array( $pinterestProfile ) ? $pinterestProfile : [];
        $instagramProfile = is_array( $instagramProfile ) ? $instagramProfile : [];
        $mediumProfile = is_array( $mediumProfile ) ? $mediumProfile : [];
        $selectedSocialProfiles = array_merge( $facebookProfile, $selectedSocialProfiles );
        $selectedSocialProfiles = array_merge( $twitterProfile, $selectedSocialProfiles );
        $selectedSocialProfiles = array_merge( $linkedinProfile, $selectedSocialProfiles );
        $selectedSocialProfiles = array_merge( $pinterestProfile, $selectedSocialProfiles );
        $selectedSocialProfiles = array_merge( $instagramProfile, $selectedSocialProfiles );
        $selectedSocialProfiles = array_merge( $mediumProfile, $selectedSocialProfiles );
        if( Helper::is_enable_classic_editor() ) {
            update_post_meta( $post_id, '_selected_social_profile', json_decode( json_encode( $selectedSocialProfiles ), true ) ); 
        }
    }



    /**
     * aja request call back
     * fetch selected profile
     */
    public function instant_share_fetch_profile()
    {
        
         // Verify nonce
        $nonce = sanitize_text_field($_REQUEST['_nonce']);
        if (!wp_verify_nonce($nonce, 'wpscp-pro-social-profile')) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'wp-scheduled-posts')], 401);
            die();
        }
 
         if( !Helper::is_user_allow() ) {
             wp_send_json_error( [ 'message' => __('You are unauthorized to access social profiles.', 'wp-scheduled-posts') ], 401 );
             wp_die();
         }

        $allProfile = array();
        $facebook_selected_profiles  = !empty( $_REQUEST['facebook_selected_profiles'] ) ? array_map( 'sanitize_text_field', $_REQUEST['facebook_selected_profiles'] ) : [];
        $twitter_selected_profiles   = !empty( $_REQUEST['twitter_selected_profiles'] ) ? array_map( 'sanitize_text_field', $_REQUEST['twitter_selected_profiles'] ) : [];
        $linkedin_selected_profiles  = !empty( $_REQUEST['linkedin_selected_profiles'] ) ? array_map( 'sanitize_text_field', $_REQUEST['linkedin_selected_profiles'] ) : [];
        $pinterest_selected_profiles = !empty( $_REQUEST['pinterest_selected_profiles'] ) ? array_map( 'sanitize_text_field', $_REQUEST['pinterest_selected_profiles'] ) : [];
        $instagram_selected_profiles = !empty( $_REQUEST['instagram_selected_profiles'] ) ? array_map( 'sanitize_text_field', $_REQUEST['instagram_selected_profiles'] ) : [];
        $medium_selected_profiles    = !empty( $_REQUEST['medium_selected_profiles'] ) ? array_map( 'sanitize_text_field', $_REQUEST['medium_selected_profiles'] ) : [];
        $threads_selected_profiles   = !empty( $_REQUEST['threads_selected_profiles'] ) ? array_map( 'sanitize_text_field', $_REQUEST['threads_selected_profiles'] ) : [];

        // get data from db
        $facebook  = \WPSP\Helper::get_social_profile(WPSCP_FACEBOOK_OPTION_NAME, $facebook_selected_profiles);
        $twitter   = \WPSP\Helper::get_social_profile(WPSCP_TWITTER_OPTION_NAME, $twitter_selected_profiles);
        $linkedin  = \WPSP\Helper::get_social_profile(WPSCP_LINKEDIN_OPTION_NAME, $linkedin_selected_profiles);
        $pinterest = \WPSP\Helper::get_social_profile(WPSCP_PINTEREST_OPTION_NAME);
        if( !empty( $pinterest_selected_profiles ) ) {
            $pinterest = array_filter( $pinterest, function($single_pinterest) use( $pinterest_selected_profiles ){
                return in_array( $single_pinterest->default_board_name->value, $pinterest_selected_profiles );
            } );
        }
        $instagram = \WPSP\Helper::get_social_profile(WPSCP_INSTAGRAM_OPTION_NAME, $instagram_selected_profiles);
        $medium    = \WPSP\Helper::get_social_profile(WPSCP_MEDIUM_OPTION_NAME, $medium_selected_profiles);
        $threads   = \WPSP\Helper::get_social_profile(WPSCP_THREADS_OPTION_NAME, $threads_selected_profiles);

        // get data from ajax request
        $is_facebook_share  = !empty( $_REQUEST['is_facebook_share'] ) ? sanitize_text_field( $_REQUEST['is_facebook_share'] ) : null;
        $is_twitter_share   = !empty( $_REQUEST['is_twitter_share'] ) ? sanitize_text_field( $_REQUEST['is_twitter_share'] ) : null;
        $is_linkedin_share  = !empty( $_REQUEST['is_linkedin_share'] ) ? sanitize_text_field( $_REQUEST['is_linkedin_share'] ) : null;
        $is_pinterest_share = !empty( $_REQUEST['is_pinterest_share'] ) ? sanitize_text_field( $_REQUEST['is_pinterest_share'] ) : null;
        $is_instagram_share = !empty( $_REQUEST['is_instagram_share'] ) ? sanitize_text_field( $_REQUEST['is_instagram_share'] ) : null;
        $is_medium_share = !empty( $_REQUEST['is_medium_share'] ) ? sanitize_text_field( $_REQUEST['is_medium_share'] ) : null;
        $is_threads_share = !empty( $_REQUEST['is_threads_share'] ) ? sanitize_text_field( $_REQUEST['is_threads_share'] ) : null;

        if ($is_facebook_share === "true") {
            $allProfile['facebook'] = $facebook;
        }
        if ($is_twitter_share === "true") {
            $allProfile['twitter'] = $twitter;
        }
        if ($is_linkedin_share === "true") {
            $allProfile['linkedin'] = $linkedin;
        }
        if ($is_pinterest_share === "true") {
            $allProfile['pinterest'] = $pinterest;
        }
        if ($is_instagram_share === "true") {
            $allProfile['instagram'] = $instagram;
        }
        if ($is_medium_share === "true") {
            $allProfile['medium'] = $medium;
        }
        if ($is_threads_share === "true") {
            $allProfile['threads'] = $threads;
        }

        $markup = '';
        if (is_array($allProfile) && count($allProfile) > 0) {
            foreach ($allProfile as $profileName => $profile) {
                $markup .= '<div class="entry-head ' . $profileName . '">
                        <img src="' . WPSP_ASSETS_URI . 'images/icon-' . $profileName . '-small-white.png' . '" alt="logo" />
                        <h2 class="entry-head-title">' . $profileName . '</h2>
                    </div>
                    <ul class="autoOverflowModal">';
                foreach ($profile as $key => $profileItem) {
                    if ($profileItem->status == false) {
                        unset($allProfile[$profileName][$key]);
                        continue;
                    }
                    if(isset($profileItem->type)){
                        if('organization' === $profileItem->type){
                            $profileItem->type = 'Page';
                        }
                        else if('person' === $profileItem->type){
                            $profileItem->type = 'Profile';
                        }
                    }
                    $markup .= '<li id="' . $profileName . '_' . $key . '">
                            <div class="item-content">
                                ' . (isset($profileItem->thumbnail_url) ? '<div class="entry-thumbnail"><img src="' . $profileItem->thumbnail_url . '" alt="logo"></div>' : '') . '
                                <h4 class="entry-title">' . $profileItem->name . '</h4>
                                ' . (isset($profileItem->type) ? '<span class="type">' . ucfirst($profileItem->type) . '</span>' : '') . '
                                <span class="entry-status">
                                    <span class="status">Request Sending...</span>
                                </span>
                            </div>
                            <div class="entry-log"><div>
                        </li>';
                }
                $markup .= '</ul>';
            }
        } else {
            $markup .= esc_html__("Failed!, You didn't select any social media.", 'wp-scheduled-posts');
        }

        wp_send_json(array('markup' => $markup, 'profile' => $allProfile));
        wp_die();
    }


    public function instant_social_single_profile_share($params)
    {
        if( !wp_doing_ajax() ) {
            $_GET = $params;
        }
        // Verify nonce
        $nonce = sanitize_text_field($_GET['nonce']);
        if (!wp_verify_nonce($nonce, 'wpscp-pro-social-profile')) {
            wp_send_json_error(['message' => __('Invalid nonce.', 'wp-scheduled-posts')], 401);
            die();
        }
        
        // Check user capability
        if( !Helper::is_user_allow() ) {
            wp_send_json_error( [ 'message' => __('You are unauthorized to access social profiles.', 'wp-scheduled-posts') ], 401 );
            wp_die();
        }

        $postid = intval($_GET['postid']);
        $platform = (isset($_GET['platform']) ? sanitize_text_field($_GET['platform']) : '');
        $is_share_on_publish = (isset($_GET['share_on_publish']) ? $_GET['share_on_publish'] : false);
        $profileID = (isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '');
        $platformKey = (isset($_GET['platformKey']) ? sanitize_text_field($_GET['platformKey']) : '');
        $pinterest_board_type = (isset($_GET['pinterest_board_type']) ? sanitize_text_field($_GET['pinterest_board_type']) : '');
        $pinterestBoardName = (isset($_GET['pinterest_custom_board_name']) ? sanitize_text_field($_GET['pinterest_custom_board_name']) : '');
        $pinterestSectionName = (isset($_GET['pinterest_custom_section_name']) ? sanitize_text_field($_GET['pinterest_custom_section_name']) : '');
        $pinterestCustomSectionName = explode( '|', $pinterestSectionName );
        if( !empty( $pinterestCustomSectionName[0] ) ) {
            $pinterestSectionName = $pinterestCustomSectionName[0];
        }
        // all social platform
        if ($platform == 'facebook') {
            $facebook = \WPSP\Helper::get_social_profile(WPSCP_FACEBOOK_OPTION_NAME);
            $platformKey = !empty( $profileID ) ? array_search($profileID, array_column($facebook, 'id')) : intval($platformKey);
            if ($facebook[$platformKey]->status == false) {
                wp_die();
            }
            // share
            $facebookshare = new \WPSP\Social\Facebook();
            $facebookshare->socialMediaInstantShare(
                $facebook[$platformKey]->app_id,
                $facebook[$platformKey]->app_secret,
                $facebook[$platformKey]->access_token,
                $facebook[$platformKey]->type,
                $facebook[$platformKey]->id,
                $postid,
                $platformKey,
                $is_share_on_publish
            );
            if( !$is_share_on_publish ) {
                wp_die();
            }
        } else if ($platform == 'twitter') {
            $twitter = \WPSP\Helper::get_social_profile(WPSCP_TWITTER_OPTION_NAME);
            $platformKey = !empty( $profileID ) ? array_search($profileID, array_column($twitter, 'id')) : intval($platformKey);
            // if disable account then it will be off
            if ($twitter[$platformKey]->status == false) {
                wp_die();
            }
            // share
            $wpscptwitter = new \WPSP\Social\Twitter();
            $wpscptwitter->socialMediaInstantShare(
                $twitter[$platformKey]->app_id,
                $twitter[$platformKey]->app_secret,
                $twitter[$platformKey]->oauth_token,
                $twitter[$platformKey]->oauth_token_secret,
                $postid,
                $platformKey,
                $is_share_on_publish
            );
            if( !$is_share_on_publish ) {
                wp_die();
            }
        } else if ($platform == 'linkedin') {
            $linkedin = \WPSP\Helper::get_social_profile(WPSCP_LINKEDIN_OPTION_NAME);
            $platformKey = !empty( $profileID ) ? array_search($profileID, array_column($linkedin, 'id')) : intval($platformKey);
            // if disable account then it will be off
            if ($linkedin[$platformKey]->status == false) {
                wp_die();
            }
            // share
            $linkedinshare = new \WPSP\Social\Linkedin();
            $linkedinshare->socialMediaInstantShare(
                $postid,
                $platformKey,
                $is_share_on_publish,
            );
            if( !$is_share_on_publish ) {
                wp_die();
            }
        } else if ($platform == 'pinterest') {
            $pinterest = \WPSP\Helper::get_social_profile(WPSCP_PINTEREST_OPTION_NAME);
            $platformKey = !empty( $profileID ) ? array_search($profileID, array_column($pinterest, 'id')) : intval($platformKey);
            // if disable account then it will be off
            if ($pinterest[$platformKey]->status == false) {
                wp_die();
            }
            // share
            $pinterestshare = new \WPSP\Social\Pinterest();
            $pinterestshare->socialMediaInstantShare(
                $postid,
                ($pinterest_board_type === "custom" ? $pinterestBoardName : $pinterest[$platformKey]->default_board_name),
                ($pinterest_board_type === "custom" ? $pinterestSectionName : $pinterest[$platformKey]->defaultSection),
                $platformKey,
                $is_share_on_publish,
            );
            if( !$is_share_on_publish ) {
                wp_die();
            }
        } else if ($platform == 'instagram') {
            $instagram = \WPSP\Helper::get_social_profile(WPSCP_INSTAGRAM_OPTION_NAME);
            $platformKey = !empty( $profileID ) ? array_search($profileID, array_column($instagram, 'id')) : intval($platformKey);
            if ($instagram[$platformKey]->status == false) {
                wp_die();
            }
            // share
            $instagramshare = new \WPSP\Social\Instagram();
            $instagramshare->socialMediaInstantShare(
                $instagram[$platformKey]->app_id,
                $instagram[$platformKey]->app_secret,
                $instagram[$platformKey]->access_token,
                $instagram[$platformKey]->type,
                $instagram[$platformKey]->id,
                $postid,
                $platformKey,
                $is_share_on_publish,
            );
            if( !$is_share_on_publish ) {
                wp_die();
            }
        } else if ($platform == 'medium') {
            $medium = \WPSP\Helper::get_social_profile(WPSCP_MEDIUM_OPTION_NAME);
            $platformKey = !empty( $profileID ) ? array_search($profileID, array_column($medium, 'id')) : intval($platformKey);
            if ($medium[$platformKey]->status == false) {
                wp_die();
            }
            $mediumshare = new \WPSP\Social\Medium();
            $mediumshare->socialMediaInstantShare(
                $medium[$platformKey]->app_id,
                $medium[$platformKey]->app_secret,
                $medium[$platformKey]->access_token,
                $medium[$platformKey]->type,
                $medium[$platformKey]->__id,
                $postid,
                $platformKey,
                $medium[$platformKey]->id,
                $is_share_on_publish,
            );
            if( !$is_share_on_publish ) {
                wp_die();
            }
        }  else if ($platform == 'threads') {
            $threads = \WPSP\Helper::get_social_profile(WPSCP_THREADS_OPTION_NAME);
            $platformKey = !empty( $profileID ) ? array_search($profileID, array_column($threads, 'id')) : intval($platformKey);
            if ($threads[$platformKey]->status == false) {
                wp_die();
            }
            $threads_share = new \WPSP\Social\Threads();
            $threads_share->socialMediaInstantShare(
                $threads[$platformKey]->app_id,
                $threads[$platformKey]->app_secret,
                $threads[$platformKey]->access_token,
                $threads[$platformKey]->type,
                $threads[$platformKey]->id,
                $postid,
                $platformKey,
                $is_share_on_publish,
            );
            if( !$is_share_on_publish ) {
                wp_die();
            }
        } else {
            wp_send_json_error(__('Sorry, your requested platform integration is not added.', 'wp-scheduled-posts'));
            wp_die();
        }
    }
}
