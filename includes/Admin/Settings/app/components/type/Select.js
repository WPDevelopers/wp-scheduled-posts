import React from 'react'
import Select2 from 'react-select'
import { useField } from 'formik'

const Select = ({
    id,
    title,
    subtitle,
    multiple,
    desc,
    options,
    setFieldValue,
}) => {
    const [field] = useField(id)
    return (
        <div className='form-group'>
            <div className='form-info'>
                <label htmlFor={id}>{title}</label>
                <span className='sub-title'>{subtitle}</span>
            </div>
            <div className='form-body'>
                <Select2
                    isMulti={multiple === true ? true : false}
                    isClearable
                    id={field.id}
                    name={field.name}
                    options={Object.values(options).map((value, key) => ({
                        value: key,
                        label: value,
                    }))}
                    onChange={(option) => setFieldValue(field.name, option.key)}
                    value={[{ value: field.key, label: field.value }]}
                />
                <span className='desc'>{desc}</span>
            </div>
        </div>
    )
}

export default Select
