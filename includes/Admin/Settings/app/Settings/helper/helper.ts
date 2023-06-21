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

// Active social profile tab
export const activeSocialTab = () => {
    history.pushState(null, null, window.location.href.split("&")[0]);
    const selectSocialProfileTab = document.querySelectorAll('[data-key="layout_social_profile"]');
    const selectSocialProfileSection = document.getElementById('layout_social_profile');
    selectSocialProfileSection.classList.add('wprf-active');
    selectSocialProfileTab.forEach((element) => {
        element.classList.add('wprf-active-nav');
    });
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
