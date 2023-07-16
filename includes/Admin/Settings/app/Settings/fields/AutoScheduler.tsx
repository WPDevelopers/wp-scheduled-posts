import React, { useState,useEffect } from 'react'
import classNames from 'classnames';
import { __ } from '@wordpress/i18n';
import { generateTimeOptions } from '../helper/helper';
import Select from 'react-select';

const AutoScheduler = (props) => {

    const [autoScheduler,setAutoSchedulerValue] = useState(props?.value ?? []);
    const [setEndSelectedTime, setStartSelectedTime] = useState(null);

    const weeks = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const timeOptions = generateTimeOptions();

    const handleWeekChange = (week, event) => {
        setAutoSchedulerValue((prevWeeks) => {
            const existingWeekIndex = prevWeeks.findIndex(item => Object.keys(item)[0] === week);
            // console.log(existingWeekIndex);
            
            if (existingWeekIndex !== -1) {
              const updatedWeeks = [...prevWeeks];
              updatedWeeks[week] = event.target.value;
              return updatedWeeks;
            } else {
              return [
                ...prevWeeks,
                {
                  [week]: event.target.value,
                },
              ];
            }
        });
        
        console.log(autoScheduler);
        
    }

    const handleStartTimeChange = (selectedStartTime) => {
        // setAutoSchedulerValue( ( prevValue ) => {
        //     const existingStartTime = prevValue.findIndex((item) => item.start_time === 'start_time');
        //     if (existingStartTime !== -1) {
        //       const updateAutoSchedulerValue = [...prevValue];

        //       updateAutoSchedulerValue[existingStartTime] = selectedStartTime?.value;
        //       return updateAutoSchedulerValue;
        //     } else {
        //         return [
        //             ...prevValue,
        //             {
        //                 start_time : selectedStartTime?.value
        //             }
        //         ]
        //     }
        // } )
        // setStartSelectedTime(selectedStartTime);
    }

    let { name, multiple, onChange } = props;
    useEffect(() => {
        const valueFormat = autoScheduler?.map( (item) => {
            let property_name = item?.week+'_post_limit';
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
                        <span>Default : {setEndSelectedTime?.label}</span>
                    </div>
                    <div className="time">
                        <Select
                            options={timeOptions}
                            onChange={handleStartTimeChange}
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
                    weeks.map( (week,index) => (
                        <div className="week">
                             {/* value={ autoScheduler.length > 0 ? autoScheduler[index][item.toLowerCase() + '_post_limit' ] : '' } */}
                            <input type="number" value={ autoScheduler.find(item => item[week.toLowerCase() + "_post_limit"] !== undefined)?.[week.toLowerCase() + "_post_limit"] } onChange={ (event) => handleWeekChange( week.toLowerCase() + "_post_limit", event) } />
                            <span>{ __('Number of posts','wp-scheduled-posts') }</span>
                            <h6>{ week }</h6>
                        </div>
                    ) )
                }
            </div>
      </div>
    )
}

export default AutoScheduler;