import React, { useState } from 'react';
import { updateProSettings, clearPublishImmediately } from '../../helper/helper';
const { __ } = wp.i18n;

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

// Close the SchedulePress panel modal (markup defined in includes/Admin/Metabox/index.php).
// Mirrors the closeModal() there: drop the active class and restore body scroll.
const closeSchedulePressModal = () => {
    if (typeof document === 'undefined') return;
    const modal = document.getElementById('wpsp-post-panel-modal');
    if (modal) modal.classList.remove('wpsp-post-panel-active');
    document.body.style.overflow = '';
};

const PublishImmediately = ({ state, dispatch, postId, publishImmediatelyBtn, publishFutureDateBtn }) => {
    const [isPublishingCurrentDate, setIsPublishingCurrentDate] = useState(false);
    const [isPublishingFutureDate, setIsPublishingFutureDate] = useState(false);

    const handlePublishImmediately = () => {
        setIsPublishingCurrentDate(true);
        updateProSettings(postId, {
            publish_immediately_current_date: true
        }).then(() => {
            showCustomToast('success', 'Post published using Current Date.');
            closeSchedulePressModal();
        }).catch((error) => {
            showCustomToast('error', 'Failed to publish using Current Date.');
            console.log(error);
        }).finally(() => {
            setIsPublishingCurrentDate(false);
        });
    };

    const handlePublishFutureDate = () => {
        setIsPublishingFutureDate(true);
        updateProSettings(postId, {
            publish_immediately_future_date: true
        }).then(() => {
            showCustomToast('success', 'Post published using Future Date.');
            closeSchedulePressModal();
        }).catch((error) => {
            showCustomToast('error', 'Failed to publish using Future Date.');
            console.log(error);
        }).finally(() => {
            setIsPublishingFutureDate(false);
        });
    };

    return (
        <div className="sc-publish-future">
            <div className="select--wrapper">
                <label>
                    <input
                        type="checkbox"
                        checked={ state.publishImmediately || false }
                        onChange={ (e) => {
                            dispatch({ type: 'SET_PUBLISH_IMMEDIATELY', payload: e.target.checked });
                        }}
                    />
                    <span>{ __("Publish future post immediately",'wp-scheduled-posts') }</span>
                </label>
            </div>
            { state.publishImmediately && (
                <div className="sc-publish-future-buttons">
                    <button className="button button-primary" onClick={ handlePublishImmediately } disabled={ isPublishingCurrentDate || isPublishingFutureDate }>
                        { isPublishingCurrentDate ? __('Publishing...','wp-scheduled-posts') : publishImmediatelyBtn }
                    </button>
                    <button className="button button-primary" onClick={ handlePublishFutureDate } disabled={ isPublishingCurrentDate || isPublishingFutureDate }>
                        { isPublishingFutureDate ? __('Publishing...','wp-scheduled-posts') : publishFutureDateBtn }
                    </button>
                </div>
            )}
        </div>
    );
};

/**
 * Shown when a post is already carrying the "publish future post immediately"
 * intent.
 *
 * That intent lives in the prevent_future_post meta and keeps re-asserting
 * 'publish' on every later save. The buttons above set it, but they are hidden
 * once the post reaches 'publish', so without this block the state is active
 * and invisible with no way to switch it off.
 */
export const PublishImmediatelyActive = ({ postId, onCleared }) => {
    const [isClearing, setIsClearing] = useState(false);

    const handleClear = () => {
        setIsClearing(true);
        clearPublishImmediately(postId).then((res) => {
            showCustomToast('success', res?.message || 'Immediate publishing turned off.');
            if (typeof onCleared === 'function') onCleared(res);
        }).catch((error) => {
            showCustomToast('error', error?.message || 'Failed to turn off immediate publishing.');
            console.log(error);
        }).finally(() => {
            setIsClearing(false);
        });
    };

    return (
        <div className="sc-publish-future sc-publish-future-active">
            <p className="sc-publish-future-notice">
                { __('This post was published ahead of its scheduled date. It stays published on every save until you turn this off.', 'wp-scheduled-posts') }
            </p>
            <div className="sc-publish-future-buttons">
                <button
                    className="button"
                    onClick={ handleClear }
                    disabled={ isClearing }
                >
                    { isClearing
                        ? __('Turning off...', 'wp-scheduled-posts')
                        : __('Turn off & restore schedule', 'wp-scheduled-posts') }
                </button>
            </div>
        </div>
    );
};

export default PublishImmediately;
