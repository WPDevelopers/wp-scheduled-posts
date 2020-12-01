import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import DatePicker from 'react-datepicker'
import Select2 from 'react-select'
import { FieldArray } from 'formik'
const ScheduleTable = ({
    id,
    title,
    subtitle,
    desc,
    groupName,
    index,
    setFieldValue,
    value,
}) => {
    const options = [
        { value: 'saturday', label: 'Sat' },
        { value: 'sunday', label: 'Sun' },
        { value: 'monday', label: 'Mon' },
        { value: 'tuesday', label: 'Tue' },
        { value: 'wednesday', label: 'Wed' },
        { value: 'thursday', label: 'Thu' },
        { value: 'friday', label: 'Fri' },
    ]
    const [selectDay, setSelectDay] = useState(options[0])
    const [selectTime, setSelectTime] = useState(new Date())
    return (
        <React.Fragment>
            <div className='manual-schedule'>
                <FieldArray
                    name={`${groupName}.${index}.${id}.[${selectDay.value}]`}
                    render={(arrayHelpers) => (
                        <div>
                            <ul className='manual-schedule-builder'>
                                <li>
                                    <span>
                                        {__(
                                            'Select Days',
                                            'wp-scheduled-posts'
                                        )}
                                    </span>
                                    <Select2
                                        className='select-days'
                                        value={selectDay}
                                        onChange={(option) =>
                                            setSelectDay(option)
                                        }
                                        options={options}
                                        isMulti={false}
                                    />
                                </li>
                                <li>
                                    <span>
                                        {__(
                                            'Select Time',
                                            'wp-scheduled-posts'
                                        )}
                                    </span>
                                    <DatePicker
                                        selected={selectTime}
                                        onChange={(date) => setSelectTime(date)}
                                        showTimeSelect
                                        showTimeSelectOnly
                                        timeIntervals={15}
                                        timeCaption='Time'
                                        dateFormat='h:mm aa'
                                    />
                                </li>
                                <li>
                                    <button
                                        className='btn-schedule'
                                        type='button'
                                        onClick={() => {
                                            arrayHelpers.insert(
                                                [],
                                                selectTime.toLocaleTimeString(
                                                    [],
                                                    {
                                                        timeStyle: 'short',
                                                    }
                                                )
                                            )
                                        }}
                                    >
                                        {__(
                                            'Save Schedule',
                                            'wp-scheduled-posts'
                                        )}
                                    </button>
                                </li>
                            </ul>
                        </div>
                    )}
                />
                <ul className='schedule-list'>
                    {options.map((item, optionIndex) => (
                        <FieldArray
                            key={optionIndex}
                            name={`${groupName}.${index}.${id}.${item.value}`}
                            render={(arrayHelpers) => (
                                <li data-day={optionIndex} key={optionIndex}>
                                    <span className='dayname'>
                                        {item.label}
                                    </span>
                                    {value !== undefined &&
                                        value[index].weekdata !== null &&
                                        value[index].weekdata[item.value] !==
                                            undefined &&
                                        value[index].weekdata[item.value].map(
                                            (item, index) => (
                                                <span key={index}>
                                                    {item}
                                                    <button
                                                        type='button'
                                                        onClick={() => {
                                                            arrayHelpers.remove(
                                                                index
                                                            )
                                                        }}
                                                    >
                                                        <span className='dashicons dashicons-no-alt'></span>
                                                    </button>
                                                </span>
                                            )
                                        )}
                                </li>
                            )}
                        />
                    ))}
                </ul>
            </div>
        </React.Fragment>
    )
}

export default ScheduleTable
