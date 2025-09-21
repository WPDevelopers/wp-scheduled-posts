<?php 
namespace WPSP;

class Post 
{
    public function __construct()
    {
        // Print modal HTML to footer of all editors + frontend
        add_action('elementor/editor/footer', [$this, 'post_modal_options']); // Elementor
        add_action('admin_footer', function() {
            global $post, $pagenow;
            $allowed_post_types = ['post', 'page'];
        
            if (in_array($pagenow, ['post.php', 'post-new.php'])) {
                if (isset($post) && in_array($post->post_type, $allowed_post_types)) {
                    $this->post_modal_options();
                }
                if (!isset($post) && isset($_GET['post_type']) && in_array($_GET['post_type'], $allowed_post_types)) {
                    $this->post_modal_options();
                }
            }
        });
        
        add_action('add_meta_boxes', [$this, 'wpsp_add_metabox']);
    }

    public function wpsp_add_metabox() {
        add_meta_box(
            'wpsp_modal_metabox',
            __('SchedulePress', 'myplugin'),
            [$this, 'render_classic_metabox'],
            null, // applies to all post types
            'side',
            'default'
        );
    }
    
    public function render_classic_metabox($post) {
        echo '<button type="button" class="button button-primary" onclick="mypluginOpenModal()">' . esc_html__('Editor Panel', 'myplugin') . '</button>';
    }
    

    public function post_modal_options() {
        ?>
            <div id="wpsp-post-modal" class="wpsp-post--modal">
                <div class="wpsp-modal--wrapper">
                    <?php require_once WPSP_INCLUDES_DIR_PATH . '/Views/header.php'; ?>
                    <div class="wpsp-modal--body">
                        <div class="scheduling--wrapper">
                            <h2 class="title"><?php echo __('Scheduling Settings','wp-scheduled-posts') ?></h2>
                            <?php require_once WPSP_INCLUDES_DIR_PATH . '/Views/auto_schedule.php'; ?>
                            <?php require_once WPSP_INCLUDES_DIR_PATH . '/Views/scheduling_options.php'; ?>
                            <?php require_once WPSP_INCLUDES_DIR_PATH . '/Views/advanced_schedule.php'; ?>
                        </div>
                        <div class="social-share--wrapper">
                            <?php $this->post_modal_social_share() ?>
                        </div>
                    </div>
                    
                    <div class="wpsp-modal--footer">
                        <button class="btn secondary-btn">Save Settings</button>
                        <button class="btn primary-btn">Share Now
                            <svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1.5 11L6.5 6L1.5 1" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        <?php
    }

    public function post_modal_scheduling_options()
    {
        ?>
            <div class="wpsp-post--card">
                <div class="card--title">
                    <h4 class="title">Scheduling Options</h4>
                    <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="9" cy="9.5" r="9" fill="#FFEEE0"/>
                        <path d="M4.06398 11.3651C3.83586 9.88232 3.60775 8.39961 3.37964 6.91686C3.32905 6.58817 3.70304 6.364 3.96906 6.56353C4.67976 7.09656 5.39043 7.62955 6.10113 8.16257C6.33513 8.33807 6.6685 8.28096 6.83073 8.03758L8.60571 5.37508C8.79329 5.09372 9.20669 5.09372 9.39426 5.37508L11.1693 8.03758C11.3315 8.28096 11.6649 8.33803 11.8989 8.16257C12.6096 7.62955 13.3202 7.09656 14.0309 6.56353C14.2969 6.364 14.6709 6.58817 14.6204 6.91686C14.3923 8.39961 14.1642 9.88232 13.936 11.3651H4.06398Z" fill="#FFA454"/>
                        <path d="M13.4218 13.8328H4.57914C4.29489 13.8328 4.06445 13.6024 4.06445 13.3181V12.1875H13.9365V13.3181C13.9365 13.6024 13.706 13.8328 13.4218 13.8328Z" fill="#FFA454"/>
                    </svg>
                </div>
                <div class="wpsp-post-items--wrapper">
                    <div class="wpsp-post--items">
                        <div class="card--title">
                            <h5 class="title">Unpublish On</h5>
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_3532_3956)">
                                <circle cx="6.99935" cy="6.99935" r="5.83333" stroke="#667085" stroke-width="1.2"/>
                                <path d="M7 4.08398V7.58398" stroke="#667085" stroke-width="1.2" stroke-linecap="round"/>
                                <circle cx="6.99935" cy="9.33333" r="0.583333" fill="#667085"/>
                                </g>
                                <defs>
                                <clipPath id="clip0_3532_3956">
                                <rect width="14" height="14" fill="white"/>
                                </clipPath>
                                </defs>
                            </svg>
                        </div>
                        <div class="wpsp-date--picker">
                            <form action="/action_page.php">
                                <input type="datetime-local" id="birthdaytime" name="birthdaytime">
                            </form>
                        </div>
                    </div>
                    <div class="wpsp-post--items">
                        <div class="card--title">
                            <h5 class="title">Republish On</h5>
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_3532_3956)">
                                <circle cx="6.99935" cy="6.99935" r="5.83333" stroke="#667085" stroke-width="1.2"/>
                                <path d="M7 4.08398V7.58398" stroke="#667085" stroke-width="1.2" stroke-linecap="round"/>
                                <circle cx="6.99935" cy="9.33333" r="0.583333" fill="#667085"/>
                                </g>
                                <defs>
                                <clipPath id="clip0_3532_3956">
                                <rect width="14" height="14" fill="white"/>
                                </clipPath>
                                </defs>
                            </svg>
                        </div>
                        <div class="wpsp-date--picker">
                            <form action="/action_page.php">
                                <input type="datetime-local" id="birthdaytime" name="birthdaytime">
                            </form>
                        </div>
                    </div>
                </div>
                <div class="wpsp-tag--wrapper">
                    <div class="card--title">
                        <h5 class="title">Republish On</h5>
                    </div>
                    <div class="tag-item--wrapper">
                        <div class="tag--item">
                            <span>tagname1</span>
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9.20078 9.20078L2.80078 2.80078" stroke="#475467" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2.80078 9.20078L9.20078 2.80078" stroke="#475467" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="tag--item">
                            <span>tagname1</span>
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9.20078 9.20078L2.80078 2.80078" stroke="#475467" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2.80078 9.20078L9.20078 2.80078" stroke="#475467" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="tag--item">
                            <span>tagname1</span>
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9.20078 9.20078L2.80078 2.80078" stroke="#475467" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2.80078 9.20078L9.20078 2.80078" stroke="#475467" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="advance-schedule--wrapper">
                    <div class="advance-schedule--header">
                        <h5 class="title">Advanced Schedule</h5>
                        <label class="toggle--wrap">
                            <input type="checkbox" checked="checked"> 
                            <span class="slider"></span>
                        </label> 
                    </div>
                    <div class="advance-schedule--items">
                        <div class="wpsp-post-items--wrapper">
                            <div class="wpsp-post--items">
                                <div class="card--title">
                                    <h5 class="title">Unpublish On</h5>
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <g clip-path="url(#clip0_3532_3956)">
                                        <circle cx="6.99935" cy="6.99935" r="5.83333" stroke="#667085" stroke-width="1.2"/>
                                        <path d="M7 4.08398V7.58398" stroke="#667085" stroke-width="1.2" stroke-linecap="round"/>
                                        <circle cx="6.99935" cy="9.33333" r="0.583333" fill="#667085"/>
                                        </g>
                                        <defs>
                                        <clipPath id="clip0_3532_3956">
                                        <rect width="14" height="14" fill="white"/>
                                        </clipPath>
                                        </defs>
                                    </svg>
                                </div>
                                <div class="wpsp-date--picker">
                                    <form action="/action_page.php">
                                        <input type="datetime-local" id="birthdaytime" name="birthdaytime">
                                    </form>
                                </div>
                            </div>
                            <div class="wpsp-post--items">
                                <div class="card--title opacity">
                                    <h5 class="title">Republish On</h5>
                                </div>
                                <div class="add-version--wrap">
                                    <button class="add-version--btn">
                                        <span>Add new version</span>
                                        <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <g clip-path="url(#clip0_3637_2112)">
                                            <path d="M8 3.83398V13.1673" stroke="#6C62FF" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M3.33398 8.5H12.6673" stroke="#6C62FF" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </g>
                                            <defs>
                                            <clipPath id="clip0_3637_2112">
                                            <rect width="16" height="16" fill="white" transform="translate(0 0.5)"/>
                                            </clipPath>
                                            </defs>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php
    }

    public function post_modal_social_share()
    {
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
                    <input type='hidden' id='wpscppro_custom_social_share_image' class='regular-text text-upload' name='wpscppro_custom_social_share_image'/>
                    <div id="wpsp_social_share_image_preview"></div>
                    <div class="upload-button--wrapper">
                        <button class="btn upload--btn" id="wpsp_upload_banner"><?php echo __('Upload Banner','wp-scheduled-posts') ?></button>
                        <button class="btn remove--btn" id="wpsp_remove_banner"><?php echo __('Remove Banner','wp-scheduled-posts') ?></button>
                    </div>
                </div>
                <div class="social-platform--wrapper">
                    <span class="title"><?php echo __('Choose Social Share Platform','wp-scheduled-posts') ?></span>
                    <!-- Social item Facebook -->
                    <?php $this->social_profile_facebook(); ?>
                    <!-- Social item LinkedIn -->
                    <?php $this->social_profile_linkedin(); ?>

                    <!-- Social item Instagram -->
                    <div class="social--item">
                        <div class="card--title">
                            <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect y="0.322266" width="18" height="18" rx="9" fill="url(#paint0_linear_3520_6459)"/>
                                <path d="M10.9119 4.50195H7.09191C5.38791 4.50195 4.00391 5.83652 4.00391 7.47967V11.1632C4.00391 12.8064 5.38791 14.141 7.09191 14.141H10.9119C12.6159 14.141 13.9999 12.8064 13.9999 11.1632V7.47967C13.9999 5.83652 12.6159 4.50195 10.9119 4.50195ZM12.8839 11.1671C12.8839 12.2162 11.9999 13.0725 10.9079 13.0725H7.08791C5.99991 13.0725 5.11191 12.2201 5.11191 11.1671V7.48352C5.11191 6.43438 5.99591 5.5781 7.08791 5.5781H10.9079C11.9959 5.5781 12.8839 6.43052 12.8839 7.48352V11.1671Z" fill="white"/>
                                <path d="M9.00131 6.85938C7.59331 6.85938 6.44531 7.96637 6.44531 9.32409C6.44531 10.6818 7.59331 11.7888 9.00131 11.7888C10.4093 11.7888 11.5573 10.6818 11.5573 9.32409C11.5573 7.96637 10.4093 6.85938 9.00131 6.85938ZM9.00131 10.8207C8.14531 10.8207 7.44931 10.1495 7.44931 9.32409C7.44931 8.49866 8.14531 7.82752 9.00131 7.82752C9.85731 7.82752 10.5533 8.49866 10.5533 9.32409C10.5533 10.1495 9.85731 10.8207 9.00131 10.8207Z" fill="white"/>
                                <path d="M11.7482 7.12871C11.9837 7.0919 12.1437 6.87797 12.1055 6.6509C12.0673 6.42382 11.8455 6.26958 11.61 6.3064C11.3745 6.34321 11.2145 6.55713 11.2527 6.7842C11.2909 7.01128 11.5127 7.16552 11.7482 7.12871Z" fill="white"/>
                                <defs>
                                <linearGradient id="paint0_linear_3520_6459" x1="2.14716" y1="16.1751" x2="14.9372" y2="3.38511" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#FEE411"/>
                                <stop offset="0.0518459" stop-color="#FEDB16"/>
                                <stop offset="0.1381" stop-color="#FEC125"/>
                                <stop offset="0.2481" stop-color="#FE983D"/>
                                <stop offset="0.3762" stop-color="#FE5F5E"/>
                                <stop offset="0.5" stop-color="#FE2181"/>
                                <stop offset="1" stop-color="#9000DC"/>
                                </linearGradient>
                                </defs>
                            </svg>
                            <h5 class="title">Instagram</h5>
                        </div>
                        <div class="carousal--wrapper">
                            <div class="carousal--tab">
                                <div id="tab-reels" class="tab--item active" onclick="switchTab('reels')">
                                    Reels 
                                    <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="8" cy="8.32227" r="8" fill="#F7E6D7"/>
                                        <path d="M3.61242 9.98271C3.40966 8.66471 3.20689 7.34675 3.00412 6.02874C2.95916 5.73658 3.29159 5.53731 3.52806 5.71468C4.15979 6.18848 4.79149 6.66224 5.42322 7.13604C5.63122 7.29204 5.92756 7.24128 6.07176 7.02494L7.64952 4.65828C7.81626 4.40818 8.18372 4.40818 8.35046 4.65828L9.92822 7.02494C10.0724 7.24128 10.3688 7.29201 10.5768 7.13604C11.2085 6.66224 11.8402 6.18848 12.4719 5.71468C12.7084 5.53731 13.0408 5.73658 12.9959 6.02874C12.7931 7.34675 12.5904 8.66471 12.3876 9.98271H3.61242Z" fill="#FF9437"/>
                                        <path d="M11.9309 12.1754H4.07078C3.81811 12.1754 3.61328 11.9706 3.61328 11.7179V10.7129H12.3884V11.7179C12.3884 11.9706 12.1836 12.1754 11.9309 12.1754Z" fill="#FF9437"/>
                                    </svg>
                                </div>
                                <div id="tab-carousel" class="tab--item" onclick="switchTab('carousel')">
                                    Carousel 
                                    <svg width="16" height="17" viewBox="0 0 16 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="8" cy="8.32227" r="8" fill="#F7E6D7"/>
                                        <path d="M3.61242 9.98271C3.40966 8.66471 3.20689 7.34675 3.00412 6.02874C2.95916 5.73658 3.29159 5.53731 3.52806 5.71468C4.15979 6.18848 4.79149 6.66224 5.42322 7.13604C5.63122 7.29204 5.92756 7.24128 6.07176 7.02494L7.64952 4.65828C7.81626 4.40818 8.18372 4.40818 8.35046 4.65828L9.92822 7.02494C10.0724 7.24128 10.3688 7.29201 10.5768 7.13604C11.2085 6.66224 11.8402 6.18848 12.4719 5.71468C12.7084 5.53731 13.0408 5.73658 12.9959 6.02874C12.7931 7.34675 12.5904 8.66471 12.3876 9.98271H3.61242Z" fill="#FF9437"/>
                                        <path d="M11.9309 12.1754H4.07078C3.81811 12.1754 3.61328 11.9706 3.61328 11.7179V10.7129H12.3884V11.7179C12.3884 11.9706 12.1836 12.1754 11.9309 12.1754Z" fill="#FF9437"/>
                                    </svg>
                                </div>
                            </div>

                            <div class="tab--content">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 15C3 17.8284 3 19.2426 3.87868 20.1213C4.75736 21 6.17157 21 9 21H15C17.8284 21 19.2426 21 20.1213 20.1213C21 19.2426 21 17.8284 21 15" stroke="#1B1B50" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 16V3M12 3L16 7.375M12 3L8 7.375" stroke="#1B1B50" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <div class="text">Reel1.png, Reel2.png, Reel3.png</div>
                                <div class="subtext">3 image uploaded</div>
                                <button class="btn primary-btn">Browse File</button>
                            </div>

                            <div id="tabContent" class="tab--content">
                                <!-- Dynamic content goes here -->
                            </div>
                            </div>

                            <!-- Popup with slider -->
                            <div class="popup" id="popup">
                            <div class="popup-content">
                                <span class="close-btn" onclick="closePopup()">&times;</span>
                                <img id="popupImage" src="" alt="Popup Image">
                                <div class="slider-nav">
                                <button onclick="prevImage()">Prev</button>
                                <button onclick="nextImage()">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php 
    }

    public function social_profile_facebook()
    {
        $facebookProfiles = \WPSP\Helper::get_settings('facebook_profile_list');
        ?>
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
                <div class="wpsp-select--option">
                    <div class="dropdown--selected selectedBox" id="selectedBox">
                        <span><?php echo __('Select profile','wp-scheduled-posts') ?></span>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 7.5L10 12.5L15 7.5" stroke="#475467" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="dropdown--options" id="dropdownOptions">
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
                            ?>
                                <div class="option">
                                    <div class="author--details">
                                        <img src="<?php echo esc_url( $profile->thumbnail_url ); ?>" alt="<?php echo esc_attr( $profile->name ); ?>">
                                        <h5 class="title"><?php echo esc_html( $profile->name ); ?></h5>
                                        <button class="profile">PROFILE</button>
                                    </div>
                                    <input 
                                        type="checkbox" 
                                        class="profile" 
                                        value="<?php echo esc_attr( $profile->name ); ?>" 
                                        data-img="<?php echo esc_url( $profile->thumbnail_url ); ?>"
                                    >
                                </div>
                            <?php
                                endforeach;
                            endif;
                        ?>
                    </div>
                </div>
            </div>
        <?php 
    }
    public function social_profile_linkedin()
    {
        $linkedinProfiles = \WPSP\Helper::get_settings('linkedin_profile_list');
        ?>
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
                    <h5 class="title"><?php echo __('LinkedIn','wp-scheduled-posts') ?></h5>
                </div>
                <div class="wpsp-select--option">
                    <div class="dropdown--selected selectedBox" id="selectedBox">
                        <span><?php echo __('Select profile','wp-scheduled-posts') ?></span>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 7.5L10 12.5L15 7.5" stroke="#475467" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="dropdown--options" id="dropdownOptions">
                        <div class="option">
                            <span><?php echo __('Select All', 'wp-scheduled-posts'); ?></span>
                            <input type="checkbox" id="selectAll">
                        </div>
                        <?php
                            if ( ! empty( $linkedinProfiles ) && is_array( $linkedinProfiles ) ) :
                                foreach ( $linkedinProfiles as $profile ) :
                                    if ( empty( $profile->name ) || empty( $profile->thumbnail_url ) ) {
                                        continue; // skip if essential data is missing
                                    }
                            ?>
                                <div class="option">
                                    <div class="author--details">
                                        <img src="<?php echo esc_url( $profile->thumbnail_url ); ?>" alt="<?php echo esc_attr( $profile->name ); ?>">
                                        <h5 class="title"><?php echo esc_html( $profile->name ); ?></h5>
                                        <button class="profile">PROFILE</button>
                                    </div>
                                    <input 
                                        type="checkbox" 
                                        class="profile" 
                                        value="<?php echo esc_attr( $profile->name ); ?>" 
                                        data-img="<?php echo esc_url( $profile->thumbnail_url ); ?>"
                                    >
                                </div>
                            <?php
                                endforeach;
                            endif;
                        ?>
                    </div>
                </div>
            </div>
        <?php 
    }
    
}
