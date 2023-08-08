import React from 'react';
import ReactDOM from 'react-dom';
import { addFilter } from '@wordpress/hooks'
import Sidebar from './Settings/Sidebar';
import CalendarWrapper from './Settings/CalendarWrapper';
document.addEventListener('DOMContentLoaded', function () {
    addFilter('wprf_tab_content', 'SchedulePress', (x, props) => {
        return <Sidebar props={props} />
    })

    ReactDOM.render(
      <CalendarWrapper {...window.wpspSettingsCalendar} />,
      document.getElementById("wpsp-dashboard-body")
    );
})
