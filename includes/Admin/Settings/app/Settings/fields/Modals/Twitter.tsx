import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { handleImageError } from '../../helper/helper';

export default function Twitter({ platform, data, addProfileToggle, savedProfile }) {
    const [isErrorMessage, setIsErrorMessage] = useState(false);
    return (
        <>
           <div className='wpsp-modal-social-platform'>
                <ul>
                    {data.map((item, index) => (
                        <li key={index}>
                            <div className='item-content'>
                                <div className='entry-thumbnail'>
                                    <img 
                                        src={`${item?.thumbnail_url}`} 
                                        alt={__(item?.name, 'wp-scheduled-posts')}
                                        onError={handleImageError} // Attach the error handler
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
