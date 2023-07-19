import React,{useEffect, useState} from 'react'
import classNames from 'classnames';
import Select from 'react-select';
import { __ } from '@wordpress/i18n';
import { generateTimeOptions } from '../helper/helper';
import { selectStyles } from '../helper/styles';
import { useBuilderContext } from 'quickbuilder';

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
    const timeOptions = generateTimeOptions();

    const [selectDay, setSelectDay] = useState(options[0])
    const [selectTime, setSelectTime] = useState(timeOptions[0])
    const [savedManualSchedule,setSavedManualSchedule] = useState([]);
    const [formatedSchedule,setFormatedSchedule] = useState(builderContext.values['manage_schedule']?.[name]?.weekdata);
    console.log('manual-scheduler', props);
    
    const handleSavedManualSchedule = () => {
        setSavedManualSchedule(prevSchedule => {
            const updatedSchedule = [...prevSchedule];
            updatedSchedule.push({ [selectDay.value]: selectTime.value });
            return updatedSchedule;
        });
    }
    useEffect( () => {
        console.log(savedManualSchedule);
        
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
        onChange({
            target: {
                type: "auto-scheduler",
                name:["manage_schedule",name],
                value: formattedData,
                multiple,
            },
        });
    },[savedManualSchedule] )

    // useEffect(() => {

    //     let autoSchedulerObj = savedManualSchedule?.map( (item) => {
    //         let property_name = item?.day+'_post_limit';
    //         return { [property_name] : item?.value }
    //     } )
    //     autoSchedulerObj.push( { start_time : startSelectedTime?.value }  );
    //     autoSchedulerObj.push( { end_time : endSelectedTime?.value }  );
	// 	onChange({
	// 		target: {
	// 			type: "auto-scheduler",
	// 			name:["manage_schedule",name],
	// 			value: autoSchedulerObj,
	// 			multiple,
	// 		},
	// 	});
	// }, [savedManualSchedule]);
    
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
                            formatedSchedule[item.value]?.map( ( data,index ) => (
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