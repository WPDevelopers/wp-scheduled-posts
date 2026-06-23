import React, { useContext, useState, useEffect, useCallback, useMemo, useRef } from 'react';
import { Button } from '@wordpress/components';
const { __ } = wp.i18n;
import { AppContext } from '../../../context/AppContext';
import Header from './Header';
import { facebook, twitter_x, linkedin, pinterest, instagram, medium, threads, google_business } from '../../../icons/icons';

// Sub-components
import PlatformNavigation from './PlatformNavigation';
import ProfileSelector from './ProfileSelector';
import TemplateEditor from './TemplateEditor';
import ScheduleControls from './ScheduleControls';
import PreviewCard from './PreviewCard';
import useSocialProfiles from './hooks/useSocialProfiles';
import useCurrentPostData from './hooks/useCurrentPostData';
import AllDisabledPlatform from './AllDisabledPlatform';
import AICaptionDrawer from './AICaptionDrawer';

const SOCIAL_PLATFORMS = [
  'facebook',
  'twitter',
  'linkedin',
  'pinterest',
  'instagram',
  'medium',
  'threads',
  'google_business',
];

const platformLimits = {
  facebook: 63206,
  twitter: 280,
  linkedin: 1300,
  pinterest: 500,
  instagram: 2100,
  medium: 45000,
  threads: 480,
  google_business: 1500,
};

const DEFAULT_TEMPLATE = '{title} {content} {url} {tags}';

const getDefaultScheduleData = (postStatus) => {
  const isPublished = postStatus === 'publish';
  return {
    dateOption: isPublished ? 'today' : 'same_day',
    customDays: '',
    customDate: '',
    timeOption: isPublished ? 'in_1h' : 'same_time',
    customHours: '',
    customTime: '',
    schedulingType: isPublished ? 'absolute' : 'relative',
  };
};

const WPSPCustomTemplateModal = ({
  WPSchedulePostsFree = { adminURL: '#', assetsURI: '', socialProfileURL: window.WPSchedulePostsFree.socialProfileURL },
  info = 'Info message here',
  // Profile data props are no longer needed, we fetch them internally
  // Post data props
  postId: propPostId,
  postStatus: propPostStatus,
  postTitleProp,
  postContentProp,
  postUrlProp,
  uploadSocialShareBanner,
}) => {
  const { state, dispatch } = useContext(AppContext);
  const { socialProfiles, isLoading: isProfilesLoading } = useSocialProfiles();
  
  const { postId, postStatus, postTitle, postContent, postUrl, featuredImageUrl } = useCurrentPostData({
    postId: propPostId,
    postStatus: propPostStatus,
    postTitleProp,
    postContentProp,
    postUrlProp,
  });

  // Derived state
  const socialBannerUrl = state?.socialShareSettings?.socialBannerUrl;
  const bannerImage = uploadSocialShareBanner || socialBannerUrl || featuredImageUrl;
  const social_media_enabled = window.WPSchedulePostsFree?.social_media_enabled || {};
  const isPro = !!window.WPSchedulePostsFree?.is_pro;
  
  const platforms = [
    { platform: 'facebook', icon: facebook, color: '#1877f2', bgColor: '#1877f2' },
    { platform: 'twitter', icon: twitter_x, color: '#000000', bgColor: '#000000' },
    { platform: 'linkedin', icon: linkedin, color: '#0077b5', bgColor: '#0077b5' },
    { platform: 'pinterest', icon: pinterest, color: '#bd081c', bgColor: '#bd081c' },
    { platform: 'instagram', icon: instagram, color: '#e4405f', bgColor: '#e4405f' },
    { platform: 'medium', icon: medium, color: '#00ab6c', bgColor: '#00ab6c' },
    { platform: 'threads', icon: threads, color: '#000', bgColor: '#000' },
    { platform: 'google_business', icon: google_business, color: '#db4437', bgColor: '#db4437' },
  ];

  // Filter platforms based on what's enabled
  const filteredPlatforms = platforms.filter(({ platform }) => social_media_enabled[platform]);
  const firstSelectedProfile = Object.entries(social_media_enabled).find(([key, value]) => value === true)?.[0];

  // State
  const [showPreview, setShowPreview] = useState(true);
  const [showGlobalTemplateWarning, setShowGlobalTemplateWarning] = useState(false);
  const [selectedPlatform, setSelectedPlatform] = useState(firstSelectedProfile || 'facebook');
  const [selectedProfile, setSelectedProfile] = useState([]);
  const [customTemplates, setCustomTemplates] = useState({});
  const [characterCount, setCharacterCount] = useState(0);
  const [saveText, setSaveText] = useState(__('Save', 'wp-scheduled-posts'));
  const [saveError, setSaveError] = useState('');
  const [isSaving, setIsSaving] = useState(false);
  const [isUpdatingContent, setIsUpdatingContent] = useState(false);
  const [allPlatformData, setAllPlatformData] = useState({});
  const [apiTemplateData, setApiTemplateData] = useState({});
  const [hasLoadedScheduling, setHasLoadedScheduling] = useState(false);
  const [scheduleData, setScheduleData] = useState(getDefaultScheduleData(postStatus));
  const [isAICaptionOpen, setIsAICaptionOpen] = useState(false);
  const [hasFetchedTemplates, setHasFetchedTemplates] = useState(false);
  const hasSeededDefaultsRef = useRef(false);

  // The panel header's "Write With AI" button opens this modal with a flag set so
  // the AI Caption drawer appears immediately. Consume and clear the flag once.
  useEffect(() => {
    if (state.autoOpenAICaption) {
      setIsAICaptionOpen(true);
      dispatch({ type: 'SET_AUTO_OPEN_AI_CAPTION', payload: false });
    }
  }, [state.autoOpenAICaption, dispatch]);

  // API functions for data management
  const fetchTemplateData = useCallback(async () => {
    if (!postId) return {};

    try {
      const response = await wp.apiFetch({
        path: `/wp-scheduled-posts/v1/custom-templates/${postId}`,
        method: 'GET',
      });

      if (response && (response.success === undefined || response.success)) {
        // Handle both standard WP response and direct data return
        const data = response.data || response;
        const templatesOnly = { ...(data || {}) };
        const scheduling = templatesOnly.scheduling;
        delete templatesOnly.scheduling;

        setApiTemplateData(templatesOnly);

        if (scheduling && typeof scheduling === 'object') {
          setScheduleData({
            ...getDefaultScheduleData(postStatus),
            ...scheduling,
          });
          setHasLoadedScheduling(true);
        } else {
          setScheduleData(getDefaultScheduleData(postStatus));
          setHasLoadedScheduling(false);
        }
        return data || {};
      } else {
        console.error('Failed to fetch template data:', response.message);
        return {};
      }
    } catch (error) {
      console.error('Error fetching template data:', error);
      return {};
    }
  }, [postId, postStatus]);

  // Global template management
  const getIsGlobalForPlatform = useCallback((platform) => {
    const platformData = apiTemplateData[platform];
    return platformData?.is_global === 1 || platformData?.is_global === '1' || platformData?.is_global === true;
  }, [apiTemplateData]);

  const getProfileStorageId = useCallback((platform, profile) => {
    if (!profile) return '';
    if (platform === 'pinterest') {
      return String(profile.boardId || profile.id || '');
    }
    return String(profile.id || '');
  }, []);

  const getSelectedProfileIdsForPlatform = useCallback((platform = selectedPlatform) => {
    const ids = selectedProfile
      .map((profile) => getProfileStorageId(platform, profile))
      .filter(Boolean);
    // Pinterest can have multiple selectable entries that resolve to the same board id.
    // Keep duplicates so checking another Pinterest entry is treated as a real update.
    if (platform === 'pinterest') {
      return ids;
    }
    return Array.from(new Set(ids));
  }, [selectedProfile, selectedPlatform, getProfileStorageId]);

  // Default profile selection for a platform on a new post: only enabled profiles
  // (status !== false), and on the free plan capped to the first one per platform.
  // This mirrors Helper::get_social_profile() so the UI matches what is shared.
  const getDefaultProfileIdsForPlatform = useCallback((platform) => {
    const list = (socialProfiles && socialProfiles[platform]) || [];
    const enabled = list.filter((profile) => profile.status !== false);
    const allowed = isPro ? enabled : enabled.slice(0, 1);
    const ids = allowed
      .map((profile) => getProfileStorageId(platform, profile))
      .filter(Boolean);
    return platform === 'pinterest' ? ids : Array.from(new Set(ids));
  }, [socialProfiles, getProfileStorageId, isPro]);

  // Whether this post already has a saved custom template/selection. New posts have
  // none, which is how we know to default-select everything.
  const hasSavedCustomTemplate = useMemo(() => (
    SOCIAL_PLATFORMS.some((platform) => {
      const data = apiTemplateData[platform];
      if (!data) return false;
      const hasTpl = typeof data.template === 'string' && data.template.trim() !== '';
      const hasProf = Array.isArray(data.profiles) && data.profiles.length > 0;
      return hasTpl || hasProf;
    })
  ), [apiTemplateData]);

  const setUseGlobalTemplatePlatform = useCallback((platform, checked) => {
    const updateData = (prev) => ({
      ...prev,
      [platform]: {
        ...(prev[platform] || {}),
        template: customTemplates[platform] || '',
        profiles: getSelectedProfileIdsForPlatform(platform),
        is_global: checked ? 1 : '',
      }
    });

    setApiTemplateData(updateData);
    setAllPlatformData(updateData);
  }, [customTemplates, getSelectedProfileIdsForPlatform]);

  // Get available profiles for selected platform
  const getAvailableProfiles = useCallback(() => {
    if (!socialProfiles) return [];
    
    switch (selectedPlatform) {
      case 'facebook': return socialProfiles.facebook || [];
      case 'twitter': return socialProfiles.twitter || [];
      case 'linkedin': return socialProfiles.linkedin || [];
      case 'pinterest': return socialProfiles.pinterest || [];
      case 'instagram': return socialProfiles.instagram || [];
      case 'medium': return socialProfiles.medium || [];
      case 'threads': return socialProfiles.threads || [];
      case 'google_business': return socialProfiles.google_business || [];
      default: return [];
    }
  }, [selectedPlatform, socialProfiles]);

  // Get date options based on post status
  const getDateOptions = () => {
    const isPublished = postStatus === 'publish';
    if (isPublished) {
      return [
        { value: 'today', label: __('Today', 'wp-scheduled-posts') },
        { value: 'tomorrow', label: __('Tomorrow', 'wp-scheduled-posts') },
        { value: 'next_week', label: __('Next week', 'wp-scheduled-posts') },
        { value: 'next_month', label: __('Next month', 'wp-scheduled-posts') },
        { value: 'in_days', label: __('In __ days', 'wp-scheduled-posts') },
        { value: 'custom_date', label: __('Choose a custom date...', 'wp-scheduled-posts') }
      ];
    } else {
      return [
        { value: 'same_day', label: __('Same day as publication', 'wp-scheduled-posts') },
        { value: 'day_after', label: __('The day after publication', 'wp-scheduled-posts') },
        { value: 'week_after', label: __('A week after publication', 'wp-scheduled-posts') },
        { value: 'month_after', label: __('A month after publication', 'wp-scheduled-posts') },
        { value: 'days_after', label: __('__ days after publication', 'wp-scheduled-posts') },
        { value: 'custom_date', label: __('Choose a custom date...', 'wp-scheduled-posts') }
      ];
    }
  };

  // Get time options based on post status
  const getTimeOptions = () => {
    const isPublished = postStatus === 'publish';
    if (isPublished) {
      return [
        { value: 'in_1h', label: __('In one hour', 'wp-scheduled-posts') },
        { value: 'in_3h', label: __('In three hours', 'wp-scheduled-posts') },
        { value: 'in_5h', label: __('In five hours', 'wp-scheduled-posts') },
        { value: 'in_hours', label: __('In __ hours', 'wp-scheduled-posts') },
        { value: 'custom_time', label: __('Choose a custom time...', 'wp-scheduled-posts') }
      ];
    } else {
      return [
        { value: 'same_time', label: __('Same time as publication', 'wp-scheduled-posts') },
        { value: 'hour_after', label: __('One hour after publication', 'wp-scheduled-posts') },
        { value: 'three_hours_after', label: __('Three hours after publication', 'wp-scheduled-posts') },
        { value: 'five_hours_after', label: __('Five hours after publication', 'wp-scheduled-posts') },
        { value: 'hours_after', label: __('__ hours after publication', 'wp-scheduled-posts') },
        { value: 'custom_time', label: __('Choose a custom time...', 'wp-scheduled-posts') }
      ];
    }
  };

  // Generate preview content
  const generatePreview = useCallback((template) => {
    if (!template) return '';
    let preview = template;
    preview = preview.replace(/{title}/g, postTitle || 'Sample Post Title');
    preview = preview.replace(/{content}/g, postContent || 'This is sample post content...');
    preview = preview.replace(/{url}/g, postUrl || 'https://example.com/post');
    preview = preview.replace(/{tags}/g, '#wordpress #blog');
    return preview;
  }, [postTitle, postContent, postUrl]);

  // Global save function
  const handleGlobalSave = async () => {
    try {
      setIsSaving(true);
      setSaveError('');
      setSaveText(isUpdatingContent ? __('Updating...', 'wp-scheduled-posts') : __('Saving...', 'wp-scheduled-posts'));

      const platformsToSave = [];
      
      // Get current platform data
      const currentPlatformData = {
        template: customTemplates[selectedPlatform] || '',
        profiles: getSelectedProfileIdsForPlatform(selectedPlatform),
        is_global: getIsGlobalForPlatform(selectedPlatform),
      };

      // Update current platform in allPlatformData
      const allData = {
        ...allPlatformData,
        [selectedPlatform]: currentPlatformData
      };

      // Process all platforms
      for (const platform of SOCIAL_PLATFORMS) {
        const platformData = allData[platform];
        const hasTemplate = platformData?.template && platformData.template.trim() !== '';
        const hasProfiles = platformData?.profiles && platformData.profiles.length > 0;

        if (hasTemplate || hasProfiles) {
          platformsToSave.push({
            platform,
            template: platformData.template || '',
            profiles: platformData.profiles || [],
            is_global: platformData.is_global ? 1 : '',
          });
        }
      }

      // Send batch request
      const schedulingPayload = {
        ...scheduleData,
        platforms: platformsToSave.map((item) => item.platform),
      };

      const response = await wp.apiFetch({
        path: `/wp-scheduled-posts/v1/custom-templates/${postId}`,
        method: 'POST',
        data: {
          platforms: platformsToSave,
          scheduling: schedulingPayload,
        },
      });

      if (response && (response.success === undefined || response.success)) {
        await fetchTemplateData();
        setAllPlatformData({});
        const successMessage = isUpdatingContent ? __('Updated Successfully', 'wp-scheduled-posts') : __('Saved Successfully', 'wp-scheduled-posts');
        setSaveText(successMessage);
        handleClose();
        setTimeout(() => setSaveText(__('Update', 'wp-scheduled-posts')), 2000);
      } else {
        throw new Error(response.message || 'Failed to save templates');
      }
      
    } catch (error) {
      // The REST endpoint returns the specific reason(s) in `errors` (e.g. a
      // platform's caption exceeding its character limit). Surface them instead
      // of a generic "Save Failed" so the user knows what to fix.
      const detail = Array.isArray(error?.errors) && error.errors.length
        ? error.errors.join(' ')
        : error?.message || '';
      setSaveError(detail);
      setSaveText(__('Save Failed', 'wp-scheduled-posts'));
      setTimeout(() => setSaveText(isUpdatingContent ? __('Update', 'wp-scheduled-posts') : __('Save', 'wp-scheduled-posts')), 2000);
      console.error('Error saving templates:', error);
    } finally {
      setIsSaving(false);
    }
  };

  // Helper to check for changes
  const hasAnyChanges = useCallback(() => {
    const currentTemplate = customTemplates[selectedPlatform] || '';
    const currentProfiles = getSelectedProfileIdsForPlatform(selectedPlatform);

    if (currentTemplate.trim() !== '' || currentProfiles.length > 0) return true;

    for (const platform of SOCIAL_PLATFORMS) {
      const platformData = allPlatformData[platform];
      if (platformData && (platformData.template.trim() !== '' || platformData.profiles.length > 0)) return true;
    }

    if (apiTemplateData) {
      for (const platform of SOCIAL_PLATFORMS) {
        const savedData = apiTemplateData[platform];
        if (savedData && (
          (savedData.template && savedData.template.trim() !== '') ||
          (savedData.profiles && savedData.profiles.length > 0)
        )) return true;
      }
    }
    return false;
  }, [customTemplates, selectedPlatform, allPlatformData, apiTemplateData, getSelectedProfileIdsForPlatform]);

  // Helper to check if platform has data
  const platformHasData = useCallback((platform) => {
    if (platform === selectedPlatform) {
      const currentTemplate = customTemplates[selectedPlatform] || '';
      const currentProfiles = getSelectedProfileIdsForPlatform(selectedPlatform);
      return currentTemplate.trim() !== '' || currentProfiles.length > 0;
    }

    const tempData = allPlatformData[platform];
    if (tempData && (tempData.template.trim() !== '' || tempData.profiles.length > 0)) return true;

    const savedData = apiTemplateData[platform];
    return savedData && (
      (savedData.template && savedData.template.trim() !== '') ||
      (savedData.profiles && savedData.profiles.length > 0)
    );
  }, [allPlatformData, apiTemplateData, selectedPlatform, customTemplates, getSelectedProfileIdsForPlatform]);

  // Handle platform switching
  const handlePlatformSwitch = useCallback((newPlatform) => {
    if (selectedPlatform) {
      const currentTemplate = customTemplates[selectedPlatform] || '';
      const currentProfiles = getSelectedProfileIdsForPlatform(selectedPlatform);

      if (currentTemplate.trim() !== '' || currentProfiles.length > 0) {
        setAllPlatformData(prev => ({
          ...prev,
          [selectedPlatform]: {
            template: currentTemplate,
            profiles: currentProfiles,
            is_global: getIsGlobalForPlatform(selectedPlatform),
          }
        }));
      } else {
        setAllPlatformData(prev => {
          const newData = { ...prev };
          delete newData[selectedPlatform];
          return newData;
        });
      }
    }
    setSelectedPlatform(newPlatform);
  }, [selectedPlatform, customTemplates, getIsGlobalForPlatform, getSelectedProfileIdsForPlatform]);

  const handleClose = () => {
    setAllPlatformData({});
    dispatch({ type: 'SET_CUSTOM_SOCIAL_MESSAGE_MODAL', payload: false });
  };

  // Effects
  useEffect(() => {
    if (!hasLoadedScheduling) {
      setScheduleData(getDefaultScheduleData(postStatus));
    }
  }, [postStatus, hasLoadedScheduling]);

  useEffect(() => {
    const preview = generatePreview(customTemplates[selectedPlatform] || '');
    setCharacterCount(preview.length);
  }, [customTemplates, selectedPlatform, postTitle, postContent, postUrl, generatePreview]);

  useEffect(() => {
    if (selectedPlatform) {
      const tempData = allPlatformData[selectedPlatform];
      const savedData = apiTemplateData[selectedPlatform];
      let dataToLoad = null;

      if (tempData && (tempData.template || tempData.profiles?.length > 0)) {
        dataToLoad = tempData;
      } else if (savedData && (savedData.template || savedData.profiles?.length > 0)) {
        dataToLoad = savedData;
        setSaveText(__('Update', 'wp-scheduled-posts'));
      }

      const available = getAvailableProfiles();

      if (dataToLoad) {
        setCustomTemplates(prev => ({ ...prev, [selectedPlatform]: dataToLoad.template || '' }));
        const profilesToSet = (dataToLoad.profiles || []).map((profileId) => {
          const normalizedId = String(profileId);
          return available.find((profile) => getProfileStorageId(selectedPlatform, profile) === normalizedId);
        }).filter(Boolean);
        setSelectedProfile(profilesToSet);
        setIsUpdatingContent(true);
      } else {
        setCustomTemplates(prev => ({ ...prev, [selectedPlatform]: DEFAULT_TEMPLATE }));
        setSelectedProfile([]);
        setSaveText(__('Save', 'wp-scheduled-posts'));
        setIsUpdatingContent(false);
      }
    }
  }, [selectedPlatform, allPlatformData, apiTemplateData, getAvailableProfiles, getProfileStorageId]);

  useEffect(() => {
      // Clear any temporary data when opening modal
      setAllPlatformData({});
      (async () => {
        await fetchTemplateData();
        setHasFetchedTemplates(true);
      })();
  }, [fetchTemplateData]); // On mount

  // For a brand-new post with no saved custom template, default the selection to
  // every enabled platform with all of its profiles. Users can then deselect from
  // "Manage Social Sharing". This mirrors the backend default (no custom template =
  // share to all enabled profiles), and seeding every enabled platform ensures a
  // Save persists them all — otherwise enabling the custom template would silently
  // stop sharing on the platforms the user never opened.
  useEffect(() => {
    if (hasSeededDefaultsRef.current) return;
    if (!hasFetchedTemplates || isProfilesLoading) return;

    // Existing post that already has saved selections — respect them, don't seed.
    if (hasSavedCustomTemplate) {
      hasSeededDefaultsRef.current = true;
      return;
    }

    const seeded = {};
    SOCIAL_PLATFORMS.forEach((platform) => {
      if (!social_media_enabled[platform]) return;
      const profileIds = getDefaultProfileIdsForPlatform(platform);
      if (!profileIds.length) return;
      seeded[platform] = { template: DEFAULT_TEMPLATE, profiles: profileIds, is_global: '' };
    });

    if (Object.keys(seeded).length) {
      // Preserve any interaction that already happened (`prev` wins).
      setAllPlatformData((prev) => ({ ...seeded, ...prev }));
    }
    hasSeededDefaultsRef.current = true;
  }, [hasFetchedTemplates, isProfilesLoading, hasSavedCustomTemplate, social_media_enabled, getDefaultProfileIdsForPlatform]);

  const availableProfiles = getAvailableProfiles();
  const currentLimit = platformLimits[selectedPlatform] || 1000;
  const isOverLimit = characterCount > currentLimit;
  
  let globalProfile = null;
  for (const [platform, config] of Object.entries(apiTemplateData)) {
    if (config.is_global === 1 || config.is_global === true) {
      globalProfile = platform;
      break;
    }
  }

  const previewContent = generatePreview(customTemplates[selectedPlatform] || '');
  const isGlobalForCurrentPlatform = getIsGlobalForPlatform(selectedPlatform);

  // Sub-component handlers
  const onSelectProfile = useCallback((profile) => {
      const isSelected = selectedProfile.some((p) => p.id === profile.id);
      if (isSelected) {
          setSelectedProfile(selectedProfile.filter((p) => p.id !== profile.id));
      } else {
          setSelectedProfile([...selectedProfile, profile]);
      }
  }, [selectedProfile]);

  const onUpdateSchedule = useCallback((field, value) => {
      setScheduleData(prev => ({ ...prev, [field]: value }));
  }, []);

  const onToggleGlobal = useCallback((e) => {
    if (globalProfile != null && globalProfile !== selectedPlatform) {
        setShowGlobalTemplateWarning(true);
        setTimeout(() => {
            setShowGlobalTemplateWarning(false);
        }, 3000);
    } else {
        setUseGlobalTemplatePlatform(selectedPlatform, e.target.checked);
    }
  }, [globalProfile, selectedPlatform, setUseGlobalTemplatePlatform]);
  
  // Handle AI caption generation. Sends the drawer form values to the AI endpoint and
  // writes the returned captions back into the matching platform templates.
  // Fetch captions for the drawer's results screen. Returns { platform: caption }
  // so the drawer can show them for review/editing — nothing is inserted here.
  const handleGenerateCaption = useCallback(async (payload, { signal } = {}) => {
    const { platforms: targetPlatforms = [] } = payload || {};

    const response = await wp.apiFetch({
      path: `/wp-scheduled-posts/v1/ai-caption/${postId}`,
      method: 'POST',
      data: payload,
      signal,
    });

    // The user hit Stop while the request was in flight — discard the result.
    if (signal?.aborted) return null;

    // Expected shape: { captions: { facebook: '...', twitter: '...' } }
    const captions = response?.captions || response?.data?.captions;
    if (!captions || typeof captions !== 'object') return null;

    const generated = {};
    targetPlatforms.forEach((platform) => {
      if (captions[platform]) generated[platform] = captions[platform];
    });
    return generated;
  }, [postId]);

  // Called from the results screen — either "Insert All Captions" (closes the
  // drawer) or a per-platform "Insert" (close=false keeps the drawer open).
  const handleInsertCaptions = useCallback((captions, { close = true } = {}) => {
    if (!captions || typeof captions !== 'object') return;
    const generatedPlatforms = Object.keys(captions).filter((platform) => captions[platform]);

    // Update the editor for the currently visible platform.
    setCustomTemplates((prev) => {
      const next = { ...prev };
      generatedPlatforms.forEach((platform) => {
        next[platform] = captions[platform];
      });
      return next;
    });

    // Persist into the temp per-platform store so switching platforms doesn't reset
    // the generated caption back to the default placeholder, and so Save picks it up.
    setAllPlatformData((prev) => {
      const next = { ...prev };
      generatedPlatforms.forEach((platform) => {
        next[platform] = {
          ...(next[platform] || {}),
          template: captions[platform],
          profiles: next[platform]?.profiles || [],
          is_global: next[platform]?.is_global || '',
        };
      });
      return next;
    });

    if (close) {
      setIsAICaptionOpen(false);
    }
  }, []);

  return (
    <div className={`wpsp-modal-content ${availableProfiles.length === 0 ? 'no-profile-found' : ''}`}>
      <Header onOpenAICaption={() => setIsAICaptionOpen(true)} />
      <div className="wpsp-modal-layout">
        {/* Left Side */}
        <div className="wpsp-modal-left">
          {filteredPlatforms?.length === 0 ? (
            <AllDisabledPlatform platforms={platforms} />
          ) : (
            <PlatformNavigation 
              platforms={platforms}
              selectedPlatform={selectedPlatform}
              onSelectPlatform={handlePlatformSwitch}
              platformHasData={platformHasData}
              filteredPlatforms={filteredPlatforms}
              social_media_enabled={social_media_enabled}
            />
          )}

          <div className="wpsp-custom-template-content-wrapper">
           { filteredPlatforms?.length !== 0 && <ProfileSelector 
                availableProfiles={availableProfiles}
                selectedProfile={selectedProfile}
                onSelectProfile={onSelectProfile}
                selectedPlatform={selectedPlatform}
                WPSchedulePostsFree={WPSchedulePostsFree}
                isLoading={isProfilesLoading}
            />
           }

            {selectedPlatform && (
                <TemplateEditor 
                    template={customTemplates[selectedPlatform]}
                    onChange={(val) => setCustomTemplates((prev) => ({ ...prev, [selectedPlatform]: val }))}
                    characterCount={characterCount}
                    currentLimit={currentLimit}
                    isOverLimit={isOverLimit}
                    showPreview={showPreview}
                    onTogglePreview={() => setShowPreview(!showPreview)}
                    isGlobal={isGlobalForCurrentPlatform}
                    onToggleGlobal={onToggleGlobal}
                    globalProfileParams={{
                        globalProfile,
                        selectedPlatform,
                        showGlobalTemplateWarning,
                        isGlobalForCurrentPlatform
                    }}
                    availableProfilesCount={availableProfiles.length}
                />
            )}

            <ScheduleControls 
                scheduleData={scheduleData}
                onUpdateSchedule={onUpdateSchedule}
                dateOptions={getDateOptions()}
                timeOptions={getTimeOptions()}
            />
          </div>
        </div>

        {/* Right Side - Preview */}
        {showPreview && (
          <PreviewCard 
            platform={selectedPlatform}
            profile={selectedProfile[0]}
            templateHtml={previewContent}
            postData={{
                title: postTitle,
                content: postContent,
                url: window.location.origin, // Assuming internal logic, derived earlier
                bannerImage: bannerImage
            }}
            info={info}
          />
        )}
      </div>

      {/* Footer */}
      <div className="wpsp-modal--footer">
        <div className="wpsp-custom-social-footer-wrapper">
          {saveError && (
            <div className="wpsp-custom-social-footer-error" role="alert">
              {saveError}
            </div>
          )}
          <div className="wpsp-custom-social-footer-right">
            <button  className="btn primary-btn" onClick={handleGlobalSave} disabled={isSaving || !hasAnyChanges()}>
              {saveText}
            </button>
          </div>
        </div>
      </div>

      {/* AI Caption Drawer */}
      <AICaptionDrawer
        isOpen={isAICaptionOpen}
        onClose={() => setIsAICaptionOpen(false)}
        platforms={platforms}
        social_media_enabled={social_media_enabled}
        selectedPlatform={selectedPlatform}
        onGenerate={handleGenerateCaption}
        onInsertCaptions={handleInsertCaptions}
      />
    </div>
  );
};

export default WPSPCustomTemplateModal;
