import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { useBuilderContext } from 'quickbuilder';
import React, { useEffect, useMemo, useState } from 'react';
import Select from 'react-select';
import { SweetAlertStatusChangingMsg } from '../ToasterMsg';
import { convertTo12HourFormat, generateTimeOptions } from '../helper/helper';
import { selectStyles } from '../helper/styles';
import ProToggle from './utils/ProToggle';

const ManualScheduler = (props) => {
  const builderContext = useBuilderContext();
  let { name, multiple, onChange } = props;
  // @ts-ignore
  let is_pro = wpspSettingsGlobal?.pro_version ? true : false;

  const options = [
    { value: 'saturday', label: 'Sat' },
    { value: 'sunday', label: 'Sun' },
    { value: 'monday', label: 'Mon' },
    { value: 'tuesday', label: 'Tue' },
    { value: 'wednesday', label: 'Wed' },
    { value: 'thursday', label: 'Thu' },
    { value: 'friday', label: 'Fri' },
  ];
  const timeOptions = generateTimeOptions();
  const [selectDay, setSelectDay] = useState(options[0]);
  const [selectTime, setSelectTime] = useState(timeOptions[0]);
  let defaultWeekData = {};
  if( !is_pro && !builderContext.values['manage_schedule']?.[name]?.weekdata ) {
    defaultWeekData =  { weekdata : { saturday: ['12:00 AM','12:30 AM','1:00 AM','3:00 PM'], sunday: ['2:00 AM','3:00 PM','1:15 AM','3:15 AM'], monday: ['4:15 AM','4:30 AM','5:00 AM','5:30 PM'], tuesday: ['11:00 AM','1:30 AM','10:00 AM','3:00 PM'], wednesday: ['9:00 AM','7:30 AM','8:00 AM','10:00 PM'], thursday: ['6:00 AM','3:30 AM','4:00 AM','6:00 PM'], friday:['9:00 AM','2:30 AM','5:00 AM','9:00 PM']  } }
  }

  const [savedManualSchedule, setSavedManualSchedule] = useState(builderContext.values['manage_schedule']?.[name] ?? defaultWeekData );
  const [manualSchedulerStatus, setManualSchedulerStatus] = useState( builderContext.values['manage_schedule']?.[name]?.['is_active_status'] || false);


  useMemo(() => {
    setManualSchedulerStatus( builderContext.values['manage_schedule']?.[name]?.['is_active_status'] || false );
  }, [builderContext.values['manage_schedule']?.[name]?.['is_active_status']])

  const handleSavedManualSchedule = () => {
    setSavedManualSchedule( (prevSchedule) => {
      const updatedSchedule = {weekdata: {}, ...prevSchedule};
      // @ts-ignore
      if( updatedSchedule?.weekdata?.[selectDay.value] ) {
        if( !updatedSchedule?.weekdata?.[selectDay.value].includes(selectTime?.value) ){
          updatedSchedule?.weekdata?.[selectDay.value].push( selectTime?.value );
        }
      }else{
        updatedSchedule.weekdata[selectDay.value] = [selectTime?.value];
      }
      return updatedSchedule;
    } );
  };

  const handleDeleteManualSchedule = ( item, data ) => {
    setSavedManualSchedule( (prevSchedule) => {
      const updatedSchedule = {weekdata: {}, ...prevSchedule};
      // @ts-ignore
      updatedSchedule?.weekdata?.[item.value] = updatedSchedule?.weekdata?.[item.value]?.filter( time => time !== data);
      return updatedSchedule;
    } );
  }

  useEffect(() => {
    let manage_schedule = builderContext.values['manage_schedule'] ?? {};
    let weekdata = savedManualSchedule.weekdata;
    let manualSchedulerData = {};
    manualSchedulerData['weekdata'] = weekdata;
    manualSchedulerData['is_active_status'] = manualSchedulerStatus ?? false;
    manage_schedule[name] = manualSchedulerData;
    if( manualSchedulerStatus ) {
      manage_schedule['activeScheduleSystem'] = 'manual_schedule';
    }
    if( is_pro ) {
      onChange({
        target: {
          type: 'manual-scheduler',
          name: ['manage_schedule', name],
          value: manualSchedulerData,
          multiple,
        },
      });
    }
  }, [savedManualSchedule, manualSchedulerStatus]);

  // Handle status changing for auto & manual scheduler
  const handleManualScheduleStatusToggle = (event) => {
    let manualScheduleStatus = builderContext.values['manage_schedule']?.['auto_schedule']?.['is_active_status'];
      if(  manualScheduleStatus && event.target.checked ) {
          SweetAlertStatusChangingMsg({ status: event.target.checked,text : __('Enabling Manual Scheduler will deactivate Auto Scheduler automatically.','wp-scheduled-posts') }, handleStatusChange);
      }else{
          setManualSchedulerStatus(event.target.checked);
      }
  }

  const handleStatusChange = ( status ) => {
      let autoSchedulerData = {...builderContext.values['manage_schedule']?.['auto_schedule'] };
      autoSchedulerData['is_active_status'] = false;
      builderContext.setFieldValue(['manage_schedule', 'auto_schedule'], [...autoSchedulerData]);
      setManualSchedulerStatus(status);
  };

  return (
    <div
      key={name}
      className={classNames(
        'wprf-control',
        'wprf-manual-scheduler',
        `wprf-${props.name}-manual-scheduler`,
        props?.classes
      )}>
      <ProToggle
        key={'pro-toggle'}
        title={__('Manual Scheduler', 'wp-scheduled-posts')}
        sub_title={__(
          'To configure the Manual Scheduler Settings, check out this <a href="https://wpdeveloper.com/docs/wp-scheduled-posts/how-does-manual-scheduler-work/" target="_blank">Doc</a>'
        )}
        name={name}
        is_pro={!is_pro}
        value={ manualSchedulerStatus }
        handle_status_change={ handleManualScheduleStatusToggle }
      />
      <div key='content' className={`content ${!is_pro ? 'pro-deactivated' : ''}`}>
        <Select
          styles={selectStyles}
          className="select-days main-select"
          value={selectDay}
          onChange={(option) => setSelectDay(option)}
          options={options}
          isMulti={false}
          isDisabled={!is_pro}
        />
        <Select
          styles={selectStyles}
          className="select-days main-select"
          value={selectTime}
          onChange={(option) => setSelectTime(option)}
          options={timeOptions}
          isMulti={false}
          isDisabled={!is_pro}
        />
        <button
          onClick={handleSavedManualSchedule}
          disabled={!is_pro}>
          {__('Add', 'wp-scheduled-posts')}
        </button>
      </div>
      <div key='weeks' className={`weeks ${!is_pro ? 'pro-deactivated' : ''}`}>
        {options.map( (item, optionIndex) => (
          <div className="week-wrapper" key={optionIndex}>
            <div
              className="week">
              <h6>{item.label}</h6>
              <span className="week-inner">
                { savedManualSchedule?.weekdata?.[item.value]?.map((data, index) => (
                  <span key={index}>
                    { convertTo12HourFormat( data ) }
                    <i
                      onClick={ () => {
                        if( is_pro ) {
                          handleDeleteManualSchedule(item, data)
                        }
                      }}
                      className="wpsp-icon wpsp-close"></i>
                  </span>
                )) }
              </span>
            </div>
          </div>
        ) )}
      </div>
    </div>
  );
};

export default ManualScheduler;
