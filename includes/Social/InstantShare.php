<?php

namespace WPSP\Social;

use DirkGroenen\Pinterest\Pinterest;
use myPHPNotes\LinkedIn;


class InstantShare
{
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'instant_share_metabox'));
        add_action('save_post', array($this, 'instant_share_metabox_data_save'), 10, 2);
        // ajax request for fetch selected profile
        add_action('wp_ajax_wpscp_instant_share_fetch_profile', array($this, 'instant_share_fetch_profile'));
        add_action('wp_ajax_wpscp_instant_social_single_profile_share', array($this, 'instant_social_single_profile_share'));
    }
    public function instant_share_metabox()
    {
        $allow_post_types = \WPSP\Helper::get_settings('allow_post_types');
        $allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
        add_meta_box('WpScp_instantshare_meta_box', __('Social Share Settings', 'wp-scheduled-posts'), array($this, 'instant_share_metabox_markup'), $allow_post_types, 'side', 'low');
    }
    public function instant_share_metabox_markup()
    {
        wp_nonce_field(basename(__FILE__), 'wpscp_pro_instant_social_share_nonce');
        // status
        $twitterIntegation = \WPSP\Helper::get_settings('twitter_profile_status');
        $facebookIntegation = \WPSP\Helper::get_settings('facebook_profile_status');
        $linkedinIntegation = \WPSP\Helper::get_settings('linkedin_profile_status');
        $pinterestIntegation = \WPSP\Helper::get_settings('pinterest_profile_status');
        // profile
        $facebookProfile = \WPSP\Helper::get_settings('facebook_profile_list');
        $twitterProfile = \WPSP\Helper::get_settings('twitter_profile_list');
        $linkedinProfile = \WPSP\Helper::get_settings('linkedin_profile_list');
        $pinterestProfile = \WPSP\Helper::get_settings('pinterest_profile_list');
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
                        ?>
                            <div>
                                <img id="wpscpprouploadimagepreviewold" src="<?php print esc_url($imageUrl[0]); ?>" alt="image">
                            </div>
                        <?php endif; ?>
                        <div id="wpscpprouploadimagepreview"></div>
                        <input type='button' id="wpscppro_btn_meta_image_upload" class='button button-primary' value='Upload Social Share Banner' />
                        <input type="button" id="wpscppro_btn_remove_meta_image_upload" class="button button-danger" value="Remove Banner" <?php print($socialshareimage == "" ? 'style="display:none;"' : ''); ?>>
                    </span>
                </div>
                <h4 class="meta-heading"><?php esc_html_e('Choose Social Share Platform', 'wp-scheduled-posts'); ?></h4>
                <ul>
                    <?php
                    if ($facebookIntegation == 'on' && is_array($facebookProfile) && count($facebookProfile) > 0) :
                        $facebookShareCount = get_post_meta(get_the_ID(), '__wpscppro_social_share_facebook');
                        $isFacebook = get_post_meta(get_the_ID(), '_wpsp_is_facebook_share', true);
                    ?>
                        <li class="facebook">
                            <label>
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
                            <label>
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
                            <label>
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
                        $pinterestBoardType = get_post_meta(get_the_ID(), '_wpscppro_pinterestboardtype', true);
                        $pinterestDefaultBoard = ($pinterestBoardType == "" ? 'default' : $pinterestBoardType);
                        $isPinterest = get_post_meta(get_the_ID(), '_wpsp_is_pinterest_share', true);
                    ?>
                        <li class="pinterest">
                            <label>
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
                            <p class="boardname">
                                <label><input type="radio" name="pinterestboardtype" value="default" <?php checked($pinterestDefaultBoard, 'default', true); ?>><?php esc_html_e('Default Board', 'wp-scheduled-posts'); ?></label>
                                <label><input type="radio" name="pinterestboardtype" value="custom" <?php checked($pinterestDefaultBoard, 'custom', true); ?>><?php esc_html_e('Custom Board', 'wp-scheduled-posts'); ?> </label>
                                <input type="text" id="wpscppropinterestboardname" name="wpscppro-pinterest-board-name" placeholder="pinterest_username/boardname" value="<?php print($pinterestCustomBoardName != "" ? $pinterestCustomBoardName : ''); ?>" <?php print(($pinterestDefaultBoard == "default") ? 'style="display: none;"' : ''); ?>>
                            </p>
                            <div class="errorlog"></div>
                        </li>
                    <?php
                    endif;
                    ?>
                </ul>
                <button id="wpscpproinstantsharenow" class="button button-primary button-large"><?php esc_html_e('Share Now', 'wp-scheduled-posts'); ?></button>
                <div class="wpscppro-ajax-status"></div>
            </div>
        </div>
<?php
    }
    public function instant_share_metabox_data_save($post_id, $post)
    {
        if (!isset($_POST['wpscp_pro_instant_social_share_nonce']) || !wp_verify_nonce($_POST['wpscp_pro_instant_social_share_nonce'], basename(__FILE__))) {
            return;
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
        if (isset($_POST['wpscppro-dont-share-socialmedia'])) {
            update_post_meta($post_id, '_wpscppro_dont_share_socialmedia', sanitize_text_field($_POST['wpscppro-dont-share-socialmedia']));
        } else {
            delete_post_meta($post_id, '_wpscppro_dont_share_socialmedia');
        }
        // facebook
        update_post_meta($post_id, '_wpsp_is_facebook_share', sanitize_text_field((isset($_POST['_wpsp_is_facebook_share']) ? $_POST['_wpsp_is_facebook_share'] : 'off')));
        
        // twitter
        update_post_meta($post_id, '_wpsp_is_twitter_share', sanitize_text_field((isset($_POST['_wpsp_is_twitter_share']) ? $_POST['_wpsp_is_twitter_share'] : 'off')));
        
        // linkedin
        update_post_meta($post_id, '_wpsp_is_linkedin_share', sanitize_text_field((isset($_POST['_wpsp_is_linkedin_share']) ? $_POST['_wpsp_is_linkedin_share'] : 'off')));
        
        // pinterest
        update_post_meta($post_id, '_wpsp_is_pinterest_share', sanitize_text_field((isset($_POST['_wpsp_is_pinterest_share']) ? $_POST['_wpsp_is_pinterest_share'] : 'off')));

        // pinterest meta checkbox
        if (isset($_POST['pinterestboardtype'])) {
            update_post_meta($post_id, '_wpscppro_pinterestboardtype', sanitize_text_field($_POST['pinterestboardtype']));
        }
        // pinterest meta board name save
        if (isset($_POST['wpscppro-pinterest-board-name'])) {
            update_post_meta($post_id, '_wpscppro_pinterest_board_name', sanitize_text_field($_POST['wpscppro-pinterest-board-name']));
        }
    }



    /**
     * aja request call back 
     * fetch selected profile
     */
    public function instant_share_fetch_profile()
    {
        $allProfile = array();
        // get data from db
        $facebook = \WPSP\Helper::get_social_profile(WPSCP_FACEBOOK_OPTION_NAME);
        $twitter = \WPSP\Helper::get_social_profile(WPSCP_TWITTER_OPTION_NAME);
        $linkedin = \WPSP\Helper::get_social_profile(WPSCP_LINKEDIN_OPTION_NAME);
        $pinterest = \WPSP\Helper::get_social_profile(WPSCP_PINTEREST_OPTION_NAME);
        // get data from ajax request
        $is_facebook_share = $_REQUEST['is_facebook_share'];
        $is_twitter_share = $_REQUEST['is_twitter_share'];
        $is_linkedin_share = $_REQUEST['is_linkedin_share'];
        $is_pinterest_share = $_REQUEST['is_pinterest_share'];

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

        $markup = '';
        if (is_array($allProfile) && count($allProfile) > 0) {
            foreach ($allProfile as $profileName => $profile) {
                $markup .= '<div class="entry-head ' . $profileName . '">
                        <img src="' . WPSP_ASSETS_URI . 'images/icon-' . $profileName . '-small-white.png' . '" alt="logo" />
                        <h2 class="entry-head-title">' . $profileName . '</h2>
                    </div>
                    <ul>';
                foreach ($profile as $key => $profileItem) {
                    $markup .= '<li id="' . $profileName . '_' . $key . '">
                            <div class="item-content">
                                ' . (isset($profileItem->thumbnail_url) ? '<div class="entry-thumbnail"><img src="' . $profileItem->thumbnail_url . '" alt="logo"></div>' : '') . '
                                <h4 class="entry-title">' . $profileItem->name . '</h4>
                                ' . (isset($profileItem->type) ? '<span class="type">' . $profileItem->type . '</span>' : '') . '
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
            $markup .= esc_html__('Failed!, Your are not selected any social media.', 'wp-scheduled-posts');
        }

        wp_send_json(array('markup' => $markup, 'profile' => $allProfile));
        wp_die();
    }


    public function instant_social_single_profile_share()
    {
        $postid = intval($_REQUEST['postid']);
        $platform = (isset($_POST['platform']) ? $_POST['platform'] : '');
        $platformKey = (isset($_POST['platformKey']) ? $_POST['platformKey'] : '');
        $pinterestBoardName = (isset($_POST['pinterest_custom_board_name']) ? $_POST['pinterest_custom_board_name'] : '');
        // all social platfrom
        if ($platform == 'facebook') {
            $facebook = \WPSP\Helper::get_social_profile(WPSCP_FACEBOOK_OPTION_NAME);
            // if disable account then it will be off
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
                $platformKey
            );
            wp_die();
        } else if ($platform == 'twitter') {
            $twitter = \WPSP\Helper::get_social_profile(WPSCP_TWITTER_OPTION_NAME);
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
                $platformKey
            );
            wp_die();
        } else if ($platform == 'linkedin') {
            $linkedin = \WPSP\Helper::get_social_profile(WPSCP_LINKEDIN_OPTION_NAME);
            // if disable account then it will be off
            if ($linkedin[$platformKey]->status == false) {
                wp_die();
            }
            // share
            $linkedinshare = new \WPSP\Social\Linkedin();
            $linkedinshare->socialMediaInstantShare(
                $linkedin[$platformKey]->app_id,
                $linkedin[$platformKey]->app_secret,
                $linkedin[$platformKey]->access_token,
                $postid,
                $platformKey
            );
            wp_die();
        } else if ($platform == 'pinterest') {
            $pinterest = \WPSP\Helper::get_social_profile(WPSCP_PINTEREST_OPTION_NAME);
            // if disable account then it will be off
            if ($pinterest[$platformKey]->status == false) {
                wp_die();
            }
            // share
            $pinterestshare = new \WPSP\Social\Pinterest();
            $pinterestshare->socialMediaInstantShare(
                $pinterest[$platformKey]->app_id,
                $pinterest[$platformKey]->app_secret,
                $pinterest[$platformKey]->access_token,
                $postid,
                ($pinterestBoardName != "" ? $pinterestBoardName : $pinterest[$platformKey]->default_board_name),
                $platformKey
            );
            wp_die();
        } else {
            wp_send_json_error(__('Sorry, your requested platform integation is not added.', 'wp-scheduled-posts'));
            wp_die();
        }
    }
}
