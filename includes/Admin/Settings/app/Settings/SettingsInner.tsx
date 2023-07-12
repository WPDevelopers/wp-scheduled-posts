import { __ } from "@wordpress/i18n";
import React, { useCallback, useEffect, useState } from "react";
import { FormBuilder, useBuilderContext } from "quickbuilder";
import apiFetch from '@wordpress/api-fetch';
import { SweetAlertToaster,SweetAlertProMsg } from './ToasterMsg';
import Content from "./Content";

const SettingsInner = (props) => {
  const builderContext = useBuilderContext();
  const [ isProAlertModal, setProAlertModal] = useState(false);
  const onChange = (event) => {
    builderContext.setActiveTab(event?.target?.value);
  };

  builderContext.submit.onSubmit = useCallback((event, context) => {
    context.setSubmitting(true);
    console.log(context.values);
    apiFetch( {
        path  : 'wp-scheduled-posts/v1/settings',
        method: 'POST',
        data  : {
            wpspSetting: JSON.stringify(context.values, null, 2),
        },
    } ).then( ( res ) => {
        if( res ) {
          SweetAlertToaster().fire();
        }
    } );
  }, []);

  useEffect(() => {
    builderContext.registerAlert('pro_alert', (props) => {
      SweetAlertProMsg();
    });
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
