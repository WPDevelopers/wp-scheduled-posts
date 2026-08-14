import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import React, { useEffect, useState } from 'react';
import Select, { components } from 'react-select';
import { selectStyles } from '../../helper/styles';
import { Button, Toggle } from '../../components/ui';

// Prepare options with checkbox
const Option = (props) => {
  return (
    <components.Option {...props}>
      <span className="wprf-select-pro-option">{props.label}</span>
    </components.Option>
  );
};

export default function MainProfile({
  props,
  handleProfileStatusChange,
  profileStatus,
  openApiCredentialsModal,
}) {
  let options = [];
  // @ts-ignore
  let pageDisabled = wpspSettingsGlobal?.pro_version ? false : true;
  let currentActiveAccountType = localStorage.getItem('account_type');

  if (props?.type == 'facebook') {
    options = [
      {
        value: 'page',
        label: __('Page', 'wp-scheduled-posts'),
        selected: currentActiveAccountType == 'page' ? true : false,
      },
      {
        value: 'group',
        label: __('Group', 'wp-scheduled-posts'),
        selected: currentActiveAccountType == 'group' ? true : false,
      },
    ];
  } else {
    options = [
      {
        value: 'profile',
        label: __('Profile', 'wp-scheduled-posts'),
        selected: currentActiveAccountType == 'profile' ? true : false,
      },
      {
        value: 'page',
        label: __('Page', 'wp-scheduled-posts'),
        isDisabled: pageDisabled,
        selected: currentActiveAccountType == 'page' ? true : false,
      },
    ];
  }

  const [accountType, setAccountType] = useState(undefined);
  const [hasError, setHasError] = useState(false);
  /* Only Facebook and LinkedIn ask which kind of account is being added. */
  const hasSelect = ['facebook', 'linkedin'].includes(props?.type);
  const handleAccountType = (selectedOption) => {
    setAccountType(selectedOption.value);
  };

  const mainSelectStyles = {
    ...selectStyles,
    control: (base, state) => ({
      ...base,
      boxShadow: 'none',
      borderColor: '#D7DBDF',
      backgroundColor: '#F9FAFC',
      color: '#6E6E8D',
      '&:hover': {
        borderColor: '#cccccc',
      },
    }),
  };
  // @ts-ignore
  useEffect(() => {
    let errorTimeOut;
    if (hasError) {
      if (accountType) setHasError(false);
      else
        setTimeout(() => {
          setHasError(false);
        }, 5000);
    }
    return () => {
      clearTimeout(errorTimeOut);
    };
  }, [hasError, accountType]);

  return (
    <>
      <div className="card-header tw-flex tw-items-center tw-justify-between tw-gap-3 tw-mb-5">
        <div className="heading tw-flex tw-items-center tw-gap-3">
          <img
            src={`${props?.logo}`}
            alt={`${props?.label}`}
            className="tw-w-8 tw-h-8 tw-object-contain"
          />
          <h5 className="tw-text-xl tw-font-medium tw-text-ink tw-m-0">
            {props?.label}
          </h5>
        </div>

        <Toggle
          id={props?.id}
          tone="success"
          checked={!!profileStatus}
          /* The handler only reads `target.checked`, so a stand-in is enough. */
          onChange={(checked) =>
            handleProfileStatusChange({ target: { checked } })
          }
        />
      </div>

      <div
        className="card-content tw-text-base tw-text-ink-muted tw-mb-5 [&_p]:tw-m-0 [&_a]:tw-text-ink"
        dangerouslySetInnerHTML={{ __html: props?.desc }}
      />
      <div
        className={classNames(
          'card-footer tw-relative tw-flex',
          hasSelect && 'has-select tw-max-w-[250px]',
          hasSelect && hasError && 'has-error'
        )}>
        {hasSelect && (
          <>
            {hasError && (
              <p className="tw-absolute tw--top-9 tw-left-0 tw-z-10 tw-m-0 tw-rounded-md tw-bg-danger-500 tw-px-2.5 tw-py-1.5 tw-text-xs tw-text-white after:tw-absolute after:tw-left-4 after:tw-top-full after:tw-border-4 after:tw-border-solid after:tw-border-transparent after:tw-border-t-danger-500 after:tw-content-['']">
                <span>
                  {__('Please select an option', 'wp-scheduled-posts')}
                </span>
              </p>
            )}
            <Select
              id={props?.id}
              onChange={(event) => {
                handleAccountType(event);
              }}
              components={{
                Option,
              }}
              options={options}
              className="main-select"
              styles={mainSelectStyles}
              classNamePrefix="social-media-type-select"
            />
          </>
        )}
        <Button
          type="button"
          variant={accountType ? 'primary' : 'outline'}
          size="lg"
          className={classNames(
            'wpscp-social-tab__btn--addnew-profile tw-ml-auto tw-shrink-0',
            accountType && 'selected',
            // Butts up against the account-type select when there is one.
            hasSelect
              ? 'tw-rounded-l-none'
              : 'tw-rounded-md'
          )}
          onClick={() => {
            if (accountType || ['twitter', 'pinterest', 'instagram', 'medium','threads','bluesky','mastodon' ].includes(props?.type)) {
              openApiCredentialsModal(accountType);
            } else {
              setHasError(true);
            }
          }}>
          {__('Add New', 'wp-scheduled-posts')}
        </Button>
      </div>
    </>
  );
}
