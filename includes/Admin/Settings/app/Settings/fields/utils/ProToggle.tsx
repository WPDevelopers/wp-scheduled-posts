import React from 'react'
import { Toggle } from 'quickbuilder';

const ProToggle = ( { title,subTitle, name, disabledStatus,status,handleStatusChange } ) => {
  return (
        <div className="header">
            <div className={`wprf-control-label ${ disabledStatus ? 'pro-deactivated' : ''}`}>
                <label htmlFor={name}>{ title }</label>
                <p className="wprf-help" dangerouslySetInnerHTML={{ __html: subTitle }}></p>
            </div>
            <Toggle name="is_active_status" type="toggle" is_pro={true} id={name} value={status} onChange={handleStatusChange} />
        </div>
  )
}

export default ProToggle