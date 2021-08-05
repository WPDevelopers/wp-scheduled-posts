(function ($) {
    var wpsp_menu = $('#elementor-panel-footer-sub-menu-item-wpsp'),
        modal = $('#schedulepress-elementor-modal');

    $(window).on('load', function () {
        $('.elementor-panel-footer-sub-menu-wrapper .elementor-panel-footer-sub-menu').append(wpsp_menu);
    });

    $(document).on('click', '#elementor-panel-footer-sub-menu-item-wpsp', function (e) {
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
            data = $form.serialize()
        $.post(url, data, function (data) {
            $(".wpsp-el-result").html(data);
        });
    })
})(jQuery);