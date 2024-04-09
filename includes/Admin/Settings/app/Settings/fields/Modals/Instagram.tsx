import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'

export default function Instagram({ platform, data, addProfileToggle, savedProfile }) {
    const [isErrorMessage, setIsErrorMessage] = useState(false);
    console.log('data',data);
    
    return (
        <>
           <div className='wpsp-modal-social-platform'>
                <ul>
                    {data.map((item, index) => (
                        <li key={index}>
                            <div className='item-content'>
                                <div className='entry-thumbnail'>
                                    <img
                                        src={item.thumbnail_url}
                                        alt='logo'
                                    />
                                    <h4 className='entry-title'>
                                        {item.name}
                                    </h4>
                                </div>
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
                <button
                type="submit"
                className="wpsp-modal-save-account"
                onClick={(event) => {
                  event.preventDefault();
                  savedProfile(event)
                }}
                >{ __( 'Save','wp-scheduled-posts' ) }</button>
            </div>
        </>
    )
}
