import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { getFormatDateTime, getProfileExpiry, handleImageError, runReconnect } from '../../helper/helper';

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
        if ( result?.callbackSearch ) {
            // Finish on the screen's normal connect handler.
            window.location.search = result.callbackSearch;
            return;
        }
        setReconnecting( false );
        if ( result?.renewed?.length ) {
            window.location.reload();
        }
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
                        bluesky: (
                            <span className={`badge bluesky-profile`}>{ __('Profile', 'wp-scheduled-posts') }</span>
                        ),
                        mastodon: (
                            <span className={`badge mastodon-profile`}>{ __('Profile', 'wp-scheduled-posts') }</span>
                        ),
                    }[platform]
                }
                <h4> { platform == 'pinterest' ? item?.default_board_name?.label : item?.name }</h4>
                <span>{ item?.added_by?.replace(/^\w/, (c) => c.toUpperCase()) } { __('on','wp-scheduled-posts') } {getFormatDateTime(item?.added_date)}</span>
                { expiry && (
                    <span className={ `profile-expiry${ expiry.needsAttention ? ' is-expired' : ( expiry.autoRenews ? '' : ( expiry.daysLeft <= 7 ? ' is-expiring' : '' ) ) }` }>
                        { expiry.needsAttention
                            ? `${ __('Connection expired on','wp-scheduled-posts') } ${ getFormatDateTime( expiry.date ) } — ${ __('reconnect to keep sharing','wp-scheduled-posts') }`
                            : ( expiry.autoRenews
                                ? __('Connection renews automatically','wp-scheduled-posts')
                                : `${ __('Connection expires on','wp-scheduled-posts') } ${ getFormatDateTime( expiry.date ) } (${ expiry.daysLeft } ${ expiry.daysLeft === 1 ? __('day','wp-scheduled-posts') : __('days','wp-scheduled-posts') } ${ __('left','wp-scheduled-posts') })` ) }
                    </span>
                ) }
                <div className="connection-status">
                    <span
                        className={ `connection-indicator${ isExpired ? ' is-expired' : ' is-active' }` }
                        tabIndex={0}
                        aria-label={ isExpired ? __('Connection expired','wp-scheduled-posts') : __('Connection active','wp-scheduled-posts') }
                    >
                        { isExpired ? (
                            // Slashed circle — reads as "disabled" rather than as an
                            // error, so it does not compete with the red expiry line.
                            <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                <circle cx="8" cy="8" r="6.4" fill="none" stroke="currentColor" strokeWidth="1.6" />
                                <line x1="3.9" y1="12.1" x2="12.1" y2="3.9" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
                            </svg>
                        ) : (
                            <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                <circle cx="8" cy="8" r="7" fill="currentColor" />
                                <path d="M4.6 8.2 6.9 10.5 11.4 6" fill="none" stroke="#fff" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
                            </svg>
                        ) }
                        <span className="connection-tooltip">
                            { isExpired
                                ? __('Connection expired','wp-scheduled-posts')
                                : __('Connection active','wp-scheduled-posts') }
                        </span>
                    </span>
                    { isExpired && (
                        <button
                            type="button"
                            className={ `reconnect-profile${ reconnecting ? ' is-busy' : '' }` }
                            aria-label={ __('Reconnect','wp-scheduled-posts') }
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
                                    : ( reconnectTargets.length > 1
                                        ? `${ __('Reconnect all expired','wp-scheduled-posts') } (${ reconnectTargets.length })`
                                        : __('Reconnect','wp-scheduled-posts') ) }
                            </span>
                        </button>
                    ) }
                </div>
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
