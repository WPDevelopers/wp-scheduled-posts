import React from 'react'
import CreatableSelect2 from 'react-select/creatable'
import { useField } from 'formik'

const CreatableSelect = ({
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

    const onChange = (option, actionMeta) => {
        if (option == null) {
            return setFieldValue(field.name, '')
        }
        return setFieldValue(
            field.name,
            multiple ? option.map((item) => item.value) : option.value
        )
    }
    const getValue = () => {
        return Object.entries(field.value).map(([key, value]) => ({
            value: value,
            label: value,
        }))
    }
    return (
        <div className='form-group'>
            <div className='form-info'>
                <label htmlFor={id}>{title}</label>
                <span className='sub-title'>{subtitle}</span>
            </div>
            <div className='form-body'>
                <CreatableSelect2
                    isClearable
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

export default CreatableSelect
