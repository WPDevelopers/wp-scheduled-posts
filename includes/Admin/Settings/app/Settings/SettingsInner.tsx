import { __ } from "@wordpress/i18n";
import React, { useCallback, useEffect } from "react";
import { FormBuilder, useBuilderContext } from "quickbuilder";
import apiFetch from '@wordpress/api-fetch';

import Content from "./Content";

const SettingsInner = (props) => {
  const builderContext = useBuilderContext();

  useEffect(() => {
    // let iconLists = {};
    // iconLists['source'] = <SourceIcon />
    // iconLists['design'] = <DesignIcon />
    // iconLists['content'] = <ContentIcon />
    // iconLists['display'] = <DisplayIcon />
    // iconLists['customize'] = <CustomizeIcon />
    // builderContext.registerIcons('tabs', iconLists);
    // builderContext.registerAlert('pro_alert', proAlert);
    // builderContext.registerAlert('toast', ToastAlert);
  }, []);

  const onChange = (event) => {
    builderContext.setActiveTab(event?.target?.value);
    // console.log(event);
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
        console.log( res );
    } );
  }, []);

  useEffect(() => {
    // builderContext.setActiveTab(props.settings.active);
    // console.log(builderContext.active);


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
