import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { handleImageError } from '../../helper/helper';

export default function Linkedin({ platform, data, addProfileToggle,savedProfile }) {
    let {profiles, pages, ...appData} = data;
    let account_type = localStorage.getItem('account_type');
    let all_profiles = [];
    let all_pages = [];
    if( account_type == 'profile' ) {
        all_profiles = profiles ? profiles : [];
        all_profiles = all_profiles.map((val, i) => {
            return {...appData, ...val}
        });
    }else if( account_type == 'page' ) {
        all_pages = pages ? pages : [];
        all_pages = all_pages.map((val, i) => {
            return {...appData, ...val}
        });
    }

    return (
        <>
           <div className='wpsp-modal-social-platform'>
                <ul>
                    {all_pages.length > 0 && (
                        <li className='group-title'>{__('Pages:', 'wp-scheduled-posts')} </li>
                    )}
                    {all_pages.map((item, index) => (
                        <li key={index}>
                            <div className='item-content'>
                                <div className='entry-thumbnail'>
                                    <img 
                                        src={`${item?.thumbnail_url}`} 
                                        alt={__(item?.name, 'wp-scheduled-posts')}
                                        onError={handleImageError} // Attach the error handler
                                    />
                                    <h4 className='entry-title'>
                                        {item?.name}
                                    </h4>
                                </div>
                                <div className='control'>
                                    <input
                                        type='checkbox'
                                        onChange={(e) =>
                                            addProfileToggle(
                                                item,
                                                index,
                                                e
                                            )
                                        }
                                    />
                                    <div></div>
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
                <ul>
                    {all_profiles.length > 0 && (
                        <li className='group-title'>{__('Profile:', 'wp-scheduled-posts')} </li>
                    )}
                    {all_profiles.map((item, index) => (
                        <li key={index}>
                            <div className='item-content'>
                                <div className='entry-thumbnail'>
                                    <img 
                                        src={`${item?.thumbnail_url}`} 
                                        alt={__(item?.name, 'wp-scheduled-posts')}
                                        onError={handleImageError} // Attach the error handler
                                    />
                                    <h4 className='entry-title'>
                                        {item?.name}
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
