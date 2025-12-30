import React, { memo } from 'react';

const PlatformNavigation = ({ 
    platforms, 
    selectedPlatform, 
    onSelectPlatform, 
    platformHasData 
}) => {
    return (
        <div className="wpsp-platform-icons">
            {platforms.map(({ platform, icon, bgColor }) => (
                <button
                    key={platform}
                    className={`wpsp-platform-icon ${selectedPlatform} ${selectedPlatform === platform ? 'active' : ''} ${platformHasData(platform) ? 'has-data' : ''}`}
                    onClick={() => onSelectPlatform(platform)}
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
    );
};

export default memo(PlatformNavigation);
