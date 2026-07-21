import { __ } from '@wordpress/i18n';
import React from 'react';
import HelpTip from '../controls/HelpTip';
import { WEEKDAYS } from '../defaults';
import { ScheduleSettings, Weekday } from '../types';

type Props = {
    value: ScheduleSettings;
    onChange: (patch: Partial<ScheduleSettings>) => void;
};

const Schedule = ({ value, onChange }: Props) => {
    const setPerDay = (day: Weekday, count: number) =>
        onChange({ per_day: { ...value.per_day, [day]: count } });

    return (
        <div className="rs-edit-group">
            <div className="rs-edit-group-head">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <circle cx="12" cy="12" r="10" />
                    <polyline points="12 6 12 12 16 14" />
                </svg>
                {__('Schedule', 'wp-scheduled-posts')}
                <span className="rs-group-hint">
                    {__('Time slots when this ruleset may publish posts.', 'wp-scheduled-posts')}
                </span>
            </div>
            <div className="rs-edit-group-body">
                <div className="rs-edit-grid cols-3">
                    <div className="rs-field">
                        <label className="rs-field-lbl">
                            {__('Start Time', 'wp-scheduled-posts')}
                            <HelpTip text={__('Default: 12:05 AM', 'wp-scheduled-posts')} />
                        </label>
                        <input
                            className="input"
                            type="time"
                            value={value.start_time}
                            onChange={(e) => onChange({ start_time: e.target.value })}
                        />
                    </div>
                    <div className="rs-field">
                        <label className="rs-field-lbl">
                            {__('End Time', 'wp-scheduled-posts')}
                            <HelpTip text={__('Default: 11:55 PM', 'wp-scheduled-posts')} />
                        </label>
                        <input
                            className="input"
                            type="time"
                            value={value.end_time}
                            onChange={(e) => onChange({ end_time: e.target.value })}
                        />
                    </div>
                    <div className="rs-field">
                        <label className="rs-field-lbl">
                            {__('Interval Between Publications', 'wp-scheduled-posts')}
                            <HelpTip text={__('Time between two consecutive auto-publishes. Format HH:MM (24h).', 'wp-scheduled-posts')} />
                        </label>
                        <input
                            className="input"
                            type="text"
                            placeholder="HH:MM"
                            pattern="[0-9]{1,2}:[0-5][0-9]"
                            value={value.interval}
                            onChange={(e) => onChange({ interval: e.target.value })}
                        />
                    </div>
                </div>

                <div
                    className="rs-edit-group-head"
                    style={{
                        marginTop: 14,
                        border: 0,
                        borderTop: '1px solid var(--line-2)',
                        background: 'transparent',
                        padding: '10px 0 8px',
                    }}>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" />
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                    {__('Posts per Day', 'wp-scheduled-posts')}
                    <span className="rs-group-hint">
                        {__('Set how many posts may be published on each weekday.', 'wp-scheduled-posts')}
                    </span>
                </div>

                <div className="sw-days">
                    {WEEKDAYS.map((day) => {
                        const val = value.per_day[day] || 0;
                        return (
                            <div
                                className={`sw-day ${val > 0 ? 'has-posts' : ''}`}
                                key={day}>
                                <input
                                    className="sw-day-count"
                                    type="number"
                                    min={0}
                                    max={99}
                                    value={val}
                                    onChange={(e) =>
                                        setPerDay(day, Number(e.target.value))
                                    }
                                />
                                <div className="sw-day-hint">
                                    {__('Number of posts', 'wp-scheduled-posts')}
                                </div>
                                <div className="sw-day-name">
                                    {day.charAt(0).toUpperCase() + day.slice(1)}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
};

export default Schedule;
