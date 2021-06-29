import React from 'react'
import { Field } from 'formik'

const Text = ({
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
    return (
        <div className='form-group'>
            <div className='form-info'>
                <label htmlFor={id}>{title}</label>
                <span className='sub-title'>{subtitle}</span>
            </div>
            <div className='form-body'>
                {arrayHelpers !== undefined ? (
                    <Field
                        className='text-field'
                        type='text'
                        name={`${groupName}.${id}`}
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
                        className='text-field'
                        type='text'
                        id={id}
                        name={id}
                        onChange={(e) => setFieldValue(id, e.target.value)}
                    />
                )}
                <span className='desc'>{desc}</span>
            </div>
        </div>
    )
}

export default Text
