import { __ } from '@wordpress/i18n';
import React from 'react';
import HelpTip from '../controls/HelpTip';
import NumberWithSuffix from '../controls/NumberWithSuffix';
import { RECYCLE_MODE_OPTIONS } from '../defaults';
import { RecyclingSettings } from '../types';

type Props = {
    value: RecyclingSettings;
    onChange: (patch: Partial<RecyclingSettings>) => void;
};

const Recycling = ({ value, onChange }: Props) => (
    <div className="rs-edit-group">
        <div className="rs-edit-group-head">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                <polyline points="23 4 23 10 17 10" />
                <polyline points="1 20 1 14 7 14" />
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10" />
                <path d="M20.49 15a9 9 0 0 1-14.85 3.36L1 14" />
            </svg>
            {__('Recycling', 'wp-scheduled-posts')}
            <span className="rs-group-hint">
                {__('Republish old content if not enough eligible posts are found.', 'wp-scheduled-posts')}
            </span>
        </div>
        <div className="rs-edit-group-body">
            <div className="rs-edit-grid cols-4">
                <div className="rs-field">
                    <label className="rs-field-lbl">
                        {__('Min Recycle Age', 'wp-scheduled-posts')}
                        <HelpTip text={__('Posts must be older than this number of days to be recyclable.', 'wp-scheduled-posts')} />
                    </label>
                    <NumberWithSuffix
                        value={value.min_recycle_age}
                        suffix={__('days', 'wp-scheduled-posts')}
                        onChange={(min_recycle_age) => onChange({ min_recycle_age })}
                    />
                </div>
                <div className="rs-field">
                    <label className="rs-field-lbl">
                        {__('Max Recycle Age', 'wp-scheduled-posts')}
                        <HelpTip text={__('Posts must be newer than this number of days to be recyclable.', 'wp-scheduled-posts')} />
                    </label>
                    <NumberWithSuffix
                        value={value.max_recycle_age}
                        suffix={__('days', 'wp-scheduled-posts')}
                        onChange={(max_recycle_age) => onChange({ max_recycle_age })}
                    />
                </div>
                <div className="rs-field">
                    <label className="rs-field-lbl">
                        {__('Recycle Mode', 'wp-scheduled-posts')}
                        <HelpTip text={__('How to choose which post to republish.', 'wp-scheduled-posts')} />
                    </label>
                    <select
                        className="select"
                        value={value.mode}
                        onChange={(e) => onChange({ mode: e.target.value })}>
                        {RECYCLE_MODE_OPTIONS.map((o) => (
                            <option key={o.value} value={o.value}>
                                {o.label}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="rs-field">
                    <label className="rs-field-lbl">
                        {__('Max Times to Recycle', 'wp-scheduled-posts')}
                        <HelpTip text={__('Maximum times to recycle each post. 0 = unlimited.', 'wp-scheduled-posts')} />
                    </label>
                    <NumberWithSuffix
                        value={value.max_times}
                        suffix={__('times', 'wp-scheduled-posts')}
                        onChange={(max_times) => onChange({ max_times })}
                    />
                </div>
            </div>
        </div>
    </div>
);

export default Recycling;
