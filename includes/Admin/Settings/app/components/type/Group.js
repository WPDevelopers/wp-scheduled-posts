import { object } from 'prop-types'
import React from 'react'
import { FieldArray } from 'formik'
import Fields from './../Fields'
const Group = ({ id, group, values }) => {
    return (
        <React.Fragment>
            {Object.entries(group).map(([index, item]) => (
                <div
                    id={`wpsp_${item.id}`}
                    className='wpsp-integ-item_section wpsp-integ-active'
                    key={index}
                >
                    <div className='wpsp-integ-bar wpsp-integ-active'>
                        <h3>{item.title}</h3>
                        {item.subtitle !== undefined && (
                            <p
                                dangerouslySetInnerHTML={{
                                    __html: item.subtitle,
                                }}
                            ></p>
                        )}
                    </div>
                    <div className={'group-fields ' + item.id}>
                        <FieldArray
                            name={`${id}.${item.id}`}
                            render={(arrayHelpers) => (
                                <React.Fragment>
                                    {item.fields !== undefined &&
                                        item.fields.length > 0 &&
                                        item.fields.map(
                                            (fieldItem, fieldIndex) => (
                                                <Fields
                                                    {...fieldItem}
                                                    key={fieldIndex}
                                                    arrayHelpers={arrayHelpers}
                                                    groupName={`${id}.${item.id}`}
                                                    index={fieldIndex}
                                                    value={values[id][item.id]}
                                                />
                                            )
                                        )}
                                </React.Fragment>
                            )}
                        />
                    </div>
                </div>
            ))}
        </React.Fragment>
    )
}
export default Group
