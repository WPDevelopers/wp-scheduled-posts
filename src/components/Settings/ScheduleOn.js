import React, { useContext, useEffect, useRef, useState } from 'react';
import { AppContext } from '../../context/AppContext';
const { DateTimePicker, Popover } = wp.components;
const { __ } = wp.i18n;

const formatDateTime = (dateString) => {
    const date = new Date(dateString);
    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
};

const ScheduleOn = () => {
    const { state, dispatch } = useContext(AppContext);
    const [scheduleDate, setScheduleDate] = useState(() => state?.scheduleDate || '');
    const [isOpenScheduleDate, setIsOpenScheduleDate] = useState(false);
    const anchorRef = useRef();

    useEffect(() => {
        const savedDate = state?.scheduleDate || '';
        if (savedDate && !scheduleDate) {
            setScheduleDate(savedDate);
        }
    }, [state?.scheduleDate, scheduleDate]);

    useEffect(() => {
        dispatch({ type: 'SET_SCHEDULE_DATE', payload: scheduleDate || '' });
    }, [scheduleDate, dispatch]);

    return (
        <div className="wpsp-post-panel-modal-settings-schedule">
            <div className="wpsp-post--card">
                <div className="card--title">
                    <h4 className="title">{__('Schedule On', 'wp-scheduled-posts')}</h4>
                </div>
                <div className="wpsp-post-items--wrapper">
                    <div className="wpsp-post--items">
                        <div className="card--title">
                            <h5 className="title">{__('Schedule On', 'wp-scheduled-posts')}</h5>
                        </div>
                        <div className="wpsp-date--picker">
                            <span ref={anchorRef}>
                                <input
                                    onClick={() => setIsOpenScheduleDate(true)}
                                    type="text"
                                    value={scheduleDate ? formatDateTime(scheduleDate) : ''}
                                    placeholder={__('Select date and time', 'wp-scheduled-posts')}
                                    readOnly
                                />
                            </span>

                            {isOpenScheduleDate && (
                                <Popover
                                    anchor={anchorRef.current}
                                    placement="bottom-start"
                                    onClose={() => setIsOpenScheduleDate(false)}
                                >
                                    <DateTimePicker
                                        currentDate={scheduleDate || undefined}
                                        onChange={setScheduleDate}
                                        is12Hour={false}
                                    />
                                </Popover>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ScheduleOn;
