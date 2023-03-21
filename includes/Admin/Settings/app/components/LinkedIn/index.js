import React from 'react'
import { FieldArray } from 'formik'
import { wpspGetPluginRootURI } from '../../utils/helper'
const LinkedIn = ({ platform, fieldName, field, data }) => {
    let {profiles, ...appData} = data;
    profiles = profiles ? profiles : [];
    profiles = profiles.map((val, i) => {
        return {...appData, ...val}
    });


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
                        <ul>
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
