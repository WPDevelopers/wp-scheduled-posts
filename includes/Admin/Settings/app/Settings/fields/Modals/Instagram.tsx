import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'

export default function Instagram({ platform, data, addProfileToggle, savedProfile }) {
    let error_message = '';
    if( data.length <= 0 ) {
        error_message = __('It seems that there are no Instagram accounts associated to your Facebook profile.', 'wp-scheduled-posts');
    }
    return (
        <>
           <div className='wpsp-modal-social-platform'>
                { data.length > 0 && 
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
                }
                { data.length <= 0 &&
                    <span className='profile-not-found-message'>{ error_message }</span>
                }
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
