import React, { memo } from 'react';
const { __ } = wp.i18n;
import { authorIcon } from '../../../../assets/gutenberg/utils/helpers/icons';

const PreviewCard = ({
    platform,
    profile,
    templateHtml,
    postData,
    info
}) => {
    const { title, content, url, bannerImage } = postData;
    const previewProfileName = profile?.name || 'Preview Name';
    const previewThumbnailUrl = profile?.thumbnail_url || '';

    if (!profile) {
        return (
            <div className={`wpsp-modal-right ${platform}`}>
                <div className="wpsp-preview-card">
                    <div className="wpsp-preview-not-available">
                        {/* {info} */}
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g clip-path="url(#clip0_4194_9684)">
                            <path d="M20.0007 3.33398C29.2057 3.33398 36.6673 10.7957 36.6673 20.0007C36.6708 24.3764 34.9532 28.5781 31.8855 31.6984C28.8177 34.8187 24.6459 36.6074 20.2707 36.6783C15.8955 36.7492 11.6679 35.0966 8.50065 32.0773C5.33341 29.058 3.48063 24.9143 3.34232 20.5407L3.33398 20.0007L3.34065 19.534C3.58732 10.5457 10.9507 3.33398 20.0007 3.33398ZM20.0173 25.0007L19.8057 25.0123C19.4006 25.0605 19.0272 25.2556 18.7563 25.5606C18.4855 25.8656 18.3358 26.2594 18.3358 26.6673C18.3358 27.0753 18.4855 27.469 18.7563 27.774C19.0272 28.0791 19.4006 28.2741 19.8057 28.3223L20.0007 28.334L20.2123 28.3223C20.6174 28.2741 20.9907 28.0791 21.2616 27.774C21.5325 27.469 21.6821 27.0753 21.6821 26.6673C21.6821 26.2594 21.5325 25.8656 21.2616 25.5606C20.9907 25.2556 20.6174 25.0605 20.2123 25.0123L20.0173 25.0007ZM20.0007 11.6673C19.5924 11.6674 19.1984 11.8172 18.8934 12.0885C18.5883 12.3598 18.3934 12.7336 18.3457 13.139L18.334 13.334V20.0007L18.3457 20.1957C18.3938 20.6007 18.5889 20.9741 18.8939 21.245C19.1989 21.5158 19.5927 21.6655 20.0007 21.6655C20.4086 21.6655 20.8024 21.5158 21.1074 21.245C21.4124 20.9741 21.6075 20.6007 21.6557 20.1957L21.6673 20.0007V13.334L21.6557 13.139C21.6079 12.7336 21.413 12.3598 21.1079 12.0885C20.8029 11.8172 20.4089 11.6674 20.0007 11.6673Z" fill="#6C62FF"/>
                            </g>
                            <defs>
                            <clipPath id="clip0_4194_9684">
                            <rect width="40" height="40" fill="white"/>
                            </clipPath>
                            </defs>
                        </svg>
                        <h3>{__('Preview not available', 'wp-scheduled-posts')}</h3>
                        <p>{__('Please make sure you select a social profile first.', 'wp-scheduled-posts')}</p>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className={`wpsp-modal-right ${platform}`}>
            <div className="wpsp-preview-card">
                <div className="wpsp-preview-header">
                    <div className="wpsp-preview-avatar">
                        <div className="wpsp-avatar-circle">
                            <img
                                src={previewThumbnailUrl}
                                alt={previewProfileName}
                                className="wpsp-profile-image"
                                onError={(e) => {
                                    e.target.onerror = null;
                                    e.target.src = `data:image/svg+xml;utf8,${encodeURIComponent(authorIcon)}`;
                                }}
                            />
                        </div>
                        <div className="wpsp-preview-info">
                            <div className="wpsp-preview-name">{previewProfileName}</div>
                            <div className="wpsp-preview-date">
                                {new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="wpsp-preview-content-area">
                    <div
                        className="wpsp-preview-text"
                        dangerouslySetInnerHTML={{ __html: templateHtml }}
                    ></div>

                    <div className="wpsp-preview-post">
                        <div className="wpsp-preview-image">
                            {bannerImage ? (
                                <img
                                    src={bannerImage}
                                    alt="Preview"
                                    style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                                />
                            ) : (
                                <div
                                    style={{
                                        width: '100%',
                                        height: '100%',
                                        background: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        color: 'white',
                                        fontSize: '14px',
                                    }}
                                >
                                    {__('No image selected', 'wp-scheduled-posts')}
                                </div>
                            )}
                        </div>

                        <div className="wpsp-preview-post-content">
                            <div className="wpsp-preview-url">{window.location.origin}</div>
                            <div className="wpsp-preview-title">{title}</div>
                            <div
                                className="wpsp-preview-excerpt"
                                dangerouslySetInnerHTML={{ __html: content }}
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default memo(PreviewCard);
