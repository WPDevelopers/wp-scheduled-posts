/**
 * Tab routing.
 *
 * wp-admin owns the path (`admin.php?page=schedulepress`), so the tab lives in
 * a query parameter and we drive it with the History API rather than a router.
 * The active tab is written to `?tab=`, and a sub-tab — Social Templates,
 * Scheduling Hub — to `?section=`, so a reload or a shared link lands where
 * the user actually was.
 */

/**
 * Canonical slug per tab. Anything not listed derives its slug from the id, so
 * tabs the Pro plugin or a filter adds still route without being registered.
 */
const TAB_SLUGS: Record<string, string> = {
    layout_general: 'general',
    layout_calendar: 'calendar',
    layout_email_notify: 'email-notify',
    layout_social_profile: 'social-profile',
    layout_social_template: 'social-template',
    layout_ai: 'ai',
    layout_mcp: 'mcp',
    layout_scheduling_hub: 'advanced-schedule',
    layout_license: 'license',
};

/**
 * Slugs that used to appear in links from the admin menu and the docs. They
 * keep resolving even though they are no longer what we write.
 */
const ALIASES: Record<string, string> = {
    'scheduling-hub': 'layout_scheduling_hub',
    'social-templates': 'layout_social_template',
    'email-notification': 'layout_email_notify',
    'write-with-ai': 'layout_ai',
};

function deriveSlug(id: string): string {
    return id.replace(/^layout_/, '').replace(/_/g, '-');
}

export function slugForTab(id: string): string {
    return TAB_SLUGS[id] || deriveSlug(id);
}

/** Resolves a slug against the tabs that actually exist right now. */
export function tabForSlug(slug: string | null, tabs: any[]): string | null {
    if (!slug) {
        return null;
    }

    const wanted = slug.toLowerCase();
    const ids = (tabs || []).map((tab: any) => tab?.id).filter(Boolean);

    const match = ids.find(
        (id: string) => slugForTab(id) === wanted || deriveSlug(id) === wanted || id === wanted
    );

    if (match) {
        return match;
    }

    const aliased = ALIASES[wanted];

    return aliased && ids.includes(aliased) ? aliased : null;
}

export interface Route {
    tab: string | null;
    section: string | null;
}

export function readRoute(): Route {
    const params = new URLSearchParams(window.location.search);

    return {
        tab: params.get('tab'),
        section: params.get('section'),
    };
}

/**
 * Writes the route without reloading. `replace` is for normalising the URL on
 * first load — only a real tab change should add a history entry, so that Back
 * steps through tabs the user actually visited.
 */
export function writeRoute(route: Partial<Route>, replace = false) {
    const url = new URL(window.location.href);

    if ('tab' in route) {
        if (route.tab) {
            url.searchParams.set('tab', route.tab);
        } else {
            url.searchParams.delete('tab');
        }
    }

    if ('section' in route) {
        if (route.section) {
            url.searchParams.set('section', route.section);
        } else {
            url.searchParams.delete('section');
        }
    }

    if (url.href === window.location.href) {
        return;
    }

    window.history[replace ? 'replaceState' : 'pushState']({}, '', url.href);
}
