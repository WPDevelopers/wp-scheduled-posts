import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { useBuilderContext } from 'quickbuilder';
import React, { useEffect, useRef, useState } from 'react';
import { FieldList } from '../renderer/Renderer';
import NavRail from './NavRail';
import { readRoute, slugForTab, tabForSlug, writeRoute } from './routing';
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
    /*
     * The tab we asked for from the URL, held until it actually becomes
     * active. `setActiveTab` goes through a reducer, so the sync effect below
     * runs once more with the *old* tab first — writing the URL on that pass
     * would clobber the `section` the sub-tab has not read yet.
     */
    const pendingRestore = useRef<string | null>(null);
    /* The first URL write normalises the address bar instead of adding to it. */
    const isFirstRoute = useRef(true);
    /* Distinguishes a real tab switch from a re-render on the same tab. */
    const previousTab = useRef<string | null>(null);

    const tabs = builderContext.tabs || [];
    const activeId = builderContext.config?.active;
    const activeTab = tabs.find((tab: any) => tab.id === activeId) || tabs[0];

    /*
     * Restore the tab from the URL on load, so a reload or a shared link lands
     * where the user was rather than back on General.
     */
    useEffect(() => {
        const page = new URLSearchParams(window.location.search).get('page');

        if (page === 'schedulepress-calendar') {
            pendingRestore.current = 'layout_calendar';
            builderContext.setActiveTab('layout_calendar');
            return;
        }

        const restored = tabForSlug(readRoute().tab, tabs);

        if (restored) {
            pendingRestore.current = restored;
            builderContext.setActiveTab(restored);
        }
    }, []);

    // Keep the URL in step with the tab.
    useEffect(() => {
        if (!activeTab?.id) {
            return;
        }

        /*
         * A restore is in flight: the URL already says where we are going, so
         * leave it alone until we get there.
         */
        if (pendingRestore.current) {
            if (activeTab.id === pendingRestore.current) {
                pendingRestore.current = null;
                previousTab.current = activeTab.id;
                isFirstRoute.current = false;
            }

            return;
        }

        if (isFirstRoute.current) {
            isFirstRoute.current = false;
            previousTab.current = activeTab.id;
            writeRoute({ tab: slugForTab(activeTab.id) }, true);
            return;
        }

        // Only a real switch clears the sub-tab; it belonged to the old tab.
        const isSwitch = previousTab.current !== activeTab.id;

        writeRoute(
            isSwitch
                ? { tab: slugForTab(activeTab.id), section: null }
                : { tab: slugForTab(activeTab.id) }
        );
        previousTab.current = activeTab.id;
    }, [activeTab?.id]);

    // Browser back/forward moves between tabs.
    useEffect(() => {
        const onPopState = () => {
            const restored = tabForSlug(readRoute().tab, tabs);

            if (restored && restored !== builderContext.config?.active) {
                pendingRestore.current = restored;
                builderContext.setActiveTab(restored);
            }
        };

        window.addEventListener('popstate', onPopState);

        return () => window.removeEventListener('popstate', onPopState);
    }, [tabs, builderContext.config?.active]);

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
