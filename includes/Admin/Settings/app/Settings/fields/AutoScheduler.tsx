import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { useBuilderContext } from 'quickbuilder';
import React, { useEffect, useState } from 'react';
import Select from 'react-select';
import { generateTimeOptions } from '../helper/helper';
import ProToggle from './utils/ProToggle';

import { selectStyles } from '../helper/styles';
import { SweetAlertStatusChangingMsg } from '../ToasterMsg';

const AutoScheduler = (props) => {
    let { name, multiple, onChange } = props;
    const builderContext = useBuilderContext();
    const timeOptions = generateTimeOptions();
    const modifiedDayDataFormet = [];
    const weeks = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
    weeks.forEach(day => {
      const obj = builderContext.values['manage_schedule']?.[name]?.find(item => item[`${day}_post_limit`]);
      if (obj) {
        modifiedDayDataFormet.push({
          day: day,
          value: obj[`${day}_post_limit`]
        });
      }
    });
    let getStartTime = builderContext.values['manage_schedule']?.[name]?.find( (item) => item['start_time'] );
    getStartTime = getStartTime ? getStartTime['start_time'] : '';
    let getEndTime = builderContext.values['manage_schedule']?.[name]?.find( (item) => item['end_time'] );
    let getAutoSchedulerStatus = builderContext.values['manage_schedule']?.[name]?.filter( (item) => item.hasOwnProperty('is_active_status') )[0].is_active_status;
    getEndTime = getEndTime ? getEndTime['end_time'] : '';
    const startTimeFormat = getStartTime ? { label : getStartTime, value : getStartTime } : null;
    const endTimeFormat = getEndTime ? { label : getEndTime, value : getEndTime } : null;

    const [autoScheduler,setAutoSchedulerValue] = useState(modifiedDayDataFormet ?? []);
    const [startSelectedTime, setStartSelectedTime] = useState(startTimeFormat ? startTimeFormat : timeOptions[0]);
    const [endSelectedTime, setEndSelectedTime] = useState(endTimeFormat ? endTimeFormat : timeOptions[0]);
    const [autoSchedulerStatus, setautoSchedulerStatus] = useState(getAutoSchedulerStatus ?? false);

    useEffect(() => {
      setautoSchedulerStatus( getAutoSchedulerStatus )
    }, [getAutoSchedulerStatus])

    const handleDayChange = (day, event) => {
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

    const handleTimeChange = (type,event) => {

        if( type === 'start' ) {
            setStartSelectedTime(event);
        }else{
            setEndSelectedTime(event);
        }
    }

    useEffect(() => {
        let autoSchedulerObj = autoScheduler?.map( (item) => {
            let property_name = item?.day+'_post_limit';
            return { [property_name] : item?.value }
        } )
        autoSchedulerObj.push( { start_time : startSelectedTime?.value }  );
        autoSchedulerObj.push( { end_time : endSelectedTime?.value }  );
        autoSchedulerObj.push( { is_active_status : autoSchedulerStatus }  );
		onChange({
			target: {
				type: "auto-scheduler",
				name:["manage_schedule",name],
				value: autoSchedulerObj,
				multiple,
			},
		});
	}, [autoScheduler,startSelectedTime,endSelectedTime, autoSchedulerStatus]);

    let manualSchedulerData = builderContext.values['manage_schedule']?.['manual_schedule'];
    let manualSchedulerStatusIndex = manualSchedulerData.findIndex(obj => obj.hasOwnProperty("is_active_status"));
    const handleAutoScheduleStatusToggle = (event) => {
        if( manualSchedulerStatusIndex !== -1) {
                SweetAlertStatusChangingMsg({ status: event.target.checked }, handleStatusChange);
            }else{
                setautoSchedulerStatus(event.target.checked);
            }
        }
    }
    
    const handleStatusChange = ( status ) => {
        manualSchedulerData[manualSchedulerStatusIndex].is_active_status = false;
        builderContext.setFieldValue(['manage_schedule', 'manual_schedule'], [...manualSchedulerData]);
        setautoSchedulerStatus(status);
    };
    
    // @ts-ignore
    let is_pro = wpspSettingsGlobal?.pro_version ? true : false;

    return (
        <div className={classNames('wprf-control', 'wprf-auto-scheduler', `wprf-${props.name}-auto-scheduler`, props?.classes)}>
            <ProToggle
                title={__("Auto Scheduler",'wp-scheduled-posts')}
                sub_title={__('To configure the Auto Scheduler Settings, check out this <a href="https://wpdeveloper.com/docs/wp-scheduled-posts/how-does-auto-scheduler-work/">Doc</a>')}
                name={name}
                is_pro={!is_pro}
                value={autoSchedulerStatus}
                handle_status_change={ handleAutoScheduleStatusToggle }
            />
            <div className={`content ${ !is_pro ? 'pro-deactivated' : ''}`}>
                <div className="start-time set-timing">
                    <div className="time-title">
                        <h4>{ __('Start Time','wp-scheduled-posts') }</h4>
                        <span>{ __('Default','wp-scheduled-posts') } : { startSelectedTime?.label }</span>
                    </div>
                    <div className="time">
                        <Select
                            styles={selectStyles}
                            value={startSelectedTime}
                            options={ timeOptions }
                            defaultValue={timeOptions[0] }
                            onChange={ (event) => handleTimeChange('start',event) }
                            isDisabled={!is_pro}
                            className='select-start-time main-select'
                        />
                    </div>
                </div>
                <div className="end-time set-timing">
                    <div className="time-title">
                        <h4>{ __('End Time','wp-scheduled-posts') }</h4>
                        <span>{ __('Default','wp-scheduled-posts') } : {endSelectedTime?.label}</span>
                    </div>
                    <div className="time">
                        <Select
                            styles={selectStyles}
                            value={endSelectedTime}
                            options={ timeOptions }
                            onChange={ (event) => handleTimeChange('end',event) }
                            isDisabled={!is_pro}
                            className='select-start-time main-select'
                        />
                    </div>
                </div>
            </div>
            <div className={`weeks ${!is_pro ? 'pro-deactivated' : ''}`}>
                {
                    weeks.map( (day,index) => (
                        <div key={index} className="week">
                            <input
                                type="number"
                                value={ autoScheduler?.find(item => item.day === day)?.value || 0 }
                                onChange={ (event) => handleDayChange( day, event ) }
                                disabled={!is_pro}
                                readOnly={!is_pro}
                            />
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