import React from 'react'
import { __ } from '@wordpress/i18n'

function ProfileLimitNotice() {
  return (
    <>
        <div className='error-message'>
            {__(
                'SchedulePress connects one profile per network.',
                'wp-scheduled-posts'
            )}
        </div>
    </>
  )
}

export default ProfileLimitNotice
