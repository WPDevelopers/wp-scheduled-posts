import { useContext, useState } from 'react';
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

    const getCurrentPostId = () => {
        // Gutenberg
        if (wp?.data?.select && wp.data.select('core/editor')) {
            const id = wp.data.select('core/editor').getCurrentPostId();
            if (id) return id;
        }

        // Localized globals (Classic / Elementor / builders)
        if (window.WPSchedulePostsFree?.current_post_id) {
            return window.WPSchedulePostsFree.current_post_id;
        }
        if (window.WPSchedulePosts?.current_post_id) {
            return window.WPSchedulePosts.current_post_id;
        }

        // Classic editor fallback
        const inputPostId = document.getElementById('post_ID')?.value;
        if (inputPostId) return parseInt(inputPostId, 10);

        // URL fallback
        const queryPostId = new URLSearchParams(window.location.search).get('post');
        if (queryPostId) return parseInt(queryPostId, 10);

        return null;
    };

    const handleSaveSettings = () => {
        if (typeof wp === 'undefined' || !wp.apiFetch) return;

        const postId = getCurrentPostId();

        if (!postId) return;

        setIsSaving(true);

        const data = {
            disable_social_share: socialShareSettings.isSocialShareDisabled,
            social_banner_id: socialShareSettings.socialBannerId
        };

        Promise.all([
            // Social settings (free plugin)
            wp.apiFetch({
                path: `/wp-scheduled-posts/v1/social-settings/${postId}`,
                method: 'POST',
                data: data
            }),
            // Post-panel settings: free plugin endpoint handles schedule_date,
            // then fires `schedulepress_after_free_settings_save` so the Pro
            // plugin picks up unpublish_on, republish_on, etc. via its hook.
            wp.apiFetch({
                path: `/wp-scheduled-posts/v1/post-panel/${postId}`,
                method: 'POST',
                data: {
                    is_scheduled: shouldSchedulePost,
                    schedule_date: shouldSchedulePost ? scheduleDate : '',
                    // Pro fields – forwarded to Pro via hook
                    unpublish_on: unpublishOn,
                    republish_on: republishOn,
                    advanced_schedule: advancedSchedule,
                    advanced_schedule_on: advancedScheduleDate,
                }
            })
        ]).then(() => {
            showCustomToast('success', 'Settings saved successfully.');
            const modal = document.getElementById('wpsp-post-panel-modal');
            if (modal) {
                modal.classList.remove('wpsp-post-panel-active');
                document.body.style.overflow = '';
            }
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
                <svg width="7" height="12" viewBox="0 0 7 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0.599609 10.5996L5.59961 5.59961L0.599609 0.599609" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
    );
};

export default Footer;
