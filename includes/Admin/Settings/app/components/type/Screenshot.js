import React from 'react'
import { __ } from '@wordpress/i18n'
const Screenshot = ({
    id,
    title,
    src,
}) => {
    return (
        <div id={id}>
            <img title={title} src={src} />
        </div>
    )
}

export default Screenshot
