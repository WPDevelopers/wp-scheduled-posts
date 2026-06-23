import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  aiCaption,
  facebookWithBG,
  twitterWithBG,
  linkedinWithBG,
  pinterestWithBG,
  instagramWithBG,
  mediumWithBG,
  threadsWithBG,
  googleMyBusinessWithBG,
} from '../../../icons/icons';
const { __ } = wp.i18n;

// Colored (badge) platform icons for the result cards. The plain icons passed
// down via `platforms` are white glyphs meant to sit on a colored circle, so
// they render invisible on the light result-card background — use these instead.
const PLATFORM_RESULT_ICONS = {
  facebook: facebookWithBG,
  twitter: twitterWithBG,
  linkedin: linkedinWithBG,
  pinterest: pinterestWithBG,
  instagram: instagramWithBG,
  medium: mediumWithBG,
  threads: threadsWithBG,
  google_business: googleMyBusinessWithBG,
};

// Human readable labels for the platform checkboxes shown in the drawer.
const PLATFORM_LABELS = {
  facebook: 'Facebook',
  twitter: 'X(Twitter)',
  linkedin: 'LinkedIn',
  pinterest: 'Pinterest',
  instagram: 'Instagram',
  medium: 'Medium',
  threads: 'Threads',
  google_business: 'Google Business',
};

// Per-platform character limits — kept in sync with platformLimits in
// CustomTemplateModal so the result-card count matches the editor's limit.
const PLATFORM_CHAR_LIMITS = {
  facebook: 63206,
  twitter: 280,
  linkedin: 1300,
  pinterest: 500,
  instagram: 2100,
  medium: 45000,
  threads: 480,
  google_business: 1500,
};

const TONE_OPTIONS = [
  { value: 'professional', label: __('Professional', 'wp-scheduled-posts') },
  { value: 'casual', label: __('Casual', 'wp-scheduled-posts') },
  { value: 'friendly', label: __('Friendly', 'wp-scheduled-posts') },
  { value: 'witty', label: __('Witty', 'wp-scheduled-posts') },
  { value: 'bold', label: __('Bold', 'wp-scheduled-posts') },
  { value: 'informative', label: __('Informative', 'wp-scheduled-posts') },
  // Derives tone & style from the post's own content instead of a fixed preset.
  { value: 'post_specific', label: __('Match Post Tone', 'wp-scheduled-posts') },
];

// Character ranges shown to the user double as the target the AI generates to.
// Keep these ranges in sync with $length_guide in includes/API/AICaption.php.
const LENGTH_OPTIONS = [
  { value: 'auto', label: __('Auto (Recommended)', 'wp-scheduled-posts') },
  { value: 'short', label: __('Short (Up to 280 characters - Ideal for X)', 'wp-scheduled-posts') },
  { value: 'medium', label: __('Medium (Up to 500 characters)', 'wp-scheduled-posts') },
  { value: 'long', label: __('Long (500+ characters - Ideal for LinkedIn & Facebook)', 'wp-scheduled-posts') },
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

// Chevron used in the custom select trigger (rotates 180° while open).
const chevronIcon = (
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" xmlns="http://www.w3.org/2000/svg">
    <path d="m6 9 6 6 6-6" />
  </svg>
);

// Small pencil icon for the per-caption "Edit" action on result cards.
const editIcon = (
  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" xmlns="http://www.w3.org/2000/svg">
    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
  </svg>
);

// Small "insert into editor" icon for the per-caption Insert action.
const insertIcon = (
  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" xmlns="http://www.w3.org/2000/svg">
    <path d="M12 3v12m0 0 4-4m-4 4-4-4M4 21h16" />
  </svg>
);

// Checkmark shown next to the selected option inside the custom select menu.
const checkIcon = (
  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" xmlns="http://www.w3.org/2000/svg">
    <path d="M20 6 9 17l-5-5" />
  </svg>
);

/**
 * Branded dropdown that replaces the native <select> for the drawer's
 * option fields. Shows the selected label in a trigger button and opens a
 * floating menu with a checkmark on the active option. Closes on outside
 * click or Escape (Escape is captured so it doesn't also close the drawer).
 */
const CustomSelect = ({ options, value, onChange, ariaLabel }) => {
  const [open, setOpen] = useState(false);
  const ref = useRef(null);
  const selected = options.find((o) => o.value === value) || options[0];

  useEffect(() => {
    if (!open) return;
    const handleClick = (e) => {
      if (ref.current && !ref.current.contains(e.target)) setOpen(false);
    };
    const handleKey = (e) => {
      if (e.key === 'Escape') {
        e.stopPropagation();
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClick);
    // Capture phase so Escape closes only the menu, not the whole drawer.
    document.addEventListener('keydown', handleKey, true);
    return () => {
      document.removeEventListener('mousedown', handleClick);
      document.removeEventListener('keydown', handleKey, true);
    };
  }, [open]);

  return (
    <div className={`wpsp-ai-select ${open ? 'is-open' : ''}`} ref={ref}>
      <button
        type="button"
        className="wpsp-ai-select__trigger"
        onClick={() => setOpen((v) => !v)}
        aria-haspopup="listbox"
        aria-expanded={open}
        aria-label={ariaLabel}
      >
        <span className="wpsp-ai-select__value">{selected?.label}</span>
        <span className="wpsp-ai-select__chevron" aria-hidden="true">{chevronIcon}</span>
      </button>
      {open && (
        <ul className="wpsp-ai-select__menu" role="listbox" aria-label={ariaLabel}>
          {options.map((opt) => {
            const isSelected = opt.value === value;
            return (
              <li
                key={opt.value}
                role="option"
                aria-selected={isSelected}
                className={`wpsp-ai-select__option ${isSelected ? 'is-selected' : ''}`}
                onClick={() => {
                  onChange(opt.value);
                  setOpen(false);
                }}
              >
                <span className="wpsp-ai-select__check" aria-hidden="true">
                  {isSelected ? checkIcon : null}
                </span>
                <span className="wpsp-ai-select__option-label">{opt.label}</span>
              </li>
            );
          })}
        </ul>
      )}
    </div>
  );
};

/**
 * Shimmering placeholder card shown in the drawer body while captions are
 * generating — mirrors the result card layout (platform row + caption box).
 */
const SkeletonCard = () => (
  <div className="wpsp-ai-skeleton-card" aria-hidden="true">
    <div className="wpsp-ai-skeleton-card__head">
      <span className="wpsp-ai-skeleton wpsp-ai-skeleton__dot" />
      <span className="wpsp-ai-skeleton wpsp-ai-skeleton__bar wpsp-ai-skeleton__bar--name" />
      <span className="wpsp-ai-skeleton-card__pill">
        <span className="wpsp-ai-skeleton wpsp-ai-skeleton__dot wpsp-ai-skeleton__dot--sm" />
        <span className="wpsp-ai-skeleton wpsp-ai-skeleton__bar wpsp-ai-skeleton__bar--pill" />
      </span>
      <span className="wpsp-ai-skeleton wpsp-ai-skeleton__dot wpsp-ai-skeleton-card__chevron" />
    </div>
    <div className="wpsp-ai-skeleton-card__box">
      <span className="wpsp-ai-skeleton wpsp-ai-skeleton__line" />
      <span className="wpsp-ai-skeleton wpsp-ai-skeleton__line" />
      <span className="wpsp-ai-skeleton wpsp-ai-skeleton__line wpsp-ai-skeleton__line--md" />
      <span className="wpsp-ai-skeleton wpsp-ai-skeleton__line wpsp-ai-skeleton__line--sm" />
      <span className="wpsp-ai-skeleton wpsp-ai-skeleton__edit" />
    </div>
  </div>
);

// Captions longer than this get clamped with a "Read more" toggle.
const READ_MORE_THRESHOLD = 180;

/**
 * Accordion card for one generated caption (results screen). Header row
 * toggles expansion; the expanded body shows the caption with an inline
 * Edit mode and a Read more toggle for long captions.
 */
const ResultCard = ({
  platformKey,
  icon,
  caption,
  isExpanded,
  onToggle,
  isEditing,
  onStartEdit,
  onStopEdit,
  onChangeCaption,
  onInsert,
  isInserted,
}) => {
  const isLong = caption.length > READ_MORE_THRESHOLD;
  const [showFull, setShowFull] = useState(false);

  const charCount = caption.length;
  const charLimit = PLATFORM_CHAR_LIMITS[platformKey];
  const isOverLimit = charLimit ? charCount > charLimit : false;
  const charCountNode = (
    <span className={`wpsp-ai-result-card__char-count ${isOverLimit ? 'is-over-limit' : ''}`}>
      {charLimit ? `${charCount}/${charLimit}` : charCount}
    </span>
  );

  return (
    <div className={`wpsp-ai-result-card ${isExpanded ? 'is-expanded' : ''}`}>
      <button
        type="button"
        className="wpsp-ai-result-card__head"
        onClick={onToggle}
        aria-expanded={isExpanded}
      >
        <span className="wpsp-ai-result-card__icon" aria-hidden="true">{icon}</span>
        <span className="wpsp-ai-result-card__name">{PLATFORM_LABELS[platformKey] || platformKey}</span>
        <span className="wpsp-ai-result-card__badge">
          <span className="wpsp-ai-result-card__badge-icon" aria-hidden="true">{aiCaption}</span>
          {__('AI Caption', 'wp-scheduled-posts')}
        </span>
        <span className="wpsp-ai-result-card__chevron" aria-hidden="true">{chevronIcon}</span>
      </button>
      {isExpanded && (
        <div className="wpsp-ai-result-card__box">
          {isEditing ? (
            <>
              <textarea
                className="wpsp-ai-drawer__textarea wpsp-ai-result-card__textarea"
                value={caption}
                onChange={(e) => onChangeCaption(e.target.value)}
                rows={5}
                autoFocus
              />
              <div className="wpsp-ai-result-card__actions">
                {charCountNode}
                <button type="button" className="wpsp-ai-result-card__edit" onClick={onStopEdit}>
                  {__('Done', 'wp-scheduled-posts')}
                </button>
              </div>
            </>
          ) : (
            <>
              <p className={`wpsp-ai-result-card__text ${isLong && !showFull ? 'is-clamped' : ''}`}>
                {caption}
              </p>
              {isLong && (
                <button
                  type="button"
                  className="wpsp-ai-result-card__read-more"
                  onClick={() => setShowFull((v) => !v)}
                >
                  {showFull
                    ? __('Show less', 'wp-scheduled-posts')
                    : __('Read more…', 'wp-scheduled-posts')}
                </button>
              )}
              <div className="wpsp-ai-result-card__actions">
                {charCountNode}
                <button
                  type="button"
                  className={`wpsp-ai-result-card__insert ${isInserted ? 'is-inserted' : ''}`}
                  onClick={onInsert}
                >
                  <span aria-hidden="true">{isInserted ? checkIcon : insertIcon}</span>
                  {isInserted ? __('Inserted', 'wp-scheduled-posts') : __('Insert', 'wp-scheduled-posts')}
                </button>
                <button type="button" className="wpsp-ai-result-card__edit" onClick={onStartEdit}>
                  <span aria-hidden="true">{editIcon}</span>
                  {__('Edit', 'wp-scheduled-posts')}
                </button>
              </div>
            </>
          )}
        </div>
      )}
    </div>
  );
};

const AICaptionDrawer = ({
  isOpen,
  onClose,
  platforms = [],
  social_media_enabled = {},
  selectedPlatform,
  onGenerate,
  onInsertCaptions,
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
  // Generated captions keyed by platform — non-null switches the drawer to the results screen.
  const [results, setResults] = useState(null);
  const [expandedPlatforms, setExpandedPlatforms] = useState([]);
  const [editingPlatform, setEditingPlatform] = useState(null);
  // Platforms showing the transient "Inserted" flash. The button stays enabled
  // so a caption can be inserted as many times as the user wants.
  const [insertedPlatforms, setInsertedPlatforms] = useState([]);
  const insertFlashTimers = useRef({});
  const moreRef = useRef(null);
  // AbortController for the in-flight generation request, so Stop/close can cancel it.
  const abortRef = useRef(null);

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
      // Closing the drawer (Cancel / overlay / Escape) abandons any in-flight generation
      // and discards un-inserted results so it reopens on the form screen.
      abortRef.current?.abort();
      abortRef.current = null;
      setIsGenerating(false);
      setResults(null);
      setExpandedPlatforms([]);
      setEditingPlatform(null);
      setInsertedPlatforms([]);
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
    const controller = new AbortController();
    abortRef.current = controller;
    try {
      setError('');
      setIsGenerating(true);
      if (typeof onGenerate === 'function') {
        const captions = await onGenerate(payload, { signal: controller.signal });
        if (!controller.signal.aborted) {
          if (captions && typeof captions === 'object' && Object.keys(captions).length > 0) {
            // Switch to the results screen with the first caption expanded.
            setResults(captions);
            setExpandedPlatforms(Object.keys(captions).slice(0, 1));
            setEditingPlatform(null);
            setInsertedPlatforms([]);
          } else {
            setError(__('No captions were generated. Please try again.', 'wp-scheduled-posts'));
          }
        }
      }
    } catch (err) {
      // A user-initiated Stop aborts the request — that's not an error.
      if (err?.name !== 'AbortError' && !controller.signal.aborted) {
        console.error('AI caption generation failed:', err);
        setError(
          err?.message ||
            __('Caption generation failed. Please try again.', 'wp-scheduled-posts')
        );
      }
    } finally {
      if (abortRef.current === controller) {
        abortRef.current = null;
        setIsGenerating(false);
      }
    }
  };

  // Stop button in the loading footer — cancel the request, return to the form.
  const handleStop = () => {
    abortRef.current?.abort();
    abortRef.current = null;
    setIsGenerating(false);
  };

  // Results screen — card order follows the platform list, icons come from it too.
  const platformIcons = useMemo(
    () => Object.fromEntries(platforms.map((p) => [p.platform, p.icon])),
    [platforms]
  );
  const orderedResultPlatforms = results
    ? platforms.map((p) => p.platform).filter((key) => results[key])
    : [];

  const toggleResultCard = (key) => {
    setExpandedPlatforms((prev) =>
      prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key]
    );
  };

  const handleInsertAll = () => {
    if (results && typeof onInsertCaptions === 'function') {
      onInsertCaptions(results);
    }
  };

  // Insert a single platform's caption without closing the drawer, so the user
  // can pick and choose per platform — and insert the same one repeatedly.
  // The "Inserted" state is a brief confirmation flash, then reverts to "Insert".
  const handleInsertOne = (key) => {
    if (results && results[key] && typeof onInsertCaptions === 'function') {
      onInsertCaptions({ [key]: results[key] }, { close: false });
      setInsertedPlatforms((prev) => (prev.includes(key) ? prev : [...prev, key]));
      if (insertFlashTimers.current[key]) clearTimeout(insertFlashTimers.current[key]);
      insertFlashTimers.current[key] = setTimeout(() => {
        setInsertedPlatforms((prev) => prev.filter((k) => k !== key));
        delete insertFlashTimers.current[key];
      }, 1500);
    }
  };

  // Clear any pending flash timers on unmount.
  useEffect(() => () => {
    Object.values(insertFlashTimers.current).forEach(clearTimeout);
    insertFlashTimers.current = {};
  }, []);

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

        {/* Body — form → loading skeletons → generated-caption results */}
        <div className={`wpsp-ai-drawer__body ${isGenerating ? 'is-loading' : ''}`} aria-busy={isGenerating}>
          {isGenerating ? (
            <div
              className="wpsp-ai-drawer__skeletons"
              role="status"
              aria-label={__('Generating captions…', 'wp-scheduled-posts')}
            >
              {Array.from({ length: Math.max(selectedPlatforms.length, 2) }, (_, i) => (
                <SkeletonCard key={i} />
              ))}
            </div>
          ) : results ? (
            <div className="wpsp-ai-drawer__results">
              {orderedResultPlatforms.map((key) => (
                <ResultCard
                  key={key}
                  platformKey={key}
                  icon={PLATFORM_RESULT_ICONS[key] || platformIcons[key]}
                  caption={results[key]}
                  isExpanded={expandedPlatforms.includes(key)}
                  onToggle={() => toggleResultCard(key)}
                  isEditing={editingPlatform === key}
                  onStartEdit={() => setEditingPlatform(key)}
                  onStopEdit={() => setEditingPlatform(null)}
                  onChangeCaption={(text) =>
                    setResults((prev) => ({ ...prev, [key]: text }))
                  }
                  onInsert={() => handleInsertOne(key)}
                  isInserted={insertedPlatforms.includes(key)}
                />
              ))}
            </div>
          ) : (
            <>
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
              placeholder={__('Write a catchy announcement for our new feature. Include a few fun emojis.', 'wp-scheduled-posts')}
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
            <CustomSelect
              options={TONE_OPTIONS}
              value={tone}
              onChange={setTone}
              ariaLabel={__('Tone & Style Controls', 'wp-scheduled-posts')}
            />
          </div>

          {/* Caption Length */}
          <div className="wpsp-ai-drawer__card">
            <p className="wpsp-ai-drawer__card-title">{__('Caption Length (Auto / Manual)', 'wp-scheduled-posts')}</p>
            <CustomSelect
              options={LENGTH_OPTIONS}
              value={length}
              onChange={setLength}
              ariaLabel={__('Caption Length', 'wp-scheduled-posts')}
            />
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
            </>
          )}
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
          {isGenerating ? (
            <button
              type="button"
              className="wpsp-ai-drawer__stop"
              onClick={handleStop}
              aria-label={__('Stop generating', 'wp-scheduled-posts')}
            >
              <span className="wpsp-ai-drawer__stop-spinner" aria-hidden="true" />
              {__('Generating…', 'wp-scheduled-posts')}
            </button>
          ) : results ? (
            <button
              type="button"
              className="wpsp-ai-drawer__generate"
              onClick={handleInsertAll}
              disabled={orderedResultPlatforms.length === 0}
            >
              {__('Insert All Captions', 'wp-scheduled-posts')}
            </button>
          ) : (
            <button
              type="button"
              className="wpsp-ai-drawer__generate"
              onClick={handleGenerate}
              disabled={selectedPlatforms.length === 0}
            >
              <span className="wpsp-ai-drawer__generate-icon">{sparkleWhite}</span>
              {__('Generate Captions', 'wp-scheduled-posts')}
            </button>
          )}
        </div>
      </aside>
    </>
  );
};

export default AICaptionDrawer;
