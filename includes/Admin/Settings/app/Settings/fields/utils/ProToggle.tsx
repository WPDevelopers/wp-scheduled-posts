import { useBuilderContext } from 'quickbuilder';
import Toggle from 'quickbuilder/dist/fields/Toggle';
import React from 'react';

const ProToggle = ({ handle_status_change, has_toggle = true, ...props }) => {
  const builderContext = useBuilderContext();
  let { name, onChange, type } = props;
  const handleProToggleChange = (event) => {
    onChange({
      target: {
        type: type,
        name: name,
        value: event.target.checked,
      },
    });
  };

  return (
    <div className="header">
      <div
        className={`wprf-control-label ${
          props?.is_pro ? 'pro-deactivated' : ''
        }`}>
        <label htmlFor={props?.name}>{props?.title}</label>
        <p
          className="wprf-help"
          dangerouslySetInnerHTML={{ __html: props?.sub_title }}></p>
      </div>
      {has_toggle && (
        <Toggle
          name="is_active_status"
          type="toggle"
          {...props}
          is_pro={props?.is_pro}
          id={props?.name}
          value={props.value}
          onChange={
            handle_status_change ? handle_status_change : handleProToggleChange
          }
        />
      )}
    </div>
  );
};

export default ProToggle;
