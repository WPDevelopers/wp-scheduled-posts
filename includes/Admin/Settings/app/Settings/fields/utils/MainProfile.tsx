import React from 'react'
import { __ } from '@wordpress/i18n'

export default function MainProfile( { props, handleProfileStatusChange, profileStatus, openApiCredentialsModal } ) {
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
            <p dangerouslySetInnerHTML={{ __html: props?.desc }}></p>
        </div>
        <div className="card-footer">
            <button
                type="button"
                className={
                "wpscp-social-tab__btn--addnew-profile"
                }
                onClick={() => openApiCredentialsModal('pinterest')}
                >
                { __("Add New", "wp-scheduled-posts")}
            </button>
        </div>
    </>
  )
}