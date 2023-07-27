import React from 'react';

const Html = (props) => {
    return (
        <div className='wprf-control-wrapper wprf-section-html' dangerouslySetInnerHTML={{__html: props.html}}/>
    )
}

export default Html;