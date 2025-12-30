import React, { memo, useState } from 'react';
const { __ } = wp.i18n;
import { tikIcon, authorIcon } from '../../../../assets/gutenberg/utils/helpers/icons';

const ProfileSelector = ({ 
    availableProfiles, 
    selectedProfile, 
    onSelectProfile,
    WPSchedulePostsFree,
    isLoading
}) => {
    const [activeDropdown, setActiveDropdown] = useState(false);

    if (isLoading) {
        return (
            <div className="wpsp-profile-selection-area-wrapper loading" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '20px' }}>
                {__('Loading profiles...', 'wp-scheduled-posts')}
            </div>
        );
    }

    if (availableProfiles.length === 0) {
        return (
            <>
                <h5
                    dangerouslySetInnerHTML={{
                        __html: __(
                            `*You may forget to add or enable profile/page from <a target="_blank" href='${WPSchedulePostsFree.adminURL}admin.php?page=schedulepress&tab=social-profile'>SchedulePress settings</a>.`,
                            'wp-scheduled-posts'
                        ),
                    }}
                ></h5>
                
                {/* Empty state styling if needed */}
                <div className="wpsp-profile-selection-area-wrapper no-profile-found"></div>
            </>
        );
    }

    return (
        <div className="wpsp-profile-selection-area-wrapper">
            <div className="selected-profile-area">
                <ul>
                    {availableProfiles.slice(0, 5).map((profile) => {
                        const isSelected = selectedProfile.some((p) => p.id === profile.id);
                        return (
                            <li
                                key={profile.id}
                                className="selected-profile"
                                title={profile.name}
                                onClick={() => onSelectProfile(profile)}
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
                                                onSelectProfile(profile);
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
                                onClick={() => onSelectProfile(profile)}
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
        </div>
    );
};

export default memo(ProfileSelector);
