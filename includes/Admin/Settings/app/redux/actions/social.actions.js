export const FETCH_SOCIAL_POPUP_INFO = 'FETCH_SOCIAL_POPUP_INFO'
export const IS_SHOW_REDIRECT_POPUP = 'IS_SHOW_REDIRECT_POPUP'
export const fetch_social_popup_info = () => {
    let queryString = new URLSearchParams(window.location.search)
    let redirectFromOauth = false
    let type = queryString.get('type')
    if (
        queryString.get('action') === 'wpsp_social_add_social_profile' ||
        (queryString.get('action') === 'wpsp_social_add_social_profile' &&
            queryString.get('code')) ||
        (queryString.get('oauth_verifier') && queryString.get('oauth_token'))
    ) {
        redirectFromOauth = true
    }
    let social = {
        redirectFromOauth,
        queryString,
        type,
    }
    return (dispatch) => {
        dispatch({
            type: FETCH_SOCIAL_POPUP_INFO,
            payload: social,
        })
    }
}
export const close_redirect_popup = () => {
    return (dispatch) => {
        dispatch({
            type: IS_SHOW_REDIRECT_POPUP,
            payload: false,
        })
    }
}
