import classNames from 'classnames';
import React, { useState } from 'react';
import { __ } from '@wordpress/i18n';

const EyeIcon = (
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
    <circle cx="12" cy="12" r="3" />
  </svg>
);

const EyeOffIcon = (
  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
    <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
    <line x1="2" y1="2" x2="22" y2="22" />
  </svg>
);

const OpenAI = (props) => {
  const { name = 'openai_api_key', id, label, value, onChange } = props;
  const [show, setShow] = useState(false);
  const [keyValue, setKeyValue] = useState(value || '');

  const handleChange = (e) => {
    const val = e.target.value;
    setKeyValue(val);
    onChange({
      target: {
        type: 'text',
        name,
        value: val,
      },
    });
  };

  const isConnected = !!(keyValue && keyValue.trim().length > 0);

  return (
    <div className={classNames('wprf-control', 'wprf-openai', `wprf-${name}-openai`)}>
      <div className="wprf-control-label">
        <label htmlFor={id}>{label}</label>
      </div>
      <div className="wprf-control-field">
        <div className="wprf-openai-input-wrap">
          <input
            id={id}
            type={show ? 'text' : 'password'}
            className="wprf-openai-input"
            value={keyValue}
            placeholder="sk-..."
            onChange={handleChange}
            autoComplete="off"
            spellCheck={false}
          />
          <button
            type="button"
            className="wprf-openai-toggle"
            onClick={() => setShow((s) => !s)}
            aria-label={show ? __('Hide API key', 'wp-scheduled-posts') : __('Show API key', 'wp-scheduled-posts')}
          >
            {show ? EyeOffIcon : EyeIcon}
          </button>
        </div>

        <div className="wprf-openai-meta">
          <span className={classNames('wprf-openai-status', isConnected ? 'is-connected' : 'is-empty')}>
            <span className="wprf-openai-status-dot" />
            {isConnected
              ? __('API key added', 'wp-scheduled-posts')
              : __('No API key added', 'wp-scheduled-posts')}
          </span>
          <a
            className="wprf-openai-link"
            href="https://platform.openai.com/api-keys"
            target="_blank"
            rel="noopener noreferrer"
          >
            {__('Get your OpenAI API key', 'wp-scheduled-posts')}
          </a>
        </div>

        <p className="wprf-openai-help">
          {__('Your API key is stored on your site and used only to generate AI captions for your social posts.', 'wp-scheduled-posts')}
        </p>
      </div>
    </div>
  );
};

export default OpenAI;
