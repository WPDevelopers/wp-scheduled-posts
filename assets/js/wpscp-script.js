jQuery(document).ready(function ($) {
    var get_query_vars = function (name) {
        var vars = {}
        window.location.href.replace(
            /[?&]+([^=&]+)=([^&]*)/gi,
            function (m, key, value) {
                vars[key] = value
            }
        )
        if (name != '') {
            return vars[name]
        }
        return vars
    }

    //success msg html added
    var wpscp_success_span = document.querySelector('.wpscp-success')
    if (wpscp_success_span) {
        wpscp_success_span.style.display = 'none'
    }

    /* Loader JS */
    $(window).bind('load', function () {
        $('.wpsp_loader').fadeOut(200)
    })

    /* Top Nav Tab Event JS */
    var top_nav_tabs = document.querySelectorAll(
        '.wpsp_top_nav_link_wrapper ul li a'
    )
    var top_nav_contents = document.querySelectorAll('.wpsp_nav_tab_content')
    $('.wpsp_top_nav_link_wrapper ul li').on('click', function () {
        var target = $(this).find('a').attr('href').replace('#', '#wpsp-')
        $(this)
            .find('a')
            .addClass('wpsp_top_nav_tab_active')
            .parent('li')
            .siblings()
            .find('a')
            .removeClass('wpsp_top_nav_tab_active')
        $(target)
            .addClass('wpsp_nav_tab_content_active')
            .siblings()
            .removeClass('wpsp_nav_tab_content_active')
    })

    if (jQuery.type(get_query_vars('page')) !== 'undefined' && jQuery.type(get_query_vars('tab')) !== 'undefined') {
        let sp_page = get_query_vars('page').toLowerCase();
        let tabName = get_query_vars('tab').toLowerCase();
        let hashtable = {
            'general' : 'react-tabs-0',
            'email-notify' : 'react-tabs-2',
            'social-profile' : 'react-tabs-4',
            'social-templates' : 'react-tabs-6',
            'manage-schedule' : 'react-tabs-8',
            'missed-schedule' : 'react-tabs-10',
            'license' : 'react-tabs-12',

        };
        if (sp_page === 'schedulepress' && hashtable[tabName]) {
            $('.react-tabs__tab-list li#' + hashtable[tabName]
            ).trigger('click');
        }
    }

    // schedule post checkbox event
    $('#schedule_click_button').change(function () {
        // this will contain a reference to the checkbox
        if (this.checked) {
            $('#publish').attr('value', 'Schedule')
        } else {
            $('#publish').attr('value', 'Publish')
        }
    })

    /**
     * Calendar quick edit time
     */
    jQuery(document).on(
        'click',
        '#external-events-listing a.wpscpquickedit',
        function () {
            jQuery('#timeEditControls').hide()
        }
    )
    jQuery(document).on('click', '#calendar a.wpscpquickedit', function () {
        jQuery('#timeEditControls').show()
    })
    jQuery('a[rel="modal:open"]').on('click', function () {
        jQuery('#wpsp-status').val('Draft')
        jQuery('#timeEditControls').hide()
    })
    jQuery('a[rel="modal:close"]').on('click', function () {
        jQuery('#timeEditControls').hide()
    })

    /**
     * WP admin sidebar boardname toggle
     */
    jQuery('#wpscppropinterestis').on('click', function () {
        if ($(this).is(':checked')) {
            jQuery('.boardname').show()
        } else {
            jQuery('.boardname').hide()
        }
    })
    jQuery('input[name="pinterestboardtype"]').on('click', function () {
        if ($(this).val() == 'custom' && this.checked == true) {
            $('#wpscppropinterestboardname').show()
        } else {
            $('#wpscppropinterestboardname').hide()
        }
    })

    /**
     * WP admin sidebar Upload Image
     */
    $('body').on('click', '#wpscppro_btn_meta_image_upload', function (e) {
        e.preventDefault()
        var button = $(this),
            custom_uploader = wp
                .media({
                    title: 'Insert image',
                    library: {
                        type: 'image',
                    },
                    button: {
                        text: 'Use this image', // button label text
                    },
                    multiple: false, // for multiple image selection set to true
                })
                .on('select', function () {
                    // it also has "open" and "close" events
                    var attachment = custom_uploader
                        .state()
                        .get('selection')
                        .first()
                        .toJSON()
                    jQuery('#wpscppro_custom_social_share_image').val(
                        attachment.id
                    )
                    jQuery('#wpscpprouploadimagepreviewold').hide()
                    jQuery('#wpscpprouploadimagepreview').html(
                        '<img class="true_pre_image" src="' +
                            attachment.url +
                            '" style="max-width:100%; height: auto; display:block;" />'
                    )
                    $('#wpscppro_btn_remove_meta_image_upload').show()
                })
                .open()
    })
    /**
     * WP admin sidebar Remove Image
     */
    $('body').on(
        'click',
        '#wpscppro_btn_remove_meta_image_upload',
        function (e) {
            e.preventDefault()
            $('#wpscppro_custom_social_share_image').val('')
            $('#wpscpprouploadimagepreviewold').hide()
            $('#wpscpprouploadimagepreview').empty()
            $(this).hide()
        }
    )

    // publish future post immediately
    jQuery('#wpscp-future-post-help-handler').on('click', function () {
        jQuery('#wpscp-future-post-help-info').toggle()
    })
    jQuery('#wpsp_prevent_future_post').on('click', function () {
        if ($(this).prop('checked') == true) {
            jQuery('#wpsp_date_type').slideDown('fast')
        } else if ($(this).prop('checked') == false) {
            jQuery('#wpsp_date_type').slideUp('fast')
        }
    })

    $("#external-events-filter").select2({});
    $("#external-events-filter").on('change', function(e){
        var lists = jQuery('#external-events-listing .fc-event');
        var selectedOptions = jQuery(e.target).find(':selected');
        var selectedOptionsArr = selectedOptions.toArray().map(item => item.value);

        if(selectedOptions.length == 0 || jQuery.inArray('all', selectedOptionsArr) != -1){
            lists.show();
            return;
        }
        else{
            lists.hide();
        }

        lists.each(function(i, element){
            var post = jQuery(element).find('.wpscp-event-post');
            var terms = post.data('terms');
            selectedOptions.each(function(index, elm){
                var tax = jQuery(elm).data('tax');
                var term = elm.value.replace(/(.+?)\.(.+)/, '$2');
                if(typeof terms[tax] != 'undefined' && jQuery.inArray(term, terms[tax]) != -1){
                    jQuery(element).show();
                    return false;
                }
            })
        });
    })
    $("#external-events-filter").trigger('change');
})

function wpscp_formatDate_from_string(strr) {
    var date = new Date(strr)
    var monthNames = [
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December',
    ]

    var day = date.getDate()
    var monthIndex = date.getMonth()
    var year = date.getFullYear()

    return day + ' ' + monthNames[monthIndex] + ' ' + year
}

/**
 * Notification For Calendar
 * @param {*} obj
 */
function wpscp_calendar_notifi(obj) {
    var error = false
    var content = ''
    switch (obj.type) {
        case 'newpost':
            content = 'New Future Post has been successfully Created'
            break
        case 'updatepost':
            content = 'Your Post has been successfully Updated'
            break
        case 'future_post_update':
            content =
                'Your Post has been successfully moved to "' +
                wpscp_formatDate_from_string(obj.post_date) +
                '"'
            break
        case 'draft_new_post':
            content = 'New Draft Post has been successfully Created'
            break
        case 'draft_post_update':
            content =
                'Your Post has been successfully Updated. Saved As Draft Mode'
            break
        case 'post_delete':
            content = 'Your Post has been successfully Deleted'
            break
        case 'future_to_draft':
            content = 'Your Post Status has been changed to ' + obj.post_status
            break
        case 'draft_to_future':
            content = 'Your Post Status has been changed to ' + obj.post_status
            break
        default:
            content = ''
    }

    if (!error) {
        jQuery.notifi(content, {
            autoHideDelay: 5000,
        })
    } else {
        jQuery.notifi(content, {
            autoHideDelay: 5000,
            noticeClass: 'ntf-warning',
        })
    }
}
