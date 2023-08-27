import apiFetch from '@wordpress/api-fetch';

// Fetch data from API
export const fetchDataFromAPI = async (body) => {
    // @ts-ignore 
    const ajax_url = wpspSettingsGlobal?.admin_ajax;
    const response = await fetch(ajax_url, {
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

export const getLicense = async (body) => {
    return apiFetch( {
        path: 'wp-scheduled-posts/v1/get_license',
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

export const  convertTo12HourFormat = (time24) => {
    if ( !/\d{1,2}:\d{2} [ap]m/i.test(time24)) {
        const [hours, minutes] = time24.split(':');
        const isPM = parseInt(hours, 10) >= 12;
    
        let hours12 = parseInt(hours, 10) % 12;
        hours12 = hours12 === 0 ? 12 : hours12; // Handle midnight (00:00) as 12 AM
        return `${hours12}:${minutes} ${isPM ? 'PM' : 'AM'}`;
    }
    return time24;
}

export const to24HourFormat = (time: string) => {
  if (/\d{1,2}:\d{2} [ap]m/i.test(time)) {
    const [hours, minutes] = time.match(/\d+/g);
    const isPM = time.toLowerCase().indexOf('pm') > -1;
    const _hours = parseInt(hours, 10) + (isPM && hours !== '12' ? 12 : 0) - (hours === '12' && !isPM ? 12 : 0);
    const hours24 = _hours === 24 ? 0 : _hours;
    const paddedHours = hours24.toString().padStart(2, '0');
    const paddedMinutes = minutes.padStart(2, '0');
    const timeString = `${paddedHours}:${paddedMinutes}:00`;
    return timeString;
  }
  return time;
}
// Generate time options
export const generateTimeOptions = () => {
    const times = [];
    const startTime = new Date();
    startTime.setHours(0, 0, 0, 0); // Set start time to 12:00 AM
    for (let i = 0; i < 24 * 4; i++) {
        const time = new Date(startTime.getTime() + i * 15 * 60000);
        let hours = time.getHours();
        const minutes = time.getMinutes();
        if (hours >= 24) {
            hours %= 24; // Reset hours to 0 after 23
        }
        const timeString = time.toLocaleString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        });
        // Format time in 24-hour format for value
        const valueTimeString = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
        times.push({ value: valueTimeString, label: timeString });
    }
    return times;
};

export const findOptionLabelByValue = (data, targetValue) => {
    if (data[targetValue]) {
        return { value: targetValue, label: data[targetValue].label };
    }

    for (const key in data) {
        if (typeof data[key] === "object" && data[key].value === targetValue) {
            return { value: targetValue, label: data[key].label };
        }
    }

    for (const key in data) {
        if (typeof data[key] === "object" && data[key].options) {
            const foundLabel = findOptionLabelByValue(data[key].options, targetValue);
            if (foundLabel) {
                return foundLabel;
            }
        }
    }

    return null;
}