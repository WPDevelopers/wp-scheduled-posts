import React from 'react'
import { useField, useFormikContext } from 'formik'

const ManageScheduleToggle = ({
    id,
    title,
    subtitle,
    desc,
    groupName,
    arrayHelpers,
}) => {
    const { setFieldValue } = useFormikContext()
    const [field] = useField('manage_schedule.activeScheduleSystem')
    const [, scheduleName] = groupName.split('.')

    const saveToggleSchedule = (e) => {
        if (field.value === scheduleName) {
            setFieldValue('manage_schedule.activeScheduleSystem', '')
        } else {
            setFieldValue('manage_schedule.activeScheduleSystem', scheduleName)
        }
    }

    return (
        <div className='form-group'>
            <div className='form-info'>
                <label htmlFor={id}>
                    {title}{' '}
                    {field.value == scheduleName
                        ? '(Activated)'
                        : '(Deactivated)'}
                </label>
                {subtitle && <span className='sub-title'>{subtitle}</span>}
            </div>
            <div className='form-body'>
                <div className='checkbox_wrap'>
                    <div className='wpsp_switch'>
                        {arrayHelpers !== undefined && (
                            <input
                                type='checkbox'
                                checked={
                                    field.value == scheduleName ? true : false
                                }
                                onChange={(e) => saveToggleSchedule(e)}
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

export default ManageScheduleToggle
