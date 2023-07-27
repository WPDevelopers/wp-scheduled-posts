import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { Toggle, useBuilderContext } from 'quickbuilder';
import React, { useEffect, useState } from 'react';
import Select from 'react-select';
import { generateTimeOptions } from '../helper/helper';
import { selectStyles } from '../helper/styles';
import ProToggle from './utils/ProToggle';

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

    let formatDBManualScheduledData = Object.entries(builderContext?.values?.manage_schedule?.[name]?.[0]?.weekdata ?? []).reduce((result, [day, times]) => {
        // @ts-ignore
        times.forEach(time => {
            result.push({ [day]: time });
        });
        return result;
    }, []);

    let manualSchedulerStatusDBData;
      for (const item of Object.entries(builderContext?.values?.manage_schedule?.[name]?.[1] ?? [])) {
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
    const [manualSchedulerStatus, setManualSchedulerStatus] = useState(manualSchedulerStatusDBData ?? "");

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

    const handleManualScheduleStatusToggle = (event) => {
        // @ts-ignore
        setManualSchedulerStatus(event.target.checked)
    }

    // @ts-ignore 
    let is_pro = wpspSettingsGlobal?.pro_version ? true : false;

    return (
        <div className={classNames('wprf-control', 'wprf-manual-scheduler', `wprf-${props.name}-manual-scheduler`, props?.classes)}>
            <ProToggle
                title={__("Manual Scheduler",'wp-scheduled-posts')}
                sub_title={__('To configure the Manual Scheduler Settings, check out this <a href="https://wpdeveloper.com/docs/wp-scheduled-posts/how-does-manual-scheduler-work/" target="_blank">Doc</a>')}
                name={name}
                is_pro={!is_pro} 
                value={manualSchedulerStatus} 
                handle_status_change={handleManualScheduleStatusToggle}   
            />
            <div className={`content ${!is_pro ? 'pro-deactivated' : ''}`}>
                <Select
                    styles={selectStyles}
                    className='select-days main-select'
                    value={selectDay}
                    onChange={(option) =>
                        setSelectDay(option)
                    }
                    options={options}
                    isMulti={false}
                    isDisabled={!is_pro}
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
                    isDisabled={!is_pro}
                />
                <button onClick={handleSavedManualSchedule} disabled={!is_pro}>{ __('Save Schedule','wp-scheduled-posts') }</button>
            </div>
            <div className={`weeks ${!is_pro ? 'pro-deactivated' : ''}`}>
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