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




})(jQuery);