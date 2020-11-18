import React, { useState } from 'react'
import { wpspSettingsGlobal } from './../../utils/helper'
import { FieldArray, Form } from 'formik'
export default function Facebook({ fieldName, field, page, group }) {
    const [isErrorMessage, setIsErrorMessage] = useState(false)
    const addProfileToggle = (item, index, arrayHelpers, e) => {
        if (e.target.checked) {
            // free
            if (!wpspSettingsGlobal.pro_version) {
                if (field.value.length == 0) {
                    arrayHelpers.insert(index, item)
                } else {
                    setIsErrorMessage(true)
                    e.preventDefault()
                    e.stopPropagation()
                }
            } else {
                setIsErrorMessage(false)
                arrayHelpers.insert(index, item)
            }
        } else {
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
                        <div className='entry-head facebook'>
                            <img
                                src='https://itushar.me/dev/wp-content/plugins/wp-scheduled-posts/admin/assets/images/icon-facebook-small-white.png'
                                alt='logo'
                            />
                            <h2 className='entry-head-title'>Facebook</h2>
                        </div>
                        {isErrorMessage && (
                            <div className='error-message'>
                                Multi Profile is a Premium Feature. To use this
                                feature, Upgrade to PRO.
                            </div>
                        )}
                        <ul>
                            <li>Pages: </li>
                            {page.map((item, index) => (
                                <li id={'facebook_page_' + index} key={index}>
                                    <div className='item-content'>
                                        <div className='entry-thumbnail'>
                                            <img
                                                src='https://scontent-lax3-1.xx.fbcdn.net/v/t1.0-1/cp0/p50x50/104447021_103269271446191_8892114688067945178_o.png?_nc_cat=104&amp;_nc_sid=dbb9e7&amp;_nc_ohc=X_6m8nD-nooAX8Duvu3&amp;_nc_ht=scontent-lax3-1.xx&amp;oh=61b337157a9eca69e54506b10d5d42ac&amp;oe=5FAB5877'
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
                            <li>Group: </li>
                            {group.map((item, index) => (
                                <li id={'facebook_group_' + index} key={index}>
                                    <div className='item-content'>
                                        <div className='entry-thumbnail'>
                                            <img
                                                src='https://scontent-lax3-1.xx.fbcdn.net/v/t1.0-1/cp0/p50x50/104447021_103269271446191_8892114688067945178_o.png?_nc_cat=104&amp;_nc_sid=dbb9e7&amp;_nc_ohc=X_6m8nD-nooAX8Duvu3&amp;_nc_ht=scontent-lax3-1.xx&amp;oh=61b337157a9eca69e54506b10d5d42ac&amp;oe=5FAB5877'
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
                    </div>
                )}
            />
        </React.Fragment>
    )
}
