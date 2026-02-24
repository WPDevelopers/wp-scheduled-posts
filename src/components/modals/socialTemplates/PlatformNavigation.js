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
                            className={`wpsp-platform-icon ${isActive ? 'active' : ''} ${isDisabled ? 'disabled-profile' : 'has-data'}`}
                            onClick={!isDisabled ? () => onSelectPlatform(platform) : undefined}
                            disabled={isDisabled}
                            style={{
                                backgroundColor: isActive ? bgColor : '#f0f0f0',
                                color: isActive ? '#fff' : '#666',
                                fontWeight: isActive ? 'bold' : 'normal',
                                cursor: isDisabled ? 'not-allowed' : 'pointer',
                            }}
                        >
                            {icon}
                        </button>

                        {isDisabled && (
                            <span className="wpsp-tooltip">
                                No profile data available
                            </span>
                        )}
                    </div>
                );
            })}
        </div>
    );
};

export default memo(PlatformNavigation);