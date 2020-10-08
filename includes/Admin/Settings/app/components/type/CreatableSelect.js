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
    return (
        <div className='form-group'>
            <div className='form-info'>
                <label htmlFor={id}>{title}</label>
                <span className='sub-title'>{subtitle}</span>
            </div>
            <div className='form-body'>
                <CreatableSelect2
                    isClearable
                    isMulti={multiple === true ? true : false}
                    id={field.id}
                    name={field.name}
                    options={Object.values(options).map((value, key) => ({
                        value: key,
                        label: value,
                    }))}
                    onChange={(option) =>
                        setFieldValue(field.name, option.label)
                    }
                    value={[{ value: field.value, label: field.value }]}
                />
                <span className='desc'>{desc}</span>
            </div>
        </div>
    )
}

export default CreatableSelect
