import React from 'react'

const ViewMore = ( { setSelectedProfileViewMore } ) => {
  return (
    <button onClick={() => setSelectedProfileViewMore(true)}>View More</button>
  )
}

export default ViewMore