import React from 'react'

const RawHTML = ({ id, content }) => {
    return (
        <div className='form-group rawhtml'>
            <div
                className='form-info'
                dangerouslySetInnerHTML={{ __html: content }}
            ></div>
        </div>
    )
}

export default RawHTML
