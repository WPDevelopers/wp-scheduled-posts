import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { useBuilderContext } from 'quickbuilder';
import React, { useCallback, useEffect, useState } from 'react';
import Select from 'react-select';
import { generateTimeOptions } from '../helper/helper';
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
  const [savedManualSchedule, setSavedManualSchedule] = useState(builderContext.values['manage_schedule']?.[name] ?? []);
  const [manualSchedulerStatus, setManualSchedulerStatus] = useState( builderContext.values['manage_schedule']?.[name]?.['is_active_status'] ?? false);
  
  // useEffect(() => {
  //   setManualSchedulerStatus( manualSchedulerStatusDBData )
  // }, [manualSchedulerStatusDBData])

  const handleSavedManualSchedule = () => {
    setSavedManualSchedule( (prevSchedule) => {
      const updatedSchedule = prevSchedule;
      // @ts-ignore 
      if( updatedSchedule?.weekdata?.[selectDay.value] ) {
        updatedSchedule?.weekdata?.[selectDay.value].push( selectTime?.value );
      }else{
        updatedSchedule.weekdata[selectDay.value] = [selectTime?.value];
      }
      return updatedSchedule;
    } );
  };
  useEffect(() => {
    if (savedManualSchedule.length > 0) {
      let weekdata = savedManualSchedule.weekdata;
      if( !is_pro ) {
        weekdata = { saturday: ['12:00 AM','12:30 AM','1:00 AM','3:00 PM'], sunday: ['2:00 AM','3:00 PM','1:15 AM','3:15 AM'], monday: ['4:15 AM','4:30 AM','5:00 AM','5:30 PM'], tuesday: ['11:00 AM','1:30 AM','10:00 AM','3:00 PM'], wednesday: ['9:00 AM','7:30 AM','8:00 AM','10:00 PM'], thursday: ['6:00 AM','3:30 AM','4:00 AM','6:00 PM'], friday:['9:00 AM','2:30 AM','5:00 AM','9:00 PM']  };
      }
      let manualSchedulerData = {};
      manualSchedulerData['weekdata'] = weekdata;
      manualSchedulerData['is_active_status'] = manualSchedulerStatus;
      if( !is_pro ) {
        onChange({
          target: {
            type: 'manual-scheduler',
            name: ['manage_schedule', name],
            value: manualSchedulerData,
            multiple,
          },
        });
      }
    }
  }, [savedManualSchedule, manualSchedulerStatus]);

  // let autoSchedulerObj = builderContext.values['manage_schedule']?.['auto_schedule'];
  // let isActiveStatusIndex = autoSchedulerObj?.findIndex(obj => obj.hasOwnProperty("is_active_status"));
  const handleManualScheduleStatusToggle = (event) => {
  //   if (isActiveStatusIndex !== -1) {
  //     const isAutoActive = autoSchedulerObj[isActiveStatusIndex].is_active_status;
  //     if( isAutoActive && event.target.checked  ) {
  //       SweetAlertStatusChangingMsg({ status: event.target.checked }, handleStatusChange);
  //     }else{
    //     }
    //   }
    setManualSchedulerStatus(event.target.checked);
  };

  // Handle status change after confirm from popup alert
  // const handleStatusChange = ( status ) => {
  //   autoSchedulerObj[isActiveStatusIndex].is_active_status = false;
  //   // builderContext.setFieldValue(['manage_schedule', 'auto_schedule'], [...autoSchedulerObj]);
  //   setManualSchedulerStatus(status);
  // }
  console.log('saved-manual-scheduler-data',savedManualSchedule);
  
  const weekData = useCallback(
    (item, optionIndex) => (
      <div className="week-wrapper" key={optionIndex}>
        <div
          className="week">
          <h6>{item.label}</h6>
          <span className="week-inner">
            { savedManualSchedule?.weekdata?.[item.value]?.map((data, index) => (
              <span key={index}>
                {data}
                <i
                  onClick={() => {
                    if( is_pro ) {
                      const updatedSchedule = savedManualSchedule.filter(
                        (_item) => {
                          const propertyValue = _item[item.value];
                          return propertyValue !== data;
                        }
                      );
                      setSavedManualSchedule(updatedSchedule);
                    }
                  }}
                  className="wpsp-icon wpsp-close"></i>
              </span>
            )) }
          </span>
        </div>
      </div>
    ),
    [options,savedManualSchedule],
  )
  

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
        value={manualSchedulerStatus}
        handle_status_change={handleManualScheduleStatusToggle}
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
        {options.map( weekData )}
      </div>
    </div>
  );
};

export default ManualScheduler;
