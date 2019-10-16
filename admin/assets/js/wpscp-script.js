jQuery(document).ready(function ($) {
    jQuery('#wpscp-future-post-help-handler').on('click', function(){
        jQuery("#wpscp-future-post-help-info").toggle(200);
    });
    jQuery(document).ready(function($){
        $("#catalog-visibility").before($("#prevent_future_post_box"));
    });

    //onchange anything change submit button class
    var wpsp_activated_inputs = document.querySelectorAll('.wpsp_field_activate');
    
    wpsp_activated_inputs.forEach( function(wpsp_activated_input) {
	    wpsp_activated_input.addEventListener('change', function(event) {
	    	
	    	current_event = event.target;
	    	wpspOnChangeSubmitBtnStyle(current_event);
	    	
	    })

    } )

    function wpspOnChangeSubmitBtnStyle(current_event) {
    	var current_section = current_event.closest('.wpsp_nav_tab_content');

    		if(current_section.id == 'wpsp-wpsp_integ' || current_section.id == 'wpsp-wpsp_social_templates') {
    			var current_section = current_event.closest('.wpsp-integ-item_section');
    		}

    	var wpsp_gen_form_submit = current_section.querySelector('.wpsp_form_submit');
		    wpsp_gen_form_submit.classList.add('wpsp-save-now');
    }


    //radio form section show
    var fb_radios = document.querySelectorAll('.wpsp_fb_radio');
    fb_radios.forEach( function(fb_radio) {
    	var wpsp_app_infos = document.querySelectorAll('.wpsp_app_info');

    	//initially get the checkbox is checked or not
    	var data_id = fb_radio.getAttribute('data-id');
		wpsp_app_infos.forEach( function(wpsp_app_info) {
    		
    		if(data_id == 'second' && fb_radio.checked) {
	    		wpsp_app_info.style.display = 'table-row';
    		}
    	} )

		//onclick show/unshow
    	fb_radio.addEventListener('click', function(event) {
    		var current_radio = event.target;
	    	wpsp_app_infos.forEach( function(wpsp_app_info) {
	    		var current_data_id = current_radio.getAttribute('data-id');

	    		if(current_data_id == 'second' && current_radio.checked) {
	    			if(wpsp_app_info.style.display = 'none') {
			    		wpsp_app_info.style.display = 'table-row';

		    		}
	    		}else{
	    			wpsp_app_info.style.display = 'none';
	    		}
	    		
	    	} )

    	})
	});
});

