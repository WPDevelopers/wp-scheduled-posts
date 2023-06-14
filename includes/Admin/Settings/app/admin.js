import React from 'react';
import ReactDOM from 'react-dom';
import { addFilter } from '@wordpress/hooks'
import SettingWrapper from './Settings/SettingsWrapper';
import Sidebar from './Settings/Sidebar';
import Field from "./Settings/fields/Field";

document.addEventListener('DOMContentLoaded', function () {
    addFilter('wprf_tab_content', 'SchedulePress', (x, props) => {
        return <Sidebar props={props} />
    })
    addFilter('custom_field', 'SchedulePress', Field);
    ReactDOM.render(
        <SettingWrapper wpspObject={window.wpspSettingsGlobal} />,
        document.getElementById('wpsp-dashboard-body')
    )
})
