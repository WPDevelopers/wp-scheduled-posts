import React, { useState, useEffect } from 'react';
import './CustomSocialTemplateModal.css';

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
        facebook: '{title} {content} {url} {tags}',
        twitter: '{title} {content} {url} {tags}',
        linkedin: '{title} {content} {url} {tags}',
        pinterest: '{title} {content} {url} {tags}',
        instagram: '{title} {content} {url} {tags}',
        medium: '{title} {content} {url} {tags}',
        threads: '{title} {content} {url} {tags}'
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
  // useEffect(() => {
  //   setSelectedProfile('');
  // }, [selectedPlatform]);

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

  // Helper functions
  const insertTag = (tag) => {
    const textarea = document.querySelector('.wpsp-template-textarea');
    if (textarea) {
      const start = textarea.selectionStart;
      const end = textarea.selectionEnd;
      const newValue = customTemplate.substring(0, start) + tag + customTemplate.substring(end);
      setCustomTemplate(newValue);

      // Set cursor position after the inserted tag
      setTimeout(() => {
        textarea.focus();
        textarea.setSelectionRange(start + tag.length, start + tag.length);
      }, 0);
    }
  };

  const getPlatformTips = (platform) => {
    const tips = {
      facebook: __('• Use engaging questions to boost interaction\n• Include relevant hashtags (1-2 recommended)\n• Optimal length: 40-80 characters', 'wp-scheduled-posts'),
      twitter: __('• Keep it concise and punchy\n• Use trending hashtags\n• Optimal length: 71-100 characters', 'wp-scheduled-posts'),
      linkedin: __('• Professional tone works best\n• Include industry-relevant hashtags\n• Optimal length: 150-300 characters', 'wp-scheduled-posts'),
      pinterest: __('• Use descriptive, keyword-rich text\n• Include relevant hashtags\n• Optimal length: 100-200 characters', 'wp-scheduled-posts'),
      instagram: __('• Use engaging captions with emojis\n• Include 5-10 relevant hashtags\n• Optimal length: 125-150 characters', 'wp-scheduled-posts'),
      medium: __('• Focus on compelling headlines\n• Use subtitle for context\n• Optimal length: 50-100 characters', 'wp-scheduled-posts'),
      threads: __('• Keep it conversational\n• Use relevant hashtags sparingly\n• Optimal length: 100-500 characters', 'wp-scheduled-posts')
    };
    return tips[platform] || __('Select a platform to see specific tips', 'wp-scheduled-posts');
  };

  const clearTemplate = () => {
    setCustomTemplate('');
  };

  const loadExistingTemplate = () => {
    if (selectedPlatform) {
      const customTemplates = getCustomTemplatesMeta();
      const existingTemplate = customTemplates[selectedPlatform] || '';
      setCustomTemplate(existingTemplate);
    }
  };

  if (!isOpen) return null;

  const availableProfiles = getAvailableProfiles();
  const currentLimit = platformLimits[selectedPlatform] || 1000;
  const isOverLimit = characterCount > currentLimit;

  return (
    <Modal
      title={__('Create Social Message Template', 'wp-scheduled-posts')}
      onRequestClose={onClose}
      className="wpsp-custom-template-modal"
      style={{ maxWidth: '900px', width: '95vw', height: '90vh' }}
    >
      <div className="wpsp-modal-container">
        {/* Header Section */}
        <div className="wpsp-modal-header">
          <h3 className="wpsp-modal-title">{__('Customize Your Social Media Message', 'wp-scheduled-posts')}</h3>
          <p className="wpsp-modal-subtitle">{__('Create platform-specific templates with dynamic placeholders', 'wp-scheduled-posts')}</p>
        </div>

        {/* Main Content */}
        <div className="wpsp-modal-content">
          <div className="wpsp-modal-layout">
            {/* Left Side - Template Editor */}
            <div className="wpsp-modal-left">
              {/* Platform Selection */}
              <div className="wpsp-section">
                <h4 className="wpsp-section-title">{__('Select Platform', 'wp-scheduled-posts')}</h4>
                <div className="wpsp-platform-grid">
                  {[
                    { platform: 'facebook', icon: 'f', label: 'Facebook', bgColor: '#1877f2' },
                    { platform: 'twitter', icon: '𝕏', label: 'Twitter', bgColor: '#000000' },
                    { platform: 'linkedin', icon: 'in', label: 'LinkedIn', bgColor: '#0077b5' },
                    { platform: 'pinterest', icon: 'P', label: 'Pinterest', bgColor: '#bd081c' },
                    { platform: 'instagram', icon: '📷', label: 'Instagram', bgColor: '#e4405f' },
                    { platform: 'medium', icon: 'M', label: 'Medium', bgColor: '#00ab6c' },
                    { platform: 'threads', icon: '@', label: 'Threads', bgColor: '#000' }
                  ].map(({ platform, icon, label, bgColor }) => (
                    <button
                      key={platform}
                      className={`wpsp-platform-card ${selectedPlatform === platform ? 'active' : ''}`}
                      onClick={() => setSelectedPlatform(platform)}
                      title={label}
                    >
                      <div
                        className="wpsp-platform-icon"
                        style={{
                          backgroundColor: selectedPlatform === platform ? bgColor : '#f8f9fa',
                          color: selectedPlatform === platform ? '#fff' : bgColor,
                        }}
                      >
                        {icon}
                      </div>
                      <span className="wpsp-platform-label">{label}</span>
                      {getCustomTemplatesMeta()?.[platform] && (
                        <div className="wpsp-template-indicator">✓</div>
                      )}
                    </button>
                  ))}
                </div>
              </div>

              {/* Profile Selection - Optional for reference */}
              {selectedPlatform && availableProfiles.length > 0 && (
                <div className="wpsp-section">
                  <h4 className="wpsp-section-title">{__('Connected Profiles', 'wp-scheduled-posts')} <span className="wpsp-optional">({__('Optional', 'wp-scheduled-posts')})</span></h4>
                  <div className="wpsp-profile-list">
                    {availableProfiles.slice(0, 3).map(profile => (
                      <div key={profile.id} className="wpsp-profile-item">
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
                          <div className="wpsp-profile-type">{profile.type || __('Profile', 'wp-scheduled-posts')}</div>
                        </div>
                      </div>
                    ))}
                    {availableProfiles.length > 3 && (
                      <div className="wpsp-profile-more">
                        +{availableProfiles.length - 3} {__('more', 'wp-scheduled-posts')}
                      </div>
                    )}
                  </div>
                </div>
              )}

              {/* Template Editor */}
              <div className="wpsp-section">
                <div className="wpsp-section-header">
                  <h4 className="wpsp-section-title">{__('Template Editor', 'wp-scheduled-posts')}</h4>
                  <div className="wpsp-template-actions">
                    <button
                      type="button"
                      className="wpsp-btn wpsp-btn-outline"
                      onClick={loadExistingTemplate}
                      disabled={!selectedPlatform}
                      title={__('Load existing template for this platform', 'wp-scheduled-posts')}
                    >
                      {__('Load Existing', 'wp-scheduled-posts')}
                    </button>
                    <button
                      type="button"
                      className="wpsp-btn wpsp-btn-outline"
                      onClick={clearTemplate}
                      title={__('Clear template content', 'wp-scheduled-posts')}
                    >
                      {__('Clear', 'wp-scheduled-posts')}
                    </button>
                  </div>
                </div>

                {selectedPlatform ? (
                  <div className="wpsp-template-input-container">
                    <div className="wpsp-template-input-wrapper">
                      <textarea
                        value={customTemplate}
                        onChange={(e) => setCustomTemplate(e.target.value)}
                        placeholder={__('Create your custom template using placeholders like {title}, {content}, {url}, and {tags}...', 'wp-scheduled-posts')}
                        className="wpsp-template-textarea"
                        rows="8"
                      />
                      <div className="wpsp-template-overlay">
                        <div className="wpsp-character-indicator">
                          <span className={`wpsp-character-count ${isOverLimit ? 'over-limit' : ''}`}>
                            {characterCount}
                          </span>
                          <span className="wpsp-character-limit">/{currentLimit}</span>
                        </div>
                      </div>
                    </div>

                    {/* Quick Insert Tags */}
                    <div className="wpsp-quick-tags">
                      <div className="wpsp-quick-tags-header">
                        <span className="wpsp-quick-tags-label">{__('Quick Insert:', 'wp-scheduled-posts')}</span>
                      </div>
                      <div className="wpsp-tag-buttons">
                        {[
                          { tag: '{title}', label: __('Title', 'wp-scheduled-posts'), icon: '📝' },
                          { tag: '{content}', label: __('Content', 'wp-scheduled-posts'), icon: '📄' },
                          { tag: '{url}', label: __('URL', 'wp-scheduled-posts'), icon: '🔗' },
                          { tag: '{tags}', label: __('Tags', 'wp-scheduled-posts'), icon: '🏷️' }
                        ].map(({ tag, label, icon }) => (
                          <button
                            key={tag}
                            type="button"
                            className="wpsp-tag-btn"
                            onClick={() => insertTag(tag)}
                            title={__('Insert', 'wp-scheduled-posts') + ' ' + tag}
                          >
                            <span className="wpsp-tag-icon">{icon}</span>
                            <span className="wpsp-tag-text">{label}</span>
                          </button>
                        ))}
                      </div>
                    </div>
                  </div>
                ) : (
                  <div className="wpsp-select-platform-hint">
                    <div className="wpsp-hint-icon">👆</div>
                    <div className="wpsp-hint-text">
                      {__('Select a platform above to start creating your custom template', 'wp-scheduled-posts')}
                    </div>
                  </div>
                )}
              </div>
          </div>

          {/* Right Side - Live Preview */}
          <div className="wpsp-modal-right">
            <div className="wpsp-section">
              <h4 className="wpsp-section-title">{__('Live Preview', 'wp-scheduled-posts')}</h4>

              <div className="wpsp-preview-container">
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
                      <div className="wpsp-preview-text">{previewContent}</div>
                    ) : (
                      <div className="wpsp-preview-placeholder">
                        <div className="wpsp-placeholder-icon">👁️</div>
                        <div className="wpsp-placeholder-text">
                          {__('Template preview will appear here as you type...', 'wp-scheduled-posts')}
                        </div>
                      </div>
                    )}

                    {/* Mock post preview card */}
                    <div className="wpsp-preview-post-card">
                      <div className="wpsp-preview-image">
                        {uploadSocialShareBanner ? (
                          <img src={uploadSocialShareBanner} alt="Preview" />
                        ) : (
                          <div className="wpsp-preview-image-placeholder">
                            <div className="wpsp-image-icon">🖼️</div>
                            <span>{__('Featured Image', 'wp-scheduled-posts')}</span>
                          </div>
                        )}
                      </div>
                      <div className="wpsp-preview-post-content">
                        <div className="wpsp-preview-url">{window.location.origin}</div>
                        <div className="wpsp-preview-title">
                          {postTitle || __('Test Custom Templates', 'wp-scheduled-posts')}
                        </div>
                        <div className="wpsp-preview-excerpt">
                          {postContent || __('This is a test post for custom templates', 'wp-scheduled-posts')}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Platform-specific tips */}
                {selectedPlatform && (
                  <div className="wpsp-platform-tips">
                    <h5 className="wpsp-tips-title">{__('Platform Tips', 'wp-scheduled-posts')}</h5>
                    <div className="wpsp-tips-content">
                      {getPlatformTips(selectedPlatform)}
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Fixed Footer */}
        <div className="wpsp-modal-footer">
          <div className="wpsp-footer-content">
            <div className="wpsp-footer-left">
            </div>

            <div className="wpsp-footer-right">
              <Button
                isSecondary
                onClick={onClose}
                className="wpsp-cancel-btn"
                variant="secondary"
              >
                {__('Cancel', 'wp-scheduled-posts')}
              </Button>
              <Button
                isPrimary
                onClick={handleSave}
                disabled={!selectedPlatform || !customTemplate.trim() || isOverLimit}
                className="wpsp-save-btn"
                variant="primary"
              >
                <span className="wpsp-btn-icon">💾</span>
                {__('Save Template', 'wp-scheduled-posts')}
              </Button>
            </div>
          </div>
        </div>
      </div>
    </Modal>
  );
};

export default CustomSocialTemplateModal;
