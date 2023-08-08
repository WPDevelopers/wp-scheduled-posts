import { __ } from '@wordpress/i18n';
import React from 'react'
import { BuilderProvider, useBuilder } from 'quickbuilder';
import SettingsInner from './SettingsInner';
import 'quickbuilder/dist/index.css';
import Header from './Header';
import '../assets/sass/index.scss';
import { ToastContainer } from "react-toastify";
import Calendar from './fields/Calendar';

const CalendarWrapper = (props) => {
    const urlParams = new URLSearchParams(window.location.search);
    const page      = urlParams.get('page');
    const postType  = page.replace('schedulepress-', '');

    const post_types = props.post_types.filter((post_type) => post_type.value === postType);

    return (
        <>
            <Header image_path={props?.image_path} />
            <Calendar {...props} post_types={post_types} disablePostType={true} postType={postType} />
        </>
    )
}
export default CalendarWrapper;