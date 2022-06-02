<div id="wpwrap">
    <div class="wpsp-dashboard-body">
        <div class="wpsp_calendar_loader">
            <img src="<?php echo esc_url(WPSP_ASSETS_URI . 'images/wpscp-logo.gif'); ?>" alt="Loader">
        </div>
        <!-- Topbar -->
        <div class="wpsp_top_bar_wrapper">
            <div class="wpsp_top_bar_logo">
                <img src="<?php echo esc_url(WPSP_ASSETS_URI . 'images/wpsp-icon.svg'); ?>" alt="">
            </div>
            <div class="wpsp_top_bar_heading">
                <h2 class="wpsp_topbar_title"><?php esc_html_e('SchedulePress (Formerly Known as WP Scheduled Posts)', 'wp-scheduled-posts'); ?></h2>
                <p class="wpsp_topbar_version_name"><?php echo esc_html__('Version ', 'wp-scheduled-posts') . WPSP_VERSION; ?></p>
            </div>
        </div>
        <!-- main content -->
        <div class="wpscp-calendar-wrap">
            <?php
            //get all options
            $post_type = (isset($_GET['post_type']) ? $_GET['post_type'] : '');
            if(isset($_GET['page']) && $_GET['page'] === 'schedulepress-post'){
                $post_type = 'post';
            }

            $allow_post_types  = \WPSP\Helper::get_settings('allow_post_types');
            $allow_post_types  = (!empty($allow_post_types) ? $allow_post_types : array('post'));

            $tax_terms  = \WPSP\Helper::get_all_tax_term($post_type ? $post_type : $allow_post_types);

            $calendar_schedule_time = \WPSP\Helper::get_settings('calendar_schedule_time');
            $calendar_schedule_time = (!empty($calendar_schedule_time) ? $calendar_schedule_time : '12:00 AM');

            $allow_categories = \WPSP\Helper::get_settings('allow_categories');
            ?>
            <!-- modal -->
            <div id="wpscp_quickedit" class="modal">
                <div class="wpsp-quickedit-inner">
                    <div>
                        <h3 class="entry-title"><?php print esc_html__('New ', 'wp-scheduled-posts') . (($post_type == null || $post_type == "") ? 'Post' : $post_type); ?></h3>
                    </div>
                    <div class="wpsp_quickedit inline-edit-row">
                        <form action="#" method="post">
                            <fieldset>
                                <div class="form-group">
                                    <input type="text" class="regular-text" id="title" name="title" placeholder="<?php esc_attr_e('Title', 'wp-scheduled-posts'); ?>">
                                </div>
                                <div class="form-group">
                                    <textarea cols="15" rows="7" id="content" name="content" placeholder="<?php esc_attr_e('Content', 'wp-scheduled-posts'); ?>"></textarea>
                                </div>
                                <div class="form-group-inline">
                                    <select name="status" id="wpsp-status" disabled="disabled">
                                        <option value="Draft"><?php esc_html_e('Draft', 'wp-scheduled-posts'); ?></option>
                                        <option value="Scheduled"><?php esc_html_e('Scheduled', 'wp-scheduled-posts'); ?></option>
                                        <option value="Publish"><?php esc_html_e('Publish', 'wp-scheduled-posts'); ?></option>
                                    </select>
                                </div>
                                <div id="timeEditControls">
                                    <input type="text" class="ptitle" id="wpsp_time" name="time" value="<?php echo esc_attr($calendar_schedule_time); ?>" size="8" maxlength="8" autocomplete="OFF" placeholder="12:00 AM">
                                </div>
                            </fieldset>
                            <input type="hidden" id="postID" name="postID">
                            <input type="hidden" id="date" name="date">
                            <p class="submit inline-edit-save" id="edit-slug-buttons">
                                <button id="wpcNewPostScheduleButton"><?php esc_html_e('Save', 'wp-scheduled-posts'); ?></button>
                                <a class="button-secondary close" href="#" rel="modal:close"><?php esc_html_e('Cancel', 'wp-scheduled-posts'); ?></a>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
            <div class="full-calendar-wrapper">
                <div id='calendar-container'>
                    <div id='external-events'>
                        <div id='external-events-listing'>
                            <h4 class="unscheduled"><?php print esc_html__('Unscheduled ', 'wp-scheduled-posts') . (($post_type == null || $post_type == "") ? 'Posts' : $post_type); ?><span class="spinner"></span></h4>
                            <?php if($post_type !== "page"):?>
                            <select id="external-events-filter" multiple="multiple" style="width: 100%">
                                <option value="all" <?php echo (in_array('all', $allow_categories) || empty($allow_categories)) ? 'selected' : '';?>>All</option>
                                <?php foreach ($tax_terms as $tax_label => $terms):?>
                                    <optgroup label="<?php echo $tax_label;?>">
                                    <?php foreach ($terms as $term_slug => $term):?>
                                        <option value="<?php echo "{$term['taxonomy']}.$term_slug";?>" data-tax="<?php echo $term['taxonomy'];?>" <?php echo in_array("{$term['taxonomy']}.$term_slug", $allow_categories) ? 'selected' : '';?>><?php echo $term['name'];?></option>
                                    <?php endforeach;?>
                                    </optgroup>
                                <?php endforeach;?>
                            </select>
                            <?php endif;?>
                            <?php
                            $query = new \WP_Query(array(
                                'post_type'         => $post_type ? $post_type : $allow_post_types,
                                'post_status'       => array('draft', 'pending'),
                                'posts_per_page'    => -1
                            ));
                            while ($query->have_posts()) : $query->the_post();
                                $taxonomies = \WPSP\Helper::get_all_post_terms();

                            ?>
                                <div class='fc-event'>
                                    <div class="wpscp-event-post" data-postid="<?php print get_the_id(); ?>" data-post-type="<?php print get_post_type(); ?>" data-terms='<?php echo json_encode($taxonomies)?>'>
                                        <div class="postlink ">
                                            <span>
                                                <span class="posttime">[<?php print get_the_time('', get_the_id()); ?>]</span> <?php print wp_trim_words(get_the_title(), 3, '...') . ' ' . '[' . get_post_status(get_the_id()) . ']'; ?>
                                            </span>
                                        </div>
                                        <div class="postactions">
                                            <div>
                                                <div class="edit">
                                                    <a href="<?php print esc_url(get_edit_post_link(get_the_id())); ?>"><i class="dashicons dashicons-edit"></i><?php esc_html_e('Edit', 'wp-scheduled-posts'); ?></a>
                                                    <a class="wpscpquickedit" href="#" data-type="quickedit"><i class="dashicons dashicons-welcome-write-blog"></i><?php esc_html_e('Quick Edit', 'wp-scheduled-posts'); ?></a>
                                                </div>
                                                <div class="deleteview">
                                                    <a class="wpscpEventDelete" href="#"><i class="dashicons dashicons-trash"></i><?php esc_html_e('Delete', 'wp-scheduled-posts'); ?></a>
                                                    <a href="<?php print esc_url(get_the_permalink()); ?>"><i class="dashicons dashicons-admin-links"></i><?php esc_html_e(' View', 'wp-scheduled-posts'); ?></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            endwhile;
                            wp_reset_postdata();
                            ?>
                        </div>
                        <!-- Link to open the modal -->
                        <p><a class="btn-draft-post-create" href="#wpscp_quickedit" rel="modal:open" data-type="draft"><?php esc_html_e('New Draft', 'wp-scheduled-posts'); ?></a></p>
                    </div>
                    <div id='calendar'></div>
                </div>
            </div>
        </div>
    </div>
</div>