import React, { useContext, useState } from 'react';
import { AppContext } from '../../context/AppContext';

const Footer = () => {
    const { state } = useContext(AppContext);
    const { socialShareSettings } = state;
    const { unpublishOn, republishOn } = state;
    const [isSaving, setIsSaving] = useState(false);

    const handleSaveSettings = () => {
        if (typeof wp === 'undefined' || !wp.apiFetch) return;

        let postId = null;
        if (wp.data && wp.data.select && wp.data.select('core/editor')) {
             postId = wp.data.select('core/editor').getCurrentPostId();
        }
        
        if (!postId && typeof window.WPSchedulePostsFree !== 'undefined') {
            postId = window.WPSchedulePostsFree.current_post_id;
        }

        if (!postId) return;

        setIsSaving(true);

        const data = {
            disable_social_share: socialShareSettings.isSocialShareDisabled,
            social_banner_id: socialShareSettings.socialBannerId
        };

        wp.apiFetch({
            path: `/wp-scheduled-posts/v1/social-settings/${postId}`,
            method: 'POST',
            data: data
        }).then((response) => {
            setIsSaving(false);
            // Optional: Dispatch a success notice
            if (wp.data.dispatch('core/notices')) {
                wp.data.dispatch('core/notices').createSuccessNotice(
                    'Settings saved successfully.',
                    { type: 'snackbar' }
                );
            }
        }).catch((error) => {
            setIsSaving(false);
            if (wp.data.dispatch('core/notices')) {
                wp.data.dispatch('core/notices').createErrorNotice(
                    'Failed to save settings.',
                    { type: 'snackbar' }
                );
            }
            console.error(error);
        });

        // Send api request to handle pro features
        wp.apiFetch({
            path: `/wp-scheduled-posts-pro/v1/pro-settings/${postId}`,
            method: 'POST',
            data: {
                unpublish_on: unpublishOn,
                republish_on: republishOn
            }
        }).then((response) => {
            setIsSaving(false);
            // Optional: Dispatch a success notice
            if (wp.data.dispatch('core/notices')) {
                wp.data.dispatch('core/notices').createSuccessNotice(
                    'Settings saved successfully.',
                    { type: 'snackbar' }
                );
            }
        }).catch((error) => {
            setIsSaving(false);
            if (wp.data.dispatch('core/notices')) {
                wp.data.dispatch('core/notices').createErrorNotice(
                    'Failed to save settings.',
                    { type: 'snackbar' }
                );
            }
            console.error(error);
        });


    };
    return (
        <div className="wpsp-post-panel-footer">
            <div className="wpsp-modal--footer">
                <button 
                    className="btn secondary-btn" 
                    id="wpsp-save-settings" 
                    onClick={handleSaveSettings}
                    disabled={isSaving}
                >
                    {isSaving ? 'Saving...' : 'Save Settings'}
                </button>
                <button className="btn primary-btn">Share Now
                    <svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1.5 11L6.5 6L1.5 1" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                    </svg>
                </button>
            </div>
        </div>
    );
};

export default Footer;
