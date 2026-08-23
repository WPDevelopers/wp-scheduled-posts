import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { useBuilderContext } from 'quickbuilder';
import React, { useState } from 'react';
import AutoRulesets from './AutoRulesets';
import ManualScheduler from './ManualScheduler';
import { Ruleset, ScheduleMode } from './types';

/**
 * Scheduling Hub — Manage Schedule mode selector (design: scheduling-hub.html).
 *
 * Phase-1 UI scaffold: Auto/Manual mode tabs that swap the settings panel.
 * `is_pro` gated like the existing AutoScheduler field. No persistence yet —
 * see wp-scheduled-posts-pro/docs/specs/auto-scheduler-rulesets.md.
 */
const SchedulingHub = (props) => {
    const { name, multiple, onChange } = props;
    // @ts-ignore — injected global
    const is_pro = wpspSettingsGlobal?.pro_version ? true : false;
    const builderContext = useBuilderContext();
    const [mode, setMode] = useState<ScheduleMode>('auto');

    // Hydrate saved rulesets from the shared manage_schedule settings.
    const savedRulesets: Ruleset[] =
        builderContext.values?.['manage_schedule']?.['auto_scheduler_rulesets'] ?? [];

    // Persist the ruleset array back into manage_schedule so the global
    // "Save Changes" flow writes it to wpsp_settings_v5 (AutoScheduler pattern).
    const persistRulesets = (rulesets: Ruleset[]) => {
        const manage_schedule = { ...(builderContext.values?.['manage_schedule'] ?? {}) };
        manage_schedule['auto_scheduler_rulesets'] = rulesets;
        onChange({
            target: {
                type: 'scheduling-hub',
                name: ['manage_schedule'],
                value: manage_schedule,
                multiple,
            },
        });
    };

    const switchMode = (next: ScheduleMode) => {
        if (next === mode) return;
        // Phase-2: confirm + discard dirty ruleset before switching.
        setMode(next);
    };

    return (
        <div
            className={classNames(
                'wprf-control',
                'wpsp-scheduling-hub',
                props?.classes
            )}>
            <div className="mode-tabs-wrap">
                <div className="mode-tabs" role="radiogroup" aria-label={__('Scheduling mode', 'wp-scheduled-posts')}>
                    <button
                        className={`mode-tab ${mode === 'auto' ? 'is-active' : ''}`}
                        type="button"
                        role="radio"
                        aria-checked={mode === 'auto'}
                        onClick={() => switchMode('auto')}>
                        <span className="mt-ic">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
                            </svg>
                        </span>
                        {__('Auto Scheduler', 'wp-scheduled-posts')}
                    </button>
                    <button
                        className={`mode-tab ${mode === 'manual' ? 'is-active' : ''}`}
                        type="button"
                        role="radio"
                        aria-checked={mode === 'manual'}
                        onClick={() => switchMode('manual')}>
                        <span className="mt-ic">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <circle cx="12" cy="14" r="3" />
                                <path d="M12 2v4" />
                            </svg>
                        </span>
                        {__('Manual Scheduler', 'wp-scheduled-posts')}
                    </button>
                </div>
                <span className="mode-tab-hint">
                    {__('Only one mode can be active at a time.', 'wp-scheduled-posts')}
                </span>
            </div>

            <div className={classNames('wpsp-sh-panel', { 'pro-deactivated': !is_pro })}>
                {mode === 'auto' ? (
                    <AutoRulesets initial={savedRulesets} persist={persistRulesets} />
                ) : (
                    <ManualScheduler />
                )}
            </div>
        </div>
    );
};

export default SchedulingHub;
