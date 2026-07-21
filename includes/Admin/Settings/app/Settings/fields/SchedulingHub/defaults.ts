// Scheduling Hub — option lists, defaults & factory helpers (Phase 1)
import { Ruleset, Weekday } from './types';

export const WEEKDAYS: Weekday[] = [
    'sunday',
    'monday',
    'tuesday',
    'wednesday',
    'thursday',
    'friday',
    'saturday',
];

export const ORDER_OF_QUERY_OPTIONS = [
    { value: 'oldest', label: 'Oldest Posts' },
    { value: 'newest', label: 'Newest Posts' },
    { value: 'random', label: 'Random' },
    { value: 'most_comments', label: 'Most Comments' },
    { value: 'least_comments', label: 'Least Comments' },
    { value: 'az', label: 'Alphabetical (A→Z)' },
    { value: 'za', label: 'Alphabetical (Z→A)' },
];

export const RECYCLE_MODE_OPTIONS = [
    { value: 'oldest', label: 'Oldest Posts' },
    { value: 'newest', label: 'Newest Posts' },
    { value: 'random', label: 'Random' },
    { value: 'least_recycled', label: 'Least Recently Recycled' },
];

export const emptyPerDay = (): Record<Weekday, number> =>
    WEEKDAYS.reduce((acc, day) => {
        acc[day] = 0;
        return acc;
    }, {} as Record<Weekday, number>);

/** Build a fresh, blank ruleset with sensible defaults (matches the design). */
export const blankRuleset = (nextId: number): Ruleset => ({
    id: nextId,
    name: 'New Ruleset',
    status: 'disabled',
    query: {
        post_types: ['post'],
        post_statuses: ['publish'],
        taxonomies: [],
        min_post_age: 0,
        max_post_age: 0,
        max_posts_per_query: 1,
        order: 'oldest',
        ignore_sticky: true,
    },
    recycling: {
        min_recycle_age: 90,
        max_recycle_age: 0,
        mode: 'oldest',
        max_times: 0,
    },
    schedule: {
        start_time: '00:05',
        end_time: '23:55',
        interval: '12:30',
        per_day: emptyPerDay(),
    },
});

/** Next monotonic id given the current list. */
export const nextRulesetId = (rulesets: Ruleset[]): number =>
    (rulesets.reduce((max, r) => Math.max(max, r.id), 0) || 0) + 1;

/**
 * Derived frequency badge from a ruleset's schedule (placeholder heuristic).
 * Real derivation lands in Phase 2 with the engine.
 */
export const deriveFrequency = (ruleset: Ruleset): string => {
    const total = WEEKDAYS.reduce(
        (sum, d) => sum + (Number(ruleset.schedule.per_day[d]) || 0),
        0
    );
    return total > 0 ? `${total}/wk` : ruleset.schedule.interval || '—';
};
