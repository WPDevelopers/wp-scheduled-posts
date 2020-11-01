jQuery(document).ready(function ($) {
    var get_query_vars = function (name) {
        var vars = {}
        window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function (
            m,
            key,
            value
        ) {
            vars[key] = value
        })
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

    if (jQuery.type(get_query_vars('page')) !== 'undefined') {
        var query_vars = get_query_vars('page').split('#')
        if (query_vars[0] === 'wp-scheduled-posts') {
            var query_ID =
                query_vars[1] !== undefined ? query_vars[1].split('?') : ''
            $(
                '.wpsp_top_nav_link_wrapper ul li[data-tab="' +
                    query_ID[0] +
                    '"]'
            ).trigger('click')
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
    $('body').on('click', '#wpscppro_btn_remove_meta_image_upload', function (
        e
    ) {
        e.preventDefault()
        $('#wpscppro_custom_social_share_image').val('')
        $('#wpscpprouploadimagepreviewold').hide()
        $('#wpscpprouploadimagepreview').empty()
        $(this).hide()
    })
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
