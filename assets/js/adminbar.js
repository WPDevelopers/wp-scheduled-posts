(function($){

	$(document).ready(function() {
		//Admin Topbar menu js
		var barItem = $('#wp-admin-bar-wpscp-default > li');
		$('#wp-admin-bar-wpscp-default > li').each(function(index, element) {
		
			$(this).removeClass('menupop');
			var sub_ul = $(this).children('.ab-sub-wrapper').html();
			$(this).html(sub_ul);

			if(index == 0) {
				$(this).show();
				$(this).addClass('wpsp_pagi_active');
			}else if(index == barItem.length - 1){
				$(this).show();
			}else{
				$(this).hide();

			}

		});



		// Topbar scheduled post item pagination
		var wpsp_arrow_pagis = $('.wpsp_arrow_pagi');
		wpsp_arrow_pagis.each(function(index, element) {
			//by default previous btn should be hide
			$('.wpsp_arrow_prev').hide();

			var pagi_total_items = $('#wp-admin-bar-wpscp-default > li');
			var pagi_total_length = pagi_total_items.length - 1;
			if(pagi_total_length == 1) {
				$(this).hide();
			}
 
			$(this).on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				var pagi_items = $('#wp-admin-bar-wpscp-default > li:not(:last-child)');
				var target_id,prev_btn,next_btn,wpsp_pagi_active,active_id,this_id;
					btn = $(this);
					prev_btn = btn.hasClass('wpsp_arrow_prev');
					next_btn = btn.hasClass('wpsp_arrow_next');
					wpsp_pagi_active = $('.wpsp_pagi_active');


				//$(this).closest('#wp-admin-bar-wpscp').off('mouseenter mouseleave');
				$('#wp-admin-bar-wpscp .ab-sub-wrapper').show()
				$('#wp-admin-bar-wpscp .ab-sub-wrapper').addClass('force-show')
				//current active item's id
				active_id = wpsp_pagi_active.attr('id');

				//update old active item to hide and current item to show by add class
				$('#'+active_id).hide();
				
				
				//loop through all element and find target element
				pagi_items.each(function(index,element) {
					this_id = $(this).attr('id');

					//when click next or prev button get target element id
					if( prev_btn ) {
						wpsp_pagi_active.removeClass('wpsp_pagi_active');
						target_id = wpsp_pagi_active.prev().attr('id');
						
						//update target id to first item
						var first_id = $('#wp-admin-bar-wpscp-default > li').first().attr('id');
						if(target_id == first_id){
							target_id = first_id;
							btn.hide();
							$('.wpsp_arrow_next').show();
						}

					}else if( next_btn ) {
						wpsp_pagi_active.removeClass('wpsp_pagi_active');
						target_id = wpsp_pagi_active.next().attr('id');
						
						//update target id to last item
						var powered_by_id = $('#wp-admin-bar-wpscp-default > li').last().prev().attr('id');
						if(target_id == powered_by_id) {
							target_id = $('#wp-admin-bar-wpscp-default > li').last().prev().attr('id');
							btn.hide();
							
						}
						$('.wpsp_arrow_prev').show();

					}

					$("#"+target_id).addClass('wpsp_pagi_active');

				})

				
			})
		})

		$(document).on('click', function(e) {
			//hide topbar scheduled items 
			if(e.target.id!='wp-admin-bar-wpscp'){
				$('#wp-admin-bar-wpscp .ab-sub-wrapper').removeClass('force-show')
			}
			
		})
	})
}(jQuery))