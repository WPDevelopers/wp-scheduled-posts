import { __ } from '@wordpress/i18n';
import React from 'react';
import ChipMultiSelect, { Option } from '../controls/ChipMultiSelect';
import HelpTip from '../controls/HelpTip';
import NumberWithSuffix from '../controls/NumberWithSuffix';
import TaxonomyFilter from '../controls/TaxonomyFilter';
import { ORDER_OF_QUERY_OPTIONS } from '../defaults';
import { QuerySettings as QuerySettingsType } from '../types';

type Props = {
    value: QuerySettingsType;
    onChange: (patch: Partial<QuerySettingsType>) => void;
};

// @ts-ignore — localized by PHP (Assets.php)
const globalData: any = typeof wpspSettingsGlobal !== 'undefined' ? wpspSettingsGlobal : {};
const POST_TYPE_OPTIONS: Option[] = globalData?.post_types ?? [];
const POST_STATUS_OPTIONS: Option[] = globalData?.post_statuses ?? [];

const QuerySettings = ({ value, onChange }: Props) => (
    <div className="rs-edit-group">
        <div className="rs-edit-group-head">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
            </svg>
            {__('Query Settings', 'wp-scheduled-posts')}
            <span className="rs-group-hint">
                {__('Filter which posts this ruleset applies to.', 'wp-scheduled-posts')}
            </span>
        </div>
        <div className="rs-edit-group-body">
            <div className="rs-edit-grid">
                <div className="rs-field">
                    <label className="rs-field-lbl">
                        {__('Post Type', 'wp-scheduled-posts')}
                        <HelpTip text={__('Limit this ruleset to the selected post types.', 'wp-scheduled-posts')} />
                    </label>
                    <ChipMultiSelect
                        options={POST_TYPE_OPTIONS}
                        values={value.post_types}
                        placeholder={__('Search & select post types…', 'wp-scheduled-posts')}
                        onChange={(post_types) => onChange({ post_types })}
                    />
                </div>
                <div className="rs-field">
                    <label className="rs-field-lbl">
                        {__('Post Status', 'wp-scheduled-posts')}
                        <HelpTip text={__('Limit this ruleset to posts in the selected statuses.', 'wp-scheduled-posts')} />
                    </label>
                    <ChipMultiSelect
                        options={POST_STATUS_OPTIONS}
                        values={value.post_statuses}
                        placeholder={__('Search & select statuses…', 'wp-scheduled-posts')}
                        onChange={(post_statuses) => onChange({ post_statuses })}
                    />
                </div>
            </div>

            <div className="rs-field" style={{ marginTop: 12 }}>
                <label className="rs-field-lbl">
                    {__('Taxonomy Filter', 'wp-scheduled-posts')}
                    <HelpTip text={__('Limit this ruleset to posts in the selected categories, tags or custom taxonomy terms. Leave empty for no taxonomy filter.', 'wp-scheduled-posts')} />
                </label>
                <TaxonomyFilter
                    values={value.taxonomies}
                    onChange={(taxonomies) => onChange({ taxonomies })}
                />
            </div>

            <div className="rs-edit-grid cols-3" style={{ marginTop: 12 }}>
                <div className="rs-field">
                    <label className="rs-field-lbl">
                        {__('Min Post Age', 'wp-scheduled-posts')}
                        <HelpTip text={__('Posts must be older than this number of days. 0 to ignore.', 'wp-scheduled-posts')} />
                    </label>
                    <NumberWithSuffix
                        value={value.min_post_age}
                        suffix={__('days', 'wp-scheduled-posts')}
                        onChange={(min_post_age) => onChange({ min_post_age })}
                    />
                </div>
                <div className="rs-field">
                    <label className="rs-field-lbl">
                        {__('Max Post Age', 'wp-scheduled-posts')}
                        <HelpTip text={__('Posts must be newer than this number of days. 0 to ignore.', 'wp-scheduled-posts')} />
                    </label>
                    <NumberWithSuffix
                        value={value.max_post_age}
                        suffix={__('days', 'wp-scheduled-posts')}
                        onChange={(max_post_age) => onChange({ max_post_age })}
                    />
                </div>
                <div className="rs-field">
                    <label className="rs-field-lbl">
                        {__('Max Posts per Query', 'wp-scheduled-posts')}
                        <HelpTip text={__('Max number of posts to query during each ruleset check.', 'wp-scheduled-posts')} />
                    </label>
                    <input
                        className="input"
                        type="number"
                        min={1}
                        max={500}
                        value={value.max_posts_per_query}
                        onChange={(e) =>
                            onChange({ max_posts_per_query: Number(e.target.value) })
                        }
                    />
                </div>
            </div>

            <div className="rs-field" style={{ marginTop: 12 }}>
                <label className="rs-field-lbl">
                    {__('Order of Query', 'wp-scheduled-posts')}
                    <HelpTip text={__('How eligible posts are ordered. Determines which post wins when more than one matches.', 'wp-scheduled-posts')} />
                </label>
                <select
                    className="select"
                    value={value.order}
                    onChange={(e) => onChange({ order: e.target.value })}>
                    {ORDER_OF_QUERY_OPTIONS.map((o) => (
                        <option key={o.value} value={o.value}>
                            {o.label}
                        </option>
                    ))}
                </select>
            </div>

            <div className="rs-field rs-field-inline" style={{ marginTop: 12 }}>
                <div>
                    <label className="rs-field-lbl">
                        {__('Ignore Sticky Posts', 'wp-scheduled-posts')}
                        <HelpTip text={__('Skip posts pinned to the front page.', 'wp-scheduled-posts')} />
                    </label>
                    <div className="rs-field-hint">
                        {__('Excludes WordPress sticky posts from queries.', 'wp-scheduled-posts')}
                    </div>
                </div>
                <label className="toggle">
                    <input
                        type="checkbox"
                        checked={value.ignore_sticky}
                        onChange={(e) => onChange({ ignore_sticky: e.target.checked })}
                    />
                    <span className="toggle-slider" />
                </label>
            </div>
        </div>
    </div>
);

export default QuerySettings;
