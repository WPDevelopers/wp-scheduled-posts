import React from 'react'
import TimePicker from 'rc-time-picker'
import moment from 'moment'
import { useField } from 'formik'

const Time = ({
    id,
    title,
    subtitle,
    desc,
    arrayHelpers,
    index,
    setFieldValue,
    groupName,
    value,
}) => {
    let groupTimeFlug = false
    let groupNow
    const [field] = useField(id)
    const format = 'h:mm a'
    const now = field.value
        ? moment(field.value, format)
        : moment().hour(0).minute(0)

    if (value && value[index] && value[index][id]) {
        groupNow = value[index][id]
            ? moment(value[index][id], format)
            : moment().hour(0).minute(0)
        groupTimeFlug = true
    }

    return (
        <div className='form-group'>
            <div className='form-info'>
                <label htmlFor={id}>{title}</label>
                <span className='sub-title'>{subtitle}</span>
            </div>
            <div className='form-body'>
                {arrayHelpers !== undefined && groupTimeFlug ? (
                    <TimePicker
                        name={`${groupName}.${id}`}
                        showSecond={false}
                        defaultValue={groupNow}
                        className='timepicker'
                        onChange={(value) =>
                            arrayHelpers.replace(index, {
                                [id]: value.format(format),
                            })
                        }
                        format={format}
                        use12Hours
                        inputReadOnly
                    />
                ) : (
                    <TimePicker
                        id={id}
                        name={field.name}
                        showSecond={false}
                        defaultValue={now}
                        className='timepicker'
                        onChange={(value) =>
                            setFieldValue(id, value.format(format))
                        }
                        format={format}
                        use12Hours
                        inputReadOnly
                    />
                )}
                <span className='desc'>{desc}</span>
            </div>
        </div>
    )
}

export default Time
