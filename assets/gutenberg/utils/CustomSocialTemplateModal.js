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
  const [selectedProfile, setSelectedProfile] = useState('');
  const [customTemplate, setCustomTemplate] = useState('');
  const [characterCount, setCharacterCount] = useState(0);
  const [previewContent, setPreviewContent] = useState('');

  // Get post meta for custom templates and post ID
  const { meta, postId } = useSelect((select) => ({
    meta: select('core/editor').getEditedPostAttribute('meta') || {},
    postId: select('core/editor').getCurrentPostId(),
  }));
  const { editPost } = useDispatch('core/editor');

  // Initialize meta structure if it doesn't exist
  const getCustomTemplatesMeta = () => {
    const customTemplates = meta._wpsp_custom_templates;
    if (!customTemplates || typeof customTemplates !== 'object') {
      return {
        facebook: '',
        twitter: '',
        linkedin: '',
        pinterest: '',
        instagram: '',
        medium: '',
        threads: ''
      };
    }
    return customTemplates;
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

  // Update character count and preview when template changes
  useEffect(() => {
    const preview = generatePreview(customTemplate);
    setPreviewContent(preview);
    setCharacterCount(preview.length);
  }, [customTemplate, postTitle, postContent, postUrl]);

  // Load existing template when platform changes
  useEffect(() => {
    if (selectedPlatform) {
      const customTemplates = getCustomTemplatesMeta();
      const existingTemplate = customTemplates[selectedPlatform] || '';
      setCustomTemplate(existingTemplate);
    }
  }, [selectedPlatform, meta]);

  // Reset profile selection when platform changes
  useEffect(() => {
    setSelectedProfile('');
  }, [selectedPlatform]);

  // Save template
  const handleSave = async () => {
    if (!customTemplate.trim()) {
      alert(__('Please enter a template.', 'wp-scheduled-posts'));
      return;
    }

    try {
      // Get the current templates
      const currentCustomTemplates = getCustomTemplatesMeta();

      // Create the updated templates structure
      const updatedTemplates = {
        ...currentCustomTemplates,
        [selectedPlatform]: customTemplate.trim()
      };

      // Send the request to save the template
      const response = await wp.apiFetch({
        path: `/wp-scheduled-posts/v1/custom-templates/${postId}`,
        method: 'POST',
        data: {
          platform: selectedPlatform,
          template: customTemplate.trim()
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

        onClose();
      } else {
        throw new Error(response.message || 'Failed to save template');
      }
    } catch (error) {
      console.error('Error saving template:', error);
      alert(__('Error saving template: ', 'wp-scheduled-posts') + (error.message || 'Please try again.'));
    }
  };

  // Delete template
  const handleDelete = async () => {
    if (!confirm(__('Are you sure you want to delete this template?', 'wp-scheduled-posts'))) {
      return;
    }

    try {
      const response = await wp.apiFetch({
        path: `/wp-scheduled-posts/v1/custom-templates/${postId}`,
        method: 'DELETE',
        data: {
          platform: selectedPlatform
        }
      });

      if (response.success) {
        // Update the local meta state
        const currentCustomTemplates = getCustomTemplatesMeta();
        const updatedTemplates = {
          ...currentCustomTemplates,
          [selectedPlatform]: ''
        };

        editPost({
          meta: {
            ...meta,
            _wpsp_custom_templates: updatedTemplates,
          },
        });

        setCustomTemplate('');
      } else {
        throw new Error(response.message || 'Failed to delete template');
      }
    } catch (error) {
      console.error('Error deleting template:', error);
      alert(__('Error deleting template: ', 'wp-scheduled-posts') + (error.message || 'Please try again.'));
    }
  };

  if (!isOpen) return null;

  const availableProfiles = getAvailableProfiles();
  const currentLimit = platformLimits[selectedPlatform] || 1000;
  const isOverLimit = characterCount > currentLimit;

  return (
    <Modal
      title={__('Create Social Message', 'wp-scheduled-posts')}
      onRequestClose={onClose}
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
                  onClick={() => setSelectedPlatform(platform)}
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

            {/* Profile Selection */}
            {selectedPlatform && (
              <div className="wpsp-profile-selection-area">
                {availableProfiles.length > 0 ? (
                  <div className="wpsp-profile-grid">
                    {availableProfiles.map(profile => (
                      <div
                        key={profile.id}
                        className={`wpsp-profile-card ${selectedProfile === profile.id ? 'selected' : ''}`}
                        onClick={() => {
                          // Toggle functionality: if already selected, deselect; otherwise select
                          if (selectedProfile === profile.id) {
                            setSelectedProfile(''); // Deselect
                          } else {
                            setSelectedProfile(profile.id); // Select
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
                          <div className="wpsp-profile-type">
                            {profile.type || __('Profile', 'wp-scheduled-posts')}
                          </div>
                        </div>
                        {selectedProfile === profile.id && (
                          <div className="wpsp-profile-checkmark">✓</div>
                        )}
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="wpsp-no-profiles">
                    <div className="wpsp-no-profiles-icon">🔗</div>
                    <div className="wpsp-no-profiles-text">
                      {__('No profiles connected for', 'wp-scheduled-posts')} {selectedPlatform.charAt(0).toUpperCase() + selectedPlatform.slice(1)}
                    </div>
                    <div className="wpsp-no-profiles-subtext">
                      {__('Please connect your social media accounts in the settings.', 'wp-scheduled-posts')}
                    </div>
                  </div>
                )}
              </div>
            )}

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
          <Button isSecondary onClick={onClose} className="wpsp-cancel-btn">
            {__('Cancel', 'wp-scheduled-posts')}
          </Button>
          {selectedPlatform && getCustomTemplatesMeta()?.[selectedPlatform] && (
            <Button isDestructive onClick={handleDelete} className="wpsp-delete-btn">
              {__('Delete Template', 'wp-scheduled-posts')}
            </Button>
          )}
          <Button
            isPrimary
            onClick={handleSave}
            disabled={!selectedPlatform || !customTemplate.trim() || isOverLimit}
            className="wpsp-save-btn"
          >
            {__('Save', 'wp-scheduled-posts')} →
          </Button>
        </div>
      </div>
    </Modal>
  );
};

export default CustomSocialTemplateModal;
