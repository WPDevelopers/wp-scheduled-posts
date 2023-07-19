import React,{useEffect, useState} from 'react'
import classNames from 'classnames';
import Select from 'react-select';
import { __ } from '@wordpress/i18n';
import { generateTimeOptions } from '../helper/helper';
import { selectStyles } from '../helper/styles';
import { useBuilderContext } from 'quickbuilder';
import { Toggle } from 'quickbuilder';

const ManualScheduler = (props) => {
    const builderContext = useBuilderContext();
    let { name, multiple, onChange } = props;
    const options = [
        { value: 'saturday', label: 'Sat' },
        { value: 'sunday', label: 'Sun' },
        { value: 'monday', label: 'Mon' },
        { value: 'tuesday', label: 'Tue' },
        { value: 'wednesday', label: 'Wed' },
        { value: 'thursday', label: 'Thu' },
        { value: 'friday', label: 'Fri' },
    ]
    
    let formatDBManualScheduledData = Object.entries(builderContext?.values?.manage_schedule?.[name]?.[0]?.weekdata).reduce((result, [day, times]) => {
        // @ts-ignore 
        times.forEach(time => {
            result.push({ [day]: time });
        });
        return result;
    }, []);
    
    let manualSchedulerStatusDBData;
      for (const item of Object.entries(builderContext?.values?.manage_schedule?.[name]?.[1])) {
        if (item[0] === "is_active_status") {
            manualSchedulerStatusDBData = item[1];
            break;
        }
    }

    const timeOptions = generateTimeOptions();
    const [selectDay, setSelectDay] = useState(options[0])
    const [selectTime, setSelectTime] = useState(timeOptions[0])
    const [savedManualSchedule,setSavedManualSchedule] = useState(formatDBManualScheduledData ?? []);
    const [formatedSchedule,setFormatedSchedule] = useState([]);
    const [manualSchedulerStatus, setManualSchedulerStatus] = useState(manualSchedulerStatusDBData ?? null);

    const handleSavedManualSchedule = () => {
        setSavedManualSchedule(prevSchedule => {
            const updatedSchedule = [...prevSchedule];
            updatedSchedule.push({ [selectDay.value]: selectTime.value });
            return updatedSchedule;
        });
    }
    useEffect( () => {
        
        if( savedManualSchedule.length > 0 ) {
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
            let manualSchedulerData = [ { weekdata : formattedData }, { is_active_status : manualSchedulerStatus } ];
            onChange({
                target: {
                    type: "manual-scheduler",
                    name:["manage_schedule",name],
                    value: manualSchedulerData,
                    multiple,
                },
            });
        }
    
    },[savedManualSchedule,manualSchedulerStatus] )
    
    const handleAutoScheduleStatusToogle = (event) => {
        // @ts-ignore 
        setManualSchedulerStatus(event.target.checked)
    }
    return (
        <div className={classNames('wprf-control', 'wprf-manual-scheduler', `wprf-${props.name}-manual-scheduler`, props?.classes)}>
            <div className="header">
                {/* <div className="title">
                    <h3>Manual Scheduler</h3>
                    <span> To configure the Auto Scheduler Settings, check out this <a href="#">Doc</a></span>
                </div>
                <div className="switcher">
                    <input type="checkbox" name="" id="" />
                </div> */}
                <Toggle name="is_active_status" id="manual_is_active_status" label="Manual Scheduler" description="To configure the Auto Scheduler Settings, check out this Doc" value={manualSchedulerStatus} onChange={handleAutoScheduleStatusToogle}  />
            </div>
            <div className="content">
                <Select
                    styles={selectStyles}
                    className='select-days main-select'
                    value={selectDay}
                    onChange={(option) =>
                        setSelectDay(option)
                    }
                    options={options}
                    isMulti={false}
                />
                <Select
                    styles={selectStyles}
                    className='select-days main-select'
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
                    <div key={optionIndex} className="week">
                        <h6>{ item.label }</h6>
                        {
                            formatedSchedule?.[item.value]?.map( ( data,index ) => (
                                <span key={index}>{ data }
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
            </div>
      </div>
    )
}

export default ManualScheduler;