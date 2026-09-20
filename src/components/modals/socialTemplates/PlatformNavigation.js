import React, { memo } from 'react';
import useProOverlay from '../../../helper/useProOverlay';

const PlatformNavigation = ({ 
    platforms, 
    selectedPlatform, 
    onSelectPlatform, 
    social_media_enabled
}) => {
    const { openProPopup } = useProOverlay();

    return (
        <div className="wpsp-platform-icons">
            {platforms.map(({ platform, icon, bgColor, locked, label }) => {
                const isActive = selectedPlatform === platform;
                // A locked platform is sold, not connected: it never counts as
                // enabled, and clicking it asks for an upgrade rather than a
                // social account.
                const isDisabled = locked || !social_media_enabled[platform];
                return (
                    <div key={platform} className="wpsp-tooltip-wrapper">
                        <div className={`wpsp-platform-icon-button-wrapper ${isActive ? 'active' : ''} ${platform}`}>
                            <button
                                className={`wpsp-platform-icon ${isActive ? 'active' : ''} ${platform} ${isDisabled ? 'disabled-profile' : 'has-data'}`}
                                onClick={locked ? openProPopup : (!isDisabled ? () => onSelectPlatform(platform) : undefined)}
                                disabled={isDisabled && !locked}
                            >
                                {icon}
                            </button>

                            {locked ? (
                                <div className="wpsp-tooltip">
                                    {label} is a PRO feature. <br/>
                                    <span>Upgrade to SchedulePress Pro <br/> to share here.</span>
                                </div>
                            ) : isDisabled && (
                                <div className="wpsp-tooltip">
                                    Not connected yet. <br/>
                                    <span>Connect a social account from <br/> SchedulePress → Social Profiles.</span>
                                </div>
                            )}
                        </div>
                    </div>
                );
            })}
        </div>
    );
};

export default memo(PlatformNavigation);