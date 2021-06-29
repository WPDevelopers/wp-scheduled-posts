import {
    FETCH_SOCIAL_POPUP_INFO,
    IS_SHOW_REDIRECT_POPUP,
} from './../actions/social.actions'
function social(state = {}, action) {
    switch (action.type) {
        case FETCH_SOCIAL_POPUP_INFO:
            return action.payload
        case IS_SHOW_REDIRECT_POPUP:
            return { ...state, ...{ redirectFromOauth: action.payload } }
        default:
            return state
    }
}

export default social
