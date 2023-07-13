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
    return await apiFetch( {
        path: 'wp-scheduled-posts/v1/fetch_pinterest_section',
        method: 'POST',
        data: body,
    } ).then( ( res ) => {
        return res;
    } );
};

export const activateLicense = async (body) => {
    return apiFetch( {
        path: 'wp-scheduled-posts/v1/activate_license',
        method: 'POST',
        data: body,
    } ).then( ( res ) => {
        return res;
    } );
};

export const deActivateLicense = async () => {
    return apiFetch( {
        path: 'wp-scheduled-posts/v1/deactivate_license',
        method: 'POST',
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
       return { error:true, message };
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
    let data = {
        defaultBoard: defaultBoard,
        profile: profile,
    };
    const response = await fetPinterestBoardData(data);
    return response;
}

// Format date-time
export const getFormatDateTime = ( dateTime = '' ) => {
    const date = new Date(dateTime);
    const formattedDate = date.toLocaleDateString('en-US', { day: 'numeric', month: 'long', year: 'numeric' });
    return formattedDate;
}