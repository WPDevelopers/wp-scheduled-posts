<?php
/**
 * Make Object of wpsp_scheduled
 *
 */
if ( is_admin() ) {
    global $wpspcalendar;
    if ( empty($wpspcalendar) )
        $wpspcalendar = new wpsp_scheduled();
}


/*
 * This error code matches CONCURRENCY_ERROR from wpspcalendar.js
 */
define( 'WPSP_CONCURRENCY_ERROR', 4 );

/*
 * This error code matches PERMISSION_ERROR from wpspcalendar.js
 */
define( 'WPSP_PERMISSION_ERROR', 5 );

/*
 * This error code matches NONCE_ERROR from wpspcalendar.js
 */
define( 'WPSP_NONCE_ERROR', 6 );

/**
 * WPSP Scheduled Facts
 *
 * @class wpsp_scheduled
 */
class wpsp_scheduled {
    
    protected $supports_custom_types;
    protected $default_time;

    function __construct() {
        add_action('wp_ajax_wpsp_saveoptions', array(&$this, 'wpsp_scheduled_saveoptions'));
        add_action('wp_ajax_wpsp_changedate', array(&$this, 'wpsp_scheduled_changedate'));
        add_action('wp_ajax_wpsp_savepost', array(&$this, 'wpsp_scheduled_savepost'));
        add_action('wp_ajax_wpsp_changetitle', array(&$this, 'wpsp_scheduled_changetitle'));
        add_action('admin_menu', array(&$this, 'wpsp_scheduled_list_add_management_page'));
        add_action('wp_ajax_wpsp_posts', array(&$this, 'wpsp_scheduled_posts'));
        add_action('wp_ajax_wpsp_getpost', array(&$this, 'wpsp_scheduled_getpost'));
        add_action('wp_ajax_wpsp_deletepost', array(&$this, 'wpsp_scheduled_deletepost'));
        
        /*
         * This boolean variable will be used to check whether this 
         * installation of WordPress supports custom post types.
         */
        $this->supports_custom_types = function_exists('get_post_types') && function_exists('get_post_type_object');

        /*
         * This is the default time that posts get created at, for now 
         * we are using 10am, but this could become an option later.
         */
        $this->default_time = get_option("wpsp_default_time") != "" ? get_option("wpsp_default_time") : '10:00';
         
         /*
          * This is the default status used for creating new posts.
          */
        $this->default_status = get_option("wpsp_default_status") != "" ? get_option("wpsp_default_status") : 'draft';
        
        /*
         * We use these variables to hold the post dates for the filter when 
         * we do our post query.
         */
        
    }
    
    /*
     * This function adds our calendar page to the admin UI
     * @function wpsp_scheduled_list_add_management_page
     */
    function wpsp_scheduled_list_add_management_page() {
        if (function_exists('add_management_page') ) {
            
            $page = add_submenu_page( pluginsFOLDER, __('Schedule Calendar', 'wp-scheduled-posts'), __('Schedule Calendar', 'wp-scheduled-posts'), 'manage_options', 'wpsp-schedule-calendar', array(&$this, 'admin_list_wpsp'));
            add_action( "admin_print_scripts-$page", array(&$this, 'wpsp_scripts'));

            if ($this->supports_custom_types) {


                /* 
                 * We add one calendar for Posts and then we add a separate calendar for each
                 * custom post type.  This calendar will have an URL like this:
                 * /wp-admin/edit.php?post_type=podcasts&page=cal_podcasts
                 *
                 * We can then use the post_type parameter to show the posts of just that custom
                 * type and update the labels for each post type.
                 */
                $args = array(
                    'public'   => get_option("wpsp_custom_posts_public") != "" ? get_option("wpsp_custom_posts_public") : true,
                    '_builtin' => false
                ); 
                $output = 'names'; // names or objects
                $operator = 'and'; // 'and' or 'or'
                $post_types = get_post_types($args,$output,$operator); 

                foreach ($post_types as $post_type) {
                    $show_this_post_type = apply_filters("wpsp_show_calendar_$post_type", true);
                    if ($show_this_post_type) {
                        $page = add_submenu_page('edit.php?post_type=' . $post_type, __('Calendar', 'wpspcalendar'), __('Calendar', 'wpspcalendar'), 'edit_posts', 'cal_' . $post_type, array(&$this, 'admin_list_wpsp'));
                        add_action( "admin_print_scripts-$page", array(&$this, 'wpsp_scripts'));
                    }
                }
            }
        }
    }

    
    
    /*
     * This is a utility function to open a file add it to our
     * output stream.  We use this to embed JavaScript and CSS
     * files and cut down on the number of HTTP requests.
     */
    function wpspscriptFile($myFile) {
        $fh = fopen($myFile, 'r');
        $theData = fread($fh, filesize($myFile));
        fclose($fh);
        echo $theData;
    }
     
    /*
     * This is the function that generates our admin page.  It adds the CSS files and 
     * generates the divs that we need for the JavaScript to work.
     */
    function admin_list_wpsp() {
        
        /*
         * We want to count the number of times they load the calendar
         * so we only show the feedback after they have been using it 
         * for a little while.
         */
        $wpspcalendar_count = get_option("count_wpsp");
        if ($wpspcalendar_count == '') {
            $wpspcalendar_count = 0;
            add_option("count_wpsp", $wpspcalendar_count, "", "yes");
        }
            
        if (get_option("wpsp_got_feedback") != "done") {
            $wpspcalendar_count++;
            update_option("count_wpsp", $wpspcalendar_count);
        }
        
        /*
         * This section of code embeds certain CSS and
         * JavaScript files into the HTML.  This has the 
         * advantage of fewer HTTP requests, but the 
         * disadvantage that the browser can't cache the
         * results.  We only do this for files that will
         * be used on this page and nowhere else.
         */
         
        echo '<!-- This is the styles from time picker.css -->';
        echo '<style type="text/css">';
        $this->wpspscriptFile(dirname( __FILE__ ) . "/lib/timePicker.css");
        echo '</style>';
        
        echo '<!-- This is the styles from humanmsg.css -->';
        echo '<style type="text/css">';
        $this->wpspscriptFile(dirname( __FILE__ ) . "/lib/humanmsg.css");
        echo '</style>';
        
        echo '<!-- This is the styles from wpspcalendar.css -->';
        echo '<style type="text/css">';
        $this->wpspscriptFile(dirname( __FILE__ ) . "/wpspcalendar.css");
        echo '</style>';
        
        ?>
        
        <!-- This is just a little script so we can pass the AJAX URL and some localized strings -->
        <script type="text/javascript">
            jQuery(document).ready(function(){
                wpsp.plugin_url = '<?php echo(plugins_url("/", __FILE__ )); ?>';
                wpsp.wp_nonce = '<?php echo wp_create_nonce("edit-calendar"); ?>';
                <?php 
                    if (get_option("wpsp_select_weeks") != "") {
                ?>
                    wpsp.weeksPref = <?php echo(get_option("wpsp_select_weeks")); ?>;
                <?php
                    }
                ?>
                
                <?php 
                    if (get_option("wpsp_author_pref") != "") {
                ?>
                    wpsp.authorPref = <?php echo(get_option("wpsp_author_pref")); ?>;
                <?php
                    } 
                ?>
                
                <?php 
                    if (get_option("wpsp_time_pref") != "") {
                ?>
                    wpsp.timePref = <?php echo(get_option("wpsp_time_pref")); ?>;
                <?php
                    }
                ?>
                
                <?php 
                    if (get_option("wpsp_status_pref") != "") {
                ?>
                    wpsp.statusPref = <?php echo(get_option("wpsp_status_pref")); ?>;
                <?php
                    }
                ?>
                
                <?php 
                    if (get_option("wpsp_got_feedback") != "done") {
                ?>
                    wpsp.doFeedbackPref = true;
                    wpsp.visitCount = <?php echo(get_option("count_wpsp")); ?>;
                <?php
                    }
                ?>
    
                <?php $this->wpsp_getLastPost(); ?>
                
                wpsp.startOfWeek = <?php echo(get_option("start_of_week")); ?>;
                wpsp.timeFormat = "<?php echo(get_option("time_format")); ?>";
                wpsp.previewDateFormat = "MMMM d";
                wpsp.defaultTime = "<?php echo $this->default_time; ?>";
                wpsp.defaultStatus = "<?php echo $this->default_status; ?>";
    
                /*
                 * We want to show the day of the first day of the week to match the user's 
                 * country code.  The problem is that we can't just use the WordPress locale.
                 * If the locale was fr-FR so we started the week on Monday it would still 
                 * say Sunday was the first day if we didn't have a proper language bundle
                 * for French.  Therefore we must depend on the language bundle writers to
                 * specify the locale for the language they are adding.
                 * 
                 */
                wpsp.locale = '<?php echo(__('en-US', 'wpspcalendar')) ?>';
                
                /*
                 * These strings are all localized values.  The WordPress localization mechanism 
                 * doesn't really extend to JavaScript so we localize the strings in PHP and then
                 * pass the values to JavaScript.
                 */
                
                wpsp.str_by = <?php echo($this->wpsp_json_encode(__('%1$s by %2$s', 'wpspcalendar'))) ?>;
                
                wpsp.str_addPostLink = <?php echo($this->wpsp_json_encode(__('New Post', 'wpspcalendar'))) ?>;
                wpsp.str_addDraftLink = <?php echo($this->wpsp_json_encode(__('New Draft', 'wpspcalendar'))) ?>;
                wpsp.ltr = <?php echo($this->wpsp_json_encode(strtolower(__('ltr', 'wpspcalendar')))) ?>;
                
                wpsp.str_draft = <?php echo($this->wpsp_json_encode(__(' [DRAFT]', 'wpspcalendar'))) ?>;
                wpsp.str_pending = <?php echo($this->wpsp_json_encode(__(' [PENDING]', 'wpspcalendar'))) ?>;
                wpsp.str_sticky = <?php echo($this->wpsp_json_encode(__(' [STICKY]', 'wpspcalendar'))) ?>;
                wpsp.str_draft_sticky = <?php echo($this->wpsp_json_encode(__(' [DRAFT, STICKY]', 'wpspcalendar'))) ?>;
                wpsp.str_pending_sticky = <?php echo($this->wpsp_json_encode(__(' [PENDING, STICKY]', 'wpspcalendar'))) ?>;
                wpsp.str_edit = <?php echo($this->wpsp_json_encode(__('Edit', 'wpspcalendar'))) ?>;
                wpsp.str_quick_edit = <?php echo($this->wpsp_json_encode(__('Quick Edit', 'wpspcalendar'))) ?>;
                wpsp.str_del = <?php echo($this->wpsp_json_encode(__('Delete', 'wpspcalendar'))) ?>;
                wpsp.str_view = <?php echo($this->wpsp_json_encode(__('View', 'wpspcalendar'))) ?>;
                wpsp.str_republish = <?php echo($this->wpsp_json_encode(__('Edit', 'wpspcalendar'))) ?>;
                wpsp.str_status = <?php echo($this->wpsp_json_encode(__('Status:', 'wpspcalendar'))) ?>;
                wpsp.str_cancel = <?php echo($this->wpsp_json_encode(__('Cancel', 'wpspcalendar'))) ?>;
                wpsp.str_posttitle = <?php echo($this->wpsp_json_encode(__('Title', 'wpspcalendar'))) ?>;
                wpsp.str_postcontent = <?php echo($this->wpsp_json_encode(__('Content', 'wpspcalendar'))) ?>;
                wpsp.str_newpost = <?php echo($this->wpsp_json_encode(__('Add a new post on %s', 'wpspcalendar'))) ?>;
                wpsp.str_newdraft = <?php echo($this->wpsp_json_encode(__('Add a new draft', 'wpspcalendar'))) ?>;
                wpsp.str_newpost_title = <?php echo($this->wpsp_json_encode(sprintf(__('New %s - ', 'wpspcalendar'), $this->wpsp_get_posttype_singlename()))) ?> ;
                wpsp.str_newdraft_title = <?php echo($this->wpsp_json_encode(__('New Draft', 'wpspcalendar'))) ?>;
                wpsp.str_update = <?php echo($this->wpsp_json_encode(__('Update', 'wpspcalendar'))) ?>;
                wpsp.str_publish = <?php echo($this->wpsp_json_encode(__('Schedule', 'wpspcalendar'))) ?>;
                wpsp.str_review = <?php echo($this->wpsp_json_encode(__('Submit for Review', 'wpspcalendar'))) ?>;
                wpsp.str_save = <?php echo($this->wpsp_json_encode(__('Save', 'wpspcalendar'))) ?>;
                wpsp.str_edit_post_title = <?php echo($this->wpsp_json_encode(__('Edit %1$s - %2$s', 'wpspcalendar'))) ?>;
                wpsp.str_scheduled = <?php echo($this->wpsp_json_encode(__('Scheduled', 'wpspcalendar'))) ?>;
                
                wpsp.str_del_msg1 = <?php echo($this->wpsp_json_encode(__('You are about to delete the post "', 'wpspcalendar'))) ?>;
                wpsp.str_del_msg2 = <?php echo($this->wpsp_json_encode(__('". Press Cancel to stop, OK to delete.', 'wpspcalendar'))) ?>;
                
                wpsp.concurrency_error = <?php echo($this->wpsp_json_encode(__('Looks like someone else already moved this post.', 'wpspcalendar'))) ?>;
                wpsp.permission_error = <?php echo($this->wpsp_json_encode(__('You do not have permission to edit posts.', 'wpspcalendar'))) ?>;
                wpsp.checksum_error = <?php echo($this->wpsp_json_encode(__('Invalid checksum for post. This is commonly a cross-site scripting error.', 'wpspcalendar'))) ?>;
                wpsp.general_error = <?php echo($this->wpsp_json_encode(__('There was an error contacting your blog.', 'wpspcalendar'))) ?>;
                
                wpsp.str_screenoptions = <?php echo($this->wpsp_json_encode(__('Screen Options', 'wpspcalendar'))) ?>;
                wpsp.str_optionscolors = <?php echo($this->wpsp_json_encode(__('Colors', 'wpspcalendar'))) ?>;
                wpsp.str_optionsdraftcolor = <?php echo($this->wpsp_json_encode(__('Drafts: ', 'wpspcalendar'))) ?>;
                wpsp.str_apply = <?php echo($this->wpsp_json_encode(__('Apply', 'wpspcalendar'))) ?>;
                wpsp.str_show_title = <?php echo($this->wpsp_json_encode(__('Show on screen', 'wpspcalendar'))) ?>;
                wpsp.str_opt_weeks = <?php echo($this->wpsp_json_encode(__(' weeks at a time', 'wpspcalendar'))) ?>;
                wpsp.str_show_opts = <?php echo($this->wpsp_json_encode(__('Show in Calendar Cell', 'wpspcalendar'))) ?>;
                wpsp.str_opt_author = <?php echo($this->wpsp_json_encode(__('Author', 'wpspcalendar'))) ?>;
                wpsp.str_opt_status = <?php echo($this->wpsp_json_encode(__('Status', 'wpspcalendar'))) ?>;
                wpsp.str_opt_time = <?php echo($this->wpsp_json_encode(__('Time of day', 'wpspcalendar'))) ?>;
                wpsp.str_fatal_error = <?php echo($this->wpsp_json_encode(__('An error occurred while loading the calendar: ', 'wpspcalendar'))) ?>;
                wpsp.str_fatal_parse_error = <?php echo($this->wpsp_json_encode(__('<br /><br />The calendar was not able to parse the data your blog returned about the posts.  This error is most likely caused by a conflict with another plugin on your blog.  The actual parse error was:<br/><br/> ', 'wpspcalendar'))) ?>;
                
                wpsp.str_weekserror = <?php echo($this->wpsp_json_encode(__('The calendar can only show between 1 and 8 weeks at a time.', 'wpspcalendar'))) ?>;
                wpsp.str_weekstt = <?php echo($this->wpsp_json_encode(__('Select the number of weeks for the calendar to show.', 'wpspcalendar'))) ?>;

                wpsp.str_showdrafts = <?php echo($this->wpsp_json_encode(__('Show Unscheduled Drafts'))) ?>;
                wpsp.str_hidedrafts = <?php echo($this->wpsp_json_encode(__('Hide Unscheduled Drafts'))) ?>;
    
                wpsp.str_feedbackmsg = <?php echo($this->wpsp_json_encode(__('<div id="feedbacksection">' . 
                 '<h2>Help us Make the wpsp Better</h2>' .
                 'We are always trying to improve the wpsp and you can help. May we collect some data about your blog and browser settings to help us improve this plugin?  We\'ll only do it once and your blog will show up on our <a target="_blank" href="https://wordpress.org/plugins/wp-scheduled-posts/">wpsp Statistics page</a>.<br /><br />' . 
                 '<button class="button-secondary" onclick="wpsp.doFeedback();">Collect Data</button> ' . 
                 '<a href="#" id="nofeedbacklink" onclick="wpsp.noFeedback(); return false;">No thank you</a></div>', 'wpspcalendar'))) ?>;
    
                wpsp.str_feedbackdone = <?php echo($this->wpsp_json_encode(__('<h2>We\'re done</h2>We\'ve finished collecting data.  Thank you for helping us make the calendar better.', 'wpspcalendar'))) ?>;
            });
        </script>
        
        <?php
        /*
         * There are a few images we want to reference where we need the full path to the image
         * since we don't want to make assumptions about the plugin file structure.  We need to 
         * set those here since we need PHP to get the full path.  
         */
        ?>
    
        <style type="text/css">
            .loadingclass > .postlink, .loadingclass:hover > .postlink, .tiploading {
                background-image: url('<?php echo(admin_url("images/loading.gif", __FILE__ )); ?>');
            }
    
            #loading {
                background-image: url('<?php echo(admin_url("images/loading.gif", __FILE__ )); ?>');
            }

    
        </style>
        
        <?php
            echo '<script type="text/javascript">';
                $this->wpspscriptFile(dirname( __FILE__ ) . "/wpspcalendar.min.js");
            echo '</script>';
        ?>
        
        <div class="wrap wpsp-dashboard-body">
            <div class="icon32" id="icon-edit"><br/></div>
            <h2 id="wpsp_title_main"><?php echo sprintf( __('%1$s Calendar', 'wpspcalendar'), $this->wpsp_get_posttype_multiplename() ) ?></h2>
            
            <div class="wpsp-calendar-wrap">
                <div id="loadingcont">
                    <div id="loading"> </div>
                </div>
                
                <div id="topbar" class="tablenav clearfix">
                    <div id="topleft" class="tablenav-pages alignleft">
                        <h3>
                            <a href="#" title="<?php echo(__('Jump back', 'wpspcalendar')) ?>" class="prev page-numbers" id="prevmonth">&lsaquo;</a>
                            <span id="currentRange"></span>
                            <a href="#" title="<?php echo(__('Skip ahead', 'wpspcalendar')) ?>" class="next page-numbers" id="nextmonth">&rsaquo;</a>
                            <a class="next page-numbers" title="<?php echo(__('Scroll the calendar and make the last post visible', 'wpspcalendar')) ?>" id="moveToLast">&raquo;</a>

                            <a class="next page-numbers" title="<?php echo(__('Scroll the calendar and make the today visible', 'wpspcalendar')) ?>" id="moveToToday"><?php echo(__('Show Today', 'wpspcalendar')) ?></a>
                            
                            
                        </h3>
                    </div>

                    <div id="topright" class="tablenav-pages alignright">
                        <a class="next page-numbers" title="<?php echo(__('Show unscheduled posts', 'wpspcalendar')) ?>" id="showdraftsdrawer"><?php echo(__('Show Unscheduled Drafts', 'wpspcalendar')) ?></a>
                    </div>
                </div>
                
                <div id="draftsdrawer_cont">
                    <div id="draftsdrawer">
                        <div class="draftsdrawerheadcont" title="<?php echo(__('Unscheduled draft posts', 'wpspcalendar')) ?>"><div class="dayhead"><?php echo(__('Unscheduled', 'wpspcalendar')) ?></div></div>
                        <div class="day" id="00000000">
                            <div id="draftsdrawer_loading"></div>
                            <div id="unscheduled" class="dayobj"></div>
                        </div>
                    </div>
                </div>
                
                <div id="cal_cont">
                    <div id="scrollable_wpsp" class="scrollable_wpsp vertical">
                        <div id="cal"></div>
                    </div>
                </div>
            </div>
            <?php $this->wpsp_edit_popup(); ?>
            
        </div>
    <?php
    }

    
    /*
     * Generate the DOM elements for the quick edit popup from
     * within the calendar.
     */
    function wpsp_edit_popup() {
    
    ?>
        <div id="wpsp_quickedit" style="display:none;">
            <div class="wpsp-quickedit-inner">
                <div id="tooltiphead">
                  <h3 id="tooltiptitle"><?php _e('Edit Post', 'wpspcalendar') ?></h3>
                  <a href="#" id="tipclose" onclick="wpsp.hideForm(); return false;" title="close"> <span class="dashicons dashicons-no-alt"></span></a>
                </div>
        
                <div class="wpsp_quickedit inline-edit-row">
        
                    <fieldset>
                        <label>
                            <span class="title"><?php _e('Title', 'wpspcalendar') ?></span>
                            <span class="input-text-wrap"><input type="text" class="ptitle" id="wpsp-title-new-field" name="title" /></span>
                        </label>
        
                        <label>
                            <span class="title"><?php _e('Content', 'wpspcalendar') ?></span>
                            <span class="input-text-wrap"><textarea cols="15" rows="7" id="content" name="content"></textarea></span>
                        </label>
        
                        <div id="timeEditControls">
                            <label>
                                <span class="title"><?php _e('Time', 'wpspcalendar') ?></span>
                                <span class="input-text-wrap"><input type="text" class="ptitle" id="wpsp-time" name="time" value="" size="8" maxlength="8" autocomplete="off" /></span>
                            </label>
                                
                            <label>
                                <span class="title"><?php _e('Status', 'wpspcalendar') ?></span>
                                <span class="input-text-wrap">
                                    <select name="status" id="wpsp-status">
                                        <option value="draft"><?php _e('Draft', 'wpspcalendar') ?></option>
                                        <option value="pending"><?php _e('Pending Review', 'wpspcalendar') ?></option>
                                        <?php if ( current_user_can('publish_posts') ) {?>
                                            <option id="futureoption" value="future"><?php _e('Scheduled', 'wpspcalendar') ?></option>
                                        <?php } ?>
                                    </select>
                                </span>
                            </label>
                        </div>
        
                        </fieldset>
        
                        <p class="submit inline-edit-save" id="edit-slug-buttons">
                            <a class="button-primary disabled" id="newPostScheduleButton" href="#"><?php _e('Schedule', 'wpspcalendar') ?></a>
                            <a href="#" onclick="wpsp.hideForm(); return false;" class="button-secondary cancel"><?php _e('Cancel', 'wpspcalendar') ?></a>
                        </p>
        
                        <input type="hidden" id="wpsp-date" name="date" value="" />
                        <input type="hidden" id="wpsp-id" name="id" value="" />
        
                </div><?php // end .tooltip ?>
            </div>
        </div><?php // end #tooltip 
    }
    
    /*
     * When we get a set of posts to populate the calendar we don't want
     * to get all of the posts.  This filter allows us to specify the dates
     * we want. We also exclude posts that have not been set to a specific date.
     */
    function wpsp_filter_where($where = '') {
        global $wpspcalendar_startDate, $wpspcalendar_endDate;
        if ($wpspcalendar_startDate == '00000000') {
            $where .= " AND post_date_gmt LIKE '0000%'";
        } else {
            /*
             * The start date and end date come from the client and we want to make
             * sure there's no SQL injection attack here.  We know these values must
             * be dates in a format like 2013-02-03.  Date parsing is complex and PHP
             * dates allow a lot of different formats.  The simplest way to make sure
             * this isn't a SQL injection attack is to remove the dashes and check if
             * the result is numeric.  If it is then this can't be a SQL injection attack.
             */
             if (!is_numeric(str_replace("-", "", $wpspcalendar_startDate)) || !is_numeric(str_replace("-", "", $wpspcalendar_endDate))) {
                die("The specified start date and end date for the posts query must be numeric.");
             }
             
            $where .= " AND post_date >= '" . $wpspcalendar_startDate . "' AND post_date < '" . $wpspcalendar_endDate . "' AND post_date_gmt NOT LIKE '0000%'";
        }
        return $where;
    }
    
    /*
     * This function adds all of the JavaScript files we need.
     *
     */
    function wpsp_scripts() {
        /*
         * To get proper localization for dates we need to include the correct JavaScript file for the current
         * locale.  We can do this based on the locale in the localized bundle to make sure the date locale matches
         * the locale for the other strings.
         */
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');

        wp_enqueue_script("wpsp-date", plugins_url("lib/languages/date-".__('en-US', 'wpspcalendar').".js", __FILE__ ));
        wp_enqueue_script("wpsp-lib", plugins_url("lib/wpspcalendarclass.min.js", __FILE__ ), array( 'jquery' ));
        
        return;
    }
    
    /*
     * This is an AJAX call that gets the posts between the from date 
     * and the to date.  
     */
    function wpsp_scheduled_posts() {
        header("Content-Type: application/json");
        $this->wpsp_addNoCacheHeaders();
        if (!$this->wpsp_checknonce()) {
            die();
        }
        
        global $wpspcalendar_startDate, $wpspcalendar_endDate;
        
        $wpspcalendar_startDate = isset($_GET['from']) ? $_GET['from'] : null;
        $wpspcalendar_endDate = isset($_GET['to']) ? $_GET['to'] : null;
        global $post;
        $args = array(
            'posts_per_page' => -1,
            'post_status' => "publish&future&draft",
            'post_parent' => null // any parent
        );

        /* 
         * If we're in the specific post type case we need to add
         * the post type to our query.
         */
        $post_type = isset($_GET['post_type'])?$_GET['post_type']:null;
        if ($post_type) {
            $args['post_type'] = $post_type;
        }

        /* 
         * If we're getting the list of posts for the drafts drawer we
         * want to sort them by the post title.
         */
        if ($wpspcalendar_startDate == '00000000') {
            $args['orderby'] = 'title';
        }

        /* 
         * We add a WHERE clause to filter by calendar date and/or by whether
         * or not the posts have been scheduled to a specific date:
         * WHERE `post_date_gmt` = '0000-00-00 00:00:00'
         */
        add_filter( 'posts_where', array(&$this, 'wpsp_filter_where' ));
        $myposts = query_posts($args);
        remove_filter( 'posts_where', array(&$this, 'wpsp_filter_where' ));

        ?>[
        <?php
        $size = sizeof($myposts);
        
        for($i = 0; $i < $size; $i++) {    
            $post = $myposts[$i];
            $this->wpsp_postJSON($post, $i < $size - 1);
        }
        
        ?> ]
        <?php
        
        die();
    }
    
    /*
     * This filter specifies a special WHERE clause so we just get the posts we're 
     * interested in for the last post.
     */
    function wpsp_lastpost_filter_where($where = '') {
        $where .= " AND (`post_status` = 'draft' OR `post_status` = 'publish' OR `post_status` = 'future')";
        return $where;
    }
    
    /*
     * Get information about the last post (the one furthest in the future) and make
     * that information available to the JavaScript code so it can make the last post
     * button work.
     */
    function wpsp_getLastPost() {
        $args = array(
            'posts_per_page' => 1,
            'post_parent' => null,
            'order' => 'DESC'
        );
        
        add_filter( 'posts_where', array(&$this, 'wpsp_lastpost_filter_where' ));
        $myposts = query_posts($args);
        remove_filter( 'posts_where', array(&$this, 'wpsp_lastpost_filter_where' ));
        
        if (sizeof($myposts) > 0) {
            $post = $myposts[0];
            setup_postdata($post);
            ?>
            wpsp.lastPostDate = '<?php echo(date('dmY',strtotime($post->post_date))); ?>';
            wpsp.lastPostId = '<?php echo($post->ID); ?>';
            <?php
        } else {
            ?>
            wpsp.lastPostDate = '-1';
            wpsp.lastPostId = '-1';
            <?php
        }
    }
    
    /*
     * This is for an AJAX call that returns a post with the specified ID
     */
    function wpsp_scheduled_getpost() {
        
        header("Content-Type: application/json");
        $this->wpsp_addNoCacheHeaders();
        
        // If nonce fails, return
        if (!$this->wpsp_checknonce()) {
            die();
        }
        
        $post_id = isset($_GET['postid'])?intval($_GET['postid']):-1;
        
        // If a proper post_id wasn't passed, return
        if(!$post_id) die();
        
        $args = array(
            'post__in' => array($post_id)
        );
        
        /* 
         * If we're in the specific post type case we need to add
         * the post type to our query.
         */
        $post_type = isset($_GET['post_type'])?$_GET['post_type']:null;
        if ($post_type) {
            $args['post_type'] = $post_type;
        }
        
        $post = query_posts($args);
        
        // get_post and setup_postdata don't get along, so we're doing a mini-loop
        if(have_posts()) :
            while(have_posts()) : the_post();
                ?>
                {
                "post" :
                    <?php
                    $this->wpsp_postJSON($post[0], false, true);
                    ?>
                }
                <?php
            endwhile;
			// Reset post data
			wp_reset_postdata();
        endif;
        die();
    }
    
    /*
     * Wrap php's json_encode() for a WP-specific apostrophe bug
     */
    function wpsp_json_encode($string) {
        /*
         * WordPress escapes apostrophe's when they show up in post titles as &#039;
         * This is the HTML ASCII code for a straight apostrophe.  This works well
         * with Firefox, but IE complains with a very unhelpful error message.  We
         * can replace them with a right curly apostrophe since that works in IE
         * and Firefox. It is also a little nicer typographically.  
         *
         */
        return json_encode(str_replace("&#039;", "&#146;", $string));
    }
    
    /* 
     * This helper functions gets the plural name of the post
     * type specified by the post_type parameter.
     */
    function wpsp_get_posttype_multiplename() {
    
        $post_type = isset($_GET['post_type'])?$_GET['post_type']:null;
        if (!$post_type) {
            return __('Posts ', 'wpspcalendar');
        }
    
        $postTypeObj = get_post_type_object($post_type);
        return $postTypeObj->labels->name;
    }
    
    /* 
     * This helper functions gets the singular name of the post
     * type specified by the post_type parameter.
     */
    
    function wpsp_get_posttype_singlename() {
    
        $post_type = isset($_GET['post_type'])?$_GET['post_type']:null;
        if (!$post_type) {
            return __('Post ', 'wpspcalendar');
        }
    
        $postTypeObj = get_post_type_object($post_type);
        return $postTypeObj->labels->singular_name;
    }
    
    /*
     * This function sets up the post data and prints out the values we
     * care about in a JSON data structure.  This prints out just the
     * value part. If $fullPost is set to true, post_content is also returned.
     */
    function wpsp_postJSON($post, $addComma = true, $fullPost = false) {
        $timeFormat = get_option("time_format");
        if ($timeFormat == "g:i a") {
            $timeFormat = "ga";
        } else if ($timeFormat == "g:i A") {
            $timeFormat = "gA";
        } else if ($timeFormat == "H:i") {
            $timeFormat = "H";
        }
        
        setup_postdata($post);
        
        if (get_post_status() == 'auto-draft' || get_post_status() == 'inherit' || get_post_status() == 'trash' ) {
            /*
             * WordPress 3 added a new post status of auto-draft so
             * we want to hide them from the calendar. 
             * We also want to hide posts with type 'inherit'
             */
            return;
        }
        
        /* 
         * We want to return the type of each post as part of the
         * JSON data about that post.  Right now this will always
         * match the post_type parameter for the calendar, but in
         * the future we might support a mixed post type calendar
         * and this extra data will become useful.  Right now we
         * are using this data for the title on the quick edit form.
         */
        if( $this->supports_custom_types ) {
            $postTypeObj = get_post_type_object(get_post_type( $post ));
            $postTypeTitle = $postTypeObj->labels->singular_name;
        } else {
            $postTypeTitle = 'post';
        }

        $post_date_gmt = date('dmY',strtotime($post->post_date_gmt));
        if ($post_date_gmt == '01011970') {
            $post_date_gmt = '00000000';
        }
        
        /*
         * The date function in PHP isn't consistent in the way it handles
         * formatting dates that are all zeros.  In that case we can manually
         * format the all zeros date so it shows up properly.
         */
        if ($post->post_date_gmt == '0000-00-00 00:00:00') {
            $post_date_gmt = '00000000';
        }
        
        $slugs = '';
        foreach(get_the_category() as $category) {
            $slugs .= $category->slug . ' ';
        }
        
        ?>
            {
                "date" : "<?php the_time('d') ?><?php the_time('m') ?><?php the_time('Y') ?>", 
                "date_gmt" : "<?php echo $post_date_gmt; ?>",
                "time" : "<?php echo trim(get_the_time()) ?>", 
                "formattedtime" : "<?php $this->wpsp_json_encode(the_time($timeFormat)) ?>", 
                "sticky" : "<?php echo is_sticky($post->ID) ?>",
                "url" : "<?php $this->wpsp_json_encode(the_permalink()) ?>", 
                "status" : "<?php echo get_post_status() ?>",
                "orig_status" : "<?php echo get_post_status() ?>",
                "title" : <?php echo $this->wpsp_json_encode(isset( $post->post_title ) ? $post->post_title : '') ?>,
                "author" : <?php echo $this->wpsp_json_encode(get_the_author()) ?>,
                "type" : "<?php echo get_post_type( $post ) ?>",
                "typeTitle" : "<?php echo $postTypeTitle ?>",
                "slugs" : <?php echo $this->wpsp_json_encode($slugs) ?>,
    
                <?php if ( current_user_can('edit_post', $post->ID) ) {?>
                "editlink" : "<?php echo get_edit_post_link($post->ID) ?>",
                <?php } ?>
    
                <?php if ( current_user_can('delete_post', $post->ID) ) {?>
                "dellink" : "javascript:wpsp.deletePost(<?php echo $post->ID ?>)",
                <?php } ?>
    
                "permalink" : "<?php echo get_permalink($post->ID) ?>",
                "id" : "<?php the_ID(); ?>"
                
                <?php if($fullPost) : ?>
                , "content" : <?php echo $this->wpsp_json_encode($post->post_content) ?>
                
                <?php endif; ?>
            }
        <?php
        if ($addComma) {
            ?>,<?php
        }
    }
    
    /*
     * This is a helper AJAX function to delete a post. It gets called
     * when a user clicks the delete button, and allows the user to 
     * retain their position within the calendar without a page refresh.
     * It is not called unless the user has permission to delete the post.
     */
    function wpsp_scheduled_deletepost() {
        if (!$this->wpsp_checknonce()) {
            die();
        }
    
        header("Content-Type: application/json");
        $this->wpsp_addNoCacheHeaders();
        
        $wpspcalendar_postid = isset($_GET['postid'])?$_GET['postid']:null;
        
        if (!current_user_can('delete_post', $wpspcalendar_postid)) {
            die("You don't have permission to delete this post");
        }
        
        $post = get_post($wpspcalendar_postid, ARRAY_A);
        $title = $post['post_title'];
        $date = date('dmY', strtotime($post['post_date'])); // [TODO] : is there a better way to generate the date string ... ??
        $date_gmt = date('dmY',strtotime($post['post_date_gmt']));
        if ($date_gmt == '01011970') {
            $date_gmt = '00000000';
        }
        
        $force = !EMPTY_TRASH_DAYS;                    // wordpress 2.9 thing. deleted post hangs around (ie in a recycle bin) after deleted for this # of days
        if ( isset($post->post_type) && ($post->post_type == 'attachment' )) {
            $force = ( $force || !MEDIA_TRASH );
            if ( ! wp_delete_attachment($wpspcalendar_postid, $force) )
                wp_die( __('Error in deleting...') );
        } else {
            if ( !wp_delete_post($wpspcalendar_postid, $force) )
                wp_die( __('Error in deleting...') );
        }
    
    // return the following info so that jQuery can then remove post from wpsp display :
    ?>
    {
        "post" :
        {
            "date" : "<?php echo $date ?>", 
            "title" : "<?php echo $title ?>",
            "id" : "<?php echo $wpspcalendar_postid ?>",
            "date_gmt" : "<?php echo $date_gmt; ?>"
        }
    }
    <?php
    
        die();    
    }
    
    /*
     * This is a helper AJAX function to change the title of a post.  It
     * gets called from the save button in the tooltip when you change a
     * post title in a calendar.
     */
    function wpsp_scheduled_changetitle() {
        if (!$this->wpsp_checknonce()) {
            die();
        }
    
        header("Content-Type: application/json");
        $this->wpsp_addNoCacheHeaders();
        
        $wpspcalendar_postid = isset($_GET['postid'])?$_GET['postid']:null;
        $wpspcalendar_newTitle = isset($_GET['title'])?$_GET['title']:null;
        
        $post = get_post($wpspcalendar_postid, ARRAY_A);
        setup_postdata($post);
        
        $post['post_title'] = wp_strip_all_tags($wpspcalendar_newTitle);
        
        /*
         * Now we finally update the post into the database
         */
        wp_update_post( $post );
        
        /*
         * We finish by returning the latest data for the post in the JSON
         */
        global $post;
        $args = array(
            'posts_id' => $wpspcalendar_postid,
        );
        
        $post = get_post($wpspcalendar_postid);
        
        ?>{
            "post" :
        <?php
        
            $this->wpsp_postJSON($post);
        
        ?>
        }
        <?php
        
        
        die();
    }
    

    /*
     * This is a helper function to create a new draft post on a specified date
     * or update an existing post.
     */
    function wpsp_scheduled_savepost() {
        
        if (!$this->wpsp_checknonce()) {
            die();
        }
        
        // Most blogs have warnings turned off by default, but if they're
        // turned on the warnings can cause errors in the JSON data when
        // we change the post status so we set the warning level to hide
        // warnings and then reset it at the end of this function.
        $my_error_level = error_reporting();
        error_reporting(E_ERROR);
    
        header("Content-Type: application/json");
        $this->wpsp_addNoCacheHeaders();
        
        $wpspcalendar_date = isset($_POST["date"])?$_POST["date"]:null;
        $wpspcalendar_date_gmt = isset($_POST["date_gmt"])?$_POST["date_gmt"]:get_gmt_from_date($wpspcalendar_date);
        
        $my_post = array();
        
        // If the post id is not specified, we're creating a new post
        if($_POST['id'] && intval($_POST['id']) > 0) {
            $my_post['ID'] = intval($_POST['id']);
        } else {
            // We have a new post
            //$my_post['ID'] = 0; // and the post ID to 0
            
            // Set the status to draft unless the user otherwise specifies
            if ($_POST['status']) {
                $my_post['post_status'] = $_POST['status'];
            } else {
                $my_post['post_status'] = 'draft';
            }
        }
        
        $my_post['post_title'] = isset($_POST["title"])?wp_strip_all_tags($_POST["title"]):null;
        $my_post['post_content'] = isset($_POST["content"])?$_POST["content"]:null;
        
        if ($wpspcalendar_date_gmt != '0000-00-00 00:00:00' || $my_post['ID'] > 0) {
            /*
             * We don't want to set a date if this a new post in the drafts
             * drawer since WordPress 3.5 will reject new posts with a 0000 
             * GMT date.
             */
            $my_post['post_date'] = $wpspcalendar_date;
            $my_post['post_date_gmt'] = $wpspcalendar_date_gmt;
            $my_post['post_modified'] = $wpspcalendar_date;
            $my_post['post_modified_gmt'] = $wpspcalendar_date_gmt;
        }
        
        $my_post['post_status'] = $_POST['status'];
        
        /* 
         * When we create a new post we need to specify the post type
         * passed in from the JavaScript.
         */
        $post_type = isset($_POST["post_type"])?$_POST["post_type"]:null;
        if ($post_type) {
            $my_post['post_type'] = $post_type;
        }

        // If we are updating a post
        if($_POST['id']) {
            if ($_POST['status'] != $_POST['orig_status']) {
                wp_transition_post_status($_POST['status'], $_POST['orig_status'], $my_post);
                $my_post['post_status'] = $_POST['status'];
            }
            $my_post_id = wp_update_post($my_post);
        } else {
            // We have a new post, insert the post into the database
            $my_post_id = wp_insert_post($my_post, true);
        }
        
        // TODO: throw error if update/insert or getsinglepost fails
        /*
         * We finish by returning the latest data for the post in the JSON
         */
        $args = array(
            'post__in' => array($my_post_id)
        );
        
        if ($post_type) {
            $args['post_type'] = $post_type;
        }
        $post = query_posts($args);
        
        // get_post and setup_postdata don't get along, so we're doing a mini-loop
        if(have_posts()) :
            while(have_posts()) : the_post();
                ?>
                {
                "post" :
                    <?php
                    $this->wpsp_postJSON($post[0], false);
                    ?>
                }
                <?php
            endwhile;
        endif;
        
        error_reporting($my_error_level);
        
        die();
    }
    
    /*
     * This function checks the nonce for the URL.  It returns
     * true if the nonce checks out and outputs a JSON error
     * and returns false otherwise.
     */
    function wpsp_checknonce() {
        header("Content-Type: application/json");
        $this->wpsp_addNoCacheHeaders();
        
        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'edit-calendar')) {
           /*
             * This is just a sanity check to make sure
             * this isn't a CSRF attack.  Most of the time this
             * will never be run because you can't see the calendar unless
             * you are at least an editor
             */
            ?>
            {
                "error": <?php echo(WPSP_NONCE_ERROR); ?>
            }
            <?php
            return false;
        }
        return true;
    }
    
    /*
     * This function changes the date on a post.  It does optimistic 
     * concurrency checking by comparing the original post date from
     * the browser with the one from the database.  If they don't match
     * then it returns an error code and the updated post data.
     *
     * If the call is successful then it returns the updated post data.
     */
    function wpsp_scheduled_changedate() {
        if (!$this->wpsp_checknonce()) {
            die();
        }
        header("Content-Type: application/json");
        $this->wpsp_addNoCacheHeaders();
        
        $wpspcalendar_postid = isset($_GET['postid'])?$_GET['postid']:null;
        $wpspcalendar_newDate = isset($_GET['newdate'])?$_GET['newdate']:null;
        $wpspcalendar_oldDate = isset($_GET['olddate'])?$_GET['olddate']:null;
        $wpspcalendar_postStatus = isset($_GET['postStatus'])?$_GET['postStatus']:null;
        $move_to_drawer = $wpspcalendar_newDate == '0000-00-00';
        $move_from_drawer = $wpspcalendar_oldDate == '00000000';

        global $post;
        $args = array(
            'posts_id' => $wpspcalendar_postid,
        );
        $post = get_post($wpspcalendar_postid);
        setup_postdata($post);

        /*
         * Posts in WordPress have more than one date.  There is the GMT date,
         * the date in the local time zone, the modified date in GMT and the
         * modified date in the local time zone.  We update all of them.
         */
        if ( $move_from_drawer ) {
            /* 
             * Set the date to 'unscheduled' [ie. 0]. We use this date 
             * further down in the concurrency check, and this will make the dates
             * technically off by 10 hours, but it's still the same day. We only do 
             * this for posts that were created as drafts.  Works for now, but
             * we would have to revamp this if we use an actual timestamp check.
             */
            $post->post_date = '0000-00-00 ' . date('H:i:s', strtotime($post->post_date));
        } else if ( $move_to_drawer ) {
            // echo ( "\r\npost->post_date_gmt=".$post->post_date_gmt);
            $post->post_date_gmt = $post->post_date;
        } else {
            // set the scheduled time as our original time
            $post->post_date_gmt = $post->post_date;
        }

        /*
         * Error-checking:
         */
        $error = false;
        if (!current_user_can('edit_post', $wpspcalendar_postid)) {
            /*
             * This is just a sanity check to make sure that the current
             * user has permission to edit posts.  Most of the time this
             * will never be run because you can't see the calendar unless
             * you are at least an editor.
             */
            $error = WPSP_PERMISSION_ERROR;
        } else if ( date('Y-m-d', strtotime($post->post_date)) != date('Y-m-d', strtotime($wpspcalendar_oldDate)) ) {
            /*
             * We are doing optimistic concurrency checking on the dates.  If
             * the user tries to move a post we want to make sure nobody else
             * has moved that post since the page was last updated.  If the 
             * old date in the database doesn't match the old date from the
             * browser then we return an error to the browser along with the
             * updated post data.
             */
            $error = WPSP_CONCURRENCY_ERROR;
        }

        if ( $error ) {
            ?>
            {
                "error": <?php echo $error; ?>,
                "post" :
            <?php
                $this->wpsp_postJSON($post, false, true);
            ?> }
            
            <?php
            die();
        }


        /*
         * No errors, so let's go create our new post parameters to update
         */
        
        $updated_post = array();
        $updated_post['ID'] = $wpspcalendar_postid;

        if ( !$move_to_drawer ) {
            $updated_post['post_date'] = $wpspcalendar_newDate . substr($post->post_date, strlen($wpspcalendar_newDate));
        }

        /*
         * When a user creates a draft and never sets a date or publishes it 
         * then the GMT date will have a timestamp of 00:00:00 to indicate 
         * that the date hasn't been set.  In that case we need to specify
         * an edit date or the wp_update_post function will strip our new
         * date out and leave the post as publish immediately.
         */
        $needsEditDate = preg_match( '/^0000/', $post->post_date_gmt );

        if ( $needsEditDate ) {
            // echo "\r\nneeds edit date\r\n";
            $updated_post['edit_date'] = $wpspcalendar_newDate . substr($post->post_date, strlen($wpspcalendar_newDate));
        }

        if ( $move_to_drawer ) {
            $updated_post['post_date_gmt'] = "0000-00-00 00:00:00";
            $updated_post['edit_date'] = $post->post_date;
        } else if ( $move_from_drawer ) {
            $updated_post['post_date_gmt'] = get_gmt_from_date($post->post_date);
            $updated_post['post_modified_gmt'] = get_gmt_from_date($post->post_date);
        }

        /*
         * We need to make sure to use the GMT formatting for the date.
         */
        if ( !$move_to_drawer ) {
            $updated_post['post_date_gmt'] = get_gmt_from_date($updated_post['post_date']);
            $updated_post['post_modified'] = $wpspcalendar_newDate . substr($post->post_modified, strlen($wpspcalendar_newDate));
            $updated_post['post_modified_gmt'] = get_gmt_from_date($updated_post['post_date']);
        }
        
        if ($wpspcalendar_postStatus != $post->post_status) {
            /*
             * We only want to update the post status if it has changed.
             * If the post status has changed that takes a few more steps
             */
            wp_transition_post_status($wpspcalendar_postStatus, $post->post_status, $post);
            $updated_post['post_status'] = $wpspcalendar_postStatus;
            
            // Update counts for the post's terms.
            foreach ( (array) get_object_taxonomies('post') as $taxonomy ) {
                $tt_ids = wp_get_object_terms($post_id, $taxonomy, 'fields=tt_ids');
                wp_update_term_count($tt_ids, $taxonomy);
            }
            
            do_action('edit_post', $wpspcalendar_postid, $post);
            do_action('save_post', $wpspcalendar_postid, $post);
            do_action('wp_insert_post', $wpspcalendar_postid, $post);
        }
        
        /*
         * Now we finally update the post into the database
         */
        wp_update_post( $updated_post );
        
        /*
         * We finish by returning the latest data for the post in the JSON
         */
        global $post;
        $args = array(
            'posts_id' => $wpspcalendar_postid,
        );
        
        $post = get_post($wpspcalendar_postid);
        ?>{
            "post" :
            
        <?php
            $this->wpsp_postJSON($post, false, true);
        ?>}
        <?php
        
        die();
    }
    
    /*
     * This function saves the preferences
     */
    function wpsp_scheduled_saveoptions() {
        if (!$this->wpsp_checknonce()) {
            die();
        }
    
        header("Content-Type: application/json");
        $this->wpsp_addNoCacheHeaders();
        
        /*
         * The number of weeks preference
         */
        $wpspcalendar_weeks = isset($_GET['weeks'])?$_GET['weeks']:null;
        if ($wpspcalendar_weeks != null) {
            add_option("wpsp_select_weeks", $wpspcalendar_weeks, "", "yes");
            update_option("wpsp_select_weeks", $wpspcalendar_weeks);
        }
        
        /*
         * The show author preference
         */
        $wpspcalendar_author = isset($_GET['author-hide'])?$_GET['author-hide']:null;
        if ($wpspcalendar_author != null) {
            add_option("wpsp_author_pref", $wpspcalendar_author, "", "yes");
            update_option("wpsp_author_pref", $wpspcalendar_author);
        }
        
        /*
         * The show status preference
         */
        $wpspcalendar_status = isset($_GET['status-hide'])?$_GET['status-hide']:null;
        if ($wpspcalendar_status != null) {
            add_option("wpsp_status_pref", $wpspcalendar_status, "", "yes");
            update_option("wpsp_status_pref", $wpspcalendar_status);
        }
        
        /*
         * The show time preference
         */
        $wpspcalendar_time = isset($_GET['time-hide'])?$_GET['time-hide']:null;
        if ($wpspcalendar_time != null) {
            add_option("wpsp_time_pref", $wpspcalendar_time, "", "yes");
            update_option("wpsp_time_pref", $wpspcalendar_time);
        }
    
        /*
         * The wpsp feedback preference
         */
        $wpspcalendar_feedback = isset($_GET['dofeedback'])?$_GET['dofeedback']:null;
        if ($wpspcalendar_feedback != null) {
            add_option("wpsp_got_feedback", $wpspcalendar_feedback, "", "yes");
            update_option("wpsp_got_feedback", $wpspcalendar_feedback);
        }
        
        /*
         * We finish by returning the latest data for the post in the JSON
         */
        ?>{
            "update" : "success"
        }
        <?php
        
        die();
    }
    
    /*
     * Add the no cache headers to make sure that our responses aren't
     * cached by the browser.
     */
    function wpsp_addNoCacheHeaders() {
        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
    }

}

?>