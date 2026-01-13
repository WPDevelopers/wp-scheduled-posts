const AppReducer = (state, action) => {
  switch (action.type) {
    case 'SET_CUSTOM_SOCIAL_MESSAGE_MODAL':
      return {
        ...state,
        isOpenCustomSocialMessageModal: action.payload,
      };
    case 'SET_PUBLISH_IMMEDIATELY':
      return {
        ...state,
        publishImmediately: action.payload,
      };
    case 'SET_UNPUBLISH_ON':
      return {
        ...state,
        unpublishOn: action.payload,
      };
    case 'SET_REPUBLISH_ON':
      return {
        ...state,
        republishOn: action.payload,
      };
    case 'SET_ADVANCED_SCHEDULE':
      return {
        ...state,
        advancedSchedule: action.payload,
      };
    case 'SET_ADVANCED_SCHEDULE_DATE':
      return {
        ...state,
        advancedScheduleDate: action.payload,
      };
    case 'SET_IS_SCHEDULED':
      return {
        ...state,
        isScheduled: action.payload,
      };
    case 'SET_SCHEDULE_TYPE':
      return {
        ...state,
        scheduleType: action.payload,
      };
    case 'SET_SCHEDULE_DATE':
      return {
        ...state,
        scheduleDate: action.payload,
      };
    case 'SET_SOCIAL_SHARE_SETTINGS':
      return {
        ...state,
        socialShareSettings: {
          ...state.socialShareSettings,
          ...action.payload,
        },
      };
    default:
      return state;
  }
};

export default AppReducer;
