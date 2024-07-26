import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import Select, { components } from 'react-select';
import { selectStyles } from '../../helper/styles';

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

  if (props?.type == 'medium') {
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
      <div className="card-header">
        <div className="heading">
          <img
            width={'30px'}
            src={`${props?.logo}`}
            alt={`${props?.label}`}
          />
          <h5>{props?.label}</h5>
        </div>
        <div className="status">
          <div className="switcher">
            <input
              id={props?.id}
              type="checkbox"
              checked={profileStatus}
              className="wprf-switcher-checkbox"
              onChange={(event) => handleProfileStatusChange(event)}
            />
            <label
              className="wprf-switcher-label"
              htmlFor={props?.id}
              style={{ background: profileStatus && '#02AC6E' }}>
              <span className={`wprf-switcher-button`} />
            </label>
          </div>
        </div>
      </div>
      <div className="card-content">
        <p dangerouslySetInnerHTML={{ __html: props?.desc }} />
      </div>
      <div
        className={`card-footer ${
          ['facebook', 'linkedin'].includes(props?.type)
            ? `has-select ${hasError ? 'has-error' : ''}`
            : ''
        }`}>
        {['facebook', 'linkedin'].includes(props?.type) && (
          <>
            {hasError ? (
              <p className="error-tooltip">
                <span>
                  {__('Please select an option', 'wp-scheduled-posts')}
                </span>
              </p>
            ) : (
              ''
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
        <button
          type="button"
          className={`wpscp-social-tab__btn--addnew-profile ${
            accountType ? 'selected' : ''
          }`}
          onClick={() => {
            if (accountType || ['twitter', 'pinterest', 'instagram', 'medium' ].includes(props?.type)) {
              openApiCredentialsModal(accountType);
            } else {
              setHasError(true);
            }
          }}>
          {__('Add New', 'wp-scheduled-posts')}
        </button>
      </div>
    </>
  );
}
