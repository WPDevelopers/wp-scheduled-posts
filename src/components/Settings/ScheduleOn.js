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
