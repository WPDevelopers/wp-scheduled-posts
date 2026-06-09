import React, { useEffect, useMemo, useRef, useState } from 'react';
import { aiCaption } from '../../../icons/icons';
const { __ } = wp.i18n;

// Human readable labels for the platform checkboxes shown in the drawer.
const PLATFORM_LABELS = {
  facebook: 'Facebook',
  twitter: 'X',
  linkedin: 'LinkedIn',
  pinterest: 'Pinterest',
  instagram: 'Instagram',
  medium: 'Medium',
  threads: 'Threads',
  google_business: 'Google Business',
};

const TONE_OPTIONS = [
  { value: 'professional', label: __('Professional', 'wp-scheduled-posts') },
  { value: 'casual', label: __('Casual', 'wp-scheduled-posts') },
  { value: 'friendly', label: __('Friendly', 'wp-scheduled-posts') },
  { value: 'witty', label: __('Witty', 'wp-scheduled-posts') },
  { value: 'bold', label: __('Bold', 'wp-scheduled-posts') },
  { value: 'informative', label: __('Informative', 'wp-scheduled-posts') },
];

const LENGTH_OPTIONS = [
  { value: 'auto', label: __('Auto (Recommended)', 'wp-scheduled-posts') },
  { value: 'short', label: __('Short', 'wp-scheduled-posts') },
  { value: 'medium', label: __('Medium', 'wp-scheduled-posts') },
  { value: 'long', label: __('Long', 'wp-scheduled-posts') },
];

// Sparkle icon used inside the gradient "Generate Captions" button (rendered white).
const sparkleWhite = (
  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M7.5 4.5 Q7.5 10.5 13.5 10.5 Q7.5 10.5 7.5 16.5 Q7.5 10.5 1.5 10.5 Q7.5 10.5 7.5 4.5 Z" fill="#fff" />
    <path d="M14 1.2 Q14 4 16.8 4 Q14 4 14 6.8 Q14 4 11.2 4 Q14 4 14 1.2 Z" fill="#fff" />
  </svg>
);

// Small magic-wand icon for the "Auto-generate based on post content" hint.
const wandIcon = (
  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path d="m6 21 15-15-3-3L3 18l3 3Z" stroke="#6E6E8D" strokeWidth="1.5" strokeLinejoin="round" />
    <path d="M15 6l3 3M12 3v2M19 9h2M5 5h2M3 13h2" stroke="#6E6E8D" strokeWidth="1.5" strokeLinecap="round" />
  </svg>
);

const AICaptionDrawer = ({
  isOpen,
  onClose,
  platforms = [],
  social_media_enabled = {},
  selectedPlatform,
  onGenerate,
}) => {
  // Platforms the user has connected/enabled, in the order defined by `platforms`.
  const enabledPlatforms = useMemo(
    () => platforms.map((p) => p.platform).filter((key) => social_media_enabled[key]),
    [platforms, social_media_enabled]
  );

  const [selectedPlatforms, setSelectedPlatforms] = useState([]);
  const [prompt, setPrompt] = useState('');
  const [autoGenerate, setAutoGenerate] = useState(true);
  const [tone, setTone] = useState('professional');
  const [length, setLength] = useState('auto');
  const [generateHashtags, setGenerateHashtags] = useState(true);
  const [includeEmojis, setIncludeEmojis] = useState(false);
  const [isMoreOpen, setIsMoreOpen] = useState(false);
  const [isGenerating, setIsGenerating] = useState(false);
  const [error, setError] = useState('');
  const moreRef = useRef(null);

  // Pre-select the currently active platform (or all enabled ones) each time the drawer opens.
  useEffect(() => {
    if (isOpen) {
      const initial = selectedPlatform && social_media_enabled[selectedPlatform]
        ? [selectedPlatform]
        : enabledPlatforms;
      setSelectedPlatforms(initial);
      setError('');
    } else {
      setIsMoreOpen(false);
    }
  }, [isOpen, selectedPlatform, enabledPlatforms, social_media_enabled]);

  // Close on Escape — collapse the More+ popover first, then the drawer.
  useEffect(() => {
    if (!isOpen) return;
    const handleKey = (e) => {
      if (e.key !== 'Escape') return;
      if (isMoreOpen) {
        setIsMoreOpen(false);
      } else {
        onClose();
      }
    };
    document.addEventListener('keydown', handleKey);
    return () => document.removeEventListener('keydown', handleKey);
  }, [isOpen, isMoreOpen, onClose]);

  // Close the More+ popover when clicking outside of it.
  useEffect(() => {
    if (!isMoreOpen) return;
    const handleClick = (e) => {
      if (moreRef.current && !moreRef.current.contains(e.target)) {
        setIsMoreOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClick);
    return () => document.removeEventListener('mousedown', handleClick);
  }, [isMoreOpen]);

  const togglePlatform = (key) => {
    setSelectedPlatforms((prev) =>
      prev.includes(key) ? prev.filter((p) => p !== key) : [...prev, key]
    );
  };

  const inlinePlatforms = enabledPlatforms.slice(0, 4);
  const morePlatforms = enabledPlatforms.slice(4);
  const hasMorePlatforms = morePlatforms.length > 0;

  const handleGenerate = async () => {
    if (isGenerating) return;
    const payload = {
      platforms: selectedPlatforms,
      prompt,
      autoGenerate,
      tone,
      length,
      generateHashtags,
      includeEmojis,
    };
    try {
      setError('');
      setIsGenerating(true);
      if (typeof onGenerate === 'function') {
        await onGenerate(payload);
      }
    } catch (err) {
      console.error('AI caption generation failed:', err);
      setError(
        err?.message ||
          __('Caption generation failed. Please try again.', 'wp-scheduled-posts')
      );
    } finally {
      setIsGenerating(false);
    }
  };

  return (
    <>
      <div
        className={`wpsp-ai-drawer-overlay ${isOpen ? 'is-open' : ''}`}
        onClick={onClose}
        aria-hidden="true"
      />
      <aside
        className={`wpsp-ai-drawer ${isOpen ? 'is-open' : ''}`}
        role="dialog"
        aria-modal="true"
        aria-label={__('Write Caption with AI', 'wp-scheduled-posts')}
      >
        {/* Header */}
        <div className="wpsp-ai-drawer__header">
          <span className="wpsp-ai-drawer__glow" aria-hidden="true" />
          <span className="wpsp-ai-drawer__header-icon">{aiCaption}</span>
          <h2 className="wpsp-ai-drawer__title">{__('Write Caption with AI', 'wp-scheduled-posts')}</h2>
          <button
            type="button"
            className="wpsp-ai-drawer__close"
            onClick={onClose}
            aria-label={__('Close', 'wp-scheduled-posts')}
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width={20} height={20} aria-hidden="true">
              <path d="m13.06 12 6.47-6.47-1.06-1.06L12 10.94 5.53 4.47 4.47 5.53 10.94 12l-6.47 6.47 1.06 1.06L12 13.06l6.47 6.47 1.06-1.06L13.06 12Z" />
            </svg>
          </button>
        </div>

        {/* Body */}
        <div className="wpsp-ai-drawer__body">
          {/* Choose Social Platforms */}
          <div className="wpsp-ai-drawer__card">
            <p className="wpsp-ai-drawer__card-title">{__('Choose Social Platforms', 'wp-scheduled-posts')}</p>
            <div className="wpsp-ai-drawer__platforms">
              {inlinePlatforms.map((key) => (
                <label key={key} className="wpsp-ai-checkbox">
                  <input
                    type="checkbox"
                    checked={selectedPlatforms.includes(key)}
                    onChange={() => togglePlatform(key)}
                  />
                  <span className="wpsp-ai-checkbox__box" aria-hidden="true" />
                  <span className="wpsp-ai-checkbox__label">{PLATFORM_LABELS[key] || key}</span>
                </label>
              ))}
              {hasMorePlatforms && (
                <div className="wpsp-ai-drawer__more-wrap" ref={moreRef}>
                  <button
                    type="button"
                    className={`wpsp-ai-drawer__more ${isMoreOpen ? 'is-active' : ''}`}
                    onClick={() => setIsMoreOpen((v) => !v)}
                    aria-haspopup="true"
                    aria-expanded={isMoreOpen}
                  >
                    {__('More+', 'wp-scheduled-posts')}
                  </button>
                  {isMoreOpen && (
                    <div className="wpsp-ai-drawer__more-dropdown" role="menu">
                      {morePlatforms.map((key) => (
                        <label key={key} className="wpsp-ai-checkbox wpsp-ai-drawer__more-item">
                          <input
                            type="checkbox"
                            checked={selectedPlatforms.includes(key)}
                            onChange={() => togglePlatform(key)}
                          />
                          <span className="wpsp-ai-checkbox__box" aria-hidden="true" />
                          <span className="wpsp-ai-checkbox__label">{PLATFORM_LABELS[key] || key}</span>
                        </label>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>

          {/* Prompt */}
          <div className="wpsp-ai-drawer__card">
            <p className="wpsp-ai-drawer__card-title">{__('Prompt', 'wp-scheduled-posts')}</p>
            <textarea
              className="wpsp-ai-drawer__textarea"
              placeholder={__('Write your prompt', 'wp-scheduled-posts')}
              value={prompt}
              onChange={(e) => setPrompt(e.target.value)}
              rows={3}
            />
            <label className="wpsp-ai-drawer__toggle-row">
              <span className="wpsp-ai-switch">
                <input
                  type="checkbox"
                  checked={autoGenerate}
                  onChange={(e) => setAutoGenerate(e.target.checked)}
                />
                <span className="wpsp-ai-switch__track" aria-hidden="true" />
              </span>
              <span className="wpsp-ai-drawer__toggle-text">
                <span className="wpsp-ai-drawer__toggle-icon">{wandIcon}</span>
                {__('Auto-generate based on post content', 'wp-scheduled-posts')}
              </span>
            </label>
          </div>

          {/* Tone & Style Controls */}
          <div className="wpsp-ai-drawer__card">
            <p className="wpsp-ai-drawer__card-title">{__('Tone & Style Controls', 'wp-scheduled-posts')}</p>
            <select
              className="wpsp-ai-drawer__select"
              value={tone}
              onChange={(e) => setTone(e.target.value)}
            >
              {TONE_OPTIONS.map((opt) => (
                <option key={opt.value} value={opt.value}>{opt.label}</option>
              ))}
            </select>
          </div>

          {/* Caption Length */}
          <div className="wpsp-ai-drawer__card">
            <p className="wpsp-ai-drawer__card-title">{__('Caption Length (Auto / Manual)', 'wp-scheduled-posts')}</p>
            <select
              className="wpsp-ai-drawer__select"
              value={length}
              onChange={(e) => setLength(e.target.value)}
            >
              {LENGTH_OPTIONS.map((opt) => (
                <option key={opt.value} value={opt.value}>{opt.label}</option>
              ))}
            </select>
          </div>

          {/* Hashtags & Emojis */}
          <div className="wpsp-ai-drawer__card">
            <p className="wpsp-ai-drawer__card-title">{__('Hashtags & Emojis', 'wp-scheduled-posts')}</p>
            <div className="wpsp-ai-drawer__inline-options">
              <label className="wpsp-ai-checkbox">
                <input
                  type="checkbox"
                  checked={generateHashtags}
                  onChange={(e) => setGenerateHashtags(e.target.checked)}
                />
                <span className="wpsp-ai-checkbox__box" aria-hidden="true" />
                <span className="wpsp-ai-checkbox__label">{__('Generate hashtags', 'wp-scheduled-posts')}</span>
              </label>
              <label className="wpsp-ai-checkbox">
                <input
                  type="checkbox"
                  checked={includeEmojis}
                  onChange={(e) => setIncludeEmojis(e.target.checked)}
                />
                <span className="wpsp-ai-checkbox__box" aria-hidden="true" />
                <span className="wpsp-ai-checkbox__label">{__('Include emojis', 'wp-scheduled-posts')}</span>
              </label>
            </div>
          </div>
        </div>

        {/* Footer */}
        {error && (
          <div className="wpsp-ai-drawer__error" role="alert">
            {error}
          </div>
        )}
        <div className="wpsp-ai-drawer__footer">
          <button type="button" className="wpsp-ai-drawer__cancel" onClick={onClose}>
            {__('Cancel', 'wp-scheduled-posts')}
          </button>
          <button
            type="button"
            className="wpsp-ai-drawer__generate"
            onClick={handleGenerate}
            disabled={isGenerating || selectedPlatforms.length === 0}
          >
            <span className="wpsp-ai-drawer__generate-icon">{sparkleWhite}</span>
            {isGenerating
              ? __('Generating…', 'wp-scheduled-posts')
              : __('Generate Captions', 'wp-scheduled-posts')}
          </button>
        </div>
      </aside>
    </>
  );
};

export default AICaptionDrawer;
