import React, { useContext } from 'react';
import { applyFilters } from '@wordpress/hooks'
import SchedulingOptions from '../Settings/SchedulingOptions';
import { AppContext } from '../../context/AppContext';
import ManageSchedule from '../Settings/ManageSchedule';

const Settings = () => {
    const { state, dispatch } = useContext(AppContext);
    return (
        <div className="wpsp-post-panel-modal-settings">
            <h2>Scheduling Settings</h2>
            {wp.hooks.applyFilters( 'wpsp_manage_schedule', <ManageSchedule />, { state, dispatch }) }
            {wp.hooks.applyFilters( 'wpsp_schedule_options', <SchedulingOptions />, { state, dispatch }) }
        </div>
    );
};

export default Settings;
