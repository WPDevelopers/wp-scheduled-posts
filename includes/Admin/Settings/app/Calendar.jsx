import React from 'react';
import ReactDOM from 'react-dom';
import CalendarWrapper from './Settings/CalendarWrapper';

document.addEventListener('DOMContentLoaded', function () {
    ReactDOM.render(
      <CalendarWrapper {...window.wpspSettingsGlobal} />,
      document.getElementById("wpsp-dashboard-body")
    );
})
