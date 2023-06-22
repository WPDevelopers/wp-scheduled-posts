import classNames from 'classnames';
import React, {useState} from 'react'
import { __experimentalGetSettings, format, dateI18n } from '@wordpress/date';

const Time = (props) => {

    const settings = __experimentalGetSettings();
    const [selectedTime, setSelectedTime] = useState('');
  
    const handleTimeChange = (event) => {
      setSelectedTime(event.target.value);
    };
  
    const renderTimeOptions = () => {
      const intervalMinutes = 15;
      const currentTime = new Date();
      currentTime.setSeconds(0); // Reset seconds to 0
  
      const timeOptions = [];
  
      while (currentTime.getDate() === new Date().getDate()) {
        const formattedTime = format('H:i', currentTime);
        const optionLabel = dateI18n(settings.formats.time, currentTime,true);
        timeOptions.push(
          <option key={formattedTime} value={formattedTime}>
            {optionLabel}
          </option>
        );
  
        currentTime.setMinutes(currentTime.getMinutes() + intervalMinutes);
      }
      console.log('Hello',timeOptions);
    };
    // renderTimeOptions();
    return (
        <div className={classNames('wprf-control', 'wprf-time', `wprf-${props.name}-time`, props?.classes)}>
           <div className="wprf-control-label">
                <label htmlFor={`${props?.id}`}>{props?.label}</label>
                <div className="selected-options">
                    <ul>
                      
                    </ul>
                </div>
            </div>
            <div className="wprf-control-field">
                <div className="wprf-checkbox-select-wrap wprf-checked wprf-label-position-right">
                    <select name="" id="">
                        <option value="">HEllo</option>
                        <option value="">HEllo</option>
                    </select>
                </div>
            </div>
        </div>
    )
}

export default Time;