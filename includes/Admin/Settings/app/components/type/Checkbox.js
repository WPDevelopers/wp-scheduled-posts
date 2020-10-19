import React from 'react'
import { useField } from 'formik'

const Checkbox = ({
    id,
    title,
    subtitle,
    desc,
    setFieldValue,
    arrayHelpers,
    index,
    value,
}) => {
    const [field] = useField(id)
    if (value !== undefined) {
        console.log(value[id])
    }
    return (
        <div className='form-group'>
            <div className='form-info'>
                <label htmlFor={id}>{title}</label>
                {subtitle && <span className='sub-title'>{subtitle}</span>}
            </div>
            <div className='form-body'>
                <div className='checkbox_wrap'>
                    <div className='wpsp_switch'>
                        {arrayHelpers !== undefined ? (
                            <input
                                type='checkbox'
                                // checked={
                                //     value !== undefined &&
                                //     value[index] !== undefined
                                //         ? value[index][id]
                                //         : false
                                // }
                                name={id}
                                onChange={(e) =>
                                    arrayHelpers.replace(index, {
                                        [id]: e.target.checked,
                                    })
                                }
                            />
                        ) : (
                            <input
                                type='checkbox'
                                checked={field.value}
                                name={field.name}
                                onChange={() =>
                                    setFieldValue(field.name, !field.value)
                                }
                            />
                        )}

                        <span className='wpsp_switch_slider'></span>
                    </div>
                </div>
                {desc && <span className='desc'>{desc}</span>}
            </div>
        </div>
    )
}

export default Checkbox
