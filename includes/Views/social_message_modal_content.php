<?php
    global $post;

    // Helper function to get safe thumbnail URL
    function get_safe_thumbnail_url($profile, $autho_logo) {
        $thumbnail_url = ! empty( $profile->thumbnail_url ) ? $profile->thumbnail_url : $autho_logo;
        if ( ! empty( $profile->thumbnail_url ) ) {
            $response = wp_remote_head( $profile->thumbnail_url );
            if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
                $thumbnail_url = $autho_logo;
            }
        }
        return $thumbnail_url;
    }

    // Helper function to render profile selection area
    function render_profile_selection_area($platform, $profiles, $selected_profiles, $autho_logo) {
        if ( empty( $profiles ) || ! is_array( $profiles ) ) {
            return '';
        }

        $output = '';
        foreach ( $profiles as $profile ) :
            if ( empty( $profile->name ) || empty( $profile->thumbnail_url ) ) {
                continue;
            }
            $thumbnail_url = get_safe_thumbnail_url($profile, $autho_logo);
            $checked = in_array( $profile->id, $selected_profiles ) ? 'checked' : '';

            $output .= '<li class="selected-profile" title="' . esc_attr( $profile->name ) . '">';
            $output .= '<img src="' . esc_url( $thumbnail_url ) . '" alt="' . esc_attr( $profile->name ) . '" class="wpsp-profile-image">';
            if( $checked ) {
                $output .= '<div class="wpsp-selected-profile-action">';
                $output .= '<span class="wpsp-remove-profile-btn">×</span>';
                $output .= '<span class="wpsp-selected-profile-btn">';
                $output .= '<svg width="10" height="11" viewBox="0 0 10 11" fill="none" xmlns="http://www.w3.org/2000/svg">';
                $output .= '<rect x="0.6" y="0.900781" width="8.8" height="8.8" rx="4.4" fill="#6C62FF"></rect>';
                $output .= '<rect x="0.6" y="0.900781" width="8.8" height="8.8" rx="4.4" stroke="white" stroke-width="0.8"></rect>';
                $output .= '<g clip-path="url(#clip0_4477_4922)">';
                $output .= '<path d="M3.58398 5.30078L4.58398 6.30078L6.58398 4.30078" stroke="white" stroke-width="0.64" stroke-linecap="round" stroke-linejoin="round"></path>';
                $output .= '</g>';
                $output .= '<defs><clipPath id="clip0_4477_4922"><rect width="4" height="4" fill="white" transform="translate(3 3.30078)"></rect></clipPath></defs>';
                $output .= '</svg>';
                $output .= '</span>';
                $output .= '</div>';
            }
            $output .= '</li>';
        endforeach;

        return $output;
    }

    // Helper function to render profile dropdown
    function render_profile_dropdown($platform, $profiles, $selected_profiles, $autho_logo) {
        if ( empty( $profiles ) || ! is_array( $profiles ) ) {
            return '';
        }

        $output = '';
        foreach ( $profiles as $profile ) :
            $thumbnail_url = get_safe_thumbnail_url($profile, $autho_logo);

            $output .= '<div class="wpsp-profile-card" data-profile-id="' . esc_attr( $profile->id ) . '" data-profile-name="' . esc_attr( $profile->name ) . '" data-profile-img="' . esc_url( $thumbnail_url ) . '">';
            $output .= '<div class="wpsp-profile-avatar">';
            $output .= '<img src="' . esc_url( $thumbnail_url ) . '" alt="' . esc_attr( $profile->name ) . '" class="wpsp-profile-image">';
            $output .= '</div>';
            $output .= '<div class="wpsp-profile-info">';
            $output .= '<div class="wpsp-profile-name">' . esc_html( $profile->name ) . '</div>';
            $output .= '</div>';
            $output .= '<div class="wpsp-profile-checkbox">';
            $output .= '<input type="checkbox" class="wpsp-modal-profile-checkbox wpsp-' . $platform . '-checkbox" name="' . $platform . '_profiles[]" value="' . esc_attr( $profile->id ) . '" data-name="' . esc_attr( $profile->name ) . '" data-img="' . esc_url( $thumbnail_url ) . '" data-platform="' . $platform . '" ' . (in_array( $profile->id, $selected_profiles ) ? 'checked' : '') . '>';
            $output .= '</div>';
            $output .= '</div>';
        endforeach;

        return $output;
    }
    $facebookProfiles          = \WPSP\Helper::get_settings('facebook_profile_list');
    $linkedinProfiles          = \WPSP\Helper::get_settings('linkedin_profile_list');
    $instagramProfiles         = \WPSP\Helper::get_settings('instagram_profile_list');
    $googleBusinessProfiles    = \WPSP\Helper::get_settings('google_business_profile_list');
    $twitterProfiles           = \WPSP\Helper::get_settings('twitter_profile_list');
    $pinterestProfiles         = \WPSP\Helper::get_settings('pinterest_profile_list');
    $mediumProfiles            = \WPSP\Helper::get_settings('medium_profile_list');
    $threadsProfiles           = \WPSP\Helper::get_settings('threads_profile_list');
    // Load data from _wpsp_custom_templates meta field
    $custom_templates = get_post_meta($post->ID, '_wpsp_custom_templates', true);
    if (!is_array($custom_templates)) {
        $custom_templates = array();
    }

    // Extract selected profile IDs for each platform
    $selected_facebook_profiles = array();
    $selected_instagram_profiles = array();
    $selected_google_business_profiles = array();
    $selected_twitter_profiles = array();
    $selected_linkedin_profiles = array();
    $selected_pinterest_profiles = array();
    $selected_medium_profiles = array();
    $selected_threads_profiles = array();

    if (isset($custom_templates['facebook']) && isset($custom_templates['facebook']['profiles'])) {
        $selected_facebook_profiles = $custom_templates['facebook']['profiles'];
    }
    if (isset($custom_templates['instagram']) && isset($custom_templates['instagram']['profiles'])) {
        $selected_instagram_profiles = $custom_templates['instagram']['profiles'];
    }
    if (isset($custom_templates['google_business']) && isset($custom_templates['google_business']['profiles'])) {
        $selected_google_business_profiles = $custom_templates['google_business']['profiles'];
    }
    if (isset($custom_templates['twitter']) && isset($custom_templates['twitter']['profiles'])) {
        $selected_twitter_profiles = $custom_templates['twitter']['profiles'];
    }
    if (isset($custom_templates['linkedin']) && isset($custom_templates['linkedin']['profiles'])) {
        $selected_linkedin_profiles = $custom_templates['linkedin']['profiles'];
    }
    if (isset($custom_templates['pinterest']) && isset($custom_templates['pinterest']['profiles'])) {
        $selected_pinterest_profiles = $custom_templates['pinterest']['profiles'];
    }
    if (isset($custom_templates['medium']) && isset($custom_templates['medium']['profiles'])) {
        $selected_medium_profiles = $custom_templates['medium']['profiles'];
    }
    if (isset($custom_templates['threads']) && isset($custom_templates['threads']['profiles'])) {
        $selected_threads_profiles = $custom_templates['threads']['profiles'];
    }

    // Get template content for each platform
    $facebook_template = '';
    $instagram_template = '';
    $google_business_template = '';
    $twitter_template = '';
    $linkedin_template = '';
    $pinterest_template = '';
    $medium_template = '';
    $threads_template = '';

    if (isset($custom_templates['facebook']) && isset($custom_templates['facebook']['template'])) {
        $facebook_template = $custom_templates['facebook']['template'];
    }
    if (isset($custom_templates['instagram']) && isset($custom_templates['instagram']['template'])) {
        $instagram_template = $custom_templates['instagram']['template'];
    }
    if (isset($custom_templates['google_business']) && isset($custom_templates['google_business']['template'])) {
        $google_business_template = $custom_templates['google_business']['template'];
    }
    if (isset($custom_templates['twitter']) && isset($custom_templates['twitter']['template'])) {
        $twitter_template = $custom_templates['twitter']['template'];
    }
    if (isset($custom_templates['linkedin']) && isset($custom_templates['linkedin']['template'])) {
        $linkedin_template = $custom_templates['linkedin']['template'];
    }
    if (isset($custom_templates['pinterest']) && isset($custom_templates['pinterest']['template'])) {
        $pinterest_template = $custom_templates['pinterest']['template'];
    }
    if (isset($custom_templates['medium']) && isset($custom_templates['medium']['template'])) {
        $medium_template = $custom_templates['medium']['template'];
    }
    if (isset($custom_templates['threads']) && isset($custom_templates['threads']['template'])) {
        $threads_template = $custom_templates['threads']['template'];
    }

    // Get global template settings
    $facebook_is_global = '';
    $instagram_is_global = '';
    $google_business_is_global = '';
    $twitter_is_global = '';
    $linkedin_is_global = '';
    $pinterest_is_global = '';
    $medium_is_global = '';
    $threads_is_global = '';

    if (isset($custom_templates['facebook']) && isset($custom_templates['facebook']['is_global'])) {
        $facebook_is_global = $custom_templates['facebook']['is_global'];
    }
    if (isset($custom_templates['instagram']) && isset($custom_templates['instagram']['is_global'])) {
        $instagram_is_global = $custom_templates['instagram']['is_global'];
    }
    if (isset($custom_templates['google_business']) && isset($custom_templates['google_business']['is_global'])) {
        $google_business_is_global = $custom_templates['google_business']['is_global'];
    }
    if (isset($custom_templates['twitter']) && isset($custom_templates['twitter']['is_global'])) {
        $twitter_is_global = $custom_templates['twitter']['is_global'];
    }
    if (isset($custom_templates['linkedin']) && isset($custom_templates['linkedin']['is_global'])) {
        $linkedin_is_global = $custom_templates['linkedin']['is_global'];
    }
    if (isset($custom_templates['pinterest']) && isset($custom_templates['pinterest']['is_global'])) {
        $pinterest_is_global = $custom_templates['pinterest']['is_global'];
    }
    if (isset($custom_templates['medium']) && isset($custom_templates['medium']['is_global'])) {
        $medium_is_global = $custom_templates['medium']['is_global'];
    }
    if (isset($custom_templates['threads']) && isset($custom_templates['threads']['is_global'])) {
        $threads_is_global = $custom_templates['threads']['is_global'];
    }

    // Combine all selected profiles for backward compatibility
    $all_selected_profile_ids = array_merge(
        $selected_facebook_profiles,
        $selected_instagram_profiles,
        $selected_google_business_profiles,
        $selected_twitter_profiles,
        $selected_linkedin_profiles,
        $selected_pinterest_profiles,
        $selected_medium_profiles,
        $selected_threads_profiles
    );

?>

<div id="wpsp-social-message-modal">
    <div class="wpsp-modal-header">
        <div class="platform-info">
            <h4><?php echo __('Add Social Message','wp-scheduled-posts') ?></h4>
            <div class="wpsp-modal-close-btn">
                <button class="close-button" id="wpsp-close-social-message-modal"><i class="wpsp-icon wpsp-close"></i></button>
            </div>
        </div>
    </div>
    <div class="wpsp-modal-content">
        <form id="wpsp-social-message-form" method="post">
            <?php wp_nonce_field('wpsp_social_message_nonce', 'wpsp_social_message_nonce'); ?>
            <input type="hidden" name="post_id" value="<?php echo esc_attr($post->ID); ?>">
            <div class="wpsp-modal-layout">
                <!-- Left Panel -->
                <div class="wpsp-modal-left">
                    <div class="wpsp-platform-icons">
                        <button class="wpsp-platform-icon facebook active" title="Facebook">
                            <svg width="10" height="18" viewBox="0 0 10 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M7.97914 0.00359757C8.16286 0.00434154 8.34659 0.00329123 8.53032 0.00175951C8.67308 0.000796713 8.8158 0.000884195 8.95856 0.0012343C9.0264 0.0012343 9.09419 0.000971701 9.16203 0.000359013C9.25625 -0.000428729 9.35038 0.000184004 9.4446 0.00119056L9.60639 0.00132195C9.7946 0.0377769 9.85988 0.0975139 9.97258 0.247623C10.0002 0.387403 10.0002 0.387403 9.9995 0.546877L10 0.635367C10.0001 0.731374 9.99927 0.82738 9.99766 0.923374C9.99744 0.990332 9.99735 1.05729 9.99735 1.12429C9.99704 1.26442 9.99601 1.40455 9.99443 1.54464C9.99242 1.72416 9.99174 1.90363 9.99156 2.0831C9.99138 2.22131 9.99067 2.35951 9.98981 2.49772C9.98945 2.56389 9.98914 2.63006 9.98892 2.69623C9.98844 2.78872 9.98757 2.88121 9.98631 2.97369C9.98587 3.02629 9.98542 3.0789 9.98488 3.13308C9.9729 3.26393 9.9611 3.33658 9.88285 3.44235C9.75826 3.52301 9.65821 3.541 9.51033 3.54244L9.39592 3.54419L9.27299 3.54493L9.1448 3.54686C9.00944 3.54892 8.87413 3.55036 8.73877 3.55176C7.77114 3.49968 7.77114 3.49968 6.92169 3.83622C6.7782 4.06305 6.73787 4.27627 6.73302 4.53933L6.73033 4.66935L6.72804 4.80808L6.72522 4.95136C6.72232 5.10208 6.71957 5.2528 6.71696 5.40352L6.71113 5.71044C6.70633 5.96098 6.70171 6.21153 6.69736 6.46203L6.80656 6.46085C7.14651 6.45735 7.48646 6.45468 7.82646 6.45297C8.00121 6.45205 8.17596 6.45078 8.35076 6.44881C8.51946 6.44693 8.68816 6.44588 8.85694 6.4454C8.92124 6.44509 8.98553 6.44448 9.04987 6.44352C9.14005 6.44225 9.23023 6.44221 9.3205 6.44216L9.47596 6.44098C9.64573 6.46693 9.71914 6.51757 9.83799 6.63708C9.90421 6.7843 9.88886 6.94693 9.88936 7.10575L9.88981 7.19402C9.89026 7.29004 9.89035 7.38605 9.89039 7.48211L9.89084 7.68286C9.89111 7.82307 9.8912 7.96329 9.89115 8.10347C9.89115 8.2829 9.89178 8.46233 9.89254 8.64176C9.89304 8.77996 9.89313 8.91817 9.89313 9.05637C9.89313 9.1225 9.89335 9.18867 9.89371 9.25479C9.89416 9.3474 9.89402 9.43996 9.89371 9.53256L9.89393 9.69204C9.88263 9.83488 9.86612 9.92652 9.79312 10.0506C9.61913 10.1768 9.49436 10.1981 9.27972 10.1968H9.10559C9.04332 10.1961 8.98104 10.1954 8.91877 10.1947C8.68661 10.1944 8.45446 10.1931 8.22231 10.1909C8.05056 10.1896 7.87886 10.189 7.70711 10.1883C7.37053 10.1869 7.03394 10.1847 6.69736 10.1819L6.69763 10.2982C6.6996 11.2314 6.70108 12.1646 6.70202 13.0978C6.70247 13.5491 6.7031 14.0004 6.70413 14.4517C6.70503 14.845 6.70561 15.2383 6.70579 15.6315C6.70592 15.8398 6.70619 16.0482 6.70682 16.2564C6.70745 16.4524 6.70763 16.6485 6.7075 16.8445C6.7075 16.9164 6.70772 16.9884 6.70803 17.0604C6.70848 17.1586 6.70839 17.2569 6.70817 17.3551L6.70839 17.5217C6.69758 17.6627 6.67313 17.7589 6.60762 17.8843C6.45678 17.9985 6.28145 17.985 6.09835 17.9852L5.99856 17.9863C5.8899 17.9874 5.78128 17.9877 5.67261 17.9881L5.44563 17.9892C5.28712 17.99 5.12861 17.9903 4.9701 17.9905C4.76713 17.9909 4.56417 17.9921 4.36122 17.9943C4.205 17.9956 4.04877 17.9959 3.8925 17.996C3.81771 17.9961 3.74287 17.9967 3.66804 17.9976C3.56332 17.9988 3.45869 17.9986 3.35397 17.9981L3.26065 18C3.10088 17.9976 3.0251 17.977 2.89813 17.8804C2.76789 17.7211 2.76627 17.6412 2.76703 17.4393L2.76645 17.345C2.76609 17.241 2.7673 17.1371 2.76852 17.0332C2.76861 16.9585 2.76856 16.8839 2.76843 16.8093C2.76838 16.6068 2.76968 16.4044 2.77125 16.202C2.77264 15.9904 2.77278 15.7788 2.77305 15.5671C2.77376 15.1665 2.7756 14.7658 2.77785 14.3652C2.78036 13.9091 2.78157 13.4529 2.78269 12.9967C2.78507 12.0585 2.78897 11.1202 2.794 10.1819L2.70184 10.1825C2.38522 10.1845 2.06865 10.1858 1.75203 10.1867C1.6339 10.1872 1.51572 10.1878 1.39759 10.1885C1.22772 10.1896 1.0579 10.1901 0.888085 10.1905L0.728002 10.1919H0.578329L0.447005 10.1925C0.315817 10.181 0.220028 10.1507 0.10203 10.0944C-0.0143531 9.94713 0.000901277 9.78919 0.000676946 9.60897L0.000228531 9.51532C-0.00022013 9.41339 9.35166e-05 9.31147 0.000362714 9.20954L0.000138848 8.99668C4.24984e-06 8.84806 0.000138602 8.69944 0.000587263 8.55082C0.00112566 8.3604 0.000811424 8.16999 0.000273031 7.97953C-4.10326e-05 7.83305 4.86998e-05 7.68653 0.000273031 7.54006C0.000362763 7.46986 0.000273496 7.39962 4.91653e-05 7.32942C-0.000105186 7.23128 0.000103846 7.13313 0.000676946 7.03498L0.000901496 6.86575C0.0117591 6.73105 0.0207771 6.65761 0.10203 6.54956C0.24264 6.45407 0.364093 6.45135 0.532521 6.4521H0.684886L0.848648 6.4535L1.0169 6.45385C1.16406 6.45428 1.31131 6.45507 1.45851 6.45604C1.6089 6.45691 1.75925 6.45735 1.90964 6.45779C2.20441 6.4587 2.49918 6.46019 2.794 6.46203L2.79279 6.36488C2.78915 6.05862 2.78646 5.75236 2.78471 5.44606C2.78381 5.28869 2.78251 5.13136 2.78045 4.97399C2.7625 3.56988 2.93344 2.35488 3.91565 1.25418L3.98452 1.17436C5.01402 0.0725688 6.56204 -0.00252932 7.97918 0.00359757H7.97914Z" fill="#3C69D6"></path>
                            </svg>
                        </button>
                        <button class="wpsp-platform-icon instagram" title="Instagram">
                            <svg width="15" height="15" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6.38906 1.5625H3.61063C3.06756 1.56291 2.54685 1.77883 2.16284 2.16284C1.77883 2.54685 1.56291 3.06756 1.5625 3.61062V6.38906C1.56283 6.93219 1.77871 7.45297 2.16273 7.83705C2.54675 8.22113 3.0675 8.43709 3.61063 8.4375H6.38906C6.93216 8.437 7.45287 8.22102 7.83687 7.83696C8.22087 7.4529 8.43677 6.93216 8.43719 6.38906V3.61062C8.43694 3.0675 8.22108 2.5467 7.83703 2.16266C7.45299 1.77861 6.93218 1.56275 6.38906 1.5625ZM7.74594 6.38906C7.74586 6.7489 7.60287 7.09398 7.34843 7.34843C7.09398 7.60287 6.7489 7.74585 6.38906 7.74594H3.61063C3.43245 7.74594 3.25603 7.71084 3.09142 7.64265C2.92681 7.57445 2.77725 7.4745 2.65128 7.3485C2.52531 7.2225 2.42539 7.07291 2.35724 6.90829C2.28908 6.74367 2.25402 6.56724 2.25406 6.38906V3.61062C2.25402 3.43247 2.28908 3.25605 2.35724 3.09144C2.4254 2.92684 2.52532 2.77728 2.6513 2.6513C2.77728 2.52532 2.92684 2.4254 3.09144 2.35724C3.25605 2.28908 3.43247 2.25402 3.61063 2.25406H6.38906C6.74882 2.25415 7.09382 2.3971 7.34821 2.65148C7.60259 2.90587 7.74554 3.25087 7.74563 3.61062L7.74594 6.38906Z" fill="black"></path>
                                <path d="M5.00003 3.2225C4.01941 3.2225 3.22253 4.01969 3.22253 5C3.22253 5.98032 4.01972 6.7775 5.00003 6.7775C5.98035 6.7775 6.77753 5.98032 6.77753 5C6.77753 4.01969 5.98066 3.2225 5.00003 3.2225ZM5.00003 6.08594C4.71203 6.08598 4.4358 5.97161 4.23211 5.76799C4.02843 5.56437 3.91398 5.28817 3.91394 5.00016C3.9139 4.71215 4.02827 4.43592 4.23189 4.23224C4.43552 4.02856 4.71171 3.91411 4.99972 3.91407C5.28773 3.91403 5.56396 4.0284 5.76764 4.23202C5.97132 4.43564 6.08577 4.71184 6.08582 4.99985C6.08586 5.28786 5.97149 5.56409 5.76786 5.76777C5.56424 5.97145 5.28804 6.0859 5.00003 6.08594ZM6.78128 2.80969C6.8655 2.80975 6.94781 2.83478 7.0178 2.88161C7.0878 2.92843 7.14235 2.99496 7.17455 3.07277C7.20675 3.15059 7.21515 3.23621 7.19871 3.3188C7.18226 3.4014 7.1417 3.47726 7.08215 3.53681C7.02261 3.59636 6.94674 3.63692 6.86414 3.65337C6.78155 3.66981 6.69593 3.6614 6.61812 3.6292C6.5403 3.597 6.47377 3.54246 6.42695 3.47246C6.38012 3.40247 6.3551 3.32016 6.35503 3.23594C6.35503 3.00094 6.51441 2.80969 6.78128 2.80969Z" fill="black"></path>
                            </svg>
                        </button>
                        <button class="wpsp-platform-icon twitter" title="Twitter">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" fill="currentColor"/>
                            </svg>
                        </button>
                        <button class="wpsp-platform-icon linkedin" title="LinkedIn">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" fill="currentColor"/>
                            </svg>
                        </button>
                        <button class="wpsp-platform-icon pinterest" title="Pinterest">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.746-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24c6.624 0 11.99-5.367 11.99-11.987C24.007 5.367 18.641.001 12.017.001z" fill="currentColor"/>
                            </svg>
                        </button>
                        <button class="wpsp-platform-icon medium" title="Medium">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M13.54 12a6.8 6.8 0 01-6.77 6.82A6.8 6.8 0 010 12a6.8 6.8 0 016.77-6.82A6.8 6.8 0 0113.54 12zM20.96 12c0 3.54-1.51 6.42-3.38 6.42-1.87 0-3.39-2.88-3.39-6.42s1.52-6.42 3.39-6.42 3.38 2.88 3.38 6.42M24 12c0 3.17-.53 5.75-1.19 5.75-.66 0-1.19-2.58-1.19-5.75s.53-5.75 1.19-5.75C23.47 6.25 24 8.83 24 12z" fill="currentColor"/>
                            </svg>
                        </button>
                        <button class="wpsp-platform-icon threads" title="Threads">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12.186 24h-.007c-3.581-.024-6.334-1.205-8.184-3.509C2.35 18.44 1.5 15.586 1.5 12.068v-.036c0-3.518.85-6.373 2.495-8.424C5.845 1.205 8.598.024 12.179 0h.014c2.746.02 5.043.725 6.826 2.098 1.677 1.29 2.858 3.13 3.509 5.467l-2.04.569c-1.104-3.96-3.898-5.984-8.304-6.015-2.91.022-5.11.936-6.54 2.717C4.307 6.504 3.616 8.914 3.616 12.05v.036c0 3.137.691 5.547 2.028 7.215 1.43 1.781 3.63 2.695 6.54 2.717 4.405-.031 7.199-2.055 8.303-6.015l2.04.569c-.651 2.337-1.832 4.177-3.509 5.467C17.236 23.275 14.939 23.98 12.193 24h-.007z" fill="currentColor"/>
                                <path d="M17.165 12.906c.176-.464.276-.96.3-1.482.023-.522-.047-1.077-.21-1.665-.326-1.176-.915-2.180-1.75-2.984-.835-.805-1.207-1.215-1.207-1.215s.372.41 1.207 1.215c.835.804 1.424 1.808 1.75 2.984.163.588.233 1.143.21 1.665-.024.522-.124 1.018-.3 1.482z" fill="currentColor"/>
                            </svg>
                        </button>
                        <button class="wpsp-platform-icon google_business" title="Google_business">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24" width="24" viewBox="0 0.43 1064 928.69">
                                <linearGradient id="a" x1="0%" x2="99.999%" y1="49.999%" y2="49.999%">
                                <stop offset=".03" stop-color="#4079d8"></stop>
                                <stop offset="1" stop-color="#4989f5"></stop>
                                </linearGradient>
                                <g fill="none" fill-rule="evenodd">
                                <g fill-rule="nonzero">
                                    <rect fill="#4989f5" height="696.14" rx="36.88" width="931" x="53.45" y="232.98"></rect>
                                    <path d="M936.81 227.75H100.06c-25.92 0-46.09 200.6-46.09 226.52L512.2 929.12h424.61c26-.071 47.059-21.13 47.13-47.13V274.87c-.077-25.996-21.134-47.049-47.13-47.12z" fill="url(#a)"></path>
                                    <path d="M266.03 349.56h266V.44H305.86z" fill="#3c4ba6"></path>
                                    <path d="M798.03 349.56h-266V.44H758.2zM984.45 66.62l.33 1.19c-.08-.42-.24-.81-.33-1.19z" fill="#7babf7"></path>
                                    <path d="M984.78 67.8l-.33-1.19C976.017 27.993 941.837.455 902.31.43H758.2L798 349.56h266z" fill="#3f51b5"></path>
                                    <path d="M79.61 66.62l-.33 1.19c.08-.42.24-.81.33-1.19z" fill="#7babf7"></path>
                                    <path d="M79.27 67.8l.33-1.19C88.033 27.993 122.213.455 161.74.43h144.12L266 349.56H0z" fill="#7babf7"></path>
                                </g>
                                <path d="M266.48 349.47c0 73.412-59.513 132.925-132.925 132.925S.63 422.882.63 349.47z" fill="#709be0"></path>
                                <path d="M532.33 349.47c0 73.412-59.513 132.925-132.925 132.925S266.48 422.882 266.48 349.47z" fill="#3c4ba6"></path>
                                <path d="M798.18 349.47c0 73.412-59.513 132.925-132.925 132.925S532.33 422.882 532.33 349.47z" fill="#709be0"></path>
                                <path d="M1064 349.47c0 73.412-59.513 132.925-132.925 132.925S798.15 422.882 798.15 349.47z" fill="#3c4ba6"></path>
                                <path d="M931.08 709.6c-.47-6.33-1.25-12.11-2.36-19.49h-145c0 20.28 0 42.41-.08 62.7h84a73.05 73.05 0 0 1-30.75 46.89s0-.35-.06-.36a88 88 0 0 1-34 13.27 99.85 99.85 0 0 1-36.79-.16 91.9 91.9 0 0 1-34.31-14.87 95.72 95.72 0 0 1-33.73-43.1c-.52-1.35-1-2.71-1.49-4.09v-.15l.13-.1a93 93 0 0 1-.05-59.84A96.27 96.27 0 0 1 718.9 654c23.587-24.399 58.829-33.576 91.32-23.78a83 83 0 0 1 33.23 19.56l28.34-28.34c5-5.05 10.19-9.94 15-15.16a149.78 149.78 0 0 0-49.64-30.74 156.08 156.08 0 0 0-103.83-.91c-1.173.4-2.34.817-3.5 1.25A155.18 155.18 0 0 0 646 651a152.61 152.61 0 0 0-13.42 38.78c-16.052 79.772 32.623 158.294 111.21 179.4 25.69 6.88 53 6.71 78.89.83a139.88 139.88 0 0 0 63.14-32.81c18.64-17.15 32-40 39-64.27a179 179 0 0 0 6.26-63.33z" fill="#fff" fill-rule="nonzero"></path>
                                </g>
                            </svg>
                        </button>
                    </div>
                    <div class="wpsp-custom-template-content-wrapper">
                        <!-- Include Platform Tab Files -->
                        <?php  include __DIR__ . '/Tabs/facebook-tab.php'; ?>
                        <?php  include __DIR__ . '/Tabs/instagram-tab.php'; ?>
                        <?php  include __DIR__ . '/Tabs/google-business-tab.php'; ?>
                        <?php  include __DIR__ . '/Tabs/twitter-tab.php'; ?>
                        <?php  include __DIR__ . '/Tabs/linkedin-tab.php'; ?>
                        <?php  include __DIR__ . '/Tabs/pinterest-tab.php'; ?>
                        <?php  include __DIR__ . '/Tabs/medium-tab.php'; ?>
                        <?php  include __DIR__ . '/Tabs/threads-tab.php'; ?>
                    </div>
                    <div class="wpsp-date-time-section" style="margin-bottom: 1.5em;">
                        <div style="display: flex; gap: 1.5em; align-items: flex-end;">
                            <div>
                                <label style="font-weight: 600; display: block; margin-bottom: 4px;">Date</label>
                                <select class="wpsp-date-select">
                                    <option value="tomorrow">Tomorrow</option>
                                    <option value="next_week">Next week</option>
                                    <option value="next_month">Next month</option>
                                    <option value="in_days">In __ days</option>
                                    <option value="custom_date">Choose a custom date...</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-weight: 600; display: block; margin-bottom: 4px;">Time</label>
                                <select class="wpsp-time-select">
                                    <option value="in_1h">In one hour</option>
                                    <option value="in_3h">In three hours</option>
                                    <option value="in_5h">In five hours</option>
                                    <option value="in_hours">In __ hours</option>
                                    <option value="custom_time">Choose a custom time...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                  <!-- /Left Panel -->
                <div class="wpsp-modal-right facebook">
                    <div class="wpsp-preview-card">
                        <div class="wpsp-preview-header">
                            <div class="wpsp-preview-avatar">
                                <div class="wpsp-avatar-circle"><img src="data:image/svg+xml;utf8,%0A%20%20%3Csvg%20width%3D%2248%22%20height%3D%2248%22%20viewBox%3D%220%200%2048%2048%22%20fill%3D%22none%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%0A%20%20%20%20%3Cpath%20d%3D%22M24%200.000976562C10.7466%200.000976562%200%2010.7454%200%2023.9999C0%2037.2544%2010.7455%2047.9989%2024%2047.9989C37.2556%2047.9989%2048%2037.2544%2048%2023.9999C48%2010.7454%2037.2556%200.000976562%2024%200.000976562ZM24%207.17694C28.3855%207.17694%2031.9392%2010.7317%2031.9392%2015.1151C31.9392%2019.4995%2028.3855%2023.0532%2024%2023.0532C19.6166%2023.0532%2016.0629%2019.4995%2016.0629%2015.1151C16.0629%2010.7317%2019.6166%207.17694%2024%207.17694ZM23.9947%2041.7242C19.6208%2041.7242%2015.6149%2040.1313%2012.525%2037.4948C11.7723%2036.8528%2011.338%2035.9114%2011.338%2034.9236C11.338%2030.478%2014.936%2026.9201%2019.3826%2026.9201H28.6195C33.0672%2026.9201%2036.6515%2030.478%2036.6515%2034.9236C36.6515%2035.9124%2036.2193%2036.8517%2035.4655%2037.4937C32.3767%2040.1313%2028.3697%2041.7242%2023.9947%2041.7242Z%22%20fill%3D%22%23D7DBDF%22%2F%3E%0A%20%20%3C%2Fsvg%3E%0A" alt="" class="wpsp-profile-image"></div>
                                <div class="wpsp-preview-info">
                                <div class="wpsp-preview-date">23 September 2025</div>
                                </div>
                            </div>
                        </div>
                        <div class="wpsp-preview-content-area">
                            <div class="wpsp-preview-text">Test title lorem ipsum dolor sit amet... https://schedulepress.test/test-title/ #wordpress #blog</div>
                            <div class="wpsp-preview-post">
                                <div class="wpsp-preview-image">
                                <div style="width: 100%; height: 100%; background: linear-gradient(135deg, rgb(102, 126, 234) 0%, rgb(118, 75, 162) 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;">No image selected</div>
                                </div>
                                <div class="wpsp-preview-post-content">
                                <div class="wpsp-preview-url">https://schedulepress.test</div>
                                <div class="wpsp-preview-title">Test title</div>
                                <div class="wpsp-preview-excerpt">lorem ipsum dolor sit amet...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal Footer with Save Button -->
            <div class="wpsp-modal-footer">
                <button type="button" class="wpsp-btn wpsp-btn-secondary" id="wpsp-modal-cancel">
                    <?php echo __('Cancel', 'wp-scheduled-posts'); ?>
                </button>
                <button type="submit" class="wpsp-btn wpsp-btn-primary" id="wpsp-save-social-message">
                    <?php echo __('Save Message', 'wp-scheduled-posts'); ?>
                </button>
            </div>
        </form>
          <!-- /Left Panel -->
        <div class="wpsp-modal-right facebook">
            <div class="wpsp-preview-card">
                <div class="wpsp-preview-header">
                    <div class="wpsp-preview-avatar">
                        <div class="wpsp-avatar-circle"><img src="data:image/svg+xml;utf8,%0A%20%20%3Csvg%20width%3D%2248%22%20height%3D%2248%22%20viewBox%3D%220%200%2048%2048%22%20fill%3D%22none%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%0A%20%20%20%20%3Cpath%20d%3D%22M24%200.000976562C10.7466%200.000976562%200%2010.7454%200%2023.9999C0%2037.2544%2010.7455%2047.9989%2024%2047.9989C37.2556%2047.9989%2048%2037.2544%2048%2023.9999C48%2010.7454%2037.2556%200.000976562%2024%200.000976562ZM24%207.17694C28.3855%207.17694%2031.9392%2010.7317%2031.9392%2015.1151C31.9392%2019.4995%2028.3855%2023.0532%2024%2023.0532C19.6166%2023.0532%2016.0629%2019.4995%2016.0629%2015.1151C16.0629%2010.7317%2019.6166%207.17694%2024%207.17694ZM23.9947%2041.7242C19.6208%2041.7242%2015.6149%2040.1313%2012.525%2037.4948C11.7723%2036.8528%2011.338%2035.9114%2011.338%2034.9236C11.338%2030.478%2014.936%2026.9201%2019.3826%2026.9201H28.6195C33.0672%2026.9201%2036.6515%2030.478%2036.6515%2034.9236C36.6515%2035.9124%2036.2193%2036.8517%2035.4655%2037.4937C32.3767%2040.1313%2028.3697%2041.7242%2023.9947%2041.7242Z%22%20fill%3D%22%23D7DBDF%22%2F%3E%0A%20%20%3C%2Fsvg%3E%0A" alt="" class="wpsp-profile-image"></div>
                        <div class="wpsp-preview-info">
                        <div class="wpsp-preview-date">23 September 2025</div>
                        </div>
                    </div>
                </div>
                <div class="wpsp-preview-content-area">
                    <div class="wpsp-preview-text">Test title lorem ipsum dolor sit amet... https://schedulepress.test/test-title/ #wordpress #blog</div>
                    <div class="wpsp-preview-post">
                        <div class="wpsp-preview-image">
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, rgb(102, 126, 234) 0%, rgb(118, 75, 162) 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;">No image selected</div>
                        </div>
                        <div class="wpsp-preview-post-content">
                        <div class="wpsp-preview-url">https://schedulepress.test</div>
                        <div class="wpsp-preview-title">Test title</div>
                        <div class="wpsp-preview-excerpt">lorem ipsum dolor sit amet...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
