import { __ } from "@wordpress/i18n";
import React, { useCallback, useEffect, useState } from "react";
import { FormBuilder, useBuilderContext } from "quickbuilder";
import apiFetch from '@wordpress/api-fetch';
import { SweetAlertToaster,SweetAlertProMsg } from './ToasterMsg';
import Content from "./Content";
import Sidebar from "./Sidebar";

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
    // console.log('builderContext', builderContext);
    // https://schedule.test/wp-admin/admin.php?page=schedulepress-calendar
    // check if page param = schedulepress-calendar
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('page') === 'schedulepress-calendar') {
      // set active tab to layout_calendar
      builderContext.setActiveTab('layout_calendar');
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
