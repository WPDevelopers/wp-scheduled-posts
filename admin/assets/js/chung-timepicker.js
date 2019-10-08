/*
 * author: Lin.Chung
 * email: zjl_java@163.com
 * address: https://github.com/zhongjinglin/chungTimePicker
 * date: 2017/9/28
 * version: 1.0
 */

! function($) {

	'use strict';

	$.chungTimePicker = function(element, options) {

		//定义变量
		var obj = {
			ele: $(element),
			hour: ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'],
			minute: ['00', '05', '10', '15', '20', '25', '30', '35', '40', '45', '50', '55'],
			errorPrefix: 'ChungTimePicker(error msg): ',
			timepicker: $('<div class="chung-timepicker"></div>'),
			timepicker_hours: $('<div class="chung-timepicker-hours"></div>'),
			timepicker_minutes: $('<div class="chung-timepicker-minutes"></div>'),
			timepicker_bottom: $('<div class="chung-timepicker-bottom"></div>'),
			options: $.extend({}, $.fn.chungTimePicker.defaults, typeof options == 'object' && options),
			originalValue: ''
		}

		//点击其他元素的时候移除控件
		$(document).click(function() {
			obj.timepicker.remove();
		});

		//重置控件偏移量
		$(window).resize(function() {
			setOffset();
		});

		//为元素绑定点击事件，当点击的时候生成时间控件
		obj.ele.on('click', function(event) {
			//阻止移除操作
			event.preventDefault();
			event.stopPropagation();

			$('.chung-timepicker').remove();

			obj.originalValue = obj.ele.val();

			init();

			//在控件范围内发生点击事件的时候阻止移除操作
			obj.timepicker.on('click', function(event) {
				event.preventDefault();
				event.stopPropagation();
			});

		})

		//初始化时间控件
		function init() {
			createHoursWrap();
			createMinutesWrap();
			createBottomOperate();

			obj.timepicker.append(obj.timepicker_hours)
				.append(obj.timepicker_minutes)
				.append(obj.timepicker_bottom);

			obj.timepicker_hours.removeAttr('style');
			obj.timepicker_minutes.removeAttr('style');

			switch(obj.options.viewType) {
				case 0:
					obj.timepicker_minutes.css('display', 'none');
					break;
				case 1:
					obj.timepicker_hours.css({
						'display': 'inline-block'
					});
					obj.timepicker_minutes.css({
						'display': 'inline-block',
						'margin-left': '20px'
					});
					break;
				default:
					console.log(obj.errorPrefix + 'viewType Error');
					break;
			}

			setOffset();

			$('body').append(obj.timepicker);

			hoursEvent();
			minutesEvent();
			backHoursView();
			cleanBtnEvent();
		}

		//创建时视图面板
		function createHoursWrap() {
			var table = $('<table class="table-condensed"></table>'),
				title = $('<thead><tr><th colspan="6" class="title">Hour</th></tr></thead>'),
				hours = $('<tbody></tbody>'),
				tempVal = obj.ele.val().split(":")[0],
				td = '';

			for(var i = 0; i < obj.hour.length; i++) {
				if(tempVal == obj.hour[i]) {
					td += '<td class="active">' + obj.hour[i] + '</td>';
				} else {
					td += '<td>' + obj.hour[i] + '</td>';
				}
				if((i + 1) % obj.options.rowCount == 0) {
					var tr = $('<tr></tr>');
					tr.append(td);
					hours.append(tr);
					td = '';
				}
			}

			if(td != '') {
				var tr = $('<tr></tr>');
				tr.append(td);
				hours.append(tr);
			}

			table.append(title)
				.append(hours);

			obj.timepicker_hours.empty().append(table);
		}

		//创建分视图面板
		function createMinutesWrap() {
			var table = $('<table class="table-condensed"></table>'),
				title = $('<thead><tr></tr></thead>'),
				hours = $('<tbody></tbody>'),
				tempVal = obj.ele.val().split(":")[1],
				td = '';

			switch(obj.options.viewType) {
				case 0:
					title.find('tr').append('<th class="prev js-back-hours"><i class="icon-arrow-left"></i></th><th colspan="4" class="title">Minute</th>');
					break;
				case 1:
					title.find('tr').append('<th colspan="6" class="title">Minute</th>');
					break;
				default:
					console.log(obj.errorPrefix + 'viewType Error');
					break;
			}

			for(var i = 0; i < obj.minute.length; i++) {
				if(tempVal == obj.minute[i]) {
					td += '<td class="active">' + obj.minute[i] + '</td>';
				} else {
					td += '<td>' + obj.minute[i] + '</td>';
				}
				if((i + 1) % obj.options.rowCount == 0) {
					var tr = $('<tr></tr>');
					tr.append(td);
					hours.append(tr);
					td = '';
				}
			}

			if(td != '') {
				var tr = $('<tr></tr>');
				tr.append(td);
				hours.append(tr);
			}

			table.append(title)
				.append(hours);

			obj.timepicker_minutes.empty().append(table);
		}

		//创建底部操作按钮面板
		function createBottomOperate() {
			switch(obj.options.viewType) {
				case 0:
					obj.timepicker_bottom.empty().append('<span class="bottom-btn js-clear">Clear</span>')
						.append('<span class="bottom-btn js-cancel">Cancel</span>');
					break;
				case 1:
					obj.timepicker_bottom.empty().append('<span class="bottom-btn js-clear">Clear</span>')
						.append('<span class="bottom-btn js-cancel">Cancel</span>')
						.append('<span class="bottom-btn js-confirm">Okay</span>');

					break;
				default:
					console.log(obj.errorPrefix + 'viewType Error.');
					break;
			}
		}

		//绑定小时面板点击事件
		function hoursEvent() {
			obj.timepicker_hours.on('click', 'td', function(event) {
				event.preventDefault();
				event.stopPropagation();

				var _this = $(this);

				obj.timepicker_hours.find('td').removeClass('active');
				_this.addClass('active');

				var hourValue = _this.text().trim();
				var temp = obj.ele.val().split(":");
				if(temp.length > 1) {
					obj.ele.val(hourValue + ":" + temp[1]);
				} else {
					obj.ele.val(hourValue + ":00");
				}

				if(obj.options.viewType == 0) {
					obj.timepicker_hours.hide();
					obj.timepicker_minutes.show();
				}

				return false;
			});
		}

		//分钟面板点击事件
		function minutesEvent() {
			obj.timepicker_minutes.on('click', 'td', function(event) {
				event.preventDefault();
				event.stopPropagation();

				var _this = $(this);

				obj.timepicker_minutes.find('td').removeClass('active');
				_this.addClass('active');

				var minutesValue = _this.text().trim();
				obj.ele.val(obj.ele.val().split(":")[0] + ":" + minutesValue);

				if(obj.options.viewType == 0) {
					obj.timepicker.remove();
					if(obj.options.callback) obj.options.callback(obj.ele);
				}

				return false;
			});
		}

		//返回小时视图事件
		function backHoursView() {
			if(obj.options.viewType == 0) {
				obj.timepicker_minutes.on('click', '.js-back-hours', function() {
					obj.timepicker_minutes.hide();
					obj.timepicker_hours.show();
				});
			}
		}

		//确定，清除，取消事件
		function cleanBtnEvent() {
			obj.timepicker_bottom.on('click', '.js-confirm,.js-clear,.js-cancel', function(event) {
				event.preventDefault();
				event.stopPropagation();

				var _this = $(this);

				if(_this.hasClass('js-confirm')) {
					obj.timepicker.remove();
					if(obj.options.confirmCallback) obj.options.confirmCallback(obj.ele);

				} else if(_this.hasClass('js-clear')) {
					obj.ele.val('');
					obj.timepicker.remove();
					if(obj.options.clearCallback) obj.options.clearCallback(obj.ele);

				} else if(_this.hasClass('js-cancel')) {
					obj.ele.val(obj.originalValue);
					obj.timepicker.remove();
					if(obj.options.cancelCallback) obj.options.cancelCallback(obj.ele);

				} else {
					console.log(obj.errorPrefix + 'Error');
				}

				return false;
			});
		}

		//设置控件视图偏移量
		function setOffset() {
			var offset = obj.ele.offset();

			obj.timepicker.css({
				'left': offset.left,
				'top': offset.top + obj.ele.outerHeight()
			});
		}

	};

	//jQuery扩展
	$.fn.extend({
		chungTimePicker: function(options) {
			this.each(function() {
				new $.chungTimePicker(this, options);
			});
			return this;
		}
	});

	//默认参数
	$.fn.chungTimePicker.defaults = {
		viewType: 0, //视图显示类型，0：两个视图显示，1：一个视图显示，即小时和分钟在一个面板
		rowCount: 6, //每行显示的数量
	};

}(window.jQuery);
