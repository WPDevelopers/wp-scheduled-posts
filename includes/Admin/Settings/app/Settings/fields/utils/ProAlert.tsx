import React from 'react'
import { __ } from '@wordpress/i18n'

function ProAlert() {
  return (
    <>
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
    </>
  )
}

export default ProAlert