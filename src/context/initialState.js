const initialState = {
  isOpenCustomSocialMessageModal: false,
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
