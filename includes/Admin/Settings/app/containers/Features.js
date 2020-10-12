import React from 'react'
import { __ } from '@wordpress/i18n'
import Upgrade from './../components/Upgrade'
import Support from './../components/Support'
const Features = ({ pluginRootURI, proVersion }) => {
    const assetsURI = pluginRootURI + 'assets/images/'
    const supportData = [
        {
            link:
                'https://wpdeveloper.net/docs/wp-scheduled-posts/how-does-auto-scheduler-work/',
            title: __('Auto Scheduler', 'wp-scheduled-posts'),
            icon: assetsURI + 'auto_scheduler.png',
        },
        {
            link:
                'https://wpdeveloper.net/docs/wp-scheduled-posts/how-does-manual-scheduler-work/',
            title: __('Manual Scheduler', 'wp-scheduled-posts'),
            icon: assetsURI + 'manual_scheduler.png',
        },
        {
            link:
                'https://wpdeveloper.net/docs/wp-scheduled-posts/how-to-handle-the-missed-schedule-error-using-wp-scheduled-post/',
            title: __('Missed Schedule Handler', 'wp-scheduled-posts'),
            icon: assetsURI + 'manual_scheduler_handler.png',
        },
        {
            link: 'https://wpdeveloper.net/support/',
            title: __('Premium Support', 'wp-scheduled-posts'),
            icon: assetsURI + 'premium_support_care.png',
        },
    ]
    return (
        <React.Fragment>
            <Upgrade icon={assetsURI + 'wpsp.png'} proVersion={proVersion} />
            <div className='wpsp_features_lists'>
                <h3>
                    {__('SchedulePress - Pro Features', 'wp-scheduled-posts')}
                </h3>
                <div className='wpsp_support_panel'>
                    {proVersion === '' && (
                        <h4>
                            {__(
                                'In Pro version, You will get following supports:',
                                'wp-scheduled-posts'
                            )}
                        </h4>
                    )}
                    <div className='wpsp_suppurt_lists'>
                        {supportData.map((item, index) => (
                            <Support key={index} {...item} />
                        ))}
                    </div>
                </div>
            </div>
        </React.Fragment>
    )
}
export default Features
