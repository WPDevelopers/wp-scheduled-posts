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
                        <div className="card--title d-flex">
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
                            <div className="wpsp-reset-schedule-on">
                                <button onClick={() => setScheduleDate('')}>
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9.62772 0.00525906C9.66757 0.00533738 9.70743 0.0054157 9.7485 0.0054964C10.3234 0.00739947 10.8823 0.0238844 11.4458 0.147593C11.4824 0.155195 11.519 0.162797 11.5567 0.17063C12.8342 0.438902 14.0762 0.930878 15.1411 1.69447C15.167 1.71278 15.1928 1.73109 15.2194 1.74996C15.3057 1.81141 15.3913 1.87366 15.4768 1.93617C15.5149 1.96349 15.5149 1.96349 15.5538 1.99136C15.8835 2.23679 16.1756 2.52909 16.4732 2.81166C16.4733 2.78037 16.4735 2.74909 16.4736 2.71686C16.4753 2.42161 16.4777 2.12639 16.4811 1.83116C16.4828 1.67938 16.4842 1.52762 16.4848 1.37584C16.4856 1.20123 16.4877 1.02665 16.49 0.852046C16.49 0.797701 16.4901 0.743356 16.4901 0.687365C16.491 0.636626 16.4918 0.585886 16.4927 0.533609C16.4931 0.489049 16.4934 0.44449 16.4938 0.39858C16.5218 0.245644 16.5817 0.172977 16.688 0.0616555C16.8477 -0.0182042 17.0297 -0.00619574 17.2036 0.0186868C17.3474 0.127259 17.4047 0.192252 17.4615 0.362437C17.466 0.48616 17.4682 0.608859 17.4684 0.732602C17.4686 0.770413 17.4688 0.808225 17.469 0.847183C17.4697 0.972395 17.4699 1.09761 17.4702 1.22282C17.4704 1.30979 17.4707 1.39675 17.4709 1.48372C17.4714 1.66613 17.4716 1.84855 17.4718 2.03096C17.472 2.2647 17.473 2.49843 17.4743 2.73216C17.4751 2.91183 17.4753 3.09149 17.4754 3.27116C17.4755 3.35733 17.4758 3.4435 17.4764 3.52967C17.4771 3.65023 17.477 3.77075 17.4768 3.89131C17.4772 3.9269 17.4776 3.96248 17.478 3.99915C17.4762 4.24379 17.4304 4.37977 17.2627 4.56423C17.1036 4.64548 16.95 4.63463 16.7738 4.6337C16.7347 4.634 16.6956 4.63429 16.6554 4.6346C16.5262 4.63538 16.3971 4.63522 16.2679 4.63497C16.1781 4.63517 16.0883 4.6354 15.9985 4.63566C15.8103 4.63604 15.6221 4.63594 15.4338 4.63551C15.1926 4.63502 14.9514 4.63588 14.7102 4.63714C14.5248 4.63793 14.3393 4.63791 14.1539 4.63767C14.065 4.63766 13.976 4.63792 13.8871 4.63846C13.7627 4.63909 13.6384 4.63864 13.514 4.6379C13.4773 4.63832 13.4406 4.63873 13.4028 4.63916C13.2289 4.637 13.1431 4.63008 12.9932 4.53409C12.8694 4.40567 12.8278 4.32621 12.8047 4.14906C12.8252 3.97848 12.8866 3.89037 12.9927 3.75697C13.1441 3.65603 13.2092 3.65945 13.3887 3.65733C13.4437 3.65648 13.4988 3.65562 13.5555 3.65474C13.6156 3.65423 13.6757 3.65373 13.7358 3.65324C13.7972 3.65243 13.8587 3.65159 13.9202 3.65072C14.0819 3.64851 14.2436 3.64681 14.4053 3.64521C14.5703 3.64349 14.7353 3.6413 14.9004 3.63914C15.2241 3.63498 15.5479 3.63135 15.8716 3.62806C15.8239 3.57995 15.7761 3.53189 15.7283 3.48385C15.7017 3.45708 15.6751 3.43031 15.6477 3.40273C15.4765 3.23465 15.2858 3.08965 15.0982 2.94056C15.0366 2.89005 15.0366 2.89005 14.9738 2.83851C13.2035 1.42043 10.8885 0.824519 8.64783 1.06068C7.21094 1.2317 5.83392 1.75253 4.65677 2.59681C4.63156 2.61485 4.60635 2.6329 4.58038 2.65149C4.22478 2.90921 3.89976 3.19636 3.58255 3.49916C3.54588 3.53355 3.50921 3.56795 3.47143 3.60339C3.17559 3.8885 2.92304 4.20001 2.68021 4.53041C2.66227 4.5548 2.64433 4.5792 2.62584 4.60434C1.2938 6.44521 0.774314 8.76555 1.10715 11.0026C1.48231 13.2566 2.70802 15.322 4.5734 16.656C5.68453 17.4375 6.92473 17.965 8.26614 18.1945C8.34283 18.2089 8.34283 18.2089 8.42107 18.2237C9.34159 18.3702 10.3625 18.334 11.274 18.1515C11.3347 18.1394 11.3347 18.1394 11.3966 18.127C13.0424 17.7842 14.4899 16.9881 15.6997 15.8312C15.7396 15.7936 15.7794 15.7561 15.8204 15.7174C17.3832 14.2055 18.2301 12.0341 18.2967 9.88001C18.2968 9.84687 18.2969 9.81373 18.297 9.77959C18.302 9.55849 18.3469 9.4327 18.4927 9.25697C18.6518 9.15093 18.7808 9.14178 18.9654 9.17103C19.2017 9.29994 19.2017 9.29994 19.2661 9.42884C19.4318 11.7736 18.5093 14.1568 16.9888 15.9171C16.9553 15.9562 16.9218 15.9953 16.8873 16.0356C16.7942 16.1415 16.699 16.2445 16.6021 16.3468C16.582 16.3681 16.5618 16.3894 16.5411 16.4113C16.1998 16.7699 15.8389 17.083 15.4419 17.3781C15.418 17.3963 15.394 17.4145 15.3693 17.4332C14.9586 17.7451 14.5244 18.0001 14.0669 18.2374C14.0187 18.2626 14.0187 18.2626 13.9694 18.2883C12.6238 18.9816 11.1605 19.2884 9.65457 19.2821C9.61466 19.282 9.57474 19.2819 9.53362 19.2818C8.9581 19.2797 8.40048 19.2632 7.83646 19.1398C7.77526 19.1273 7.77526 19.1273 7.71284 19.1146C5.46101 18.6514 3.33302 17.384 1.96054 15.5175C1.90688 15.4446 1.85225 15.3725 1.7975 15.3005C1.50702 14.9123 1.27041 14.4994 1.04739 14.0695C1.02221 14.0212 1.02221 14.0212 0.996514 13.972C0.306719 12.6332 -0.00347904 11.1722 2.9417e-05 9.67323C6.70645e-05 9.63373 0.000104712 9.59423 0.0001435 9.55353C0.00658322 7.49619 0.642814 5.48161 1.90677 3.84291C1.93812 3.8017 1.96947 3.76049 2.00177 3.71803C3.58536 1.69003 5.8999 0.368791 8.45637 0.0457514C8.84575 0.0018899 9.23636 0.00366229 9.62772 0.00525906Z" fill="black"/>
                                    </svg>
                                </button>
                            </div>
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
