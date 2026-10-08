import apiFetch from '@wordpress/api-fetch';
import { FormBuilder, useBuilderContext } from "quickbuilder";
import React, { useCallback, useEffect, useRef, useState } from "react";
import Content from "./Content";
import { SweetAlertProMsg, SweetAlertToaster } from './ToasterMsg';

// Ignores key and profile order, so a list a field re-sorts on load is not a change.
const normalize = (value) => {
  if (Array.isArray(value)) {
    const items = value.map(normalize);
    const byId = items.every((item) => item && typeof item === 'object' && 'id' in item);
    return byId ? [...items].sort((a, b) => String(a.id).localeCompare(String(b.id))) : items;
  }
  if (value && typeof value === 'object') {
    return Object.keys(value).sort().reduce((out, key) => ({ ...out, [key]: normalize(value[key]) }), {});
  }
  return value ?? null;
};
const stable = (value) => JSON.stringify(normalize(value));

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

  // Each setting as last saved, so only the fields the user changed are posted.
  const saved = useRef(null);
  const touched = useRef(false);

  useEffect(() => {
    const mark = () => { touched.current = true; };
    const events = ['pointerdown', 'keydown', 'input', 'change'];
    events.forEach((name) => document.addEventListener(name, mark, true));
    return () => events.forEach((name) => document.removeEventListener(name, mark, true));
  }, []);

  useEffect(() => {
    const values = builderContext.values || {};
    const current = Object.keys(values).reduce((out, key) => ({ ...out, [key]: stable(values[key]) }), {});
    // Fields settle their own values while loading; that is not a change to save.
    if (saved.current === null || !touched.current) {
      saved.current = current;
      return;
    }
    const changed = Object.keys(current).filter((key) => current[key] !== saved.current[key]);
    if (!changed.length) {
      return;
    }
    const timer = setTimeout(() => {
      apiFetch( {
          path  : 'wp-scheduled-posts/v1/settings',
          method: 'POST',
          data  : changed.reduce((out, key) => ({ ...out, [key]: values[key] }), {}),
      } ).then( () => {
          changed.forEach((key) => { saved.current[key] = current[key]; });
      } );
    }, 100);
    return () => clearTimeout(timer);
  }, [builderContext.values]);

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
