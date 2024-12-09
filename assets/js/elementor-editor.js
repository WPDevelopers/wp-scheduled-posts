(function ($) {
    var wpsp_menu = $('#elementor-panel-footer-sub-menu-item-wpsp'),
        modal = $('#schedulepress-elementor-modal'),
        wpsp_quick_button = $('#elementor-panel-footer-wpsp-modal'),
        wpsp_submit_button = $('.wpsp-el-form-submit:not(.wpsp-advanced-schedule)'),
        wpsp_submit_button_text = $('span:nth-child(2)', wpsp_submit_button),
        label_schedule = wpsp_submit_button.data('label-schedule'),
        label_publish = wpsp_submit_button.data('label-publish'),
        label_update = wpsp_submit_button.data('label-update'),
        label_draft = wpsp_submit_button.data('label-draft'),
        advanced_schedule = $('.wpsp-advanced-schedule'),
        advanced_schedule_text = $('span:nth-child(2)', advanced_schedule),
        avd_label_schedule = advanced_schedule.data('label-schedule'),
        avd_label_update = advanced_schedule.data('label-update'),
        status = advanced_schedule.data('status'),
        isAdvanced = advanced_schedule.data('is-advanced'),
        immediately_btn = $('.wpsp-immediately-publish'),
        wpsp_adv_date,
        wpsp_date;
        

    var updateLabel = function(current_time, selected_time){
        if (current_time.getTime() < selected_time.getTime()) {
            wpsp_submit_button_text.text(label_schedule)
        } else {
            if( status == 'draft' ) {
                wpsp_submit_button_text.text(label_draft)
            }else{
                wpsp_submit_button_text.text(label_publish)
            }
        }
    }

    var isFuture = function(){
        var current_time  = new Date();
        var selected_time = wpsp_date.selectedDates && wpsp_date.selectedDates[0] ? wpsp_date.selectedDates[0] : current_time;
        if(selected_time.getTime() > current_time.getTime()){
            return true;
        }
        return false;
    }

    $(window).on('load', function () {
        $('.elementor-panel-footer-sub-menu-wrapper .elementor-panel-footer-sub-menu').append(wpsp_menu);
        wpsp_quick_button.insertAfter('#elementor-panel-footer-saver-preview');
        wpsp_date = flatpickr("#wpsp-schedule-datetime", {
            enableTime: true,
            dateFormat: "Y-m-d H:i:S",
            altInput: true,
            altFormat: "F j, Y h:i K",
            appendTo: window.document.querySelector('.wpsp-el-modal-date-picker'),
            onChange: function(selectedDates, dateStr, instance) {
                var current_time = new Date(),
                    selected_time = new Date(dateStr);

                updateLabel(current_time, selected_time);

                if (status === 'publish' && current_time.getTime() < selected_time.getTime()) {
                    advanced_schedule.show();
                    if(isAdvanced){
                        wpsp_submit_button.hide();
                        advanced_schedule_text.text(avd_label_update);
                    }
                    else{
                        wpsp_submit_button.show();
                        advanced_schedule_text.text(avd_label_schedule);
                    }
                } else {
                    wpsp_submit_button.show();
                    advanced_schedule.hide();
                }
            }
        });
        
        if ($('.wpsp-pro-fields.wpsp-pro-activated label input').length) {
            flatpickr(".wpsp-pro-fields.wpsp-pro-activated label input", {
                enableTime: true,
                dateFormat: "Y/m/d H:i",
                altInput: true,
                altFormat: "F j, Y h:i K",
                appendTo: window.document.querySelector('.wpsp-el-modal-date-picker'),
            });
        }

        wpsp_adv_date = flatpickr("#wpsp-advanced-schedule-datetime", {
            enableTime: true,
            dateFormat: "Y-m-d H:i:S",
            altInput: true,
            altFormat: "F j, Y h:i K",
            appendTo: window.document.querySelector('.wpsp-el-modal-date-picker'),
        });

        //Tooltip and icon swapping
        function addTooltip($el) {
            $el.tipsy({
                // `n` for down, `s` for up
                gravity: 's',
                offset: $el.data('tooltip-offset'),
                title: function (){
                    return $el.data('tooltip');
                },
            });
        }
        let $schedulePress_icon = $('#elementor-panel-footer-wpsp-modal');
        let $previewIcon = $('#elementor-panel-footer-saver-preview');
        if ($schedulePress_icon.length > 0 && $previewIcon.length > 0 ) {
            // swap wpsp icon
            let clonedPrevIcon = $previewIcon.clone();
            let clonedSpIcon = $schedulePress_icon.clone();
            $previewIcon.replaceWith(clonedSpIcon);
            $schedulePress_icon.replaceWith(clonedPrevIcon);
            //Add tooltip to elementor panel schedulepress icon and preview icon after dom change
            addTooltip($('#elementor-panel-footer-saver-preview')); // need to pass new query
            addTooltip($('#elementor-panel-footer-wpsp-modal'));

        }else {
            //Add tooltip to elementor panel schedulepress icon
            if ($schedulePress_icon.length > 0){
                addTooltip($schedulePress_icon);// use cached query
            }
        }



        var current_time  = new Date();
        var selected_time = wpsp_date.selectedDates && wpsp_date.selectedDates[0] ? wpsp_date.selectedDates[0] : current_time;
        updateLabel(current_time, selected_time);
        if ('publish' === status && isFuture()) {
            advanced_schedule.show();
        } else {
            advanced_schedule.hide();
        }


        if ('publish' === status){
            if(isAdvanced){
                wpsp_submit_button.show();
                immediately_btn.show().removeClass('active');
                advanced_schedule_text.text(avd_label_update);
            }
            else{
                wpsp_submit_button.show();
                immediately_btn.hide().removeClass('active');
                advanced_schedule_text.text(avd_label_schedule);
            }
        }

        // deprecated event
        elementor.saver.on('page:status:change', function(_status, oldStatus){
            if('publish' == _status && 'draft' == oldStatus){
                status = 'publish';
            }
        });

    });


    $(document).on('click', '#elementor-panel-footer-sub-menu-item-wpsp, #elementor-panel-footer-wpsp-modal,.elementor-panel-footer-wpsp-modal', function (e) {
        e.preventDefault();
        modal.fadeIn();
        }).on('click', '.elementor-templates-modal__header__close > svg, .elementor-templates-modal__header__close > svg *, #schedulepress-elementor-modal', function (e) {
            e.preventDefault();
            if (e.target === this) {
                modal.fadeOut();
            }
        }).on('click', '.wpsp-immediately-publish', function (e) {
            e.preventDefault();
            wpsp_date.clear();
            if( typeof wpsp_adv_date.clear == 'function' ) {
				wpsp_adv_date.clear();
			}
            wpsp_submit_button_text.text(label_publish);
            $(this).addClass('active');
            wpsp_submit_button_text.trigger('click', [$(this)]);
        }).on('click', '.wpsp-advanced-schedule', function (e) {
            e.preventDefault();
            $('#advanced').val(true);
            wpsp_submit_button_text.trigger('click', [$(this)]);
        }).on('click', 'button.wpsp-el-form-submit', function (e, target) {
        e.preventDefault();
        var clickedButton = target || $(this);

        var $form = modal.find('form'),
            url = $form.attr('action'),
            data = $form.serialize(),
            wpsp_el_result = $(".wpsp-el-result");


        clickedButton.addClass('elementor-button-state');
        $.post(url, data, function (data) {
            $('#elementor-panel-saver-button-publish').trigger('click');

            wpsp_el_result.html(data.data.msg).slideDown();
            $('#advanced').val(false);

            if (data.success) {
                clickedButton.removeClass('elementor-button-state');
                wpsp_el_result.addClass('wpsp-msg-success');
                wpsp_date.setDate(data.data.post_time);
                isAdvanced = data.data.advanced;
                status = data.data.status;
                if (data.data.status === 'future') {
                    advanced_schedule.hide();
                    immediately_btn.show();
                    wpsp_submit_button_text.text(label_schedule);
                } else {
                    if(status === 'publish' && isFuture()){
                        advanced_schedule.show();
                    }

                    if(isAdvanced){
                        // wpsp_submit_button.hide();
                        immediately_btn.show().removeClass('active');
                        advanced_schedule_text.text(avd_label_update);
                    }
                    else{
                        wpsp_submit_button.show();
                        immediately_btn.hide().removeClass('active');
                        advanced_schedule_text.text(avd_label_schedule);
                    }
                    wpsp_submit_button_text.text(label_update);
                }
            }

            setTimeout(function () {
                wpsp_el_result.slideUp().html('').removeClass('wpsp-msg-success');
            }, 3000);
        });
    });
    $(document).ready(function () {
        $('.wpsp-el-accordion-header').click(function () {
          // Deactivate all accordions
          $('.wpsp-el-accordion-header').not(this).removeClass('wpsp-el-active');
          $('.wpsp-el-accordion-content').not($(this).next('.wpsp-el-accordion-content')).slideUp();
      
          // Toggle the active class on the clicked header
          $(this).toggleClass('wpsp-el-active');
      
          // Toggle the display property of the associated content
          $(this).next('.wpsp-el-accordion-content').slideToggle();
        });
    });      

    $(document).ready(function() {
        setTimeout(() => {
            jQuery('#elementor-editor-wrapper-v2 .MuiBox-root .MuiGrid-root:first-child .MuiStack-root:last-child').append(`
                <div class="elementor-panel-footer-wpsp-modal"><span id="elementor-panel-footer-wpsp-modal-label" class="eui-box eui-tooltip MuiBox-root css-0"><button class="MuiButtonBase-root MuiIconButton-root MuiIconButton-sizeMedium eui-icon-button wpsp-elementor-topbar-panel-button" tabindex="0" type="button" aria-label="Preview Changes"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 500 500" style="enable-background:new 0 0 500 500;display:block;width:18px;margin:0 auto;" xml:space="preserve">
                <g>
                    <g>
                        <path class="st0" d="M212.3,462.4C95,462.4-0.4,366.9-0.4,249.7S95,37,212.3,37c37,0,73.2,9.6,105.1,27.9
                            c9.8,5.7,13.2,18.1,7.5,27.7c-5.7,9.8-18.1,13.2-27.7,7.5c-25.6-14.7-55.1-22.5-84.9-22.5c-94.7,0-171.8,77.1-171.8,171.8
                            s77.1,171.8,171.8,171.8c48.1,0,92.6-19.4,125.5-54.3c7.8-8.3,20.7-8.5,28.7-1c8.3,7.8,8.5,20.7,1,28.7
                            C327.4,437.8,271,462.4,212.3,462.4z"></path>
                    </g>
                    <path class="st0" d="M186.1,208.3l-43.2-39.3c-8.3-7.5-21.2-7-28.7,1.3c-7.5,8.3-7,21.2,1.3,28.7l46.8,42.4
                        C165.9,227.7,174.5,215.8,186.1,208.3z"></path>
                    <path class="st0" d="M445.4,81.7c-7-8.8-19.9-10.4-28.7-3.4L250,210.1c11.1,8.3,19.1,20.4,21.7,34.7L442,110.2
                        C451.1,103.2,452.4,90.5,445.4,81.7z"></path>
                    <path class="st0" d="M234.3,222.8c-5.2-2.8-11.1-4.4-17.3-4.4c-5.7,0-10.9,1.3-15.5,3.4c-12.7,6-21.2,18.6-21.2,33.4
                        c0,0.8,0,1.6,0,2.3c1.3,19.1,17.1,34.4,36.7,34.4c18.9,0,34.4-14.2,36.5-32.6c0.3-1.3,0.3-2.8,0.3-4.4
                        C253.7,241.1,245.9,229,234.3,222.8z"></path>
                    <path class="st0" d="M493.8,202.6h-51.2c-3.4,0-6.2,2.8-6.2,6.2v45.5c0,3.4,2.8,6.2,6.2,6.2h51.2c3.4,0,6.2-2.8,6.2-6.2v-45.5
                        C500,205.4,497.2,202.6,493.8,202.6z"></path>
                    <g>
                        <path class="st0" d="M410,202.6h-51.2c-3.4,0-6.2,2.8-6.2,6.2v45.5c0,3.4,2.8,6.2,6.2,6.2H410c3.4,0,6.2-2.8,6.2-6.2v-45.5
                            C416.4,205.4,413.6,202.6,410,202.6z"></path>
                        <path class="st0" d="M410,277.6h-51.2c-3.4,0-6.2,2.8-6.2,6.2v45.5c0,3.4,2.8,6.2,6.2,6.2H410c3.4,0,6.2-2.8,6.2-6.2v-45.5
                            C416.4,280.2,413.6,277.6,410,277.6z"></path>
                        <path class="st0" d="M493.8,277.6h-51.2c-3.4,0-6.2,2.8-6.2,6.2v45.5c0,3.4,2.8,6.2,6.2,6.2h51.2c3.4,0,6.2-2.8,6.2-6.2v-45.5
                            C500,280.2,497.2,277.6,493.8,277.6z"></path>
                    </g>
                </g>
                </svg></button></span></div>`);
        }, 0);
    });


    // Assuming the button has an ID like 'your-button-id'
    jQuery(document).on('click', '#elementor-editor-wrapper-v2 .MuiBox-root .MuiGrid-root:last-child button[aria-label="Save Options"]', function() {
        // Append your HTML content to the specified element
        jQuery('#document-save-options .MuiMenu-list').append(`
            <hr class="MuiDivider-root MuiDivider-fullWidth eui-divider css-1px5dlw">
                <div class="elementor-panel-footer-wpsp-modal elementor-topbar-panel MuiButtonBase-root MuiMenuItem-root MuiMenuItem-gutters MuiMenuItem-root MuiMenuItem-gutters eui-menu-item css-108tsqf" tabindex="-1" role="menuitem">
                    <div class="MuiListItemIcon-root eui-list-item-icon css-5n5rd1"><svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium eui-svg-icon css-vubbuv" focusable="false" aria-hidden="true" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M5 4.75C4.66848 4.75 4.35054 4.8817 4.11612 5.11612C3.8817 5.35054 3.75 5.66848 3.75 6V17C3.75 17.3315 3.8817 17.6495 4.11612 17.8839C4.35054 18.1183 4.66848 18.25 5 18.25H19C19.3315 18.25 19.6495 18.1183 19.8839 17.8839C20.1183 17.6495 20.25 17.3315 20.25 17V9C20.25 8.66848 20.1183 8.35054 19.8839 8.11612C19.6495 7.8817 19.3315 7.75 19 7.75H12C11.8011 7.75 11.6103 7.67098 11.4697 7.53033L8.68934 4.75H5ZM3.05546 4.05546C3.57118 3.53973 4.27065 3.25 5 3.25H9C9.19891 3.25 9.38968 3.32902 9.53033 3.46967L12.3107 6.25H19C19.7293 6.25 20.4288 6.53973 20.9445 7.05546C21.4603 7.57118 21.75 8.27065 21.75 9V17C21.75 17.7293 21.4603 18.4288 20.9445 18.9445C20.4288 19.4603 19.7293 19.75 19 19.75H5C4.27065 19.75 3.57118 19.4603 3.05546 18.9445C2.53973 18.4288 2.25 17.7293 2.25 17V6C2.25 5.27065 2.53973 4.57118 3.05546 4.05546Z"></path></svg></div><div class="MuiListItemText-root MuiListItemText-dense eui-list-item-text css-1tsvksn"><span class="MuiTypography-root MuiTypography-body2 MuiListItemText-primary css-14tqbo1">SchedulePress</span>
                </div>
            </div>
        `);
    });

    $(document).ready(function () {
        updateContent();
        $('.wpsp-el-accordion-item input[type="radio"]').change(function () {
            var platform  = $(this).attr('data-platform');
            updateContent(platform);
        });
        function updateContent( platform ) {
            if( platform == 'linkedin-page' || platform == 'linkedin-profile' ) {
                $(`.wpsp-el-accordion-item-linkedin .wpsp-el-content-${platform}`).hide();
                const selectedValue = $(`.wpsp-el-accordion-item-linkedin input[name="wpsp-el-content-${platform}"]:checked`).val();
                $(`.wpsp-el-accordion-item-linkedin .wpsp-el-content-${platform}[data-value="${selectedValue}"]`).show();
            }else if( platform == 'linkedin-tab' ) {
                $(`.wpsp-el-accordion-item-linkedin .wpsp-el-content-linkedin`).hide();
                const selectedValue = $(`.wpsp-el-accordion-item-linkedin input[name="wpsp-el-content-${platform}"]:checked`).val();
                $(`.wpsp-el-accordion-item-linkedin input[name="wpsp-el-content-${platform}"]:checked`).parents('label').siblings().removeClass('active');
                $(`.wpsp-el-accordion-item-linkedin input[name="wpsp-el-content-${platform}"]:checked`).parents('label').addClass('active');
                $(`.wpsp-el-accordion-item-linkedin .wpsp-el-content-linkedin[data-value="${selectedValue}"]`).show();
            } else {
                $(`.wpsp-el-accordion-item-${platform} .wpsp-el-content-${platform}`).hide();
                const selectedValue = $(`.wpsp-el-accordion-item-${platform} input[name="wpsp-el-content-${platform}"]:checked`).val();
                $(`.wpsp-el-accordion-item-${platform} .wpsp-el-content-${platform}[data-value="${selectedValue}"]`).show();
            }
        }

        $(document).on('click', '#wpscpproInstantShareModal .close-kylefoxModal',function(){
            jQuery('body #schedulepress-elementor-modal').css({ 'opacity' : 1 });
        });

        // Linkedin tab selection
        var contentContainers = document.querySelectorAll('.wpsp-el-content-linkedin-tab');
        var tabs = document.querySelectorAll('[name="wpsp-el-content-linkedin-tab"]');
        tabs.forEach(function(tab) {
            tab.addEventListener('change', function() {
                var selectedValue = this.value;
                contentContainers.forEach(function(container) {
                    if (container.classList.contains(selectedValue)) {
                        container.style.display = 'block';
                    } else {
                        container.style.display = 'none';
                    }
                });
            });
        });

        $(document).on('click', '.wpsp_el_share_now',(function(event){
            event.preventDefault();
            // modal append if not exists dom
            if ($('#wpscpproInstantShareModal').length === 0) {
                jQuery('body').append(
                    '<div id="wpscpproInstantShareModal"><div class="modalBody">Fetch Your Selected Profile</div></div>'
                )
            } else {
                jQuery('body #wpscpproInstantShareModal').html(
                    '<div class="modalBody">Fetch Your Selected Profile</div>'
                )
            }
            jQuery('body #wpscpproInstantShareModal').kylefoxModal({
                escapeClose: false,
                clickClose: false,
                showClose: true,
            })
            jQuery('body #schedulepress-elementor-modal').css({'opacity' : 0});
            const pinterest_selection = $('.wpsp-el-accordion-item-pinterest .wpsp-el-container [name="wpsp-el-content-pinterest"]:checked').val();
            
            // selected facebook profile
            let facebook_selected_profiles;
            let is_facebook_share = true;
            const el_facebook_profile = $('[name="wpsp-el-content-facebook"]:checked').val();
            if( el_facebook_profile == 'wpsp-el-social-facebook-custom' ) {
                facebook_selected_profiles = $('[name="wpsp_el_social_facebook[]"]:checked').map(function() {
                    return $(this).val();
                }).get();
                if( facebook_selected_profiles.length == 0 ) {
                    is_facebook_share = false;
                }
            }else{
                facebook_selected_profiles = $('[name="wpsp_el_social_facebook[]"]').map(function() {
                    return $(this).val();
                }).get();
                if( facebook_selected_profiles.length == 0 ) {
                    is_facebook_share = false;
                }
            }
            // selected twitter profile
            let twitter_selected_profiles;
            let is_twitter_share = true;
            const el_twitter_profile = $('[name="wpsp-el-content-twitter"]:checked').val();
            if( el_twitter_profile == 'wpsp-el-social-twitter-custom' ) {
                twitter_selected_profiles = $('[name="wpsp_el_social_twitter[]"]:checked').map(function() {
                    return $(this).val();
                }).get();
                if( twitter_selected_profiles.length == 0 ) {
                    is_twitter_share = false;
                }
            }else{
                twitter_selected_profiles = $('[name="wpsp_el_social_twitter[]"]').map(function() {
                    return $(this).val();
                }).get();
                if( twitter_selected_profiles.length == 0 ) {
                    is_twitter_share = false;
                }
            }

            // selected twitter profile
            let linkedin_selected_profiles;
            let is_linkedin_share = true;
            const el_linkedin_page = $('[name="wpsp-el-content-linkedin-page"]:checked').val();
            const el_linkedin_profile = $('[name="wpsp-el-content-linkedin-profile"]:checked').val();
            if( el_linkedin_page == 'wpsp-el-social-linkedin-page-custom' ||  el_linkedin_profile == 'wpsp-el-social-linkedin-profile-custom' ) {
                linkedin_selected_profiles = $('[name="wpsp_el_social_linkedin[]"]:checked').map(function() {
                    return $(this).val();
                }).get();
                if( linkedin_selected_profiles.length == 0 ) {
                    is_linkedin_share = false;
                }
            }else{
                linkedin_selected_profiles = $('[name="wpsp_el_social_linkedin[]"]').map(function() {
                    return $(this).val();
                }).get();
                if( linkedin_selected_profiles.length == 0 ) {
                    is_linkedin_share = false;
                }
            }

            // selected pinterest profile
            let pinterest_selected_profiles;
            let is_pinterest_share = true;
            let pinterestBoardType;
            const el_pinterest_profile = $('[name="wpsp-el-content-pinterest"]:checked').val();
            if( el_pinterest_profile == 'wpsp-el-social-pinterest-custom' ) {
                pinterest_selected_profiles = $('[name="wpsp_el_social_pinterest[]"]:checked').map(function() {
                    return $(this).val();
                }).get();
                if( pinterest_selected_profiles.length == 0 ) {
                    is_pinterest_share = false;
                }
            }else{
                pinterest_selected_profiles = $('[name="wpsp_el_social_pinterest[]"]').map(function() {
                    return $(this).val();
                }).get();
                if( pinterest_selected_profiles.length == 0 ) {
                    is_pinterest_share = false;
                }
            }
            if( pinterest_selection === 'wpsp-el-social-pinterest-custom' ) {
                pinterestBoardType = 'custom';
            }
            
            // selected instagram profile
            let instagram_selected_profiles;
            let is_instagram_share = true;
            const el_instagram_profile = $('[name="wpsp-el-content-instagram"]:checked').val();
            if( el_instagram_profile == 'wpsp-el-social-instagram-custom' ) {
                instagram_selected_profiles = $('[name="wpsp_el_social_instagram[]"]:checked').map(function() {
                    return $(this).val();
                }).get();
                if( instagram_selected_profiles.length == 0 ) {
                    is_instagram_share = false;
                }
            }else{
                instagram_selected_profiles = $('[name="wpsp_el_social_instagram[]"]').map(function() {
                    return $(this).val();
                }).get();
                if( instagram_selected_profiles.length == 0 ) {
                    is_instagram_share = false;
                }
            }


             // selected medium profile
             let medium_selected_profiles;
             let is_medium_share = true;
             const el_medium_profile = $('[name="wpsp-el-content-medium"]:checked').val();
             if( el_medium_profile == 'wpsp-el-social-medium-custom' ) {
                 medium_selected_profiles = $('[name="wpsp_el_social_medium[]"]:checked').map(function() {
                     return $(this).val();
                 }).get();
                 if( medium_selected_profiles.length == 0 ) {
                     is_medium_share = false;
                 }
             }else{
                 medium_selected_profiles = $('[name="wpsp_el_social_medium[]"]').map(function() {
                     return $(this).val();
                 }).get();
                 if( medium_selected_profiles.length == 0 ) {
                     is_medium_share = false;
                 }
             }
            
             // selected threads profile
             let threads_selected_profiles;
             let is_threads_share = true;
             const el_threads_profile = $('[name="wpsp-el-content-threads"]:checked').val();
             if( el_threads_profile == 'wpsp-el-social-threads-custom' ) {
                 threads_selected_profiles = $('[name="wpsp_el_social_threads[]"]:checked').map(function() {
                     return $(this).val();
                 }).get();
                 if( threads_selected_profiles.length == 0 ) {
                     is_threads_share = false;
                 }
             }else{
                 threads_selected_profiles = $('[name="wpsp_el_social_threads[]"]').map(function() {
                     return $(this).val();
                 }).get();
                 if( threads_selected_profiles.length == 0 ) {
                     is_threads_share = false;
                 }
             }

            var postid = jQuery('#wpscppropostid').val()
            // var nonce = jQuery('#wpscp_pro_instant_social_share_nonce').val()
            const nonce = wpscpSocialProfile?.nonce;
            var data = {
                action: 'wpscp_instant_share_fetch_profile',
                _nonce: nonce,
                postid: postid,
                is_facebook_share,
                is_twitter_share,
                is_linkedin_share,
                is_pinterest_share,
                is_instagram_share,
                is_medium_share,
                is_threads_share,
                facebook_selected_profiles,
                twitter_selected_profiles,
                linkedin_selected_profiles,
                pinterest_selected_profiles,
                instagram_selected_profiles,
                medium_selected_profiles,
                threads_selected_profiles,
            }
    
            jQuery.post(ajaxurl, data, function (response, status) {
                if (status == 'success') {
                    jQuery('body #wpscpproInstantShareModal .modalBody').html(
                        response.markup
                    )
                    /**
                     * Single Profile Ajax sending via loop
                     */
                    $.each(response.profile, function (profile, profileKey) {
                        const nonce = wpscpSocialProfile?.nonce;
                        Object.keys(profileKey).forEach(function (key) {
                            var data = {
                                action: 'wpscp_instant_social_single_profile_share',
                                platform: profile,
                                nonce : nonce,
                                platformKey: key,
                                postid: postid,
                                pinterest_board_type: pinterestBoardType,
                            }
                            if(profile === 'pinterest' && pinterestBoardType === 'custom'){
                                pinterest_selected_profiles.forEach(single_pinterest_board => {
                                    if( single_pinterest_board == profileKey[key]?.default_board_name?.value ) {
                                        data.pinterest_custom_board_name   = single_pinterest_board;
                                        data.pinterest_custom_section_name = jQuery(".wpsp-el-content-pinterest .social-profile #wpsp_el_pinterest_section_" + single_pinterest_board).val();
                                        jQuery.get(ajaxurl, data, function (response, status) {
                                            WpScp_Social_single_profile_share_response_markup(
                                                profile,
                                                key,
                                                response
                                            )
                                        })
                                    }
                                });
                            }else{
                                jQuery.get(ajaxurl, data, function (response, status) {
                                    WpScp_Social_single_profile_share_response_markup(
                                        profile,
                                        key,
                                        response
                                    )
                                })
                            }
                            
                        })
                    })
                } else {
                    jQuery('body #wpscpproInstantShareModal').append(
                        'failed element'
                    )
                }
            })
        }))
    });

    // Checkbox selection  stopPropagation
    document.addEventListener('DOMContentLoaded', function () {
        var checkboxesAndRadios = document.querySelectorAll('.el-social-share-platform input[type="checkbox"], .wpsp-pro-fields a, .el-social-share-platform input[type="radio"], .el-social-share-platform label, .wpsp-el-empty-profile-message a, .wpsp-el-disabled-text a, .post-type-message span a');
        checkboxesAndRadios.forEach(function (checkboxOrRadio) {
            checkboxOrRadio.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        });
    });

    /**
     * popup social media share modal response
     * @param {ID} key
     * @param {ajax response} response
     * @returns markup
     */
    function WpScp_Social_single_profile_share_response_markup(
        profile,
        key,
        response
    ) {
        var logStatusSelector = $('#' + profile + '_' + key + ' .entry-status')
        var logSelector = $('#' + profile + '_' + key + ' .entry-log')
        var viewLogButton =
            '<a href="#" data-id="' +
            profile +
            '_' +
            key +
            '" class="viewlog">View Log</a>'
        var viewLogButtonFailed =
            '<a href="#" data-id="' +
            profile +
            '_' +
            key +
            '" class="viewlog failed">View Log</a>'
        var successStatus = '<span class="status success">Shared</span>'
        var failedStatus = '<span class="status failed">Failed</span>'
        // handle pinterest, twitter, linkedin response
        if (response.success) {
            logStatusSelector.replaceWith(successStatus + viewLogButton)
            logSelector.append(
                '<div class="log">' + JSON.stringify(response.data) + '</div>'
            )
        } else {
            logStatusSelector.replaceWith(failedStatus + viewLogButtonFailed)
            logSelector.append('<div class="log">' + response.data + '</div>')
        }
    }
    jQuery('.wpsp-el-form-next').click(function(event){
        event.stopPropagation();
        $(this).removeClass('wpsp-d-block').addClass('wpsp-d-none');
        $('.wpsp-elementor-modal-wrapper').addClass('wpsp-flex-direction-unset');
        $('.wpsp-el-form-prev').removeClass('wpsp-d-none').addClass('wpsp-d-block');
        $('.wpsp_form_next_button_wrapper').removeClass('wpsp-d-none').addClass('wpsp-d-block');
        $('.wpsp-el-fields-next').addClass('active');
        $('.wpsp-el-fields-prev').removeClass('active');
        $('.wpsp_form_next_button_wrapper').removeClass('active');
    });
    jQuery('.wpsp-el-form-prev').click(function(event){
        event.stopPropagation();
        $(this).removeClass('wpsp-d-block').addClass('wpsp-d-none');
        $('.wpsp-elementor-modal-wrapper').removeClass('wpsp-flex-direction-unset');
        $('.wpsp_form_next_button_wrapper').removeClass('wpsp-d-block').addClass('wpsp-d-none');
        $('.wpsp-el-form-next').removeClass('wpsp-d-none').addClass('wpsp-d-block');
        $('.wpsp-el-fields-prev').addClass('active');
        $('.wpsp_form_next_button_wrapper').addClass('active');
        $('.wpsp-el-fields-next').removeClass('active');
    });

    /**
     * Modal ajax log view
    */
     jQuery(document).on(
        'click',
        '#wpscpproInstantShareModal a.viewlog',
        function (e) {
            e.preventDefault()
            jQuery('#' + e.target.dataset.id + ' .log').show() // show log
            jQuery(this).hide() // hide log button
        }
    )
})(jQuery);  
