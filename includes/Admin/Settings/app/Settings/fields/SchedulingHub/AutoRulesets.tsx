import { __ } from '@wordpress/i18n';
import React, { useEffect, useRef, useState } from 'react';
import RulesetEditor from './RulesetEditor';
import RulesetRow from './RulesetRow';
import { blankRuleset, nextRulesetId } from './defaults';
import { Ruleset } from './types';

// Live "Time now" stamp (design: #rsNowStamp).
const nowStamp = () => {
    const d = new Date();
    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(
        d.getHours()
    )}:${pad(d.getMinutes())}:${pad(d.getSeconds())} ${tz}`;
};

type Props = {
    initial: Ruleset[];
    persist: (rulesets: Ruleset[]) => void;
};

/**
 * Auto Scheduler — Rulesets (auto mode body).
 * In-memory CRUD + inline editor + local dirty state. Commit operations
 * (editor Save, Enable/Disable, Delete) push the array up via `persist` so the
 * global "Save Changes" writes it to `manage_schedule.auto_scheduler_rulesets`.
 */
const AutoRulesets = ({ initial, persist }: Props) => {
    const [rulesets, setRulesets] = useState<Ruleset[]>(initial ?? []);
    const [activeId, setActiveId] = useState<number | null>(null);
    const [isNew, setIsNew] = useState(false);
    const [dirty, setDirty] = useState(false);
    const snapshot = useRef<Ruleset | null>(null);
    const [stamp, setStamp] = useState(nowStamp());

    useEffect(() => {
        const t = setInterval(() => setStamp(nowStamp()), 1000);
        return () => clearInterval(t);
    }, []);

    const active = activeId !== null ? rulesets.find((r) => r.id === activeId) : null;

    const patchActive = (patch: Partial<Ruleset>) => {
        if (activeId === null) return;
        setRulesets((prev) =>
            prev.map((r) => (r.id === activeId ? { ...r, ...patch } : r))
        );
        setDirty(true);
    };

    const openEditor = (id: number) => {
        if (activeId !== null && activeId !== id && dirty) {
            // eslint-disable-next-line no-alert
            if (!window.confirm(__('Discard unsaved changes on the open ruleset?', 'wp-scheduled-posts'))) return;
            revertActive();
        }
        const r = rulesets.find((x) => x.id === id) || null;
        snapshot.current = r ? { ...r } : null;
        setActiveId(id);
        setIsNew(false);
        setDirty(false);
    };

    const revertActive = () => {
        if (activeId === null) return;
        if (isNew) {
            setRulesets((prev) => prev.filter((r) => r.id !== activeId));
        } else if (snapshot.current) {
            const snap = snapshot.current;
            setRulesets((prev) => prev.map((r) => (r.id === snap.id ? snap : r)));
        }
    };

    const closeEditor = () => {
        setActiveId(null);
        setIsNew(false);
        setDirty(false);
        snapshot.current = null;
    };

    const createRuleset = () => {
        if (activeId !== null && dirty) {
            // eslint-disable-next-line no-alert
            if (!window.confirm(__('Discard unsaved changes on the open ruleset?', 'wp-scheduled-posts'))) return;
            revertActive();
        }
        const fresh = blankRuleset(nextRulesetId(rulesets));
        setRulesets((prev) => [...prev, fresh]);
        snapshot.current = null;
        setActiveId(fresh.id);
        setIsNew(true);
        setDirty(false);
    };

    const deleteRuleset = (id: number) => {
        // eslint-disable-next-line no-alert
        if (!window.confirm(__('Delete this ruleset? This cannot be undone.', 'wp-scheduled-posts'))) return;
        const next = rulesets.filter((r) => r.id !== id);
        setRulesets(next);
        if (activeId === id) closeEditor();
        persist(next);
    };

    const toggleActive = () => {
        if (activeId === null) return;
        const next = rulesets.map((r) =>
            r.id === activeId
                ? {
                      ...r,
                      status: (r.status === 'enabled' ? 'disabled' : 'enabled') as Ruleset['status'],
                  }
                : r
        );
        setRulesets(next);
        persist(next);
    };

    const saveActive = () => {
        // Editor edits are already applied to `rulesets` in place; commit them.
        persist(rulesets);
        closeEditor();
    };

    const discardActive = () => {
        if (dirty) revertActive();
        closeEditor();
    };

    return (
        <div className="mode-settings">
            <header className="ms-head">
                <span className="ms-head-ic">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
                    </svg>
                </span>
                <div className="ms-head-text">
                    <div className="ms-head-title">
                        {__('Auto Scheduler — Rulesets', 'wp-scheduled-posts')}
                    </div>
                    <div className="ms-head-sub">
                        {__('Build unlimited rulesets to recycle posts, change taxonomies, publish drafts on a schedule, and more.', 'wp-scheduled-posts')}
                    </div>
                </div>
            </header>

            <div className="ms-body">
                <section className="rs-section">
                    <header className="rs-section-head">
                        <h4 className="rs-section-title">
                            {__('Saved Rulesets', 'wp-scheduled-posts')}
                        </h4>
                        <span className="rs-section-meta">
                            {__('Time now:', 'wp-scheduled-posts')}{' '}
                            <b style={{ color: 'var(--ink-2)', fontWeight: 600 }}>
                                {stamp}
                            </b>
                        </span>
                        <div className="rs-section-actions">
                            <button
                                className="btn btn-sm btn-primary"
                                type="button"
                                onClick={createRuleset}>
                                + {__('New Ruleset', 'wp-scheduled-posts')}
                            </button>
                        </div>
                    </header>

                    <div className="ruleset-list">
                        {rulesets.length === 0 ? (
                            <div className="ruleset-empty">
                                {__('No rulesets yet. Click ', 'wp-scheduled-posts')}
                                <b>{__('New Ruleset', 'wp-scheduled-posts')}</b>
                                {__(' to create your first one.', 'wp-scheduled-posts')}
                            </div>
                        ) : (
                            rulesets.map((r) => (
                                <React.Fragment key={r.id}>
                                    <RulesetRow
                                        ruleset={r}
                                        isActive={r.id === activeId}
                                        onEdit={() => openEditor(r.id)}
                                        onRun={() => {}}
                                        onDelete={() => deleteRuleset(r.id)}
                                    />
                                    {r.id === activeId && active && (
                                        <RulesetEditor
                                            ruleset={active}
                                            isNew={isNew}
                                            dirty={dirty}
                                            onChange={patchActive}
                                            onRun={() => {}}
                                            onToggle={toggleActive}
                                            onSave={saveActive}
                                            onDiscard={discardActive}
                                        />
                                    )}
                                </React.Fragment>
                            ))
                        )}
                    </div>
                </section>
            </div>
        </div>
    );
};

export default AutoRulesets;
