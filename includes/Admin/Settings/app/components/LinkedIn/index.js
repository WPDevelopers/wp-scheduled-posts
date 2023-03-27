import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { FieldArray } from 'formik'
import { wpspGetPluginRootURI, wpspSettingsGlobal } from '../../utils/helper'
const LinkedIn = ({ platform, fieldName, field, data }) => {
    let {profiles, pages, ...appData} = data;
    profiles = profiles ? profiles : [];
    profiles = profiles.map((val, i) => {
        return {...appData, ...val}
    });
    pages = pages ? pages : [];
    pages = pages.map((val, i) => {
        return {...appData, ...val}
    });

    const [isErrorMessage, setIsErrorMessage] = useState(false)
    const addProfileToggle = (item, index, arrayHelpers, e) => {
        if (e.target.checked) {
            // free
            if (!wpspSettingsGlobal.pro_version) {
                e.target.disabled = true
                setIsErrorMessage(true)
                e.preventDefault()
                e.stopPropagation()
            } else {
                setIsErrorMessage(false)
                arrayHelpers.insert(index, item)
            }
        } else {
            e.target.disabled = false
            setIsErrorMessage(false)
            arrayHelpers.remove(index)
        }
    }

    return (
        <React.Fragment>
            <FieldArray
                name={fieldName}
                render={(arrayHelpers) => (
                    <div className='wpsp-modal-social-platform'>
                        <div className={'entry-head ' + platform}>
                            <img
                                src={
                                    wpspGetPluginRootURI +
                                    'assets/images/icon-' +
                                    platform +
                                    '-small-white.png'
                                }
                                alt='logo'
                            />
                            <h2 className='entry-head-title'>{platform}</h2>
                        </div>
                        {isErrorMessage && (
                            <div className='error-message'>
                                {__(
                                    'LinkedIn page is a Premium Feature. To use this feature,',
                                    'wp-scheduled-posts'
                                )}
                                {" "}
                                <a target="_blank" href='https://wpdeveloper.com/in/schedulepress-pro'>
                                    {__(
                                        'Upgrade to PRO.',
                                        'wp-scheduled-posts'
                                    )}
                                </a>
                            </div>
                        )}
                        <ul>
                            {pages.length > 0 && (
                                <li>{__('Pages:', 'wp-scheduled-posts')} </li>
                            )}
                            {pages.map((item, index) => (
                                <li key={index}>
                                    <div className='item-content'>
                                        <div className='entry-thumbnail'>
                                            <img
                                                src={item.thumbnail_url}
                                                alt='logo'
                                            />
                                        </div>
                                        <h4 className='entry-title'>
                                            {item.name}
                                        </h4>
                                        <div className='control'>
                                            <input
                                                type='checkbox'
                                                name={`${field.name}.${index}`}
                                                onChange={(e) =>
                                                    addProfileToggle(
                                                        item,
                                                        index,
                                                        arrayHelpers,
                                                        e
                                                    )
                                                }
                                            />
                                            <div></div>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                        <ul>
                            {profiles.length > 0 && (
                                <li>{__('Profile:', 'wp-scheduled-posts')} </li>
                            )}
                            {profiles.map((item, index) => (
                                <li key={index}>
                                    <div className='item-content'>
                                        <div className='entry-thumbnail'>
                                            <img
                                                src={item.thumbnail_url}
                                                alt='logo'
                                            />
                                        </div>
                                        <h4 className='entry-title'>
                                            {item.name}
                                        </h4>
                                        <div className='control'>
                                            <input
                                                type='checkbox'
                                                name={`${field.name}.${index}`}
                                                onChange={(e) => {
                                                    if (e.target.checked) {
                                                        return arrayHelpers.insert(
                                                            index,
                                                            item
                                                        )
                                                    } else {
                                                        return arrayHelpers.remove(
                                                            index
                                                        )
                                                    }
                                                }}

                                            />
                                            <div></div>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            />
        </React.Fragment>
    )
}
export default LinkedIn
