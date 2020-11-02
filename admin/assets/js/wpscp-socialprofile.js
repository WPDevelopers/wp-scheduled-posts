jQuery(document).ready(function ($) {
    /**
     * WordPress sidebar checkbox control for don't share
     */
    jQuery('#wpscpprodontshare').on('click', function (e) {
        if (this.checked == true) {
            jQuery('#socialmedia').hide()
        } else {
            jQuery('#socialmedia').show()
        }
    })
    /**
     * Upgrade pro alert
     */
    var wpscpUpgradeAlert = function (errorMessage) {
        var premium_content = document.createElement('p')
        var premium_anchor = document.createElement('a')

        premium_anchor.setAttribute(
            'href',
            'https://wpdeveloper.net/in/wp-scheduled-posts-pro'
        )
        premium_anchor.innerText = 'Upgrade to PRO.'
        premium_anchor.style.color = 'red'
        var proErrorMessage =
            'Multi Profile is a Premium Feature. To use this feature, <strong>' +
            premium_anchor.outerHTML +
            ' </strong>'
        premium_content.innerHTML =
            errorMessage !== undefined && errorMessage.search('Premium') > 0
                ? proErrorMessage
                : errorMessage

        swal({
            title: 'Failed!',
            content: premium_content,
            icon: 'error',
            buttons: [false, 'Close'],
            dangerMode: true,
        })
    }
    /**
     * ajax instant share modal
     */
    jQuery(document).on('click', '#wpscpproinstantsharenow', function (e) {
        e.preventDefault()
        // modal append if not exists dom
        if ($('#wpscpproInstantShareModal').length === 0) {
            jQuery('body').append(
                '<div id="wpscpproInstantShareModal"><div class="modalBody">Fetch Your Selected Profile</div></div>'
            )
        } else {
            jQuery('body #wpscpproInstantShareModal').html(
                '<div class="modalBody">Fetch Your Selected Profile</div>'
            )
        }
        // call modal
        jQuery('body #wpscpproInstantShareModal').modal({
            escapeClose: false,
            clickClose: false,
            showClose: true,
        })
        // get data from dom
        var nonce = jQuery('#wpscp_pro_instant_social_share_nonce').val()
        var postid = jQuery('#wpscppropostid').val()
        var facebook = jQuery('#wpscpprofacebookis').is(':checked')
        var twitter = jQuery('#wpscpprotwitteris').is(':checked')
        var linkedin = jQuery('#wpscpprolinkedinis').is(':checked')
        var instagram = jQuery('#wpscpproinstagramis').is(':checked')
        var pinterest = jQuery('#wpscppropinterestis').is(':checked')
        var pinterestCustomBoardName = jQuery(
            '#wpscppropinterestboardname'
        ).val()
        var data = {
            action: 'wpscp_instant_share_fetch_profile',
            _nonce: nonce,
            postid: postid,
            is_facebook_share: facebook,
            is_twitter_share: twitter,
            is_linkedin_share: linkedin,
            is_instagram_share: instagram,
            is_pinterest_share: pinterest,
            pinterest_custom_board_name: pinterestCustomBoardName,
        }

        jQuery.post(ajaxurl, data, function (response, status) {
            if (status == 'success') {
                jQuery('body #wpscpproInstantShareModal .modalBody').html(
                    response.markup
                )
                /**
                 * Single Profile Ajax sending via loop
                 */
                $.each(response.profile, function (profile, profileKey) {
                    Object.keys(profileKey).forEach(function (key) {
                        var data = {
                            action: 'wpscp_instant_social_single_profile_share',
                            platform: profile,
                            platformKey: key,
                            postid: postid,
                            pinterest_custom_board_name: pinterestCustomBoardName,
                        }
                        jQuery.post(ajaxurl, data, function (response, status) {
                            WpScp_Social_single_profile_share_response_markup(
                                profile,
                                key,
                                response
                            )
                        })
                    })
                })
            } else {
                jQuery('body #wpscpproInstantShareModal').append(
                    'failed element'
                )
            }
        })
    })
    /**
     * Modal ajax log view
     */
    jQuery(document).on(
        'click',
        '#wpscpproInstantShareModal a.viewlog',
        function (e) {
            e.preventDefault()
            jQuery('#' + e.target.dataset.id + ' .log').show() // show log
            jQuery(this).hide() // hide log button
        }
    )

    /**
     * popup social media share modal response
     * @param {ID} key
     * @param {ajax response} response
     * @returns markup
     */
    function WpScp_Social_single_profile_share_response_markup(
        profile,
        key,
        response
    ) {
        var logStatusSelector = $('#' + profile + '_' + key + ' .entry-status')
        var logSelector = $('#' + profile + '_' + key + ' .entry-log')
        var viewLogButton =
            '<a href="#" data-id="' +
            profile +
            '_' +
            key +
            '" class="viewlog">View Log</a>'
        var viewLogButtonFailed =
            '<a href="#" data-id="' +
            profile +
            '_' +
            key +
            '" class="viewlog failed">View Log</a>'
        var successStatus = '<span class="status success">Shared</span>'
        var failedStatus = '<span class="status failed">Failed</span>'
        // handle pinterest, twitter, linkedin response
        if (response.success) {
            logStatusSelector.replaceWith(successStatus + viewLogButton)
            logSelector.append(
                '<div class="log">' + JSON.stringify(response.data) + '</div>'
            )
        } else {
            logStatusSelector.replaceWith(failedStatus + viewLogButtonFailed)
            logSelector.append('<div class="log">' + response.data + '</div>')
        }
    }

    /**
     * Generate HTML DOM for setting option
     * @param {*} Obj
     */
    function WpScp_Social_profile_response_markup(
        response,
        platform,
        key,
        optionName
    ) {
        var outputHTML = ''
        outputHTML +=
            '<div class="wpscp-social-tab__item-list__single_item" data-type="' +
            platform +
            '" data-item="' +
            key +
            '" data-option_name="' +
            optionName +
            '">' +
            '<div class="entry-thumbnail">' +
            '<img src="' +
            (response.thumbnail_url != ''
                ? response.thumbnail_url
                : 'http://0.gravatar.com/avatar/64e1b8d34f425d19e1ee2ea7236d3028?s=96&d=mm&r=g') +
            '" alt="thumbnail">' +
            '</div>' +
            '<div class="entry-content">' +
            '<h4 class="entry-content__title">' +
            response.name +
            '</h4>' +
            '<p class="entry-content__doc">Added by <strong>' +
            response.added_by +
            '</strong> on ' +
            response.added_date +
            (response.default_board_name !== undefined
                ? '<br /> <strong>Default Board: <strong>' +
                  response.default_board_name
                : '') +
            '</p>' +
            '</div>' +
            '<div class="entry-control">' +
            '<div class="checkbox-toggle">' +
            '<form method="post">' +
            '<input type="checkbox" class="wpsp_field_activate" checked>' +
            '<svg class="is_checked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 426.67 426.67">' +
            '<path d="M153.504 366.84c-8.657 0-17.323-3.303-23.927-9.912L9.914 237.265c-13.218-13.218-13.218-34.645 0-47.863 13.218-13.218 34.645-13.218 47.863 0l95.727 95.727 215.39-215.387c13.218-13.214 34.65-13.218 47.86 0 13.22 13.218 13.22 34.65 0 47.863L177.435 356.928c-6.61 6.605-15.27 9.91-23.932 9.91z"/>' +
            '</svg>' +
            '<svg class="is_unchecked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 212.982 212.982">' +
            '<path d="M131.804 106.49l75.936-75.935c6.99-6.99 6.99-18.323 0-25.312-6.99-6.99-18.322-6.99-25.312 0L106.49 81.18 30.555 5.242c-6.99-6.99-18.322-6.99-25.312 0-6.99 6.99-6.99 18.323 0 25.312L81.18 106.49 5.24 182.427c-6.99 6.99-6.99 18.323 0 25.312 6.99 6.99 18.322 6.99 25.312 0L106.49 131.8l75.938 75.937c6.99 6.99 18.322 6.99 25.312 0 6.99-6.99 6.99-18.323 0-25.313l-75.936-75.936z" fill-rule="evenodd" clip-rule="evenodd"/>' +
            '</svg>' +
            '</form>' +
            '</div>' +
            '<div class="entry-control__more-link">' +
            '<button class="btn-more-link"><img src="' +
            wpscpSocialProfile.plugin_url +
            'admin/assets/images/icon-more.png' +
            '" alt="moreitem"></button>' +
            '<ul class="entry-control__more-link__group_absolute">' +
            '<li>' +
            (platform != 'linkedin' || platform != 'pinterest'
                ? '<button class="btn btn-refresh">Refresh</button>'
                : '') +
            '<button class="btn btn-remove">Delete</button>' +
            '</li>' +
            '</ul>' +
            '</div>' +
            '</div>' +
            '</div>'
        return outputHTML
    }

    /**
     * Example: group notify modal
     */
    function wpscp_facebook_group_notify_for_admin() {
        if ($('#wpscpproFacebookGroupNotifyModal').length === 0) {
            jQuery('body').append(
                '<div id="wpscpproFacebookGroupNotifyModal">' +
                    '<div class="modalBody">' +
                    '<div class="entry-content">' +
                    '<div class="entry-thumbnail">' +
                    '<img src="' +
                    wpscpSocialProfile.plugin_url +
                    'admin/assets/images/facebookGroupAppInstall.gif" alt="" />' +
                    '</div>' +
                    "<p>You've now added a social share for your Facebook Group successfully. But before we proceed, you need to install our Facebook App, which takes not more than a minute. Check out this <a href='https://wpdeveloper.net/docs/share-scheduled-posts-facebook/' target='_blank'>documentation</a> to learn how to connect your Group wtih WP Scheduled Posts app on Facebook.</p>" +
                    '<div>' +
                    '<a href="#" class="btn-close-facebook-group-notify">' +
                    '<img src="' +
                    wpscpSocialProfile.plugin_url +
                    'admin/assets/images/close.png" alt="close" />' +
                    '</a>' +
                    '</div>' +
                    '</div>'
            )
            jQuery(document).on(
                'click',
                '.btn-close-facebook-group-notify',
                function () {
                    jQuery(this)
                        .closest('#wpscpproFacebookGroupNotifyModal')
                        .remove()
                }
            )
        }
    }

    /**
     * Multi Social Profile
     * @platform facebook, linkedin, twitter, facebok
     */
    function wpscp_multi_social_account(Obj) {
        var ParrentSelector = Obj.selector
        // ajax loader
        var ajaxloader =
            '<div class="wpscp-pro-ajax-loader"><div></div><div></div><div></div><div></div></div>'
        // remove profile
        $(document).on(
            'click',
            ParrentSelector + ' button.btn-remove',
            function (e) {
                e.preventDefault()
                var item = $(this).closest(
                    '.wpscp-social-tab__item-list__single_item'
                )
                $(this).html(ajaxloader)
                var data = {
                    action: Obj.ajaxAction.removeProfile,
                    type: item.data('type'),
                    option_name: item.data('option_name'),
                    ID: item.data('item'),
                }
                // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                jQuery.post(ajaxurl, data, function (response, status) {
                    if (status == 'success') {
                        if (response.data == true) {
                            item.remove()
                        }
                    }
                })
            }
        )

        // add profile
        jQuery(document).on(
            'click',
            ParrentSelector + ' .wpscp-social-tab__btn--addnew-profile',
            function (e) {
                e.preventDefault()
                // loader
                var that = $(this)
                var btnInnerDom = that.html()
                that.html(ajaxloader)
                var data = {
                    action: Obj.ajaxAction.addProfile,
                    type: Obj.platform,
                }
                // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                jQuery.post(ajaxurl, data, function (response) {
                    if (response.success) {
                        open(response.data, '_self')
                    } else {
                        that.html(btnInnerDom)
                        wpscpUpgradeAlert(response.data)
                    }
                })
            }
        )

        // Social media Switch Toggle (group profile)
        jQuery(document).on(
            'click',
            ParrentSelector +
                ' .wpscp-social-tab__item-header .checkbox-toggle input[type="checkbox"]',
            function () {
                var that = $(this)
                var data = {
                    action: Obj.ajaxAction.switchGroupProfile,
                    option_name: $(this).attr('name'),
                    status: $(this).is(':checked') == true ? 'on' : false,
                }
                // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                jQuery.post(ajaxurl, data, function (response) {
                    var platfromID = $(that).closest('.wp-tab-panel').attr('id')
                    if (response.status == 'on') {
                        jQuery(
                            '#' +
                                platfromID +
                                ' .wpscp-social-tab__item-list__single_item'
                        ).removeClass('disable')
                        jQuery(ParrentSelector + ' .entry-content').removeClass(
                            'disable'
                        )
                        swal('Good job!', response.data, 'success')
                    } else {
                        jQuery(
                            '#' +
                                platfromID +
                                ' .wpscp-social-tab__item-list__single_item'
                        ).addClass('disable')
                        jQuery(ParrentSelector + ' .entry-content').addClass(
                            'disable'
                        )
                        swal('Cancelled!', response.data, 'error')
                    }
                })
            }
        )
        // Social Single Profile Switch Toggle
        jQuery(document).on(
            'click',
            ParrentSelector +
                ' .wpscp-social-tab__item-list__single_item:not(.disable) .checkbox-toggle input[type="checkbox"]',
            function () {
                var item = $(this).closest(
                    '.wpscp-social-tab__item-list__single_item'
                )
                var data = {
                    action: Obj.ajaxAction.switchSingleProfile,
                    status: $(this).is(':checked') == true ? 1 : 0,
                    type: item.data('type'),
                    option_name: item.data('option_name'),
                    ID: item.data('item'),
                }
                // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                jQuery.post(ajaxurl, data, function (response) {
                    if (response.status == 1 && response.token_expired == 1) {
                        jQuery(ParrentSelector + ' .entry-content').removeClass(
                            'disable'
                        )
                        swal({
                            title: 'Token Expired.',
                            text:
                                'Your access token is expired. are you want to refresh your token?',
                            icon: 'warning',
                            dangerMode: true,
                            buttons: ['Cancel', 'Refresh Token'],
                        }).then(function (willRefresh) {
                            if (willRefresh) {
                                $(
                                    '.wpscp-social-tab #' +
                                        item.data('type') +
                                        ' .btn-refresh'
                                ).click()
                            }
                        })
                    } else if (
                        response.status == 1 &&
                        response.token_expired != 1
                    ) {
                        jQuery(ParrentSelector + ' .entry-content').removeClass(
                            'disable'
                        )
                        swal('Good job!', response.data, 'success')
                    } else {
                        jQuery(ParrentSelector + ' .entry-content').addClass(
                            'disable'
                        )
                        swal('Cancelled!', response.data, 'error')
                    }
                })
            }
        )
    }
    // pinterest
    wpscp_multi_social_account({
        selector: '.wpscp-social-tab #pinterest',
        optionName: 'wpscp_pinterest_account',
        platform: 'pinterest',
        ajaxAction: {
            removeProfile: 'wpscp_multi_social_remove_profile',
            addProfile: 'wpscp_social_add_profile',
            switchGroupProfile: 'wpscp_social_group_profile_switch_toggle',
            switchSingleProfile: 'wpscp_social_single_profile_switch_toggle',
        },
    })
    // linkedin
    wpscp_multi_social_account({
        selector: '.wpscp-social-tab #linkedin',
        optionName: 'wpscp_linkedin_account',
        platform: 'linkedin',
        ajaxAction: {
            removeProfile: 'wpscp_multi_social_remove_profile',
            addProfile: 'wpscp_social_add_profile',
            switchGroupProfile: 'wpscp_social_group_profile_switch_toggle',
            switchSingleProfile: 'wpscp_social_single_profile_switch_toggle',
        },
    })

    // twitter
    wpscp_multi_social_account({
        selector: '.wpscp-social-tab #twitter',
        optionName: 'wpscp_twitter_account',
        platform: 'twitter',
        ajaxAction: {
            removeProfile: 'wpscp_multi_social_remove_profile',
            addProfile: 'wpscp_social_add_profile',
            switchGroupProfile: 'wpscp_social_group_profile_switch_toggle',
            switchSingleProfile: 'wpscp_social_single_profile_switch_toggle',
        },
    })

    // facebook
    wpscp_multi_social_account({
        selector: '.wpscp-social-tab #facebook',
        optionName: 'wpscp_facebook_account',
        platform: 'facebook',
        ajaxAction: {
            removeProfile: 'wpscp_multi_social_remove_profile',
            addProfile: 'wpscp_social_add_profile',
            switchGroupProfile: 'wpscp_social_group_profile_switch_toggle',
            switchSingleProfile: 'wpscp_social_single_profile_switch_toggle',
        },
    })

    /**
     * Token Generate After oAuth & open modal for facebook and pinterest
     */
    jQuery(window).on('load', function () {
        var queryString = new URLSearchParams(window.location.search)
        if (
            queryString.get('action') === 'wpscp_social_add_profile' ||
            (queryString.get('action') === 'wpscp_social_temp_add_profile' &&
                queryString.get('code')) ||
            (queryString.get('oauth_verifier') &&
                queryString.get('oauth_token'))
        ) {
            // Integation tab active after page load finish
            jQuery(
                '.wpsp_top_nav_link_wrapper ul li[data-tab="wpsp_integ"] '
            ).click()

            // append modal, for generate token
            if ($('#wpscpproMultiSocialTokenModel').length === 0) {
                jQuery('body').append(
                    '<div id="wpscpproMultiSocialTokenModel"><div class="modalbody">Generating Token & Fetching User Data</div></div>'
                )
            }
            jQuery('body #wpscpproMultiSocialTokenModel').modal({
                escapeClose: false,
                clickClose: false,
                showClose: false,
            })

            /**
             * send ajax requrest for generate access token and fetch user, page info
             */
            var data = {
                action: 'wpscp_social_profile_fetch_user_info_and_token',
                type: queryString.get('type'),
                code: queryString.get('code'),
                appId: queryString.get('appId'),
                appSecret: queryString.get('appSecret'),
                oauthVerifier: queryString.get('oauth_verifier'),
                oauthToken: queryString.get('oauth_token'),
            }
            jQuery.post(ajaxurl, data, function (response) {
                var modalBodySelector = jQuery(
                    'body #wpscpproMultiSocialTokenModel .modalbody'
                )
                if (response.success) {
                    if (
                        typeof response.type !== 'undefined' &&
                        response.type === 'pinterest'
                    ) {
                        // if response send from pinterest then update modal ui
                        $(
                            '.wpscp-social-tab ul.wp-tab-bar li a[href="#pinterest"]'
                        ).click()
                        localStorage.setItem(
                            'wpcpproPinterestInfo',
                            JSON.stringify(response.info)
                        )
                        wpscppro_pinterest_board_selection_modal(
                            JSON.parse(response.boards)
                        )
                    } else if (
                        typeof response.type !== 'undefined' &&
                        response.type === 'facebook'
                    ) {
                        // if response send from facebook then update modal ui
                        localStorage.setItem(
                            'wpcpproFacebookPage',
                            JSON.stringify(response.page)
                        )
                        localStorage.setItem(
                            'wpcpproFacebookGroup',
                            JSON.stringify(response.group)
                        )
                        wpscppro_facebook_page_and_group_selection_modal(
                            response.page,
                            response.group
                        )
                    } else if (
                        typeof response.type !== 'undefined' &&
                        response.type === 'twitter'
                    ) {
                        $(
                            '.wpscp-social-tab ul.wp-tab-bar li a[href="#twitter"]'
                        ).click()
                        // dom update
                        var twitterTabList =
                            '.wpscp-social-tab #twitter .wpscp-social-tab__item-list'
                        var key = parseInt(
                            $(
                                twitterTabList +
                                    ' .wpscp-social-tab__item-list__single_item'
                            ).length
                        )
                        $(twitterTabList).append(
                            WpScp_Social_profile_response_markup(
                                response.data,
                                'twitter',
                                key,
                                'wpscp_twitter_account'
                            )
                        )
                        // modal update
                        modalBodySelector.html(
                            '<div class="message"><p class="success"><strong>Congratulations!</strong> Your data is saved.</p></div>'
                        )
                        modalBodySelector.prepend(
                            '<a class="close-modal" href="#" rel="modal:close">Close</a>'
                        )
                    } else if (
                        typeof response.type !== 'undefined' &&
                        response.type === 'linkedin'
                    ) {
                        $(
                            '.wpscp-social-tab ul.wp-tab-bar li a[href="#linkedin"]'
                        ).click()
                        // dom update
                        var linkedinTabList =
                            '.wpscp-social-tab #linkedin .wpscp-social-tab__item-list'
                        var key = parseInt(
                            $(
                                linkedinTabList +
                                    ' .wpscp-social-tab__item-list__single_item'
                            ).length
                        )
                        $(linkedinTabList).append(
                            WpScp_Social_profile_response_markup(
                                response.data,
                                'linkedin',
                                key,
                                'wpscp_linkedin_account'
                            )
                        )
                        // modal update
                        modalBodySelector.html(
                            '<div class="message"><p class="success"><strong>Congratulations!</strong> Your data is saved.</p></div>'
                        )
                        modalBodySelector.prepend(
                            '<a class="close-modal" href="#" rel="modal:close">Close</a>'
                        )
                    } else {
                        // modal update
                        modalBodySelector.html(
                            '<div class="message"><p class="success"><strong>Failed!</strong> Someting went wrong...</p></div>'
                        )
                        modalBodySelector.prepend(
                            '<a class="close-modal" href="#" rel="modal:close">Close</a>'
                        )
                    }
                } else {
                    // failed message
                    modalBodySelector.html(
                        '<div class="message"><p class="error">Failed: ' +
                            response.data +
                            ' </p></div>'
                    )
                    modalBodySelector.prepend(
                        '<a class="close-modal" href="#" rel="modal:close">Close</a>'
                    )
                }
                // remove unnecessary query string
                if (history.pushState) {
                    history.pushState(
                        null,
                        null,
                        window.location.href.split('&')[0]
                    )
                }
            })
        }
    })

    /**
     * Pinterest Board Selection Modal
     * this modal automatic run after fetch oauth token
     */
    function wpscppro_pinterest_board_selection_modal(response) {
        if ($('#wpscpproPinterestBoardSelectModal').length === 0) {
            var boardListTopMessage = ''
            var boardList = ''
            if (Array.isArray(response.data)) {
                boardListTopMessage += '<li>Select Your Boards: </li>'
                $.each(response.data, function (key, value) {
                    boardList +=
                        '<li id="pinterest_' +
                        key +
                        '">' +
                        '<div class="item-content">' +
                        '<div class="entry-thumbnail">' +
                        '<img src="http://localhost/schedulepress/wp-content/plugins/wp-scheduled-posts-pro/admin/assets/images/icon-pinterest-small-white.png" alt="logo">' +
                        '</div>' +
                        '<h4 class="entry-title">' +
                        value.name +
                        '</h4>' +
                        '<div class="control">' +
                        '<input type="radio" name="boardname" value="' +
                        value.url +
                        '"' +
                        (key == 0 ? ' checked' : '') +
                        '>' +
                        '<div>' +
                        '</div>' +
                        '</li>'
                })
            } else {
                boardList +=
                    '<li id="pinterest_default_board_name">' +
                    '<div class="item-content">' +
                    '<div class="entry-thumbnail">' +
                    '<img src="http://localhost/schedulepress/wp-content/plugins/wp-scheduled-posts-pro/admin/assets/images/icon-pinterest-small-white.png" alt="logo">' +
                    '</div>' +
                    '<h4 class="entry-title">Enter Your Board url:</h4>' +
                    '<div class="control">' +
                    '<input type="text" name="boardname" value="" placeholder="https://www.pinterest.com/username/boardname/">' +
                    '<div>' +
                    '</div>' +
                    '</li>'
            }

            jQuery('body').append(
                '<div id="wpscpproPinterestBoardSelectModal">' +
                    '<div class="modalBody">' +
                    '<div class="entry-head pinterest">' +
                    '<img src="http://localhost/schedulepress/wp-content/plugins/wp-scheduled-posts-pro/admin/assets/images/icon-pinterest-small-white.png" alt="logo">' +
                    '<h2 class="entry-head-title">Pinterest</h2>' +
                    '</div>' +
                    '<ul>' +
                    boardListTopMessage +
                    boardList +
                    '</ul>' +
                    '<button class="btn btn-pinterest-save-board">Save</button>' +
                    '</div>' +
                    '</div>'
            )
        }
        jQuery('body #wpscpproPinterestBoardSelectModal').modal({
            escapeClose: false,
            clickClose: false,
            showClose: true,
        })
    }

    /**
     * Pinterest data saving option
     */
    jQuery(document).on(
        'click',
        'button.btn-pinterest-save-board',
        function () {
            var boardurl =
                $('body #pinterest_default_board_name').length > 0
                    ? $('body input[name="boardname"]').val()
                    : $('body input[name="boardname"]:checked').val()
            var data = {
                action: 'wpscp_social_profile_pinterest_data_save',
                boardurl: boardurl,
                info: JSON.parse(localStorage.getItem('wpcpproPinterestInfo')),
            }

            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
            jQuery.post(ajaxurl, data, function (response) {
                var pinterestBoardModal = $(
                    '#wpscpproPinterestBoardSelectModal .modalBody'
                )
                // remove local storage item
                localStorage.removeItem('wpcpproPinterestInfo')
                if (response.success) {
                    var pinterestTabList =
                        '.wpscp-social-tab #pinterest .wpscp-social-tab__item-list'
                    var key = parseInt(
                        $(
                            pinterestTabList +
                                ' .wpscp-social-tab__item-list__single_item'
                        ).length
                    )
                    // insert dom for integation setting
                    $(pinterestTabList).append(
                        WpScp_Social_profile_response_markup(
                            response.data,
                            'pinterest',
                            key,
                            'wpscp_pinterest_account'
                        )
                    )
                    // insert dom in popup modal
                    pinterestBoardModal.html(
                        '<div class="message"><p><strong>Congratulations!</strong> Your selected data is now saved.</p></div>'
                    )
                } else {
                    pinterestBoardModal.html(
                        '<div class="message"><p><strong>Failed!</strong> Someting went wrong. please try again.</p></div>'
                    )
                }
            })
        }
    )

    /**
     * Facebook Page & Group Selection Modal
     * This modal automatic run after fetch oauth token
     */
    function wpscppro_facebook_page_and_group_selection_modal(page, group) {
        if ($('#wpscpproFacebookPageAndGroupSelectModal').length === 0) {
            var pageList = ''
            if (page.length > 0) {
                $.each(page, function (key, value) {
                    pageList +=
                        '<li id="facebook_page_' +
                        key +
                        '">' +
                        '<div class="item-content">' +
                        '<div class="entry-thumbnail">' +
                        '<img src="' +
                        value.thumbnail_url +
                        '" alt="logo">' +
                        '</div>' +
                        '<h4 class="entry-title">' +
                        value.name +
                        '</h4>' +
                        '<div class="control">' +
                        '<input type="checkbox" name="pagekey" value="' +
                        key +
                        '">' +
                        '<div>' +
                        '</div>' +
                        '</li>'
                })
            }

            var groupList = ''
            if (group.length > 0) {
                $.each(group, function (key, value) {
                    groupList +=
                        '<li id="facebook_group_' +
                        key +
                        '">' +
                        '<div class="item-content">' +
                        '<div class="entry-thumbnail">' +
                        '<img src="' +
                        value.thumbnail_url +
                        '" alt="logo">' +
                        '</div>' +
                        '<h4 class="entry-title">' +
                        value.name +
                        '</h4>' +
                        '<div class="control">' +
                        '<input type="checkbox" name="groupkey" value="' +
                        key +
                        '">' +
                        '<div>' +
                        '</div>' +
                        '</li>'
                })
            }

            jQuery('body').append(
                '<div id="wpscpproFacebookPageAndGroupSelectModal">' +
                    '<div class="modalBody">' +
                    '<div class="entry-head facebook">' +
                    '<img src="' +
                    wpscpSocialProfile.plugin_url +
                    'admin/assets/images/icon-facebook-small-white.png" alt="logo">' +
                    '<h2 class="entry-head-title">Facebook</h2>' +
                    '</div>' +
                    '<ul>' +
                    (pageList != '' ? '<li>Pages: </li>' + pageList : '') +
                    (groupList != '' ? '<li>Groups: </li>' + groupList : '') +
                    '</ul>' +
                    '<button class="btn btn-facebook-save-pagegroup">Save</button>' +
                    '</div>' +
                    '</div>'
            )
        }
        jQuery('body #wpscpproFacebookPageAndGroupSelectModal').modal({
            escapeClose: false,
            clickClose: false,
            showClose: true,
        })
        /**
         * Enable/Disable Multi Profile Feature
         */
        if (wpscpSocialProfile.is_active_pro !== '1') {
            $(
                'body #wpscpproFacebookPageAndGroupSelectModal input[type="checkbox"]'
            ).on('change', function (e) {
                if (
                    $(
                        'body #wpscpproFacebookPageAndGroupSelectModal input[type="checkbox"]:checked'
                    ).length > 1
                ) {
                    this.checked = false
                    wpscpUpgradeAlert(
                        'Multi Profile is a Premium Feature. To use this feature'
                    )
                }
            })
        }
    }
    /**
     * Facebook data saving option
     * facebook page & group selection modal for data saving
     */
    jQuery(document).on(
        'click',
        'button.btn-facebook-save-pagegroup',
        function () {
            // page
            var pageList = JSON.parse(
                localStorage.getItem('wpcpproFacebookPage')
            )
            var selectedPage = []
            $('body input[name="pagekey"]:checked').each(function (i) {
                selectedPage[i] = pageList[parseInt($(this).val())]
            })
            // group
            var groupList = JSON.parse(
                localStorage.getItem('wpcpproFacebookGroup')
            )
            var selectedGroup = []
            $('body input[name="groupkey"]:checked').each(function (i) {
                selectedGroup[i] = groupList[parseInt($(this).val())]
            })
            var data = {
                action: 'wpscp_social_profile_facebook_data_save',
                page: selectedPage,
                group: selectedGroup,
            }
            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
            jQuery.post(ajaxurl, data, function (response) {
                var facebookPageGroupModal = $(
                    '#wpscpproFacebookPageAndGroupSelectModal .modalBody'
                )
                // remove localstorage save data
                localStorage.removeItem('wpcpproFacebookPage')
                localStorage.removeItem('wpcpproFacebookGroup')
                if (response.success) {
                    var facebookTabList =
                        '.wpscp-social-tab #facebook .wpscp-social-tab__item-list'
                    var key = parseInt(
                        $(
                            facebookTabList +
                                ' .wpscp-social-tab__item-list__single_item'
                        ).length
                    )
                    // insert dom for integation setting
                    $.each(response.data, function (index, value) {
                        $(facebookTabList).append(
                            WpScp_Social_profile_response_markup(
                                value,
                                'facebook',
                                key,
                                'wpscp_facebook_account'
                            )
                        )
                        key += 1
                    })
                    // insert dom in popup modal
                    facebookPageGroupModal.html(
                        '<div class="message"><p><strong>Congratulations!</strong> Your selected data is now saved.</p></div>'
                    )
                    // show group popup message
                    if (selectedGroup.length > 0) {
                        wpscp_facebook_group_notify_for_admin()
                    }
                } else {
                    facebookPageGroupModal.html(
                        '<div class="message"><p><strong>Failed!</strong> Someting went wrong. please try again.</p></div>'
                    )
                }
            })
        }
    )

    /**
     * Token Refresh for facebook, twitter, linkedin and pinterest
     */
    wpscppro_social_profile_access_token_refresh()
    function wpscppro_social_profile_access_token_refresh() {
        // ajax loader
        var ajaxloader =
            '<div class="wpscp-pro-ajax-loader"><div></div><div></div><div></div><div></div></div>'
        // facebook
        $(document).on(
            'click',
            '.wpscp-social-tab #facebook .btn-refresh',
            function () {
                var that = this
                $(that).html(ajaxloader)
                var item = $(that).closest(
                    '.wpscp-social-tab__item-list__single_item'
                )
                var data = {
                    action: 'wpscp_social_profile_access_token_refresh',
                    _wpscpnonce: wpscpSocialProfile.nonce,
                    type: item.data('type'),
                    item: item.data('item'),
                    option_name: item.data('option_name'),
                }
                jQuery.post(ajaxurl, data, function (response) {
                    if (response.success && response.data) {
                        $(that).html('Refreshed')
                        swal('Good job!', 'Your token is refresh', 'success')
                    } else {
                        $(that).html('Failed')
                        swal(
                            'Sorry!',
                            'Your token is not refresh. please try again or remove your profile and add new again',
                            'failed'
                        )
                    }
                })
            }
        )
        // twitter
        $(document).on(
            'click',
            '.wpscp-social-tab #twitter .btn-refresh',
            function () {
                swal({
                    title: 'Someting went wrong refresh token.',
                    text:
                        'Do you want to re-authorize and try again? Note: Your current account will be removed if you take this action.',
                    icon: 'warning',
                    // buttons: true,
                    dangerMode: true,
                    buttons: ['Cancel', 'Remove & Re Authorize'],
                }).then(function (willDelete) {
                    if (willDelete) {
                        $('.wpscp-social-tab #twitter .btn.btn-remove').click()
                        swal({
                            text:
                                'Item Removing and Reauthorize please wait...',
                            buttons: false,
                            closeOnClickOutside: false,
                            timer: 2000,
                        }).then(function () {
                            $(
                                '.wpscp-social-tab #twitter .wpscp-social-tab__btn--addnew-profile'
                            ).click()
                        })
                    }
                })
            }
        )
        // linkedin
        $(document).on(
            'click',
            '.wpscp-social-tab #linkedin .btn-refresh',
            function () {
                swal({
                    title: 'Someting went wrong refresh token.',
                    text:
                        'Do you want to re-authorize and try again? Note: Your current account will be removed if you take this action.',
                    icon: 'warning',
                    // buttons: true,
                    dangerMode: true,
                    buttons: ['Cancel', 'Remove & Re Authorize'],
                }).then(function (willDelete) {
                    if (willDelete) {
                        $('.wpscp-social-tab #linkedin .btn.btn-remove').click()
                        swal({
                            text:
                                'Item Removing and Reauthorize please wait...',
                            buttons: false,
                            closeOnClickOutside: false,
                            timer: 2000,
                        }).then(function () {
                            $(
                                '.wpscp-social-tab #linkedin .wpscp-social-tab__btn--addnew-profile'
                            ).click()
                        })
                    }
                })
            }
        )
        // pinterest
        $(document).on(
            'click',
            '.wpscp-social-tab #pinterest .btn-refresh',
            function () {
                swal({
                    title: 'Someting went wrong refresh token.',
                    text:
                        'Do you want to re-authorize and try again? Note: Your current account will be removed if you take this action.',
                    icon: 'warning',
                    // buttons: true,
                    dangerMode: true,
                    buttons: ['Cancel', 'Remove & Re Authorize'],
                }).then(function (willDelete) {
                    if (willDelete) {
                        $(
                            '.wpscp-social-tab #pinterest .btn.btn-remove'
                        ).click()
                        swal({
                            text:
                                'Item Removing and Reauthorize please wait...',
                            buttons: false,
                            closeOnClickOutside: false,
                            timer: 2000,
                        }).then(function () {
                            $(
                                '.wpscp-social-tab #pinterest .wpscp-social-tab__btn--addnew-profile'
                            ).click()
                        })
                    }
                })
            }
        )
    }

    /** Temp it will be delete after approve app */
    // temp add profile. it will be remove after approve real app
    jQuery(document).on(
        'click',
        '.wpscp-social-tab__btn--temp-addnew-profile',
        function (e) {
            e.preventDefault()
            // if modal exists then remove it
            if ($('body #wpscpproTempModalForInsertAccount').length !== 0) {
                $('body #wpscpproTempModalForInsertAccount').remove()
            }
            var type = $(this).data('type')
            var pinterest =
                '<h3>Pinterest</h3>' +
                '<p> For details on Pinterest configuration, check out this ' +
                '<a class="docs" href="https://wpdeveloper.net/docs/wordpress-posts-on-pinterest/" target="_blank">Doc</a> <br />' +
                '<a href="https://developers.pinterest.com/" target="_blank"><strong>Click here</strong></a> here to Retrieve Your API Keys from your Pinterest account</p>' +
                '</div>'
            var linkedin =
                '<h3>linkedin</h3>' +
                '<p> For details on Linkedin configuration, check out this ' +
                '<a class="docs" href="https://wpdeveloper.net/docs/share-wordpress-posts-on-linkedin/" target="_blank">Doc</a> <br />' +
                '<a href="https://www.linkedin.com/developers/" target="_blank"><strong>Click here</strong></a> here to Retrieve Your API Keys from your Linkedin account</p>' +
                '</div>'
            var twitter =
                '<h3>Twitter</h3>' +
                '<p> For details on Twitter configuration, check out this ' +
                '<a class="docs" href="https://wpdeveloper.net/docs/automatically-tweet-wordpress-posts/" target="_blank">Doc</a> <br />' +
                '<a href="https://developer.twitter.com/" target="_blank"><strong>Click here</strong></a> here to Retrieve Your API Keys from your Twitter account</p>' +
                '</div>'
            var facebook =
                '<h3>Facebook</h3>' +
                '<p> For details on Facebook configuration, check out this ' +
                '<a class="docs" href="https://wpdeveloper.net/docs/share-scheduled-posts-facebook/" target="_blank">Doc</a> <br />' +
                '<a href="https://developers.facebook.com/" target="_blank"><strong>Click here</strong></a> here to Retrieve Your API Keys from your Facebook account</p>' +
                '</div>'

            // add header markup
            var header = ''
            var redirectURLDescription = ''
            if (type === 'pinterest') {
                header = pinterest
                redirectURLDescription =
                    'Copy this and paste it in your Pinterest app redirect URI field.'
            } else if (type === 'linkedin') {
                header = linkedin
                redirectURLDescription =
                    'Copy this and paste it in your Linkdin app redirect URI field.'
            } else if (type === 'twitter') {
                header = twitter
                redirectURLDescription =
                    'Copy this and paste it in your Twitter app Callback URI field.'
            } else if (type === 'facebook') {
                header = facebook
                redirectURLDescription =
                    'Copy this and paste it in your Facebook app Callback URI field.'
            }
            header +=
                '<input type="hidden" name="tempmodaltype" value="' +
                type +
                '" />'

            if ($('#wpscpproTempModalForInsertAccount').length === 0) {
                jQuery('body').append(
                    '<div id="wpscpproTempModalForInsertAccount">' +
                        '<div class="modalbody">' +
                        '<form><div class="wpsp-social-account-insert-modal">' +
                        '<div class="wpsp-social-modal-header">' +
                        header +
                        '<table class="form-table">' +
                        '<tbody>' +
                        '<tr>' +
                        '<td colspan="2" align="left">' +
                        '<div class="form-group redirect-group">' +
                        '<div class="form-label">' +
                        '<label>Redirect URI: </label>' +
                        '</div>' +
                        '<div class="form-input">' +
                        '<input type="text" class="form-control" name="redirect_uri" value="' +
                        wpscpSocialProfile.redirect_url +
                        '" placeholder="Redirect URI">' +
                        '<div class="doc">' +
                        redirectURLDescription +
                        '</div>' +
                        '</div>' +
                        '</div>' +
                        '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td colspan="2" align="left">' +
                        '<div class="form-group">' +
                        '<div class="form-label">' +
                        '<label>App ID: </label>' +
                        '</div>' +
                        '<div class="form-input">' +
                        '<input type="text" class="form-control" name="app_id" value="" placeholder="App ID" required>' +
                        '</div>' +
                        '</div>' +
                        '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td colspan="2" align="left">' +
                        '<div class="form-group">' +
                        '<div class="form-label">' +
                        '<label>App Secret: </label>' +
                        '</div>' +
                        '<div class="form-input">' +
                        '<input type="text" class="form-control" name="app_secret" value="" placeholder="App Secret" required>' +
                        '</div>' +
                        '</div>' +
                        '</td>' +
                        '</tr>' +
                        '<tr>' +
                        '<td colspan="2" align="left">' +
                        '<div class="form-group">' +
                        '<a class="submit">Generate Access Token</a>' +
                        '</div>' +
                        '</td>' +
                        '</tr>' +
                        '</tbody>' +
                        '</table>' +
                        '</div>' +
                        '</form>' +
                        '</div>' +
                        '</div>'
                )
            }
            jQuery('body #wpscpproTempModalForInsertAccount').modal({
                escapeClose: false,
                clickClose: false,
                showClose: true,
            })
        }
    )
    var ajaxloader =
        '<div class="wpscp-pro-ajax-loader"><div></div><div></div><div></div><div></div></div>'
    // temp add profile form submit it will be remove after approve real app
    jQuery(document).on(
        'click',
        '#wpscpproTempModalForInsertAccount a.submit',
        function (e) {
            e.preventDefault()
            $(this).html(ajaxloader)
            jQuery('body .modalerror').remove()
            var type = $('input[name="tempmodaltype"]').val()
            var appId = $('input[name="app_id"]').val()
            var appSecret = $('input[name="app_secret"]').val()
            if (appId != '' && appSecret != '') {
                var data = {
                    action: 'wpscp_social_temp_add_profile',
                    appId: appId,
                    appSecret: appSecret,
                    type: type,
                }

                // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                jQuery.post(ajaxurl, data, function (response) {
                    if (response.success) {
                        open(response.data, '_self')
                    } else {
                        jQuery('.jquery-modal').hide()
                        wpscpUpgradeAlert(response.data)
                    }
                })
            } else {
                jQuery(this).html('Failed, Try Again')
                jQuery(this).after(
                    '<span class="modalerror">All Input Field Is Required.</span>'
                )
            }
        }
    )

    /**
     * Twitter Notice for temp
     * @since 3.3.2
     */
    jQuery('.wpscp-twitter-app-notice .notice-dismiss').on(
        'click',
        function () {
            var data = {
                action: 'wpscp_twitter_app_notice',
                whatever: 1234,
            }

            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
            jQuery.post(ajaxurl, data, function (response) {
                console.log(response)
            })
        }
    )
    /**
     * Facebook Notice for temp
     * @since 3.3.2
     */
    jQuery('.wpscp-facebook-app-notice .notice-dismiss').on(
        'click',
        function () {
            var data = {
                action: 'wpscp_facebook_app_notice',
                whatever: 1234,
            }

            // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
            jQuery.post(ajaxurl, data, function (response) {
                console.log(response)
            })
        }
    )

    /** Temp code end */
})
