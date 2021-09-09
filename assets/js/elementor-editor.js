(function ($) {
    var wpsp_menu = $('#elementor-panel-footer-sub-menu-item-wpsp'),
        modal = $('#schedulepress-elementor-modal'),
        wpsp_quick_button = $('#elementor-panel-footer-wpsp-modal'),
        wpsp_submit_button = $('.wpsp-el-form-submit'),
        wpsp_submit_button_text = $('span:nth-child(2)', wpsp_submit_button),
        label_schedule = wpsp_submit_button.data('label-schedule'),
        label_publish = wpsp_submit_button.data('label-publish'),
        label_update = wpsp_submit_button.data('label-update'),
        wpsp_date;

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

                if (current_time.getTime() < selected_time.getTime()) {
                    wpsp_submit_button_text.text(label_schedule)
                } else {
                    wpsp_submit_button_text.text(label_publish)
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
        var $form = modal.find('form'),
            url = $form.attr('action'),
            data = $form.serialize(),
            wpsp_el_result = $(".wpsp-el-result");

        $('#elementor-panel-saver-button-publish').trigger('click');

        wpsp_submit_button.addClass('elementor-button-state');
        $.post(url, data, function (data) {
            wpsp_el_result.html(data.data.msg).slideDown();

            if (data.success) {
                var immediately_btn = $('.wpsp-immediately-publish');
                wpsp_submit_button.removeClass('elementor-button-state');
                wpsp_el_result.addClass('wpsp-msg-success');
                wpsp_date.setDate(data.data.post_time);

                if (data.data.status === 'future') {
                    immediately_btn.show();
                    wpsp_submit_button_text.text(label_schedule);
                } else {
                    immediately_btn.hide().removeClass('active');
                    wpsp_submit_button_text.text(label_update);
                }
            }

            setTimeout(function () {
                wpsp_el_result.slideUp().html('').removeClass('wpsp-msg-success');
            }, 3000);
        });
    });




})(jQuery);