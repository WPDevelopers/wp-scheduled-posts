import React from 'react';
import '../assets/sass/index.scss';
import '../assets/css/tailwind.css';

import TopBar from './shell/TopBar';
import Calendar from './fields/Calendar';

/**
 * Standalone per-post-type calendar page. It has no builder context — the
 * calendar field talks to its own REST route — so it only borrows the shell's
 * top bar for continuity with the settings screens.
 */
const CalendarWrapper = (props) => {
    const urlParams = new URLSearchParams(window.location.search);
    const page      = urlParams.get('page');
    const postType  = page.replace('schedulepress-', '');

    const post_types = props.post_types.filter((post_type) => post_type.value === postType);

    return (
        <div className="tw-min-h-screen tw-bg-canvas tw-font-sans tw-text-ink">
            <TopBar imagePath={props?.image_path} saveState="idle" />

            <div className="tw-px-5 tw-py-6 lg:tw-px-8 lg:tw-py-8">
                <div className='wprf-tab-layout_calendar'>
                    <div id="calendar_section" className='calendar_section'>
                        <div className='wprf-section-fields'>
                            <Calendar {...props} _post_types={props.post_types} post_types={post_types} disablePostType={true} postType={postType} />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}
export default CalendarWrapper;
