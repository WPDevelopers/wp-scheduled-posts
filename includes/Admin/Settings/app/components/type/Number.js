import React from 'react'
import { Field } from 'formik'

const Number = ({
    id,
    title,
    subtitle,
    desc,
    arrayHelpers,
    index,
    setFieldValue,
    groupName,
    value,
    ...props
}) => {
    return (
        <div className='form-group'>
            <div className='form-info'>
                <label htmlFor={id}>{title}</label>
                <span className='sub-title'>{subtitle}</span>
            </div>
            <div className='form-body'>
                {arrayHelpers !== undefined ? (
                    <Field
                        className='number-field'
                        type='number'
                        name={`${groupName}.${id}`}
                        max={props?.max}
                        min={props?.min}
                        value={
                            value !== undefined && value[index] !== undefined
                                ? value[index][id]
                                : ''
                        }
                        onChange={(e) =>
                            arrayHelpers.replace(index, {
                                [id]: e.target.value,
                            })
                        }
                    />
                ) : (
                    <Field
                        className='number-field'
                        type='number'
                        id={id}
                        name={id}
                        max={props?.max}
                        min={props?.min}
                        onChange={(e) => setFieldValue(id, e.target.value)}
                    />
                )}
                <span className='desc'>{desc}</span>
            </div>
        </div>
    )
}

export default Number
