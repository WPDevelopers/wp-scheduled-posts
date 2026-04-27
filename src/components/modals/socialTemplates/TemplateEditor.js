import React, { memo } from 'react';
const { __ } = wp.i18n;
import { eyeIcon, eyeCloseIcon } from '../../../icons/icons';

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
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="6.99935" cy="6.99935" r="5.83333" stroke="#667085" stroke-width="1.2"></circle><path d="M7 4.08398V7.58398" stroke="#667085" stroke-width="1.2" stroke-linecap="round"></path><circle cx="6.99935" cy="9.33333" r="0.583333" fill="#667085"></circle></svg>
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
