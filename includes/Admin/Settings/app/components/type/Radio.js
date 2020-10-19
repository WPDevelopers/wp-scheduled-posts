import React from 'react'
import { useField } from 'formik'

const Radio = ({
    id,
    title,
    subtitle,
    options,
    desc,
    setFieldValue,
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
                        {arrayHelpers !== undefined ? (
                            <input
                                id={id + item}
                                // value={
                                //     value !== undefined &&
                                //     value[index] !== undefined
                                //         ? value[index][id]
                                //         : ''
                                // }
                                // checked={
                                //     value !== undefined &&
                                //     value[index] !== undefined
                                //         ? value[index][id] == item
                                //         : false
                                // }
                                name={id}
                                type='radio'
                                onChange={() =>
                                    arrayHelpers.replace(index, {
                                        [id]: item,
                                    })
                                }
                            />
                        ) : (
                            <input
                                id={id + item}
                                value={item}
                                checked={field.value == item}
                                name={id}
                                type='radio'
                                onChange={() => setFieldValue(field.name, item)}
                            />
                        )}
                        <label htmlFor={id + item}>{options[item]}</label>
                    </div>
                ))}
                <span className='desc'>{desc}</span>
            </div>
        </div>
    )
}

export default Radio
