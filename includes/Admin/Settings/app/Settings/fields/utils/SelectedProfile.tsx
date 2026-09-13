import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { getFormatDateTime, getProfileExpiry, handleImageError, runReconnect } from '../../helper/helper';
import { SweetAlertToaster } from '../../ToasterMsg';

export default function SelectedProfile({ platform, item, handleSelectedProfileStatusChange, handleDeleteSelectedProfile, handleEditSelectedProfile, handleReconnectProfile = null, allProfiles = [], profileStatus = false }) {
    // Null for the platforms whose credentials never expire on a timer, so those
    // profiles simply show nothing rather than a misleading "never expires".
    const expiry = getProfileExpiry( item );
    // A profile with no stored expiry cannot have lapsed on a timer, so it counts
    // as live — otherwise Twitter, Facebook, Bluesky and Mastodon would be the
    // only rows in the list with no status indicator at all.
    // renewal_failed can be set on a profile whose clock has not run out yet, so
    // it is read directly rather than only through the expiry helper.
    const isExpired = expiry ? expiry.needsAttention : !! item?.renewal_failed;
    const [reconnecting, setReconnecting] = useState(false);

    // Every expired profile on the card is reconnected together, so one click
    // clears the whole card rather than needing one press per row. The parent
    // hands over its full list and the filtering happens here, so the ten
    // platform components do not each need their own copy of the expiry rule.
    const expiredOnCard = ( allProfiles || [] ).filter(( profile ) => {
        const profileExpiry = getProfileExpiry( profile );
        return profileExpiry ? profileExpiry.needsAttention : !! profile?.renewal_failed;
    });
    const reconnectTargets = expiredOnCard.length
        ? expiredOnCard.map(( profile ) => ({ platform, item: profile }))
        : [ { platform, item } ];

    const onReconnect = async () => {
        if ( reconnecting ) {
            return;
        }
        // Let the host override the whole behaviour if it wants to.
        if ( handleReconnectProfile ) {
            handleReconnectProfile( item, platform, reconnectTargets );
            return;
        }
        setReconnecting( true );
        const result = await runReconnect( reconnectTargets );
        setReconnecting( false );
        if ( result?.renewed?.length ) {
            // The stored profiles changed underneath the screen, so re-read them
            // rather than leaving stale tokens on display.
            window.location.reload();
            return;
        }
        // Nothing was renewed. Saying so beats leaving the button looking as if
        // the click never happened — the author has just been through a consent
        // screen and deserves to know it did not take.
        if ( result?.cancelled ) {
            return;
        }
        SweetAlertToaster({
            type: 'error',
            title: result?.failed?.[0]?.message
                ?? result?.message
                ?? __( 'Could not reconnect this profile. Please try again.', 'wp-scheduled-posts' ),
        }).fire();
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
                {/* Badge and reconnect button share one anchor in the card's top
                    right corner, so the button sits beside the profile type. */}
                <div className="profile-meta">
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
                        bluesky: (
                            <span className={`badge bluesky-profile`}>{ __('Profile', 'wp-scheduled-posts') }</span>
                        ),
                        mastodon: (
                            <span className={`badge mastodon-profile`}>{ __('Profile', 'wp-scheduled-posts') }</span>
                        ),
                    }[platform]
                }
                { isExpired && (
                    <button
                        type="button"
                        className={ `reconnect-profile${ reconnecting ? ' is-busy' : '' }` }
                        aria-label={ __('Connection expired','wp-scheduled-posts') }
                        disabled={ reconnecting }
                        onClick={ onReconnect }
                    >
                        <svg width="15" height="15" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                            <path d="M13.3 8a5.3 5.3 0 1 1-1.6-3.8" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
                            <path d="M13.5 2.2v3.1h-3.1" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                        <span className="connection-tooltip">
                            { reconnecting
                                ? __('Reconnecting…','wp-scheduled-posts')
                                : __('Connection expired','wp-scheduled-posts') }
                        </span>
                    </button>
                ) }
                </div>
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
