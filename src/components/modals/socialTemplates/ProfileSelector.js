import React, { memo, useState } from 'react';
const { __ } = wp.i18n;
import { tikIcon, authorIcon } from '../../../icons/icons';

const ProfileSelector = ({ 
    availableProfiles, 
    selectedProfile, 
    onSelectProfile,
    selectedPlatform,
    WPSchedulePostsFree,
    isLoading
}) => {
    const [activeDropdown, setActiveDropdown] = useState(false);
    const isPinterest = selectedPlatform === 'pinterest';
    const getProfileLabel = (profile) => {
        if (!isPinterest) {
            return profile.name;
        }

        return profile.displayName
            || (profile.sectionName ? `${profile.name} / ${profile.sectionName}` : profile.name);
    };

    if (isLoading) {
        return (
            <div className="wpsp-profile-selection-area-wrapper loading" style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', padding: '20px' }}>
                {__('Loading profiles...', 'wp-scheduled-posts')}
            </div>
        );
    }

    const noActiveProfile =
    !availableProfiles.length ||
    availableProfiles.every(profile => profile.status === false);

    if (noActiveProfile) {
        return (
            <>
                <h5
                    dangerouslySetInnerHTML={{
                        __html: __(
                            `*It seems you haven\'t connected any profile/page in your <a target="_blank" href='${WPSchedulePostsFree?.socialProfileURL}'>SchedulePress settings</a>.`,
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
                                title={getProfileLabel(profile)}
                                onClick={() => onSelectProfile(profile)}
                            >
                                {profile.thumbnail_url ? (
                                    <img
                                        src={profile.thumbnail_url}
                                        alt={getProfileLabel(profile)}
                                        className="wpsp-profile-image"
                                        onError={(e) => {
                                            e.target.onerror = null;
                                            e.target.src = `data:image/svg+xml;utf8,${encodeURIComponent(authorIcon)}`;
                                        }}
                                    />
                                ) : (
                                    <div className="wpsp-profile-placeholder">{getProfileLabel(profile)?.charAt(0).toUpperCase() || '?'}</div>
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
                                            alt={getProfileLabel(profile)}
                                            className="wpsp-profile-image"
                                            onError={(e) => {
                                                e.target.onerror = null;
                                                e.target.src = `data:image/svg+xml;utf8,${encodeURIComponent(authorIcon)}`;
                                            }}
                                        />
                                    ) : (
                                        <div className="wpsp-profile-placeholder">{getProfileLabel(profile)?.charAt(0).toUpperCase() || '?'}</div>
                                    )}
                                </div>
                                <div className="wpsp-profile-info">
                                    <div className="wpsp-profile-name">{getProfileLabel(profile)}</div>
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
