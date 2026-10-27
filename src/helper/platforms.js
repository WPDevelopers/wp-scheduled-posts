import React from 'react';

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
 * extension only has to supply a URL. The plain icon is the white glyph the
 * built-ins draw on their round buttons; with a background it is the small
 * colour logo. `icon_url` is a full-size logo.
 */
export const platformIconUrl = (slug, withBackground = false) => {
    const definition = platformDefinition(slug);
    if (!definition) return '';
    return withBackground
        ? definition.icon_bg_url || definition.icon_url
        : definition.icon_glyph_url || definition.icon_small_white_url || definition.icon_url;
};

/**
 * The <img> for an extension icon, sized like the built-in SVGs: the 12px
 * glyph, or the 20px round badge the `*WithBG` icons are. Without a size a
 * logo renders at its own size, which for Google Business is 2500x2185 and
 * covers the whole panel.
 */
export const platformIconImg = (slug, round = false, alt = '') => {
    const definition = platformDefinition(slug) || {};
    const src = round ? definition.icon_round_url || platformIconUrl(slug, true) : platformIconUrl(slug);
    const size = round ? 20 : 12;
    return <img src={src} alt={alt} width={size} height={size} style={{ objectFit: 'contain' }} />;
};

/**
 * Built-in networks with the extension ones slotted in after Threads, where
 * Google Business sat before it moved to Pro.
 */
export const withExtensionSlugs = (builtins, slugs = extensionSlugs()) => {
    const extra = slugs.filter((slug) => !builtins.includes(slug));
    const at = builtins.indexOf('threads') + 1 || builtins.length;
    return [...builtins.slice(0, at), ...extra, ...builtins.slice(at)];
};
