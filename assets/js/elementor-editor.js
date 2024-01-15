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

    $(document).ready(function() {
        jQuery('#elementor-editor-wrapper-v2 .eui-box .MuiGrid-root:first-child .eui-stack:last-child').append(`
            <div class="elementor-panel-footer-wpsp-modal"><span id="elementor-panel-footer-wpsp-modal-label" class="eui-box eui-tooltip MuiBox-root css-0"><button class="MuiButtonBase-root MuiIconButton-root MuiIconButton-sizeMedium eui-icon-button wpsp-elementor-topbar-panel-button" tabindex="0" type="button" aria-label="Preview Changes"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 500 500" style="enable-background:new 0 0 500 500;display:block;width:18px;margin:0 auto;" xml:space="preserve">
            <style type="text/css">
                .st0{fill:#A4AFB7;}
                #elementor-panel-footer-wpsp-modal:hover .st0{fill:#d5dadf;}
            </style>
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
    });


    // Assuming the button has an ID like 'your-button-id'
    jQuery(document).on('click', '#elementor-editor-wrapper-v2 .MuiBox-root .MuiGrid-root:last-child button[aria-label="Save Options"]', function() {
        // Append your HTML content to the specified element
        jQuery('#document-save-options .MuiMenu-list').append(`
            <hr class="MuiDivider-root MuiDivider-fullWidth eui-divider css-1px5dlw">
                <div class="elementor-panel-footer-wpsp-modal MuiButtonBase-root MuiMenuItem-root MuiMenuItem-gutters MuiMenuItem-root MuiMenuItem-gutters eui-menu-item css-108tsqf" tabindex="-1" role="menuitem">
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
            $(`.wpsp-el-accordion-item-${platform} .wpsp-el-content-${platform}`).hide();
            const selectedValue = $(`.wpsp-el-accordion-item-${platform} input[name="wpsp-el-content-${platform}"]:checked`).val();
            $(`.wpsp-el-accordion-item-${platform} .wpsp-el-content-${platform}[data-value="${selectedValue}"]`).show();
        }
    });

    // Checkbox selection  stopPropagation
    document.addEventListener('DOMContentLoaded', function () {
        var checkboxesAndRadios = document.querySelectorAll('.el-social-share-platform input[type="checkbox"], .el-social-share-platform input[type="radio"], .el-social-share-platform label');
        checkboxesAndRadios.forEach(function (checkboxOrRadio) {
            checkboxOrRadio.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        });
    });

    jQuery('.wpsp-el-form-next').click(function(event){
        event.stopPropagation();
        $('.wpsp-el-fields-next').addClass('active');
        $('.wpsp-el-fields-prev').removeClass('active');
    });
    
})(jQuery);  
