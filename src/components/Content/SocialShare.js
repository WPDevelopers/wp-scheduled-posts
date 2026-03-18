import React, { useContext, useState, useEffect, useMemo } from 'react';
import { AppContext } from '../../context/AppContext';
import {
    facebook,
    twitter_x,
    linkedin,
    pinterest,
    instagram,
    medium,
    threads,
    google_business,
    authorIcon,
    facebookWithBG,
    twitterWithBG,
    linkedinWithBG,
    pinterestWithBG,
    instagramWithBG,
    mediumWithBG,
    threadsWithBG,
    googleMyBusinessWithBG,
} from '../../icons/icons';
import ShareNowButton from './ShareNowButton';

const PLATFORM_CONFIG = {
    facebook: { label: 'Facebook', icon: facebook, iconWithBG: facebookWithBG },
    twitter: { label: 'Twitter', icon: twitter_x, iconWithBG: twitterWithBG },
    linkedin: { label: 'LinkedIn', icon: linkedin, iconWithBG: linkedinWithBG },
    pinterest: { label: 'Pinterest', icon: pinterest, iconWithBG: pinterestWithBG },
    instagram: { label: 'Instagram', icon: instagram, iconWithBG: instagramWithBG },
    medium: { label: 'Medium', icon: medium, iconWithBG: mediumWithBG },
    threads: { label: 'Threads', icon: threads, iconWithBG: threadsWithBG },
    google_business: { label: 'Google Business', icon: google_business, iconWithBG: googleMyBusinessWithBG },
};

const PLATFORM_ORDER = [
    'facebook',
    'twitter',
    'linkedin',
    'pinterest',
    'instagram',
    'medium',
    'threads',
    'google_business',
];

const processProfiles = (list) => {
    if (!Array.isArray(list)) return [];

    const uniqueMap = new Map();
    list.forEach((item) => {
        if (item && item.id !== undefined && item.id !== null) {
            uniqueMap.set(String(item.id), item);
        }
    });

    return Array.from(uniqueMap.values());
};

const SocialShare = () => {
    const { state, dispatch } = useContext(AppContext);

    const { socialShareSettings } = state;
    const { isSocialShareDisabled, socialBannerUrl } = socialShareSettings;
    const [selectedProfilesByPlatform, setSelectedProfilesByPlatform] = useState({});
    const [isProfilesLoading, setIsProfilesLoading] = useState(false);
    const [isRemoveBanner, setIsRemoveBanner] = useState(false);
    // Use useSelect to reactively fetch meta
    const meta = wp.data.useSelect((select) => {
        const store = select('core/editor');
        return store ? store.getEditedPostAttribute('meta') : null;
    }, []);
    const postId = wp.data.useSelect((select) => {
        const store = select('core/editor');
        return store ? store.getCurrentPostId() : null;
    }, []);

    useEffect(() => {
        let imageId = null;
        let disabled = false;
        let bannerUrl = '';
        let isClassic = false;

        if (meta) {
            imageId = meta._wpscppro_custom_social_share_image;
            disabled = meta._wpscppro_dont_share_socialmedia;
        } else if (typeof window.WPSchedulePostsFree !== 'undefined') {
            imageId = window.WPSchedulePostsFree._wpscppro_custom_social_share_image_id;
            // Handle string/boolean mismatch from PHP localization
            disabled = window.WPSchedulePostsFree._wpscppro_dont_share_socialmedia === '1' || window.WPSchedulePostsFree._wpscppro_dont_share_socialmedia === true;
            bannerUrl = window.WPSchedulePostsFree._wpscppro_custom_social_share_image || '';
            isClassic = true;
        }

        const updateState = (url) => {
            dispatch({
                type: 'SET_SOCIAL_SHARE_SETTINGS',
                payload: {
                    isSocialShareDisabled: disabled || false,
                    socialBannerId: imageId,
                    socialBannerUrl: url,
                },
            });
        };

        if (imageId) {
            if (isClassic && bannerUrl) {
                updateState(bannerUrl);
            } else if (typeof wp.media !== 'undefined') {
                const attachment = wp.media.attachment(imageId);
                attachment.fetch().then(() => {
                    updateState(attachment.get('url'));
                }).catch(() => {
                    updateState('');
                });
            } else {
                updateState(bannerUrl);
            }
        } else {
            dispatch({
                type: 'SET_SOCIAL_SHARE_SETTINGS',
                payload: {
                    isSocialShareDisabled: disabled || false,
                    socialBannerId: null,
                    socialBannerUrl: '',
                },
            });
        }
    }, [meta, dispatch]);

    useEffect(() => {
        const resolvedPostId = postId || (window.WPSchedulePostsFree ? window.WPSchedulePostsFree.current_post_id : null);

        if (!resolvedPostId || !wp?.apiFetch) {
            setSelectedProfilesByPlatform({});
            return;
        }

        let isMounted = true;

        const fetchSelectedProfiles = async () => {
            try {
                setIsProfilesLoading(true);

                const templateResponse = await wp.apiFetch({
                    path: `/wp-scheduled-posts/v1/custom-templates/${resolvedPostId}`,
                    method: 'GET',
                });

                const templateData = templateResponse?.data || templateResponse || {};
                const mappedData = {};

                PLATFORM_ORDER.forEach((platform) => {
                    const selectedIds = Array.isArray(templateData?.[platform]?.profiles)
                        ? templateData[platform].profiles.map((id) => String(id))
                        : [];

                    if (!selectedIds.length) {
                        return;
                    }

                    mappedData[platform] = selectedIds.map((profileId) => ({
                        id: profileId,
                        name: profileId,
                        thumbnail_url: '',
                    }));
                });

                // Set from saved template data first so cards always appear.
                if (isMounted) {
                    setSelectedProfilesByPlatform(mappedData);
                }

                try {
                    const optionDataRaw = await wp.apiFetch({
                        path: '/wp-scheduled-posts/v1/get-option-data',
                        method: 'GET',
                    });

                    const optionData = typeof optionDataRaw === 'string' ? JSON.parse(optionDataRaw) : optionDataRaw;

                    const pinterestData = (optionData?.pinterest_profile_list || [])
                        .map((user) => ({
                            id: user?.default_board_name?.value,
                            name: user?.default_board_name?.label,
                            thumbnail_url: user?.thumbnail_url,
                            ...user,
                        }))
                        .filter((item) => item.id);

                    const allProfiles = {
                        facebook: processProfiles(optionData?.facebook_profile_list),
                        twitter: processProfiles(optionData?.twitter_profile_list),
                        linkedin: processProfiles(optionData?.linkedin_profile_list),
                        pinterest: processProfiles(pinterestData),
                        instagram: processProfiles(optionData?.instagram_profile_list),
                        medium: processProfiles(optionData?.medium_profile_list),
                        threads: processProfiles(optionData?.threads_profile_list),
                        google_business: processProfiles(optionData?.google_business_profile_list),
                    };

                    const enrichedData = {};

                    PLATFORM_ORDER.forEach((platform) => {
                        const selectedIds = mappedData[platform]?.map((profile) => String(profile.id)) || [];
                        if (!selectedIds.length) {
                            return;
                        }

                        const availableProfiles = allProfiles[platform] || [];
                        const availableById = new Map(
                            availableProfiles.map((profile) => [String(profile.id), profile])
                        );

                        enrichedData[platform] = selectedIds.map((profileId) => {
                            const profile = availableById.get(profileId);
                            return profile || {
                                id: profileId,
                                name: profileId,
                                thumbnail_url: '',
                            };
                        });
                    });

                    if (isMounted) {
                        setSelectedProfilesByPlatform(enrichedData);
                    }
                } catch (profilesError) {
                    // Keep already-mapped IDs if profile option data fails.
                    // eslint-disable-next-line no-console
                    console.error('Error fetching profile option data:', profilesError);
                }
            } catch (error) {
                if (isMounted) {
                    setSelectedProfilesByPlatform({});
                }
                // eslint-disable-next-line no-console
                console.error('Error fetching selected social profiles:', error);
            } finally {
                if (isMounted) {
                    setIsProfilesLoading(false);
                }
            }
        };

        fetchSelectedProfiles();

        return () => {
            isMounted = false;
        };
    }, [postId, state.isOpenCustomSocialMessageModal]);

    const selectedPlatformCards = useMemo(() => {
        return PLATFORM_ORDER
            .filter((platform) => Array.isArray(selectedProfilesByPlatform[platform]) && selectedProfilesByPlatform[platform].length > 0)
            .map((platform) => ({
                platform,
                ...PLATFORM_CONFIG[platform],
                profiles: selectedProfilesByPlatform[platform],
            }));
    }, [selectedProfilesByPlatform]);
    const hasSavedSocialMessage = selectedPlatformCards.length > 0;
    const resolvedPostId = postId || (window.WPSchedulePostsFree ? window.WPSchedulePostsFree.current_post_id : null);

    // Helper to update global state
    const updateSettings = (updates) => {
        dispatch({
            type: 'SET_SOCIAL_SHARE_SETTINGS',
            payload: updates,
        });
    };

    const openMediaUploader = () => {
        if (typeof wp === 'undefined' || !wp.media) return;

        const custom_uploader = wp.media({
            title: 'Upload Social Banner',
            button: {
                text: 'Use this media',
            },
            multiple: false,
            library: {
                type: 'image',
            },
        });

        custom_uploader.on('select', () => {
            const attachment = custom_uploader.state().get('selection').first().toJSON();
            updateSettings({
                socialBannerId: attachment.id,
                socialBannerUrl: attachment.url,
            });
        });

        custom_uploader.open();
    };

    const removeSocialBanner = () => {
        setIsRemoveBanner(false);
        updateSettings({
            socialBannerId: null,
            socialBannerUrl: '',
        });
    };

    const handleRemoveBanner = () => {
        setIsRemoveBanner(true);
    }

    const handleDisableSocialShare = (e) => {
        updateSettings({
            isSocialShareDisabled: e.target.checked,
        });
    };

    const handleCustomSocialMessage = () => {
        dispatch({ type: 'SET_CUSTOM_SOCIAL_MESSAGE_MODAL', payload: !state.isOpenCustomSocialMessageModal });
    };

    return (
        <div className="wpsp-modal-social-share">
            <h2>Social Share Settings</h2>
            <div className="wpsp-post--card">
                <div className="wpsp-disabled-social-share-checkbox">
                    <div className="wpsp-share-checkbox">
                        <input
                            type="checkbox"
                            id="socialShareDisable"
                            name="socialShareDisable"
                            checked={isSocialShareDisabled}
                            onChange={handleDisableSocialShare}
                        />
                        <label htmlFor="socialShareDisable">Disable Social Share</label>
                    </div>
                </div>
                <div className={`wpsp-custom-social-message-with-banner`}>
                    <div className="wpsp-upload-social-banner">
                        { !socialBannerUrl &&
                            <div className="wpsp-upload-social-banner-btn">
                                <button className="wpsp-upload-social-share-btn" onClick={openMediaUploader}>Upload Social Banner</button>
                                <p>*If you don't upload, featured image will be selected as banner</p>
                            </div>
                        }
                        
                        { !isRemoveBanner ? <div className="wpsp-upload-social-banner-preview">
                            <div className="wpsp-upload-social-banner-preview-inner">
                                {socialBannerUrl && <img style={{ width: '100%', height: 'auto' }} src={socialBannerUrl} alt="Social Banner" />}
                            </div>
                        </div> : <div className="wpsp-upload-social-banner-remove-confirm">
                            <h3>Remove Social Banner?</h3>
                            <p>Delete this banner image? The featured image will be used when sharing</p>
                            <div className='wpsp-remove-social-banner-btn-wrapper'>
                                <button className="wpsp-cancel-remove-banner-btn" onClick={() => setIsRemoveBanner(false)}>Cancel</button>
                                <button className="wpsp-remove-banner-btn" onClick={removeSocialBanner}>Yes, Remove</button>
                            </div>
                        </div> }
                        { (socialBannerUrl && !isRemoveBanner) && <div className="wpsp-upload-social-banner-remove">
                            <button className="wpsp-upload-social-share-btn" onClick={openMediaUploader}>Update Banner</button>
                            <button className="wpsp-remove-banner-social-share-btn" onClick={handleRemoveBanner}>Remove Banner</button>
                        </div> }
                    </div>
                    {!hasSavedSocialMessage && (
                        <div className="wpsp-add-social-message-wrapper">
                            <p>Add message for target platforms</p>
                            <button className="wpsp-add-social-message-btn" onClick={handleCustomSocialMessage}>
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                                    <path d="M7.5 0L7.743 0.000749946L7.9815 0.00375009L8.4435 0.0165001L8.66775 0.0262501L9.102 0.05175L9.51675 0.08625C13.1055 0.43425 14.5657 1.8945 14.9137 5.48325L14.9482 5.898L14.9737 6.33225C14.9775 6.40575 14.9812 6.48075 14.9835 6.5565L14.9962 7.0185L15 7.5L14.9962 7.9815L14.9835 8.4435L14.9737 8.66775L14.9482 9.102L14.9137 9.51675C14.5657 13.1055 13.1055 14.5657 9.51675 14.9137L9.102 14.9482L8.66775 14.9737C8.59425 14.9775 8.51925 14.9812 8.4435 14.9835L7.9815 14.9962L7.5 15L7.0185 14.9962L6.5565 14.9835L6.33225 14.9737L5.898 14.9482L5.48325 14.9137C1.8945 14.5657 0.43425 13.1055 0.08625 9.51675L0.05175 9.102L0.0262501 8.66775C0.0226014 8.59302 0.0193514 8.51827 0.0165001 8.4435L0.00375009 7.9815C0.00150009 7.824 0 7.6635 0 7.5L0.000749946 7.257L0.00375009 7.0185L0.0165001 6.5565L0.0262501 6.33225L0.05175 5.898L0.08625 5.48325C0.43425 1.8945 1.8945 0.43425 5.48325 0.08625L5.898 0.05175L6.33225 0.0262501C6.40575 0.0225001 6.48075 0.0187501 6.5565 0.0165001L7.0185 0.00375009C7.176 0.00150009 7.3365 0 7.5 0ZM7.5 4.5C7.30109 4.5 7.11032 4.57902 6.96967 4.71967C6.82902 4.86032 6.75 5.05109 6.75 5.25V6.75H5.25L5.16225 6.75525C4.97243 6.77783 4.79839 6.87204 4.6757 7.01863C4.55301 7.16522 4.49092 7.35312 4.50212 7.54395C4.51332 7.73478 4.59697 7.91414 4.73597 8.04536C4.87498 8.17659 5.05884 8.24979 5.25 8.25H6.75V9.75L6.75525 9.83775C6.77783 10.0276 6.87204 10.2016 7.01863 10.3243C7.16522 10.447 7.35312 10.5091 7.54395 10.4979C7.73478 10.4867 7.91414 10.403 8.04536 10.264C8.17659 10.125 8.24979 9.94116 8.25 9.75V8.25H9.75L9.83775 8.24475C10.0276 8.22217 10.2016 8.12796 10.3243 7.98137C10.447 7.83478 10.5091 7.64688 10.4979 7.45605C10.4867 7.26521 10.403 7.08586 10.264 6.95464C10.125 6.82341 9.94116 6.75021 9.75 6.75H8.25V5.25L8.24475 5.16225C8.22326 4.97981 8.13556 4.81161 7.99828 4.68954C7.861 4.56747 7.6837 4.50002 7.5 4.5Z" fill="white" />
                                </svg> Add Social Message</button>
                        </div>
                    )}
                    <div className='wpsp-social-platforms-card-wrapper'>
                        { selectedPlatformCards.length > 0 && <h4>Selected Social Platforms</h4> }
                        <div className='wpsp-social-platforms--wrapper'>
                            <div className='wpsp-social-platforms-cards'>
                                {isProfilesLoading && <p>Loading selected profiles...</p>}
                                
                                {!isProfilesLoading && selectedPlatformCards.map((card) => {
                                    const visibleProfiles = card.profiles.slice(0, 5);
                                    const extraCount = card.profiles.length - visibleProfiles.length;

                                    return (
                                        <div className='wpsp-social-card' key={card.platform}>
                                            <div className='social-platforms-card-header'>
                                                <span className={`wpsp-social-platform-icon-bubble ${card.platform}`}>{card.iconWithBG}</span>
                                                <div className="wpsp-preview-name">{card.label}</div>
                                            </div>
                                            <div className='social-platforms-card-content'>
                                                {visibleProfiles.map((profile) => (
                                                    profile.thumbnail_url ? (
                                                        <img
                                                            key={profile.id}
                                                            src={profile.thumbnail_url}
                                                            alt={profile.name || card.label}
                                                            title={profile.name || ''}
                                                            onError={(e) => {
                                                                e.target.onerror = null;
                                                                e.target.src = `data:image/svg+xml;utf8,${encodeURIComponent(authorIcon)}`;
                                                            }}
                                                        />
                                                    ) : (
                                                        <img
                                                            key={profile.id}
                                                            src={`data:image/svg+xml;utf8,${encodeURIComponent(authorIcon)}`}
                                                            alt={profile.name || card.label}
                                                            title={profile.name || ''}
                                                        />
                                                    )
                                                ))}
                                                {extraCount > 0 && <div className='count-card'>{`+${extraCount}`}</div>}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                        <div className='wpsp-social-btn--wrapper'>
                            {hasSavedSocialMessage && (
                                <button className="wpsp-upload-social-share-btn" onClick={handleCustomSocialMessage}>Edit Social Message</button>
                            )}
                            <ShareNowButton selectedProfilesByPlatform={selectedProfilesByPlatform} postId={resolvedPostId} hasSavedSocialMessage={hasSavedSocialMessage} />
                        </div>
                    </div>
                    {isSocialShareDisabled &&
                        <div className="wpsp-social-share-disabled-message">
                            <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_5703_6004)">
                                <path d="M15.9993 2.66797C23.3633 2.66797 29.3327 8.6373 29.3327 16.0013C29.3355 19.5019 27.9614 22.8632 25.5072 25.3595C23.053 27.8558 19.7156 29.2867 16.2154 29.3434C12.7152 29.4002 9.33313 28.0781 6.79935 25.6626C4.26556 23.2472 2.78333 19.9322 2.67268 16.4333L2.66602 16.0013L2.67135 15.628C2.86868 8.4373 8.75935 2.66797 15.9993 2.66797ZM16.0127 20.0013L15.8433 20.0106C15.5193 20.0492 15.2206 20.2052 15.0039 20.4493C14.7872 20.6933 14.6675 21.0083 14.6675 21.3346C14.6675 21.661 14.7872 21.976 15.0039 22.22C15.2206 22.464 15.5193 22.6201 15.8433 22.6586L15.9993 22.668L16.1687 22.6586C16.4927 22.6201 16.7914 22.464 17.0081 22.22C17.2248 21.976 17.3445 21.661 17.3445 21.3346C17.3445 21.0083 17.2248 20.6933 17.0081 20.4493C16.7914 20.2052 16.4927 20.0492 16.1687 20.0106L16.0127 20.0013ZM15.9993 9.33464C15.6728 9.33468 15.3576 9.45458 15.1135 9.67159C14.8695 9.8886 14.7136 10.1876 14.6753 10.512L14.666 10.668V16.0013L14.6753 16.1573C14.7139 16.4814 14.87 16.78 15.114 16.9968C15.358 17.2135 15.673 17.3332 15.9993 17.3332C16.3257 17.3332 16.6407 17.2135 16.8847 16.9968C17.1287 16.78 17.2848 16.4814 17.3233 16.1573L17.3327 16.0013V10.668L17.3233 10.512C17.2851 10.1876 17.1292 9.8886 16.8852 9.67159C16.6411 9.45458 16.3259 9.33468 15.9993 9.33464Z" fill="#EF963C"/>
                                </g>
                                <defs>
                                <clipPath id="clip0_5703_6004">
                                <rect width="32" height="32" fill="white"/>
                                </clipPath>
                                </defs>
                            </svg>
                            <h3>Social sharing is disabled</h3>
                            <p>Enable it to manage platforms.</p>
                        </div>
                    }
                </div>
            </div>
        </div>
    );
};

export default SocialShare;
