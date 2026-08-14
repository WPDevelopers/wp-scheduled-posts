import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { useBuilderContext } from 'quickbuilder';
import React, { useEffect, useRef, useState } from 'react';
import { FieldList } from '../renderer/Renderer';
import NavRail from './NavRail';
import TopBar, { SaveState } from './TopBar';

/** Blurb under each tab heading. The PHP config carries no per-tab copy. */
const TAB_INTROS: Record<string, string> = {
    layout_general: __(
        'Where scheduled posts show up across the admin, and which post types SchedulePress manages.',
        'wp-scheduled-posts'
    ),
    layout_calendar: __(
        'Drag posts between days to reschedule them, and set the default publish time.',
        'wp-scheduled-posts'
    ),
    layout_email_notify: __(
        'Choose who gets an email when a post changes state.',
        'wp-scheduled-posts'
    ),
    layout_social_profile: __(
        'Connect the accounts SchedulePress shares your posts to.',
        'wp-scheduled-posts'
    ),
    layout_social_template: __(
        'Control the caption format used for each platform.',
        'wp-scheduled-posts'
    ),
    layout_ai: __(
        'Generate social captions from your post content.',
        'wp-scheduled-posts'
    ),
    layout_mcp: __(
        'Let AI assistants read and manage your schedule over MCP.',
        'wp-scheduled-posts'
    ),
    layout_scheduling_hub: __(
        'Automatic scheduling, bulk rescheduling and missed schedule recovery.',
        'wp-scheduled-posts'
    ),
    layout_license: __(
        'Activate your license for updates and premium support.',
        'wp-scheduled-posts'
    ),
};

const SettingsApp = ({ wpspObject }) => {
    const builderContext = useBuilderContext();
    const [saveState, setSaveState] = useState<SaveState>('idle');
    const savedTimer = useRef<any>(null);
    /* The first values render is the load, not an edit — don't flash "Saving". */
    const isInitialValues = useRef(true);

    const tabs = builderContext.tabs || [];
    const activeId = builderContext.config?.active;
    const activeTab = tabs.find((tab: any) => tab.id === activeId) || tabs[0];

    // Deep-links from the admin menu (?tab=general, ?page=…-calendar).
    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const page = params.get('page');
        const tab = params.get('tab');

        if (page === 'schedulepress-calendar') {
            builderContext.setActiveTab('layout_calendar');
            return;
        }

        const byQuery = {
            'advanced-schedule': 'layout_scheduling_hub',
            license: 'layout_license',
            general: 'layout_general',
            'social-profile': 'layout_social_profile',
        };

        if (page === 'schedulepress' && tab && byQuery[tab]) {
            builderContext.setActiveTab(byQuery[tab]);
        }
    }, []);

    // Settings save themselves as you change them; the top bar reports on it.
    useEffect(() => {
        if (isInitialValues.current) {
            isInitialValues.current = false;
            return;
        }

        setSaveState('saving');
        clearTimeout(savedTimer.current);

        const request = setTimeout(() => {
            apiFetch({
                path: 'wp-scheduled-posts/v1/settings',
                method: 'POST',
                data: builderContext.values,
            })
                .then(() => {
                    setSaveState('saved');
                    savedTimer.current = setTimeout(() => setSaveState('idle'), 2500);
                })
                .catch(() => setSaveState('idle'));
        }, 400);

        return () => clearTimeout(request);
    }, [builderContext.values]);

    const isPro = !!wpspObject?.pro_version;

    return (
        <div className="tw-min-h-screen tw-bg-canvas tw-font-sans tw-text-ink">
            <TopBar
                imagePath={wpspObject?.image_path}
                freeVersion={wpspObject?.free_version}
                proVersion={wpspObject?.pro_version}
                saveState={saveState}
            />

            <div className="tw-flex tw-flex-col lg:tw-flex-row lg:tw-items-start">
                <div className="tw-border-0 tw-border-solid tw-border-line lg:tw-sticky lg:tw-top-[105px] lg:tw-border-r">
                    <NavRail
                        tabs={tabs}
                        activeId={activeTab?.id}
                        onSelect={(id) => builderContext.setActiveTab(id)}
                        isPro={isPro}
                        imagePath={wpspObject?.image_path}
                    />
                </div>

                <main className="tw-min-w-0 tw-flex-1 tw-px-5 tw-py-6 lg:tw-px-8 lg:tw-py-8">
                    <div className="tw-mx-auto tw-max-w-[960px]">
                        <div className="tw-mb-6">
                            <h1 className="tw-m-0 tw-text-2xl tw-font-semibold tw-text-ink-strong">
                                {activeTab?.label}
                            </h1>
                            {TAB_INTROS[activeTab?.id] && (
                                <p className="tw-m-0 tw-mt-1.5 tw-text-base tw-text-ink-muted">
                                    {TAB_INTROS[activeTab.id]}
                                </p>
                            )}
                        </div>

                        <div
                            /* Keyed so switching tabs replays the entrance. */
                            key={activeTab?.id}
                            /*
                             * `wprf-tab-<id>` is kept because the SCSS for the
                             * screens not yet migrated — the calendar, the
                             * scheduling hub panels — is still scoped under it.
                             */
                            className={`wprf-tab-${activeTab?.id} tw-flex tw-flex-col tw-gap-5 tw-animate-slide-up`}
                        >
                            <FieldList fields={activeTab?.fields} />
                        </div>
                    </div>
                </main>
            </div>
        </div>
    );
};

export default SettingsApp;
