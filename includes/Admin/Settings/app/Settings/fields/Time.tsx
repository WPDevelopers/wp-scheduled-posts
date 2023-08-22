import classNames from 'classnames';
import React, { useEffect, useState } from 'react';
import Select from 'react-select';
import { convertTo12HourFormat, generateTimeOptions, to24HourFormat } from '../helper/helper';
import { selectStyles } from '../helper/styles';

const Time = (props) => {
  const timeValue = props.value || '00:00';
  const [selectedTime, setSelectedTime] = useState( { label : convertTo12HourFormat( timeValue ), value : to24HourFormat( timeValue ) } );
  const handleTimeChange = (selectedOption) => {
    setSelectedTime(selectedOption);
  };
  const timeOptions = generateTimeOptions();
  // Save time
  let { name='select', onChange } = props;
  useEffect(() => {
		onChange({
			target: {
				type: "time",
				name,
				value: selectedTime?.value,
			},
		});
	}, [selectedTime]);
  return (
      <div className={classNames('wprf-control', 'wprf-time', `wprf-${props.name}-time`)}>
          <div className="wprf-control-label">
              <label htmlFor={`${props?.id}`}>{props?.label}</label>
          </div>
          <div className="wprf-control-field">
              <div className="wprf-time-select-wrap wprf-checked wprf-label-position-right">
                  <Select
                    id={props?.id}
                    value={selectedTime}
                    onChange={handleTimeChange}
                    options={timeOptions}
                    styles={selectStyles}
                    isDisabled={ !(props?.is_pro === undefined || props?.is_pro === null) ? !props?.is_pro : false }
                    className={`time-select wprf-${props.name} ${props?.classes}`}
                    classNamePrefix={`wprf-${props.name}`}
                  />
              </div>
          </div>
      </div>
  )
}

export default Time;