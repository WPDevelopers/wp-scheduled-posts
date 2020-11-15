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
        title: 'Facebook',
        subtitle:
            'You can enable/disable facebook social share. For details on facebook configuration, check out this <a href="https://wpdeveloper.net/docs/share-scheduled-posts-facebook/" target="_blank">Doc</a>',
    },
    twitter: {
        icon: 'icon-twitter.png',
        title: 'Twitter',
        subtitle:
            'You can enable/disable twitter social share. For details on twitter configuration, check out this <a href="https://wpdeveloper.net/docs/automatically-tweet-wordpress-posts/" target="_blank">Doc</a>',
    },
    linkedin: {
        icon: 'icon-linkedin.png',
        title: 'Linkedin',
        subtitle:
            'You can enable/disable linkedin social share. For details on linkedin configuration, check out this <a href="https://wpdeveloper.net/docs/share-wordpress-posts-on-linkedin/" target="_blank">Doc</a>',
    },
    pinterest: {
        icon: 'icon-pinterest.png',
        title: 'Pinterest',
        subtitle:
            'You can enable/disable pinterest social share. For details on pinterest configuration, check out this <a href="https://wpdeveloper.net/docs/wordpress-posts-on-pinterest/" target="_blank">Doc</a>',
    },
}

export const socialPopUpData = {
    facebook: {
        title: 'Facebook',
        subtitle:
            'For details on Facebook configuration, check out this <a target="_blank" href="https://wpdeveloper.net/docs/automatically-tweet-wordpress-posts/">Doc</a> <br /> <a target="_blank" href="https://developer.facebook.com/">Click here</a> to Retrieve Your API Keys from your Facebook account',
    },
    twitter: {
        title: 'Twitter',
        subtitle:
            'For details on Twitter configuration, check out this <a target="_blank" href="https://wpdeveloper.net/docs/automatically-tweet-wordpress-posts/">Doc</a> <br /> <a target="_blank" href="https://developer.twitter.com/">Click here</a> to Retrieve Your API Keys from your Twitter account',
    },
    linkedin: {
        title: 'Linkedin',
        subtitle:
            'For details on Linkedin configuration, check out this <a target="_blank" href="https://wpdeveloper.net/docs/share-wordpress-posts-on-linkedin">Doc</a> <br /> <a target="_blank" href="https://www.linkedin.com/developers/">Click here</a> to Retrieve Your API Keys from your Linkedin account',
    },
    pinterest: {
        title: 'Pinterest',
        subtitle:
            'For details on Pinterest configuration, check out this <a target="_blank" href="https://wpdeveloper.net/docs/wordpress-posts-on-pinterest/">Doc</a> <br /> <a target="_blank" href="https://developers.pinterest.com">Click here</a> to Retrieve Your API Keys from your Pinterest account',
    },
}
