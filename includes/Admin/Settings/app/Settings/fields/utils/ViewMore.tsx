import React from 'react'
import { __ } from '@wordpress/i18n'
import { Button } from '../../components/ui'

const ViewMore = ( { setSelectedProfileViewMore } ) => {
  return (
    <Button
      variant="link"
      className="view-all tw-mt-1.5 tw-self-end"
      onClick={() => setSelectedProfileViewMore(true)}
      rightIcon={<i className="wpsp-icon wpsp-arrow tw-text-[0.9em]" />}
    >
      {__('View More', 'wp-scheduled-posts')}
    </Button>
  )
}

export default ViewMore
