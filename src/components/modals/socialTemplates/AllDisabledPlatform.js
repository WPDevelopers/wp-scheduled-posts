import React, { memo } from 'react';

const AllDisabledPlatform = ({ platforms }) => {
    return (
        <div className="wpsp-platform-icons all-profiles-disabled">
            {platforms.map(({ platform, icon }) => (
                <div key={platform} className="wpsp-tooltip-wrapper">
                    <div className={`wpsp-platform-icon-button-wrapper ${platform}`}>
                        <button
                            key={platform}
                            className={`wpsp-platform-icon disabled`}
                            title={platform}
                        >
                            {icon}
                        </button>

                        <div className="wpsp-tooltip">
                            Not connected yet. <br/>
                            <span>Connect a social account from <br/> SchedulePress → Social Profiles.</span>
                        </div>
                    </div>
                </div>
            ))}
            <p className="wpsp-disabled-message">*Connect accounts from SchedulePress → Social Profiles to enable sharing.</p>
        </div>
    );
};

export default memo(AllDisabledPlatform);
