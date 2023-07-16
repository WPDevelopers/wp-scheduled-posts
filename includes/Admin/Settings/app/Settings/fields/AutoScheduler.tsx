import React, { useState,useEffect } from 'react'
import classNames from 'classnames';
import { __ } from '@wordpress/i18n';
import { generateTimeOptions } from '../helper/helper';
import Select from 'react-select';

const AutoScheduler = (props) => {

    console.log('props-value',props.value);
    const convertedData = [];
    const weeks = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
    weeks.forEach(day => {
      const obj = props.value.find(item => item[`${day}_post_limit`]);
      if (obj) {
        convertedData.push({
          day: day,
          value: obj[`${day}_post_limit`]
        });
      }
    });
    
    console.log('converted-data',convertedData);
    

    const [autoScheduler,setAutoSchedulerValue] = useState(convertedData ?? []);
    const [setEndSelectedTime, setStartSelectedTime] = useState(null);
    const timeOptions = generateTimeOptions();

    const handleWeekChange = (day, event) => {
        setAutoSchedulerValue((prevWeeks) => {
            const existingWeekIndex = prevWeeks.findIndex((item) => item.day === day);
            if (existingWeekIndex !== -1) {
              const updatedWeeks = [...prevWeeks];
              updatedWeeks[existingWeekIndex].value = event.target.value;
              return updatedWeeks;
            } else {
              return [
                ...prevWeeks,
                {
                  day: day,
                  value: event.target.value,
                },
              ];
            }
        });
    }


    let { name, multiple, onChange } = props;
    useEffect(() => {
        const valueFormat = autoScheduler?.map( (item) => {
            let property_name = item?.day+'_post_limit';
            return { [property_name] : item?.value }
        } )
		onChange({
			target: {
				type: "auto-scheduler",
				name,
				value: valueFormat,
				multiple,
			},
		});
	}, [autoScheduler]);
    
    
    return (
        <div className={classNames('wprf-control', 'wprf-auto-scheduler', `wprf-${props.name}-auto-scheduler`, props?.classes)}>
            <div className="header">
                <div className="title">
                    <h3>Auto Scheduler</h3>
                    <span> To configure the Auto Scheduler Settings, check out this <a href="#">Doc</a></span>
                </div>
                <div className="switcher">
                    <input type="checkbox" name="" id="" />
                </div>
            </div>
            <div className="content">
                <div className="start-time set-timing">
                    <div className="time-title">
                        <h4>Start Time</h4>
                        <span>Default : 12:30 AM</span>
                    </div>
                    <div className="time">
                        <Select
                            options={timeOptions}
                            // onChange={handleTimeChange}
                            className='select-start-time'
                        />
                    </div>
                </div>
                <div className="end-time set-timing">
                    <div className="time-title">
                        <h4>End Time</h4>
                        <span>Default : 04:00 PM</span>
                    </div>
                    <div className="time">
                        <select name="" id="">
                            <option value=""> 04:00 PM</option>
                            <option value=""> 04:00 PM</option>
                        </select>
                    </div>
                </div>
            </div>
            <div className="weeks">
                {
                    weeks.map( (day,index) => (
                        <div className="week">
                            <input type="number" value={ autoScheduler.find(item => item.day === day)?.value } onChange={ (event) => handleWeekChange( day, event ) } />
                            <span>{ __('Number of posts','wp-scheduled-posts') }</span>
                            <h6>{ day.toUpperCase() }</h6>
                        </div>
                    ) )
                }
            </div>
      </div>
    )
}

export default AutoScheduler;