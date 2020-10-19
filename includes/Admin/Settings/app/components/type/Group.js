import { object } from 'prop-types'
import React from 'react'
import { useField, FieldArray } from 'formik'
import Fields from './../Fields'
const Group = ({ id, group, values }) => {
    return (
        <React.Fragment>
            {/* {group !== undefined &&
                Object.keys(group).length > 0 &&
                object.keys(group).map((index, item) => console.log(index))} */}
            {Object.entries(group).map(([index, item]) => (
                <div
                    id={`wpsp_${item.id}_template`}
                    className='wpsp-integ-item_section wpsp-integ-active'
                    key={index}
                >
                    <div className='wpsp-integ-bar wpsp-integ-active'>
                        <h3>{item.title}</h3>
                        <p>
                            To configure the Twitter Tweet Settings, check out
                            this{' '}
                            <a
                                className='docs'
                                href='https://wpdeveloper.net/docs/automatically-tweet-wordpress-posts/'
                                target='_blank'
                            >
                                Doc
                            </a>
                        </p>
                    </div>
                    <div className='wpsp-integ-content wpsp-social-integ-content'>
                        <FieldArray
                            name={id}
                            render={(arrayHelpers) => (
                                <div>
                                    {item.fields !== undefined &&
                                        item.fields.length > 0 &&
                                        item.fields.map(
                                            (fieldItem, fieldIndex) => (
                                                <Fields
                                                    {...fieldItem}
                                                    key={fieldIndex}
                                                    arrayHelpers={arrayHelpers}
                                                    index={fieldIndex}
                                                    value={values[id][item.id]}
                                                />
                                            )
                                        )}
                                </div>
                            )}
                        />
                    </div>
                </div>
            ))}
        </React.Fragment>
    )
}
export default Group
