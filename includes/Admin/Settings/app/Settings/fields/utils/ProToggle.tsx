import React from 'react'
import { Toggle } from 'quickbuilder';

const ProToggle = ( props ) => {
  let { name, multiple, onChange,type } = props;

  const handleProToggleChange = (event) => {
    onChange({
			target: {
				type: type,
				name: name,
				value: event.target.checked,
			},
		});
  }

  return (
        <div className="header">
            <div className={`wprf-control-label ${ props?.disabled_status ? 'pro-deactivated' : ''}`}>
                <label htmlFor={props?.name}>{ props?.title }</label>
                <p className="wprf-help" dangerouslySetInnerHTML={{ __html: props?.sub_title }}></p>
            </div>
            <Toggle name="is_active_status" type="toggle" is_pro={true} id={props?.name} value={props?.status} onChange={props.handle_status_change ? props.handle_status_change : handleProToggleChange} {...props} />
        </div>
  )
}

export default ProToggle