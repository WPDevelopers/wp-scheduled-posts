import React, { memo } from 'react';
import { __ } from '@wordpress/i18n';

const ScheduleControls = ({
    scheduleData,
    onUpdateSchedule,
    dateOptions,
    timeOptions
}) => {
    return (
        <div className="wpsp-date-time-section" style={{ marginBottom: '1.5em', marginTop: '1.5em' }}>
            <div style={{ display: 'flex', gap: '1.5em', alignItems: 'flex-end', flexWrap: 'wrap' }}>
                {/* Date Field */}
                <div>
                    <label style={{ fontWeight: 600, display: 'block', marginBottom: 4 }}>{__('Date', 'wp-scheduled-posts')}</label>
                    <select
                        value={scheduleData.dateOption}
                        onChange={e => onUpdateSchedule('dateOption', e.target.value)}
                        style={{ maxWidth: '200px' }}
                    >
                        {dateOptions.map(opt => (
                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                        ))}
                    </select>
                </div>

                {/* Custom Date Input */}
                {scheduleData.dateOption === 'custom_date' && (
                    <div>
                        <label style={{ fontWeight: 600, display: 'block', marginBottom: 4 }}>{__('Custom Date', 'wp-scheduled-posts')}</label>
                        <input
                            type="date"
                            value={scheduleData.customDate}
                            onChange={e => onUpdateSchedule('customDate', e.target.value)}
                        />
                    </div>
                )}

                {/* Custom Days Input */}
                {(scheduleData.dateOption === 'in_days' || scheduleData.dateOption === 'days_after') && (
                    <div>
                        <label style={{ fontWeight: 600, display: 'block', marginBottom: 4 }}>{__('Days', 'wp-scheduled-posts')}</label>
                        <input
                            type="number"
                            min="1"
                            value={scheduleData.customDays}
                            onChange={e => onUpdateSchedule('customDays', e.target.value)}
                            style={{ maxWidth: '80px' }}
                        />
                    </div>
                )}

                {/* Time Field */}
                <div>
                    <label style={{ fontWeight: 600, display: 'block', marginBottom: 4 }}>{__('Time', 'wp-scheduled-posts')}</label>
                    <select
                        value={scheduleData.timeOption}
                        onChange={e => onUpdateSchedule('timeOption', e.target.value)}
                        style={{ maxWidth: '200px' }}
                    >
                        {timeOptions.map(opt => (
                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                        ))}
                    </select>
                </div>

                {/* Custom Time Input */}
                {scheduleData.timeOption === 'custom_time' && (
                    <div>
                        <label style={{ fontWeight: 600, display: 'block', marginBottom: 4 }}>{__('Custom Time', 'wp-scheduled-posts')}</label>
                        <input
                            type="time"
                            value={scheduleData.customTime}
                            onChange={e => onUpdateSchedule('customTime', e.target.value)}
                        />
                    </div>
                )}

                {/* Custom Hours Input */}
                {(scheduleData.timeOption === 'in_hours' || scheduleData.timeOption === 'hours_after') && (
                    <div>
                        <label style={{ fontWeight: 600, display: 'block', marginBottom: 4 }}>{__('Hours', 'wp-scheduled-posts')}</label>
                        <input
                            type="number"
                            min="1"
                            value={scheduleData.customHours}
                            onChange={e => onUpdateSchedule('customHours', e.target.value)}
                            style={{ maxWidth: '80px' }}
                        />
                    </div>
                )}
            </div>
        </div>
    );
};

export default memo(ScheduleControls);
