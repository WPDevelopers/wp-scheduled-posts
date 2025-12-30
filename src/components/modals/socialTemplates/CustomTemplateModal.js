import React, { useContext, useState, useEffect, useCallback } from 'react';
import { Button } from '@wordpress/components';
const { useSelect } = wp.data;
import apiFetch from '@wordpress/api-fetch';
const { __ } = wp.i18n;
import { AppContext } from '../../../context/AppContext';
import Header from './Header';
import { facebook, twitter_x, linkedin, pinterest, instagram, medium, threads, google_business } from '../../../../assets/gutenberg/utils/helpers/icons';

// Sub-components
import PlatformNavigation from './PlatformNavigation';
import ProfileSelector from './ProfileSelector';
import TemplateEditor from './TemplateEditor';
import ScheduleControls from './ScheduleControls';
import PreviewCard from './PreviewCard';

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

const WPSPCustomTemplateModal = ({
  WPSchedulePostsFree = { adminURL: '#', assetsURI: '' },
  info = 'Info message here',
  // Profile data props
  facebookProfileData,
  twitterProfileData,
  linkedinProfileData,
  pinterestProfileData,
  instagramProfileData,
  mediumProfileData,
  threadsProfileData,
  googleBusinessProfileData,
  // Post data props
  postId: propPostId,
  postStatus: propPostStatus,
  postTitleProp,
  postContentProp,
  postUrlProp,
  uploadSocialShareBanner,
}) => {
  const { state, dispatch } = useContext(AppContext);
  
  // Get post data from WP data if not provided via props
  const { postId, postStatus, postTitle, postContent, postUrl, featuredImageUrl } = useSelect((select) => {
    const editor = select('core/editor');
    const core = select('core');
    const id = propPostId || editor?.getCurrentPostId();
    const featuredMediaId = editor?.getEditedPostAttribute('featured_media');
    const media = featuredMediaId ? core?.getMedia(featuredMediaId) : null;

    return {
      postId: id,
      postStatus: propPostStatus || editor?.getEditedPostAttribute('status'),
      postTitle: postTitleProp || editor?.getEditedPostAttribute('title'),
      postContent: postContentProp || editor?.getEditedPostAttribute('content'),
      postUrl: postUrlProp || editor?.getPermalink(),
      featuredImageUrl: media?.source_url || null,
    };
  }, [propPostId, propPostStatus, postTitleProp, postContentProp, postUrlProp]);

  // Derived state
  const bannerImage = uploadSocialShareBanner || featuredImageUrl;
  const social_media_enabled = window.WPSchedulePostsFree?.social_media_enabled || {};
  
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
  const [isSaving, setIsSaving] = useState(false);
  const [isUpdatingContent, setIsUpdatingContent] = useState(false);
  const [allPlatformData, setAllPlatformData] = useState({});
  const [apiTemplateData, setApiTemplateData] = useState({});
  
  const [scheduleData, setScheduleData] = useState({
    dateOption: postStatus === 'publish' ? 'today' : 'same_day',
    customDays: '',
    customDate: '',
    timeOption: postStatus === 'publish' ? 'now' : 'same_time',
    customHours: '',
    customTime: '',
    schedulingType: postStatus === 'publish' ? 'absolute' : 'relative',
  });


  // API functions for data management
  const fetchTemplateData = useCallback(async () => {
    if (!postId) return {};

    try {
      const response = await apiFetch({
        path: `/wp-scheduled-posts/v1/custom-templates/${postId}`,
        method: 'GET',
      });

      if (response && (response.success === undefined || response.success)) {
        // Handle both standard WP response and direct data return
        const data = response.data || response;
        setApiTemplateData(data || {});
        return data || {};
      } else {
        console.error('Failed to fetch template data:', response.message);
        return {};
      }
    } catch (error) {
      console.error('Error fetching template data:', error);
      return {};
    }
  }, [postId]);

  const fetchSchedulingData = async () => {
    if (!postId) return {};
    return {}; // We handle scheduling data via state and meta sync on save
  };

  // Global template management
  const getIsGlobalForPlatform = useCallback((platform) => {
    const platformData = apiTemplateData[platform];
    return platformData?.is_global === 1 || platformData?.is_global === '1' || platformData?.is_global === true;
  }, [apiTemplateData]);

  const setUseGlobalTemplatePlatform = useCallback((platform, checked) => {
    const updateData = (prev) => ({
      ...prev,
      [platform]: {
        ...(prev[platform] || {}),
        template: customTemplates[platform] || '',
        profiles: selectedProfile.map(profile => profile.id),
        is_global: checked ? 1 : '',
      }
    });

    setApiTemplateData(updateData);
    setAllPlatformData(updateData);
  }, [customTemplates, selectedProfile]);

  // Get available profiles for selected platform
  const getAvailableProfiles = useCallback(() => {
    switch (selectedPlatform) {
      case 'facebook': return facebookProfileData || [];
      case 'twitter': return twitterProfileData || [];
      case 'linkedin': return linkedinProfileData || [];
      case 'pinterest': return pinterestProfileData || [];
      case 'instagram': return instagramProfileData || [];
      case 'medium': return mediumProfileData || [];
      case 'threads': return threadsProfileData || [];
      case 'google_business': return googleBusinessProfileData || [];
      default: return [];
    }
  }, [selectedPlatform, facebookProfileData, twitterProfileData, linkedinProfileData, pinterestProfileData, instagramProfileData, mediumProfileData, threadsProfileData, googleBusinessProfileData]);

  // Get date options based on post status
  const getDateOptions = () => {
    const isPublished = postStatus === 'publish';
    if (isPublished) {
      return [
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
      setSaveText(isUpdatingContent ? __('Updating...', 'wp-scheduled-posts') : __('Saving...', 'wp-scheduled-posts'));
      
      const platformsToSave = [];
      
      // Get current platform data
      const currentPlatformData = {
        template: customTemplates[selectedPlatform] || '',
        profiles: selectedProfile.map(profile => profile.id),
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
      const response = await apiFetch({
        path: `/wp-scheduled-posts/v1/custom-templates/${postId}`,
        method: 'POST',
        data: {
          platforms: platformsToSave,
          scheduling: scheduleData,
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
    const currentProfiles = selectedProfile.map(profile => profile.id);

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
  }, [customTemplates, selectedPlatform, selectedProfile, allPlatformData, apiTemplateData]);

  // Helper to check if platform has data
  const platformHasData = useCallback((platform) => {
    if (platform === selectedPlatform) {
      const currentTemplate = customTemplates[selectedPlatform] || '';
      const currentProfiles = selectedProfile.map(profile => profile.id);
      return currentTemplate.trim() !== '' || currentProfiles.length > 0;
    }

    const tempData = allPlatformData[platform];
    if (tempData && (tempData.template.trim() !== '' || tempData.profiles.length > 0)) return true;

    const savedData = apiTemplateData[platform];
    return savedData && (
      (savedData.template && savedData.template.trim() !== '') ||
      (savedData.profiles && savedData.profiles.length > 0)
    );
  }, [allPlatformData, apiTemplateData, selectedPlatform, customTemplates, selectedProfile]);

  // Handle platform switching
  const handlePlatformSwitch = useCallback((newPlatform) => {
    if (selectedPlatform) {
      const currentTemplate = customTemplates[selectedPlatform] || '';
      const currentProfiles = selectedProfile.map(profile => profile.id);

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
  }, [selectedPlatform, customTemplates, selectedProfile, getIsGlobalForPlatform]);

  const handleClose = () => {
    setAllPlatformData({});
    dispatch({ type: 'SET_CUSTOM_SOCIAL_MESSAGE_MODAL', payload: false });
  };

  // Effects
  useEffect(() => {
    const isPublished = postStatus === 'publish';
    setScheduleData(prev => ({
      ...prev,
      dateOption: isPublished ? 'today' : 'same_day',
      timeOption: isPublished ? 'now' : 'same_time',
      schedulingType: isPublished ? 'absolute' : 'relative'
    }));
  }, [postStatus]);

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
        setSaveText('Update');
      }

      const available = getAvailableProfiles();
      
      if (dataToLoad) {
        setCustomTemplates(prev => ({ ...prev, [selectedPlatform]: dataToLoad.template || '' }));
        const profilesToSet = (dataToLoad.profiles || []).map(profileId =>
          available.find(profile => profile.id === profileId)
        ).filter(Boolean);
        setSelectedProfile(profilesToSet);
        setIsUpdatingContent(true);
      } else {
        setCustomTemplates(prev => ({ ...prev, [selectedPlatform]: '{title} {content} {url} {tags}' }));
        setSelectedProfile([]);
      }
    }
  }, [selectedPlatform, allPlatformData, apiTemplateData, getAvailableProfiles]);

  useEffect(() => {
      // Clear any temporary data when opening modal
      setAllPlatformData({});
      fetchTemplateData();
  }, [fetchTemplateData]); // On mount

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

  return (
    <div className={`wpsp-modal-content ${availableProfiles.length === 0 ? 'no-profile-found' : ''}`}>
      <Header/>
      <div className="wpsp-modal-layout">
        {/* Left Side */}
        <div className="wpsp-modal-left">
          <PlatformNavigation 
              platforms={filteredPlatforms}
              selectedPlatform={selectedPlatform}
              onSelectPlatform={handlePlatformSwitch}
              platformHasData={platformHasData}
          />

          <div className="wpsp-custom-template-content-wrapper">
            <ProfileSelector 
                availableProfiles={availableProfiles}
                selectedProfile={selectedProfile}
                onSelectProfile={onSelectProfile}
                WPSchedulePostsFree={WPSchedulePostsFree}
            />

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
      <div className="wpsp-modal-footer">
        <div className="wpsp-custom-social-footer-wrapper">
          <div className="wpsp-custom-social-footer-right">
            <button isSecondary onClick={handleClose}>
              {__('Cancel', 'wp-scheduled-posts')}
            </button>
            <button isPrimary onClick={handleGlobalSave} disabled={isSaving || isOverLimit || !hasAnyChanges()}>
              {saveText}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default WPSPCustomTemplateModal;
