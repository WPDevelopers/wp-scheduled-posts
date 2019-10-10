<div id="wpwrap">
    <div class="wpscp-calendar-wrap">
        <?php 
            //get all options
            $wpscp_all_options  = get_option('wpscp_options');
            $allow_post_types =  ($wpscp_all_options['allow_post_types'] == '' ? array('post') : $wpscp_all_options['allow_post_types']);
        ?>

    
        <!-- modal -->
        <div id="wpscp_quickedit" class="modal">
            <div class="wpsp-quickedit-inner">
                <div id="tooltiphead">
                    <h3 id="tooltiptitle">New Post</h3>
                    <a href="#" id="tipclose" title="close"> <span class="dashicons dashicons-no-alt"></span></a>
                </div>

                <div class="wpsp_quickedit inline-edit-row">
                    <form action="#" method="post">
                        <fieldset>
                            <label>
                                <span class="title">Title</span>
                                <span class="input-text-wrap"><input type="text" class="ptitle" id="title" name="title"></span>
                            </label>

                            <label>
                                <span class="title">Content</span>
                                <span class="input-text-wrap"><textarea cols="15" rows="7" id="content" name="content"></textarea></span>
                            </label>

                            <label>
                                <span class="title">Status</span>
                                <span class="input-text-wrap">
                                    <select name="status" id="wpsp-status" disabled="disabled">
                                        <option value="Draft">Draft</option>
                                        <option value="Scheduled">Scheduled</option>
                                    </select>
                                </span>
                            </label>

                            <div id="timeEditControls">
                                <label>
                                    <span class="title">Time</span>
                                    <span class="input-text-wrap">
                                        <input type="text" class="ptitle" id="wpsp_time" name="time" value="" size="8" maxlength="8" autocomplete="OFF">
                                    </span>
                                </label>
                            </div>

                        </fieldset>
                        <input type="hidden" id="postID" name="postID">
                        <input type="hidden" id="date" name="date">
                        <p class="submit inline-edit-save" id="edit-slug-buttons">
                            <button id="wpcNewPostScheduleButton">Submit</button>
                            <a class="button-secondary close" href="#" rel="modal:close">Close</a>
                        </p>
                    </form>
                </div>            
            </div>
        </div>

        <!-- <div class="ui-notification-bar"></div>
<div class="ui-notification-container"></div>
<div class="ui-notification-bottomleft"></div>
        <button class="custom-anim">Add with custom animation</button> -->

        <div class="full-calendar-wrapper">
            <div id='calendar-container'>
                <div id='external-events'>
                <div id='external-events-listing'>
                    <h4 class="unscheduled"><?php esc_html_e('Unscheduled', 'wpscp'); ?><span class="spinner"></span></h4>

                    <?php 
                        $query = new WP_Query(array(
                            'post_type'         => $allow_post_types,
                            'post_status'       => array('draft'),
                            'posts_per_page'    => -1
                        ));
                    ?>
                
                    <?php 
                        while($query->have_posts()) : $query->the_post();
                    ?>
                    <div class='fc-event'>
                        <div class="wpscp-event-post" data-postid="<?php print get_the_id(); ?>">
                            <div class="postlink ">
                                <span>
                                    <span class="posttime">[<?php print get_the_time( '', get_the_id() ); ?>]</span> <?php print get_the_title() . ' ' . '['.get_post_status(get_the_id()).']'; ?>
                                </span>
                            </div>
                            <div class="postactions">
                                <a href="<?php print esc_url(get_edit_post_link(get_the_id())); ?>">Edit</a>
                                <a class="wpscpquickedit" href="#" data-type="quickedit">Quick Edit</a>
                                <a class="wpscpEventDelete" href="#">Delete</a>
                                <a href="<?php print esc_url(get_the_permalink()); ?>">View</a>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                        wp_reset_postdata();
                    ?>
                </div>
                    <!-- Link to open the modal -->
                    <p><a href="#wpscp_quickedit" rel="modal:open" data-type="draft">New Draft</a></p>
                </div>
                <div id='calendar'></div>
            </div>
        </div>
    </div>
</div>