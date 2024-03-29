import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { useBuilderContext } from 'quickbuilder';
import React, { useEffect, useMemo, useState } from 'react';
import { generateTimeOptions } from '../helper/helper';
import ProToggle from './utils/ProToggle';

import { SweetAlertStatusChangingMsg } from '../ToasterMsg';
import Time from './Time';

const AutoScheduler = (props) => {
    let { name, multiple, onChange } = props;
    const builderContext = useBuilderContext();
    const timeOptions = generateTimeOptions();
    const modifiedDayDataFormet = [];
    const weeks = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
    weeks.forEach(day => {
        const obj = builderContext.values['manage_schedule']?.[name];
      if ( obj?.[`${day}_post_limit`] ) {
        modifiedDayDataFormet.push({
          day: day,
          value: obj[`${day}_post_limit`]
        });
      }
    });
    // @ts-ignore
    const is_pro = wpspSettingsGlobal?.pro_version ? true : false;
    const getStartTime = builderContext.values['manage_schedule']?.[name]?.['start_time'];
    const getEndTime = builderContext.values['manage_schedule']?.[name]?.['end_time'];
    const getAutoSchedulerStatus = builderContext.values['manage_schedule']?.[name]?.['is_active_status'] || false;
    // const startTimeFormat = getStartTime ? { label : convertTo12HourFormat(getStartTime), value : to24HourFormat( getStartTime ) } : null;
    // const endTimeFormat = getEndTime ? { label : convertTo12HourFormat(getEndTime), value : to24HourFormat( getEndTime )  } : null;

    const [autoScheduler,setAutoSchedulerValue] = useState( modifiedDayDataFormet ?? [] );
    const [startSelectedTime, setStartSelectedTime] = useState( getStartTime ? getStartTime : "00:00" );
    const [endSelectedTime, setEndSelectedTime] = useState( getEndTime ? getEndTime : "23:45");
    const [autoSchedulerStatus, setautoSchedulerStatus] = useState(getAutoSchedulerStatus);

    useMemo( () => {
        setautoSchedulerStatus(getAutoSchedulerStatus);
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
            setStartSelectedTime(event.target.value);
        }else{
            setEndSelectedTime(event.target.value);
        }
    }
    useEffect(() => {
        let manage_schedule = builderContext.values['manage_schedule'] ?? {};
        let autoSchedulerObj = {};
        autoScheduler?.map( (item) => {
            let property_name = item?.day+'_post_limit';
            autoSchedulerObj[property_name] = item?.value;
        } );
        autoSchedulerObj['start_time'] = startSelectedTime;
        autoSchedulerObj['end_time'] = endSelectedTime;
        autoSchedulerObj['is_active_status'] = autoSchedulerStatus ?? false;
        manage_schedule[name] = autoSchedulerObj;
        if( autoSchedulerStatus ) {
            manage_schedule['activeScheduleSystem'] = 'auto_schedule';
        }
		onChange({
			target: {
				type: "auto-scheduler",
				name:["manage_schedule"],
				value: manage_schedule,
                multiple
			},
		});

	}, [autoScheduler, startSelectedTime, endSelectedTime, autoSchedulerStatus ]);

    // Handle status changing for auto and manual scheduler
    const handleAutoScheduleStatusToggle = (event) => {
        let manualScheduleStatus = builderContext.values['manage_schedule']?.['manual_schedule']?.['is_active_status'];
        if(  manualScheduleStatus && event.target.checked ) {
            SweetAlertStatusChangingMsg({ status: event.target.checked,text : __('Enabling Auto Scheduler will deactivate Manual Scheduler automatically.','wp-scheduled-posts') }, handleStatusChange );
        }else{
            setautoSchedulerStatus(event.target.checked);
        }
    }

    const handleStatusChange = ( status ) => {
        let manualSchedulerData = {...builderContext.values['manage_schedule']?.['manual_schedule'] };
        manualSchedulerData['is_active_status'] = false;
        builderContext.setFieldValue( ['manage_schedule', 'manual_schedule'], [...manualSchedulerData,]);
        setautoSchedulerStatus(status);
    };

    return (
        <div className={classNames('wprf-control', 'wprf-auto-scheduler', `wprf-${props.name}-auto-scheduler`, props?.classes)}>
            <ProToggle
                title={ __("Auto Scheduler",'wp-scheduled-posts') }
                sub_title={ __('To configure the Auto Scheduler Settings, check out this <a href="https://wpdeveloper.com/docs/wp-scheduled-posts/how-does-auto-scheduler-work/ ">Doc</a>') }
                name={name}
                is_pro={!is_pro}
                value={autoSchedulerStatus}
                handle_status_change={ handleAutoScheduleStatusToggle }
            />
            <div className={`content ${ !is_pro ? 'pro-deactivated' : ''}`}>
                <div className="start-time set-timing">
                    <div className="time-title">
                        <h4>{ __('Start Time','wp-scheduled-posts') }</h4>
                        <span>{ __('Default','wp-scheduled-posts') } : { __('12:00 AM','wp-scheduled-posts') }</span>
                    </div>
                    <div className="time">
                        <Time
                            value={ startSelectedTime }
                            name="time-select"
                            onChange={ (event) => handleTimeChange('start',event) }
                            is_pro={  is_pro }
                            classes='select-start-time main-select'
                        />
                    </div>
                </div>
                <div className="end-time set-timing">
                    <div className="time-title">
                        <h4>{ __('End Time','wp-scheduled-posts') }</h4>
                        <span>{ __('Default','wp-scheduled-posts') } : { __('11:45 PM','wp-scheduled-posts') }</span>
                    </div>
                    <div className="time">
                        <Time
                            value={ endSelectedTime }
                            onChange={ (event) => handleTimeChange('end',event) }
                            is_pro={  is_pro }
                            name="time-select"
                            classes='select-start-time main-select'
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