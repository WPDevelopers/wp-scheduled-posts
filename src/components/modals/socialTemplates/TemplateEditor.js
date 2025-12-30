import React, { memo } from 'react';
const { __ } = wp.i18n;
import { eyeIcon, eyeCloseIcon } from '../../../../assets/gutenberg/utils/helpers/icons';

const TemplateEditor = ({
    template,
    onChange,
    characterCount,
    currentLimit,
    isOverLimit,
    showPreview,
    onTogglePreview,
    isGlobal,
    onToggleGlobal,
    globalProfileParams,
    availableProfilesCount
}) => {
    const { globalProfile, selectedPlatform, showGlobalTemplateWarning, isGlobalForCurrentPlatform } = globalProfileParams;
    const isDisabled = globalProfile != null && globalProfile !== selectedPlatform;

    return (
        <div className="wpsp-template-textarea">
            <div className='wpsp-textarea-wrapper'>
                <textarea
                    value={template || ''}
                    onChange={(e) => onChange(e.target.value)}
                    placeholder={__('Enter your custom template here...', 'wp-scheduled-posts')}
                    className="wpsp-template-input"
                    rows={4}
                    disabled={isDisabled}
                />
            </div>
            <div className="wpsp-template-meta">
                <span className="wpsp-placeholders">
                    {__('Available:', 'wp-scheduled-posts')} {'{title}'} {'{content}'} {'{url}'} {'{tags}'}
                </span>
                <div className="wpsp-custom-template-field-info">
                    <span className={`${showPreview ? 'active' : 'inactive'}`} onClick={onTogglePreview}>
                        {showPreview ? eyeCloseIcon : eyeIcon}
                    </span>
                    <span className={`wpsp-char-count ${isOverLimit ? 'over-limit' : ''}`}>
                        {characterCount}/{currentLimit}
                    </span>
                </div>
            </div>

            <div className={`wpsp-global-template ${!showPreview ? 'hide-preview' : ''}`}>
                <span className={availableProfilesCount === 0 ? 'wpsp-use-global-template-text disabled' : ''} style={{ display: 'flex', alignItems: 'center', gap: '6px' }}>
                    {__('Use global template', 'wp-scheduled-posts')}
                    <span className="wpsp-tooltip-wrapper">
                        <i className="dashicons dashicons-info" style={{ color: '#ccc' }}></i>
                        <span className="wpsp-tooltip-text">
                            {__('If enabled, this template will be applied across all the selected social platforms.', 'wp-scheduled-posts')}
                        </span>
                    </span>
                </span>
                {showGlobalTemplateWarning && (
                    <div className='use-global-template-warning'>
                        <span>{__(`${globalProfile?.charAt(0).toUpperCase() + globalProfile.slice(1)} is enabled as global template`, 'wp-scheduled-posts')}</span>
                    </div>
                )}
                <div className={`wpsp-use-global-template-checkbox-wrapper ${(availableProfilesCount === 0 || isDisabled) ? 'disabled' : ''}`}>
                    <input
                        type="checkbox"
                        id={`useGlobalTemplate_${selectedPlatform}`}
                        checked={isGlobalForCurrentPlatform}
                        disabled={availableProfilesCount === 0}
                        onChange={onToggleGlobal}
                    />
                    <label htmlFor={`useGlobalTemplate_${selectedPlatform}`}></label>
                </div>
            </div>
        </div>
    );
};

export default memo(TemplateEditor);
