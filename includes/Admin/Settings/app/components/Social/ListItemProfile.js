import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { wpspGetPluginRootURI } from './../../utils/helper'

export default function ListItemProfile({
    item,
    arrayHelpers,
    groupFieldStatus,
    fieldList,
    index,
}) {
    const [showItemControl, setItemControl] = useState(false);
    const boardName = item.boards?.find(board => board.id == item.default_board_name)?.name;
    console.log(item);
    return (
        <React.Fragment>
            <div
                className='wpscp-social-tab__item-list__single_item'
                key={index}
            >
                <div className='entry-thumbnail'>
                    <img src={item.thumbnail_url} alt='icon' />
                </div>
                <div className='entry-content'>
                    <h4 className='entry-content__title'>{item.name}</h4>
                    <p className='entry-content__doc'>
                        <strong>{item.added_by}</strong> on {item.added_date}
                    </p>
                    {
                        item.default_board_name && (
                            <p className='entry-content__doc'>
                                <strong>Default Board:</strong> {boardName}
                            </p>
                        )
                    }
                </div>
                <div className='entry-control'>
                    <div className='checkbox-toggle'>
                        <input
                            type='checkbox'
                            checked={
                                groupFieldStatus.value === true
                                    ? item.status
                                    : false
                            }
                            name={`${fieldList.name}.${index}`}
                            onChange={(e) => {
                                if (groupFieldStatus.value === true) {
                                    if (e.target.checked) {
                                        const itemTrue = {
                                            ...item,
                                            status: true,
                                        }
                                        return arrayHelpers.replace(
                                            index,
                                            itemTrue
                                        )
                                    } else {
                                        const itemFalse = {
                                            ...item,
                                            status: false,
                                        }
                                        return arrayHelpers.replace(
                                            index,
                                            itemFalse
                                        )
                                    }
                                }
                            }}
                        />
                    </div>
                    <div className='entry-control__more-link'>
                        <button
                            type='button'
                            className='btn-more-link'
                            onClick={() => setItemControl(!showItemControl)}
                        >
                            <img
                                src={
                                    wpspGetPluginRootURI +
                                    'assets/images/icon-more.png'
                                }
                                alt='more item'
                            />
                        </button>
                        {showItemControl && (
                            <ul className='entry-control__more-link__group_absolute'>
                                <li>
                                    <button
                                        type='button'
                                        className='btn btn-remove'
                                        onClick={() => {
                                            return arrayHelpers.remove(index)
                                        }}
                                    >
                                        {__('Remove', 'wp-scheduled-posts')}
                                    </button>
                                </li>
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </React.Fragment>
    )
}
