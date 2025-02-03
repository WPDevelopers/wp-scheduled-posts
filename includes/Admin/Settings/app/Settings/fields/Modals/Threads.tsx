import { __ } from '@wordpress/i18n';
import React from 'react'

const Threads = ( { profiles, addProfileToggle,savedProfile } ) => {
    console.log('profiles', profiles);
    
    return (
        <>
            <div className='wpsp-modal-social-platform'>
                <ul>
                    {profiles.length > 0 && (
                        <li className='group-title'>{__('Profiles:', 'wp-scheduled-posts')} </li>
                    )}
                    {profiles.map((item, index) => (
                        <li id={'facebook_page_' + index} key={index}>
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

export default Threads