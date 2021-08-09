(function ($) {
    var wpsp_menu = $('#elementor-panel-footer-sub-menu-item-wpsp'),
        modal = $('#schedulepress-elementor-modal'),
        wpsp_quick_button = $('#elementor-panel-footer-wpsp-modal');

    $(window).on('load', function () {
        $('.elementor-panel-footer-sub-menu-wrapper .elementor-panel-footer-sub-menu').append(wpsp_menu);
        wpsp_quick_button.insertAfter('#elementor-panel-footer-saver-preview');
        $("#wpsp-schedule-datetime").flatpickr({
            enableTime: true,
            dateFormat: "Y-m-dTH:i:S",
            altInput: true,
            altFormat: "F j, Y h:i K",
        });
    });

    $(document).on('click', '#elementor-panel-footer-sub-menu-item-wpsp, #elementor-panel-footer-wpsp-modal', function (e) {
        e.preventDefault();
        modal.fadeIn();
    }).on('click', '.elementor-templates-modal__header__close > i, #schedulepress-elementor-modal', function (e) {
        e.preventDefault();
        if (e.target === this) {
            modal.fadeOut();
        }
    }).on('click', 'button.wpsp-el-form-submit', function (e) {
        e.preventDefault();
        var $form = modal.find('form'),
            url = $form.attr('action'),
            data = $form.serialize(),
            wpsp_submit_button = $('.wpsp-el-form-submit');

        wpsp_submit_button.addClass('elementor-button-state');
        $.post(url, data, function (data) {
            $(".wpsp-el-result").html(data);
            wpsp_submit_button.removeClass('elementor-button-state');
        });
    });
})(jQuery);