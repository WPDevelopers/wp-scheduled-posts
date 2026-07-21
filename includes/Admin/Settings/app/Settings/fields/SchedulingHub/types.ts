// Scheduling Hub — shared types (Phase 1: UI-only scaffold)
// See docs: wp-scheduled-posts-pro/docs/specs/auto-scheduler-rulesets.md

export type RulesetStatus = 'enabled' | 'disabled';

export type QuerySettings = {
    post_types: string[];
    post_statuses: string[];
    // Taxonomy term filter (value format: `postType.taxonomy.slug`) — feature #4.
    taxonomies: string[];
    min_post_age: number;
    max_post_age: number;
    max_posts_per_query: number;
    order: string;
    ignore_sticky: boolean;
};

export type RecyclingSettings = {
    min_recycle_age: number;
    max_recycle_age: number;
    mode: string;
    max_times: number;
};

export type ScheduleSettings = {
    start_time: string;
    end_time: string;
    interval: string;
    per_day: Record<Weekday, number>;
};

export type Ruleset = {
    id: number;
    name: string;
    status: RulesetStatus;
    query: QuerySettings;
    recycling: RecyclingSettings;
    schedule: ScheduleSettings;
};

export type Weekday =
    | 'sunday'
    | 'monday'
    | 'tuesday'
    | 'wednesday'
    | 'thursday'
    | 'friday'
    | 'saturday';

export type ScheduleMode = 'auto' | 'manual';
