import 'quickbuilder/dist/index.css';
import React from 'react';
import '../assets/sass/index.scss';
// import '../assets/sass/utils/_common.scss';

import Header from './Header';
import Calendar from './fields/Calendar';
const CalendarWrapper = (props) => {
    const urlParams = new URLSearchParams(window.location.search);
    const page      = urlParams.get('page');
    const postType  = page.replace('schedulepress-', '');

    const post_types = props.post_types.filter((post_type) => post_type.value === postType);

    return (
        <>
            <Header image_path={props?.image_path} />
            <div className='wpsp-admin-wrapper'>
                <div className='wpsp-admin-content'>
                    <div className='wprf-tab-layout_calendar'>
                        <div id="calendar_section" className='calendar_section'>
                            <div className='wprf-section-fields'>
                                <Calendar {...props} post_types={post_types} disablePostType={true} postType={postType} />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    )
}
export default CalendarWrapper;