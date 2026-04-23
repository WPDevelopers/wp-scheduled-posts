import { useContext, useEffect, useRef, useState } from 'react';
import { AppContext } from '../../context/AppContext';
const { DateTimePicker, Popover, Button } = wp.components;
const { __ } = wp.i18n;
const { useSelect } = wp.data;

// ─── Helpers ────────────────────────────────────────────────────────────────

const normalizeDateString = (dateString) => {
    if (!dateString || typeof dateString !== 'string') return '';
    if (dateString.includes(' ') && !dateString.includes('T')) {
        return dateString.replace(' ', 'T');
    }
    return dateString;
};

const formatDateTime = (dateString) => {
    const date = new Date(normalizeDateString(dateString));
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
};

/** Read post date from Classic Editor DOM fields. */
const getClassicEditorDate = () => {
    const pad = (n) => String(n || 0).padStart(2, '0');
    const year  = document.getElementById('hidden_aa')?.value;
    const month = document.getElementById('hidden_mm')?.value;
    const day   = document.getElementById('hidden_jj')?.value;
    const hour  = document.getElementById('hidden_hh')?.value || '0';
    const min   = document.getElementById('hidden_mn')?.value || '0';
    if (year && month && day) {
        return `${year}-${pad(month)}-${pad(day)}T${pad(hour)}:${pad(min)}:00`;
    }
    return '';
};

// ─── Component ───────────────────────────────────────────────────────────────

const ScheduleOn = () => {
    const { state, dispatch } = useContext(AppContext);

    // Read from Gutenberg editor (safe – won't crash in other editors)
    const editorPostDate = useSelect((select) => {
        try {
            const editor = select('core/editor');
            if (editor?.getCurrentPostType?.()) {
                return normalizeDateString(editor.getEditedPostAttribute('date') || '');
            }
        } catch (e) {}
        return '';
    }, []);

    // Initialize date synchronously from state / global vars / Classic Editor DOM
    const [scheduleDate, setScheduleDate] = useState(() => {
        const fromState = normalizeDateString(state?.scheduleDate);
        if (fromState) return fromState;

        const globalDate =
            window?.WPSchedulePostsFree?.current_post_date ||
            window?.WPSchedulePosts?.current_post_date || '';
        if (globalDate) return normalizeDateString(globalDate);

        return getClassicEditorDate();
    });

    const [isOpenScheduleDate, setIsOpenScheduleDate] = useState(false);
    const anchorRef      = useRef();
    const isCleared      = useRef(false);
    const editorHydrated = useRef(false);

    // Hydrate from Gutenberg once it finishes loading (async)
    useEffect(() => {
        if (editorHydrated.current || isCleared.current || scheduleDate || !editorPostDate) return;
        editorHydrated.current = true;
        setScheduleDate(editorPostDate);
    }, [editorPostDate, scheduleDate]);

    // Keep app context in sync so Footer "Save Changes" picks it up
    useEffect(() => {
        dispatch({ type: 'SET_SCHEDULE_DATE', payload: scheduleDate || '' });
        dispatch({ type: 'SET_IS_SCHEDULED',  payload: !!scheduleDate });
    }, [scheduleDate, dispatch]);

    // ─── Render ───────────────────────────────────────────────────────────
    return (
        <div className="wpsp-post-panel-modal-settings-schedule">
            <div className="wpsp-post--card">
                <div className="card--title">
                    <h4 className="title">{__('Schedule On', 'wp-scheduled-posts')}</h4>
                </div>

                <div className="wpsp-post-items--wrapper">
                    <div className="wpsp-post--items">

                        {/* Label + tooltip */}
                        <div className="card--title">
                            <div className="schedule-on-label">
                                <h5 className="title">{__('Schedule On', 'wp-scheduled-posts')}</h5>
                                <div className="schedule-on-tooltip">
                                    <span>
                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="6.99935" cy="6.99935" r="5.83333" stroke="#667085" strokeWidth="1.2" />
                                            <path d="M7 4.08398V7.58398" stroke="#667085" strokeWidth="1.2" strokeLinecap="round" />
                                            <circle cx="6.99935" cy="9.33333" r="0.583333" fill="#667085" />
                                        </svg>
                                    </span>
                                    <div className="info">
                                        <span>{__('Set the date and time to publish or schedule this post.', 'wp-scheduled-posts')}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Date picker input */}
                        <div className="wpsp-date--picker" style={{ position: 'relative' }}>
                            <span ref={anchorRef}>
                                <input
                                    onClick={() => setIsOpenScheduleDate(true)}
                                    type="text"
                                    value={scheduleDate ? formatDateTime(scheduleDate) : ''}
                                    placeholder={__('Select date & time', 'wp-scheduled-posts')}
                                    readOnly
                                />
                            </span>

                            {/* Clear button */}
                            {scheduleDate && (
                                <span
                                    className="wpsp-date-clear-btn"
                                    onClick={() => {
                                        isCleared.current = true;
                                        setScheduleDate('');
                                    }}
                                    title={__('Clear date', 'wp-scheduled-posts')}
                                    style={{ position: 'absolute', right: '10px', top: '50%', transform: 'translateY(-50%)', cursor: 'pointer', display: 'flex', alignItems: 'center' }}
                                >
                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10.5 3.5L3.5 10.5M3.5 3.5L10.5 10.5" stroke="#667085" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round" />
                                    </svg>
                                </span>
                            )}

                            {/* DateTimePicker popover */}
                            {isOpenScheduleDate && (
                                <Popover
                                    anchor={anchorRef.current}
                                    placement="bottom-start"
                                    onClose={() => setIsOpenScheduleDate(false)}
                                >
                                    <div style={{ marginTop: '10px', textAlign: 'right' }}>
                                        <Button
                                            variant="secondary"
                                            isSmall
                                            onClick={() => {
                                                const now = new Date().toISOString();
                                                isCleared.current = false;
                                                setScheduleDate(now);
                                            }}
                                        >
                                            {__('Now', 'wp-scheduled-posts')}
                                        </Button>
                                    </div>
                                    <DateTimePicker
                                        currentDate={scheduleDate || undefined}
                                        onChange={(date) => {
                                            isCleared.current = false;
                                            setScheduleDate(date);
                                        }}
                                        is12Hour={true}
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
