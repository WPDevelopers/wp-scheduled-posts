import { __ } from '@wordpress/i18n';
import React from 'react';
import { deriveFrequency } from './defaults';
import { Ruleset } from './types';

type Props = {
    ruleset: Ruleset;
    isActive: boolean;
    onEdit: () => void;
    onRun: () => void;
    onDelete: () => void;
};

/** A single row in the Saved Rulesets list (design: .ruleset-card). */
const RulesetRow = ({ ruleset, isActive, onEdit, onRun, onDelete }: Props) => (
    <article className={`ruleset-card ${isActive ? 'is-active' : ''}`} tabIndex={0}>
        <span className="ruleset-id">#{ruleset.id}</span>
        <span
            className={`ruleset-status ${
                ruleset.status === 'enabled' ? 'is-enabled' : ''
            }`}>
            {ruleset.status}
        </span>
        <div className="ruleset-name">{ruleset.name}</div>
        <span className="ruleset-freq">{deriveFrequency(ruleset)}</span>
        <div className="ruleset-row-actions">
            <button
                className="icon-btn"
                type="button"
                title={__('Edit ruleset', 'wp-scheduled-posts')}
                aria-label={__('Edit ruleset', 'wp-scheduled-posts')}
                onClick={onEdit}>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M12 20h9" />
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
                </svg>
            </button>
            <button
                className="icon-btn"
                type="button"
                title={__('Run once', 'wp-scheduled-posts')}
                aria-label={__('Run once', 'wp-scheduled-posts')}
                onClick={onRun}>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                    <polygon points="6 4 20 12 6 20 6 4" />
                </svg>
            </button>
            <button
                className="icon-btn is-danger"
                type="button"
                title={__('Delete ruleset', 'wp-scheduled-posts')}
                aria-label={__('Delete ruleset', 'wp-scheduled-posts')}
                onClick={onDelete}>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <polyline points="3 6 5 6 21 6" />
                    <path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6" />
                    <path d="M10 11v6" />
                    <path d="M14 11v6" />
                </svg>
            </button>
        </div>
    </article>
);

export default RulesetRow;
