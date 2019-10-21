jQuery(document).ready(function ($) {
    $('.wpscp-setup-wizard ul.tabs-nav li.nav-item a').on('click', function (event) {
        event.preventDefault();
        console.log($(this).attr('href'));

        $('.tab-content').removeClass('active');
        $('.nav-item').removeClass('tab-active');
        $('.tab-content').removeClass('active');
        // action tab item and tab content
        $(this).parent().addClass('tab-active');
        $($(this).attr('href')).addClass('active');
    });

    // Tabs Switch Option
    $('.btn-next-option').click(function(e){
        e.preventDefault();
        $('.tabs-nav > .tab-active').next('li').find('a').trigger('click');
    });
    
    $('.btn-prev-option').click(function(e){
        e.preventDefault();
        $('.tabs-nav > .tab-active').prev('li').find('a').trigger('click');
    });
});