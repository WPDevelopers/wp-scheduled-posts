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

    // Quick Setup Wizard Save
    jQuery(document).on('click', '#quicksetupwizardsave', function(e){
        e.preventDefault();
        var ajaxnonce  = $('.wpscp-setup-wizard input[name="wpscpqswnonce"]').val();
        var show_dashboard_widget  = $('.wpscp-setup-wizard input[name="show_dashboard_widget"]').attr("checked") ? 1 : 0;
        var show_in_front_end_adminbar  = $('.wpscp-setup-wizard input[name="show_in_front_end_adminbar"]').attr("checked") ? 1 : 0;
        var show_in_adminbar  = $('.wpscp-setup-wizard input[name="show_in_adminbar"]').attr("checked") ? 1 : 0;
        var prevent_future_post  = $('.wpscp-setup-wizard input[name="prevent_future_post"]').attr("checked") ? 1 : 0;
        // multiselect
        var allow_post_types  = wpscp_select_box_get_value('#allow_post_types');
        var allow_categories  = wpscp_select_box_get_value('#allow_categories');
        var allow_user_role  = wpscp_select_box_get_value('#allow_user_role');
        // indevisual option field data passing
        var autoScheduler  = $('.wpscp-setup-wizard input#autoScheduler').attr("checked") ? 'ok' : 0;
        var manualScheduler  = $('.wpscp-setup-wizard input#manualScheduler').attr("checked") ? 'ok' : 0;
        // missscheduled
        var missscheduled = $('#missscheduled').prop("checked") == true ? 1 : 0;
        // social integation - twitter
        var tw_consumer_key = $('.wpscp-setup-wizard input[name="tw_consumer_key"]').val();
        var tw_consumer_sec = $('.wpscp-setup-wizard input[name="tw_consumer_sec"]').val();
        var tw_access_key = $('.wpscp-setup-wizard input[name="tw_access_key"]').val();
        var tw_access_sec = $('.wpscp-setup-wizard input[name="tw_access_sec"]').val();
        
        // facebook
        var fb_app_id = $('.wpscp-setup-wizard input[name="fb_app_id"]').val();
        var fb_app_secret = $('.wpscp-setup-wizard input[name="fb_app_secret"]').val();
        var wpscp_pro_app_type = $('.wpscp-setup-wizard input[name="wpscp_pro_app_type"]:checked').val();
        var fb_access_token = $('#fb_access_token').val();
        console.log('facebook', wpscp_pro_app_type);
       
        var data = {
			'action': 'quick_setup_wizard_action',
			'security': ajaxnonce,
			'show_dashboard_widget': show_dashboard_widget,
			'show_in_front_end_adminbar': show_in_front_end_adminbar,
			'show_in_adminbar': show_in_adminbar,
			'prevent_future_post': prevent_future_post,
			'allow_post_types': allow_post_types,
			'allow_categories': allow_categories,
            'allow_user_role': allow_user_role,
            // indevisual option field data passing
            'autoScheduler': autoScheduler,
            'manualScheduler': manualScheduler,
            'missscheduled': missscheduled,
            // twitter
            'tw_consumer_key' : tw_consumer_key,
            'tw_consumer_sec' : tw_consumer_sec,
            'tw_access_key' : tw_access_key,
            'tw_access_sec' : tw_access_sec,
            // facebook
            'fb_app_id' : fb_app_id,
            'fb_app_secret' : fb_app_secret,
            'wpscp_pro_app_type' : wpscp_pro_app_type,
            'fb_access_token' : fb_access_token
		};
		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		jQuery.post(ajaxurl, data, function(response) {
			swal({
                title: "Good job!",
                text: "You clicked the button!",
                icon: "success",
            });
		});
        
   });

   function wpscp_select_box_get_value(selector){
        var selected=[];
        $( selector + ' :selected').each(function(){
            selected.push($(this).text());
        });
        return selected;
   }


   function wpscp_quick_setup_toggle_schedule(){
       var autoScheduler = ".wpscp-setup-wizard input#autoScheduler";
       var manualScheduler = ".wpscp-setup-wizard input#manualScheduler";
       toggleControl();
       function toggleControl(){
            if($(autoScheduler).is(':checked') === true){ // auto scheduler
                $('#toggleSwithElementContent .manualScheduler').hide(500);
                $('#toggleSwithElementContent .autoScheduler').show(500);
            }else if($(manualScheduler).is(':checked') === true) {
                $('#toggleSwithElementContent .autoScheduler').hide(500);
                $('#toggleSwithElementContent .manualScheduler').show(500);
            }
       }
        $(autoScheduler).add(manualScheduler).click(function(){
            toggleControl();
        });
   }
   wpscp_quick_setup_toggle_schedule();

   // popup modal showing for error message
   $('.wpscp-pro-feature-checkbox label').on('click', function(){
        var premium_content = document.createElement("p");
        var premium_anchor = document.createElement("a");

        premium_anchor.setAttribute('href', 'https://wpdeveloper.net/in/wp-scheduled-posts-pro');
        premium_anchor.innerText = 'Premium';
        premium_anchor.style.color = 'red';
        var pro_label = $(this).find('.nx-pro-label');
        if (pro_label.hasClass('has-to-update')) {
            premium_anchor.innerText = 'Latest Pro v' + pro_label.text().toString().replace(/[ >=<]/g, '');
        }
        premium_content.innerHTML = 'You need to upgrade to the <strong>' + premium_anchor.outerHTML + ' </strong> Version to use this module.';

        swal({
            title: "Opps...",
            content: premium_content,
            icon: "warning",
            buttons: [false, "Close"],
            dangerMode: true,
        });
   });

});

