import React from 'react'
import { __ } from '@wordpress/i18n'
const Error = () => {
    return (
        <div>
            <pre className='error'>
                {__(
                    'Type error: you are typing wrong input type',
                    'wp-scheduled-posts'
                )}
            </pre>
        </div>
    )
}

export default Error
