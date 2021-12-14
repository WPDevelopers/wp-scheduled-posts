import { __ } from '@wordpress/i18n'
export const compareConditionValue = (condition, allFieldsValue) => {
    let flag = true
    if (condition === undefined) return flag
    for (const [key, value] of Object.entries(condition)) {
        if (allFieldsValue[key] === value) {
            flag = false
        } else {
            flag = true
        }
    }
    return flag
}

export const wpspSettingsGlobal = window.wpspSettingsGlobal

export const wpspGetPluginRootURI = window.wpspSettingsGlobal.plugin_root_uri

export const socialTabHeaderData = {
    facebook: {
        icon: 'icon-facebook.png',
        title: __('Facebook', 'wp-scheduled-posts'),
        subtitle:
            __(
                'You can enable/disable facebook social share. For details on facebook configuration, check out this',
                'wp-scheduled-posts'
            ) +
            ' <a href="https://wpdeveloper.com/docs/share-scheduled-posts-facebook/" target="_blank">' +
            __('Doc.', 'wp-scheduled-posts') +
            '</a>',
    },
    twitter: {
        icon: 'icon-twitter.png',
        title: __('Twitter', 'wp-scheduled-posts'),
        subtitle:
            __(
                'You can enable/disable twitter social share. For details on twitter configuration, check out this',
                'wp-scheduled-posts'
            ) +
            ' <a href="https://wpdeveloper.com/docs/automatically-tweet-wordpress-posts/" target="_blank">' +
            __('Doc.', 'wp-scheduled-posts') +
            '</a>',
    },
    linkedin: {
        icon: 'icon-linkedin.png',
        title: __('LinkedIn', 'wp-scheduled-posts'),
        subtitle:
            __(
                'You can enable/disable linkedin social share. For details on linkedin configuration, check out this',
                'wp-scheduled-posts'
            ) +
            ' <a href="https://wpdeveloper.com/docs/share-wordpress-posts-on-linkedin/" target="_blank">' +
            __('Doc.', 'wp-scheduled-posts') +
            '</a>',
    },
    pinterest: {
        icon: 'icon-pinterest.png',
        title: __('Pinterest', 'wp-scheduled-posts'),
        subtitle:
            __(
                'You can enable/disable pinterest social share. For details on pinterest configuration, check out this',
                'wp-scheduled-posts'
            ) +
            ' <a href="https://wpdeveloper.com/docs/wordpress-posts-on-pinterest/" target="_blank">' +
            __('Doc.', 'wp-scheduled-posts') +
            '</a>',
    },
}

export const socialPopUpData = {
    facebook: {
        title: __('Facebook', 'wp-scheduled-posts'),
        subtitle:
            __(
                'For details on Facebook configuration, check out this',
                'wp-scheduled-posts'
            ) +
            ' <a target="_blank" href="https://wpdeveloper.com/docs/share-scheduled-posts-facebook/">' +
            __('Doc', 'wp-scheduled-posts') +
            '</a> <br /> <a target="_blank" href="https://developer.facebook.com/">' +
            __('Click here', 'wp-scheduled-posts') +
            '</a> ' +
            __(
                'to Retrieve Your API Keys from your Facebook account.',
                'wp-scheduled-posts'
            ),
    },
    twitter: {
        title: __('Twitter', 'wp-scheduled-posts'),
        subtitle:
            __(
                'For details on Twitter configuration, check out this',
                'wp-scheduled-posts'
            ) +
            ' <a target="_blank" href="https://wpdeveloper.com/docs/automatically-tweet-wordpress-posts/">' +
            __('Doc', 'wp-scheduled-posts') +
            '</a> <br /> <a target="_blank" href="https://developer.twitter.com/">' +
            __('Click here', 'wp-scheduled-posts') +
            '</a> ' +
            __(
                'to Retrieve Your API Keys from your Twitter account.',
                'wp-scheduled-posts'
            ),
    },
    linkedin: {
        title: __('LinkedIn', 'wp-scheduled-posts'),
        subtitle:
            __(
                'For details on LinkedIn configuration, check out this',
                'wp-scheduled-posts'
            ) +
            '<a target="_blank" href="https://wpdeveloper.com/docs/share-wordpress-posts-on-linkedin">' +
            __('Doc', 'wp-scheduled-posts') +
            '</a> <br /> <a target="_blank" href="https://www.linkedin.com/developers/">' +
            __('Click here', 'wp-scheduled-posts') +
            '</a> ' +
            __(
                'to Retrieve Your API Keys from your LinkedIn account.',
                'wp-scheduled-posts'
            ),
    },
    pinterest: {
        title: __('Pinterest', 'wp-scheduled-posts'),
        subtitle:
            __(
                'For details on Pinterest configuration, check out this',
                'wp-scheduled-posts'
            ) +
            ' <a target="_blank" href="https://wpdeveloper.com/docs/wordpress-posts-on-pinterest/">' +
            __('Doc', 'wp-scheduled-posts') +
            '</a> <br /> <a target="_blank" href="https://developers.pinterest.com">' +
            __('Click here', 'wp-scheduled-posts') +
            '</a> ' +
            __(
                'to Retrieve Your API Keys from your Pinterest account.',
                'wp-scheduled-posts'
            ),
    },
}
