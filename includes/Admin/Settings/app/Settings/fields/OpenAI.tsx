import classNames from 'classnames';
import React, { useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
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

const SpinnerIcon = (
  <svg className="wprf-openai-spinner" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round">
    <path d="M12 2a10 10 0 0 1 10 10" />
  </svg>
);

const OpenAI = (props) => {
  const { name = 'openai_api_key', id, label, value, onChange } = props;
  const [show, setShow] = useState(false);
  const [keyValue, setKeyValue] = useState(value || '');
  const [testing, setTesting] = useState(false);
  const [testResult, setTestResult] = useState<null | { success: boolean; message: string }>(null);

  const propagateChange = (val) => {
    onChange({
      target: {
        type: 'text',
        name,
        value: val,
      },
    });
  };

  const handleChange = (e) => {
    const val = e.target.value;
    setKeyValue(val);
    setTestResult(null);
    propagateChange(val);
  };

  const handleClear = () => {
    setKeyValue('');
    setTestResult(null);
    setShow(false);
    propagateChange('');
  };

  const handleTest = () => {
    if (testing) {
      return;
    }
    setTesting(true);
    setTestResult(null);
    apiFetch({
      path: 'wp-scheduled-posts/v1/ai-test-key',
      method: 'POST',
      data: { api_key: keyValue },
    })
      .then((res: any) => {
        setTestResult({
          success: !!res?.success,
          message: res?.message || (res?.success
            ? __('Connection successful.', 'wp-scheduled-posts')
            : __('Connection failed.', 'wp-scheduled-posts')),
        });
      })
      .catch((err: any) => {
        setTestResult({
          success: false,
          message: err?.message || __('Connection failed. Please try again.', 'wp-scheduled-posts'),
        });
      })
      .finally(() => {
        setTesting(false);
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

        <div className="wprf-openai-actions">
          <button
            type="button"
            className="wprf-openai-btn wprf-openai-btn-test"
            onClick={handleTest}
            disabled={testing || !isConnected}
          >
            {testing && SpinnerIcon}
            {testing
              ? __('Testing…', 'wp-scheduled-posts')
              : __('Test Connection', 'wp-scheduled-posts')}
          </button>
          <button
            type="button"
            className="wprf-openai-btn wprf-openai-btn-clear"
            onClick={handleClear}
            disabled={testing || !isConnected}
          >
            {__('Clear API Key', 'wp-scheduled-posts')}
          </button>
        </div>

        {testResult && (
          <p
            className={classNames(
              'wprf-openai-test-result',
              testResult.success ? 'is-success' : 'is-error'
            )}
            role="status"
          >
            {testResult.message}
          </p>
        )}

        <p className="wprf-openai-help">
          {__('Your API key is stored securely on your website. It is strictly used to generate captions for your social posts and nothing else.', 'wp-scheduled-posts')}
        </p>
      </div>
    </div>
  );
};

export default OpenAI;
