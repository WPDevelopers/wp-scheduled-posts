import classNames from 'classnames';
import React, {useState,useEffect} from 'react'
import Select from 'react-select';
const Time = (props) => {
  const [selectedTime, setSelectedTime] = useState(null);

  const handleTimeChange = (selectedOption) => {
    setSelectedTime(selectedOption);
  };

  const generateTimeOptions = () => {
    const times = [];
    const startTime = new Date();
    startTime.setHours(0, 0, 0, 0); // Set start time to 12:00 AM

    for (let i = 0; i < 24 * 4; i++) {
      const time = new Date(startTime.getTime() + i * 15 * 60000);
      const timeString = time.toLocaleString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
      });
      times.push({ value: timeString, label: timeString });
    }

    return times;
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
      backgroundColor: "#EBEEF5",
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