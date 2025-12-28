import React, { useContext, useState } from 'react';
import { Button } from '@wordpress/components';
import { AppContext } from '../../../context/AppContext';

const WPSPCustomTemplateModal = ({
  __ = (text) => text,
  WPSchedulePostsFree = { adminURL: '#', assetsURI: '' },
  authorIcon = '<svg></svg>',
  tikIcon = '✔',
  eyeIcon = '👁️',
  eyeCloseIcon = '🙈',
  info = 'Info message here',
  availableProfiles = [
    { id: 1, name: 'Profile 1', thumbnail_url: '' },
    { id: 2, name: 'Profile 2', thumbnail_url: '' },
    { id: 3, name: 'Profile 3', thumbnail_url: '' },
    { id: 4, name: 'Profile 4', thumbnail_url: '' },
    { id: 5, name: 'Profile 5', thumbnail_url: '' },
    { id: 6, name: 'Profile 6', thumbnail_url: '' },
  ],
  filteredPlatforms = [
    { platform: 'facebook', icon: '📘', bgColor: '#1877f2' },
    { platform: 'twitter', icon: '🐦', bgColor: '#1da1f2' },
  ],
  postTitle = 'Sample Post Title',
  postContent = '<p>Sample post content...</p>',
  uploadSocialShareBanner = '',
}) => {
  const { state, dispatch } = useContext(AppContext);
  const [selectedPlatform, setSelectedPlatform] = useState('facebook');
  const [selectedProfile, setSelectedProfile] = useState([]);
  const [customTemplates, setCustomTemplates] = useState({});
  const [showPreview, setShowPreview] = useState(true);
  const [activeDropdown, setActiveDropdown] = useState(false);
  const [scheduleData, setScheduleData] = useState({
    dateOption: 'today',
    timeOption: 'now',
    customDays: 1,
    customHours: 1,
    customDate: '',
    customTime: '',
    schedulingType: 'absolute',
  });
  const [showGlobalTemplateWarning, setShowGlobalTemplateWarning] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [characterCount, setCharacterCount] = useState(0);
  const currentLimit = 280;
  const isOverLimit = characterCount > currentLimit;
  const globalProfile = null;

  const platformHasData = (platform) => true;
  const getIsGlobalForPlatform = (platform) => false;
  const setUseGlobalTemplatePlatform = () => {};
  const getDateOptions = () => [{ value: 'today', label: 'Today' }];
  const getTimeOptions = () => [{ value: 'now', label: 'Now' }];
  const handlePlatformSwitch = (platform) => setSelectedPlatform(platform);
  const handleGlobalSave = () => alert('Save changes');
  const hasAnyChanges = () => true;

  const previewProfileName = selectedProfile[0]?.name || 'Preview Name';
  const previewThumbnailUrl = selectedProfile[0]?.thumbnail_url || '';

  const previewContent = customTemplates[selectedPlatform] || '<p>Template preview...</p>';


    const handleClose = () => {
        dispatch({ type: 'SET_CUSTOM_SOCIAL_MESSAGE_MODAL', payload: false });
    };
  return (
    <div className={`wpsp-modal-content ${availableProfiles.length === 0 ? 'no-profile-found' : ''}`}>
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

          {/* Profile Selection */}
          <div className="wpsp-custom-template-content-wrapper">
            {availableProfiles.length === 0 && (
              <h5
                dangerouslySetInnerHTML={{
                  __html: __(
                    `*You may forget to add or enable profile/page from <a target="_blank" href='${WPSchedulePostsFree.adminURL}admin.php?page=schedulepress&tab=social-profile'>SchedulePress settings</a>.`
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
                  <textarea
                    value={customTemplates[selectedPlatform] || ''}
                    onChange={(e) =>
                      setCustomTemplates((prev) => ({ ...prev, [selectedPlatform]: e.target.value }))
                    }
                    placeholder={__('Enter your custom template here...', 'wp-scheduled-posts')}
                    className="wpsp-template-input"
                    rows={4}
                  />
                </div>
              )}
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
                        {uploadSocialShareBanner ? (
                          <img
                            src={uploadSocialShareBanner}
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
              {__('Save', 'wp-scheduled-posts')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default WPSPCustomTemplateModal;
