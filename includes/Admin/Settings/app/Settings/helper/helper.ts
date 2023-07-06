import apiFetch from '@wordpress/api-fetch';
import { sprintf, __ } from "@wordpress/i18n";
import { isObject } from "quickbuilder";

// Fetch data from API
export const fetchDataFromAPI = async (body) => {
    const response = await fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(body).toString(),
    });
    return response;
};

export const fetPinterestBoardData = async (body) => {
    return apiFetch( {
        path: 'wp-scheduled-posts/v1/fetch_pinterest_section',
        method: 'POST',
        data: body,
    } ).then( ( res ) => {
        return res;
    } );
};

// Active social profile tab
export const generateTabURL = () => {
    history.pushState(null, null, window.location.href.split("&")[0]);
}

// Send API request for fetch url
export const socialProfileRequestHandler = async (redirectURI, appID, appSecret, platform) => {
    const data = {
        action: 'wpsp_social_add_social_profile',      
        redirectURI: redirectURI,
        appId: appID,
        appSecret: appSecret,
        type: platform,
    };
    const response = await fetchDataFromAPI(data);
    
    const responseData = await response.json();
    if (responseData.success) {
        open(responseData.data, '_self');
        } else {
        let message;
        try {
            const parsedData = JSON.parse(responseData.data);
            if (parsedData?.errors?.[0]?.message) {
            message = parsedData.errors[0].message;
            } else {
            message = responseData.data;
            }
        } catch (e) {
            message = responseData.data;
        }
        console.log(message);
    }
};

export const getProfileData = async (params) => {
    const data = {
        action: "wpsp_social_profile_fetch_user_info_and_token",
        type: params.get("type"),
        appId: params.get("appId"),
        appSecret: params.get("appSecret"),
        code: params.get("code"),
        redirectURI: params.get("redirectURI"),
        access_token: params.get("access_token"),
        refresh_token: params.get("refresh_token"),
        expires_in: params.get("expires_in"),
        rt_expires_in: params.get("rt_expires_in"),
        oauthVerifier: params.get("oauth_verifier"),
        oauthToken: params.get("oauth_token"),
    };
    const response = await fetchDataFromAPI(data);
    return response.json();
}

export const getPinterestBoardSection = async (defaultBoard,profile) => {
    var data = {
        defaultBoard: defaultBoard,
        profile: profile,
    };
    const response = await fetPinterestBoardData(data);
    console.log(response);
    
    return response;
}

// Format date-time
export const getFormatDateTime = ( dateTime = '' ) => {
    const date = new Date(dateTime);
    const formattedDate = date.toLocaleDateString('en-US', { day: 'numeric', month: 'long', year: 'numeric' });
    return formattedDate;
}

// Pro alert
// export const proAlert = (html = null) => {
//     let htmlObject = {};
//     if (html === null) {
//         html = sprintf(
//             __(
//                 "You need to upgrade to the <strong><a href='%s' target='_blank'>Premium Version</a></strong> to use this feature.",
//                 "notificationx"
//             ),
//             "http://wpdeveloper.com/in/upgrade-notificationx"
//         );
//     }
//     if (isObject(html)) {
//         htmlObject = html;
//         html = html.message || html.html;
//     }
//     let alertOptions = {
//         showConfirmButton: false,
//         showDenyButton: true,
//         type: "warning",
//         title: __("Opps...", "notificationx"),
//         customClass: {
//             actions: "nx-pro-alert-actions",
//         },
//         denyButtonText: "Close",
//         ...htmlObject,
//         html,
//     };
//     return SweetAlert(alertOptions);
// };
