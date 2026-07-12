const initialState = {
  isOpenCustomSocialMessageModal: false,
  // When true, the social-message modal opens straight to the AI Caption drawer
  // (set by the panel header's "Write With AI" button).
  autoOpenAICaption: false,
  isOpenProPopup: false,
  publishImmediately: false,
  isScheduled: false,
  scheduleType: '',
  scheduleDate: '',
  scheduleDateSource: '',
  unpublishOn: '',
  republishOn: '',
  advancedSchedule: false,
  advancedScheduleDate: '',
  socialShareSettings: {
    isSocialShareDisabled: false,
    socialBannerId: null,
    socialBannerUrl: '',
  },
};

export default initialState;
