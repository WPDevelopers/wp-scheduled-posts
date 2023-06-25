import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'

export default function Twitter({ platform, data,addProfileToggle }) {
    const [isErrorMessage, setIsErrorMessage] = useState(false);
    return (
        <>
           <div className='wpsp-modal-social-platform'>
                <div className={'entry-head ' + platform}>
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
                                    <input
                                        type='checkbox'
                                        onChange={(e) => {
                                            addProfileToggle(
                                                item,
                                                index,
                                                e
                                            )
                                        }}
                                    />
                                    <div></div>
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            </div>
        </>
    )
}
