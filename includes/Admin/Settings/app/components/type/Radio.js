import React from 'react'
import { useField } from 'formik'

const Radio = ({
    id,
    title,
    subtitle,
    options,
    desc,
    setFieldValue,
    groupName,
    arrayHelpers,
    index,
    value,
}) => {
    const [field] = useField(id)
    return (
        <div className='form-group'>
            <div className='form-info'>
                <label htmlFor={id}>{title}</label>
                <span className='sub-title'>{subtitle}</span>
            </div>
            <div className='form-body'>
                {Object.keys(options).map((item, optionIndex) => (
                    <div className='radio-item' key={optionIndex}>
                        <label>
                            {arrayHelpers !== undefined ? (
                                <input
                                    value={item}
                                    checked={
                                        value !== undefined &&
                                        value[index] !== undefined
                                            ? value[index][id] === item
                                            : ''
                                    }
                                    name={`${groupName}.${id}`}
                                    type='radio'
                                    onChange={(e) =>
                                        arrayHelpers.replace(index, {
                                            [id]: e.target.value,
                                        })
                                    }
                                />
                            ) : (
                                <input
                                    value={item}
                                    checked={field.value == item}
                                    name={id}
                                    type='radio'
                                    onChange={() =>
                                        setFieldValue(field.name, item)
                                    }
                                />
                            )}
                            {options[item]}
                        </label>
                    </div>
                ))}
                <span className='desc'>{desc}</span>
            </div>
        </div>
    )
}

export default Radio
