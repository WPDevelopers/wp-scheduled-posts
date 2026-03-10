import React, { memo } from 'react';

const PlatformNavigation = ({ 
    platforms, 
    selectedPlatform, 
    onSelectPlatform, 
    social_media_enabled
}) => {

    return (
        <div className="wpsp-platform-icons">
            {platforms.map(({ platform, icon, bgColor }) => {
                const isActive = selectedPlatform === platform;
                const isDisabled = !social_media_enabled[platform];
                return (
                    <div key={platform} className="wpsp-tooltip-wrapper">
                        <button
                            className={`wpsp-platform-icon ${isActive ? 'active' : ''} ${platform} ${isDisabled ? 'disabled-profile' : 'has-data'}`}
                            onClick={!isDisabled ? () => onSelectPlatform(platform) : undefined}
                            disabled={isDisabled}
                        >
                            {icon}
                        </button>

                        {isDisabled && (
                            <div className="wpsp-tooltip">
                                Not connected yet. <br/>
                                <span>Connect a social account from <br/> SchedulePress → Social Profiles.</span>
                            </div>
                        )}
                    </div>
                );
            })}
        </div>
    );
};

export default memo(PlatformNavigation);