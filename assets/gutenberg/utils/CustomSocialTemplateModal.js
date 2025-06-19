import React, { useState, useEffect } from 'react';

const {
  components: { Modal, Button },
  data: { useSelect, useDispatch },
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
];

// Platform character limits
const platformLimits = {
  facebook: 63206,
  twitter: 280,
  linkedin: 1300,
  pinterest: 500,
  instagram: 2100,
  medium: 45000,
  threads: 480,
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
  postTitle,
  postContent,
  postUrl,
  uploadSocialShareBanner
}) => {
  // Get post meta, post ID, and post status first
  const { meta, postId, postStatus } = useSelect((select) => ({
    meta: select('core/editor').getEditedPostAttribute('meta') || {},
    postId: select('core/editor').getCurrentPostId(),
    postStatus: select('core/editor').getEditedPostAttribute('status'),
  }));

  const [selectedPlatform, setSelectedPlatform] = useState('facebook');
  const [selectedProfile, setSelectedProfile] = useState([]);
  const [customTemplates, setCustomTemplates] = useState({});
  const [characterCount, setCharacterCount] = useState(0);
  const [previewContent, setPreviewContent] = useState('');
  const [saveText, setSaveText] = useState(__('Save', 'wp-scheduled-posts'));
  // Temporary storage for unsaved template data when switching platforms
  const [tempTemplateData, setTempTemplateData] = useState({});
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
  const { editPost } = useDispatch('core/editor');

  // Only one platform can use global template at a time
  const useGlobalTemplatePlatform = meta._wpsp_use_global_template_platform || '';
  const setUseGlobalTemplatePlatform = (platform, checked) => {
    editPost({
      meta: {
        ...meta,
        _wpsp_use_global_template_platform: checked ? platform : '',
      },
    });
  };

  // Manage custom template values per platform
  const initialTemplates = React.useMemo(() => {
    const templates = (meta._wpsp_custom_templates || {});
    const result = {};
    for (const platform of SOCIAL_PLATFORMS) {
      result[platform] = templates[platform]?.template || '';
    }
    return result;
  }, [meta._wpsp_custom_templates]);

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
        { value: 'today', label: __('Today', 'wp-scheduled-posts') },
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
        { value: 'now', label: __('Now', 'wp-scheduled-posts') },
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

  // Save template and profiles for the selected platform
  const handleSave = async (platformToSave = selectedPlatform) => {
    try {
      setSaveText(__('Saving...', 'wp-scheduled-posts'));
      // Prepare updated templates
      const templates = { ...(meta._wpsp_custom_templates || {}) };
      templates[platformToSave] = {
        ...(templates[platformToSave] || {}),
        template: customTemplates[platformToSave] || '',
        profiles: selectedProfile.map(profile => profile.id),
      };
      // Save via REST API
      const response = await wp.apiFetch({
        path: `/wp-scheduled-posts/v1/custom-templates/${postId}`,
        method: 'POST',
        data: {
          platform: platformToSave,
          template: customTemplates[platformToSave] || '',
          profiles: selectedProfile.map(profile => profile.id),
          scheduling: scheduleData,
        },
      });
      if (response.success) {
        // Update local meta state
        editPost({
          meta: {
            ...meta,
            _wpsp_custom_templates: templates,
          },
        });
        setTempTemplateData(prev => {
          const updated = { ...prev };
          delete updated[platformToSave];
          return updated;
        });
        setSaveText(__('Saved', 'wp-scheduled-posts'));
        setTimeout(() => setSaveText(__('Save', 'wp-scheduled-posts')), 1200);
      } else {
        throw new Error(response.message || 'Failed to save template');
      }
    } catch (error) {
      setSaveText(__('Save', 'wp-scheduled-posts'));
      console.error('Error saving template:', error);
    }
  };

  // Helper to check if there are unsaved changes for the current platform
  const hasUnsavedChanges = (platform) => {
    const metaTemplate = meta._wpsp_custom_templates?.[platform]?.template || '';
    const metaProfiles = JSON.stringify(meta._wpsp_custom_templates?.[platform]?.profiles || []);
    const currentTemplate = customTemplates[platform] || '';
    const currentProfiles = JSON.stringify(selectedProfile.map(profile => profile.id));
    return metaTemplate !== currentTemplate || metaProfiles !== currentProfiles;
  };

  // Handle platform switching with auto-save
  const handlePlatformSwitch = async (newPlatform) => {
    if (selectedPlatform && hasUnsavedChanges(selectedPlatform)) {
      await handleSave(selectedPlatform);
    }
    setSelectedPlatform(newPlatform);
  };

  // Handle modal close with cleanup
  const handleClose = () => {
    // Clear all temporary data when closing modal
    setTempTemplateData({});
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
      // First check if we have temporary unsaved data for this platform
      const tempData = tempTemplateData[selectedPlatform];

      if (tempData) {
        // Load from temporary storage
        setCustomTemplates(prev => ({ ...prev, [selectedPlatform]: tempData.template }));
        setSelectedProfile(tempData.profiles || []);
      } else {
        // Load from saved meta data
        const platformData = meta._wpsp_custom_templates[selectedPlatform];
        setCustomTemplates(prev => ({ ...prev, [selectedPlatform]: platformData?.template || '' }));
        // Map stored profile IDs to full profile objects
        const profilesToSet = (platformData?.profiles || []).map(profileId =>
          getAvailableProfiles().find(profile => profile.id === profileId)
        ).filter(Boolean); // Filter out any undefined profiles if IDs don't match
        setSelectedProfile(profilesToSet);
      }
    }
  }, [selectedPlatform, tempTemplateData, meta, facebookProfileData, twitterProfileData, linkedinProfileData, pinterestProfileData, instagramProfileData, mediumProfileData, threadsProfileData]);

  // Keep state in sync with meta changes (e.g., on post load)
  useEffect(() => {
    setCustomTemplates(initialTemplates);
  }, [initialTemplates]);

  if (!isOpen) return null;

  const availableProfiles = getAvailableProfiles();
  const currentLimit = platformLimits[selectedPlatform] || 1000;
  const isOverLimit = characterCount > currentLimit;

  return (
    <Modal
      title={__('Create Social Message', 'wp-scheduled-posts')}
      onRequestClose={handleClose}
      className="wpsp-custom-template-modal"
      style={{ maxWidth: '800px', width: '90vw' }}
    >
      <div className="wpsp-modal-content">
        <div className="wpsp-modal-layout">
          {/* Left Side - Template Editor */}
          <div className="wpsp-modal-left">
            {/* Platform Selection Icons */}
            <div className="wpsp-platform-icons">
              {[
                { platform: 'facebook', icon: 'f', color: '#1877f2', bgColor: '#1877f2' },
                { platform: 'twitter', icon: '𝕏', color: '#000000', bgColor: '#000000' },
                { platform: 'linkedin', icon: 'in', color: '#0077b5', bgColor: '#0077b5' },
                { platform: 'pinterest', icon: 'P', color: '#bd081c', bgColor: '#bd081c' },
                { platform: 'instagram', icon: '📷', color: '#e4405f', bgColor: '#e4405f' },
                { platform: 'medium', icon: 'M', color: '#00ab6c', bgColor: '#00ab6c' },
                { platform: 'threads', icon: '@', color: '#000', bgColor: '#000' }
              ].map(({ platform, icon, bgColor }) => (
                <button
                  key={platform}
                  className={`wpsp-platform-icon ${selectedPlatform === platform ? 'active' : ''}`}
                  onClick={() => handlePlatformSwitch(platform)}
                  style={{
                    backgroundColor: selectedPlatform === platform ? bgColor : '#f0f0f0',
                    color: selectedPlatform === platform ? '#fff' : '#666',
                    fontWeight: selectedPlatform === platform ? 'bold' : 'normal'
                  }}
                  title={platform.charAt(0).toUpperCase() + platform.slice(1)}
                >
                  {icon}
                </button>
              ))}
            </div>

            <div className="wpsp-profile-selection-area-wrapper">
              <div className="selected-profile-area">
                <ul>
                  { selectedProfile && selectedProfile.map( ( profile ) => (
                    <li
                      key={profile.id}
                      className="selected-profile"
                      title={profile.name}
                    >
                      {profile.thumbnail_url ? (
                        <img
                          src={profile.thumbnail_url}
                          alt={profile.name}
                          className="wpsp-profile-image"
                        />
                      ) : (
                        <div className="wpsp-profile-placeholder">
                          {profile.name ? profile.name.charAt(0).toUpperCase() : '?'}
                        </div>
                      )}
                      <button
                        className="wpsp-remove-profile-btn"
                        onClick={(e) => {
                          e.stopPropagation(); // Prevent card click from re-selecting
                          setSelectedProfile(selectedProfile.filter(p => p.id !== profile.id));
                        }}
                      >
                        &times;
                      </button>
                    </li>
                  ) ) }
                </ul>
              </div>
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
                          />
                        ) : (
                          <div className="wpsp-profile-placeholder">
                            {profile.name ? profile.name.charAt(0).toUpperCase() : '?'}
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
            </div>
            {/* Template Editor - Show when platform is selected */}
            {selectedPlatform && (
              <div className="wpsp-template-textarea">
                <textarea
                  value={customTemplates[selectedPlatform] || ''}
                  onChange={(e) => setCustomTemplates(prev => ({ ...prev, [selectedPlatform]: e.target.value }))}
                  placeholder={__('Enter your custom template here...', 'wp-scheduled-posts')}
                  className="wpsp-template-input"
                  rows={6}
                  disabled={!!useGlobalTemplatePlatform && useGlobalTemplatePlatform !== selectedPlatform}
                />
                <div className="wpsp-template-meta">
                  <span className="wpsp-placeholders">
                    {__('Available:', 'wp-scheduled-posts')} {'{title}'} {'{content}'} {'{url}'} {'{tags}'}
                  </span>
                  {/* Only show the Use global template checkbox for the selected platform, and only if none is selected or this is the selected one */}
                  {(!useGlobalTemplatePlatform || useGlobalTemplatePlatform === selectedPlatform) && (
                    <div className='wpsp-global-template'>
                      <input
                        type="checkbox"
                        id={`useGlobalTemplate_${selectedPlatform}`}
                        checked={useGlobalTemplatePlatform === selectedPlatform}
                        onChange={e => setUseGlobalTemplatePlatform(selectedPlatform, e.target.checked)}
                      />
                      <label htmlFor={`useGlobalTemplate_${selectedPlatform}`}>{__('Use global template', 'wp-scheduled-posts')}</label>
                    </div>
                  )}
                  <span className={`wpsp-char-count ${isOverLimit ? 'over-limit' : ''}`}>
                    {characterCount}/{currentLimit}
                  </span>
                </div>
              </div>
            )}

            {/* Helper text when no platform is selected */}
            {!selectedPlatform && (
              <div className="wpsp-select-profile-hint">
                <div className="wpsp-hint-icon">👆</div>
                <div className="wpsp-hint-text">
                  {__('Select a platform above to start creating your custom template', 'wp-scheduled-posts')}
                </div>
              </div>
            )}

            {/* Date & Time Scheduling Fields */}
            <div className="wpsp-date-time-section" style={{ marginBottom: '1.5em' }}>
              <div style={{ display: 'flex', gap: '1.5em', alignItems: 'flex-end' }}>
                {/* Date Field */}
                <div>
                  <label style={{ fontWeight: 600, display: 'block', marginBottom: 4 }}>{__('DATE', 'wp-scheduled-posts')}</label>
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
                  <label style={{ fontWeight: 600, display: 'block', marginBottom: 4 }}>{__('TIME', 'wp-scheduled-posts')}</label>
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

          {/* Right Side - Preview */}
          <div className="wpsp-modal-right">
            <div className="wpsp-preview-card">
              <div className="wpsp-preview-header">
                <div className="wpsp-preview-avatar">
                  <div className="wpsp-avatar-circle">W</div>
                  <div className="wpsp-preview-info">
                    <div className="wpsp-preview-name">WPDeveloper</div>
                    <div className="wpsp-preview-date">{new Date().toLocaleDateString()}</div>
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
            </div>
          </div>
        </div>

        {/* Modal Actions */}
        <div className="wpsp-modal-footer">
          <Button isSecondary onClick={handleClose} className="wpsp-cancel-btn">
            {__('Cancel', 'wp-scheduled-posts')}
          </Button>
          <Button
            isPrimary
            onClick={() => handleSave(selectedPlatform)}
            disabled={!selectedPlatform || !customTemplates[selectedPlatform] || isOverLimit}
            className="wpsp-save-btn"
          >
            <span>{saveText}</span>
          </Button>
        </div>
      </div>
    </Modal>
  );
};

export default CustomSocialTemplateModal;
