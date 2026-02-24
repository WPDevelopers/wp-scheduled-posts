import React, { memo } from 'react';

const AllDisabledPlatform = ({ platforms }) => {
    return (
        <div className="wpsp-platform-icons all-profiles-disabled">
            {platforms.map(({ platform, icon }) => (
                <button
                    key={platform}
                    className={`wpsp-platform-icon disabled`}
                    title={platform}
                >
                    {icon}
                </button>
            ))}
            <p className="wpsp-disabled-message">*Connect accounts from SchedulePress → Social Profiles to enable sharing.</p>
        </div>
    );
};

export default memo(AllDisabledPlatform);
