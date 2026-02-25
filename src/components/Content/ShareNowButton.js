import React, { useMemo, useState } from 'react';
import ShareNowStatusModal, { getProfileKey } from './ShareNowStatusModal';

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

const ShareNowButton = ({ selectedProfilesByPlatform, postId }) => {
    const [isSharing, setIsSharing] = useState(false);
    const [isStatusModalOpen, setIsStatusModalOpen] = useState(false);
    const [statusMap, setStatusMap] = useState({});

    const selectedProfiles = useMemo(() => {
        return Object.entries(selectedProfilesByPlatform || {}).flatMap(([platform, profiles]) => {
            if (!Array.isArray(profiles)) return [];
            return profiles.map((profile) => ({
                ...profile,
                platform,
            }));
        });
    }, [selectedProfilesByPlatform]);

    const handleShareNow = async () => {
        if (!postId) {
            showCustomToast('error', 'Post not found.');
            return;
        }

        if (!selectedProfiles.length) {
            showCustomToast('error', 'No selected profiles found to share.');
            return;
        }

        const nonce = window.WPSchedulePostsFree?.nonce || '';
        if (!nonce) {
            showCustomToast('error', 'Security nonce not found.');
            return;
        }

        setIsSharing(true);
        setIsStatusModalOpen(true);
        try {
            const initialStatuses = {};
            selectedProfiles.forEach((profile) => {
                initialStatuses[getProfileKey(profile)] = {
                    state: 'pending',
                    message: 'Request Sending...',
                };
            });
            setStatusMap(initialStatuses);

            const tasks = selectedProfiles.map(async (profile) => {
                const profileKey = getProfileKey(profile);
                const queryParams = new URLSearchParams({
                    id: String(profile.id || ''),
                    platform: String(profile.platform || ''),
                    postid: String(postId),
                    nonce,
                });

                if (profile.pinterest_board_type) {
                    queryParams.append('pinterest_board_type', String(profile.pinterest_board_type));
                }
                if (profile.pinterest_custom_board_name) {
                    queryParams.append('pinterest_custom_board_name', String(profile.pinterest_custom_board_name));
                }
                if (profile.pinterest_custom_section_name) {
                    queryParams.append('pinterest_custom_section_name', String(profile.pinterest_custom_section_name));
                }

                try {
                    const response = await wp.apiFetch({
                        path: `/wp-scheduled-posts/v1/instant-social-share?${queryParams.toString()}`,
                        method: 'GET',
                    });

                    const isSuccess = response?.success !== false;
                    const message =
                        typeof response?.data === 'string'
                            ? response.data
                            : response?.message || (isSuccess ? 'Shared successfully.' : 'Failed to share.');

                    setStatusMap((prev) => ({
                        ...prev,
                        [profileKey]: {
                            state: isSuccess ? 'success' : 'error',
                            message,
                        },
                    }));

                    return isSuccess;
                } catch (error) {
                    setStatusMap((prev) => ({
                        ...prev,
                        [profileKey]: {
                            state: 'error',
                            message: error?.message || 'Failed to share.',
                        },
                    }));
                    return false;
                }
            });

            const results = await Promise.all(tasks);
            const successCount = results.filter(Boolean).length;
            const totalCount = results.length;

            if (successCount === 0) {
                showCustomToast('error', 'Failed to share to selected profiles.');
            } else if (successCount < totalCount) {
                showCustomToast('success', `Shared to ${successCount} of ${totalCount} selected profiles.`);
            } else {
                showCustomToast('success', 'Shared successfully to selected profiles.');
            }
        } catch (error) {
            showCustomToast('error', 'Failed to share to selected profiles.');
            // eslint-disable-next-line no-console
            console.error(error);
        } finally {
            setIsSharing(false);
        }
    };

    return (
        <>
            <button className='wpsp-share-now-btn' onClick={handleShareNow} disabled={isSharing || selectedProfiles.length === 0}>
                {isSharing ? 'Sharing...' : 'Share Now'}
            </button>
            <ShareNowStatusModal
                isOpen={isStatusModalOpen}
                onClose={() => setIsStatusModalOpen(false)}
                selectedProfiles={selectedProfiles}
                statusMap={statusMap}
            />
        </>
    );
};

export default ShareNowButton;
