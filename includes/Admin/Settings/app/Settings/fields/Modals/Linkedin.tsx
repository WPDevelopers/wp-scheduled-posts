import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'

export default function Linkedin({ platform, data, addProfileToggle,savedProfile }) {
    let {profiles, pages, ...appData} = data;
    profiles = profiles ? profiles : [];
    profiles = profiles.map((val, i) => {
        return {...appData, ...val}
    });
    pages = pages ? pages : [];
    pages = pages.map((val, i) => {
        return {...appData, ...val}
    });
    
    return (
        <>
           <div className='wpsp-modal-social-platform'>
                <ul>
                    {pages.length > 0 && (
                        <li>{__('Pages:', 'wp-scheduled-posts')} </li>
                    )}
                    {pages.map((item, index) => (
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
                    {profiles.length > 0 && (
                        <li>{__('Profile:', 'wp-scheduled-posts')} </li>
                    )}
                    {profiles.map((item, index) => (
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
