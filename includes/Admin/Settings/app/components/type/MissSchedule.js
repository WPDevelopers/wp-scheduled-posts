import React from 'react'
import { useField } from 'formik'
import { __ } from '@wordpress/i18n'

const MissSchedule = ({
    id,
    title,
    subtitle,
    desc,
    setFieldValue,
    groupName,
    arrayHelpers,
    index,
    value,
}) => {
    const [field] = useField(id)
    return (
        <div className='miss-schedule'>
            <div className='group-item'>
                <div
                    className='form-info'
                    style={{ marginBottom: '15px', marginTop: '20px' }}
                >
                    <label htmlFor={id}>{title}</label>
                    <div className='wpsp_switch'>
                        {arrayHelpers !== undefined ? (
                            <input
                                type='checkbox'
                                checked={
                                    value !== undefined &&
                                    value[index] !== undefined
                                        ? value[index][id]
                                        : false
                                }
                                name={`${groupName}.${id}`}
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
                <p className='description' style={{ width: '80%' }}>
                    {desc}
                </p>
                <h3 className='doc-title'>
                    {__(
                        'Read Our Tutorials To Solve Missed Schedule Errors',
                        'wp-scheduled-posts'
                    )}
                </h3>
                <ul className='docs'>
                    <li>
                        <a
                            href='https://wpdeveloper.com/manage-missed-schedule-wordpress/'
                            target='_blank'
                        >
                            {__(
                                'How To Manage The Missed Schedule Error In WordPress',
                                'wp-scheduled-posts'
                            )}
                        </a>
                    </li>
                    <li>
                        <a
                            href='https://wpdeveloper.com/docs/wp-scheduled-posts/how-to-handle-the-missed-schedule-error-using-wp-scheduled-post/#0-toc-title'
                            target='_blank'
                        >
                            {__(
                                'How To Configure SchedulePress To Handle Missed Schedule Errors',
                                'wp-scheduled-posts'
                            )}
                        </a>
                    </li>
                </ul>
            </div>
            <div className='group-item'>
                <div className='video-box'>
                    <h3 className='doc-title'>
                        {__(
                            'Watch The Video Walkthrough',
                            'wp-scheduled-posts'
                        )}
                    </h3>
                    <iframe
                        frameBorder='0'
                        scrolling='no'
                        marginHeight='0'
                        marginWidth='0'
                        width='614.1'
                        height='345'
                        type='text/html'
                        src='https://www.youtube.com/embed/t0zVpg5ALos?autoplay=0&fs=0&iv_load_policy=3&showinfo=0&rel=0&cc_load_policy=0&start=0&end=0&origin=http://youtubeembedcode.com'
                    ></iframe>
                </div>
            </div>
        </div>
    )
}

export default MissSchedule
