import React, { useContext, useState } from 'react';
import { AppContext } from '../../context/AppContext';

const ensureToastStyles = () => {
    if (document.getElementById('wpsp-custom-toast-style')) return;
    const style = document.createElement('style');
    style.id = 'wpsp-custom-toast-style';
    style.textContent = `
        #wpsp-custom-toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 999999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .wpsp-custom-toast {
            min-width: 260px;
            max-width: 360px;
            padding: 12px 14px;
            border-radius: 8px;
            color: #fff;
            font-size: 13px;
            line-height: 1.3;
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transform: translateY(-8px);
            transition: all 0.2s ease;
        }
        .wpsp-custom-toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        .wpsp-custom-toast.success {
            background: #16a34a;
        }
        .wpsp-custom-toast.error {
            background: #dc2626;
        }
    `;
    document.head.appendChild(style);
};

const showCustomToast = (type, message) => {
    if (typeof document === 'undefined') return;
    ensureToastStyles();
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
