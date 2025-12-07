import { __ } from '@wordpress/i18n';
import React from 'react'
import { handleImageError } from '../../helper/helper';

const Threads = ( { profiles, addProfileToggle,savedProfile } ) => {    
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
                    { profiles.length <= 0 && <p>{__('There are no profiles found on this account', 'wp-scheduled-posts')}</p> }
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