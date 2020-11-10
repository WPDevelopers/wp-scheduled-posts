import React from 'react'
import { useField } from 'formik'
import Select2 from 'react-select'

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
    let modifiedOptions = Object.entries(options).map(([key, value]) => ({
        value: key,
        label: value,
    }))

    const onChange = (option) => {
        if (option == null) {
            return setFieldValue(field.name, '')
        }
        return setFieldValue(
            field.name,
            multiple ? option.map((item) => item.value) : option.value
        )
    }
    const getValue = () => {
        if (modifiedOptions) {
            return multiple
                ? modifiedOptions.filter(
                      (option) => field.value.indexOf(option.value) >= 0
                  )
                : modifiedOptions.find((option) => option.value === field.value)
        } else {
            return multiple ? [] : ''
        }
    }
    return (
        <div className='form-group'>
            <div className='form-info'>
                <label htmlFor={id}>{title}</label>
                <span className='sub-title'>{subtitle}</span>
            </div>
            <div className='form-body'>
                <Select2
                    id={field.id}
                    name={field.name}
                    value={getValue()}
                    onChange={onChange}
                    options={modifiedOptions}
                    isMulti={multiple === true ? true : false}
                />
                <span className='desc'>{desc}</span>
            </div>
        </div>
    )
}

export default Select
