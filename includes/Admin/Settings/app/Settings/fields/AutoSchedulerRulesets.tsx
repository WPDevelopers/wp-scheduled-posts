import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import React, { useState } from 'react';

/**
 * Auto Scheduler — Rulesets (Pro)
 *
 * UI-only scaffold. Renders the ruleset builder layout with blank fields:
 *  - Saved Rulesets list (+ New Ruleset)
 *  - Edit Ruleset panel → Query Settings + Recycling
 *
 * No persistence / backend wiring yet — all state is local. Wire the
 * `onChange`/REST layer in a later pass.
 */

type Ruleset = {
    name: string;
    // Query Settings
    post_types: string[];
    post_statuses: string[];
    min_post_age: string;
    max_post_age: string;
    max_posts_per_query: string;
    order_of_query: string;
    ignore_sticky: boolean;
    // Recycling
    min_recycle_age: string;
    max_recycle_age: string;
    recycle_mode: string;
    max_times_to_recycle: string;
    // meta
    enabled: boolean;
};

const blankRuleset = (): Ruleset => ({
    name: '',
    post_types: [],
    post_statuses: [],
    min_post_age: '',
    max_post_age: '',
    max_posts_per_query: '',
    order_of_query: 'oldest',
    ignore_sticky: false,
    min_recycle_age: '',
    max_recycle_age: '',
    recycle_mode: 'oldest',
    max_times_to_recycle: '',
    enabled: false,
});

const AutoSchedulerRulesets = (props) => {
    // @ts-ignore
    const is_pro = wpspSettingsGlobal?.pro_version ? true : false;

    const [rulesets, setRulesets] = useState<Ruleset[]>([]);
    const [editingIndex, setEditingIndex] = useState<number | null>(null);

    const editing = editingIndex !== null ? rulesets[editingIndex] : null;

    const addRuleset = () => {
        setRulesets((prev) => {
            const next = [...prev, blankRuleset()];
            setEditingIndex(next.length - 1);
            return next;
        });
    };

    const deleteRuleset = (index: number) => {
        setRulesets((prev) => prev.filter((_, i) => i !== index));
        setEditingIndex((cur) => {
            if (cur === null) return cur;
            if (cur === index) return null;
            return cur > index ? cur - 1 : cur;
        });
    };

    const updateField = (key: keyof Ruleset, value: any) => {
        if (editingIndex === null) return;
        setRulesets((prev) => {
            const next = [...prev];
            next[editingIndex] = { ...next[editingIndex], [key]: value };
            return next;
        });
    };

    return (
        <div
            className={classNames(
                'wprf-control',
                'wprf-auto-scheduler-rulesets',
                props?.classes
            )}>
            {/* Header card */}
            <div className="wpsp-rulesets-header">
                <span className="wpsp-rulesets-header__icon">⚡</span>
                <div className="wpsp-rulesets-header__text">
                    <h3>{__('Auto Scheduler — Rulesets', 'wp-scheduled-posts')}</h3>
                    <p>
                        {__(
                            'Build unlimited rulesets to recycle posts, change taxonomies, publish drafts on a schedule, and more.',
                            'wp-scheduled-posts'
                        )}
                    </p>
                </div>
            </div>

            <div className={`wpsp-rulesets-body ${!is_pro ? 'pro-deactivated' : ''}`}>
                {/* Saved rulesets */}
                <div className="wpsp-rulesets-saved">
                    <div className="wpsp-rulesets-saved__head">
                        <h4>{__('Saved Rulesets', 'wp-scheduled-posts')}</h4>
                        <button
                            type="button"
                            className="wpsp-btn wpsp-btn--primary"
                            onClick={addRuleset}
                            disabled={!is_pro}>
                            + {__('New Ruleset', 'wp-scheduled-posts')}
                        </button>
                    </div>

                    {rulesets.length === 0 ? (
                        <div className="wpsp-rulesets-empty">
                            {__(
                                'No rulesets yet. Click “New Ruleset” to create one.',
                                'wp-scheduled-posts'
                            )}
                        </div>
                    ) : (
                        <ul className="wpsp-rulesets-list">
                            {rulesets.map((rs, index) => (
                                <li
                                    key={index}
                                    className={classNames('wpsp-ruleset-row', {
                                        'is-active': editingIndex === index,
                                    })}>
                                    <span className="wpsp-ruleset-row__id">
                                        #{index + 1}
                                    </span>
                                    <span
                                        className={classNames(
                                            'wpsp-ruleset-row__status',
                                            rs.enabled ? 'is-enabled' : 'is-disabled'
                                        )}>
                                        {rs.enabled
                                            ? __('Enabled', 'wp-scheduled-posts')
                                            : __('Disabled', 'wp-scheduled-posts')}
                                    </span>
                                    <span className="wpsp-ruleset-row__name">
                                        {rs.name ||
                                            __('Untitled ruleset', 'wp-scheduled-posts')}
                                    </span>
                                    <div className="wpsp-ruleset-row__actions">
                                        <button
                                            type="button"
                                            className="wpsp-icon-btn"
                                            title={__('Edit', 'wp-scheduled-posts')}
                                            onClick={() => setEditingIndex(index)}>
                                            ✎
                                        </button>
                                        <button
                                            type="button"
                                            className="wpsp-icon-btn"
                                            title={__('Run Once', 'wp-scheduled-posts')}>
                                            ▶
                                        </button>
                                        <button
                                            type="button"
                                            className="wpsp-icon-btn wpsp-icon-btn--danger"
                                            title={__('Delete', 'wp-scheduled-posts')}
                                            onClick={() => deleteRuleset(index)}>
                                            🗑
                                        </button>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                {/* Edit ruleset panel */}
                {editing && (
                    <div className="wpsp-ruleset-edit">
                        <div className="wpsp-ruleset-edit__head">
                            <div className="wpsp-ruleset-edit__title">
                                <span className="wpsp-ruleset-edit__label">
                                    {__('Edit Ruleset', 'wp-scheduled-posts')}
                                </span>
                                <span className="wpsp-ruleset-edit__badge">
                                    #{(editingIndex ?? 0) + 1}
                                </span>
                                <input
                                    type="text"
                                    className="wpsp-input wpsp-input--title"
                                    placeholder={__(
                                        'Ruleset name…',
                                        'wp-scheduled-posts'
                                    )}
                                    value={editing.name}
                                    onChange={(e) =>
                                        updateField('name', e.target.value)
                                    }
                                />
                            </div>
                            <div className="wpsp-ruleset-edit__buttons">
                                <button type="button" className="wpsp-btn">
                                    ▶ {__('Run Once', 'wp-scheduled-posts')}
                                </button>
                                <button
                                    type="button"
                                    className="wpsp-btn wpsp-btn--dark"
                                    onClick={() =>
                                        updateField('enabled', !editing.enabled)
                                    }>
                                    ✓{' '}
                                    {editing.enabled
                                        ? __('Disable Ruleset', 'wp-scheduled-posts')
                                        : __('Enable Ruleset', 'wp-scheduled-posts')}
                                </button>
                            </div>
                        </div>

                        {/* Query Settings */}
                        <div className="wpsp-ruleset-card">
                            <div className="wpsp-ruleset-card__head">
                                <h5>🕘 {__('Query Settings', 'wp-scheduled-posts')}</h5>
                                <span>
                                    {__(
                                        'Filter which posts this ruleset applies to.',
                                        'wp-scheduled-posts'
                                    )}
                                </span>
                            </div>
                            <div className="wpsp-ruleset-grid">
                                <label className="wpsp-field">
                                    <span>{__('Post Type', 'wp-scheduled-posts')}</span>
                                    <input
                                        type="text"
                                        className="wpsp-input"
                                        placeholder={__(
                                            'Add post type…',
                                            'wp-scheduled-posts'
                                        )}
                                    />
                                </label>
                                <label className="wpsp-field">
                                    <span>{__('Post Status', 'wp-scheduled-posts')}</span>
                                    <input
                                        type="text"
                                        className="wpsp-input"
                                        placeholder={__(
                                            'Add status…',
                                            'wp-scheduled-posts'
                                        )}
                                    />
                                </label>
                                <label className="wpsp-field wpsp-field--with-suffix">
                                    <span>
                                        {__('Min Post Age', 'wp-scheduled-posts')}
                                    </span>
                                    <div className="wpsp-input-suffix">
                                        <input
                                            type="number"
                                            className="wpsp-input"
                                            placeholder="0"
                                            value={editing.min_post_age}
                                            onChange={(e) =>
                                                updateField(
                                                    'min_post_age',
                                                    e.target.value
                                                )
                                            }
                                        />
                                        <span>{__('days', 'wp-scheduled-posts')}</span>
                                    </div>
                                </label>
                                <label className="wpsp-field wpsp-field--with-suffix">
                                    <span>
                                        {__('Max Post Age', 'wp-scheduled-posts')}
                                    </span>
                                    <div className="wpsp-input-suffix">
                                        <input
                                            type="number"
                                            className="wpsp-input"
                                            placeholder="0"
                                            value={editing.max_post_age}
                                            onChange={(e) =>
                                                updateField(
                                                    'max_post_age',
                                                    e.target.value
                                                )
                                            }
                                        />
                                        <span>{__('days', 'wp-scheduled-posts')}</span>
                                    </div>
                                </label>
                                <label className="wpsp-field">
                                    <span>
                                        {__(
                                            'Max Posts per Query',
                                            'wp-scheduled-posts'
                                        )}
                                    </span>
                                    <input
                                        type="number"
                                        className="wpsp-input"
                                        placeholder="1"
                                        value={editing.max_posts_per_query}
                                        onChange={(e) =>
                                            updateField(
                                                'max_posts_per_query',
                                                e.target.value
                                            )
                                        }
                                    />
                                </label>
                                <label className="wpsp-field">
                                    <span>
                                        {__('Order of Query', 'wp-scheduled-posts')}
                                    </span>
                                    <select
                                        className="wpsp-input"
                                        value={editing.order_of_query}
                                        onChange={(e) =>
                                            updateField(
                                                'order_of_query',
                                                e.target.value
                                            )
                                        }>
                                        <option value="oldest">
                                            {__('Oldest Posts', 'wp-scheduled-posts')}
                                        </option>
                                        <option value="newest">
                                            {__('Newest Posts', 'wp-scheduled-posts')}
                                        </option>
                                        <option value="random">
                                            {__('Random', 'wp-scheduled-posts')}
                                        </option>
                                    </select>
                                </label>
                            </div>
                            <div className="wpsp-ruleset-toggle-row">
                                <div>
                                    <strong>
                                        {__(
                                            'Ignore Sticky Posts',
                                            'wp-scheduled-posts'
                                        )}
                                    </strong>
                                    <p>
                                        {__(
                                            'Excludes WordPress sticky posts from queries.',
                                            'wp-scheduled-posts'
                                        )}
                                    </p>
                                </div>
                                <label className="wpsp-switch">
                                    <input
                                        type="checkbox"
                                        checked={editing.ignore_sticky}
                                        onChange={(e) =>
                                            updateField(
                                                'ignore_sticky',
                                                e.target.checked
                                            )
                                        }
                                    />
                                    <span className="wpsp-switch__slider" />
                                </label>
                            </div>
                        </div>

                        {/* Recycling */}
                        <div className="wpsp-ruleset-card">
                            <div className="wpsp-ruleset-card__head">
                                <h5>🔁 {__('Recycling', 'wp-scheduled-posts')}</h5>
                                <span>
                                    {__(
                                        'Republish old content if not enough eligible posts are found.',
                                        'wp-scheduled-posts'
                                    )}
                                </span>
                            </div>
                            <div className="wpsp-ruleset-grid">
                                <label className="wpsp-field wpsp-field--with-suffix">
                                    <span>
                                        {__('Min Recycle Age', 'wp-scheduled-posts')}
                                    </span>
                                    <div className="wpsp-input-suffix">
                                        <input
                                            type="number"
                                            className="wpsp-input"
                                            placeholder="0"
                                            value={editing.min_recycle_age}
                                            onChange={(e) =>
                                                updateField(
                                                    'min_recycle_age',
                                                    e.target.value
                                                )
                                            }
                                        />
                                        <span>{__('days', 'wp-scheduled-posts')}</span>
                                    </div>
                                </label>
                                <label className="wpsp-field wpsp-field--with-suffix">
                                    <span>
                                        {__('Max Recycle Age', 'wp-scheduled-posts')}
                                    </span>
                                    <div className="wpsp-input-suffix">
                                        <input
                                            type="number"
                                            className="wpsp-input"
                                            placeholder="0"
                                            value={editing.max_recycle_age}
                                            onChange={(e) =>
                                                updateField(
                                                    'max_recycle_age',
                                                    e.target.value
                                                )
                                            }
                                        />
                                        <span>{__('days', 'wp-scheduled-posts')}</span>
                                    </div>
                                </label>
                                <label className="wpsp-field">
                                    <span>
                                        {__('Recycle Mode', 'wp-scheduled-posts')}
                                    </span>
                                    <select
                                        className="wpsp-input"
                                        value={editing.recycle_mode}
                                        onChange={(e) =>
                                            updateField(
                                                'recycle_mode',
                                                e.target.value
                                            )
                                        }>
                                        <option value="oldest">
                                            {__('Oldest Posts', 'wp-scheduled-posts')}
                                        </option>
                                        <option value="newest">
                                            {__('Newest Posts', 'wp-scheduled-posts')}
                                        </option>
                                        <option value="random">
                                            {__('Random', 'wp-scheduled-posts')}
                                        </option>
                                    </select>
                                </label>
                                <label className="wpsp-field wpsp-field--with-suffix">
                                    <span>
                                        {__(
                                            'Max Times to Recycle',
                                            'wp-scheduled-posts'
                                        )}
                                    </span>
                                    <div className="wpsp-input-suffix">
                                        <input
                                            type="number"
                                            className="wpsp-input"
                                            placeholder="0"
                                            value={editing.max_times_to_recycle}
                                            onChange={(e) =>
                                                updateField(
                                                    'max_times_to_recycle',
                                                    e.target.value
                                                )
                                            }
                                        />
                                        <span>{__('times', 'wp-scheduled-posts')}</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default AutoSchedulerRulesets;
