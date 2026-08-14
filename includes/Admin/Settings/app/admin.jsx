import React from 'react';
import ReactDOM from 'react-dom';
import SettingWrapper from './Settings/SettingsWrapper';

document.addEventListener('DOMContentLoaded', function () {
    ReactDOM.render(
        <SettingWrapper wpspObject={window.wpspSettingsGlobal} />,
        document.getElementById('wpsp-dashboard-body')
    )
})
