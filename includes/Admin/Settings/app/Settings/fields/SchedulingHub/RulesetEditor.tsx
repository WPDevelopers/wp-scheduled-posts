import { __ } from '@wordpress/i18n';
import React from 'react';
import HelpTip from './controls/HelpTip';
import QuerySettings from './groups/QuerySettings';
import Recycling from './groups/Recycling';
import Schedule from './groups/Schedule';
import { Ruleset } from './types';

type Props = {
    ruleset: Ruleset;
    isNew: boolean;
    dirty: boolean;
    onChange: (patch: Partial<Ruleset>) => void;
    onRun: () => void;
    onToggle: () => void;
    onSave: () => void;
    onDiscard: () => void;
};

/**
 * Inline ruleset editor (design: .rs-editor-inline). Rendered under the active
 * row. Groups: Query Settings, Recycling, Schedule.
 */
const RulesetEditor = ({
    ruleset,
    isNew,
    dirty,
    onChange,
    onRun,
    onToggle,
    onSave,
    onDiscard,
}: Props) => {
    const enabled = ruleset.status === 'enabled';
    const lockMsg = isNew
        ? __('Save the ruleset first', 'wp-scheduled-posts')
        : undefined;

    return (
        <section className="rs-section rs-editor-inline">
            <header className="rs-section-head rs-editor-head">
                <h4 className="rs-section-title">
                    {isNew
                        ? __('New Ruleset', 'wp-scheduled-posts')
                        : __('Edit Ruleset', 'wp-scheduled-posts')}
                    <span className="rs-id-tag">#{ruleset.id}</span>
                    {isNew && <span className="rs-new-tag">{__('New', 'wp-scheduled-posts')}</span>}
                </h4>
                <div className="rs-name-field">
                    <input
                        className="input rs-name-input"
                        type="text"
                        value={ruleset.name}
                        placeholder={__('Ruleset name…', 'wp-scheduled-posts')}
                        aria-label={__('Ruleset name', 'wp-scheduled-posts')}
                        onChange={(e) => onChange({ name: e.target.value })}
                    />
                    <HelpTip text={__('A short identifier for this ruleset. Shown in the saved rulesets list above.', 'wp-scheduled-posts')} />
                </div>
                <div className="rs-section-actions">
                    <button
                        className="btn btn-sm"
                        type="button"
                        disabled={isNew}
                        title={lockMsg}
                        onClick={onRun}>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                            <polygon points="6 4 20 12 6 20 6 4" />
                        </svg>
                        {__('Run Once', 'wp-scheduled-posts')}
                    </button>
                    <button
                        className="btn btn-sm btn-primary"
                        type="button"
                        disabled={isNew}
                        title={lockMsg}
                        onClick={onToggle}>
                        {enabled
                            ? __('Disable Ruleset', 'wp-scheduled-posts')
                            : __('Enable Ruleset', 'wp-scheduled-posts')}
                    </button>
                </div>
            </header>

            <div className="rs-edit-groups">
                <QuerySettings
                    value={ruleset.query}
                    onChange={(patch) =>
                        onChange({ query: { ...ruleset.query, ...patch } })
                    }
                />
                <Recycling
                    value={ruleset.recycling}
                    onChange={(patch) =>
                        onChange({ recycling: { ...ruleset.recycling, ...patch } })
                    }
                />
                <Schedule
                    value={ruleset.schedule}
                    onChange={(patch) =>
                        onChange({ schedule: { ...ruleset.schedule, ...patch } })
                    }
                />
            </div>

            <footer className="rs-edit-footer">
                <span className="rs-edit-dirty" hidden={!dirty}>
                    {__('Unsaved changes', 'wp-scheduled-posts')}
                </span>
                <button className="btn btn-sm" type="button" onClick={onDiscard}>
                    {isNew
                        ? __('Cancel', 'wp-scheduled-posts')
                        : __('Discard', 'wp-scheduled-posts')}
                </button>
                <button
                    className="btn btn-sm btn-primary"
                    type="button"
                    disabled={!(dirty || isNew)}
                    onClick={onSave}>
                    {__('Save Changes', 'wp-scheduled-posts')}
                </button>
            </footer>
        </section>
    );
};

export default RulesetEditor;
