import { __ } from '@wordpress/i18n';
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

  /* `is_pro` is only true when the feature is not licensed. */
  const locked = !!props?.is_pro;

  const description = (
    <>
      {props?.sub_title && (
        <span
          className="[&_a]:tw-text-brand-500"
          dangerouslySetInnerHTML={{ __html: props.sub_title }}
        />
      )}

      {/* A locked row otherwise offers a dimmed switch and no way forward. */}
      {locked && (
        <a
          href="https://schedulepress.com/#pricing"
          target="_blank"
          rel="noopener noreferrer"
          className="tw-mt-1.5 tw-inline-flex tw-items-center tw-gap-1 tw-text-sm tw-font-medium tw-text-warning-600 tw-no-underline hover:tw-underline"
        >
          {__('Upgrade to unlock', 'wp-scheduled-posts')}
          <svg className="tw-h-3.5 tw-w-3.5" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path
              d="M6 3.5 10.5 8 6 12.5"
              stroke="currentColor"
              strokeWidth="1.8"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          </svg>
        </a>
      )}
    </>
  );

  return (
    <div className="header">
      <SettingRow
        className="tw-py-0"
        label={props?.title}
        description={description}
        isPro={locked}
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
