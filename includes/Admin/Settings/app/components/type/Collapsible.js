import React from 'react'
import { useField } from 'formik'

const Collapsible = ({ id, title, subtitle, desc, setFieldValue }) => {
    const [field] = useField(id)
    return (
        <div className='form-group'>
            <div className='form-info'>
                <label htmlFor={id}>{title}</label>
                {subtitle && <span className='sub-title'>{subtitle}</span>}
            </div>
            <div className='form-body'>
                <button
                    className='btn-collapsible'
                    type='button'
                    onClick={() => setFieldValue(field.name, !field.value)}
                >
                    {field.value == true ? (
                        <i className='dashicons-before dashicons-arrow-up-alt2'></i>
                    ) : (
                        <i className='dashicons-before dashicons-arrow-down-alt2'></i>
                    )}
                </button>
            </div>
        </div>
    )
}

export default Collapsible
