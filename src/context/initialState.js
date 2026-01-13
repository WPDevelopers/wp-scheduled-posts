const initialState = {
  isOpenCustomSocialMessageModal: false,
  publishImmediately: false,
  isScheduled: false,
  scheduleType: '',
  scheduleDate: '',
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
