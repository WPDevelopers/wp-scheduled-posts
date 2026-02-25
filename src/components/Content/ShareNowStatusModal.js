import React, { Fragment, useMemo } from 'react';

const { Modal } = wp.components;
const { __ } = wp.i18n;

const getProfileKey = (profile) => `${profile.platform}::${profile.id}`;

const platformLabelMap = {
    facebook: 'Facebook',
    twitter: 'Twitter',
    linkedin: 'LinkedIn',
    pinterest: 'Pinterest',
    instagram: 'Instagram',
    medium: 'Medium',
    threads: 'Threads',
    google_business: 'Google Business Profile',
};

const getPlatformLogo = (platform) => {
    if (platform === 'google_business') {
        return `${WPSchedulePostsFree?.assetsURI}images/google-my-business-logo-small.png`;
    }
    return `${WPSchedulePostsFree?.assetsURI}images/${platform}.svg`;
};

const getBadgeLabel = (platform, profile) => {
    if (platform === 'pinterest') return __('Board', 'wp-scheduled-posts');
    if (platform === 'linkedin') {
        return profile?.type === 'organization' ? __('Page', 'wp-scheduled-posts') : __('Profile', 'wp-scheduled-posts');
    }
    return profile?.type || __('Profile', 'wp-scheduled-posts');
};

const ShareNowStatusModal = ({ isOpen, onClose, selectedProfiles, statusMap }) => {
    const groupedProfiles = useMemo(() => {
        const grouped = {};
        (selectedProfiles || []).forEach((profile) => {
            if (!grouped[profile.platform]) grouped[profile.platform] = [];
            grouped[profile.platform].push(profile);
        });
        return grouped;
    }, [selectedProfiles]);

    if (!isOpen) return null;

    return (
        <Modal className="social-share-modal" onRequestClose={onClose}>
            {Object.entries(groupedProfiles).map(([platform, profiles]) => (
                <div key={platform} className={`profile-${platform} social-profile`}>
                    <div className="social-logo">
                        <img src={getPlatformLogo(platform)} alt="" />
                        <h2>{platformLabelMap[platform] || platform}</h2>
                    </div>

                    {profiles.map((profile) => {
                        const profileKey = getProfileKey(profile);
                        const status = statusMap[profileKey] || { state: 'pending', message: __('Request Sending...', 'wp-scheduled-posts') };
                        const isSuccess = status.state === 'success';
                        const isFailed = status.state === 'error';

                        return (
                            <Fragment key={profileKey}>
                                <div className='single-profile'>
                                    <div className="single-profile-content">
                                        <div className="modal-content-left">
                                            <div className="profile-list">
                                                <img
                                                    src={profile?.thumbnail_url || `${WPSchedulePostsFree?.assetsURI}/images/author-logo.jpeg`}
                                                    alt={profile?.name || ''}
                                                    onError={(e) => {
                                                        e.target.onerror = null;
                                                        e.target.src = `${WPSchedulePostsFree?.assetsURI}/images/author-logo.jpeg`;
                                                    }}
                                                />
                                                <h3>{profile?.name}</h3>
                                                <span className={`badge ${platform}`}>{getBadgeLabel(platform, profile)}</span>
                                            </div>
                                        </div>
                                        <div className="modal-content-right">
                                            <span>
                                                {status.state === 'pending' && __('Request Sending...', 'wp-scheduled-posts')}
                                                {isSuccess && <Fragment><img src={`${WPSchedulePostsFree?.assetsURI}/images/response_success.svg`} alt="Shared" />{__('Shared', 'wp-scheduled-posts')}</Fragment>}
                                                {isFailed && <Fragment><img src={`${WPSchedulePostsFree?.assetsURI}/images/response_failed.svg`} alt="Failed" />{__('Failed', 'wp-scheduled-posts')}</Fragment>}
                                            </span>
                                        </div>
                                    </div>

                                    {!!status.message && (
                                        <div className={`message ${isSuccess ? 'success' : ''}`}>
                                            <span>{status.message}</span>
                                        </div>
                                    )}
                                </div>
                            </Fragment>
                        );
                    })}
                </div>
            ))}
        </Modal>
    );
};

export { getProfileKey };
export default ShareNowStatusModal;
