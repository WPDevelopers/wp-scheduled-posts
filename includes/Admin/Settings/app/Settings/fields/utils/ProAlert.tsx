import React from 'react'
import { __ } from '@wordpress/i18n'
import { Alert } from '../../components/ui'

function ProAlert() {
  return (
    <Alert tone="warning" className="error-message">
      {__(
        'Multi Profile is a Premium Feature. To use this feature,',
        'wp-scheduled-posts'
      )}
      {' '}
      <a
        target="_blank"
        rel="noopener noreferrer"
        href="https://wpdeveloper.com/in/schedulepress-pro"
        className="tw-text-brand-500 hover:tw-text-brand-700"
      >
        {__('Upgrade to PRO.', 'wp-scheduled-posts')}
      </a>
    </Alert>
  )
}

export default ProAlert
