jQuery(document).ready(function($) {
	// timepicker function
	$('#man_times').chungTimePicker({
		viewType: 1,
	});

	// sweet alert js

	$('.swal_alert_show').on('click',function(){
		swal({
	  		title: '<h2><span>Go</span> Premium',
	  		type: 'warning',
	  		html:
	    		'Purchase our <b><a href="https://wpdeveloper.net/in/wpsp" rel="nofollow" target="_blank">premium version</a></b> to unlock these pro features!',
	  		showCloseButton: true,
	  		showCancelButton: false,
	  		focusConfirm: true,
		});
	})

	// check active which option
	$("#pts_form input:checkbox,#man_form input:checkbox").click(function(){
		var this_name = $(this).attr("name");
		//alert(this_name);
		if(this_name === 'pub_check')
		{
			$('#pub_check').attr("checked",true);
			$('#cal_check').attr("checked",false);
		}else if(this_name === 'cal_check'){
			$('#cal_check').attr("checked",true);
			$('#pub_check').attr("checked",false);
		}else{
			return true;
		}
	    
	});

	//on click missed schedule checkbox button send ajax
	$('.miss-schedule-form .checkbox-toggle input[type="checkbox"]').on('change', function(e) {
		e.preventDefault();
		var value = '';

		if(this.checked) {
			value = 'yes';
		}else{
			value = '';
		}

		var missed_sched_check_uncheck = {
			action: 'missedScheduleVal',
			missed_sched_val: value,
		};

		$.post(ajax_url, missed_sched_check_uncheck, function (msg) {
				
		}, 'json');

	})
	
	
});





