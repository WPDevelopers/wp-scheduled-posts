<?php 
namespace WPSP;

class Post 
{

    public $autho_logo = WPSP_ASSETS_URI . 'images/author-logo.jpeg';
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
        add_action('wp_ajax_wpsp_save_modal_data', [$this, 'wpsp_save_modal_data']);
    }
    
    public function wpsp_save_modal_data() {
        $platforms = ['facebook', 'instagram', 'twitter', 'google_business'];
        $stored_profiles = [];
    
        foreach ($platforms as $platform) {
            $selected_ids     = $_POST["{$platform}_profiles"] ?? [];
            $platformProfiles = (array) \WPSP\Helper::get_settings("{$platform}_profile_list");
    
            if (empty($selected_ids) || empty($platformProfiles)) {
                continue;
            }
    
            // Create a lookup map for faster access
            $profile_map = [];
            foreach ($platformProfiles as $profile) {
                if (!empty($profile->id)) {
                    $profile_map[$profile->id] = $profile;
                }
            }
    
            foreach ($selected_ids as $key => $profile) {
                if (empty($profile['id']) || !isset($profile_map[$profile['id']])) {
                    continue;
                }
    
                $p = $profile_map[$profile['id']];
    
                $stored_profiles[] = [
                    'id'            => $p->id,
                    'platform'      => $platform,
                    'platformKey'   => $key,
                    'name'          => $p->name,
                    'type'          => $p->type ?? 'page',
                    'thumbnail_url' => $p->thumbnail_url ?? '',
                    'share_type'    => 'custom',
                ];
            }
        }
    
        // Save to post meta if valid
        $post_id = intval($_POST['post_id'] ?? 0);
        if ($post_id) {
            update_post_meta($post_id, '_wpsp_social_profiles', $stored_profiles);
        }
    
        wp_send_json_success([
            'message' => __('Profiles saved successfully', 'wp-scheduled-posts'),
            'data'    => $stored_profiles,
        ]);
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
        echo '<button type="button" class="button button-primary" onclick="mypluginOpenModal()">' . esc_html__('Editor Panel', 'wp-scheduled-posts') . '</button>';
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
                            <?php //$this->post_modal_social_share() ?>
                            <button type="button" class="btn add-social-message-btn" id="wpsp-add-social-message" data-modal-target="wpsp-social-message-modal">
                                <?php echo __('Add Social Message','wp-scheduled-posts') ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="wpsp-modal--footer">
                        <button class="btn secondary-btn" id="wpsp-save-settings">Save Settings</button>
                        <button class="btn primary-btn">Share Now
                            <svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1.5 11L6.5 6L1.5 1" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>
                    <?php require_once WPSP_INCLUDES_DIR_PATH . '/Views/social_message_modal_content.php'; ?>
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
                                <input type="datetime-local" id="birthdaytime">
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
                                <input type="datetime-local" id="birthdaytime">
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
                                        <input type="datetime-local" id="birthdaytime">
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
        require_once WPSP_INCLUDES_DIR_PATH . '/Views/social_share.php';
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
                                     // Default to profile thumbnail
                                    $thumbnail_url = ! empty( $profile->thumbnail_url ) ? $profile->thumbnail_url : $this->autho_logo;
                                    if ( ! empty( $profile->thumbnail_url ) ) {
                                        $response = wp_remote_head( $profile->thumbnail_url );
                                    
                                        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
                                            $thumbnail_url = $this->autho_logo;
                                        }
                                    }
                            ?>
                                <div class="option">
                                    <div class="author--details">
                                        <img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $profile->name ); ?>">
                                        <h5 class="title"><?php echo esc_html( $profile->name ); ?></h5>
                                        <button class="profile">PROFILE</button>
                                    </div>
                                    <input 
                                        type="checkbox" 
                                        class="profile" 
                                        value="<?php echo esc_attr( $profile->name ); ?>" 
                                        data-img="<?php echo esc_url( $thumbnail_url ); ?>"
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
