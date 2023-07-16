import classNames from 'classnames';
import React, {useState,useEffect} from 'react'
import Select from 'react-select';
import { generateTimeOptions } from '../helper/helper';

const Time = (props) => {
  const [selectedTime, setSelectedTime] = useState(null);

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
  const customStyles = {
    control: (base, state) => ({
      ...base,
      boxShadow: "none", 
      borderColor: "#EBEEF5",
      backgroundColor: "#F9FAFC",
      color: "#6E6E8D",
      "&:hover": {
          borderColor: "#cccccc"
      }
    }),
    clearIndicator: (base: any) => ({
      ...base,
      display: 'none',
      right: 0,
    }),
    option: (styles, { data, isDisabled, isFocused, isSelected }) => {
      return {
        ...styles,
        backgroundColor: isFocused || isSelected ? '#F3F2FF' : null,
        margin: '0 10px',
        width: '91%',
        borderRadius: '5px',
        color: "#000",
      };
    }
  }
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
                    styles={customStyles}
                    className='time-select'
                  />
              </div>
          </div>
      </div>
  )
}

export default Time;