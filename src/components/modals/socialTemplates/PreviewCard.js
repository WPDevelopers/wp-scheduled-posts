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
                        {info}
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
