import React, { useContext, useState, useEffect } from 'react';
import { AppContext } from '../../context/AppContext';
const SocialShare = () => {
  const { state, dispatch } = useContext(AppContext);

    const { socialShareSettings } = state;
    const { isSocialShareDisabled, socialBannerId, socialBannerUrl } = socialShareSettings;

    // Use useSelect to reactively fetch meta
    const meta = wp.data.useSelect((select) => {
        const store = select('core/editor');
        return store ? store.getEditedPostAttribute('meta') : null;
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
                    socialBannerUrl: url
               }
           });
        };
        
        if (imageId) {
            // If Classic and we already have URL, verify it or just use it. 
            // Better to fetch if possible to be consistent, but URL from PHP is faster init.
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
                    socialBannerUrl: ''
               }
           });
        }
    }, [meta, dispatch]);

    // Helper to update global state
    const updateSettings = (updates) => {
        dispatch({
            type: 'SET_SOCIAL_SHARE_SETTINGS',
            payload: updates
        });
    };

    const openMediaUploader = () => {
        if (typeof wp === 'undefined' || !wp.media) return;

        const custom_uploader = wp.media({
            title: 'Upload Social Banner',
            button: {
                text: 'Use this media'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });

        custom_uploader.on('select', () => {
            const attachment = custom_uploader.state().get('selection').first().toJSON();
            updateSettings({
                socialBannerId: attachment.id,
                socialBannerUrl: attachment.url
            });
        });

        custom_uploader.open();
    };

    const removeSocialBanner = () => {
        updateSettings({
            socialBannerId: null,
            socialBannerUrl: ''
        });
    };

    const handleDisableSocialShare = (e) => {
        updateSettings({
            isSocialShareDisabled: e.target.checked
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
                <div className="wpsp-upload-social-banner">
                    <div className="wpsp-upload-social-banner-btn">
                        <button className="wpsp-upload-social-share-btn" onClick={openMediaUploader}>Upload Social Banner</button>
                        <p>*If you don't upload, featured image will be selected as banner</p>
                    </div>
                    <div className="wpsp-upload-social-banner-preview">
                        <div className="wpsp-upload-social-banner-preview-inner">
                            {socialBannerUrl && <img style={{ width: '100%', height: 'auto' }} src={socialBannerUrl} alt="Social Banner" />}
                        </div>
                    </div>
                    <div className="wpsp-upload-social-banner-remove">
                        {socialBannerUrl && <button className="wpsp-upload-social-share-btn" onClick={removeSocialBanner}>Remove Banner</button>}
                    </div>
                </div>
                <div className="wpsp-add-social-message-wrapper">
                    <p>Add message for target platforms</p>
                    <button className="wpsp-add-social-message-btn" onClick={handleCustomSocialMessage}>
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                            <path d="M7.5 0L7.743 0.000749946L7.9815 0.00375009L8.4435 0.0165001L8.66775 0.0262501L9.102 0.05175L9.51675 0.08625C13.1055 0.43425 14.5657 1.8945 14.9137 5.48325L14.9482 5.898L14.9737 6.33225C14.9775 6.40575 14.9812 6.48075 14.9835 6.5565L14.9962 7.0185L15 7.5L14.9962 7.9815L14.9835 8.4435L14.9737 8.66775L14.9482 9.102L14.9137 9.51675C14.5657 13.1055 13.1055 14.5657 9.51675 14.9137L9.102 14.9482L8.66775 14.9737C8.59425 14.9775 8.51925 14.9812 8.4435 14.9835L7.9815 14.9962L7.5 15L7.0185 14.9962L6.5565 14.9835L6.33225 14.9737L5.898 14.9482L5.48325 14.9137C1.8945 14.5657 0.43425 13.1055 0.08625 9.51675L0.05175 9.102L0.0262501 8.66775C0.0226014 8.59302 0.0193514 8.51827 0.0165001 8.4435L0.00375009 7.9815C0.00150009 7.824 0 7.6635 0 7.5L0.000749946 7.257L0.00375009 7.0185L0.0165001 6.5565L0.0262501 6.33225L0.05175 5.898L0.08625 5.48325C0.43425 1.8945 1.8945 0.43425 5.48325 0.08625L5.898 0.05175L6.33225 0.0262501C6.40575 0.0225001 6.48075 0.0187501 6.5565 0.0165001L7.0185 0.00375009C7.176 0.00150009 7.3365 0 7.5 0ZM7.5 4.5C7.30109 4.5 7.11032 4.57902 6.96967 4.71967C6.82902 4.86032 6.75 5.05109 6.75 5.25V6.75H5.25L5.16225 6.75525C4.97243 6.77783 4.79839 6.87204 4.6757 7.01863C4.55301 7.16522 4.49092 7.35312 4.50212 7.54395C4.51332 7.73478 4.59697 7.91414 4.73597 8.04536C4.87498 8.17659 5.05884 8.24979 5.25 8.25H6.75V9.75L6.75525 9.83775C6.77783 10.0276 6.87204 10.2016 7.01863 10.3243C7.16522 10.447 7.35312 10.5091 7.54395 10.4979C7.73478 10.4867 7.91414 10.403 8.04536 10.264C8.17659 10.125 8.24979 9.94116 8.25 9.75V8.25H9.75L9.83775 8.24475C10.0276 8.22217 10.2016 8.12796 10.3243 7.98137C10.447 7.83478 10.5091 7.64688 10.4979 7.45605C10.4867 7.26521 10.403 7.08586 10.264 6.95464C10.125 6.82341 9.94116 6.75021 9.75 6.75H8.25V5.25L8.24475 5.16225C8.22326 4.97981 8.13556 4.81161 7.99828 4.68954C7.861 4.56747 7.6837 4.50002 7.5 4.5Z" fill="white" />
                        </svg> Add Social Message</button>
                </div>
                <div className='wpsp-social-platforms-card-wrapper'>
                    <h4>Selected Social Platforms</h4>
                    <div className='wpsp-social-platforms-cards'>
                        <div className='wpsp-social-card'>
                            <div className='social-platforms-card-header'>
                                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_4764_4971)">
                                    <path d="M18.002 9C18.002 13.4923 14.7106 17.2157 10.4082 17.8907V11.6016H12.5053L12.9043 9H10.4082V7.3118C10.4082 6.59988 10.757 5.90625 11.8749 5.90625H13.0098V3.69141C13.0098 3.69141 11.9797 3.51562 10.995 3.51562C8.93937 3.51562 7.5957 4.76156 7.5957 7.01719V9H5.31055V11.6016H7.5957V17.8907C3.29328 17.2157 0.00195312 13.4923 0.00195312 9C0.00195312 4.02961 4.03156 0 9.00195 0C13.9723 0 18.002 4.02961 18.002 9Z" fill="#1877F2"/>
                                    <path d="M12.5053 11.6016L12.9043 9H10.4082V7.31176C10.4082 6.60002 10.7569 5.90625 11.8749 5.90625H13.0098V3.69141C13.0098 3.69141 11.9798 3.51562 10.9951 3.51562C8.93934 3.51562 7.5957 4.76156 7.5957 7.01719V9H5.31055V11.6016H7.5957V17.8906C8.05393 17.9625 8.52355 18 9.00195 18C9.48036 18 9.94998 17.9625 10.4082 17.8906V11.6016H12.5053Z" fill="white"/>
                                    </g>
                                    <defs>
                                    <clipPath id="clip0_4764_4971">
                                    <rect width="18" height="18" fill="white"/>
                                    </clipPath>
                                    </defs>
                                </svg>
                                <div class="wpsp-platforms-card-name">Facebook</div>
                            </div>
                            <div className='social-platforms-card-content'>
                                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_4764_4971)">
                                    <path d="M18.002 9C18.002 13.4923 14.7106 17.2157 10.4082 17.8907V11.6016H12.5053L12.9043 9H10.4082V7.3118C10.4082 6.59988 10.757 5.90625 11.8749 5.90625H13.0098V3.69141C13.0098 3.69141 11.9797 3.51562 10.995 3.51562C8.93937 3.51562 7.5957 4.76156 7.5957 7.01719V9H5.31055V11.6016H7.5957V17.8907C3.29328 17.2157 0.00195312 13.4923 0.00195312 9C0.00195312 4.02961 4.03156 0 9.00195 0C13.9723 0 18.002 4.02961 18.002 9Z" fill="#1877F2"/>
                                    <path d="M12.5053 11.6016L12.9043 9H10.4082V7.31176C10.4082 6.60002 10.7569 5.90625 11.8749 5.90625H13.0098V3.69141C13.0098 3.69141 11.9798 3.51562 10.9951 3.51562C8.93934 3.51562 7.5957 4.76156 7.5957 7.01719V9H5.31055V11.6016H7.5957V17.8906C8.05393 17.9625 8.52355 18 9.00195 18C9.48036 18 9.94998 17.9625 10.4082 17.8906V11.6016H12.5053Z" fill="white"/>
                                    </g>
                                    <defs>
                                    <clipPath id="clip0_4764_4971">
                                    <rect width="18" height="18" fill="white"/>
                                    </clipPath>
                                    </defs>
                                </svg>
                                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_4764_4971)">
                                    <path d="M18.002 9C18.002 13.4923 14.7106 17.2157 10.4082 17.8907V11.6016H12.5053L12.9043 9H10.4082V7.3118C10.4082 6.59988 10.757 5.90625 11.8749 5.90625H13.0098V3.69141C13.0098 3.69141 11.9797 3.51562 10.995 3.51562C8.93937 3.51562 7.5957 4.76156 7.5957 7.01719V9H5.31055V11.6016H7.5957V17.8907C3.29328 17.2157 0.00195312 13.4923 0.00195312 9C0.00195312 4.02961 4.03156 0 9.00195 0C13.9723 0 18.002 4.02961 18.002 9Z" fill="#1877F2"/>
                                    <path d="M12.5053 11.6016L12.9043 9H10.4082V7.31176C10.4082 6.60002 10.7569 5.90625 11.8749 5.90625H13.0098V3.69141C13.0098 3.69141 11.9798 3.51562 10.9951 3.51562C8.93934 3.51562 7.5957 4.76156 7.5957 7.01719V9H5.31055V11.6016H7.5957V17.8906C8.05393 17.9625 8.52355 18 9.00195 18C9.48036 18 9.94998 17.9625 10.4082 17.8906V11.6016H12.5053Z" fill="white"/>
                                    </g>
                                    <defs>
                                    <clipPath id="clip0_4764_4971">
                                    <rect width="18" height="18" fill="white"/>
                                    </clipPath>
                                    </defs>
                                </svg>
                                <img src='' alt='' />
                                <img src='' alt='' />
                                <img src='' alt='' />
                                <div className='count-card'>20+</div>
                            </div>
                        </div>
                        <div className='wpsp-social-card'>
                            <div className='social-platforms-card-header'>
                                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M9 0C4.02961 0 0 4.02961 0 9C0 13.9704 4.02961 18 9 18C13.9704 18 18 13.9704 18 9C18 4.02961 13.9704 0 9 0Z" fill="black"/>
                                    <path d="M9.98546 8.18558L13.8383 3.70703H12.9253L9.57988 7.59571L6.90794 3.70703H3.82617L7.86667 9.58738L3.82617 14.2838H4.73921L8.27203 10.1773L11.0938 14.2838H14.1756L9.98522 8.18558H9.98546ZM5.06819 4.39436H6.47056L12.9257 13.6278H11.5233L5.06819 4.39436Z" fill="white"/>
                                </svg>
                                <div class="wpsp-platforms-card-name">Twitter</div>
                            </div>
                            <div className='social-platforms-card-content'>
                                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_4764_4971)">
                                    <path d="M18.002 9C18.002 13.4923 14.7106 17.2157 10.4082 17.8907V11.6016H12.5053L12.9043 9H10.4082V7.3118C10.4082 6.59988 10.757 5.90625 11.8749 5.90625H13.0098V3.69141C13.0098 3.69141 11.9797 3.51562 10.995 3.51562C8.93937 3.51562 7.5957 4.76156 7.5957 7.01719V9H5.31055V11.6016H7.5957V17.8907C3.29328 17.2157 0.00195312 13.4923 0.00195312 9C0.00195312 4.02961 4.03156 0 9.00195 0C13.9723 0 18.002 4.02961 18.002 9Z" fill="#1877F2"/>
                                    <path d="M12.5053 11.6016L12.9043 9H10.4082V7.31176C10.4082 6.60002 10.7569 5.90625 11.8749 5.90625H13.0098V3.69141C13.0098 3.69141 11.9798 3.51562 10.9951 3.51562C8.93934 3.51562 7.5957 4.76156 7.5957 7.01719V9H5.31055V11.6016H7.5957V17.8906C8.05393 17.9625 8.52355 18 9.00195 18C9.48036 18 9.94998 17.9625 10.4082 17.8906V11.6016H12.5053Z" fill="white"/>
                                    </g>
                                    <defs>
                                    <clipPath id="clip0_4764_4971">
                                    <rect width="18" height="18" fill="white"/>
                                    </clipPath>
                                    </defs>
                                </svg>
                                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <g clip-path="url(#clip0_4764_4971)">
                                    <path d="M18.002 9C18.002 13.4923 14.7106 17.2157 10.4082 17.8907V11.6016H12.5053L12.9043 9H10.4082V7.3118C10.4082 6.59988 10.757 5.90625 11.8749 5.90625H13.0098V3.69141C13.0098 3.69141 11.9797 3.51562 10.995 3.51562C8.93937 3.51562 7.5957 4.76156 7.5957 7.01719V9H5.31055V11.6016H7.5957V17.8907C3.29328 17.2157 0.00195312 13.4923 0.00195312 9C0.00195312 4.02961 4.03156 0 9.00195 0C13.9723 0 18.002 4.02961 18.002 9Z" fill="#1877F2"/>
                                    <path d="M12.5053 11.6016L12.9043 9H10.4082V7.31176C10.4082 6.60002 10.7569 5.90625 11.8749 5.90625H13.0098V3.69141C13.0098 3.69141 11.9798 3.51562 10.9951 3.51562C8.93934 3.51562 7.5957 4.76156 7.5957 7.01719V9H5.31055V11.6016H7.5957V17.8906C8.05393 17.9625 8.52355 18 9.00195 18C9.48036 18 9.94998 17.9625 10.4082 17.8906V11.6016H12.5053Z" fill="white"/>
                                    </g>
                                    <defs>
                                    <clipPath id="clip0_4764_4971">
                                    <rect width="18" height="18" fill="white"/>
                                    </clipPath>
                                    </defs>
                                </svg>
                                <img src='' alt='' />
                                <img src='' alt='' />
                                <img src='' alt='' />
                                <div className='count-card'>20+</div>
                            </div>
                        </div>
                    </div>
                    <button class="wpsp-upload-social-share-btn">Edit Social Message</button>
                </div>
            </div>
        </div>
    );
};

export default SocialShare;
