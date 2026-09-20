/**
 * Social platforms that do not ship with the free plugin.
 *
 * Two lists come over from PHP: `social_platforms` is what something is
 * actually serving right now, and `locked_platforms` is what SchedulePress Pro
 * sells but nothing here can do. A slug moves from the second list to the first
 * the moment Pro registers it, so the UI needs no version checks of its own.
 */

const globals = () => (typeof window !== 'undefined' && window.WPSchedulePostsFree) || {};

export const registeredPlatforms = () => globals().social_platforms || {};

export const lockedPlatforms = () => globals().locked_platforms || {};

export const registeredSlugs = () => Object.keys(registeredPlatforms());

export const lockedSlugs = () => Object.keys(lockedPlatforms());

/** Live first, then the ones being sold. */
export const extensionSlugs = () => [...registeredSlugs(), ...lockedSlugs()];

export const platformDefinition = (slug) =>
    registeredPlatforms()[slug] || lockedPlatforms()[slug] || null;

export const isLockedPlatform = (slug) => Boolean(lockedPlatforms()[slug]);

export const platformLabel = (slug) => platformDefinition(slug)?.label || '';

export const platformLimit = (slug) => registeredPlatforms()[slug]?.limit || 0;

/** Settings key holding a live platform's connected profiles. */
export const platformListKey = (slug) => registeredPlatforms()[slug]?.list_key || '';

/** Character limits for every extension platform, keyed by slug. */
export const extensionLimits = () =>
    Object.fromEntries(
        Object.entries(registeredPlatforms()).map(([slug, definition]) => [slug, definition.limit || 0])
    );

/**
 * An <img> stands in for the inline SVGs the built-in networks use, so an
 * extension only has to supply a URL.
 */
export const platformIconUrl = (slug, withBackground = false) => {
    const definition = platformDefinition(slug);
    if (!definition) return '';
    return withBackground ? definition.icon_bg_url || definition.icon_url : definition.icon_url;
};
