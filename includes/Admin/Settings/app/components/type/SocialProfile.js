import React from 'react'
import { Field } from 'formik'

const SocialProfile = ({ id, title, subtitle, desc, setFieldValue }) => {
    return (
        <div className='form-group'>
            <div className='form-info'>
                <label htmlFor={id}>{title}</label>
                <span className='sub-title'>{subtitle}</span>
            </div>
            <div className='form-body'>Social Profile</div>
        </div>
    )
}

export default SocialProfile
