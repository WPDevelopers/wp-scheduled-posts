import React, { useState, useEffect } from 'react';
import { authorIcon, docIcon, eyeCloseIcon, eyeIcon, facebook, google_business, info, instagram, linkedin, medium, pinterest, threads, tikIcon, twitter_x } from './helpers/icons';

const {
  components: { Modal, Button },
  data: { useSelect },
} = wp;
const { __ } = wp.i18n;

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

// Platform character limits
const platformLimits = {
  facebook : 63206,
  twitter  : 280,
  linkedin : 1300,
  pinterest: 500,
  instagram: 2100,
  medium   : 45000,
  threads  : 480,
  google_business  : 1500,
};

const CustomSocialTemplateModal = ({
  isOpen,
  onClose,
  facebookProfileData,
  twitterProfileData,
  linkedinProfileData,
  pinterestProfileData,
  instagramProfileData,
  mediumProfileData,
  threadsProfileData,
  googleBusinessProfileData,
  postTitle,
  postContent,
  postUrl,
  uploadSocialShareBanner
}) => {
  // Get post ID and post status (no meta dependency)
  const { postId, postStatus } = useSelect((select) => ({
    postId: select('core/editor').getCurrentPostId(),
    postStatus: select('core/editor').getEditedPostAttribute('status'),
  }));

  const featuredImageUrl = useSelect((select) => {
    const featuredMediaId = select('core/editor').getEditedPostAttribute('featured_media');
  
    if (!featuredMediaId) return null;
  
    const media = select('core').getMedia(featuredMediaId);
    return media?.source_url || null;
  }, []);

  const social_media_enabled = WPSchedulePostsFree?.social_media_enabled || {}; // Adjust this based on your actual data object
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

  const firstSelectedProfile = Object.entries(social_media_enabled).find(([key, value]) => value === true)?.[0];

  // final try to get image from featured image.
  if( !uploadSocialShareBanner ) {
    uploadSocialShareBanner = featuredImageUrl;
  }

  // State for API-loaded template data
  const [apiTemplateData, setApiTemplateData] = useState({});
  const [activeDropdown, setActiveDropdown] = useState(false);
  const [showPreview, setShowPreview] = useState(true);
  const [showGlobalTemplateWarning, setShowGlobalTemplateWarning] = useState(false);
  const [selectedPlatform, setSelectedPlatform] = useState( firstSelectedProfile || 'facebook' );
  const [selectedProfile, setSelectedProfile] = useState([]);
  // let's set default custom template with all dynamic variable.
  const [customTemplates, setCustomTemplates] = useState({});
  const [characterCount, setCharacterCount] = useState(0);
  const [previewContent, setPreviewContent] = useState('');
  const [saveText, setSaveText] = useState(__('Save', 'wp-scheduled-posts'));
  const [isSaving, setIsSaving] = useState(false);
  const [isUpdatingContent, setIsUpdatingContent] = useState(false);
  // Store all platform data including profiles and templates
  const [allPlatformData, setAllPlatformData] = useState({});
  // Date & Time scheduling state
  const [scheduleData, setScheduleData] = useState({
    dateOption: postStatus === 'publish' ? 'today' : 'same_day',
    customDays: '',
    customDate: '',
    timeOption: postStatus === 'publish' ? 'now' : 'same_time',
    customHours: '',
    customTime: '',
    schedulingType: postStatus === 'publish' ? 'absolute' : 'relative', // absolute for published, relative for others
  });
  // API functions for data management
  const fetchTemplateData = async () => {
    if (!postId) return {};

    try {
      const response = await wp.apiFetch({
        path: `/wp-scheduled-posts/v1/custom-templates/${postId}`,
        method: 'GET',
      });

      if (response.success) {
        setApiTemplateData(response.data || {});
        return response.data || {};
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

    try {
      // For now, we'll use the meta system for scheduling data since it's working
      // This can be moved to API later if needed
      const meta = wp.data.select('core/editor').getEditedPostAttribute('meta') || {};
      const schedulingData = meta._wpsp_social_scheduling || {};      
      // If activeDefaultTemplate exists, set it in scheduleData state
      if (schedulingData.activeDefaultTemplate) {
        setScheduleData(prev => ({ ...prev, activeDefaultTemplate: schedulingData.activeDefaultTemplate }));
      }
      return schedulingData;
    } catch (error) {
      console.error('Error fetching scheduling data:', error);
      return {};
    }
  };

  // Global template management - using is_global from API data
  const getIsGlobalForPlatform = (platform) => {
    const platformData = apiTemplateData[platform];
    return platformData?.is_global === 1 || platformData?.is_global === '1' || platformData?.is_global === true;
  };

  const setUseGlobalTemplatePlatform = (platform, checked) => {
    // Update API template data state
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
      case 'facebook':
        return facebookProfileData || [];
      case 'twitter':
        return twitterProfileData || [];
      case 'linkedin':
        return linkedinProfileData || [];
      case 'pinterest':
        return pinterestProfileData || [];
      case 'instagram':
        return instagramProfileData || [];
      case 'medium':
        return mediumProfileData || [];
      case 'threads':
        return threadsProfileData || [];
      case 'google_business':
        return googleBusinessProfileData || [];
      default:
        return [];
    }
  };

  // Get date options based on post status
  const getDateOptions = () => {
    const isPublished = postStatus === 'publish';

    if (isPublished) {
      // Absolute scheduling for published posts
      return [
        { value: 'tomorrow', label: __('Tomorrow', 'wp-scheduled-posts') },
        { value: 'next_week', label: __('Next week', 'wp-scheduled-posts') },
        { value: 'next_month', label: __('Next month', 'wp-scheduled-posts') },
        { value: 'in_days', label: __('In __ days', 'wp-scheduled-posts') },
        { value: 'custom_date', label: __('Choose a custom date...', 'wp-scheduled-posts') }
      ];
    } else {
      // Relative scheduling for draft/scheduled posts
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
      // Absolute scheduling for published posts
      return [
        { value: 'in_1h', label: __('In one hour', 'wp-scheduled-posts') },
        { value: 'in_3h', label: __('In three hours', 'wp-scheduled-posts') },
        { value: 'in_5h', label: __('In five hours', 'wp-scheduled-posts') },
        { value: 'in_hours', label: __('In __ hours', 'wp-scheduled-posts') },
        { value: 'custom_time', label: __('Choose a custom time...', 'wp-scheduled-posts') }
      ];
    } else {
      // Relative scheduling for draft/scheduled posts
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

  // Global save function - saves all platforms and scheduling data in a single batch request
  const handleGlobalSave = async () => {
    try {
      setIsSaving(true);
      isUpdatingContent ?  setSaveText(__('Updating...', 'wp-scheduled-posts')) : setSaveText(__('Saving...', 'wp-scheduled-posts') );
      // Collect all platform data that has content or selected profiles
      const platformsToSave = [];

      // Get current platform data first
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

      // Process all platforms that have data
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

      // Find the active default template (global) platform
      let activeDefaultTemplate = null;
      for (const platform of SOCIAL_PLATFORMS) {
        const platformData = allData[platform];
        if (platformData && (platformData.is_global === 1 || platformData.is_global === true)) {
          activeDefaultTemplate = platform;
          break;
        }
      }

      // Send single batch request to save all platforms
      const response = await wp.apiFetch({
        path: `/wp-scheduled-posts/v1/custom-templates/${postId}`,
        method: 'POST',
        data: {
          platforms: platformsToSave, // Batch mode
          scheduling: scheduleData,
        },
      });

      if (response.success) {
        await fetchTemplateData();
        setAllPlatformData({});
        const successMessage = isUpdatingContent ?  __('Updated Successfully', 'wp-scheduled-posts') : __('Saved Successfully','wp-scheduled-posts');
        setSaveText(successMessage);
        onClose();
        setTimeout(() => setSaveText(__('Update', 'wp-scheduled-posts')), 2000);

        // Update local editor state
        wp.data.dispatch('core/editor').editPost({
          meta: {
            ...wp.data.select('core/editor').getEditedPostAttribute('meta'),
            _wpsp_social_scheduling: scheduleData, // the new data you just saved
          }
        });
      } else {
        throw new Error(response.message || 'Failed to save templates');
      }
    } catch (error) {
      setSaveText(__('Save Failed', 'wp-scheduled-posts'));
      setTimeout(() => setSaveText(__('Update', 'wp-scheduled-posts')), 2000);
      console.error('Error saving templates:', error);
      // Show detailed error if available
      if (error.response && error.response.errors) {
        console.error('Validation errors:', error.response.errors);
      }
    } finally {
      setIsSaving(false);
    }
  };

  // Helper to check if there are any changes to save across all platforms
  const hasAnyChanges = () => {
    // Check current platform
    const currentTemplate = customTemplates[selectedPlatform] || '';
    const currentProfiles = selectedProfile.map(profile => profile.id);

    if (currentTemplate.trim() !== '' || currentProfiles.length > 0) {
      return true;
    }

    // Check all stored platform data (temporary changes)
    for (const platform of SOCIAL_PLATFORMS) {
      const platformData = allPlatformData[platform];
      if (platformData && (platformData.template.trim() !== '' || platformData.profiles.length > 0)) {
        return true;
      }
    }

    // Check if there's any saved data from API
    if (apiTemplateData) {
      for (const platform of SOCIAL_PLATFORMS) {
        const savedData = apiTemplateData[platform];
        if (savedData && (
          (savedData.template && savedData.template.trim() !== '') ||
          (savedData.profiles && savedData.profiles.length > 0)
        )) {
          return true;
        }
      }
    }

    return false;
  };

  // Helper to check if a specific platform has data
  const platformHasData = (platform) => {
    if (platform === selectedPlatform) {
      const currentTemplate = customTemplates[selectedPlatform] || '';
      const currentProfiles = selectedProfile.map(profile => profile.id);
      return currentTemplate.trim() !== '' || currentProfiles.length > 0;
    }

    // Check temporary data first
    const tempData = allPlatformData[platform];
    if (tempData && (tempData.template.trim() !== '' || tempData.profiles.length > 0)) {
      return true;
    }

    // Check saved API data
    const savedData = apiTemplateData[platform];
    return savedData && (
      (savedData.template && savedData.template.trim() !== '') ||
      (savedData.profiles && savedData.profiles.length > 0)
    );
  };

  // Handle platform switching without auto-save - preserve data across tabs
  const handlePlatformSwitch = (newPlatform) => {
    // Save current platform data before switching (only if there's actual data)
    if (selectedPlatform) {
      const currentTemplate = customTemplates[selectedPlatform] || '';
      const currentProfiles = selectedProfile.map(profile => profile.id);

      // Only save to temporary data if there's actual content or profiles
      if (currentTemplate.trim() !== '' || currentProfiles.length > 0) {
        const currentData = {
          template: currentTemplate,
          profiles: currentProfiles,
          is_global: getIsGlobalForPlatform(selectedPlatform),
        };
        setAllPlatformData(prev => ({
          ...prev,
          [selectedPlatform]: currentData
        }));
      } else {
        // Remove empty temporary data if it exists
        setAllPlatformData(prev => {
          const newData = { ...prev };
          delete newData[selectedPlatform];
          return newData;
        });
      }
    }

    // Switch to new platform
    setSelectedPlatform(newPlatform);
  };

  // Handle modal close with cleanup
  const handleClose = () => {
    // Clear all temporary data when closing modal
    setAllPlatformData({});
    onClose();
  };

  // Update scheduling data when post status changes
  useEffect(() => {
    const isPublished = postStatus === 'publish';
    setScheduleData(prev => ({
      ...prev,
      dateOption: isPublished ? 'today' : 'same_day',
      timeOption: isPublished ? 'now' : 'same_time',
      schedulingType: isPublished ? 'absolute' : 'relative'
    }));
  }, [postStatus]);

  // Update character count and preview when template changes
  useEffect(() => {
    const preview = generatePreview(customTemplates[selectedPlatform]);
    setPreviewContent(preview);
    setCharacterCount(preview.length);
  }, [customTemplates, selectedPlatform, postTitle, postContent, postUrl]);

  // Load existing template and profiles when platform changes
  useEffect(() => {
    if (selectedPlatform) {
      // Check both temporary and saved data
      const tempData = allPlatformData[selectedPlatform];
      const savedData = apiTemplateData[selectedPlatform];

      let dataToLoad = null;
      let dataSource = 'none';

      if (tempData && (tempData.template || tempData.profiles?.length > 0)) {
        dataToLoad = tempData;
        dataSource = 'temporary';
      } else if (savedData && (savedData.template || savedData.profiles?.length > 0)) {
        dataToLoad = savedData;
        setSaveText('Update');
        dataSource = 'saved';
      }
      if (dataToLoad) {
        // Load from data source
        setCustomTemplates(prev => ({ ...prev, [selectedPlatform]: dataToLoad.template || '' }));
        // Map stored profile IDs to full profile objects
        const profilesToSet = (dataToLoad.profiles || []).map(profileId =>
          getAvailableProfiles().find(profile => profile.id === profileId)
        ).filter(Boolean);
        setSelectedProfile(profilesToSet);
        setIsUpdatingContent(true);
      } else {
        // No data found, reset to empty state
        setCustomTemplates(prev => ({ ...prev, [selectedPlatform]: '{title} {content} {url} {tags}' }));
        setSelectedProfile([]);
      }
    }
  }, [selectedPlatform, allPlatformData, apiTemplateData, facebookProfileData, twitterProfileData, linkedinProfileData, pinterestProfileData, instagramProfileData, mediumProfileData, threadsProfileData, googleBusinessProfileData]);

  // Initialize modal with saved data when it opens
  useEffect(() => {
    if (isOpen) {
      // Clear any temporary data when opening modal to ensure fresh start
      setAllPlatformData({});

      // Load data from API
      const loadData = async () => {
        const [templateData, schedulingData] = await Promise.all([
          fetchTemplateData(),
          fetchSchedulingData()
        ]);

        // Load saved scheduling data
        if (schedulingData) {
          setScheduleData(prev => ({
            ...prev,
            ...schedulingData
          }));
        }

        // Find the first platform that has saved data
        if (templateData) {
          for (const platform of SOCIAL_PLATFORMS) {
            const platformData = templateData[platform];
            if (platformData && (
              (platformData.template && platformData.template.trim() !== '') ||
              (platformData.profiles && platformData.profiles.length > 0)
            )) {
              platformToSelect = platform;
              break;
            }
          }
        }
      };

      loadData();
      fetchSchedulingData();
    }
  }, [isOpen]);

  if (!isOpen) return null;

  const availableProfiles = getAvailableProfiles();
  const currentLimit = platformLimits[selectedPlatform] || 1000;
  const isOverLimit = characterCount > currentLimit;
  
  
  // Filter platforms based on what's enabled
  const filteredPlatforms = platforms.filter(({ platform }) => social_media_enabled[platform]);

  const previewThumbnailUrl = selectedProfile.length > 0 ? selectedProfile[selectedProfile.length - 1].thumbnail_url : '';
  const previewProfileName = selectedProfile.length > 0 ? selectedProfile[selectedProfile.length - 1].name : '';

  let globalProfile = null;
  for (const [platform, config] of Object.entries(apiTemplateData)) {
    if (config.is_global === 1 || config.is_global === true) {
      globalProfile = platform;
      break;
    }
  }

  return (
    <Modal
      title={
        <span className='wpsp-add-social-message-text' style={{ display: 'flex', alignItems: 'normal', gap: '8px' }}>
          { __('Add Social Message', 'wp-scheduled-posts') }
          <a className='wpsp-custom-social-doc-link' href="https://wpdeveloper.com/docs/use-custom-social-templates/" target='_blank'>{ docIcon }</a>
          <span className="wpsp-tooltip-wrapper">
              <div className="wpsp-tooltip-text">
                {__('How to Use Custom Social Templates in SchedulePress.', 'wp-scheduled-posts')}
              </div>
          </span>
        </span>
      }
      onRequestClose={handleClose}
      className="wpsp-custom-template-modal"
      style={{ maxWidth: '800px', width: '90vw' }}
    >
      <div className={`wpsp-modal-content ${ availableProfiles?.length == 0 ? 'no-profile-found' : '' }`}>
        <div className="wpsp-modal-layout">
          {/* Left Side - Template Editor */}
          <div className="wpsp-modal-left">
            {/* Platform Selection Icons */}
            <div className={`wpsp-platform-icons`}>
              {filteredPlatforms.map(({ platform, icon, bgColor }) => (
                <button
                  key={platform}
                  className={`wpsp-platform-icon ${selectedPlatform} ${selectedPlatform === platform ? 'active' : ''} ${platformHasData(platform) ? 'has-data' : ''}`}
                  onClick={() => handlePlatformSwitch(platform)}
                  style={{
                    backgroundColor: selectedPlatform === platform ? bgColor : '#f0f0f0',
                    color: selectedPlatform === platform ? '#fff' : '#666',
                    fontWeight: selectedPlatform === platform ? 'bold' : 'normal',
                    position: 'relative'
                  }}
                  title={`${platform?.charAt(0).toUpperCase() + platform.slice(1)}${platformHasData(platform) ? '' : ''}`}
                >
                  {icon}
                </button>
              ))}
            </div>

            <div className="wpsp-custom-template-content-wrapper">
              { availableProfiles.length == 0 && <h5 dangerouslySetInnerHTML={{ __html : __(`*You may forget to add or enable profile/page from <a target="_blank" href='${WPSchedulePostsFree?.adminURL}admin.php?page=schedulepress&tab=social-profile'>SchedulePress settings</a>.`,'wp-scheduled-posts') }}></h5> }
              <div className={`wpsp-profile-selection-area-wrapper ${ availableProfiles.length <= 0 ? 'no-profile-found' : '' }`}>
                <div className="selected-profile-area">
                  <ul>
                  {availableProfiles && availableProfiles.slice(0, 5).map((profile) => {
                    const isSelected = selectedProfile.some(p => p.id === profile.id);
                    return (
                      <li
                        key={profile.id}
                        className="selected-profile"
                        title={profile.name}
                        onClick={() => {
                          if (selectedProfile.some(p => p.id === profile.id)) {
                            setSelectedProfile(selectedProfile.filter(p => p.id !== profile.id)); // Deselect
                          } else {
                            setSelectedProfile([...selectedProfile, profile]); // Select
                          }
                        }}
                      >
                        {profile.thumbnail_url ? (
                          <img
                            src={profile.thumbnail_url}
                            alt={profile.name}
                            className="wpsp-profile-image"
                            onError={(e) => {
                              e.target.onerror = null; // Prevent infinite loop
                              e.target.src = `data:image/svg+xml;utf8,${encodeURIComponent(authorIcon)}`;
                            }}
                          />
                        ) : (
                          <div className="wpsp-profile-placeholder">
                            {profile.name ? profile.name?.charAt(0).toUpperCase() : '?'}
                          </div>
                        )}

                        {isSelected && (
                          <div className='wpsp-selected-profile-action'>
                            <span
                              className="wpsp-remove-profile-btn"
                              onClick={(e) => {
                                e.stopPropagation(); // Prevent card click from re-selecting
                                setSelectedProfile(selectedProfile.filter(p => p.id !== profile.id));
                              }}
                            >
                              &times;
                            </span>
                            <span className="wpsp-selected-profile-btn">
                              { tikIcon }
                            </span>
                          </div>
                        )}
                      </li>
                    );
                  })}

                  {/* If more than 5 profiles, show the "+X" card */}
                  {availableProfiles.length > 5 && (
                    <li className="selected-profile wpsp-more-profiles">
                      <div className="wpsp-profile-placeholder">
                        +{availableProfiles.length - 5}
                      </div>
                    </li>
                  )}
                  </ul>
                  <span className='select-profile-icon'  onClick={() => setActiveDropdown(!activeDropdown)}>
                    <img src={WPSchedulePostsFree.assetsURI + '/images/chevron-down.svg'} alt="" />
                  </span>
                </div>
                {activeDropdown && (
                  <div className="wpsp-profile-selection-dropdown">
                    <div className="wpsp-profile-selection-dropdown-item">
                      {availableProfiles.map(profile => (
                        <div
                          key={profile.id}
                          className={`wpsp-profile-card ${selectedProfile.some(p => p.id === profile.id) ? 'selected' : ''}`}
                          onClick={() => {
                            // Toggle functionality: if already selected, deselect; otherwise select
                            if (selectedProfile.some(p => p.id === profile.id)) {
                              setSelectedProfile(selectedProfile.filter(p => p.id !== profile.id)); // Deselect
                            } else {
                              setSelectedProfile([...selectedProfile, profile]); // Select
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
                                  e.target.onerror = null; // Prevent infinite loop
                                  e.target.src = `data:image/svg+xml;utf8,${encodeURIComponent(authorIcon)}`;
                                }}
                              />
                            ) : (
                              <div className="wpsp-profile-placeholder">
                                {profile.name ? profile.name?.charAt(0).toUpperCase() : '?'}
                              </div>
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
                {/* Template Editor - Show when platform is selected */}
                {selectedPlatform && (
                  <div className="wpsp-template-textarea">
                    <div className='wpsp-textarea-wrapper'>
                      <textarea 
                        value={customTemplates[selectedPlatform] || ''}
                        onChange={ (e) => setCustomTemplates(prev => ({ ...prev, [selectedPlatform]: e.target.value })) }
                        placeholder={__('Enter your custom template here...', 'wp-scheduled-posts')}
                        id="wpsp-template-input"
                        className="wpsp-template-input"
                        rows={4}
                        disabled={(globalProfile != null && globalProfile != selectedPlatform) ? true : false}
                      />
                    </div>
                    <div className="wpsp-template-meta">
                      <span className="wpsp-placeholders">
                        {__('Available:', 'wp-scheduled-posts')} {'{title}'} {'{content}'} {'{url}'} {'{tags}'}
                      </span>
                      {/* Global template checkbox for the selected platform */}
                      <div className="wpsp-custom-template-field-info">
                        <span className={`${showPreview ? 'active' : 'inactive'}`} onClick={ () => setShowPreview(!showPreview) }>
                          { showPreview ? eyeCloseIcon : eyeIcon }
                        </span>
                        <span className={`wpsp-char-count ${isOverLimit ? 'over-limit' : ''}`}>
                          {characterCount}/{currentLimit}
                        </span>
                      </div>
                    </div>
                    <div className={`wpsp-global-template ${ !showPreview ? 'hide-preview' : '' }`}>
                      <span className={ availableProfiles?.length == 0 ? 'wpsp-use-global-template-text disabled' : '' }  style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                        { __('Use global template','wp-scheduled-posts') }
                        <span className="wpsp-tooltip-wrapper">
                          <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            fill="none"
                            viewBox="0 0 24 24"
                            className="wpsp-tooltip-icon"
                          >
                            <circle cx="12" cy="12" r="10" fill="#6a4bff"/>
                            <text x="12" y="16" textAnchor="middle" fontSize="12" fill="#fff" fontFamily="Arial" fontWeight="bold">i</text>
                          </svg>
                          <span className="wpsp-tooltip-text">
                            {__('If enabled, this template will be applied across all the selected social platforms.', 'wp-scheduled-posts')}
                          </span>
                        </span>
                      </span>
                      { showGlobalTemplateWarning &&
                        <div className='use-global-template-warning'>
                          <span>{ __(`${globalProfile?.charAt(0).toUpperCase() + globalProfile.slice(1)} is enabled as global template`,'wp-scheduled-posts') }</span>
                        </div>
                      }
                      <div className={`wpsp-use-global-template-checkbox-wrapper ${(availableProfiles.length == 0 || (globalProfile != null && globalProfile != selectedPlatform) ) ? 'disabled' : ''}`}>
                        <input
                          type="checkbox"
                          id={`useGlobalTemplate_${selectedPlatform}`}
                          checked={getIsGlobalForPlatform(selectedPlatform)}
                          disabled={ availableProfiles?.length == 0 ? true : false }
                          onChange={e => {
                            if( (globalProfile != null && globalProfile != selectedPlatform) ) {
                              setShowGlobalTemplateWarning(true);
                              setTimeout(() => {
                                setShowGlobalTemplateWarning(false);
                              }, 3000);
                            }else {
                              setUseGlobalTemplatePlatform(selectedPlatform, e.target.checked)
                            }
                          }}
                        />
                        <label htmlFor={`useGlobalTemplate_${selectedPlatform}`}></label>
                      </div>
                    </div>
                  </div>
                )}
                {/* Date & Time Scheduling Fields */}
                <div className="wpsp-date-time-section" style={{ marginBottom: '1.5em' }}>
                  <div style={{ display: 'flex', gap: '1.5em', alignItems: 'flex-end' }}>
                    {/* Date Field */}
                    <div>
                      <label style={{ fontWeight: 600, display: 'block', marginBottom: 4 }}>{__('Date', 'wp-scheduled-posts')}</label>
                      <select
                        value={scheduleData.dateOption}
                        onChange={e => setScheduleData(prev => ({ ...prev, dateOption: e.target.value }))}
                        className="wpsp-date-select"
                      >
                        {getDateOptions().map(option => (
                          <option key={option.value} value={option.value}>
                            {option.label}
                          </option>
                        ))}
                      </select>
                      {(scheduleData.dateOption === 'in_days' || scheduleData.dateOption === 'days_after') && (
                        <input
                          type="number"
                          min="1"
                          placeholder={
                            scheduleData.schedulingType === 'absolute'
                              ? __('Enter number of days', 'wp-scheduled-posts')
                              : __('Enter number of days after publication', 'wp-scheduled-posts')
                          }
                          value={scheduleData.customDays}
                          onChange={e => setScheduleData(prev => ({ ...prev, customDays: e.target.value }))}
                          style={{ marginTop: 6, width: '100%' }}
                        />
                      )}
                      {scheduleData.dateOption === 'custom_date' && (
                        <input
                          type="date"
                          value={scheduleData.customDate}
                          onChange={e => setScheduleData(prev => ({ ...prev, customDate: e.target.value }))}
                          style={{ marginTop: 6, width: '100%' }}
                        />
                      )}
                    </div>
                    {/* Time Field */}
                    <div>
                      <label style={{ fontWeight: 600, display: 'block', marginBottom: 4 }}>{__('Time', 'wp-scheduled-posts')}</label>
                      <select
                        value={scheduleData.timeOption}
                        onChange={e => setScheduleData(prev => ({ ...prev, timeOption: e.target.value }))}
                        className="wpsp-time-select"
                      >
                        {getTimeOptions().map(option => (
                          <option key={option.value} value={option.value}>
                            {option.label}
                          </option>
                        ))}
                      </select>
                      {(scheduleData.timeOption === 'in_hours' || scheduleData.timeOption === 'hours_after') && (
                        <input
                          type="number"
                          min="1"
                          placeholder={
                            scheduleData.schedulingType === 'absolute'
                              ? __('Enter number of hours', 'wp-scheduled-posts')
                              : __('Enter number of hours after publication', 'wp-scheduled-posts')
                          }
                          value={scheduleData.customHours}
                          onChange={e => setScheduleData(prev => ({ ...prev, customHours: e.target.value }))}
                          style={{ marginTop: 6, width: '100%' }}
                        />
                      )}
                      {scheduleData.timeOption === 'custom_time' && (
                        <input
                          type="time"
                          value={scheduleData.customTime}
                          onChange={e => setScheduleData(prev => ({ ...prev, customTime: e.target.value }))}
                          style={{ marginTop: 6, width: '100%' }}
                        />
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Right Side - Preview */}
          { showPreview &&
             <div className={`wpsp-modal-right ${selectedPlatform}`}>
              <div className="wpsp-preview-card">
              { availableProfiles.length > 0 ? (
                <>
                  <div className="wpsp-preview-header">
                    <div className="wpsp-preview-avatar">
                      <div className="wpsp-avatar-circle">
                          <img
                            src={previewThumbnailUrl}
                            alt={previewProfileName}
                            className="wpsp-profile-image"
                            onError={(e) => {
                              e.target.onerror = null; // Prevent infinite loop
                              e.target.src = `data:image/svg+xml;utf8,${encodeURIComponent(authorIcon)}`;
                            }}
                          />
                      </div>
  
                      <div className="wpsp-preview-info">
                        {selectedProfile.length > 0 && (
                          <div className="wpsp-preview-name">
                            {previewProfileName}
                          </div>
                        )}
                        <div className="wpsp-preview-date">{new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })}</div>
                      </div>
                    </div>
                  </div>
                  <div className="wpsp-preview-content-area">
                    {previewContent ? (
                      <div className="wpsp-preview-text" dangerouslySetInnerHTML={{ __html: previewContent }}></div>
                    ) : (
                      <div className="wpsp-preview-placeholder">
                        {__('Template preview will appear here...', 'wp-scheduled-posts')}
                      </div>
                    )}
  
                    {/* Mock post preview */}
                    <div className="wpsp-preview-post">
                      <div className="wpsp-preview-image">
                        {uploadSocialShareBanner ? (
                          <img src={uploadSocialShareBanner} alt="Preview" style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
                        ) : (
                          <div style={{ 
                            width: '100%', 
                            height: '100%', 
                            background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            color: 'white',
                            fontSize: '14px'
                          }}>
                            {__('No image selected', 'wp-scheduled-posts')}
                          </div>
                        )}
                      </div>
                      <div className="wpsp-preview-post-content">
                        <div className="wpsp-preview-url">{window.location.origin}</div>
                        <div className="wpsp-preview-title">
                          {postTitle || __('How to Add Anchor Links in Elementor? [3 Ways]', 'wp-scheduled-posts')}
                        </div>
                        <div className="wpsp-preview-excerpt" dangerouslySetInnerHTML={{ __html: postContent || __('Picture this — you are halfway through a lengthy web page, diving into the content and accidentally scrolling to the top of the page. Annoying, right? This is where anchor links become your best...', 'wp-scheduled-posts') }}></div>
                      </div>
                    </div>
                  </div>
                </>
              ) : (
                <div className="wpsp-preview-not-available">
                  {info}
                  <h3>{ __('Preview not available', 'wp-scheduled-posts') }</h3>
                  <p>{__('Please make sure you select a social profile first.', 'wp-scheduled-posts')}</p>
                  <a href="https://wpdeveloper.com/docs/use-custom-social-templates/" target='_blank'>{ __('Let\'s See How','wp-scheduled-posts') }</a>
                </div>
              )}
              </div>
            </div>
          }
         
        </div>

        {/* Modal Actions */}
        <div className="wpsp-modal-footer">
          <div className="wpsp-custom-social-footer-wrapper">
            <div className="wpsp-custom-social-footer-right">
              <Button isSecondary onClick={handleClose} className="wpsp-cancel-btn">
                {__('Cancel', 'wp-scheduled-posts')}
              </Button>
              <Button
                isPrimary
                onClick={handleGlobalSave}
                disabled={isSaving || isOverLimit || !hasAnyChanges()}
                className="wpsp-save-btn"
              >
                <span>{saveText}</span>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </Modal>
  );
};

export default CustomSocialTemplateModal;
