import React from 'react';
import { SettingRow, Toggle } from '../../components/ui';

/**
 * Header row for a pro feature panel: title, description and the switch that
 * turns the whole panel on. Used by the Scheduling Hub sections.
 */
const ProToggle = ({ handle_status_change, has_toggle = true, ...props }: any) => {
  const { name, onChange, type } = props;

  const handleProToggleChange = (event) => {
    onChange({
      target: {
        type: type,
        name: name,
        value: event.target.checked,
      },
    });
  };

  const emit = (checked: boolean) => {
    const event = { target: { checked, name, type } };

    if (handle_status_change) {
      handle_status_change(event);
      return;
    }

    handleProToggleChange(event);
  };

  return (
    <div className="header">
      <SettingRow
        className="tw-py-0"
        label={props?.title}
        description={
          props?.sub_title ? (
            <span
              className="[&_a]:tw-text-brand-500"
              dangerouslySetInnerHTML={{ __html: props.sub_title }}
            />
          ) : undefined
        }
        isPro={!!props?.is_pro}
        control={
          has_toggle ? (
            <Toggle
              id={props?.name}
              checked={!!props?.value}
              onChange={emit}
            />
          ) : undefined
        }
      />
    </div>
  );
};

export default ProToggle;
