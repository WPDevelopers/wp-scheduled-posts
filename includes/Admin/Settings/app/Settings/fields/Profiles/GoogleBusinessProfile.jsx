import { __ } from '@wordpress/i18n';
import React from 'react'

const GoogleBusinessProfile = ({props,
    handleProfileStatusChange,
    profileStatus,
    openApiCredentialsModal}) => {    
    return (
        <div>
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
                        <span className={`wprf-switcher-button`} 
                    />
                    </label>
                </div>
                </div>
            </div>
            <div className="card-content">
                <p dangerouslySetInnerHTML={{ __html: props?.desc }} />
            </div>
            <div className="card-footer">
                <button
                    type="button"
                    onClick={() => {
                        openApiCredentialsModal()
                    }}>
                    {__('Add New', 'wp-scheduled-posts')}
                </button>
            </div>
        </div>
    )
}

export default GoogleBusinessProfile
