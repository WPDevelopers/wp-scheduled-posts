import React, { useContext, useState, useEffect } from 'react';
import { Button } from '@wordpress/components';
const { useSelect } = wp.data;
import apiFetch from '@wordpress/api-fetch';
const { __ } = wp.i18n;
import { AppContext } from '../../../context/AppContext';
import Header from './Header';
import { authorIcon, tikIcon, eyeIcon, eyeCloseIcon, facebook, twitter_x, linkedin, pinterest, instagram, medium, threads, google_business } from '../../../../assets/gutenberg/utils/helpers/icons';

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
  const [activeDropdown, setActiveDropdown] = useState(false);
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
  const fetchTemplateData = async () => {
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
  };

  const fetchSchedulingData = async () => {
    if (!postId) return {};
    return {}; // We handle scheduling data via state and meta sync on save
  };

  // Global template management
  const getIsGlobalForPlatform = (platform) => {
    const platformData = apiTemplateData[platform];
    return platformData?.is_global === 1 || platformData?.is_global === '1' || platformData?.is_global === true;
  };

  const setUseGlobalTemplatePlatform = (platform, checked) => {
    setApiTemplateData(prev => ({
      ...prev,
      [platform]: {
        ...(prev[platform] || {}),
        template: customTemplates[platform] || '',
        profiles: selectedProfile.map(profile => profile.id),
        is_global: checked ? 1 : '',
      }
    }));
    setAllPlatformData(prev => ({
      ...prev,
      [platform]: {
        ...(prev[platform] || {}),
        template: customTemplates[platform] || '',
        profiles: selectedProfile.map(profile => profile.id),
        is_global: checked ? 1 : '',
      }
    }));
  };

  // Get available profiles for selected platform
  const getAvailableProfiles = () => {
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
  };

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
  const generatePreview = (template) => {
    if (!template) return '';
    let preview = template;
    preview = preview.replace(/{title}/g, postTitle || 'Sample Post Title');
    preview = preview.replace(/{content}/g, postContent || 'This is sample post content...');
    preview = preview.replace(/{url}/g, postUrl || 'https://example.com/post');
    preview = preview.replace(/{tags}/g, '#wordpress #blog');
    return preview;
  };

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
  const hasAnyChanges = () => {
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
  };

  // Helper to check if platform has data
  const platformHasData = (platform) => {
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
  };

  // Handle platform switching
  const handlePlatformSwitch = (newPlatform) => {
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
  };

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
  }, [customTemplates, selectedPlatform, postTitle, postContent, postUrl]);

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

      const availableProfiles = getAvailableProfiles();
      
      if (dataToLoad) {
        setCustomTemplates(prev => ({ ...prev, [selectedPlatform]: dataToLoad.template || '' }));
        const profilesToSet = (dataToLoad.profiles || []).map(profileId =>
          availableProfiles.find(profile => profile.id === profileId)
        ).filter(Boolean);
        setSelectedProfile(profilesToSet);
        setIsUpdatingContent(true);
      } else {
        setCustomTemplates(prev => ({ ...prev, [selectedPlatform]: '{title} {content} {url} {tags}' }));
        setSelectedProfile([]);
      }
    }
  }, [selectedPlatform, allPlatformData, apiTemplateData, facebookProfileData, twitterProfileData, linkedinProfileData, pinterestProfileData, instagramProfileData, mediumProfileData, threadsProfileData, googleBusinessProfileData]);

  useEffect(() => {
      // Clear any temporary data when opening modal
      setAllPlatformData({});
      
      const loadData = async () => {
        const templateData = await fetchTemplateData();
      };
      loadData();
  }, []); // On mount

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

  const previewProfileName = selectedProfile[0]?.name || 'Preview Name';
  const previewThumbnailUrl = selectedProfile[0]?.thumbnail_url || '';
  const previewContent = generatePreview(customTemplates[selectedPlatform] || '');
  return (
    <div className={`wpsp-modal-content ${availableProfiles.length === 0 ? 'no-profile-found' : ''}`}>
      <Header/>
      <div className="wpsp-modal-layout">
        {/* Left Side */}
        <div className="wpsp-modal-left">

          {/* Platform Icons */}
          <div className="wpsp-platform-icons">
            {filteredPlatforms.map(({ platform, icon, bgColor }) => (
              <button
                key={platform}
                className={`wpsp-platform-icon ${selectedPlatform} ${selectedPlatform === platform ? 'active' : ''} ${platformHasData(platform) ? 'has-data' : ''}`}
                onClick={() => handlePlatformSwitch(platform)}
                style={{
                  backgroundColor: selectedPlatform === platform ? bgColor : '#f0f0f0',
                  color: selectedPlatform === platform ? '#fff' : '#666',
                  fontWeight: selectedPlatform === platform ? 'bold' : 'normal',
                  position: 'relative',
                }}
                title={platform}
              >
                {icon}
              </button>
            ))}
          </div>

          {/* Profile Selection & Template Editor */}
          <div className="wpsp-custom-template-content-wrapper">
            {availableProfiles.length === 0 && (
              <h5
                dangerouslySetInnerHTML={{
                  __html: __(
                    `*You may forget to add or enable profile/page from <a target="_blank" href='${WPSchedulePostsFree.adminURL}admin.php?page=schedulepress&tab=social-profile'>SchedulePress settings</a>.`,
                    'wp-scheduled-posts'
                  ),
                }}
              ></h5>
            )}

            <div className={`wpsp-profile-selection-area-wrapper ${availableProfiles.length === 0 ? 'no-profile-found' : ''}`}>
              <div className="selected-profile-area">
                <ul>
                  {availableProfiles.slice(0, 5).map((profile) => {
                    const isSelected = selectedProfile.some((p) => p.id === profile.id);
                    return (
                      <li
                        key={profile.id}
                        className="selected-profile"
                        title={profile.name}
                        onClick={() => {
                          if (isSelected) {
                            setSelectedProfile(selectedProfile.filter((p) => p.id !== profile.id));
                          } else {
                            setSelectedProfile([...selectedProfile, profile]);
                          }
                        }}
                      >
                        {profile.thumbnail_url ? (
                          <img
                            src={profile.thumbnail_url}
                            alt={profile.name}
                            className="wpsp-profile-image"
                            onError={(e) => {
                              e.target.onerror = null;
                              e.target.src = `data:image/svg+xml;utf8,${encodeURIComponent(authorIcon)}`;
                            }}
                          />
                        ) : (
                          <div className="wpsp-profile-placeholder">{profile.name?.charAt(0).toUpperCase() || '?'}</div>
                        )}

                        {isSelected && (
                          <div className="wpsp-selected-profile-action">
                            <span
                              className="wpsp-remove-profile-btn"
                              onClick={(e) => {
                                e.stopPropagation();
                                setSelectedProfile(selectedProfile.filter((p) => p.id !== profile.id));
                              }}
                            >
                              &times;
                            </span>
                            <span className="wpsp-selected-profile-btn">{tikIcon}</span>
                          </div>
                        )}
                      </li>
                    );
                  })}

                  {availableProfiles.length > 5 && (
                    <li className="selected-profile wpsp-more-profiles">
                      <div className="wpsp-profile-placeholder">+{availableProfiles.length - 5}</div>
                    </li>
                  )}
                </ul>

                <span className="select-profile-icon" onClick={() => setActiveDropdown(!activeDropdown)}>
                  <img src={WPSchedulePostsFree.assetsURI + '/images/chevron-down.svg'} alt="" />
                </span>
              </div>

              {activeDropdown && (
                <div className="wpsp-profile-selection-dropdown">
                  <div className="wpsp-profile-selection-dropdown-item">
                    {availableProfiles.map((profile) => (
                      <div
                        key={profile.id}
                        className={`wpsp-profile-card ${selectedProfile.some((p) => p.id === profile.id) ? 'selected' : ''}`}
                        onClick={() => {
                          if (selectedProfile.some((p) => p.id === profile.id)) {
                            setSelectedProfile(selectedProfile.filter((p) => p.id !== profile.id));
                          } else {
                            setSelectedProfile([...selectedProfile, profile]);
                          }
                        }}
                      >
                        <div className="wpsp-profile-avatar">
                          {profile.thumbnail_url ? (
                            <img
                              src={profile.thumbnail_url}
                              alt={profile.name}
                              className="wpsp-profile-image"
                              onError={(e) => {
                                e.target.onerror = null;
                                e.target.src = `data:image/svg+xml;utf8,${encodeURIComponent(authorIcon)}`;
                              }}
                            />
                          ) : (
                            <div className="wpsp-profile-placeholder">{profile.name?.charAt(0).toUpperCase() || '?'}</div>
                          )}
                        </div>
                        <div className="wpsp-profile-info">
                          <div className="wpsp-profile-name">{profile.name}</div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Template Editor */}
              {selectedPlatform && (
                <div className="wpsp-template-textarea">
                   <div className='wpsp-textarea-wrapper'>
                    <textarea
                      value={customTemplates[selectedPlatform] || ''}
                      onChange={(e) =>
                        setCustomTemplates((prev) => ({ ...prev, [selectedPlatform]: e.target.value }))
                      }
                      placeholder={__('Enter your custom template here...', 'wp-scheduled-posts')}
                      className="wpsp-template-input"
                      rows={4}
                      disabled={globalProfile != null && globalProfile !== selectedPlatform}
                    />
                  </div>
                  <div className="wpsp-template-meta">
                    <span className="wpsp-placeholders">
                      {__('Available:', 'wp-scheduled-posts')} {'{title}'} {'{content}'} {'{url}'} {'{tags}'}
                    </span>
                    <div className="wpsp-custom-template-field-info">
                      <span className={`${showPreview ? 'active' : 'inactive'}`} onClick={() => setShowPreview(!showPreview)}>
                        {showPreview ? eyeCloseIcon : eyeIcon}
                      </span>
                      <span className={`wpsp-char-count ${isOverLimit ? 'over-limit' : ''}`}>
                         {characterCount}/{currentLimit}
                      </span>
                    </div>
                  </div>

                  <div className={`wpsp-global-template ${!showPreview ? 'hide-preview' : ''}`}>
                    <span className={availableProfiles?.length === 0 ? 'wpsp-use-global-template-text disabled' : ''} style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                      {__('Use global template', 'wp-scheduled-posts')}
                      <span className="wpsp-tooltip-wrapper">
                         {info}
                         <span className="wpsp-tooltip-text">
                           {__('If enabled, this template will be applied across all the selected social platforms.', 'wp-scheduled-posts')}
                         </span>
                      </span>
                    </span>
                    {showGlobalTemplateWarning && (
                      <div className='use-global-template-warning'>
                        <span>{__(`${globalProfile?.charAt(0).toUpperCase() + globalProfile.slice(1)} is enabled as global template`, 'wp-scheduled-posts')}</span>
                      </div>
                    )}
                    <div className={`wpsp-use-global-template-checkbox-wrapper ${(availableProfiles.length === 0 || (globalProfile != null && globalProfile !== selectedPlatform)) ? 'disabled' : ''}`}>
                      <input
                        type="checkbox"
                        id={`useGlobalTemplate_${selectedPlatform}`}
                        checked={getIsGlobalForPlatform(selectedPlatform)}
                        disabled={availableProfiles?.length === 0}
                        onChange={e => {
                          if (globalProfile != null && globalProfile !== selectedPlatform) {
                            setShowGlobalTemplateWarning(true);
                            setTimeout(() => {
                              setShowGlobalTemplateWarning(false);
                            }, 3000);
                          } else {
                            setUseGlobalTemplatePlatform(selectedPlatform, e.target.checked);
                          }
                        }}
                      />
                      <label htmlFor={`useGlobalTemplate_${selectedPlatform}`}></label>
                    </div>
                  </div>
                </div>
              )}

              {/* Date & Time Scheduling Fields */}
              <div className="wpsp-date-time-section" style={{ marginBottom: '1.5em', marginTop: '1.5em' }}>
                <div style={{ display: 'flex', gap: '1.5em', alignItems: 'flex-end', flexWrap: 'wrap' }}>
                  {/* Date Field */}
                  <div>
                    <label style={{ fontWeight: 600, display: 'block', marginBottom: 4 }}>{__('Date', 'wp-scheduled-posts')}</label>
                    <select
                      value={scheduleData.dateOption}
                      onChange={e => setScheduleData(prev => ({ ...prev, dateOption: e.target.value }))}
                      style={{ maxWidth: '200px' }}
                    >
                      {getDateOptions().map(opt => (
                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                      ))}
                    </select>
                  </div>

                   {/* Custom Date Input */}
                   {scheduleData.dateOption === 'custom_date' && (
                    <div>
                      <label style={{ fontWeight: 600, display: 'block', marginBottom: 4 }}>{__('Custom Date', 'wp-scheduled-posts')}</label>
                      <input
                        type="date"
                        value={scheduleData.customDate}
                        onChange={e => setScheduleData(prev => ({ ...prev, customDate: e.target.value }))}
                      />
                    </div>
                  )}

                  {/* Custom Days Input */}
                  {(scheduleData.dateOption === 'in_days' || scheduleData.dateOption === 'days_after') && (
                    <div>
                      <label style={{ fontWeight: 600, display: 'block', marginBottom: 4 }}>{__('Days', 'wp-scheduled-posts')}</label>
                      <input
                        type="number"
                        min="1"
                        value={scheduleData.customDays}
                        onChange={e => setScheduleData(prev => ({ ...prev, customDays: e.target.value }))}
                        style={{ maxWidth: '80px' }}
                      />
                    </div>
                  )}

                   {/* Time Field */}
                   <div>
                    <label style={{ fontWeight: 600, display: 'block', marginBottom: 4 }}>{__('Time', 'wp-scheduled-posts')}</label>
                    <select
                      value={scheduleData.timeOption}
                      onChange={e => setScheduleData(prev => ({ ...prev, timeOption: e.target.value }))}
                      style={{ maxWidth: '200px' }}
                    >
                      {getTimeOptions().map(opt => (
                         <option key={opt.value} value={opt.value}>{opt.label}</option>
                      ))}
                    </select>
                  </div>

                   {/* Custom Time Input */}
                   {scheduleData.timeOption === 'custom_time' && (
                    <div>
                      <label style={{ fontWeight: 600, display: 'block', marginBottom: 4 }}>{__('Custom Time', 'wp-scheduled-posts')}</label>
                      <input
                        type="time"
                        value={scheduleData.customTime}
                        onChange={e => setScheduleData(prev => ({ ...prev, customTime: e.target.value }))}
                      />
                    </div>
                  )}

                   {/* Custom Hours Input */}
                   {(scheduleData.timeOption === 'in_hours' || scheduleData.timeOption === 'hours_after') && (
                    <div>
                      <label style={{ fontWeight: 600, display: 'block', marginBottom: 4 }}>{__('Hours', 'wp-scheduled-posts')}</label>
                      <input
                        type="number"
                        min="1"
                        value={scheduleData.customHours}
                        onChange={e => setScheduleData(prev => ({ ...prev, customHours: e.target.value }))}
                        style={{ maxWidth: '80px' }}
                      />
                    </div>
                  )}
                </div>
              </div>

            </div>
          </div>
        </div>

        {/* Right Side - Preview */}
        {showPreview && (
          <div className={`wpsp-modal-right ${selectedPlatform}`}>
            <div className="wpsp-preview-card">
              {availableProfiles.length > 0 ? (
                <>
                  <div className="wpsp-preview-header">
                    <div className="wpsp-preview-avatar">
                      <div className="wpsp-avatar-circle">
                        <img
                          src={previewThumbnailUrl}
                          alt={previewProfileName}
                          className="wpsp-profile-image"
                          onError={(e) => {
                            e.target.onerror = null;
                            e.target.src = `data:image/svg+xml;utf8,${encodeURIComponent(authorIcon)}`;
                          }}
                        />
                      </div>
                      <div className="wpsp-preview-info">
                        {selectedProfile.length > 0 && <div className="wpsp-preview-name">{previewProfileName}</div>}
                        <div className="wpsp-preview-date">
                          {new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })}
                        </div>
                      </div>
                    </div>
                  </div>

                  <div className="wpsp-preview-content-area">
                    <div
                      className="wpsp-preview-text"
                      dangerouslySetInnerHTML={{ __html: previewContent }}
                    ></div>

                    <div className="wpsp-preview-post">
                      <div className="wpsp-preview-image">
                        {bannerImage ? (
                          <img
                            src={bannerImage}
                            alt="Preview"
                            style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                          />
                        ) : (
                          <div
                            style={{
                              width: '100%',
                              height: '100%',
                              background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              color: 'white',
                              fontSize: '14px',
                            }}
                          >
                            {__('No image selected', 'wp-scheduled-posts')}
                          </div>
                        )}
                      </div>

                      <div className="wpsp-preview-post-content">
                        <div className="wpsp-preview-url">{window.location.origin}</div>
                        <div className="wpsp-preview-title">{postTitle}</div>
                        <div
                          className="wpsp-preview-excerpt"
                          dangerouslySetInnerHTML={{ __html: postContent }}
                        ></div>
                      </div>
                    </div>
                  </div>
                </>
              ) : (
                <div className="wpsp-preview-not-available">
                  {info}
                  <h3>{__('Preview not available', 'wp-scheduled-posts')}</h3>
                  <p>{__('Please make sure you select a social profile first.', 'wp-scheduled-posts')}</p>
                </div>
              )}
            </div>
          </div>
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
