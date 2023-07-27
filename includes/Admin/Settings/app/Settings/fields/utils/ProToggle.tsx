import React from 'react'
import { Toggle,useBuilderContext } from 'quickbuilder';

const ProToggle = ( props ) => {
  const builderContext = useBuilderContext();
  let { name, onChange,type } = props;
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
            <div className={`wprf-control-label ${ props?.is_pro ? 'pro-deactivated' : ''}`}>
                <label htmlFor={props?.name}>{ props?.title }</label>
                <p className="wprf-help" dangerouslySetInnerHTML={{ __html: props?.sub_title }}></p>
            </div>
            <Toggle name="is_active_status" type="toggle" {...props} is_pro={props?.is_pro} id={props?.name} value={ props.value } onChange={ props.handle_status_change ? props.handle_status_change : handleProToggleChange} />
        </div>
  )
}

export default ProToggle