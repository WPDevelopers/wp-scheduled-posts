document.addEventListener('DOMContentLoaded', function () {
    var Calendar = FullCalendar.Calendar
    var Draggable = FullCalendarInteraction.Draggable
    var containerEl = document.getElementById('external-events')
    var calendarEl = document.getElementById('calendar')
    if (jQuery(calendarEl).length == 0) {
        return
    }
    //center any element in jquery center function
    jQuery.fn.center = function (parent) {
        if (parent) {
            parent = this.parent()
        } else {
            parent = window
        }
        this.css({
            position: 'absolute',
            top:
                (jQuery(parent).height() - this.outerHeight()) / 2 +
                jQuery(parent).scrollTop() +
                'px',
            left:
                (jQuery(parent).width() - this.outerWidth()) / 2 +
                jQuery(parent).scrollLeft() +
                'px',
        })
        return this
    }

    /**
     * initialize the external events
     */
    new Draggable(containerEl, {
        itemSelector: '.fc-event',
        eventData: function (eventEl) {
            return {
                title: eventEl.innerHTML,
            }
        },
    })

    /**
     * initialize the main calendar
     */
    var calendar = new Calendar(calendarEl, {
        plugins: ['interaction', 'dayGrid', 'timeGrid'],
        header: {
            left: 'prev,next today',
            center: 'title',
            // right: 'dayGridMonth'
        },
        lazyFetching: true,
        displayEventTime: true,
        editable: true,
        droppable: true, // this allows things to be dropped onto the calendar
        textEscape: true,
        dragRevertDuration: 0,
        eventLimit: true, // for all non-TimeGrid views
        views: {
            dayGrid: {
                eventLimit: 3,
            },
        },
        drop: function (info) {
            // old event remove after drop
            info.draggedEl.parentNode.removeChild(info.draggedEl)
            // send ajax request
            var eventDate = new Date(info.date)
            jQuery('*[data-date="' + wpscpFormatDate(eventDate) + '"]')
                .children('.spinner')
                .css('visibility', 'visible')
            wpscp_calender_ajax_request({
                ID: jQuery(info.draggedEl)
                    .find('.wpscp-event-post')
                    .data('postid'),
                type: 'drop',
                date: info.date,
                post_type: _wpscpGetPostTypeName(info.draggedEl),
            })
        },
        eventRender: function (info) {
            info.el.firstChild.innerHTML = info.event._def.title
        },
        eventDragStart: function () {
            jQuery('#external-events').addClass('highlight')
        },
        eventDragStop: function (info) {
            if (isEventOverDiv(info.jsEvent.clientX, info.jsEvent.clientY)) {
                info.event.remove()
                var el = jQuery("<div class='fc-event'>")
                    .appendTo('#external-events-listing')
                    .html('<div id="draft_loading">Loading....</div>')
                el.draggable({
                    zIndex: 999,
                    revert: true,
                    revertDuration: 0,
                })
                el.data('event', {
                    title: info.event.title,
                    id: info.event.ID,
                    stick: true,
                })
                // send ajax request
                jQuery('#external-events-listing .spinner').css(
                    'visibility',
                    'visible'
                )
                wpscp_calender_ajax_request({
                    ID: jQuery(info.el)
                        .find('.wpscp-event-post')
                        .data('postid'),
                    type: 'draftDrop',
                    post_type: _wpscpGetPostTypeName(info.el),
                })
            }
        },
        eventDrop: function (info) {
            var eventDate = new Date(info.event.start)
            jQuery('*[data-date="' + wpscpFormatDate(eventDate) + '"]')
                .children('.spinner')
                .css('visibility', 'visible')
            // send ajax request
            jQuery(info.el).prev('.spinner').css('visibility', 'visible')
            wpscp_calender_ajax_request({
                ID: jQuery(info.el).find('.wpscp-event-post').data('postid'),
                post_status: 'Scheduled',
                post_type: _wpscpGetPostTypeName(info.el),
                type: 'eventDrop',
                date: new Date(
                    info.event.end ? info.event.end : info.event.start
                ),
            })
        },
    })
    calendar.render()
    // end main calendar functionality

    /**
     * Necessary Functions for Calendar and ajax call
     */

    /*
     * add Future Post Event Via ajax Call
     */
    function wpscpAllEvents() {
        jQuery
            .ajax({
                url: wpscpGetRestUrl(),
            })
            .done(function (data, status) {
                if (status == 'success') {
                    calendar.addEventSource(data)
                    jQuery('.wpsp_calendar_loader').fadeOut()
                } else {
                    jQuery('.wpsp_calendar_loader').fadeOut()
                }
            })
    }
    wpscpAllEvents()

    /*
     * Check Dragable Event is out of calendar div
     */
    var isEventOverDiv = function (x, y) {
        var external_events = jQuery('#external-events')
        var offset = external_events.offset()
        offset.right = external_events.width() + offset.left
        offset.bottom = external_events.height() + offset.top
        // Compare
        if (
            x >= offset.left &&
            // && y <= offset.top
            x <= offset.right &&
            y <= offset.bottom
        ) {
            return true
        }
        return false
    }

    /**
     * Calendar Event markup
     * @param {*} obj
     */
    function wpscpEventTemplateStructure(obj) {
        var trimTitle =
            obj.post_title !== ''
                ? obj.post_title.length > 20
                    ? obj.post_title.slice(0, 20) + '...'
                    : obj.post_title
                : '';

        var markup = ''
        markup += '<div class="wpscp-event-post" data-postid="' + obj.ID + '" data-post-type="' + obj.post_type + '" data-terms=\'' + JSON.stringify(obj.taxonomies || {}) + '\'>'
        markup +=
            '<div class="postlink "><span><span class="posttime">[' +
            wpscpFormatAMPM(new Date(obj.post_date.replace(/-/g, '/'))) +
            ']</span> ' +
            trimTitle +
            ' [' +
            obj.post_status +
            ']</span></div>'
        var link = ''
        link +=
            '<div class="edit"><a href="' +
            wpscpGetHomeUrl() +
            '/wp-admin/post.php?post=' +
            obj.ID +
            '&action=edit"><i class="dashicons dashicons-edit"></i>Edit</a><a class="wpscpquickedit" href="#" data-type="quickedit"><i class="dashicons dashicons-welcome-write-blog"></i>Quick Edit</a></div>'
        link +=
            '<div class="deleteview"><a class="wpscpEventDelete" href="#"><i class="dashicons dashicons-trash"></i> Delete</a><a href="' +
            obj.guid +
            '"><i class="dashicons dashicons-admin-links"></i> View</a></div>'
        markup += '<div class="postactions"><div>' + link + '</div></div>'
        markup += '</div>'
        return markup
    }
    /**
     * Main function for calendar ajax request
     * @param {*} obj
     */
    function wpscp_calender_ajax_request(obj) {
        var data = {
            action: 'wpscp_calender_ajax_request',
            nonce: wpscp_calendar_ajax_object.nonce,
            post_type: obj.post_type || 'post',
            post_status: obj.post_status,
            type: obj.type,
            date: obj.date,
            time: obj.time,
            ID: obj.ID,
            postTitle: obj.postTitle,
            postContent: obj.postContent,
        };

        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        jQuery.post(
            wpscp_calendar_ajax_object.ajax_url,
            data,
            function (response, status) {

                if (status === 'success' && response) {
                    var jsonData =
                        response !== null && response !== ''
                            ? JSON.parse(response)
                            : [];
                    jsonData[0].post_date = jsonData[0].post_date.replace(/-/g, '/');

                    if (
                        obj.type === 'addEvent' &&
                        obj.post_status === 'Scheduled'
                    ) {

                        var notifi_type = 'newpost'
                        // if find post id then it will be post update action
                        if (obj.ID !== '') {
                            notifi_type = 'updatepost'
                            // remove event
                            var allevents = calendar.getEvents()
                            allevents.forEach(function (el) {
                                var eventTitle = el.title
                                if (
                                    jQuery(eventTitle).data('postid') == obj.ID
                                ) {
                                    el.remove()
                                }
                            })
                        }
                        // add event
                        calendar.addEvent({
                            title: wpscpEventTemplateStructure(jsonData[0]),
                            start: new Date(
                                jsonData[0].post_date.split(' ')[0].replace(/-/g, '/')
                            ),
                            end: new Date(jsonData[0].post_date.replace(/-/g, '/')),
                            allDay: false,
                        })

                        // send notification
                        wpscp_calendar_notifi({
                            type: notifi_type,
                            post_status: obj.post_status,
                            post_date: jsonData[0].post_date.replace(/-/g, '/'),
                        })
                    } else if (obj.type === 'drop') {
                        // remove old
                        if (obj.ID !== '') {
                            // remove event
                            var allevents = calendar.getEvents()
                            allevents.forEach(function (el) {
                                var eventTitle = el.title
                                if (
                                    jQuery(eventTitle).data('postid') == obj.ID
                                ) {
                                    el.remove()
                                }
                            })
                        }
                        // add event
                        calendar.addEvent({
                            title: wpscpEventTemplateStructure(jsonData[0]),
                            start: new Date(
                                jsonData[0].post_date.split(' ')[0].replace(/-/g, '/')
                            ),
                            end: new Date(jsonData[0].post_date.replace(/-/g, '/')),
                            allDay: false,
                        })
                        // send notification
                        wpscp_calendar_notifi({
                            type: 'draft_to_future',
                            post_status:
                                jsonData.length > 0
                                    ? jsonData[0].post_status
                                    : 'future',
                            post_date: obj.date,
                        })
                    } else if (obj.type === 'eventDrop') {
                        if (obj.ID !== '') {
                            // remove event
                            var allevents = calendar.getEvents()
                            allevents.forEach(function (el) {
                                var eventTitle = el.title
                                if (
                                    jQuery(eventTitle).data('postid') == obj.ID
                                ) {
                                    el.remove()
                                }
                            })
                        }
                        // add event
                        calendar.addEvent({
                            title: wpscpEventTemplateStructure(jsonData[0]),
                            start: new Date(
                                jsonData[0].post_date.split(' ')[0].replace(/-/g, '/')
                            ),
                            end: new Date(jsonData[0].post_date.replace(/-/g, '/')),
                            allDay: false,
                        })
                        // send notification
                        wpscp_calendar_notifi({
                            type: 'future_post_update',
                            post_status:
                                jsonData.length > 0
                                    ? jsonData[0].post_status
                                    : 'future',
                            post_date: obj.date,
                        })
                    } else if (obj.type === 'draftDrop') {
                        jQuery('#draft_loading').parent().remove()
                        jQuery("<div class='fc-event'>")
                            .appendTo('#external-events-listing')
                            .html(wpscpEventTemplateStructure(jsonData[0]))
                        // send notification
                        wpscp_calendar_notifi({
                            type: 'future_to_draft',
                            post_status:
                                jsonData.length > 0
                                    ? jsonData[0].post_status
                                    : 'draft',
                        })
                    } else if (obj.post_status === 'Draft') {
                        jQuery("<div class='fc-event'>")
                            .appendTo('#external-events-listing')
                            .html(wpscpEventTemplateStructure(jsonData[0]))
                        // send notification
                        wpscp_calendar_notifi({
                            type:
                                obj.ID !== ''
                                    ? 'draft_post_update'
                                    : 'draft_new_post',
                            post_status:
                                jsonData.length > 0
                                    ? jsonData[0].post_status
                                    : 'draft',
                        })
                    }
                    // hide all spinner after complete ajax request
                    jQuery('.spinner').css('visibility', 'hidden')
                    jQuery('#external-events').removeClass('highlight')
                }
            }
        )
    }

    /**
     * Add New Post Button Button
     */

    jQuery('.fc-button').on('click', function (e) {
        e.preventDefault()
        //calling 'new post' btn in calendqr item
        wpscpNewPostBtn()
    })
    //calling 'new post' btn in calendqr item
    wpscpNewPostBtn()
    //add 'new post' btn in calendqr item
    function wpscpNewPostBtn() {
        var items = document.querySelectorAll('.fc-day-grid td.fc-day-top')
        if (items) {
            items.forEach(function (item) {
                // spainner generate
                var spinner = document.createElement('span')
                spinner.classList.add('spinner')
                // anchor link generate
                var newPostBtn = document.createElement('a')
                newPostBtn.classList.add('daynewlink')
                newPostBtn.setAttribute('href', '#wpscp_quickedit')
                newPostBtn.setAttribute('rel', 'modal:open')
                newPostBtn.textContent = 'Add New'

                var anchor = item.querySelector('.daynewlink')
                var hasBtnClass = item.contains(anchor)

                if (hasBtnClass === false) {
                    item.append(spinner)
                    item.append(newPostBtn)
                }
            })
        }
    }

    // necessary selector for calendar
    var dayNewLink = jQuery('.daynewlink')
    var modalClose = jQuery('*[rel="modal:close"]')
    var modalCloseClass = jQuery('.close-modal')
    var modalDate = jQuery('#date')
    var postID = jQuery('#postID')
    var modalTitle = jQuery('#title')
    var modalContent = jQuery('#content')
    var modalTime = jQuery('#wpsp_time')
    var modalStatus = jQuery('#wpsp-status')
    var modalSubmit = jQuery('#wpcNewPostScheduleButton')
    var quickEdit = jQuery('#wpsp_quickedit')

    // wpscp_calendar_modal
    wpscp_calendar_modal()
    // modal submit
    wpscp_calendar_modal_submit()

    /**
     * After Modal hide this function will be fire
     */
    function wpscp_calendar_modal_hide() {
        modalTitle.val('')
        modalContent.val('')
        // modalTime.val('')
        modalStatus.val('')
        modalDate.val('')
        postID.val('')
        jQuery.modal.close()
    }
    /**
     * Showing Calendar Event Modal
     */
    function wpscp_calendar_modal() {
        jQuery(document).on('click', 'a.daynewlink', function (e) {
            e.preventDefault();
            if (jQuery(this).data('type') !== 'Draft') {
                jQuery('select#wpsp-status').val('Scheduled')
                jQuery('#timeEditControls').show()
                jQuery('#wpsp_time').timepicker({})
            }
            jQuery(this).prev('.spinner').css('visibility', 'visible')
            modalDate.val(jQuery(this).parent('.fc-day-top').data('date'))
        })
        modalClose.add(modalCloseClass).on('click', function (e) {
            e.preventDefault()
            //hide modal
            wpscp_calendar_modal_hide()
            jQuery('.spinner').css('visibility', 'hidden')
        })
    }
    /**
     * Calendar Modal Popup Submit
     */
    function wpscp_calendar_modal_submit() {
        // quick post submit button
        modalSubmit.on('click', function (e) {
            e.preventDefault()
            var dateTime = wpscpFormat24Hours(
                modalTime.val() !== '' ? modalTime.val() : '12:00 AM'
            );

            var dateStr = new Date(modalDate.val().replace(/-/g, '/') + ' ' + dateTime);

            if (dateStr == 'Invalid Date') {
                dateStr = new Date();
            }
            jQuery('*[data-date="' + modalDate.val() + '"]')
                .children('.spinner')
                .css('visibility', 'visible')
            // send ajax request
            wpscp_calender_ajax_request({
                type: 'addEvent',
                date: dateStr,
                time: dateTime,
                post_status: modalStatus.val(),
                ID: postID.val(),
                postTitle: modalTitle.val(),
                post_type: wpscpGetPostTypeName(postID.val()),
                postContent: modalContent.val(),
            })

            // hide modal
            wpscp_calendar_modal_hide()
        })
    }

    /**
     * Date Format Change
     * @param {date string} date
     */
    function wpscpFormatDate(date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear()

        if (month.length < 2) month = '0' + month
        if (day.length < 2) day = '0' + day

        return [year, month, day].join('-')
    }
    /**
     * Date Format AmPm
     * @param {date string} date
     */
    function wpscpFormatAMPM(date) {
        var hours = date.getHours();
        var minutes = date.getMinutes();
        var ampm = hours >= 12 ? 'pm' : 'am'
        hours = hours % 12
        hours = hours ? hours : 12 // the hour '0' should be '12'
        minutes = minutes < 10 ? '0' + minutes : minutes
        var strTime = hours + ':' + minutes + ' ' + ampm
        return strTime
    }
    function wpscpFormat24Hours(time) {
        if (time == '') {
            return
        }
        if (time.length == 5) {
            return time
        }
        var hours = Number(time.match(/^(\d+)/)[1])
        var minutes = Number(time.match(/:(\d+)/)[1])
        var AMPM = time.match(/\s(.*)$/)[1]
        if ((AMPM == 'PM' || AMPM == 'pm') && hours < 12) hours = hours + 12
        if ((AMPM == 'AM' || AMPM == 'am') && hours == 12) hours = hours - 12
        var sHours = hours.toString()
        var sMinutes = minutes.toString()
        if (hours < 10) sHours = '0' + sHours
        if (minutes < 10) sMinutes = '0' + sMinutes
        return sHours + ':' + sMinutes
    }

    /**
     * Get Rest URL
     */
    function wpscpGetRestUrl(month = null, year = null) {
        return wpscpGetPostTypeNameSkipUnderScore(
            wpscp_calendar_ajax_object.calendar_rest_route,
            month,
            year
        )
    }
    /**
     * Get Post Type Name form Query Sting
     */
    function wpscpGetPostTypeNameSkipUnderScore(oldRestUrl, month, year) {
        var urlParams = new URLSearchParams(window.location.search)
        var postTypeName =
            urlParams.get('post_type') == 'elementor_library'
                ? 'elementorlibrary'
                : urlParams.get('post_type')
        if(postTypeName == null){
            if(urlParams.get('page') == 'schedulepress-calendar'){
                postTypeName = 'all';
            }
        }
        var updateRestUrl =
            postTypeName !== null
                ? oldRestUrl.replace(
                      'post_type=post',
                      'post_type=' + postTypeName
                  )
                : oldRestUrl

        if (month !== null) {
            updateRestUrl = updateRestUrl.replace(
                /month=[0-9]{2}/i,
                'month=' + month
            )
        }
        if (year !== null) {
            updateRestUrl = updateRestUrl.replace(
                /year=[0-9]{4}/i,
                'year=' + year
            )
        }

        return updateRestUrl
    }

    /**
     * Get Post Type Name form Query Sting
     */
    function _wpscpGetPostTypeName(elem) {
        var selector = '[data-post-type], .wpscp-event-post';
        var postType = jQuery(elem).closest(selector).data('post-type') ||
                       jQuery(elem).find(selector).data('post-type');
        return postType;
    }
    /**
     * Get Post Type Name form Query Sting
     */
    function wpscpGetPostTypeName(postID) {
        var urlParams = new URLSearchParams(window.location.search)
        var postTypeName = urlParams.get('post_type')
        if(!postTypeName && postID){
            postTypeName = jQuery('[data-postid=' + postID + ']').data('post-type');
        }
        return postTypeName == null || postTypeName == ''
            ? 'post'
            : postTypeName
    }

    /**
     * Get WP Host URL for hit restapi endpoint
     */
    function wpscpGetHomeUrl() {
        var href = window.location.href
        var index = href.indexOf('/wp-admin')
        var homeUrl = href.substring(0, index)
        return homeUrl
    }

    /**
     * Ajax Request For Update Post
     */
    jQuery(document).on('click', 'a.wpscpquickedit', function (e) {
        e.preventDefault()
        var editPostId = jQuery(this)
            .closest('[data-postid], .wpscp-event-post')
            .data('postid')
        var postType = jQuery(this)
            .closest('[data-post-type], .wpscp-event-post')
            .data('post-type')
        var data = {
            action: 'wpscp_quick_edit',
            post_type: postType,
            ID: editPostId,
        }

        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        jQuery.post(
            wpscp_calendar_ajax_object.ajax_url,
            data,
            function (response, status) {
                if (status === 'success') {
                    var jsonData = response !== '' ? JSON.parse(response) : []
                    var PostDate = jsonData[0].post_date
                    var PostDateArray = PostDate.split(' ')
                    modalTitle.val(jsonData[0].post_title)
                    modalContent.val(jsonData[0].post_content)
                    modalDate.val(PostDateArray[0])
                    postID.val(jsonData[0].ID)
                    modalTime.val(
                        wpscpFormatAMPM(new Date(jsonData[0].post_date.replace(/-/g, '/')))
                    )
                    modalStatus.val(
                        jsonData[0].post_status === 'future'
                            ? 'Scheduled'
                            : jsonData[0].post_status
                    )
                    jQuery('#wpscp_quickedit').modal('show')
                }
            }
        )
    })

    /**
     * Ajax Request For Delete Post
     */
    jQuery(document).on('click', 'a.wpscpEventDelete', function (e) {
        e.preventDefault()
        var deletePostId = jQuery(this)
            .closest('[data-postid], .wpscp-event-post')
            .data('postid')
        swal({
            title: 'Are you sure want to delete?',
            text: 'You will not be able to recover!',
            icon: 'warning',
            buttons: ['Cancel', 'Delete'],
        }).then(function (value) {
            if (value) {
                var data = {
                    action: 'wpscp_delete_event',
                    ID: deletePostId,
                }

                // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                jQuery.post(
                    wpscp_calendar_ajax_object.ajax_url,
                    data,
                    function (response, status) {
                        if (status == 'success' && !isNaN(response)) {
                            jQuery('*[data-postid="' + response + '"]')
                                .closest('.fc-event')
                                .remove()
                            // send notification
                            wpscp_calendar_notifi({
                                type: 'post_delete',
                            })
                            swal({
                                title: 'Deleted!',
                                icon: 'success',
                            })
                        } else {
                            swal({
                                title: 'Cancelled!',
                                icon: 'error',
                            })
                        }
                    }
                )
            } else {
                swal({
                    title: 'Cancelled!',
                    icon: 'error',
                })
            }
        })
    })

    /**
     * Next Month, Prev Month
     */

    function wpscp_calendar_data_fetch_by_month() {
        var EventDateList = []
        EventDateList.push(jQuery('.fc-center h2').text())
        var monthList = {
            January: 1,
            February: 2,
            March: 3,
            April: 4,
            May: 5,
            June: 6,
            July: 7,
            August: 8,
            September: 9,
            October: 10,
            November: 11,
            December: 12,
        }
        jQuery(
            'button.fc-next-button.fc-button.fc-button-primary, button.fc-prev-button.fc-button.fc-button-primary'
        ).on('click', function () {
            var CurrentDate = jQuery('.fc-center h2').text()
            if (EventDateList.includes(CurrentDate) === false) {
                EventDateList.push(CurrentDate)

                var CurrentDate = CurrentDate.split(' ')
                var month = monthList[CurrentDate[0]]
                var year = parseInt(CurrentDate[1])

                jQuery
                    .ajax({
                        url: wpscpGetRestUrl(month, year),
                    })
                    .done(function (data, status) {
                        if (status == 'success') {
                            calendar.addEventSource(data)
                            jQuery('.wpsp_calendar_loader').fadeOut()
                        } else {
                            jQuery('.wpsp_calendar_loader').fadeOut()
                        }
                    })
            }
        })
    }
    wpscp_calendar_data_fetch_by_month()
})
