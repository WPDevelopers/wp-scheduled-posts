<?php
/**
 * LinkedIn Tab Content for Social Message Modal
 * 
 * @package WPSP
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get LinkedIn profiles and selected data
$linkedinProfiles = \WPSP\Helper::get_settings('linkedin_profile_list');
$selected_linkedin_profiles = isset($custom_templates['linkedin']['profiles']) ? $custom_templates['linkedin']['profiles'] : array();
$linkedin_template = isset($custom_templates['linkedin']['template']) ? $custom_templates['linkedin']['template'] : '';
$linkedin_is_global = isset($custom_templates['linkedin']['is_global']) ? $custom_templates['linkedin']['is_global'] : '';
?>

<!-- LinkedIn Profile Section -->
<div class="wpsp-profile-selection-area-wrapper" id="wpsp-profile-linkedin" style="display: none;">
    <div class="selected-profile-area">
        <ul>
            <?php
                if ( ! empty( $linkedinProfiles ) && is_array( $linkedinProfiles ) ) :
                    foreach ( $linkedinProfiles as $profile ) :
                        if ( empty( $profile->name ) || empty( $profile->thumbnail_url ) ) {
                            continue;
                        }
                        $thumbnail_url = get_safe_thumbnail_url($profile, $this->autho_logo);
                        $checked = in_array( $profile->id, $selected_linkedin_profiles ) ? 'checked' : ''; 
                ?>
                   <li class="selected-profile" title="<?php echo esc_attr( $profile->name ); ?>">
                        <img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $profile->name ); ?>" class="wpsp-profile-image">
                        <?php if( $checked ) : ?>
                            <div class="wpsp-selected-profile-action">
                                <span class="wpsp-remove-profile-btn">×</span>
                                <span class="wpsp-selected-profile-btn">
                                    <svg width="10" height="11" viewBox="0 0 10 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="0.6" y="0.900781" width="8.8" height="8.8" rx="4.4" fill="#6C62FF"></rect>
                                        <rect x="0.6" y="0.900781" width="8.8" height="8.8" rx="4.4" stroke="white" stroke-width="0.8"></rect>
                                        <g clip-path="url(#clip0_4477_4922)">
                                            <path d="M3.58398 5.30078L4.58398 6.30078L6.58398 4.30078" stroke="white" stroke-width="0.64" stroke-linecap="round" stroke-linejoin="round"></path>
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_4477_4922">
                                            <rect width="4" height="4" fill="white" transform="translate(3 3.30078)"></rect>
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </span>
                            </div>
                        <?php endif; ?>
                    </li>
                <?php
                    endforeach;
                endif;
            ?>
        </ul>
        <span class="select-profile-icon"><img src="https://schedulepress.test/wp-content/plugins/wp-scheduled-posts/assets/images/chevron-down.svg" alt=""></span>
    </div>
    <div class="wpsp-profile-selection-dropdown">
        <div class="wpsp-profile-selection-dropdown-item">
            <?php if ( ! empty( $linkedinProfiles ) && is_array( $linkedinProfiles ) ) : ?>
                <?php foreach ( $linkedinProfiles as $profile ) : 
                     $thumbnail_url = get_safe_thumbnail_url($profile, $this->autho_logo);
                ?>
                <div class="wpsp-profile-card" data-profile-id="<?php echo esc_attr( $profile->id ); ?>" data-profile-name="<?php echo esc_attr( $profile->name ); ?>" data-profile-img="<?php echo esc_url( $thumbnail_url ); ?>">
                    <div class="wpsp-profile-avatar">
                        <img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $profile->name ); ?>" class="wpsp-profile-image">
                    </div>
                    <div class="wpsp-profile-info">
                        <div class="wpsp-profile-name"><?php echo esc_html( $profile->name ); ?></div>
                    </div>
                    <div class="wpsp-profile-checkbox">
                        <input 
                            type="checkbox" 
                            class="wpsp-modal-profile-checkbox wpsp-linkedin-checkbox" 
                            name="linkedin_profiles[]"
                            value="<?php echo esc_attr( $profile->id ); ?>" 
                            data-name="<?php echo esc_attr( $profile->name ); ?>"
                            data-img="<?php echo esc_url( $thumbnail_url ); ?>"
                            data-platform="linkedin"
                            <?php echo in_array( $profile->id, $selected_linkedin_profiles ) ? 'checked' : ''; ?>
                        >
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="wpsp-template-textarea">
        <div class="wpsp-textarea-wrapper">
            <textarea placeholder="Enter your custom template here..." id="wpsp-template-input-linkedin" class="wpsp-template-input" rows="4"><?php echo esc_textarea($linkedin_template ? $linkedin_template : '{title} {content} {url} {tags}'); ?></textarea>
        </div>
        <div class="wpsp-template-meta">
        <span class="wpsp-placeholders">Available: {title} {content} {url} {tags}</span>
        <div class="wpsp-custom-template-field-info">
            <span class="active">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_4607_4338)">
                    <path d="M7.93916 7.94141C7.6579 8.22277 7.49993 8.60434 7.5 9.00217C7.50007 9.40001 7.65818 9.78152 7.93954 10.0628C8.2209 10.344 8.60247 10.502 9.0003 10.5019C9.39814 10.5019 9.77965 10.3438 10.0609 10.0624" stroke="#98A2B3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M12.5107 12.5048C11.4587 13.163 10.241 13.5082 9 13.5C6.3 13.5 4.05 12 2.25 9.00002C3.204 7.41002 4.284 6.24152 5.49 5.49452M7.635 4.63502C8.08428 4.54407 8.54161 4.49884 9 4.50002C11.7 4.50002 13.95 6.00002 15.75 9.00002C15.2505 9.83252 14.7157 10.5503 14.1465 11.1525" stroke="#98A2B3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    <path d="M2.25 2.25L15.75 15.75" stroke="#98A2B3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                    </g>
                    <defs>
                    <clipPath id="clip0_4607_4338">
                        <rect width="18" height="18" fill="white"></rect>
                    </clipPath>
                    </defs>
                </svg>
            </span>
            <span class="wpsp-char-count ">96/63206</span>
        </div>
        </div>
        <div class="wpsp-global-template ">
        <span class="" style="display: flex; align-items: center; gap: 6px;">
            Use global template
            <span class="wpsp-tooltip-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" class="wpsp-tooltip-icon">
                    <circle cx="12" cy="12" r="10" fill="#6a4bff"></circle>
                    <text x="12" y="16" text-anchor="middle" font-size="12" fill="#fff" font-family="Arial" font-weight="bold">i</text>
                </svg>
                <span class="wpsp-tooltip-text">If enabled, this template will be applied across all the selected social platforms.</span>
            </span>
        </span>
        <div class="wpsp-use-global-template-checkbox-wrapper ">
            <input type="checkbox" id="useGlobalTemplate_linkedin" <?php echo $linkedin_is_global ? 'checked' : ''; ?>><label for="useGlobalTemplate_linkedin"></label></div>
        </div>
    </div>
</div>
