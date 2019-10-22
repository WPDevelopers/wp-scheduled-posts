jQuery(document).ready(function ($) {
    $('.wpscp-setup-wizard .wpscp-tabnav-wrap ul.tab-nav li.nav-item a').on('click', function (event) {
        event.preventDefault();
        $('.tab-content').removeClass('active');
        $('.nav-item').removeClass('tab-active');
        $('.tab-content').removeClass('active');
        // action tab item and tab content
        $(this).parent().addClass('tab-active');
        $($(this).attr('href')).addClass('active');
    });

    // Tabs Switch Option
    $('.wpscp-next-option').click(function(e){
        e.preventDefault();
        $('.wpscp-setup-wizard .wpscp-tabnav-wrap ul.tab-nav > .tab-active').next('li').find('a').trigger('click');
    });
    
    $('.wpscp-prev-option').click(function(e){
        e.preventDefault();
        $('.wpscp-setup-wizard .wpscp-tabnav-wrap ul.tab-nav > .tab-active').prev('li').find('a').trigger('click');
    });
});

