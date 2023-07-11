import { __ } from "@wordpress/i18n";
import React, { useCallback, useEffect, useState } from "react";
import { FormBuilder, useBuilderContext } from "quickbuilder";
import apiFetch from '@wordpress/api-fetch';
import wpspToast,{ ToastAlert } from './ToasterMsg';
import Content from "./Content";
import Modal from "react-modal";

const SettingsInner = (props) => {
  const builderContext = useBuilderContext();
  const [ isProAlertModal, setProAlertModal] = useState(false);

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
  };

  builderContext.submit.onSubmit = useCallback((event, context) => {
    context.setSubmitting(true);
    apiFetch( {
        path  : 'wp-scheduled-posts/v1/settings',
        method: 'POST',
        data  : {
            wpspSetting: JSON.stringify(context.values, null, 2),
        },
    } ).then( ( res ) => {
        if( res ) {
          wpspToast.info(__(`Changes Saved Successfully.`, 'notificationx'));
        }
    } );
  }, []);

  useEffect(() => {
    builderContext.registerAlert('pro_alert', (props) => {
      return { fire: () => {
        setProAlertModal(true)
      } };
    });
  }, [])

  const closeProAlertModal = () => {
    setProAlertModal(false);
  }

  return (
    <div className="wpsp-admin-wrapper">
      <Content>
        <Modal isOpen={isProAlertModal} ariaHideApp={false}>
            <h3>HEllo World</h3>
            <button onClick={closeProAlertModal}>Close</button>
        </Modal>
        <FormBuilder {...builderContext} value={builderContext.config.active} onChange={onChange} />
      </Content>
    </div>
  );
};

export default SettingsInner;
