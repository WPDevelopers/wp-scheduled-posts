import React from 'react'

const ViewMore = ( { setSelectedProfileViewMore } ) => {
  return (
    <button className='view-all' onClick={() => setSelectedProfileViewMore(true)}>View More</button>
  )
}

export default ViewMore