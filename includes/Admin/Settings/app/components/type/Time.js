import React, { useState } from 'react'
import DatePicker from 'react-datepicker'
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
    let now = new Date()
    const [field] = useField(id)

    let startDate = field.value
        ? new Date(now.toLocaleDateString('en-US') + ' ' + field.value)
        : new Date()
    if (isNaN(startDate.getTime())) {
        startDate = new Date()
    }

    let groupNow = new Date()
    if (value && value[index] && value[index][id]) {
        groupNow = new Date(
            now.toLocaleDateString('en-US') + ' ' + value[index][id]
        )
        groupTimeFlug = true
    }
    if (isNaN(groupNow.getTime())) {
        groupNow = new Date()
    }
    return (
        <div className='form-group'>
            <div className='form-info'>
                <label htmlFor={id}>{title}</label>
                <span className='sub-title'>{subtitle}</span>
            </div>
            <div className='form-body'>
                {arrayHelpers !== undefined && groupTimeFlug ? (
                    <DatePicker
                        name={`${groupName}.${id}`}
                        selected={groupNow}
                        onChange={(date) =>
                            arrayHelpers.replace(index, {
                                [id]: date.toLocaleTimeString([], {
                                    timeStyle: 'short',
                                }),
                            })
                        }
                        showTimeSelect
                        showTimeSelectOnly
                        timeIntervals={15}
                        timeCaption='Time'
                        dateFormat='h:mm aa'
                    />
                ) : (
                    <DatePicker
                        id={id}
                        name={field.name}
                        selected={startDate}
                        onChange={(date) =>
                            setFieldValue(
                                id,
                                date.toLocaleTimeString([], {
                                    timeStyle: 'short',
                                })
                            )
                        }
                        showTimeSelect
                        showTimeSelectOnly
                        timeIntervals={15}
                        timeCaption='Time'
                        dateFormat='h:mm aa'
                    />
                )}
                <span className='desc'>{desc}</span>
            </div>
        </div>
    )
}

export default Time
