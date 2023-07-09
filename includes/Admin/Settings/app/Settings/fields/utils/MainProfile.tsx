import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import Select from "react-select";

export default function MainProfile( { props, handleProfileStatusChange, profileStatus, openApiCredentialsModal } ) {    
    let options = [];
    let pageDisabled = true;
    // @ts-ignore
    if( wpspSettingsGlobal?.pro_version ) {
        pageDisabled = false;
    }
    if( props?.type == 'facebook' ) {
        options = [
            { value: 'page',    label: __('Page','wp-scheduled-posts') },
            { value: 'group',   label: __('Group','wp-scheduled-posts') }
        ]
    }else{
        options = [
            { value: 'profile',    label: __('Profile','wp-scheduled-posts') },
            { value: 'page',   label: __('Page','wp-scheduled-posts'), isDisabled: pageDisabled }
        ]
    }
    
    const handleAccountType = (selectedOption) => {
        localStorage.setItem('account_type', selectedOption.value);
    };
    
    return (
        <>
            <div className="card-header">
                <div className="heading">
                    <img width={'30px'} src={`${props?.logo}`} alt={`${props?.label}`} />
                    <h5>{props?.label}</h5>   
                    <div className="status">
                        <div className="switcher">
                            <input
                                id={props?.id}
                                type='checkbox'
                                checked={profileStatus}
                                className="wprf-switcher-checkbox"
                                onChange={(event) =>
                                    handleProfileStatusChange(event)
                                }
                            />
                            <label
                                className="wprf-switcher-label"
                                htmlFor={props?.id}
                                style={{ background: profileStatus && '#02AC6E' }}
                            >
                                <span className={`wprf-switcher-button`} />
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div className="card-content">
                <p dangerouslySetInnerHTML={{ __html: props?.desc }} />
            </div>
            { ['facebook', 'linkedin'].includes(props?.type) && (
                <Select
                    id={props?.id}
                    onChange={handleAccountType}
                    options={options}
                    defaultValue={options[0]}
                />
            ) }
            <div className="card-footer">
                <button
                    type="button"
                    className={
                    "wpscp-social-tab__btn--addnew-profile"
                    }
                    onClick={() => openApiCredentialsModal()}
                    >
                    { __("Add New", "wp-scheduled-posts")}
                </button>
            </div>
        </>
    )
}