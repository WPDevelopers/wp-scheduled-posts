import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'

export default function Facebook({ page, group, addProfileToggle }) {
    const [isErrorMessage, setIsErrorMessage] = useState(false);
    return (
        <>
            <div className='wpsp-modal-social-platform'>
            
                {isErrorMessage && (
                    <div className='error-message'>
                        {__(
                            'Multi Profile is a Premium Feature. To use this feature,',
                            'wp-scheduled-posts'
                        )}
                        {" "}
                        <a target="_blank" href='https://wpdeveloper.com/in/schedulepress-pro'>
                            {__(
                                'Upgrade to PRO.',
                                'wp-scheduled-posts'
                            )}
                        </a>
                    </div>
                )}
                <ul>
                    {page.length > 0 && (
                        <li>{__('Pages:', 'wp-scheduled-posts')} </li>
                    )}
                    {page.map((item, index) => (
                        <li id={'facebook_page_' + index} key={index}>
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

                    {group.length > 0 && (
                        <li>{__('Group:', 'wp-scheduled-posts')} </li>
                    )}

                    {group.map((item, index) => (
                        <li id={'facebook_group_' + index} key={index}>
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
            </div>
        </>
    )
}
