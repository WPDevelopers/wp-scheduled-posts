import React, { useState } from 'react'
import TimePicker from 'rc-time-picker'
import Select2 from 'react-select'
import { FieldArray } from 'formik'
import moment from 'moment'
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
    const format = 'h:mm a'
    const now = moment().hour(0).minute(0)
    const options = [
        { value: 'saturday', label: 'Saturday' },
        { value: 'sunday', label: 'Sunday' },
        { value: 'monday', label: 'Monday' },
        { value: 'tuesday', label: 'Tuesday' },
        { value: 'wednesday', label: 'Wednesday' },
        { value: 'thursday', label: 'Thursday' },
        { value: 'friday', label: 'Friday' },
    ]
    const [selectDay, setSelectDay] = useState(options[0])
    const [selectTime, setSelectTime] = useState(now.format(format))
    return (
        <React.Fragment>
            <div className='manual-schedule'>
                <FieldArray
                    name={`${groupName}.${index}.${id}.[${selectDay.value}]`}
                    render={(arrayHelpers) => (
                        <div>
                            <ul className='manual-schedule-builder'>
                                <li>
                                    <span>Select Days</span>
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
                                    <span>Select Time</span>
                                    <TimePicker
                                        showSecond={false}
                                        defaultValue={now}
                                        className='xxx'
                                        onChange={(value) =>
                                            setSelectTime(value.format(format))
                                        }
                                        format={format}
                                        use12Hours
                                        inputReadOnly
                                    />
                                </li>
                                <li>
                                    <button
                                        className='btn-schedule'
                                        type='button'
                                        onClick={() => {
                                            arrayHelpers.insert([], selectTime)
                                        }}
                                    >
                                        Save Schedule
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
                                    <span>{item.label}</span>
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
