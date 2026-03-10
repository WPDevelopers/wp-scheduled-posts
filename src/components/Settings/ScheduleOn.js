import React, { useContext, useEffect, useRef, useState } from 'react';
import { AppContext } from '../../context/AppContext';
const { DateTimePicker, Popover } = wp.components;
const { __ } = wp.i18n;
const { useSelect } = wp.data;

const normalizeDateString = (dateString) => {
    if (!dateString || typeof dateString !== 'string') {
        return '';
    }

    // Convert MySQL datetime to ISO-like string for DateTimePicker compatibility.
    if (dateString.includes(' ') && !dateString.includes('T')) {
        return dateString.replace(' ', 'T');
    }

    return dateString;
};

const formatDateTime = (dateString) => {
    const date = new Date(normalizeDateString(dateString));
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
    const editorScheduleData = useSelect((select) => {
        const editor = select('core/editor');
        if (!editor) {
            return { date: '', status: '' };
        }

        return {
            date: editor.getEditedPostAttribute('date') || '',
            status: editor.getEditedPostAttribute('status') || '',
        };
    }, []);
    const globalPostStatus = window?.WPSchedulePostsFree?.current_post_status || window?.WPSchedulePosts?.current_post_status || '';
    const globalPostDate = window?.WPSchedulePostsFree?.current_post_date || window?.WPSchedulePosts?.current_post_date || '';
    const globalScheduledDate = globalPostStatus === 'future' ? normalizeDateString(globalPostDate) : '';
    const editorScheduledDate = editorScheduleData?.status === 'future' ? normalizeDateString(editorScheduleData?.date) : '';
    const initialScheduleDate = normalizeDateString(state?.scheduleDate) || editorScheduledDate || globalScheduledDate || '';

    const [scheduleDate, setScheduleDate] = useState(() => initialScheduleDate);
    const [isOpenScheduleDate, setIsOpenScheduleDate] = useState(false);
    const anchorRef = useRef();

    useEffect(() => {
        const savedDate = normalizeDateString(state?.scheduleDate) || editorScheduledDate || globalScheduledDate || '';
        if (savedDate && !scheduleDate) {
            setScheduleDate(savedDate);
        }
    }, [state?.scheduleDate, editorScheduledDate, globalScheduledDate, scheduleDate]);

    useEffect(() => {
        dispatch({ type: 'SET_SCHEDULE_DATE', payload: scheduleDate || '' });
        dispatch({ type: 'SET_IS_SCHEDULED', payload: !!scheduleDate });
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
                            <div className="schedule-on-label">
                                <h5 className="title">{__('Schedule On', 'wp-scheduled-posts')}</h5>
                                <div className="schedule-on-tooltip">
                                    <span>
                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="6.99935" cy="6.99935" r="5.83333" stroke="#667085" strokeWidth="1.2" />
                                            <path d="M7 4.08398V7.58398" stroke="#667085" strokeWidth="1.2" stroke-linecap="round" />
                                            <circle cx="6.99935" cy="9.33333" r="0.583333" fill="#667085" />
                                        </svg>
                                    </span>
                                    <div className="info">
                                        <span>The post is scheduled to be published on this date and time with the updated content.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="wpsp-date--picker">
                            <span ref={anchorRef}>
                                <input
                                    onClick={() => setIsOpenScheduleDate(true)}
                                    type="text"
                                    value={scheduleDate ? formatDateTime(scheduleDate) : ''}
                                    placeholder={__('Y/M/D H:M:S', 'wp-scheduled-posts')}
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
