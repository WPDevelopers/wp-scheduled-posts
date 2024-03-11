import apiFetch from '@wordpress/api-fetch';
import { FormBuilder, useBuilderContext } from "quickbuilder";
import React, { useCallback, useEffect, useState } from "react";
import Content from "./Content";
import { SweetAlertProMsg, SweetAlertToaster } from './ToasterMsg';

const SettingsInner = (props) => {
  const builderContext = useBuilderContext();
  const [ isProAlertModal, setProAlertModal] = useState(false);
  const onChange = (event) => {
    builderContext.setActiveTab(event?.target?.value);
  };
  builderContext.submit.onSubmit = useCallback((event, context) => {
    context.setSubmitting(true);
    apiFetch( {
        path  : 'wp-scheduled-posts/v1/settings',
        method: 'POST',
        data  : context.values,
    } ).then( ( res ) => {
        if( res ) {
          SweetAlertToaster().fire();
        }
    } );
  }, []);

  useEffect(() => {
    builderContext.registerAlert('pro_alert', (props) => {
      return {
        fire: () => {
          SweetAlertProMsg();
        },
      };
    });
    // https://schedule.test/wp-admin/admin.php?page=schedulepress-calendar
    // check if page param = schedulepress-calendar
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('page') === 'schedulepress-calendar') {
      // set active tab to layout_calendar
      builderContext.setActiveTab('layout_calendar');
    }
    if(urlParams.get('page') === 'schedulepress' && urlParams.get('tab') === 'advanced-schedule') {
      builderContext.setActiveTab('layout_scheduling_hub');
    }
    if(urlParams.get('page') === 'schedulepress' && urlParams.get('tab') === 'license') {
      // set active tab to layout_calendar
      builderContext.setActiveTab('layout_license');
    }
    if(urlParams.get('page') === 'schedulepress' && urlParams.get('tab') === 'general') {
      // set active tab to layout_calendar
      builderContext.setActiveTab('layout_general');
    }
    if(urlParams.get('page') === 'schedulepress' && urlParams.get('tab') === 'social-profile') {
      // set active tab to layout_calendar
      builderContext.setActiveTab('layout_social_profile');
    }

  }, [])

  return (
    <div className="wpsp-admin-wrapper">
      <Content>
        <FormBuilder {...builderContext} value={builderContext.config.active} onChange={onChange} />
      </Content>
    </div>
  );
};

export default SettingsInner;
