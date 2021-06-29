import { object } from 'prop-types'
import React from 'react'
import { FieldArray } from 'formik'
import Fields from './../Fields'
const Group = ({ id, group, values }) => {
    return (
        <React.Fragment>
            {Object.entries(group).map(([index, item]) => (
                <div id={`wpsp_${item.id}`} className='group-item' key={index}>
                    <div className='group-item group-item__head'>
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
                                                <React.Fragment
                                                    key={fieldIndex}
                                                >
                                                    {values[id] &&
                                                    values[id][item.id] ? (
                                                        <Fields
                                                            {...fieldItem}
                                                            key={fieldIndex}
                                                            arrayHelpers={
                                                                arrayHelpers
                                                            }
                                                            groupName={`${id}.${item.id}`}
                                                            index={fieldIndex}
                                                            value={
                                                                values[id][
                                                                    item.id
                                                                ]
                                                            }
                                                        />
                                                    ) : (
                                                        <Fields
                                                            {...fieldItem}
                                                            key={fieldIndex}
                                                            arrayHelpers={
                                                                arrayHelpers
                                                            }
                                                            groupName={`${id}.${item.id}`}
                                                            index={fieldIndex}
                                                        />
                                                    )}
                                                </React.Fragment>
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
