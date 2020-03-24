jQuery(document).ready(function ($) {
	var get_query_vars = function (name) {
		var vars = {};
		window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function (m, key, value) {
			vars[key] = value;
		});
		if (name != '') {
			return vars[name];
		}
		return vars;
	};

	//success msg html added
	var wpscp_success_span = document.querySelector('.wpscp-success');
		if(wpscp_success_span) {
	  		wpscp_success_span.style.display = 'none';

		}

	/* Loader JS */
	$(window).bind("load", function() {
		$('.wpsp_loader').fadeOut(200);
	 });

	/* options page js start */
	$('.wpsp-options-wrap form select').select2();
	$('#notify_author_role_sent_review').select2();
	$('#notify_author_username_sent_review').select2();
	$('#notify_author_email_sent_review').select2();
	$('#notify_author_post_schedule_role').select2();
	$('#notify_author_post_schedule_username').select2();
	$('#notify_author_post_schedule_email').select2();
	/* options page js end */

	// option page custom template arrow toggle
	var custom_tem_tog_btn = document.querySelector('.toggle_arrow');
	var wpsp_cus_temp_opt = document.querySelector('.wpsp_cus_temp_opt');
	if (custom_tem_tog_btn) {
		custom_tem_tog_btn.addEventListener('click', function (e) {
			e.preventDefault();
			if (wpsp_cus_temp_opt.style.display == "block") {
				wpsp_cus_temp_opt.style.display = "none";
				this.style.transform = "rotate(0deg)";
			} else {
				wpsp_cus_temp_opt.style.display = "block";
				this.style.transform = "rotate(180deg)";
			}
		});
	}
	
	/* Top Nav Tab Event JS */
	var top_nav_tabs = document.querySelectorAll('.wpsp_top_nav_link_wrapper ul li a');
	var top_nav_contents = document.querySelectorAll('.wpsp_nav_tab_content');
	$('.wpsp_top_nav_link_wrapper ul li').on('click', function () {
		var target = $(this).find('a').attr('href').replace('#', '#wpsp-');
		$(this).find('a').addClass('wpsp_top_nav_tab_active').parent('li').siblings().find('a').removeClass('wpsp_top_nav_tab_active');
		$(target).addClass('wpsp_nav_tab_content_active').siblings().removeClass('wpsp_nav_tab_content_active');
	});

	if(jQuery.type(get_query_vars('page')) !== 'undefined') {
		var query_vars = get_query_vars('page').split('#');
		if (query_vars[0] === 'wp-scheduled-posts') {
			var query_ID = (query_vars[1] !== undefined ?  query_vars[1].split('?') : '');
			$('.wpsp_top_nav_link_wrapper ul li[data-tab="' + query_ID[0] + '"]').trigger('click');
		}

	}


	// timepicker function
	$('#man_times').chungTimePicker({
		viewType: 1,
	});

	// check active which option
	$("#pts_form input:checkbox,#man_form input:checkbox").click(function () {
		var this_name = $(this).attr("name");


		var both = $('.wpsp-checkbox-wrapper .checkbox-toggle input[type="checkbox"]');
		//both value null
		both.each(function(index) {
			both[index].value = '';
		})

		$(this).val('ok');

		if (this_name === 'pub_check') {
			if (this.checked == false) {
				$(this).attr("checked", false);
				$(this).val('');
				$('#cal_check').attr("checked", true);
				$('#cal_check').val('ok');
			} else {
				$(this).attr("checked", true);
				$(this).val('ok');
				$('#cal_check').attr("checked", false);
				$('#cal_check').val('');
			}

		} else if (this_name === 'cal_check') {

			if (this.checked == false) {
				$(this).attr("checked", false);
				$(this).val('');
				$('#pub_check').attr("checked", true);
				$('#pub_check').val('ok');
			} else {
				$().attr("checked", true);
				$(this).val('ok');
				$('#pub_check').attr("checked", false);
				$('#pub_check').val('');
			}
		} else {
			return true;
		}
	});

	

	// schedule post checkbox event
	$('#schedule_click_button').change(function() {
		// this will contain a reference to the checkbox   
		if (this.checked) {
			$('#publish').attr('value', 'Schedule');
		} else {
			$('#publish').attr('value', 'Publish');
		}
	});

	/**
	 * fb access key token generator
	 */
	wpscp_fb_acess_key_toggle();
	function wpscp_fb_acess_key_toggle(){
		var appType1st = $('input:checked[type=radio][name=wpscp_pro_app_type][data-id=first]');
		var appType2nd = $('input:checked[type=radio][name=wpscp_pro_app_type][data-id=second]');
		if(appType2nd.val() == 'userapp') {
			if($('#fb_app_id').val() == ""){
				$('#fbAcessTokenGen').hide();
				$('#acessTokenNotice').show();
			} else {
				$('#fbAcessTokenGen').show();
				$('#acessTokenNotice').hide();
			}
		}
		else if(appType1st.val() == 'wpscpapp'){
			$('#fbAcessTokenGen').show();
			$('#acessTokenNotice').hide();
		}
	}

	/**
	 * Access Token Generate Show/Hide
	 */
	$('input[type=radio][name=wpscp_pro_app_type]').change(function() {
		if (this.value == 'wpscpapp') {
			$('#fbAcessTokenGen').show();
			$('#acessTokenNotice').hide();
		}
		else if (this.value == 'userapp') {
			wpscp_fb_acess_key_toggle();
		}
	});



	/**
	 * Add TimePicker
	 */
	jQuery('#wpsp_time').timepicker({
    	timeFormat: 'h:mm p',
	});
	
	/**
	 * Calendar quick edit time
	 */
	jQuery(document).on('click', '#external-events-listing a.wpscpquickedit', function(){
		jQuery('#timeEditControls').hide();
	});
	jQuery(document).on('click', '#calendar a.wpscpquickedit', function(){
		jQuery('#timeEditControls').show();
	});
    jQuery('a[rel="modal:open"]').on('click', function(){
		jQuery('#wpsp-status').val('Draft');
    	jQuery('#timeEditControls').hide();
	});
    jQuery('a[rel="modal:close"]').on('click', function(){
    	jQuery('#timeEditControls').hide();
	});
	// Email Notify settings
	jQuery('#notify_author_is_sent_review input[type="checkbox"]').on('click', function(){
		if(this.checked === true){
			jQuery('#notify_author_post_is_review_option_area').show();
		} else {
			jQuery('#notify_author_post_is_review_option_area').hide();
		}
	});
	jQuery('#notify_author_post_is_schedule input[type="checkbox"]').on('click', function(){
		if(this.checked === true){
			jQuery('#notify_author_post_is_schedule_option_area').show();
		} else {
			jQuery('#notify_author_post_is_schedule_option_area').hide();
		}
	});
	jQuery('#wpscp_notify_sender_email input[name="notify_sender_email_address"]').on('keypress', function(){
		jQuery('.notify-email-warning').show();
	});
});

function wpscp_formatDate_from_string(strr) {
	var date = new Date(strr);
	var monthNames = [
	  "January", "February", "March",
	  "April", "May", "June", "July",
	  "August", "September", "October",
	  "November", "December"
	];
  
	var day = date.getDate();
	var monthIndex = date.getMonth();
	var year = date.getFullYear();
  
	return day + ' ' + monthNames[monthIndex] + ' ' + year;
  }

/**
 * Notification For Calendar
 * @param {*} obj 
 */
function wpscp_calendar_notifi(obj){
	var error = false;
	var content = '';
	switch(obj.type) {
		case 'newpost':
			content = 'New Future Post has been successfully Created';
		  break;
		case 'updatepost':
			content = 'Your Post has been successfully Updated';
		  break;
		case 'future_post_update':
			content = 'Your Post has been successfully moved to "'+wpscp_formatDate_from_string(obj.post_date)+'"';
		  break;
		case 'draft_new_post':
			content = 'New Draft Post has been successfully Created';
		  break;
		case 'draft_post_update':
			content = 'Your Post has been successfully Updated. Saved As Draft Mode';
		  break;
		case 'post_delete':
			content = 'Your Post has been successfully Deleted';
		  break;
		case 'future_to_draft':
			content = 'Your Post Status has been changed to ' + obj.post_status;
		  break;
		case 'draft_to_future':
			content = 'Your Post Status has been changed to ' + obj.post_status;
		  break;
		default:
			content = '';
	  }

	if(!error) {
		jQuery.notifi(content,{
			autoHideDelay:	5000,
		});
	}else {
		jQuery.notifi(content,{
			autoHideDelay:		5000,
			noticeClass:		'ntf-warning'
		});
	}
}