(function ($) {
    var wpsp_menu = $('#elementor-panel-footer-sub-menu-item-wpsp'),
        modal = $('#schedulepress-elementor-modal'),
        wpsp_quick_button = $('#elementor-panel-footer-wpsp-modal'),
        wpsp_submit_button = $('.wpsp-el-form-submit'),
        wpsp_submit_button_text = $('span:nth-child(2)', wpsp_submit_button),
        label_schedule = wpsp_submit_button.data('label-schedule'),
        label_publish = wpsp_submit_button.data('label-publish'),
        label_update = wpsp_submit_button.data('label-update'),
        advanced_schedule = $('.wpsp-advanced-schedule'),
        status = advanced_schedule.data('status'),
        isAdvanced = advanced_schedule.data('is-advanced'),
        wpsp_date;

    var updateLabel = function(current_time, selected_time){
        if (current_time.getTime() < selected_time.getTime()) {
            wpsp_submit_button_text.text(label_schedule)
        } else {
            wpsp_submit_button_text.text(label_publish)
        }
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

                if (status === 'publish' && !isAdvanced && current_time.getTime() < selected_time.getTime()) {
                    advanced_schedule.show();
                } else {
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

        var current_time = new Date(),
        selected_time = wpsp_date.selectedDates && wpsp_date.selectedDates[0] ? wpsp_date.selectedDates[0] : current_time;


        updateLabel(current_time, selected_time);
        if (status === 'publish' && !isAdvanced && current_time.getTime() < selected_time.getTime()) {
            advanced_schedule.show();
        } else {
            advanced_schedule.hide();
        }

        // $e.commands.on('document/elements/settings', function(args){
        //     console.error('XXXXX', args);
        // });
        // jQuery(window).on('elementor/commands/run/before', function(event){
        //     var detail = event.detail;
        //     if(detail.command === 'document/elements/settings'){
        //         // console.error(detail);
        //         if(detail.args.settings && detail.args.settings.post_status === "publish"){
        //             console.error('XXXXX', detail.args);
        //         }
        //     }
        // });
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
        wpsp_submit_button_text.trigger('click');
    }).on('click', 'button.wpsp-el-form-submit', function (e) {
        e.preventDefault();
        if($(this).hasClass('wpsp-advanced-schedule')){
            $('#advanced').val(true);
        }
        var $form = modal.find('form'),
            url = $form.attr('action'),
            data = $form.serialize(),
            wpsp_el_result = $(".wpsp-el-result");

        $('#elementor-panel-saver-button-publish').trigger('click');

        wpsp_submit_button.addClass('elementor-button-state');
        $.post(url, data, function (data) {
            wpsp_el_result.html(data.data.msg).slideDown();
            $('#advanced').val(false);

            if (data.success) {
                var immediately_btn = $('.wpsp-immediately-publish');
                wpsp_submit_button.removeClass('elementor-button-state');
                wpsp_el_result.addClass('wpsp-msg-success');
                wpsp_date.setDate(data.data.post_time);
                isAdvanced = data.data.advanced;

                if (data.data.status === 'future') {
                    advanced_schedule.hide();
                    immediately_btn.show();
                    wpsp_submit_button_text.text(label_schedule);
                } else {
                    if(isAdvanced){
                        advanced_schedule.hide();
                        immediately_btn.show().removeClass('active');
                    }
                    else{
                        immediately_btn.hide().removeClass('active');
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