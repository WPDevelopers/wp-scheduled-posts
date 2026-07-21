import { __ } from '@wordpress/i18n';
import React from 'react';
import DayTimePicker from './controls/DayTimePicker';

/**
 * Manual Scheduler (manual mode body).
 * Phase-1 scaffold: header + Publish Schedule day/time picker. The full
 * Query Settings + Recycling columns land in Phase 2.
 */
const ManualScheduler = () => (
    <div className="mode-settings">
        <header className="ms-head">
            <span className="ms-head-ic">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M12 2v6" />
                    <circle cx="12" cy="14" r="3" />
                </svg>
            </span>
            <div className="ms-head-text">
                <div className="ms-head-title">
                    {__('Manual Scheduler', 'wp-scheduled-posts')}
                </div>
                <div className="ms-head-sub">
                    {__('Choose which posts manual scheduling applies to, then build a schedule per day of the week.', 'wp-scheduled-posts')}
                </div>
            </div>
        </header>

        <div className="ms-body">
            <section className="rs-subsection">
                <div className="rs-subsection-head">
                    <h5 className="rs-subsection-title">
                        {__('Publish Schedule', 'wp-scheduled-posts')}
                    </h5>
                    <p className="rs-subsection-hint" style={{ marginLeft: 'auto' }}>
                        {__('Add publish times per day of the week.', 'wp-scheduled-posts')}
                    </p>
                </div>
                <DayTimePicker />
            </section>
        </div>
    </div>
);

export default ManualScheduler;
