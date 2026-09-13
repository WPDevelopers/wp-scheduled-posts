import apiFetch from '@wordpress/api-fetch';

export const removeNull = (obj) => {
  Object.keys(obj).forEach((key) => {
    if (obj[key] === null) {
      delete obj[key];
    }
  });
  return obj;
}
// Fetch data from API
export const fetchDataFromAPI = async (body) => {
    // @ts-ignore
    const ajax_url = wpspSettingsGlobal?.admin_ajax;
    const response = await fetch(ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(removeNull(body)).toString(),
    });
    return response;
};

export const fetchCategories = async (data) => {
    const { limit, page } = data;
    const queryString = new URLSearchParams({ limit, page }).toString();
    return await apiFetch({
        path: `wp-scheduled-posts/v1/get-categories?${queryString}`,
        method: 'GET',
    }).then((res) => {
        return res;
    });
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

export const updateRefreshToken = async (data) => {
    return await apiFetch({
        path: 'wp-scheduled-posts/v1/update-refresh-token',
        method: 'POST',
        data: data,
    }).then((res) => {
        return res;
    });
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

// Ask the server for the provider's authorisation URL without navigating to it.
// Splitting this out lets the reconnect flow put the same URL in a popup instead
// of throwing the whole settings screen away mid-task.
//
// `options.popup` tells the server the callback is coming back to a popup, so it
// returns to the bridge that hands the result to this screen instead of loading
// a second settings screen in there. `options.accountType` is what the profile
// being reconnected actually is — the stored profile knows, where the leftover
// localStorage value from the last connect does not.
export const getSocialAuthUrl = async (redirectURI, appID, appSecret, platform, openIDConnect = false, options: { popup?: boolean, accountType?: string } = {}) => {
    const account_type = options?.accountType ?? localStorage.getItem('account_type');
    // @ts-ignore
    const nonce = wpspSettingsGlobal?.api_nonce;
    const data = {
        action: 'wpsp_social_add_social_profile',
        nonce: nonce,
        redirectURI: redirectURI,
        appId: appID,
        appSecret: appSecret,
        type: platform,
        openIDConnect: openIDConnect,
        accountType: account_type,
        popupCallback: options?.popup ? 1 : 0,
    };
    const response = await fetchDataFromAPI(data);
    const responseData = await response.json();

    if (responseData.success) {
        return { url: responseData.data };
    }

    let message;
    try {
        const parsedData = JSON.parse(responseData.data);
        message = parsedData?.errors?.[0]?.message ?? responseData.data;
    } catch (e) {
        message = responseData.data;
    }
    return { error: true, message };
};

// Send API request for fetch url
export const socialProfileRequestHandler = async (redirectURI, appID, appSecret, platform, openIDConnect = false) => {
    const result = await getSocialAuthUrl(redirectURI, appID, appSecret, platform, openIDConnect);
    if (result?.url) {
        open(result.url, '_self');
        return;
    }
    return result;
};

export const WPSP_AUTOMATIC_REDIRECT_URI = 'https://devapi.schedulepress.com/v2/callback.php';
export const WPSP_MANUAL_REDIRECT_URI = 'https://api.schedulepress.com/callback.php';

/**
 * Ask the server to renew one profile in place.
 *
 * Resolves to either { reconnected: true } when the token could be renewed
 * silently, or { needs_auth: true, method, app_id, app_secret } when the grant is
 * gone and the provider's consent screen has to be shown again.
 */
export const reconnectProfile = async (platform, item) => {
    try {
        const res = await apiFetch({
            path: 'wp-scheduled-posts/v1/update-refresh-token',
            method: 'POST',
            data: { platform, item },
        });
        // @ts-ignore — WP wraps wp_send_json_success payloads in `data`.
        return res?.data ?? res;
    } catch (error) {
        return { error: true, message: error?.message ?? 'Reconnect request failed.' };
    }
};

// Shared with the server-side callback bridge (WPSP\Social\OAuthPopup): the name
// the popup is opened under, and the message it sends back.
export const WPSP_OAUTH_POPUP_NAME = 'wpsp_reconnect';
export const WPSP_OAUTH_MESSAGE_TYPE = 'wpsp_oauth_callback';

/**
 * Run the provider's consent screen in a popup and resolve once it comes back.
 *
 * The provider redirects to the middleware, which redirects back to this site —
 * where the callback bridge posts the query string here and closes the popup,
 * rather than loading the settings screen a second time inside it. That matters
 * for more than tidiness: the code in that callback can only be exchanged once,
 * and a settings screen booting in the popup would spend it before this one can.
 *
 * The polling below is the fallback for a callback that reaches the popup
 * without the bridge having run — an authorisation started somewhere that builds
 * its own return URL. It reads the popup directly, which is only possible once
 * the popup is back on this origin; before that every access throws, which is
 * exactly the signal that the author is still on the provider's own pages.
 */
export const openAuthPopup = (authUrl) => new Promise<any>((resolve) => {
    const width = 620;
    const height = 720;
    const left = window.screenX + Math.max(0, (window.outerWidth - width) / 2);
    const top = window.screenY + Math.max(0, (window.outerHeight - height) / 2);
    const popup = window.open(authUrl, WPSP_OAUTH_POPUP_NAME, `width=${width},height=${height},left=${left},top=${top},scrollbars=yes`);

    if (!popup) {
        resolve({ error: true, message: 'Popup blocked. Allow popups for this site and try again.' });
        return;
    }

    let settled = false;
    const finish = (result) => {
        if (settled) {
            return;
        }
        settled = true;
        window.removeEventListener('message', onMessage);
        clearInterval(timer);
        try {
            if (!popup.closed) {
                popup.close();
            }
        } catch (e) {}
        resolve(result);
    };

    const onMessage = (event) => {
        if (event.origin !== window.location.origin) {
            return;
        }
        const payload = event?.data;
        if (!payload || payload.type !== WPSP_OAUTH_MESSAGE_TYPE || !payload.search) {
            return;
        }
        finish({ search: payload.search });
    };
    window.addEventListener('message', onMessage);

    const timer = setInterval(() => {
        if (popup.closed) {
            // Give a message that is already in flight the chance to land before
            // calling this a cancellation.
            setTimeout(() => finish({ cancelled: true }), 300);
            return;
        }
        let search = null;
        try {
            // Throws while the popup is on the provider's domain.
            search = popup.location.search;
            if (!popup.location.href.startsWith(window.location.origin)) {
                return;
            }
        } catch (e) {
            return;
        }
        if (search && search.indexOf('wpsp_social_add_social_profile') !== -1) {
            finish({ search });
        }
    }, 400);
});

/**
 * What kind of account a stored profile is, in the terms the authorisation
 * request expects.
 *
 * The connect flow takes this from whichever button the author pressed and
 * leaves it in localStorage. A reconnect has no button to read, and the value
 * left over from the last connect is just as likely to be the wrong one — which
 * on LinkedIn means asking for member scopes to renew a company page. The stored
 * profile already records what it is, so that is what gets asked for.
 */
export const reconnectAccountType = (platform, item) => {
    if (platform === 'linkedin') {
        return item?.type === 'organization' ? 'page' : 'profile';
    }
    if (platform === 'facebook') {
        return item?.type === 'group' ? 'group' : 'page';
    }
    return item?.type ?? '';
}

/**
 * Reconnect one or many profiles from a single click.
 *
 * Renewals that need no consent screen are done first and all together, so a
 * batch of expired profiles usually completes without the author touching
 * anything. Only grants that are genuinely gone fall through to a popup, and
 * those are run one at a time because each finishes on the normal "choose which
 * page to add" screen.
 *
 * @param targets    [{ platform, item }]
 * @param onProgress Optional callback fired as each target settles.
 */
export const runReconnect = async (targets, onProgress = null) => {
    const renewed = [];
    const needsAuth = [];
    const failed = [];

    for (const target of targets) {
        const result = await reconnectProfile(target.platform, target.item);
        if (result?.reconnected) {
            renewed.push(target);
        } else if (result?.needs_auth) {
            needsAuth.push({ ...target, auth: result });
        } else {
            failed.push({ ...target, message: result?.message ?? 'Reconnect failed.' });
        }
        if (onProgress) {
            onProgress({ target, result, renewed, needsAuth, failed });
        }
    }

    // Everything renewed in place — nothing left to ask the author for.
    if (!needsAuth.length) {
        return { renewed, needsAuth, failed, completed: true };
    }

    const next = needsAuth[0];
    const redirectURI = next.auth.method === 'automatic'
        ? WPSP_AUTOMATIC_REDIRECT_URI
        : WPSP_MANUAL_REDIRECT_URI;

    const authUrl = await getSocialAuthUrl(
        redirectURI,
        next.auth.app_id ?? '',
        next.auth.app_secret ?? '',
        next.platform,
        // LinkedIn hands out different scopes to an OpenID app than to an older
        // member app, and asking for the wrong set is refused outright — so a
        // reconnect asks for whatever this profile was connected with.
        !! next.item?.openIDConnect,
        { popup: true, accountType: reconnectAccountType(next.platform, next.item) }
    );

    if (authUrl?.error || !authUrl?.url) {
        failed.push({ ...next, message: authUrl?.message ?? 'Could not start authorisation.' });
        return { renewed, needsAuth, failed, completed: false };
    }

    const popup = await openAuthPopup(authUrl.url);
    if (popup?.search) {
        // Exchange the callback for the account details, then let the server
        // merge them onto the profile being reconnected. Nothing navigates: the
        // popup is already closed and the settings screen never moved.
        const params = new URLSearchParams(popup.search);

        // The provider refused, or the author declined on its screen — that
        // comes back in place of a code, and there is nothing to exchange.
        const denied = params.get('error_message') ?? params.get('error_description') ?? params.get('error');
        if (denied) {
            failed.push({ ...next, message: denied });
            return { renewed, needsAuth, failed, completed: false, remaining: needsAuth.slice(1) };
        }

        const fetched = await getProfileData(params);
        const merged = await completeReconnect(
            next.platform,
            next.item?.id ?? next.item?.__id ?? '',
            fetched
        );

        if (merged?.success) {
            renewed.push(next);
            return { renewed, needsAuth, failed, completed: needsAuth.length === 1, remaining: needsAuth.slice(1) };
        }

        failed.push({ ...next, message: merged?.message ?? 'Reconnect could not be completed.' });
        return { renewed, needsAuth, failed, completed: false, remaining: needsAuth.slice(1) };
    }

    return {
        renewed,
        needsAuth,
        failed,
        completed: false,
        cancelled: popup?.cancelled ?? false,
        message: popup?.message,
    };
};

/**
 * Finish a reconnect once the consent screen has been dealt with.
 *
 * The normal connect flow ends on the "choose which account to add" screen,
 * which is why it needs a whole page. A reconnect already knows which profile it
 * is renewing, so the fetched accounts go straight to the server to be matched
 * and merged - the author stays on the settings screen throughout.
 */
export const completeReconnect = async (platform, id, payload) => {
    try {
        const res = await apiFetch({
            path: 'wp-scheduled-posts/v1/complete-reconnect',
            method: 'POST',
            data: { platform, id, payload },
        });
        // @ts-ignore
        return res?.data ?? res;
    } catch (error) {
        return { success: false, message: error?.message ?? 'Reconnect could not be completed.' };
    }
};

export const getProfileData = async (params) => {
    // @ts-ignore 
    const nonce = wpspSettingsGlobal?.api_nonce;
    const data = {
        action: "wpsp_social_profile_fetch_user_info_and_token",
        nonce : nonce,
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
        openIDConnect: params.get("openIDConnect"),
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

/**
 * Work out when a connected profile's access token stops working.
 *
 * The platforms do not agree on how this is stored, so all three shapes have to
 * be handled here rather than at every call site:
 *
 *  - Pinterest / LinkedIn / Google Business keep `expires_in` as an absolute
 *    unix timestamp (the connect handler stores `time() + expires_in`).
 *  - Threads keeps `expires_in` as the raw seconds-to-live Meta returned, so it
 *    only means anything measured from `added_date`.
 *  - Instagram keeps a preformatted date string in `expires_at`.
 *  - Twitter, Facebook, Medium, Bluesky and Mastodon store nothing, because
 *    their credentials do not expire on a timer.
 *
 * A real expiry timestamp is far above 1e9 (year 2001) while a lifetime in
 * seconds is far below it, which is what separates the first two cases.
 *
 * @return null when the platform has no expiry, otherwise the parsed date, the
 *         days remaining and whether it has already lapsed.
 */
export const getProfileExpiry = ( item ) => {
    if ( ! item ) {
        return null;
    }

    let expiryDate = null;

    if ( item?.expires_at ) {
        const parsed = new Date( String( item.expires_at ).replace( ' ', 'T' ) );
        if ( ! isNaN( parsed.getTime() ) ) {
            expiryDate = parsed;
        }
    } else if ( item?.expires_in ) {
        const raw = Number( item.expires_in );
        if ( ! isNaN( raw ) && raw > 0 ) {
            if ( raw > 1000000000 ) {
                expiryDate = new Date( raw * 1000 );
            } else if ( item?.added_date ) {
                const addedAt = new Date( String( item.added_date ).replace( ' ', 'T' ) );
                if ( ! isNaN( addedAt.getTime() ) ) {
                    expiryDate = new Date( addedAt.getTime() + raw * 1000 );
                }
            }
        }
    }

    if ( ! expiryDate ) {
        return null;
    }

    const msLeft = expiryDate.getTime() - Date.now();

    // A profile the background job can renew on its own is not in trouble just
    // because its current token is short-lived — Google hands out one-hour access
    // tokens, so judging that connection by this clock would show it as broken
    // almost permanently. What matters there is whether the renewal still works,
    // which the maintenance pass records on the profile when it stops.
    // Only a separate refresh token means the stored date is just an access-token
    // clock that renews behind the scenes (Google Business, Pinterest, LinkedIn).
    // Meta's long-lived tokens are their own refresh window — once that date
    // passes the grant is gone for good and cannot be renewed by anything — so
    // they must not be treated as self-healing.
    const renewalFailed = !! item?.renewal_failed;
    const autoRenews = ! renewalFailed && !! item?.refresh_token;

    return {
        date: expiryDate,
        expired: msLeft <= 0,
        daysLeft: Math.ceil( msLeft / 86400000 ),
        autoRenews,
        renewalFailed,
        // What the status icon should actually go by.
        needsAttention: renewalFailed || ( msLeft <= 0 && ! autoRenews ),
    };
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

export const isObject = (arg) => {
    return arg !== null && typeof arg === 'object' && !Array.isArray(arg);
};

// Function to handle image load error
export const handleImageError = (e) => {
    // @ts-ignore 
    const placeholderImage = `${wpspSettingsGlobal?.assets_path}/images/author-logo.jpeg`;
    e.target.onerror = null; // Prevents infinite loop in case placeholder image fails
    e.target.src = placeholderImage; // Set the placeholder image
};