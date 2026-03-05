import React, { useContext, useState } from 'react';
import { AppContext } from '../../context/AppContext';

const showCustomToast = (type, message) => {
    if (typeof document === 'undefined') return;
    let container = document.getElementById('wpsp-custom-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'wpsp-custom-toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `wpsp-custom-toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
        }, 220);
    }, 2600);
};

const Footer = () => {
    const { state } = useContext(AppContext);
    const { socialShareSettings } = state;
    const { unpublishOn, republishOn, advancedSchedule, advancedScheduleDate, isScheduled, scheduleDate } = state;
    const [isSaving, setIsSaving] = useState(false);
    const shouldSchedulePost = !!isScheduled && !!scheduleDate;

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

        Promise.all([
            wp.apiFetch({
                path: `/wp-scheduled-posts/v1/social-settings/${postId}`,
                method: 'POST',
                data: data
            }),
            wp.apiFetch({
                path: `/wp-scheduled-posts-pro/v1/pro-settings/${postId}`,
                method: 'POST',
                data: {
                    unpublish_on: unpublishOn,
                    republish_on: republishOn,
                    advanced_schedule: advancedSchedule,
                    advanced_schedule_on: advancedScheduleDate,
                    is_scheduled: shouldSchedulePost,
                    schedule_date: shouldSchedulePost ? scheduleDate : ''
                }
            })
        ]).then(() => {
            showCustomToast('success', 'Settings saved successfully.');
        }).catch((error) => {
            showCustomToast('error', 'Failed to save settings.');
            console.error(error);
        }).finally(() => {
            setIsSaving(false);
        });


    };
    return (
        <div className="wpsp-modal--footer">
            <button 
                className="btn primary-btn" 
                id="wpsp-save-settings" 
                onClick={handleSaveSettings}
                disabled={isSaving}
            >
                {isSaving ? 'Saving...' : 'Save Changes'}
            </button>
        </div>
    );
};

export default Footer;
