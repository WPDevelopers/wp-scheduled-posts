(function ($) {
    var wpsp_menu = $('#elementor-panel-footer-sub-menu-item-wpsp'),
        modal = $('#schedulepress-elementor-modal'),
        wpsp_quick_button = $('#elementor-panel-footer-wpsp-modal'),
        wpsp_submit_button = $('.wpsp-el-form-submit:not(.wpsp-advanced-schedule)'),
        wpsp_submit_button_text = $('span:nth-child(2)', wpsp_submit_button),
        label_schedule = wpsp_submit_button.data('label-schedule'),
        label_publish = wpsp_submit_button.data('label-publish'),
        label_update = wpsp_submit_button.data('label-update'),
        advanced_schedule = $('.wpsp-advanced-schedule'),
        advanced_schedule_text = $('span:nth-child(2)', advanced_schedule),
        avd_label_schedule = advanced_schedule.data('label-schedule'),
        avd_label_update = advanced_schedule.data('label-update'),
        status = advanced_schedule.data('status'),
        isAdvanced = advanced_schedule.data('is-advanced'),
        immediately_btn = $('.wpsp-immediately-publish'),
        wpsp_date;

    var updateLabel = function(current_time, selected_time){
        if (current_time.getTime() < selected_time.getTime()) {
            wpsp_submit_button_text.text(label_schedule)
        } else {
            wpsp_submit_button_text.text(label_publish)
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
                wpsp_submit_button.hide();
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


    $(document).on('click', '#elementor-panel-footer-sub-menu-item-wpsp, #elementor-panel-footer-wpsp-modal', function (e) {
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
                        wpsp_submit_button.hide();
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
            facebook_selected_profiles = $('[name="wpsp_el_social_facebook[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            if( facebook_selected_profiles.length == 0 ) {
                is_facebook_share = false;
            }
            // selected twitter profile
            let twitter_selected_profiles;
            let is_twitter_share = true;
            twitter_selected_profiles = $('[name="wpsp_el_social_twitter[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            if( twitter_selected_profiles.length == 0 ) {
                is_twitter_share = false;
            }
            // selected twitter profile
            let linkedin_selected_profiles;
            let is_linkedin_share = true;
            linkedin_selected_profiles = $('[name="wpsp_el_social_linkedin[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            if( linkedin_selected_profiles.length == 0 ) {
                is_linkedin_share = false;
            }

            // selected pinterest profile
            let pinterest_selected_profiles;
            let is_pinterest_share = true;
            let pinterestBoardType;
            pinterest_selected_profiles = $('[name="wpsp_el_social_pinterest[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            if( pinterest_selected_profiles.length == 0 ) {
                is_pinterest_share = false;
            }
            if( pinterest_selection === 'wpsp-el-social-pinterest-custom' ) {
                pinterestBoardType = 'custom';
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
                facebook_selected_profiles,
                twitter_selected_profiles,
                linkedin_selected_profiles,
                pinterest_selected_profiles,
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
        var checkboxesAndRadios = document.querySelectorAll('.el-social-share-platform input[type="checkbox"], .el-social-share-platform input[type="radio"], .el-social-share-platform label, .wpsp-el-empty-profile-message a');
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