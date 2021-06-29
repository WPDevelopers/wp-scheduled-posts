import React from 'react'
import { __ } from '@wordpress/i18n'

export default function Topbar({ pluginRootUri, freeVersion, proVersion }) {
    return (
        <React.Fragment>
            <div className='wpsp_top_bar_wrapper'>
                <div className='wpsp_top_bar_logo'>
                    <img
                        src={pluginRootUri + 'assets/images/wpsp-icon.svg'}
                        alt={__('logo', 'wp-scheduled-posts')}
                    />
                </div>
                <div className='wpsp_top_bar_heading'>
                    <h2 className='wpsp_topbar_title'>
                        {__(
                            'SchedulePress (Formerly Known as WP Scheduled Posts)',
                            'wp-scheduled-posts'
                        )}
                    </h2>
                    <p className='wpsp_topbar_version_name'>
                        <span>
                            <span className='free'>
                                {__('Core Version: ', 'wp-scheduled-posts')}
                                {freeVersion}
                            </span>
                            <br />
                            {proVersion !== '' && (
                                <span className='pro'>
                                    {__('Pro Version: ', 'wp-scheduled-posts')}
                                    {proVersion}
                                </span>
                            )}
                        </span>
                    </p>
                </div>
            </div>
        </React.Fragment>
    )
}
