import React, { useState, useEffect } from 'react';

const {
  components: { Modal, Button },
  data: { useSelect, useDispatch },
} = wp;
const { __ } = wp.i18n;

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
  const [selectedPlatform, setSelectedPlatform] = useState('facebook');
  const [selectedProfile, setSelectedProfile] = useState([]);
  const [customTemplate, setCustomTemplate] = useState('');
  const [characterCount, setCharacterCount] = useState(0);
  const [previewContent, setPreviewContent] = useState('');
  const [saveText, setSaveText] = useState(__('Save', 'wp-scheduled-posts'));
  // Temporary storage for unsaved template data when switching platforms
  const [tempTemplateData, setTempTemplateData] = useState({});
  // Date & Time scheduling state
  const [scheduleData, setScheduleData] = useState({
    dateOption: 'today',
    customDays: '',
    customDate: '',
    timeOption: 'now',
    customHours: '',
    customTime: '',
  });

  // Get post meta for custom templates and post ID
  const { meta, postId } = useSelect((select) => ({
    meta: select('core/editor').getEditedPostAttribute('meta') || {},
    postId: select('core/editor').getCurrentPostId(),
  }));
  const { editPost } = useDispatch('core/editor');

  // Initialize meta structure if it doesn't exist and adapt old format
  const getCustomTemplatesMeta = () => {
    const customTemplates = meta._wpsp_custom_templates;
    const defaultPlatformData = { template: '', profiles: [] }; // New default structure for platform data

    // Base structure for all platforms, initialized with default data
    const allPlatformsDefault = {
      facebook: { ...defaultPlatformData },
      twitter: { ...defaultPlatformData },
      linkedin: { ...defaultPlatformData },
      pinterest: { ...defaultPlatformData },
      instagram: { ...defaultPlatformData },
      medium: { ...defaultPlatformData },
      threads: { ...defaultPlatformData }
    };

    if (!customTemplates || typeof customTemplates !== 'object') {
      return allPlatformsDefault;
    }

    // Adapt existing data to the new structure
    const adaptedTemplates = {};
    for (const platform in customTemplates) {
      if (Object.prototype.hasOwnProperty.call(customTemplates, platform)) {
        if (typeof customTemplates[platform] === 'string') {
          // Convert old string format to new object format with empty profiles
          adaptedTemplates[platform] = { template: customTemplates[platform], profiles: [] };
        } else if (typeof customTemplates[platform] === 'object' && customTemplates[platform] !== null) {
          // Use existing object format, ensuring template and profiles exist
          adaptedTemplates[platform] = {
            template: customTemplates[platform].template || '',
            profiles: customTemplates[platform].profiles || []
          };
        } else {
          // Fallback for unexpected types, use default
          adaptedTemplates[platform] = { ...defaultPlatformData };
        }
      }
    }
    
    // Merge adapted templates with default structure to ensure all platforms are present
    return { ...allPlatformsDefault, ...adaptedTemplates };
  };

  // Platform character limits
  const platformLimits = {
    facebook: 63206,
    twitter: 280,
    linkedin: 1300,
    pinterest: 500,
    instagram: 2100,
    medium: 45000,
    threads: 480
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
      default:
        return [];
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

  // Handle platform switching with temporary data storage
  const handlePlatformSwitch = (newPlatform) => {
    if (selectedPlatform && (customTemplate.trim() || selectedProfile.length > 0)) {
      // Store current unsaved data before switching
      setTempTemplateData(prev => ({
        ...prev,
        [selectedPlatform]: {
          template: customTemplate.trim(),
          profiles: selectedProfile
        }
      }));
    }
    setSelectedPlatform(newPlatform);
  };

  // Handle modal close with cleanup
  const handleClose = () => {
    // Clear all temporary data when closing modal
    setTempTemplateData({});
    onClose();
  };

  // Update character count and preview when template changes
  useEffect(() => {
    const preview = generatePreview(customTemplate);
    setPreviewContent(preview);
    setCharacterCount(preview.length);
  }, [customTemplate, postTitle, postContent, postUrl]);

  // Load existing template and profiles when platform changes
  useEffect(() => {
    if (selectedPlatform) {
      // First check if we have temporary unsaved data for this platform
      const tempData = tempTemplateData[selectedPlatform];

      if (tempData) {
        // Load from temporary storage
        setCustomTemplate(tempData.template || '');
        setSelectedProfile(tempData.profiles || []);
      } else {
        // Load from saved meta data
        const customTemplates = getCustomTemplatesMeta();
        const platformData = customTemplates[selectedPlatform];
        setCustomTemplate(platformData.template || '');
        // Map stored profile IDs to full profile objects
        const profilesToSet = (platformData.profiles || []).map(profileId =>
          getAvailableProfiles().find(profile => profile.id === profileId)
        ).filter(Boolean); // Filter out any undefined profiles if IDs don't match
        setSelectedProfile(profilesToSet);
      }
    }
  }, [selectedPlatform, tempTemplateData, meta, facebookProfileData, twitterProfileData, linkedinProfileData, pinterestProfileData, instagramProfileData, mediumProfileData, threadsProfileData]);

  // Save template
  const handleSave = async () => {
    try {
      // Get the current templates
      const currentCustomTemplates = getCustomTemplatesMeta();
      setSaveText(__('Saving...', 'wp-scheduled-posts'));
      // Create the updated templates structure
      const updatedTemplates = {
        ...currentCustomTemplates,
        [selectedPlatform]: {
          template: customTemplate.trim(),
          profiles: selectedProfile.map(profile => profile.id)
        }
      };

      // Send the request to save the template
      const response = await wp.apiFetch({
        path: `/wp-scheduled-posts/v1/custom-templates/${postId}`,
        method: 'POST',
        data: {
          platform: selectedPlatform,
          template: customTemplate.trim(),
          profiles: selectedProfile.map(profile => profile.id), // Send selected profile IDs
          scheduling: scheduleData
        }
      });

      if (response.success) {
        // Update the local meta state
        editPost({
          meta: {
            ...meta,
            _wpsp_custom_templates: updatedTemplates,
          },
        });
        // Clear temporary data for this platform since it's now saved
        setTempTemplateData(prev => {
          const updated = { ...prev };
          delete updated[selectedPlatform];
          return updated;
        });
        setSaveText(__('Saved', 'wp-scheduled-posts'));
      } else {
        throw new Error(response.message || 'Failed to save template');
      }
    } catch (error) {
      console.error('Error saving template:', error);
    }
  };

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
                  value={customTemplate}
                  onChange={(e) => setCustomTemplate(e.target.value)}
                  placeholder={__('Enter your custom template here...', 'wp-scheduled-posts')}
                  className="wpsp-template-input"
                  rows={6}
                />
                <div className="wpsp-template-meta">
                  <span className="wpsp-placeholders">
                    {__('Available:', 'wp-scheduled-posts')} {'{title}'} {'{content}'} {'{url}'} {'{tags}'}
                  </span>
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
                    <option value="today">{__('Today', 'wp-scheduled-posts')}</option>
                    <option value="tomorrow">{__('Tomorrow', 'wp-scheduled-posts')}</option>
                    <option value="next_week">{__('Next week', 'wp-scheduled-posts')}</option>
                    <option value="next_month">{__('Next month', 'wp-scheduled-posts')}</option>
                    <option value="in_days">{__('In __ days', 'wp-scheduled-posts')}</option>
                    <option value="custom_date">{__('Choose a custom date...', 'wp-scheduled-posts')}</option>
                  </select>
                  {scheduleData.dateOption === 'in_days' && (
                    <input
                      type="number"
                      min="1"
                      placeholder={__('Enter number of days', 'wp-scheduled-posts')}
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
                    <option value="now">{__('Now', 'wp-scheduled-posts')}</option>
                    <option value="in_1h">{__('In one hour', 'wp-scheduled-posts')}</option>
                    <option value="in_3h">{__('In three hours', 'wp-scheduled-posts')}</option>
                    <option value="in_5h">{__('In five hours', 'wp-scheduled-posts')}</option>
                    <option value="in_hours">{__('In __ hours', 'wp-scheduled-posts')}</option>
                    <option value="custom_time">{__('Choose a custom time...', 'wp-scheduled-posts')}</option>
                  </select>
                  {scheduleData.timeOption === 'in_hours' && (
                    <input
                      type="number"
                      min="1"
                      placeholder={__('Enter number of hours', 'wp-scheduled-posts')}
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
            onClick={handleSave}
            disabled={!selectedPlatform || !customTemplate.trim() || isOverLimit}
            className="wpsp-save-btn"
          >
            <span>{__(  saveText, 'wp-scheduled-posts')}</span>
          </Button>
        </div>
      </div>
    </Modal>
  );
};

export default CustomSocialTemplateModal;
