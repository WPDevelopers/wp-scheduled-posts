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
            <div className='man_options'>
                <FieldArray
                    name={`${groupName}.${id}.${selectDay.value}`}
                    render={(arrayHelpers) => (
                        <div>
                            <ul className='wpsp_man_time_setting'>
                                <li>
                                    <span>Select Days</span>
                                    <Select2
                                        value={selectDay}
                                        onChange={(option) =>
                                            setSelectDay(option)
                                        }
                                        options={options}
                                        isMulti={false}
                                    />
                                </li>
                                <li>
                                    <span>Time Settings</span>
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
                                    <input
                                        type='button'
                                        onClick={() => {
                                            arrayHelpers.insert([], selectTime)
                                        }}
                                        value='Save Schedule'
                                    />
                                </li>
                            </ul>
                        </div>
                    )}
                />
            </div>
            <ul className='schedule-list'>
                {value !== undefined &&
                    Object.entries(value[id]).map(([index, item]) => (
                        <li data-day={index} key={index}>
                            <span>{index}</span>
                            {item.map((item, index) => (
                                <button key={index}>{item}</button>
                            ))}
                        </li>
                    ))}
            </ul>
        </React.Fragment>
    )
}

export default ScheduleTable
