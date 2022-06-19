import React, { useEffect, useState } from 'react'
import { FieldArray } from 'formik'
import { wpspGetPluginRootURI } from '../../utils/helper'
const Pinterest = ({ platform, fieldName, field, data, boards }) => {
    const [defaultBoard, setDefaultBoard] = useState('');

    useEffect(() => {
      setDefaultBoard(boards[0]?.id);
    }, [boards])

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
                            {data.map((item, index) => (
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
                                            <select onChange={event => setDefaultBoard(event.target.value)}>
                                                {
                                                    boards?.map(board => {
                                                        return <option value={board.id}>{board.name || board.id}</option>;
                                                    })
                                                }
                                            </select>
                                        </div>
                                        <div className='control'>
                                            <input
                                                type='checkbox'
                                                name={`${field.name}.${index}`}
                                                onChange={(e) => {
                                                    if (e.target.checked) {
                                                        console.log({default_board_name: defaultBoard});
                                                        return arrayHelpers.insert(
                                                            index,
                                                            {...item, boards, default_board_name: defaultBoard}
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
export default Pinterest
