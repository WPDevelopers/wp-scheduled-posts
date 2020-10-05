import React from 'react'
import { Field } from 'formik'

const Select = ({ id, title, subtitle, desc, options, setFieldValue }) => {
    return (
        <div className='form-group'>
            <div className='form-info'>
                <label htmlFor={id}>{title}</label>
                <span className='sub-title'>{subtitle}</span>
            </div>
            <div className='form-body'>
                <Field
                    as='select'
                    placeholder='Favorite Color'
                    id={id}
                    name={id}
                    onChange={(e) => setFieldValue(id, e.target.value)}
                >
                    {Object.keys(options).map((value, key) => (
                        <option value={key}>{value}</option>
                    ))}
                </Field>
                <span className='desc'>{desc}</span>
            </div>
        </div>
    )
}

export default Select
