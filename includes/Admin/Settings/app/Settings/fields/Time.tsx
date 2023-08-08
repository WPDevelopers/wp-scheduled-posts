import classNames from 'classnames';
import React, { useEffect, useState } from 'react';
import Select from 'react-select';
import { generateTimeOptions } from '../helper/helper';
import { selectStyles } from '../helper/styles';

const Time = (props) => {
  console.log('time-props',props);
  
  const [selectedTime, setSelectedTime] = useState({label : props?.value?.toUpperCase(), value : props?.value?.toUpperCase()});

  const handleTimeChange = (selectedOption) => {
    setSelectedTime(selectedOption);
  };
  const timeOptions = generateTimeOptions();

  // Save time
  let { name, onChange } = props;
  useEffect(() => {
		onChange({
			target: {
				type: "time",
				name,
				value: selectedTime?.value?.toLowerCase(),
			},
		});
	}, [selectedTime]);
  return (
      <div className={classNames('wprf-control', 'wprf-time', `wprf-${props.name}-time`, props?.classes)}>
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
                    className='time-select'
                  />
              </div>
          </div>
      </div>
  )
}

export default Time;