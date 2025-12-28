const AppReducer = (state, action) => {
  switch (action.type) {
    case 'SET_CUSTOM_SOCIAL_MESSAGE_MODAL':
      return {
        ...state,
        isOpenCustomSocialMessageModal: action.payload,
      };
    default:
      return state;
  }
};

export default AppReducer;
