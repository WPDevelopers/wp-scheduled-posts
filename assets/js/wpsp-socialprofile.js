jQuery(document).ready(function ($) {
    /**
     * WordPress sidebar checkbox control for don't share
     */
    if( wpscpSocialProfile?.is_active_classis_editor ) {
        jQuery(document).on('click', '#wpscpprodontshare', function (e) {
            if (this.checked == true) {
                jQuery('#socialmedia').hide()
            } else {
                jQuery('#socialmedia').show()
            }
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
        jQuery('body #wpscpproInstantShareModal').kylefoxModal({
            escapeClose: false,
            clickClose: false,
            showClose: true,
        })

        // get data from dom
        const __nonce = wpscpSocialProfile?.nonce;
        // var nonce = jQuery('#wpscp_pro_instant_social_share_nonce').val()
        var postid = jQuery('#wpscppropostid').val()
        var facebook = jQuery('#wpscpprofacebookis').is(':checked')
        var twitter = jQuery('#wpscpprotwitteris').is(':checked')
        var linkedin = jQuery('#wpscpprolinkedinis').is(':checked')
        var instagram = jQuery('#wpscpproinstagramis').is(':checked')
        var pinterest = jQuery('#wpscppropinterestis').is(':checked')
        var medium = jQuery('#wpscppromediumis').is(':checked')
        var threads = jQuery('#wpscpprothreadsis').is(':checked')
        var pinterestBoardType = jQuery("input:radio[name='pinterestboardtype']:checked").val()
        var data = {
            action: 'wpscp_instant_share_fetch_profile',
            _nonce: __nonce,
            postid: postid,
            is_facebook_share: facebook,
            is_twitter_share: twitter,
            is_linkedin_share: linkedin,
            is_instagram_share: instagram,
            is_pinterest_share: pinterest,
            is_medium_share: medium,
            is_threads_share: threads,
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
                        const nonce = wpscpSocialProfile?.nonce;
                        var data = {
                            action: 'wpscp_instant_social_single_profile_share',
                            platform: profile,
                            nonce : nonce,
                            platformKey: key,
                            postid: postid,
                            pinterest_board_type: pinterestBoardType,
                        }
                        if(profile === 'pinterest' && pinterestBoardType === 'custom'){
                            var access_token = md5(profileKey[key].access_token);
                            data.pinterest_custom_board_name = jQuery("[name='wpscppro-pinterest-board-name[" + access_token + "]']").val();
                            data.pinterest_custom_section_name = jQuery("[name='wpscppro-pinterest-section-name[" + access_token + "]']").val();
                        }
                        jQuery.get(ajaxurl, data, function (response, status) {
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
})
