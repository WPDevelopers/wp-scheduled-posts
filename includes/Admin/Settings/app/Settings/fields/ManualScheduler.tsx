import React,{useEffect, useState} from 'react'
import classNames from 'classnames';
import Select from 'react-select';
import { __ } from '@wordpress/i18n';
import { generateTimeOptions } from '../helper/helper';

const ManualScheduler = (props) => {
    const options = [
        { value: 'saturday', label: 'Sat' },
        { value: 'sunday', label: 'Sun' },
        { value: 'monday', label: 'Mon' },
        { value: 'tuesday', label: 'Tue' },
        { value: 'wednesday', label: 'Wed' },
        { value: 'thursday', label: 'Thu' },
        { value: 'friday', label: 'Fri' },
    ]
    const timeOptions = generateTimeOptions();

    const [selectDay, setSelectDay] = useState(options[0])
    const [selectTime, setSelectTime] = useState(timeOptions[0])
    const [savedManualSchedule,setSavedManualSchedule] = useState([]);
    const [formatedSchedule,setFormatedSchedule] = useState([]);

    const handleSavedManualSchedule = () => {
        setSavedManualSchedule(prevSchedule => {
            const updatedSchedule = [...prevSchedule];
            updatedSchedule.push({ [selectDay.value]: selectTime.value });
            return updatedSchedule;
        });
    }
    useEffect( () => {
        const formattedData = savedManualSchedule.reduce((result, obj) => {
            const key = Object.keys(obj)[0];
            const value = obj[key];
          
            if (!result.hasOwnProperty(key)) {
              result[key] = [value];
            } else if (!result[key].includes(value)) {
              result[key].push(value);
            }
          
            return result;
          }, {});
          setFormatedSchedule(formattedData)
    },[savedManualSchedule] )

    return (
        <div className={classNames('wprf-control', 'wprf-manual-scheduler', `wprf-${props.name}-manual-scheduler`, props?.classes)}>
            <div className="header">
                <div className="title">
                    <h3>Manual Scheduler</h3>
                    <span> To configure the Auto Scheduler Settings, check out this <a href="#">Doc</a></span>
                </div>
                <div className="switcher">
                    <input type="checkbox" name="" id="" />
                </div>
            </div>
            <div className="content">
                <Select
                    className='select-days'
                    value={selectDay}
                    onChange={(option) =>
                        setSelectDay(option)
                    }
                    options={options}
                    isMulti={false}
                />
                <Select
                    className='select-days'
                    value={selectTime}
                    onChange={(option) =>
                        setSelectTime(option)
                    }
                    options={timeOptions}
                    isMulti={false}
                />
                <button onClick={handleSavedManualSchedule}>{ __('Save Schedule','wp-scheduled-posts') }</button>
            </div>
            <div className="weeks">
                {options.map((item, optionIndex) => (
                    <div key={Math.random()} className="week">
                        <h6>{ item.label }</h6>
                        {
                            formatedSchedule[item.value]?.map( ( data,index ) => (
                                <span>{ data } 
                                    <i 
                                    onClick={ () => {
                                        const updatedSchedule = savedManualSchedule.filter(_item => {
                                            const propertyValue = _item[item.value];
                                            return propertyValue !== data;
                                        });
                                        setSavedManualSchedule(updatedSchedule);
                                    } } 
                                    className="wpsp-icon wpsp-close"></i>
                                </span>
                            ) )
                        }
                    </div>
                ))}
{/*                 
                <div className="week">
                    <h6>Mon</h6>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                </div>
                <div className="week">
                    <h6>Tue</h6>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                </div>
                <div className="week">
                    <h6>Wed</h6>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                </div>
                <div className="week">
                    <h6>Thu</h6>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                </div>
                <div className="week">
                    <h6>Fri</h6>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                </div>
                <div className="week">
                    <h6>Sat</h6>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                   <span>12:15 AM <i className="wpsp-icon wpsp-close"></i></span>
                </div> */}
            </div>
      </div>
    )
}

export default ManualScheduler;