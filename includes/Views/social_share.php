<?php
    global $post;
    $facebookProfiles          = \WPSP\Helper::get_settings('facebook_profile_list');
    $linkedinProfiles          = \WPSP\Helper::get_settings('linkedin_profile_list');
    $instagramProfiles         = \WPSP\Helper::get_settings('instagram_profile_list');
    $selected_social_profiles  = get_post_meta( $post->ID, '_wpsp_social_profiles', true );
    $get_all_selected_profiles = is_array($selected_social_profiles) ? $selected_social_profiles : [];

    // Filter profiles by platform
    $facebook_profiles = array_filter($get_all_selected_profiles, function($profile) {
        return $profile['platform'] === 'facebook';
    });
    $linkedin_profiles = array_filter($get_all_selected_profiles, function($profile) {
        return $profile['platform'] === 'linkedin';
    });
    $instagram_profiles = array_filter($get_all_selected_profiles, function($profile) {
        return $profile['platform'] === 'instagram';
    });

    $selected_social_profiles = array_column($get_all_selected_profiles, 'id');

?>
<h2 class="title"><?php echo __('Social Share Settings','wp-scheduled-posts') ?></h2>
<div class="wpsp-post--card">
    <div class="select--wrapper">
        <label>
            <input type="checkbox" value="checkbox" name="_wpscppro_dont_share_socialmedia">
            <span><?php echo __('Disable Social Share','wp-scheduled-posts') ?></span>
        </label>
    </div>
    <div class="banner--wrapper">
        <input type='hidden' id='wpscppro_custom_social_share_image' class='regular-text text-upload'/>
        <div id="wpsp_social_share_image_preview"></div>
        <div class="upload-button--wrapper">
            <button class="btn upload--btn" id="wpsp_upload_banner"><?php echo __('Upload Banner','wp-scheduled-posts') ?></button>
            <button class="btn remove--btn" id="wpsp_remove_banner"><?php echo __('Remove Banner','wp-scheduled-posts') ?></button>
        </div>
    </div>
    <div class="social-platform--wrapper">
        <span class="title"><?php echo __('Choose Social Share Platform','wp-scheduled-posts') ?></span>
        <!-- Social item Facebook -->
        <div class="social--item">
            <div class="card--title">
                <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_3622_2332)">
                    <path d="M18.002 9.5C18.002 13.9923 14.7106 17.7157 10.4082 18.3907V12.1016H12.5053L12.9043 9.5H10.4082V7.8118C10.4082 7.09988 10.757 6.40625 11.8749 6.40625H13.0098V4.19141C13.0098 4.19141 11.9797 4.01562 10.995 4.01562C8.93937 4.01562 7.5957 5.26156 7.5957 7.51719V9.5H5.31055V12.1016H7.5957V18.3907C3.29328 17.7157 0.00195312 13.9923 0.00195312 9.5C0.00195312 4.52961 4.03156 0.5 9.00195 0.5C13.9723 0.5 18.002 4.52961 18.002 9.5Z" fill="#1877F2"/>
                    <path d="M12.5053 12.1016L12.9043 9.5H10.4082V7.81176C10.4082 7.10002 10.7569 6.40625 11.8749 6.40625H13.0098V4.19141C13.0098 4.19141 11.9798 4.01562 10.9951 4.01562C8.93934 4.01562 7.5957 5.26156 7.5957 7.51719V9.5H5.31055V12.1016H7.5957V18.3906C8.05393 18.4625 8.52355 18.5 9.00195 18.5C9.48036 18.5 9.94998 18.4625 10.4082 18.3906V12.1016H12.5053Z" fill="white"/>
                    </g>
                    <defs>
                    <clipPath id="clip0_3622_2332">
                    <rect width="18" height="18" fill="white" transform="translate(0 0.5)"/>
                    </clipPath>
                    </defs>
                </svg>
                <h5 class="title"><?php echo __('Facebook','wp-scheduled-posts') ?></h5>
            </div>
            <div class="wpsp-select--option" id="facebook-profiles">
                <div class="dropdown--selected selectedBox" id="selectedBox">
                    <?php if( count( $facebook_profiles ) > 0 ) : ?>
                        <?php foreach( $facebook_profiles as $profile ) : ?>
                            <div class="avatar-tag">
                                <img src="<?php echo esc_url( $profile['thumbnail_url'] ); ?>" onerror="this.src='<?php echo esc_url( $this->autho_logo ); ?>';" alt="">
                                <?php echo esc_html( $profile['name'] ); ?></div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <span><?php echo __('Select profile','wp-scheduled-posts') ?></span>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 7.5L10 12.5L15 7.5" stroke="#475467" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="dropdown--options dropdownOptions">
                    <div class="option">
                        <span><?php echo __('Select All', 'wp-scheduled-posts'); ?></span>
                        <input type="checkbox" id="selectAll">
                    </div>
                    <?php
                        if ( ! empty( $facebookProfiles ) && is_array( $facebookProfiles ) ) :
                            foreach ( $facebookProfiles as $profile ) :
                                if ( empty( $profile->name ) || empty( $profile->thumbnail_url ) ) {
                                    continue; // skip if essential data is missing
                                }
                                // Default to profile thumbnail
                                $thumbnail_url = ! empty( $profile->thumbnail_url ) ? $profile->thumbnail_url : $this->autho_logo;
                                if ( ! empty( $profile->thumbnail_url ) ) {
                                    $response = wp_remote_head( $profile->thumbnail_url );
                                
                                    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
                                        $thumbnail_url = $this->autho_logo;
                                    }
                                }
                                $checked = in_array( $profile->id, $selected_social_profiles ) ? 'checked' : ''; 
                        ?>
                            <div class="option">
                                <div class="author--details">
                                    <img src="<?php echo esc_url( $thumbnail_url ); ?>" onerror="this.src='<?php echo esc_url( $this->autho_logo ); ?>';" alt="<?php echo esc_attr( $profile->name ); ?>">
                                    <h5 class="title"><?php echo esc_html( $profile->name ); ?></h5>
                                    <button class="profile">PROFILE</button>
                                </div>
                                <input 
                                    type="checkbox" 
                                    class="profile" 
                                    name="facebook_profiles[]"
                                    data-name="<?php echo esc_attr( $profile->name ); ?>"
                                    value="<?php echo esc_attr( $profile->id ); ?>" 
                                    data-img="<?php echo esc_url( $thumbnail_url ); ?>"
                                    <?php echo $checked; ?>
                                >
                            </div>
                        <?php
                            endforeach;
                        endif;
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Social item LinkedIn -->
        <div class="social--item">
            <div class="card--title">
                <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16.6667 0.5H1.33333C0.596954 0.5 0 1.09695 0 1.83333V17.1667C0 17.903 0.596954 18.5 1.33333 18.5H16.6667C17.403 18.5 18 17.903 18 17.1667V1.83333C18 1.09695 17.403 0.5 16.6667 0.5ZM5.33333 15.8333H2.66667V7.16667H5.33333V15.8333ZM4 5.83333C3.26362 5.83333 2.66667 5.23638 2.66667 4.5C2.66667 3.76362 3.26362 3.16667 4 3.16667C4.73638 3.16667 5.33333 3.76362 5.33333 4.5C5.33333 5.23638 4.73638 5.83333 4 5.83333ZM15.3333 15.8333H12.6667V11.6667C12.6667 10.5621 12.6467 9.14638 11.1333 9.14638C9.59333 9.14638 9.36667 10.3464 9.36667 11.5864V15.8333H6.7V7.16667H9.24667V8.46667H9.28C9.64667 7.78 10.5533 7.05333 11.9133 7.05333C14.6267 7.05333 15.3333 8.89 15.3333 11.2667V15.8333Z" fill="#0077B5"/>
                </svg>
                <h5 class="title"><?php echo __('LinkedIn','wp-scheduled-posts') ?></h5>
            </div>
            <div class="wpsp-select--option" id="linkedin-profiles">
                <div class="dropdown--selected selectedBox" id="selectedBox">
                    <?php if( count( $linkedin_profiles ) > 0 ) : ?>
                        <?php foreach( $linkedin_profiles as $profile ) : ?>
                            <div class="avatar-tag">
                                <img src="<?php echo esc_url( $profile['thumbnail_url'] ); ?>" onerror="this.src='<?php echo esc_url( $this->autho_logo ); ?>';" alt="">
                                <?php echo esc_html( $profile['name'] ); ?></div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <span><?php echo __('Select profile','wp-scheduled-posts') ?></span>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 7.5L10 12.5L15 7.5" stroke="#475467" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="dropdown--options dropdownOptions">
                    <div class="option">
                        <span><?php echo __('Select All', 'wp-scheduled-posts'); ?></span>
                        <input type="checkbox" id="selectAllLinkedIn">
                    </div>
                    <?php
                        if ( ! empty( $linkedinProfiles ) && is_array( $linkedinProfiles ) ) :
                            foreach ( $linkedinProfiles as $profile ) :
                                if ( empty( $profile->name ) || empty( $profile->thumbnail_url ) ) {
                                    continue; // skip if essential data is missing
                                }
                                // Default to profile thumbnail
                                $thumbnail_url = ! empty( $profile->thumbnail_url ) ? $profile->thumbnail_url : $this->autho_logo;
                                if ( ! empty( $profile->thumbnail_url ) ) {
                                    $response = wp_remote_head( $profile->thumbnail_url );

                                    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
                                        $thumbnail_url = $this->autho_logo;
                                    }
                                }
                                $checked = in_array( $profile->id, $selected_social_profiles ) ? 'checked' : '';
                        ?>
                            <div class="option">
                                <div class="author--details">
                                    <img src="<?php echo esc_url( $thumbnail_url ); ?>" onerror="this.src='<?php echo esc_url( $this->autho_logo ); ?>';" alt="<?php echo esc_attr( $profile->name ); ?>">
                                    <h5 class="title"><?php echo esc_html( $profile->name ); ?></h5>
                                    <button class="profile">PROFILE</button>
                                </div>
                                <input
                                    type="checkbox"
                                    class="profile"
                                    name="linkedin_profiles[]"
                                    data-name="<?php echo esc_attr( $profile->name ); ?>"
                                    value="<?php echo esc_attr( $profile->id ); ?>"
                                    data-img="<?php echo esc_url( $thumbnail_url ); ?>"
                                    <?php echo $checked; ?>
                                >
                            </div>
                        <?php
                            endforeach;
                        endif;
                    ?>
                </div>
            </div>
        </div>

        <!-- Social item Instagram -->
        <div class="social--item">
            <div class="card--title">
                <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <radialGradient id="instagram-gradient" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" style="stop-color:#833AB4"/>
                            <stop offset="50%" style="stop-color:#FD1D1D"/>
                            <stop offset="100%" style="stop-color:#FCB045"/>
                        </radialGradient>
                    </defs>
                    <path d="M9 0.5C6.55556 0.5 6.24889 0.511111 5.28889 0.555556C4.33111 0.6 3.67778 0.744444 3.10556 0.961111C2.51111 1.18556 2.01111 1.48889 1.51333 1.98667C1.01556 2.48444 0.712222 2.98444 0.487778 3.57889C0.271111 4.15111 0.126667 4.80444 0.0822222 5.76222C0.0377778 6.72222 0.0266667 7.02889 0.0266667 9.47333C0.0266667 11.9178 0.0377778 12.2244 0.0822222 13.1844C0.126667 14.1422 0.271111 14.7956 0.487778 15.3678C0.712222 15.9622 1.01556 16.4622 1.51333 16.96C2.01111 17.4578 2.51111 17.7611 3.10556 17.9856C3.67778 18.2022 4.33111 18.3467 5.28889 18.3911C6.24889 18.4356 6.55556 18.4467 9 18.4467C11.4444 18.4467 11.7511 18.4356 12.7111 18.3911C13.6689 18.3467 14.3222 18.2022 14.8944 17.9856C15.4889 17.7611 15.9889 17.4578 16.4867 16.96C16.9844 16.4622 17.2878 15.9622 17.5122 15.3678C17.7289 14.7956 17.8733 14.1422 17.9178 13.1844C17.9622 12.2244 17.9733 11.9178 17.9733 9.47333C17.9733 7.02889 17.9622 6.72222 17.9178 5.76222C17.8733 4.80444 17.7289 4.15111 17.5122 3.57889C17.2878 2.98444 16.9844 2.48444 16.4867 1.98667C15.9889 1.48889 15.4889 1.18556 14.8944 0.961111C14.3222 0.744444 13.6689 0.6 12.7111 0.555556C11.7511 0.511111 11.4444 0.5 9 0.5ZM9 2.17333C11.4022 2.17333 11.6889 2.18222 12.6356 2.22667C13.5111 2.26667 13.9844 2.40444 14.3022 2.52C14.7356 2.68 15.0444 2.87333 15.3689 3.19778C15.6933 3.52222 15.8867 3.83111 16.0467 4.26444C16.1622 4.58222 16.3 5.05556 16.34 5.93111C16.3844 6.87778 16.3933 7.16444 16.3933 9.56667C16.3933 11.9689 16.3844 12.2556 16.34 13.2022C16.3 14.0778 16.1622 14.5511 16.0467 14.8689C15.8867 15.3022 15.6933 15.6111 15.3689 15.9356C15.0444 16.26 14.7356 16.4533 14.3022 16.6133C13.9844 16.7289 13.5111 16.8667 12.6356 16.9067C11.6889 16.9511 11.4022 16.96 9 16.96C6.59778 16.96 6.31111 16.9511 5.36444 16.9067C4.48889 16.8667 4.01556 16.7289 3.69778 16.6133C3.26444 16.4533 2.95556 16.26 2.63111 15.9356C2.30667 15.6111 2.11333 15.3022 1.95333 14.8689C1.83778 14.5511 1.7 14.0778 1.66 13.2022C1.61556 12.2556 1.60667 11.9689 1.60667 9.56667C1.60667 7.16444 1.61556 6.87778 1.66 5.93111C1.7 5.05556 1.83778 4.58222 1.95333 4.26444C2.11333 3.83111 2.30667 3.52222 2.63111 3.19778C2.95556 2.87333 3.26444 2.68 3.69778 2.52C4.01556 2.40444 4.48889 2.26667 5.36444 2.22667C6.31111 2.18222 6.59778 2.17333 9 2.17333ZM9 4.58C6.47556 4.58 4.42667 6.62889 4.42667 9.15333C4.42667 11.6778 6.47556 13.7267 9 13.7267C11.5244 13.7267 13.5733 11.6778 13.5733 9.15333C13.5733 6.62889 11.5244 4.58 9 4.58ZM9 12.0533C7.39778 12.0533 6.1 10.7556 6.1 9.15333C6.1 7.55111 7.39778 6.25333 9 6.25333C10.6022 6.25333 11.9 7.55111 11.9 9.15333C11.9 10.7556 10.6022 12.0533 9 12.0533ZM14.8844 4.40222C14.8844 5.00667 14.3956 5.49556 13.7911 5.49556C13.1867 5.49556 12.6978 5.00667 12.6978 4.40222C12.6978 3.79778 13.1867 3.30889 13.7911 3.30889C14.3956 3.30889 14.8844 3.79778 14.8844 4.40222Z" fill="url(#instagram-gradient)"/>
                </svg>
                <h5 class="title"><?php echo __('Instagram','wp-scheduled-posts') ?></h5>
            </div>
            <div class="wpsp-select--option" id="instagram-profiles">
                <div class="dropdown--selected selectedBox" id="selectedBox">
                    <?php if( count( $instagram_profiles ) > 0 ) : ?>
                        <?php foreach( $instagram_profiles as $profile ) : ?>
                            <div class="avatar-tag">
                                <img src="<?php echo esc_url( $profile['thumbnail_url'] ); ?>" onerror="this.src='<?php echo esc_url( $this->autho_logo ); ?>';" alt="">
                                <?php echo esc_html( $profile['name'] ); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <span><?php echo __('Select profile','wp-scheduled-posts') ?></span>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 7.5L10 12.5L15 7.5" stroke="#475467" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="dropdown--options dropdownOptions">
                    <div class="option">
                        <span><?php echo __('Select All', 'wp-scheduled-posts'); ?></span>
                        <input type="checkbox" id="selectAllInstagram">
                    </div>
                    <?php
                        if ( ! empty( $instagramProfiles ) && is_array( $instagramProfiles ) ) :
                            foreach ( $instagramProfiles as $profile ) :
                                if ( empty( $profile->name ) ) {
                                    continue; // skip if essential data is missing
                                }
                                // Default to profile thumbnail
                                $thumbnail_url = ! empty( $profile->thumbnail_url ) ? $profile->thumbnail_url : $this->autho_logo;
                                if ( ! empty( $profile->thumbnail_url ) ) {
                                    $response = wp_remote_head( $profile->thumbnail_url );

                                    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
                                        $thumbnail_url = $this->autho_logo;
                                    }
                                }
                                $checked = in_array( $profile->id, $selected_social_profiles ) ? 'checked' : '';
                        ?>
                            <div class="option">
                                <div class="author--details">
                                    <img src="<?php echo esc_url( $thumbnail_url ); ?>" onerror="this.src='<?php echo esc_url( $this->autho_logo ); ?>';" alt="<?php echo esc_attr( $profile->name ); ?>">
                                    <h5 class="title"><?php echo esc_html( $profile->name ); ?></h5>
                                    <button class="profile">PROFILE</button>
                                </div>
                                <input
                                    type="checkbox"
                                    class="profile"
                                    name="instagram_profiles[]"
                                    data-name="<?php echo esc_attr( $profile->name ); ?>"
                                    value="<?php echo esc_attr( $profile->id ); ?>"
                                    data-img="<?php echo esc_url( $thumbnail_url ); ?>"
                                    <?php echo $checked; ?>
                                >
                            </div>
                        <?php
                            endforeach;
                        endif;
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>