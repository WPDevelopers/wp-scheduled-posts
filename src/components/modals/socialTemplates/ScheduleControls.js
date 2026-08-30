import React, { memo } from 'react';
const { __ } = wp.i18n;

const ScheduleControls = ({
    scheduleData,
    onUpdateSchedule,
    dateOptions,
    timeOptions
}) => {
    const isEnabled = !!scheduleData.enabled;

    return (
        <div className="wpsp-date-time-section">
            {/* Scheduling is opt-in: without this the date/time controls silently
                queued a second share every time a caption was saved. */}
            <div className="wpsp-schedule-toggle">
                <label>
                    <input
                        type="checkbox"
                        checked={isEnabled}
                        onChange={e => onUpdateSchedule('enabled', e.target.checked)}
                    />
                    {__('Schedule this social share for later', 'wp-scheduled-posts')}
                </label>
                <p className="wpsp-schedule-toggle__help">
                    {isEnabled
                        ? __('The caption will be shared automatically at the time chosen below.', 'wp-scheduled-posts')
                        : __('Off — the caption is saved only. Nothing is shared until you publish the post or use Share Now.', 'wp-scheduled-posts')}
                </p>
            </div>

            <div hidden={!isEnabled} aria-hidden={!isEnabled}>
                {/* Date Field */}
                <div>
                    <label>{__('Date', 'wp-scheduled-posts')}</label>
                    <select
                        value={scheduleData.dateOption}
                        disabled={!isEnabled}
                        onChange={e => onUpdateSchedule('dateOption', e.target.value)}
                    >
                        {dateOptions.map(opt => (
                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                        ))}
                    </select>
                </div>

                {/* Custom Date Input */}
                {scheduleData.dateOption === 'custom_date' && (
                    <div>
                        <label>{__('Custom Date', 'wp-scheduled-posts')}</label>
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
                        <label>{__('Days', 'wp-scheduled-posts')}</label>
                        <input
                            type="number"
                            min="1"
                            value={scheduleData.customDays}
                            onChange={e => onUpdateSchedule('customDays', e.target.value)}
                        />
                    </div>
                )}

                {/* Time Field */}
                <div>
                    <label>{__('Time', 'wp-scheduled-posts')}</label>
                    <select
                        value={scheduleData.timeOption}
                        disabled={!isEnabled}
                        onChange={e => onUpdateSchedule('timeOption', e.target.value)}
                    >
                        {timeOptions.map(opt => (
                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                        ))}
                    </select>
                </div>

                {/* Custom Time Input */}
                {scheduleData.timeOption === 'custom_time' && (
                    <div>
                        <label>{__('Custom Time', 'wp-scheduled-posts')}</label>
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
                        <label>{__('Hours', 'wp-scheduled-posts')}</label>
                        <input
                            type="number"
                            min="1"
                            value={scheduleData.customHours}
                            onChange={e => onUpdateSchedule('customHours', e.target.value)}
                        />
                    </div>
                )}
            </div>
        </div>
    );
};

export default memo(ScheduleControls);
