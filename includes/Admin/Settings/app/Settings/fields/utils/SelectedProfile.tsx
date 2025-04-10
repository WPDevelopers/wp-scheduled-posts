import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { getFormatDateTime, updateRefreshToken } from '../../helper/helper';
import { SweetAlertToaster } from '../../ToasterMsg';

export default function SelectedProfile({ platform, item, handleSelectedProfileStatusChange, handleDeleteSelectedProfile, handleEditSelectedProfile, profileStatus = false }) {
    // Define a placeholder image URL
    // @ts-ignore 
    const placeholderImage = `${wpspSettingsGlobal?.assets_path}/images/author-logo.jpeg`;

    // Function to handle image load error
    const handleImageError = (e) => {
        e.target.onerror = null; // Prevents infinite loop in case placeholder image fails
        e.target.src = placeholderImage; // Set the placeholder image
    };

    return (
        <div className="profile-item">
            <div className="profile-image">
                {/* @ts-ignore */}
                <img 
                    src={`${item?.thumbnail_url}`} 
                    alt={__(item?.name, 'wp-scheduled-posts')}
                    onError={handleImageError} // Attach the error handler
                />
            </div>
            <div className="profile-data">
                {
                    {
                        facebook: (
                            <span className={`badge facebook-${item.type}`}>{ item.type ? item.type : __('Profile','wp-scheduled-posts') }</span>
                        ),
                        twitter: (
                            <span className={`badge twitter-${item.type}`}>{ item.type ? item.type : __('Profile','wp-scheduled-posts') }</span>
                        ),
                        linkedin: (
                            <span className={`badge linkedin-${item.type}`}>{ item?.type == 'organization' ? __('Page','wp-scheduled-posts') : __('Profile','wp-scheduled-posts')  }</span>
                        ),
                        pinterest: (
                            <span className={`badge pinterest-${item?.account_type?.toLowerCase()}`}>{ item?.account_type ? __('Board','wp-scheduled-posts') : item?.type }</span>
                        ),
                        instagram: (
                            <span className={`badge instagram-${item?.account_type?.toLowerCase()}`}>{ item?.account_type ? __('Profile','wp-scheduled-posts') : item?.type }</span>
                        ),
                        medium: (
                            <span className={`badge medium-profile`}>{ __('Profile', 'wp-scheduled-posts') }</span>
                        ),
                    }[platform]
                }
                <h4> { platform == 'pinterest' ? item?.default_board_name?.label : item?.name }</h4>
                <span>{ item?.added_by?.replace(/^\w/, (c) => c.toUpperCase()) } { __('on','wp-scheduled-posts') } {getFormatDateTime(item?.added_date)}</span>
                <div className="action">
                    <div className="status">
                        { (platform === 'pinterest') && (
                            <div className="switcher">
                                <input
                                    id={item?.default_board_name?.value}
                                    type='checkbox'
                                    className="wprf-switcher-checkbox"
                                    checked={ (profileStatus == true && item?.status) ? true : false }
                                    onChange={(event) => 
                                        handleSelectedProfileStatusChange(item,event)
                                    }
                                />
                                <label
                                    className="wprf-switcher-label"
                                    htmlFor={item?.default_board_name?.value}
                                    style={{ background:  (profileStatus && item?.status) && '#02AC6E' }}
                                >
                                    <span className={`wprf-switcher-button`} />
                                </label>
                            </div>
                        ) }
                        { (platform !== 'pinterest') && (
                            <div className="switcher">
                                <input
                                    id={item?.id}
                                    type='checkbox'
                                    className="wprf-switcher-checkbox"
                                    checked={ ( profileStatus == true && item?.status ) ? true : false }
                                    onChange={(event) => 
                                        handleSelectedProfileStatusChange(item,event)
                                    }
                                />
                                <label
                                    className="wprf-switcher-label"
                                    htmlFor={item?.id}
                                    style={{ background:  (profileStatus && item?.status) && '#02AC6E' }}
                                >
                                    <span className={`wprf-switcher-button`} />
                                </label>
                            </div>
                        ) }
                    </div>
                    { ( platform == 'pinterest' ) && (
                        <div className="edit-profile">
                            <button onClick={ () => handleEditSelectedProfile( item ) }>{ __('Edit','wp-scheduled-posts') }</button>
                        </div>
                    ) }
                    <div className="remove-profile">
                        <button onClick={ () => handleDeleteSelectedProfile( item ) }>{ __('Delete','wp-scheduled-posts') }</button>
                    </div>
                </div>
            </div>
        </div>
    )
}
