import { __ } from '@wordpress/i18n'
import React from 'react'
const Upgrade = ({ icon, proVersion }) => {
    return (
        <React.Fragment>
            <div className='wpsp_features_upgrade'>
                {proVersion !== '' ? (
                    <React.Fragment>
                        <h1 className='wpsp_promo_title'>
                            {__('SchedulePress Pro', 'wp-scheduled-posts')}
                        </h1>
                        <img src={icon} alt='logo' />
                        <h2>{__('SchedulePress Pro', 'wp-scheduled-posts')}</h2>
                        <a href={'https://wpdeveloper.net/account'}>
                            {__('Manage License', 'wp-scheduled-posts')}
                        </a>
                    </React.Fragment>
                ) : (
                    <React.Fragment>
                        <h1 className='wpsp_promo_title'>
                            {__('SchedulePress', 'wp-scheduled-posts')}
                        </h1>
                        <img src={icon} alt='logo' />
                        <h2>{__('SchedulePress', 'wp-scheduled-posts')}</h2>
                        <a
                            href={
                                'http://wpdeveloper.net/in/wp-scheduled-posts'
                            }
                        >
                            {__('UPGRADE TO PRO', 'wp-scheduled-posts')}
                        </a>
                    </React.Fragment>
                )}
            </div>
        </React.Fragment>
    )
}
export default Upgrade
